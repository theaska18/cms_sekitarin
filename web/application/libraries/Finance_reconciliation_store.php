<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped bank statement reconciliation drafts and completion checks. */
class Finance_reconciliation_store
{
    private $db;

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            $db = $CI->db;
        }
        $this->db = $db;
    }

    public function available()
    {
        return $this->db->table_exists('fin_bank_reconciliation')
            && $this->db->table_exists('fin_bank_reconciliation_line')
            && $this->db->table_exists('fin_cash_account')
            && $this->db->table_exists('fin_journal_entry')
            && $this->db->table_exists('fin_journal_line');
    }

    public function count($tenant, $search = '')
    {
        if (!$this->available()) return FALSE;
        $this->db->from('fin_bank_reconciliation')->where('tenant_id', (int) $tenant);
        if ($search !== '') $this->db->like('statement_reference', $search);
        $n = $this->db->count_all_results();
        return $n === FALSE ? FALSE : (int) $n;
    }

    public function page($tenant, $search, $limit, $offset, $order, $direction)
    {
        if (!$this->available()) return FALSE;
        $allowed = array('statement_date','statement_reference','closing_balance','status');
        $order = in_array($order,$allowed,TRUE)?$order:'statement_date';
        $this->db->select('reconciliation_id,cash_account_id,statement_date,statement_reference,opening_balance,closing_balance,status')
            ->from('fin_bank_reconciliation')->where('tenant_id',(int)$tenant);
        if($search!=='')$this->db->like('statement_reference',$search);
        $q=$this->db->order_by($order,strtoupper($direction)==='ASC'?'ASC':'DESC')
            ->order_by('reconciliation_id','DESC')->limit(min(50,max(1,(int)$limit)),max(0,(int)$offset))->get();
        return $q===FALSE?FALSE:$q->result_array();
    }

    public function find($tenant,$id)
    {
        if(!$this->available())return FALSE;
        $q=$this->db->where('tenant_id',(int)$tenant)->where('reconciliation_id',(int)$id)->limit(1)->get('fin_bank_reconciliation');
        if($q===FALSE)return FALSE;if(!$q->num_rows())return NULL;
        $row=$q->row_array();
        $lines=$this->db->where('tenant_id',(int)$tenant)->where('reconciliation_id',(int)$id)->order_by('statement_on','ASC')->get('fin_bank_reconciliation_line');
        if($lines===FALSE)return FALSE;$row['lines']=$lines->result_array();return $row;
    }

    public function cash_label($tenant,$id)
    {
        $q=$this->db->select('account_code,account_name,currency_code')->where('tenant_id',(int)$tenant)
            ->where('cash_account_id',(int)$id)->limit(1)->get('fin_cash_account');
        if($q===FALSE||!$q->num_rows())return '';
        $r=$q->row();return $r->account_code.' — '.$r->account_name.' ('.$r->currency_code.')';
    }

    public function line_label($tenant,$id)
    {
        $q=$this->db->select('je.journal_date,je.journal_number,jl.description,jl.currency_code,jl.debit_amount,jl.credit_amount')
            ->from('fin_journal_line jl')->join('fin_journal_entry je','je.journal_entry_id=jl.journal_entry_id AND je.tenant_id=jl.tenant_id')
            ->where('jl.tenant_id',(int)$tenant)->where('jl.journal_line_id',(int)$id)->limit(1)->get();
        if($q===FALSE||!$q->num_rows())return '';
        $r=$q->row();$amount=(float)$r->debit_amount-(float)$r->credit_amount;
        return $r->journal_date.' — '.$r->journal_number.' — '.$r->description.' — '.$r->currency_code.' '.number_format($amount,2,'.','');
    }

    public function lookup_lines($tenant,$cash_id,$term)
    {
        if(strlen($term)<2||(int)$cash_id<1)return array();
        $cash=$this->db->where('tenant_id',(int)$tenant)->where('cash_account_id',(int)$cash_id)->where('active_flag',1)->limit(1)->get('fin_cash_account');
        if($cash===FALSE||!$cash->num_rows())return array();
        $cash_account_id=(int)$cash->row()->account_id;
        $this->db->select('jl.journal_line_id,je.journal_number,je.journal_date,jl.description,jl.debit_amount,jl.credit_amount,jl.currency_code')
            ->from('fin_journal_line jl')->join('fin_journal_entry je','je.journal_entry_id=jl.journal_entry_id AND je.tenant_id=jl.tenant_id')
            ->join('fin_bank_reconciliation_line rl','rl.journal_line_id=jl.journal_line_id AND rl.tenant_id=jl.tenant_id AND rl.matched_flag=1','left')
            ->where('jl.tenant_id',(int)$tenant)->where('jl.account_id',$cash_account_id)->where('je.status','posted')
            ->where('rl.reconciliation_line_id IS NULL',NULL,FALSE)
            ->group_start()->like('je.journal_number',$term)->or_like('jl.description',$term)->group_end();
        $q=$this->db->order_by('je.journal_date','DESC')->limit(20)->get();
        $results=array();
        if($q!==FALSE)foreach($q->result_array() as $row){
            $amount=(float)$row['debit_amount']-(float)$row['credit_amount'];
            $label=$row['journal_date'].' — '.$row['journal_number'].' — '.$row['description'].' — '.$row['currency_code'].' '.number_format($amount,2,'.','');
            $results[]=array('id'=>(int)$row['journal_line_id'],'label'=>$label,'value'=>$label,'amount'=>$amount,'date'=>$row['journal_date']);
        }
        return $results;
    }

    public function save_draft($tenant,$user,$id,$values)
    {
        $cash=$this->db->where('tenant_id',(int)$tenant)->where('cash_account_id',(int)$values['cash_account_id'])->where('active_flag',1)->limit(1)->get('fin_cash_account');
        if($cash===FALSE||!$cash->num_rows())return 'cash_account_invalid';
        $lines=is_array($values['lines'])?$values['lines']:array();
        if(!$lines||count($lines)>100)return 'lines_invalid';
        $clean=array();$sum=0.0;
        $seen=array();
        $cash_currency=(string)$cash->row()->currency_code;
        foreach($lines as $line){
            if(isset($seen[(int)$line['journal_line_id']]))return 'duplicate_journal_line';
            $seen[(int)$line['journal_line_id']]=TRUE;
            $existing_match=$this->db->where('tenant_id',(int)$tenant)->where('journal_line_id',(int)$line['journal_line_id'])->where('matched_flag',1);
            if($id!==NULL)$existing_match->where('reconciliation_id !=',(int)$id);
            $existing_match=$existing_match->limit(1)->get('fin_bank_reconciliation_line');
            if($existing_match===FALSE||$existing_match->num_rows())return 'journal_line_already_matched';
            $journal=$this->db->select('jl.journal_line_id,jl.account_id,jl.debit_amount,jl.credit_amount,jl.currency_code,je.journal_date')
                ->from('fin_journal_line jl')->join('fin_journal_entry je','je.journal_entry_id=jl.journal_entry_id AND je.tenant_id=jl.tenant_id')
                ->where('jl.tenant_id',(int)$tenant)->where('jl.journal_line_id',(int)$line['journal_line_id'])
                ->where('jl.account_id',(int)$cash->row()->account_id)->where('je.status','posted')->limit(1)->get();
            if($journal===FALSE||!$journal->num_rows())return 'journal_line_invalid';
            $j=$journal->row_array();$amount=round((float)$line['statement_amount'],6);
            $expected=round((float)$j['debit_amount']-(float)$j['credit_amount'],6);
            if($j['currency_code']!==$cash_currency||$j['journal_date']>$values['statement_date']
                ||abs($amount-$expected)>0.000001)return 'statement_amount_mismatch';
            $sum+=$amount;$clean[]=array('journal_line_id'=>(int)$j['journal_line_id'],'statement_on'=>$line['statement_on'],
                'description'=>substr((string)$line['description'],0,240),'statement_amount'=>$amount);
        }
        if(abs(round((float)$values['opening_balance']+$sum,6)-(float)$values['closing_balance'])>0.000001)return 'closing_balance_mismatch';
        $data=array('cash_account_id'=>(int)$values['cash_account_id'],'statement_date'=>$values['statement_date'],
            'statement_reference'=>$values['statement_reference'],'opening_balance'=>(float)$values['opening_balance'],
            'closing_balance'=>(float)$values['closing_balance']);
        $this->db->trans_begin();
        if($id!==NULL){
            $existing=$this->find($tenant,$id);
            if(!is_array($existing)||$existing['status']!=='draft'){$this->db->trans_rollback();return 'not_editable';}
            $this->db->where('tenant_id',(int)$tenant)->where('reconciliation_id',(int)$id)->update('fin_bank_reconciliation',$data);
            $recon_id=(int)$id;$this->db->where('tenant_id',(int)$tenant)->where('reconciliation_id',$recon_id)->delete('fin_bank_reconciliation_line');
        }else{
            $data['tenant_id']=(int)$tenant;$data['status']='draft';$data['create_on']=date('Y-m-d H:i:s');$data['created_by']=(int)$user;
            $this->db->insert('fin_bank_reconciliation',$data);$recon_id=(int)$this->db->insert_id();
            if(!$recon_id){$this->db->trans_rollback();return 'save_error';}
        }
        foreach($clean as $line){$line['tenant_id']=(int)$tenant;$line['reconciliation_id']=$recon_id;$line['matched_flag']=1;$this->db->insert('fin_bank_reconciliation_line',$line);}
        if($this->db->trans_status()===FALSE){$this->db->trans_rollback();return 'save_error';}
        $this->db->trans_commit();return 'saved';
    }

    public function complete($tenant,$user,$id)
    {
        $row=$this->find($tenant,$id);if(!is_array($row))return $row===NULL?'not_found':'unavailable';
        if($row['status']!=='draft'||!$row['lines'])return 'not_completable';
        $sum=0.0;foreach($row['lines'] as $line){if((int)$line['matched_flag']!==1)return 'unmatched_lines';$sum+=(float)$line['statement_amount'];}
        if(abs(round((float)$row['opening_balance']+$sum,6)-(float)$row['closing_balance'])>0.000001)return 'closing_balance_mismatch';
        $this->db->where('tenant_id',(int)$tenant)->where('reconciliation_id',(int)$id)->where('status','draft')
            ->update('fin_bank_reconciliation',array('status'=>'completed','completed_on'=>date('Y-m-d H:i:s'),'completed_by'=>(int)$user));
        return $this->db->trans_status()===FALSE?'complete_error':'completed';
    }
}
