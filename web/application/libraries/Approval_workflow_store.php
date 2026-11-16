<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped persistence for hierarchy and approval workflows. */
class Approval_workflow_store
{
    private $db;
    private $tenant;
    private $user;

    public function __construct($db = NULL)
    {
        $CI =& get_instance();
        if ($db === NULL) { $CI->load->database(); $db = $CI->db; }
        $session = $CI->session->userdata('admin_user');
        $this->db = $db;
        $this->tenant = is_array($session) && isset($session['tenant_id']) ? (int) $session['tenant_id'] : 0;
        $this->user = is_array($session) && isset($session['user_id']) ? (int) $session['user_id'] : 0;
    }

    public function available()
    {
        foreach (array('hr_org', 'app_approval_flow', 'app_approval_flow_step', 'app_approval_request', 'app_approval_action', 'hr_employee', 'app_user') as $table) {
            if (!$this->db->table_exists($table)) { return FALSE; }
        }
        return $this->tenant > 0 && $this->user > 0;
    }

    public function hierarchy_available()
    {
        return $this->tenant > 0 && $this->user > 0
            && $this->db->table_exists('hr_org')
            && $this->db->table_exists('hr_employee');
    }

    public function employees()
    {
        if (!$this->available()) { return array(); }
        return $this->db->select('employee_id, employee_code, employee_name')->where('tenant_id', $this->tenant)->order_by('employee_name')->get('hr_employee')->result_array();
    }

    public function hierarchy()
    {
        if (!$this->hierarchy_available()) { return array(); }
        $member_names = $this->db->table_exists('hr_org_member')
            ? "(SELECT GROUP_CONCAT(e_member.employee_name ORDER BY m.line SEPARATOR ', ') FROM hr_org_member m INNER JOIN hr_employee e_member ON e_member.employee_id=m.employee_id AND e_member.tenant_id=o.tenant_id WHERE m.org_id=o.org_id) AS member_names"
            : "'' AS member_names";
        return $this->db->select('o.org_id, o.parent_org, o.org_code, o.org_name, o.head_employee_id, e.employee_name AS head_employee_name', FALSE)
            ->select($member_names, FALSE)
            ->from('hr_org o')->join('hr_employee e', 'e.employee_id=o.head_employee_id AND e.tenant_id=o.tenant_id', 'left')
            ->where('o.tenant_id', $this->tenant)->order_by('o.org_name')->get()->result_array();
    }

    public function hierarchy_org_members($org)
    {
        if (!$this->hierarchy_available() || !$this->org_exists((int)$org) || !$this->db->table_exists('hr_org_member')) { return array(); }
        $select = 'm.employee_id, e.employee_code, e.employee_name';
        if (in_array('job_title', $this->db->list_fields('hr_org_member'), TRUE)) { $select .= ', m.job_title'; }
        return $this->db->select($select)->from('hr_org_member m')
            ->join('hr_employee e', 'e.employee_id=m.employee_id AND e.tenant_id='.$this->db->escape($this->tenant), 'inner')
            ->where('m.org_id', (int)$org)->order_by('m.line')->get()->result_array();
    }

    public function sync_org_members($org, $employees)
    {
        $org=(int)$org;
        if (!$this->org_exists($org) || !$this->db->table_exists('hr_org_member') || !is_array($employees)) { return FALSE; }
        $ids=array();
        $has_job_title = in_array('job_title', $this->db->list_fields('hr_org_member'), TRUE);
        foreach ($employees as $employee) {
            $employee_id = is_array($employee) && isset($employee['employee_id']) ? (string)$employee['employee_id'] : (is_array($employee) ? '' : (string)$employee);
            $job_title = is_array($employee) && isset($employee['job_title']) ? trim((string)$employee['job_title']) : '';
            if (!ctype_digit($employee_id) || !$this->hierarchy_employee_exists((int)$employee_id)
                || isset($ids[(int)$employee_id])
                || (function_exists('mb_strlen') ? mb_strlen($job_title, 'UTF-8') : strlen($job_title)) > 64
                || (!$has_job_title && $job_title !== '')) { return FALSE; }
            $ids[(int)$employee_id]=array('employee_id'=>(int)$employee_id,'job_title'=>$job_title);
        }
        $this->db->trans_begin();
        $existing=$this->db->where('org_id',$org)->get('hr_org_member')->result_array();
        $existing_ids=array(); foreach ($existing as $row) { $existing_ids[(int)$row['employee_id']]=$row; }
        foreach ($existing_ids as $employee=>$row) {
            if (!isset($ids[$employee]) && !$this->db->where('org_employee_id',(int)$row['org_employee_id'])->delete('hr_org_member')) { $this->db->trans_rollback(); return FALSE; }
        }
        foreach (array_values($ids) as $index=>$member) {
            $employee = $member['employee_id'];
            if (isset($existing_ids[$employee])) {
                $data = array('line'=>$index+1);
                if ($has_job_title) { $data['job_title']=$member['job_title']; }
                $this->db->where('org_employee_id',(int)$existing_ids[$employee]['org_employee_id'])->update('hr_org_member',$data);
                if ($this->db->trans_status()===FALSE) { $this->db->trans_rollback(); return FALSE; }
            } else {
                $row=array('org_id'=>$org,'employee_id'=>$employee,'line'=>$index+1);
                if ($has_job_title) { $row['job_title']=$member['job_title']; }
                if (!$this->db->insert('hr_org_member',$row)) { $this->db->trans_rollback(); return FALSE; }
            }
        }
        if ($this->db->trans_status() === FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        return TRUE;
    }

    public function hierarchy_employees($term)
    {
        $term = substr(trim((string)$term), 0, 80);
        if (!$this->hierarchy_available() || strlen($term) < 2) { return array(); }
        return $this->db->select('employee_id, employee_code, employee_name')->from('hr_employee')
            ->where('tenant_id', $this->tenant)->group_start()->like('employee_code', $term)->or_like('employee_name', $term)->group_end()
            ->order_by('employee_name')->limit(10)->get()->result_array();
    }

    public function hierarchy_org($org)
    {
        if (!$this->hierarchy_available()) { return FALSE; }
        return $this->db->where(array('tenant_id'=>$this->tenant,'org_id'=>(int)$org))->get('hr_org')->row_array();
    }

    public function hierarchy_employee_exists($employee)
    {
        return $this->hierarchy_available() && (int)$employee > 0
            && (int)$this->db->where(array('tenant_id'=>$this->tenant,'employee_id'=>(int)$employee))->count_all_results('hr_employee') === 1;
    }

    public function update_org($org, $parent, $code, $name, $head)
    {
        $org=(int)$org; $parent=$parent === NULL ? 0 : (int)$parent; $code=trim((string)$code); $name=trim((string)$name);
        if (!$this->org_exists($org) || ($parent && (!$this->org_exists($parent) || $parent === $org || $this->would_cycle_org($org,$parent)))
            || $code === '' || strlen($code)>32 || $name === ''
            || (function_exists('mb_strlen') ? mb_strlen($name,'UTF-8') : strlen($name))>64
            || preg_match('/[\\x00-\\x1F\\x7F]/',$code.$name)
            || ($head !== NULL && !$this->hierarchy_employee_exists($head))) { return FALSE; }
        $this->db->where(array('tenant_id'=>$this->tenant,'org_id'=>$org))->update('hr_org',array(
            'parent_org'=>$parent ?: NULL,'org_code'=>$code,'org_name'=>$name,'update_on'=>date('Y-m-d H:i:s')
        ));
        $ok = $this->db->affected_rows()===1 || $this->org_matches($org,$parent,$code,$name);
        if (!$ok || $head === NULL) { return $ok; }
        return $this->set_org_head($org,(int)$head);
    }

    private function org_matches($org,$parent,$code,$name)
    {
        $row=$this->hierarchy_org($org);
        return $row && (string)$row['org_code']===$code && (string)$row['org_name']===$name && (int)$row['parent_org']===$parent;
    }

    public function set_org_head($org, $employee)
    {
        $org=(int)$org; $employee=(int)$employee;
        if (!$this->org_exists($org) || !$employee || !$this->db->where(array('tenant_id'=>$this->tenant,'employee_id'=>$employee))->count_all_results('hr_employee')) { return FALSE; }
        $this->db->where(array('tenant_id'=>$this->tenant,'org_id'=>$org))->update('hr_org',array('head_employee_id'=>$employee,'update_on'=>date('Y-m-d H:i:s')));
        return $this->db->affected_rows() === 1 || (int)$this->db->where(array('tenant_id'=>$this->tenant,'org_id'=>$org,'head_employee_id'=>$employee))->count_all_results('hr_org') === 1;
    }

    public function add_org_member($org, $employee)
    {
        $org=(int)$org; $employee=(int)$employee;
        if (!$this->org_exists($org) || !$this->db->table_exists('hr_org_member') || !$employee || !$this->hierarchy_employee_exists($employee)) { return FALSE; }
        if ($this->db->where(array('org_id'=>$org,'employee_id'=>$employee))->count_all_results('hr_org_member')) { return FALSE; }
        $line=(int)$this->db->select_max('line')->where('org_id',$org)->get('hr_org_member')->row()->line + 1;
        $row=array('org_id'=>$org,'employee_id'=>$employee,'line'=>$line);
        if (in_array('job_title',$this->db->list_fields('hr_org_member'),TRUE)) { $row['job_title']=NULL; }
        return $this->db->insert('hr_org_member',$row);
    }


    public function delete_org_leaf($org)
    {
        $org=(int)$org;
        if (!$this->org_exists($org) || $this->db->where(array('tenant_id'=>$this->tenant,'parent_org'=>$org))->count_all_results('hr_org') > 0) { return FALSE; }
        return $this->db->where(array('tenant_id'=>$this->tenant,'org_id'=>$org))->delete('hr_org') && $this->db->affected_rows() === 1;
    }

    public function hierarchy_org_exists($org)
    {
        return $this->org_exists((int) $org);
    }

    public function create_child_org($parent, $code, $name)
    {
        $parent = (int)$parent; $code = trim((string)$code); $name = trim((string)$name);
        if (!$this->org_exists($parent) || $code === '' || strlen($code) > 32 || $name === ''
            || (function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name)) > 64
            || preg_match('/[\\x00-\\x1F\\x7F]/', $code . $name)) { return FALSE; }
        return $this->db->insert('hr_org', array(
            'parent_org' => $parent, 'tenant_id' => $this->tenant, 'org_code' => $code,
            'org_name' => $name, 'head_employee_id' => NULL, 'create_on' => date('Y-m-d H:i:s'),
        ));
    }

    public function create_root_org($code, $name)
    {
        $code = trim((string) $code);
        $name = trim((string) $name);
        if (!$this->hierarchy_available() || $code === '' || strlen($code) > 32
            || $name === '' || (function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name)) > 64
            || preg_match('/[\x00-\x1F\x7F]/', $code . $name)) { return FALSE; }
        return $this->db->insert('hr_org', array(
            'parent_org' => NULL,
            'tenant_id' => $this->tenant,
            'org_code' => $code,
            'org_name' => $name,
            'head_employee_id' => NULL,
            'create_on' => date('Y-m-d H:i:s'),
        ));
    }

    public function set_parent_org($org, $parent)
    {
        $org = (int)$org; $parent = (int)$parent;
        if (!$org || $org === $parent || !$this->org_exists($org) || ($parent && !$this->org_exists($parent))) { return FALSE; }
        if ($parent && $this->would_cycle_org($org, $parent)) { return FALSE; }
        $this->db->where(array('tenant_id'=>$this->tenant,'org_id'=>$org))->update('hr_org', array('parent_org'=>$parent ?: NULL,'update_on'=>date('Y-m-d H:i:s')));
        return $this->db->affected_rows() === 1 || $this->db->where(array('tenant_id'=>$this->tenant,'org_id'=>$org,'parent_org'=>$parent ?: NULL))->count_all_results('hr_org') === 1;
    }

    public function flows($search = '', $limit = NULL, $offset = 0, $sort = 'flow_name', $direction = 'ASC')
    {
        if (!$this->available()) { return array(); }
        $this->db->select('f.flow_id, f.flow_name, f.request_type, f.active_flag, COUNT(s.flow_step_id) AS step_count')
            ->from('app_approval_flow f')->join('app_approval_flow_step s','s.flow_id=f.flow_id AND s.tenant_id=f.tenant_id','left')
            ->where('f.tenant_id',$this->tenant);
        $search = substr(trim((string)$search), 0, 120);
        if ($search !== '') { $this->db->group_start()->like('f.flow_name',$search)->or_like('f.request_type',$search)->group_end(); }
        $this->db->group_by(array('f.flow_id','f.flow_name','f.request_type','f.active_flag'));
        $sort = in_array($sort, array('flow_name','request_type','active_flag'), TRUE) ? $sort : 'flow_name';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->db->order_by('f.'.$sort,$direction);
        if ($limit !== NULL) { $this->db->limit(min(50,max(1,(int)$limit)),max(0,(int)$offset)); }
        return $this->db->get()->result_array();
    }

    public function count_flows($search = '')
    {
        if (!$this->available()) { return FALSE; }
        $this->db->from('app_approval_flow')->where('tenant_id',$this->tenant);
        $search = substr(trim((string)$search), 0, 120);
        if ($search !== '') { $this->db->group_start()->like('flow_name',$search)->or_like('request_type',$search)->group_end(); }
        return (int)$this->db->count_all_results();
    }

    public function flow($flow_id)
    {
        if (!$this->available()) { return FALSE; }
        $flow = $this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>(int)$flow_id))->get('app_approval_flow')->row_array();
        if (!$flow) { return NULL; }
        $flow['approver_user_ids'] = array_map('intval', array_column($this->db->select('approver_user_id')->where(array('tenant_id'=>$this->tenant,'flow_id'=>(int)$flow_id))->order_by('step_number')->get('app_approval_flow_step')->result_array(), 'approver_user_id'));
        return $flow;
    }

    public function delete_flow($flow_id)
    {
        if (!$this->available() || (int)$flow_id < 1) { return 'error'; }
        $this->db->trans_begin();
        $flow = $this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>(int)$flow_id))->get('app_approval_flow')->row_array();
        if (!$flow) { $this->db->trans_rollback(); return 'not_found'; }
        if ($this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>(int)$flow_id))->count_all_results('app_approval_request') > 0) { $this->db->trans_rollback(); return 'in_use'; }
        $this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>(int)$flow_id))->delete('app_approval_flow_step');
        $this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>(int)$flow_id))->delete('app_approval_flow');
        if ($this->db->trans_status() === FALSE || $this->db->affected_rows() !== 1) { $this->db->trans_rollback(); return 'error'; }
        $this->db->trans_commit(); return 'deleted';
    }

    public function save_flow($name, $type, $approvers, $flow_id = 0)
    {
        $name = trim((string)$name); $type = trim((string)$type);
        $approvers = array_values(array_unique(array_filter(array_map('intval', (array)$approvers))));
        if (!$name || strlen($name) > 120 || !preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $type) || !$approvers) { return FALSE; }
        foreach ($approvers as $approver) { if (!$this->user_exists($approver)) { return FALSE; } }
        $this->db->trans_begin();
        $data = array('flow_name'=>$name,'request_type'=>$type,'active_flag'=>1,'update_on'=>date('Y-m-d H:i:s'));
        if ($flow_id) {
            $this->db->where('tenant_id',$this->tenant)->where('flow_id',(int)$flow_id)->update('app_approval_flow',$data);
            $exists = $this->db->where('tenant_id',$this->tenant)->where('flow_id',(int)$flow_id)->count_all_results('app_approval_flow');
            if (!$exists) { $this->db->trans_rollback(); return FALSE; }
            $id = (int)$flow_id;
            $this->db->where('tenant_id',$this->tenant)->where('flow_id',$id)->delete('app_approval_flow_step');
        } else {
            $data['tenant_id']=$this->tenant; $data['created_by']=$this->user; $data['create_on']=date('Y-m-d H:i:s');
            if (!$this->db->insert('app_approval_flow',$data)) { $this->db->trans_rollback(); return FALSE; }
            $id = (int)$this->db->insert_id();
        }
        foreach ($approvers as $i=>$approver) {
            if (!$this->db->insert('app_approval_flow_step',array('tenant_id'=>$this->tenant,'flow_id'=>$id,'step_number'=>$i+1,'approver_user_id'=>$approver,'create_on'=>date('Y-m-d H:i:s')))) { $this->db->trans_rollback(); return FALSE; }
        }
        if ($this->db->trans_status() === FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit(); return $id;
    }

    public function pending()
    {
        if (!$this->available()) { return array(); }
        return $this->db->select('r.request_id,r.subject,r.details,r.create_on,r.current_step,f.flow_name,u.username AS requester_name')
            ->from('app_approval_request r')->join('app_approval_flow f','f.flow_id=r.flow_id AND f.tenant_id=r.tenant_id','inner')
            ->join('app_user u','u.user_id=r.requester_user_id AND u.tenant_id=r.tenant_id','left')
            ->join('app_approval_flow_step s','s.tenant_id=r.tenant_id AND s.flow_id=r.flow_id AND s.step_number=r.current_step','inner')
            ->where('r.tenant_id',$this->tenant)->where('r.status','pending')->where('s.approver_user_id',$this->user)->order_by('r.create_on','ASC')->get()->result_array();
    }

    public function active_flows()
    {
        if (!$this->available()) { return array(); }
        return $this->db->where(array('tenant_id'=>$this->tenant,'active_flag'=>1))->order_by('flow_name')->get('app_approval_flow')->result_array();
    }

    public function submit($flow_id, $subject, $details)
    {
        $flow_id=(int)$flow_id; $subject=trim((string)$subject);
        if (!$flow_id || !$subject || strlen($subject)>180 || strlen((string)$details)>10000 || !$this->db->table_exists('app_approval_link_token') || !$this->db->table_exists('app_notification')) { return FALSE; }
        $flow=$this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>$flow_id,'active_flag'=>1))->get('app_approval_flow')->row_array();
        $first=$this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>$flow_id,'step_number'=>1))->get('app_approval_flow_step')->row_array();
        if (!$flow || !$first) { return FALSE; }
        $this->db->trans_begin();
        $created=date('Y-m-d H:i:s');
        if (!$this->db->insert('app_approval_request',array('tenant_id'=>$this->tenant,'flow_id'=>$flow_id,'requester_user_id'=>$this->user,'subject'=>$subject,'details'=>trim((string)$details),'status'=>'pending','current_step'=>1,'create_on'=>$created))) { $this->db->trans_rollback(); return FALSE; }
        $request_id=(int)$this->db->insert_id();
        $approve=$this->create_approval_token($request_id,(int)$first['approver_user_id'],'approved');
        $reject=$this->create_approval_token($request_id,(int)$first['approver_user_id'],'rejected');
        if (!$approve || !$reject || !$this->notify_approver((int)$first['approver_user_id'],$subject,$approve,$reject)) { $this->db->trans_rollback(); return FALSE; }
        if ($this->db->trans_status()===FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit(); return $request_id;
    }

    private function create_approval_token($request_id,$approver,$decision)
    {
        try { $token=bin2hex(random_bytes(32)); } catch (Exception $e) { return FALSE; }
        $expires=date('Y-m-d H:i:s',time()+86400);
        $ok=$this->db->insert('app_approval_link_token',array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'approver_user_id'=>(int)$approver,'decision'=>$decision,'token_hash'=>hash('sha256',$token),'expires_at'=>$expires,'used_at'=>NULL,'create_on'=>date('Y-m-d H:i:s')));
        return $ok ? $token : FALSE;
    }

    private function notify_approver($approver,$subject,$approve_token,$reject_token)
    {
        $fields=array();
        foreach ((array)$this->db->field_data('app_notification') as $field) { if (isset($field->name)) { $fields[(string)$field->name]=TRUE; } }
        if (!isset($fields['user_id'],$fields['title'],$fields['message'])) { return FALSE; }
        $base=rtrim(site_url('admin/system/approval'),'/');
        $message='Approval diperlukan: '.$subject."\nSetuju: ".$base.'?approval_token='.rawurlencode($approve_token).'&approval_decision=approved'."\nTidak setuju: ".$base.'?approval_token='.rawurlencode($reject_token).'&approval_decision=rejected';
        $data=array('user_id'=>(int)$approver,'title'=>'Permintaan persetujuan','message'=>$message);
        if (isset($fields['read_flag'])) { $data['read_flag']=0; }
        if (isset($fields['view_flag'])) { $data['view_flag']=0; }
        foreach (array('create_on','created_at','created_date','date_created') as $date_field) { if (isset($fields[$date_field])) { $data[$date_field]=date('Y-m-d H:i:s'); break; } }
        return $this->db->insert('app_notification',$data);
    }

    public function approval_token_request($token)
    {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/',$token) || !$this->db->table_exists('app_approval_link_token')) { return FALSE; }
        $row=$this->db->where(array('tenant_id'=>$this->tenant,'approver_user_id'=>$this->user,'token_hash'=>hash('sha256',$token)))->where('used_at IS NULL',NULL,FALSE)->where('expires_at >',date('Y-m-d H:i:s'))->get('app_approval_link_token')->row_array();
        if (!$row) { return FALSE; }
        $request=$this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$row['request_id'],'status'=>'pending'))->get('app_approval_request')->row_array();
        return $request ? array('request_id'=>(int)$request['request_id'],'subject'=>(string)$request['subject'],'details'=>(string)$request['details'],'decision'=>(string)$row['decision']) : FALSE;
    }

    public function decide_approval_token($token,$decision)
    {
        $request=$this->approval_token_request($token);
        if (!$request || !in_array($decision,array('approved','rejected'),TRUE) || $request['decision']!==$decision) { return FALSE; }
        if (!$this->decide($request['request_id'],$decision,'')) { return FALSE; }
        $this->db->where(array('tenant_id'=>$this->tenant,'approver_user_id'=>$this->user,'token_hash'=>hash('sha256',$token)))->where('used_at IS NULL',NULL,FALSE)->update('app_approval_link_token',array('used_at'=>date('Y-m-d H:i:s')));
        return $this->db->affected_rows()===1;
    }

    public function decide($request_id, $decision, $note)
    {
        if (!in_array($decision,array('approved','rejected'),TRUE)) { return FALSE; }
        $this->db->trans_begin();
        $request=$this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'status'=>'pending'))->get('app_approval_request')->row_array();
        if (!$request) { $this->db->trans_rollback(); return FALSE; }
        $step=$this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>$request['flow_id'],'step_number'=>$request['current_step'],'approver_user_id'=>$this->user))->get('app_approval_flow_step')->row_array();
        if (!$step) { $this->db->trans_rollback(); return FALSE; }
        $next=$decision==='rejected'?'rejected':'pending';
        $this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'status'=>'pending','current_step'=>(int)$request['current_step']))
            ->update('app_approval_request',array('status'=>$next,'current_step'=>(int)$request['current_step']+1,'update_on'=>date('Y-m-d H:i:s')));
        if ($this->db->affected_rows() !== 1 || !$this->db->insert('app_approval_action',array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'step_number'=>(int)$request['current_step'],'approver_user_id'=>$this->user,'decision'=>$decision,'note'=>trim((string)$note),'create_on'=>date('Y-m-d H:i:s')))) { $this->db->trans_rollback(); return FALSE; }
        if ($decision==='approved') {
            $next_step=$this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>$request['flow_id'],'step_number'=>(int)$request['current_step']+1))->get('app_approval_flow_step')->row_array();
            if (!$next_step) {
                $this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id))->update('app_approval_request',array('status'=>'approved','update_on'=>date('Y-m-d H:i:s')));
            } elseif (!$this->notify_next_approver((int)$request_id,(string)$request['subject'],(int)$next_step['approver_user_id'])) {
                $this->db->trans_rollback(); return FALSE;
            }
        }
        if ($this->db->trans_status()===FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit(); return TRUE;
    }

    private function notify_next_approver($request_id,$subject,$approver)
    {
        $approve=$this->create_approval_token($request_id,$approver,'approved');
        $reject=$this->create_approval_token($request_id,$approver,'rejected');
        return $approve && $reject && $this->notify_approver($approver,$subject,$approve,$reject);
    }

    public function users()
    {
        if (!$this->available()) { return array(); }
        return $this->db->select('user_id, username')->where('tenant_id',$this->tenant)->order_by('username')->get('app_user')->result_array();
    }

    private function org_exists($id) { return $this->db->where(array('tenant_id'=>$this->tenant,'org_id'=>(int)$id))->count_all_results('hr_org')===1; }
    private function user_exists($id) { return $this->db->where(array('tenant_id'=>$this->tenant,'user_id'=>(int)$id))->count_all_results('app_user')===1; }
    private function would_cycle_org($org, $parent)
    {
        $seen=array($org=>TRUE); $cursor=$parent;
        for ($i=0;$i<100;$i++) {
            if (isset($seen[$cursor])) { return TRUE; }
            $seen[$cursor]=TRUE;
            $row=$this->db->select('parent_org')->where(array('tenant_id'=>$this->tenant,'org_id'=>$cursor))->get('hr_org')->row_array();
            if (!$row) { return FALSE; } $cursor=(int)$row['parent_org']; if (!$cursor) { return FALSE; }
        }
        return TRUE;
    }
}
