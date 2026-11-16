<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Cash/bank transfers; cross-currency posting requires equal base-currency values. */
class Finance_transfer_store
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
        return $this->db->table_exists('fin_cash_transfer') && $this->db->table_exists('fin_cash_account')
            && $this->db->table_exists('fin_account') && $this->db->table_exists('fin_journal_entry')
            && $this->db->table_exists('fin_journal_line') && $this->db->table_exists('fin_accounting_period')
            && $this->db->table_exists('fin_settings');
    }

    public function count($tenant, $search = '')
    {
        if (!$this->available()) return FALSE;
        $this->db->from('fin_cash_transfer')->where('tenant_id', (int) $tenant);
        if ($search !== '') $this->db->group_start()->like('transfer_number', $search)->or_like('memo', $search)->group_end();
        $n = $this->db->count_all_results();
        return $n === FALSE ? FALSE : (int) $n;
    }

    public function page($tenant, $search, $limit, $offset, $order, $direction)
    {
        if (!$this->available()) return FALSE;
        $allowed = array('transfer_number','transfer_date','from_amount','to_amount','status');
        $order = in_array($order, $allowed, TRUE) ? $order : 'transfer_date';
        $this->db->select('cash_transfer_id,transfer_number,transfer_date,from_amount,to_amount,from_currency,to_currency,status,memo')
            ->from('fin_cash_transfer')->where('tenant_id', (int) $tenant);
        if ($search !== '') $this->db->group_start()->like('transfer_number', $search)->or_like('memo', $search)->group_end();
        $q=$this->db->order_by($order,strtoupper($direction)==='ASC'?'ASC':'DESC')
            ->order_by('cash_transfer_id','DESC')->limit(min(50,max(1,(int)$limit)),max(0,(int)$offset))->get();
        return $q===FALSE?FALSE:$q->result_array();
    }

    public function find($tenant, $id)
    {
        if (!$this->available()) return FALSE;
        $q=$this->db->where('tenant_id',(int)$tenant)->where('cash_transfer_id',(int)$id)->limit(1)->get('fin_cash_transfer');
        return $q===FALSE?FALSE:($q->num_rows()?$q->row_array():NULL);
    }

    public function lookup_cash($tenant, $term)
    {
        if (strlen($term)<2) return array();
        $q=$this->db->select('cash_account_id,account_code,account_name,currency_code')->where('tenant_id',(int)$tenant)
            ->where('active_flag',1)->group_start()->like('account_code',$term)->or_like('account_name',$term)->group_end()
            ->order_by('account_code','ASC')->limit(20)->get('fin_cash_account');
        $result=array();
        if($q!==FALSE) foreach($q->result_array() as $row) {
            $label=$row['account_code'].' — '.$row['account_name'].' ('.$row['currency_code'].')';
            $result[]=array('id'=>(int)$row['cash_account_id'],'label'=>$label,'value'=>$label,'currency_code'=>$row['currency_code']);
        }
        return $result;
    }

    public function cash_label($tenant,$id)
    {
        $row=$this->tenant_row('fin_cash_account','cash_account_id',$tenant,$id);
        return $row?$row['account_code'].' — '.$row['account_name'].' ('.$row['currency_code'].')':'';
    }

    public function save_draft($tenant,$user,$id,$values)
    {
        $from=$this->tenant_row('fin_cash_account','cash_account_id',$tenant,$values['from_cash_account_id']);
        $to=$this->tenant_row('fin_cash_account','cash_account_id',$tenant,$values['to_cash_account_id']);
        if(!$from||!$to||(int)$from['active_flag']!==1||(int)$to['active_flag']!==1||(int)$from['cash_account_id']===(int)$to['cash_account_id'])return 'account_invalid';
        $from_gl=$this->tenant_row('fin_account','account_id',$tenant,$from['account_id']);
        $to_gl=$this->tenant_row('fin_account','account_id',$tenant,$to['account_id']);
        if(!$from_gl||!$to_gl||(int)$from_gl['active_flag']!==1||(int)$to_gl['active_flag']!==1||$from_gl['account_type']!=='asset'||$to_gl['account_type']!=='asset')return 'account_invalid';
        $settings=$this->db->where('tenant_id',(int)$tenant)->limit(1)->get('fin_settings');
        if($settings===FALSE||!$settings->num_rows())return 'settings_missing';
        $base=strtoupper((string)$settings->row()->base_currency);
        $from_rate=(float)$values['from_exchange_rate'];$to_rate=(float)$values['to_exchange_rate'];
        $from_amount=round((float)$values['from_amount'],6);$to_amount=round((float)$values['to_amount'],6);
        if($from_amount<=0||$to_amount<=0||$from_rate<=0||$to_rate<=0)return 'amount_invalid';
        if(($from['currency_code']===$base&&abs($from_rate-1)>0.0000001)||($to['currency_code']===$base&&abs($to_rate-1)>0.0000001))return 'exchange_rate_invalid';
        $base_from=round($from_amount*$from_rate,6);$base_to=round($to_amount*$to_rate,6);
        if(abs($base_from-$base_to)>0.000001)return 'fx_imbalance';
        $data=array('from_cash_account_id'=>(int)$from['cash_account_id'],'to_cash_account_id'=>(int)$to['cash_account_id'],
            'transfer_date'=>$values['transfer_date'],'from_currency'=>$from['currency_code'],'to_currency'=>$to['currency_code'],
            'from_exchange_rate'=>$from_rate,'to_exchange_rate'=>$to_rate,'from_amount'=>$from_amount,'to_amount'=>$to_amount,
            'fee_amount'=>0,'base_from_amount'=>$base_from,'base_to_amount'=>$base_to,'memo'=>$values['memo']);
        $this->db->trans_begin();
        if($id!==NULL){
            $existing=$this->find($tenant,$id);
            if(!is_array($existing)||$existing['status']!=='draft'){$this->db->trans_rollback();return 'not_editable';}
            $this->db->where('tenant_id',(int)$tenant)->where('cash_transfer_id',(int)$id)->update('fin_cash_transfer',$data);
        }else{
            $data['tenant_id']=(int)$tenant;$data['transfer_number']='TMP-'.bin2hex(random_bytes(6));$data['status']='draft';
            $data['create_on']=date('Y-m-d H:i:s');$data['created_by']=(int)$user;
            $this->db->insert('fin_cash_transfer',$data);$id=(int)$this->db->insert_id();
            if(!$id){$this->db->trans_rollback();return 'save_error';}
            $this->db->where('tenant_id',(int)$tenant)->where('cash_transfer_id',$id)->update('fin_cash_transfer',array('transfer_number'=>'TRF-'.date('Ymd').'-'.$id));
        }
        if($this->db->trans_status()===FALSE){$this->db->trans_rollback();return 'save_error';}
        $this->db->trans_commit();return 'saved';
    }

    public function post($tenant,$user,$id)
    {
        $row=$this->find($tenant,$id);
        if(!is_array($row))return $row===NULL?'not_found':'unavailable';
        if($row['status']!=='draft')return 'not_postable';
        $period=$this->db->where('tenant_id',(int)$tenant)->where('start_on <=',$row['transfer_date'])
            ->where('end_on >=',$row['transfer_date'])->where('status','open')->get('fin_accounting_period');
        if($period===FALSE||$period->num_rows()!==1)return $period&&$period->num_rows()>1?'period_ambiguous':'period_closed';
        $from=$this->tenant_row('fin_cash_account','cash_account_id',$tenant,$row['from_cash_account_id']);
        $to=$this->tenant_row('fin_cash_account','cash_account_id',$tenant,$row['to_cash_account_id']);
        $from_gl=$from?$this->tenant_row('fin_account','account_id',$tenant,$from['account_id']):NULL;
        $to_gl=$to?$this->tenant_row('fin_account','account_id',$tenant,$to['account_id']):NULL;
        if(!$from||!$to||!$from_gl||!$to_gl||(int)$from['active_flag']!==1||(int)$to['active_flag']!==1
            ||(int)$from_gl['active_flag']!==1||(int)$to_gl['active_flag']!==1
            ||$from_gl['account_type']!=='asset'||$to_gl['account_type']!=='asset'
            ||$from['currency_code']!==$row['from_currency']||$to['currency_code']!==$row['to_currency'])return 'account_invalid';
        $debit=(float)$row['base_to_amount'];$credit=(float)$row['base_from_amount'];
        if(abs($debit-$credit)>0.000001)return 'fx_imbalance';
        $this->db->trans_begin();
        $dup=$this->db->where('tenant_id',(int)$tenant)->where('source_type','cash_transfer')->where('source_id',(int)$id)->limit(1)->get('fin_journal_entry');
        if($dup===FALSE||$dup->num_rows()){$this->db->trans_rollback();return 'already_posted';}
        $this->db->insert('fin_journal_entry',array('tenant_id'=>(int)$tenant,'period_id'=>(int)$period->row()->period_id,
            'journal_number'=>'TRF-'.$id,'journal_date'=>$row['transfer_date'],'source_type'=>'cash_transfer','source_id'=>(int)$id,
            'description'=>'Posted '.$row['transfer_number'],'status'=>'posted','debit_total'=>$debit,'credit_total'=>$credit,
            'create_on'=>date('Y-m-d H:i:s'),'created_by'=>(int)$user,'posted_on'=>date('Y-m-d H:i:s'),'posted_by'=>(int)$user));
        $journal=(int)$this->db->insert_id();
        $lines=array(
            array('account_id'=>(int)$to_gl['account_id'],'currency'=>$row['to_currency'],'rate'=>$row['to_exchange_rate'],'debit'=>(float)$row['to_amount'],'credit'=>0,'base_debit'=>$debit,'base_credit'=>0),
            array('account_id'=>(int)$from_gl['account_id'],'currency'=>$row['from_currency'],'rate'=>$row['from_exchange_rate'],'debit'=>0,'credit'=>(float)$row['from_amount'],'base_debit'=>0,'base_credit'=>$credit),
        );
        foreach($lines as $i=>$line)$this->db->insert('fin_journal_line',array('tenant_id'=>(int)$tenant,'journal_entry_id'=>$journal,'account_id'=>$line['account_id'],'line_number'=>$i+1,
            'description'=>$row['transfer_number'],'currency_code'=>$line['currency'],'exchange_rate'=>$line['rate'],'debit_amount'=>$line['debit'],'credit_amount'=>$line['credit'],
            'debit_base_amount'=>$line['base_debit'],'credit_base_amount'=>$line['base_credit']));
        $this->db->where('tenant_id',(int)$tenant)->where('cash_transfer_id',(int)$id)->where('status','draft')
            ->update('fin_cash_transfer',array('status'=>'posted','posted_on'=>date('Y-m-d H:i:s'),'posted_by'=>(int)$user));
        if(!$journal||$this->db->trans_status()===FALSE){$this->db->trans_rollback();return 'post_error';}
        $this->db->trans_commit();return 'posted';
    }

    private function tenant_row($table,$column,$tenant,$id)
    {
        $q=$this->db->where('tenant_id',(int)$tenant)->where($column,(int)$id)->limit(1)->get($table);
        return $q!==FALSE&&$q->num_rows()?$q->row_array():NULL;
    }
}
