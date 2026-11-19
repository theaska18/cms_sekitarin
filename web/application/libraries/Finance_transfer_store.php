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

    public function count($tenant, $search = '', $filters = array())
    {
        if (!$this->available()) return FALSE;
        $this->db->from('fin_cash_transfer')->where('tenant_id', (int) $tenant);
        if (!empty($filters['start_on'])) $this->db->where('transfer_date >=', $filters['start_on']);
        if (!empty($filters['end_on'])) $this->db->where('transfer_date <=', $filters['end_on']);
        if (!empty($filters['status'])) $this->db->where('status', $filters['status']);
        if ($search !== '') $this->db->group_start()->like('transfer_number', $search)->or_like('memo', $search)->group_end();
        $n = $this->db->count_all_results();
        return $n === FALSE ? FALSE : (int) $n;
    }

    public function page($tenant, $search, $limit, $offset, $order, $direction, $filters = array())
    {
        if (!$this->available()) return FALSE;
        $allowed = array('transfer_number','transfer_date','create_on','from_amount','to_amount','status','created_by_name','from_account_name','to_account_name');
        $order = in_array($order, $allowed, TRUE) ? $order : 'transfer_date';
        $revise_note = "(SELECT a.note FROM app_approval_action a"
            . " WHERE a.tenant_id = t.tenant_id AND a.request_id = t.approval_request_id"
            . " AND a.decision = 'revise' ORDER BY a.action_id DESC LIMIT 1) AS revise_note";
        $this->db->select('t.cash_transfer_id,t.transfer_number,t.transfer_date,t.create_on,t.from_amount,t.to_amount,t.from_currency,t.to_currency,t.status,t.memo,t.approval_request_id,t.from_cash_account_id,t.to_cash_account_id,'
            . "COALESCE(NULLIF(u.full_name, ''), u.username) AS created_by_name,"
            . "COALESCE(NULLIF(au.full_name, ''), au.username) AS current_approver_name,"
            . "CONCAT(fa.account_code, ' — ', fa.account_name) AS from_account_name,"
            . "CONCAT(ta.account_code, ' — ', ta.account_name) AS to_account_name,"
            . $revise_note, FALSE)
            ->from('fin_cash_transfer t')
            ->join('fin_cash_account fa', 'fa.cash_account_id = t.from_cash_account_id AND fa.tenant_id = t.tenant_id', 'left')
            ->join('fin_cash_account ta', 'ta.cash_account_id = t.to_cash_account_id AND ta.tenant_id = t.tenant_id', 'left')
            ->join('app_user u', 'u.user_id = t.created_by AND u.tenant_id = t.tenant_id', 'left')
            ->join('app_approval_request ar', 'ar.request_id = t.approval_request_id AND ar.tenant_id = t.tenant_id', 'left')
            ->join('app_user au', 'au.user_id = ar.current_approver_user_id AND au.tenant_id = ar.tenant_id', 'left')
            ->where('t.tenant_id', (int) $tenant);
        if (!empty($filters['start_on'])) $this->db->where('t.transfer_date >=', $filters['start_on']);
        if (!empty($filters['end_on'])) $this->db->where('t.transfer_date <=', $filters['end_on']);
        if (!empty($filters['status'])) $this->db->where('t.status', $filters['status']);
        if ($search !== '') $this->db->group_start()->like('t.transfer_number', $search)->or_like('t.memo', $search)->group_end();
        $order_column = in_array($order, array('created_by_name', 'from_account_name', 'to_account_name'), TRUE) ? $order : 't.' . $order;
        $q=$this->db->order_by($order_column,strtoupper($direction)==='ASC'?'ASC':'DESC')
            ->order_by('t.cash_transfer_id','DESC')->limit(min(50,max(1,(int)$limit)),max(0,(int)$offset))->get();
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
        $this->db->select('cash_account_id,account_code,account_name,currency_code')->where('tenant_id',(int)$tenant)
            ->where('active_flag',1);
        if ($term !== '') {
            $this->db->group_start()->like('account_code',$term)->or_like('account_name',$term)->group_end();
        }
        $q=$this->db->order_by('account_code','ASC')->limit(20)->get('fin_cash_account');
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
        if(!$from_gl||!$to_gl||(int)$from_gl['active_flag']!==1||(int)$from_gl['posting_flag']!==1||(int)$to_gl['active_flag']!==1||(int)$to_gl['posting_flag']!==1||$from_gl['account_type']!=='asset'||$to_gl['account_type']!=='asset')return 'account_invalid';
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
            if(!is_array($existing)||!in_array($existing['status'],array('draft','revise'),TRUE)){$this->db->trans_rollback();return 'not_editable';}
            $this->db->where('tenant_id',(int)$tenant)->where('cash_transfer_id',(int)$id)->update('fin_cash_transfer',$data);
        }else{
            $CI =& get_instance();
            $CI->load->library('application_sequence_store');
            $next_code = $CI->application_sequence_store->next_code_for_tenant($tenant, 'transfer_code');
            if (!is_array($next_code) || !isset($next_code['code']) || $next_code['code'] === '') {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
            for ($attempt = 0; $attempt < 10 && $this->transfer_number_exists($tenant, (string) $next_code['code']); $attempt++) {
                $next_code = $CI->application_sequence_store->next_code_after($next_code);
                if (!is_array($next_code) || !isset($next_code['code'])) {
                    $this->db->trans_rollback();
                    return 'sequence_unavailable';
                }
            }
            if ($this->transfer_number_exists($tenant, (string) $next_code['code'])) {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
            $data['tenant_id']=(int)$tenant;$data['transfer_number']=(string)$next_code['code'];$data['status']='draft';
            $data['create_on']=app_now();$data['created_by']=(int)$user;
            $this->db->insert('fin_cash_transfer',$data);$id=(int)$this->db->insert_id();
            if(!$id || !$CI->application_sequence_store->advance_sequence($tenant, 'transfer_code', $next_code)){
                $this->db->trans_rollback();return 'sequence_unavailable';
            }
        }
        if($this->db->trans_status()===FALSE){$this->db->trans_rollback();return 'save_error';}
        $this->db->trans_commit();return 'saved';
    }

    public function post($tenant,$user,$id)
    {
        $row=$this->find($tenant,$id);
        if(!is_array($row))return $row===NULL?'not_found':'unavailable';
        if($row['status']!=='approved')return 'not_postable';
        $period=$this->db->where('tenant_id',(int)$tenant)->where('start_on <=',$row['transfer_date'])
            ->where('end_on >=',$row['transfer_date'])->where('status','open')->get('fin_accounting_period');
        if($period===FALSE||$period->num_rows()!==1)return $period&&$period->num_rows()>1?'period_ambiguous':'period_closed';
        $from=$this->tenant_row('fin_cash_account','cash_account_id',$tenant,$row['from_cash_account_id']);
        $to=$this->tenant_row('fin_cash_account','cash_account_id',$tenant,$row['to_cash_account_id']);
        $from_gl=$from?$this->tenant_row('fin_account','account_id',$tenant,$from['account_id']):NULL;
        $to_gl=$to?$this->tenant_row('fin_account','account_id',$tenant,$to['account_id']):NULL;
        if(!$from||!$to||!$from_gl||!$to_gl||(int)$from['active_flag']!==1||(int)$to['active_flag']!==1
            ||(int)$from_gl['active_flag']!==1||(int)$from_gl['posting_flag']!==1||(int)$to_gl['active_flag']!==1||(int)$to_gl['posting_flag']!==1
            ||$from_gl['account_type']!=='asset'||$to_gl['account_type']!=='asset'
            ||$from['currency_code']!==$row['from_currency']||$to['currency_code']!==$row['to_currency'])return 'account_invalid';
        $debit=(float)$row['base_to_amount'];$credit=(float)$row['base_from_amount'];
        if(abs($debit-$credit)>0.000001)return 'fx_imbalance';
        $this->db->trans_begin();
        $locked = $this->db->query(
            'SELECT status FROM fin_cash_transfer WHERE tenant_id = ? AND cash_transfer_id = ? LIMIT 1 FOR UPDATE',
            array((int) $tenant, (int) $id)
        );
        if ($locked === FALSE || !$locked->num_rows() || (string) $locked->row()->status !== 'approved') {
            $this->db->trans_rollback();
            return 'not_postable';
        }
        $CI =& get_instance();
        $CI->load->library('finance_balance_guard');
        $balance_check = $CI->finance_balance_guard->can_debit(
            $tenant,
            (int) $row['from_cash_account_id'],
            (float) $row['from_amount'],
            (string) $row['from_currency']
        );
        if ($balance_check !== 'ok') {
            $this->db->trans_rollback();
            return $balance_check;
        }
        $dup=$this->db->where('tenant_id',(int)$tenant)->where('source_type','cash_transfer')->where('source_id',(int)$id)->limit(1)->get('fin_journal_entry');
        if($dup===FALSE||$dup->num_rows()){$this->db->trans_rollback();return 'already_posted';}
        $CI =& get_instance();
        $CI->load->library('application_sequence_store');
        $next_journal = $CI->application_sequence_store->next_code_for_tenant($tenant, 'journal_transfer', FALSE);
        $journal_number = 'TRF-' . (int) $id;
        if (is_array($next_journal) && isset($next_journal['code']) && $next_journal['code'] !== '') {
            $journal_number = (string) $next_journal['code'];
            for ($attempt = 0; $attempt < 10 && $this->journal_number_exists($tenant, $journal_number); $attempt++) {
                $next_journal = $CI->application_sequence_store->next_code_after($next_journal);
                if (!is_array($next_journal) || !isset($next_journal['code'])) { break; }
                $journal_number = (string) $next_journal['code'];
            }
        }
        $this->db->insert('fin_journal_entry',array('tenant_id'=>(int)$tenant,'period_id'=>(int)$period->row()->period_id,
            'journal_number'=>$journal_number,'journal_date'=>$row['transfer_date'],'source_type'=>'cash_transfer','source_id'=>(int)$id,
            'description'=>'Posted '.$row['transfer_number'],'status'=>'posted','debit_total'=>$debit,'credit_total'=>$credit,
            'create_on'=>app_now(),'created_by'=>(int)$user,'posted_on'=>app_now(),'posted_by'=>(int)$user));
        $journal=(int)$this->db->insert_id();
        if ($journal < 1
            || (is_array($next_journal) && !$CI->application_sequence_store->advance_sequence($tenant, 'journal_transfer', $next_journal))) {
            $this->db->trans_rollback();
            return 'post_error';
        }
        $lines=array(
            array('account_id'=>(int)$to_gl['account_id'],'currency'=>$row['to_currency'],'rate'=>$row['to_exchange_rate'],'debit'=>(float)$row['to_amount'],'credit'=>0,'base_debit'=>$debit,'base_credit'=>0),
            array('account_id'=>(int)$from_gl['account_id'],'currency'=>$row['from_currency'],'rate'=>$row['from_exchange_rate'],'debit'=>0,'credit'=>(float)$row['from_amount'],'base_debit'=>0,'base_credit'=>$credit),
        );
        foreach($lines as $i=>$line)$this->db->insert('fin_journal_line',array('tenant_id'=>(int)$tenant,'journal_entry_id'=>$journal,'account_id'=>$line['account_id'],'line_number'=>$i+1,
            'description'=>$row['transfer_number'],'currency_code'=>$line['currency'],'exchange_rate'=>$line['rate'],'debit_amount'=>$line['debit'],'credit_amount'=>$line['credit'],
            'debit_base_amount'=>$line['base_debit'],'credit_base_amount'=>$line['base_credit']));
        $this->db->where('tenant_id',(int)$tenant)->where('cash_transfer_id',(int)$id)->where('status','approved')
            ->update('fin_cash_transfer',array('status'=>'posted','posted_on'=>app_now(),'posted_by'=>(int)$user));
        if(!$journal||$this->db->trans_status()===FALSE||(int)$this->db->affected_rows()!==1){$this->db->trans_rollback();return 'post_error';}
        $this->db->trans_commit();return 'posted';
    }

    private function transfer_number_exists($tenant, $number)
    {
        return $this->db->from('fin_cash_transfer')
            ->where('tenant_id', (int) $tenant)->where('transfer_number', (string) $number)
            ->count_all_results() > 0;
    }

    private function journal_number_exists($tenant, $number)
    {
        return $this->db->from('fin_journal_entry')
            ->where('tenant_id', (int) $tenant)->where('journal_number', (string) $number)
            ->count_all_results() > 0;
    }

    private function tenant_row($table,$column,$tenant,$id)
    {
        $q=$this->db->where('tenant_id',(int)$tenant)->where($column,(int)$id)->limit(1)->get($table);
        return $q!==FALSE&&$q->num_rows()?$q->row_array():NULL;
    }
}
