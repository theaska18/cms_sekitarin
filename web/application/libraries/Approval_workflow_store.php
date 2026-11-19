<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped persistence for hierarchy and approval workflows. */
class Approval_workflow_store
{
    private $db;
    private $tenant;
    private $user;
    private $employee;

    public function __construct($db = NULL)
    {
        $CI =& get_instance();
        if ($db === NULL) { $CI->load->database(); $db = $CI->db; }
        $session = $CI->session->userdata('admin_user');
        $this->db = $db;
        $this->tenant = is_array($session) && isset($session['tenant_id']) ? (int) $session['tenant_id'] : 0;
        $this->user = is_array($session) && isset($session['user_id']) ? (int) $session['user_id'] : 0;
        $this->employee = is_array($session) && isset($session['employee_id']) ? (int) $session['employee_id'] : 0;
    }

    public function available()
    {
        foreach (array(
            'hr_org', 'hr_org_member', 'app_approval_flow', 'app_approval_flow_step',
            'app_approval_request', 'app_approval_action', 'app_approval_error',
            'app_approval_link_token', 'hr_employee', 'app_user',
            'sys_approval_flow', 'sys_approval_flow_step', 'sys_approval_flow_menu',
        ) as $table) {
            if (!$this->db->table_exists($table)) { return FALSE; }
        }
        foreach (array(
            array('app_approval_flow', 'flow_code'),
            array('app_approval_flow_step', 'approver_type'),
            array('app_approval_flow_step', 'approver_employee_id'),
            array('app_approval_flow_step', 'org_id'),
            array('app_approval_request', 'flow_code'),
            array('app_approval_request', 'requester_employee_id'),
            array('app_approval_request', 'current_approver_user_id'),
            array('app_approval_request', 'source_type'),
            array('app_approval_request', 'source_id'),
            array('sys_approval_flow', 'flow_code'),
            array('sys_approval_flow', 'flow_name'),
            array('sys_approval_flow', 'active_flag'),
            array('sys_approval_flow_step', 'flow_code'),
            array('sys_approval_flow_step', 'step_number'),
            array('sys_approval_flow_menu', 'flow_code'),
            array('sys_approval_flow_menu', 'menu_code'),
        ) as $field) {
            if (!$this->db->field_exists($field[1], $field[0])) { return FALSE; }
        }
        return $this->tenant > 0 && $this->user > 0;
    }

    /** System flow codes mapped to menus the current user can access. */
    public function accessible_flow_codes()
    {
        if (!$this->available()) { return array(); }
        $CI =& get_instance();
        $session = $CI->session->userdata('admin_user');
        $menu_codes = is_array($session) && isset($session['menu_codes']) && is_array($session['menu_codes'])
            ? array_values(array_filter($session['menu_codes'], 'is_string'))
            : array();
        if (!$menu_codes) { return array(); }
        $rows = $this->db->distinct()->select('flow_code')->from('sys_approval_flow_menu')
            ->where_in('menu_code', $menu_codes)->get()->result_array();
        return array_map('strval', array_column($rows, 'flow_code'));
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
        if (!$this->db->insert('hr_org', array(
            'parent_org' => $parent, 'tenant_id' => $this->tenant, 'org_code' => $code,
            'org_name' => $name, 'head_employee_id' => NULL, 'create_on' => date('Y-m-d H:i:s'),
        ))) { return FALSE; }
        return (int) $this->db->insert_id();
    }

    public function create_root_org($code, $name)
    {
        $code = trim((string) $code);
        $name = trim((string) $name);
        if (!$this->hierarchy_available() || $code === '' || strlen($code) > 32
            || $name === '' || (function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name)) > 64
            || preg_match('/[\x00-\x1F\x7F]/', $code . $name)) { return FALSE; }
        if (!$this->db->insert('hr_org', array(
            'parent_org' => NULL,
            'tenant_id' => $this->tenant,
            'org_code' => $code,
            'org_name' => $name,
            'head_employee_id' => NULL,
            'create_on' => date('Y-m-d H:i:s'),
        ))) { return FALSE; }
        return (int) $this->db->insert_id();
    }

    public function hierarchy_org_code_exists($code)
    {
        if (!$this->hierarchy_available() || !is_string($code) || $code === '') { return TRUE; }
        return (int)$this->db->where(array('tenant_id' => $this->tenant, 'org_code' => $code))
            ->count_all_results('hr_org') > 0;
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
        $codes = $this->accessible_flow_codes();
        if (!$codes) { return array(); }
        $tenant = (int) $this->tenant;
        $menu_codes = "(SELECT GROUP_CONCAT(m.menu_code ORDER BY m.menu_code SEPARATOR ', ')"
            . " FROM sys_approval_flow_menu m WHERE m.flow_code = f.flow_code) AS menu_codes";
        $override_id = "(SELECT af.flow_id FROM app_approval_flow af"
            . " WHERE af.tenant_id = " . $tenant . " AND af.flow_code = f.flow_code LIMIT 1) AS override_flow_id";
        $system_count = "(SELECT COUNT(*) FROM sys_approval_flow_step ss WHERE ss.flow_code = f.flow_code) AS system_step_count";
        $override_count = "(SELECT COUNT(*) FROM app_approval_flow_step os"
            . " INNER JOIN app_approval_flow af2 ON af2.flow_id = os.flow_id AND af2.tenant_id = os.tenant_id"
            . " WHERE af2.tenant_id = " . $tenant . " AND af2.flow_code = f.flow_code) AS override_step_count";
        $this->db->select('f.flow_code, f.flow_name, f.description, f.active_flag, '
            . $menu_codes . ', ' . $override_id . ', ' . $system_count . ', ' . $override_count, FALSE)
            ->from('sys_approval_flow f')->where_in('f.flow_code', $codes);
        $search = substr(trim((string)$search), 0, 120);
        if ($search !== '') {
            $this->db->group_start()->like('f.flow_code',$search)->or_like('f.flow_name',$search)
                ->or_like('f.description',$search)->group_end();
        }
        $sort = in_array($sort, array('flow_code','flow_name','active_flag'), TRUE) ? $sort : 'flow_name';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->db->order_by('f.'.$sort,$direction);
        if ($limit !== NULL) { $this->db->limit(min(50,max(1,(int)$limit)),max(0,(int)$offset)); }
        $rows = $this->db->get()->result_array();
        foreach ($rows as &$row) {
            $row['flow_code'] = (string)$row['flow_code'];
            $row['active_flag'] = (int)$row['active_flag'] === 1 ? 1 : 0;
            $row['menu_codes'] = isset($row['menu_codes']) ? (string)$row['menu_codes'] : '';
            $row['override_flow_id'] = isset($row['override_flow_id']) ? (int)$row['override_flow_id'] : 0;
            $row['step_count'] = $row['override_flow_id'] > 0
                ? (int)$row['override_step_count'] : (int)$row['system_step_count'];
            $row['step_source'] = $row['override_flow_id'] > 0 ? 'tenant' : 'system';
            $row['override'] = $row['override_flow_id'] > 0;
        }
        unset($row);
        return $rows;
    }

    public function count_flows($search = '')
    {
        if (!$this->available()) { return FALSE; }
        $codes = $this->accessible_flow_codes();
        if (!$codes) { return 0; }
        $this->db->from('sys_approval_flow f')->where_in('f.flow_code', $codes);
        $search = substr(trim((string)$search), 0, 120);
        if ($search !== '') {
            $this->db->group_start()->like('f.flow_code',$search)->or_like('f.flow_name',$search)
                ->or_like('f.description',$search)->group_end();
        }
        return (int)$this->db->count_all_results();
    }

    /** One system template with effective (tenant override or system) steps. */
    public function template_flow($flow_code)
    {
        if (!$this->available()) { return FALSE; }
        $flow_code = trim((string)$flow_code);
        if ($flow_code === '' || !in_array($flow_code, $this->accessible_flow_codes(), TRUE)) { return NULL; }
        $flow = $this->db->select('flow_code, flow_name, description, active_flag, create_on, update_on')
            ->from('sys_approval_flow')->where('flow_code',$flow_code)->limit(1)->get()->row_array();
        if (!$flow) { return NULL; }
        $override = $this->flow_override($flow_code);
        $flow['flow_code'] = (string)$flow['flow_code'];
        $flow['active_flag'] = (int)$flow['active_flag'] === 1 ? 1 : 0;
        $flow['override'] = $override ? TRUE : FALSE;
        $flow['override_flow_id'] = $override ? (int)$override['flow_id'] : 0;
        $flow['step_source'] = $override ? 'tenant' : 'system';
        $flow['steps'] = $this->effective_steps($flow_code);
        $menu_rows = $this->db->select('menu_code')->from('sys_approval_flow_menu')
            ->where('flow_code',$flow_code)->order_by('menu_code')->get()->result_array();
        $flow['menu_codes'] = array_map('strval', array_column($menu_rows, 'menu_code'));
        return $flow;
    }

    public function effective_steps($flow_code)
    {
        if (!$this->available()) { return array(); }
        $flow_code = trim((string)$flow_code);
        if ($flow_code === '') { return array(); }
        $override = $this->flow_override($flow_code);
        if ($override) {
            return $this->db->select('s.flow_step_id,s.flow_id,s.step_number,s.approver_type,s.approver_user_id,s.approver_employee_id,s.org_id,e.employee_code,e.employee_name,o.org_code,o.org_name,u.username AS configured_username')
                ->from('app_approval_flow_step s')
                ->join('hr_employee e', 'e.employee_id=s.approver_employee_id AND e.tenant_id=s.tenant_id', 'left')
                ->join('hr_org o', 'o.org_id=s.org_id AND o.tenant_id=s.tenant_id', 'left')
                ->join('app_user u', 'u.user_id=s.approver_user_id AND u.tenant_id=s.tenant_id', 'left')
                ->where(array('s.tenant_id'=>$this->tenant,'s.flow_id'=>(int)$override['flow_id']))
                ->order_by('s.step_number')->get()->result_array();
        }
        $tenant = $this->db->escape($this->tenant);
        return $this->db->select('s.flow_step_id,s.flow_code,s.step_number,s.approver_type,s.approver_user_id,s.approver_employee_id,s.org_id,e.employee_code,e.employee_name,o.org_code,o.org_name,u.username AS configured_username')
            ->from('sys_approval_flow_step s')
            ->join('hr_employee e', 'e.employee_id=s.approver_employee_id AND e.tenant_id='.$tenant, 'left')
            ->join('hr_org o', 'o.org_id=s.org_id AND o.tenant_id='.$tenant, 'left')
            ->join('app_user u', 'u.user_id=s.approver_user_id AND u.tenant_id='.$tenant, 'left')
            ->where('s.flow_code',$flow_code)->order_by('s.step_number')->get()->result_array();
    }

    private function effective_step($flow_code, $step_number)
    {
        foreach ($this->effective_steps($flow_code) as $step) {
            if ((int)$step['step_number'] === (int)$step_number) { return $step; }
        }
        return NULL;
    }

    private function flow_override($flow_code)
    {
        return $this->db->where(array('tenant_id'=>$this->tenant,'flow_code'=>(string)$flow_code))
            ->limit(1)->get('app_approval_flow')->row_array();
    }

    public function save_override($flow_code, $steps)
    {
        if (!$this->available()) { return FALSE; }
        $flow_code = trim((string)$flow_code);
        if ($flow_code === '' || !in_array($flow_code, $this->accessible_flow_codes(), TRUE)) { return FALSE; }
        $flow_exists = $this->db->where('flow_code',$flow_code)->count_all_results('sys_approval_flow') === 1;
        if (!$flow_exists) { return FALSE; }
        $normalized_steps = $this->normalize_steps($steps, TRUE);
        if ($normalized_steps === FALSE) { return FALSE; }
        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();
        $override = $this->flow_override($flow_code);
        if ($override) {
            $flow_id = (int)$override['flow_id'];
            $this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>$flow_id))
                ->update('app_approval_flow', array('update_on'=>$now));
        } else {
            if (!$this->db->insert('app_approval_flow', array(
                'tenant_id'=>$this->tenant,'flow_code'=>$flow_code,'create_on'=>$now,'update_on'=>$now,
            ))) { $this->db->trans_rollback(); return FALSE; }
            $flow_id = (int)$this->db->insert_id();
        }
        if ($this->db->where(array('tenant_id'=>$this->tenant,'flow_id'=>$flow_id))->delete('app_approval_flow_step') !== TRUE) {
            $this->db->trans_rollback(); return FALSE;
        }
        foreach ($normalized_steps as $i=>$step) {
            $row = array_merge($step, array('tenant_id'=>$this->tenant,'flow_id'=>$flow_id,'step_number'=>$i+1,'create_on'=>$now));
            if (!$this->db->insert('app_approval_flow_step', $row)) { $this->db->trans_rollback(); return FALSE; }
        }
        if ($this->db->trans_status() === FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        return TRUE;
    }

    private function normalize_steps($steps, $allow_empty = FALSE)
    {
        $steps = is_array($steps) ? array_values($steps) : array();
        if ((!$steps && !$allow_empty) || count($steps) > 20) { return FALSE; }
        $normalized = array();
        foreach ($steps as $step) {
            if (!is_array($step)) { return FALSE; }
            $type = isset($step['approver_type']) ? trim((string)$step['approver_type']) : '';
            $employee_id = isset($step['approver_employee_id']) ? trim((string)$step['approver_employee_id']) : '';
            $org_id = isset($step['org_id']) ? trim((string)$step['org_id']) : '';
            $user_id = 0;
            if ($type === 'superior') {
                $employee_id = ''; $org_id = '';
            } elseif ($type === 'organization') {
                if (!ctype_digit($org_id) || !$this->org_exists((int)$org_id)) { return FALSE; }
                $employee_id = '';
            } elseif ($type === 'employee') {
                if (!ctype_digit($employee_id) || !$this->hierarchy_employee_exists((int)$employee_id)) { return FALSE; }
                $account = $this->employee_user((int)$employee_id);
                if (!$account['ok']) { return FALSE; }
                $user_id = (int)$account['user_id']; $org_id = '';
            } elseif ($type === 'user') {
                $user_id = isset($step['approver_user_id']) ? (int)$step['approver_user_id'] : 0;
                if ($user_id < 1 || !$this->user_exists($user_id)) { return FALSE; }
                $employee_id = ''; $org_id = '';
            } else {
                return FALSE;
            }
            $normalized[] = array(
                'approver_type' => $type,
                'approver_user_id' => $user_id > 0 ? $user_id : NULL,
                'approver_employee_id' => $employee_id === '' ? NULL : (int)$employee_id,
                'org_id' => $org_id === '' ? NULL : (int)$org_id,
            );
        }
        return $normalized;
    }

    public function pending()
    {
        if (!$this->available()) { return array(); }
        $requests = $this->db->select('r.request_id,r.flow_code,r.subject,r.details,r.create_on,r.current_step,r.current_approver_user_id,f.flow_name,u.username AS requester_name')
            ->from('app_approval_request r')
            ->join('sys_approval_flow f','f.flow_code=r.flow_code','left')
            ->join('app_user u','u.user_id=r.requester_user_id AND u.tenant_id=r.tenant_id','left')
            ->where('r.tenant_id',$this->tenant)->where('r.status','pending')
            ->order_by('r.create_on','ASC')->get()->result_array();
        $pending = array();
        $steps_cache = array();
        foreach ($requests as $request) {
            $flow_code = (string)$request['flow_code'];
            if (!isset($steps_cache[$flow_code])) { $steps_cache[$flow_code] = $this->effective_steps($flow_code); }
            $step = NULL;
            foreach ($steps_cache[$flow_code] as $candidate) {
                if ((int)$candidate['step_number'] === (int)$request['current_step']) { $step = $candidate; break; }
            }
            $assigned = (int)$request['current_approver_user_id'];
            $matches = $assigned > 0
                ? $assigned === $this->user
                : ($step && (int)$step['approver_user_id'] === $this->user);
            if ($matches) {
                $request['flow_name'] = isset($request['flow_name']) && $request['flow_name'] !== NULL
                    ? (string)$request['flow_name'] : $flow_code;
                $pending[] = $request;
            }
        }
        return $pending;
    }

    public function count_my_approval_requests($search = '', $filters = array())
    {
        if (!$this->available()) { return FALSE; }
        $this->db->from('app_approval_request r')
            ->join('sys_approval_flow f','f.flow_code=r.flow_code','left')
            ->where(array('r.tenant_id'=>$this->tenant,'r.requester_user_id'=>$this->user));
        $this->apply_my_approval_search($search);
        $this->apply_my_approval_filters($filters);
        return (int)$this->db->count_all_results();
    }

    /** Page this user's requests and include the ordered outcome of every flow step. */
    public function my_approval_requests($search = '', $limit = 10, $offset = 0, $sort = 'create_on', $direction = 'DESC', $filters = array())
    {
        if (!$this->available()) { return array(); }
        $sort=in_array($sort,array('subject','flow_code','status','create_on'),TRUE)?$sort:'create_on';
        $sort='r.'.$sort;
        $direction=strtoupper($direction)==='ASC'?'ASC':'DESC';
        $this->db->select('r.request_id,r.flow_id,r.flow_code,r.requester_employee_id,r.current_approver_user_id,r.subject,r.details,r.status,r.current_step,r.create_on,r.update_on,f.flow_name',FALSE)
            ->from('app_approval_request r')
            ->join('sys_approval_flow f','f.flow_code=r.flow_code','left')
            ->join('app_user ca','ca.user_id=r.current_approver_user_id AND ca.tenant_id=r.tenant_id','left')
            ->select("COALESCE(NULLIF(ca.full_name,''),ca.username) AS current_approver_name",FALSE)
            ->where(array('r.tenant_id'=>$this->tenant,'r.requester_user_id'=>$this->user));
        $this->apply_my_approval_search($search);
        $this->apply_my_approval_filters($filters);
        $requests=$this->db->order_by($sort,$direction)->order_by('r.request_id','DESC')
            ->limit(min(50,max(1,(int)$limit)),max(0,(int)$offset))->get()->result_array();
        if (!$requests) { return array(); }

        $request_ids=array_map('intval',array_column($requests,'request_id'));
        $flow_codes=array_values(array_unique(array_filter(array_map('strval',array_column($requests,'flow_code')))));
        $steps_by_flow=array();
        foreach ($flow_codes as $flow_code) {
            $steps_by_flow[(string)$flow_code]=$this->effective_steps((string)$flow_code);
        }

        $actions_by_request=array();
        if ($request_ids) {
            $actions=$this->db->select('a.action_id,a.request_id,a.step_number,a.approver_user_id,a.decision,a.note,a.create_on,u.username AS approver_name')
                ->from('app_approval_action a')
                ->join('app_user u','u.user_id=a.approver_user_id AND u.tenant_id=a.tenant_id','left')
                ->where('a.tenant_id',$this->tenant)->where_in('a.request_id',$request_ids)
                ->order_by('a.request_id','ASC')->order_by('a.action_id','ASC')->get()->result_array();
            foreach ($actions as $action) { $actions_by_request[(int)$action['request_id']][(int)$action['step_number']]=$action; }
        }

        $errors_by_request=array();
        if ($request_ids) {
            $errors=$this->db->where(array('tenant_id'=>$this->tenant,'status'=>'open'))
                ->where_in('request_id',$request_ids)->order_by('error_id','DESC')->get('app_approval_error')->result_array();
            foreach ($errors as $error) {
                $id=(int)$error['request_id'];
                if (!isset($errors_by_request[$id])) { $errors_by_request[$id]=$error; }
            }
        }

        foreach ($requests as &$request) {
            $request_id=(int)$request['request_id'];
            $flow_code=(string)$request['flow_code'];
            $steps=isset($steps_by_flow[$flow_code])?$steps_by_flow[$flow_code]:array();
            $actions=isset($actions_by_request[$request_id])?$actions_by_request[$request_id]:array();
            $request['approval_steps']=array();
            foreach ($steps as $step) {
                $step_number=(int)$step['step_number'];
                $action=isset($actions[$step_number])?$actions[$step_number]:NULL;
                $type=(string)$step['approver_type'];
                if ($type==='organization') {
                    $approver_label=trim((string)$step['org_code'].' — '.(string)$step['org_name']);
                    if ($approver_label==='—') { $approver_label='Atasan organisasi'; }
                } elseif ($type==='employee') {
                    $approver_label=trim((string)$step['employee_name']);
                    if ($approver_label==='' && !empty($step['employee_code'])) { $approver_label=(string)$step['employee_code']; }
                } elseif ($type==='superior') {
                    $approver_label='Atasan';
                } else {
                    $approver_label=(string)$step['configured_username'];
                }
                if ($action) {
                    $step_status=(string)$action['decision'];
                    $action['approver_label']=$action['approver_name']?:$approver_label;
                } elseif ($request['status']==='needs_assignment' && $step_number===(int)$request['current_step']) {
                    $step_status='needs_assignment';
                } elseif ($request['status']==='pending' && $step_number===(int)$request['current_step']) {
                    $step_status='pending';
                } elseif ($request['status']==='rejected' && $step_number>=(int)$request['current_step']) {
                    $step_status='not_reached';
                } else {
                    $step_status='waiting';
                }
                $request['approval_steps'][]=array(
                    'step_number'=>$step_number,'approver_type'=>$type,
                    'approver_label'=>$approver_label,'assigned_approver_name'=>$step_status==='pending'
                        ? (trim((string)$request['current_approver_name'])!==''?(string)$request['current_approver_name']:(($type==='user' && !empty($step['configured_username']))?(string)$step['configured_username']:''))
                        : '',
                    'status'=>$step_status,'action'=>$action,
                );
            }
            $request['latest_action']=NULL;
            if ($actions) { $request['latest_action']=end($actions); }
            $request['open_error']=isset($errors_by_request[$request_id])?$errors_by_request[$request_id]:NULL;
        }
        unset($request);
        return $requests;
    }

    private function apply_my_approval_search($search)
    {
        $search=substr(trim((string)$search),0,120);
        if ($search!=='') {
            $this->db->group_start()->like('r.subject',$search)->or_like('r.flow_code',$search)
                ->or_like('f.flow_name',$search)->group_end();
        }
    }

    private function apply_my_approval_filters($filters)
    {
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        if (isset($fields['subject']) && $fields['subject'] !== '') {
            $this->db->like('r.subject', $fields['subject']);
        }
        if (isset($fields['flow_code']) && $fields['flow_code'] !== '') {
            $this->db->like('r.flow_code', $fields['flow_code']);
        }
        if (isset($fields['status']) && $fields['status'] !== '') {
            $this->db->where('r.status', $fields['status']);
        }
        if (isset($fields['step_status']) && $fields['step_status'] !== '') {
            $step_status = (string) $fields['step_status'];
            if (in_array($step_status, array('approved', 'rejected', 'revise'), TRUE)) {
                $literal = $this->db->escape($step_status);
                $this->db->where(
                    "EXISTS (SELECT 1 FROM app_approval_action a WHERE a.tenant_id = r.tenant_id AND a.request_id = r.request_id AND a.decision = " . $literal . ")",
                    NULL,
                    FALSE
                );
            } else {
                $this->db->where('r.status', $step_status);
            }
        }
        if (!empty($filters['date_ranges']['create_on'])) {
            $range = $filters['date_ranges']['create_on'];
            if (!empty($range['start'])) { $this->db->where('r.create_on >=', $range['start']); }
            if (!empty($range['end'])) { $this->db->where('r.create_on <=', $range['end'] . ' 23:59:59'); }
        }
    }

    public function active_flows()
    {
        if (!$this->available()) { return array(); }
        $codes = $this->accessible_flow_codes();
        if (!$codes) { return array(); }
        return $this->db->select('flow_code, flow_name')->from('sys_approval_flow')
            ->where_in('flow_code', $codes)->where('active_flag', 1)
            ->order_by('flow_name')->get()->result_array();
    }

    public function flow_suggestions($term)
    {
        if (!$this->available()) { return array(); }
        $term=substr(trim((string)$term),0,64);
        if (strlen($term)<2) { return array(); }
        $codes = $this->accessible_flow_codes();
        if (!$codes) { return array(); }
        $rows=$this->db->select('flow_code,flow_name')->from('sys_approval_flow')
            ->where_in('flow_code', $codes)->where('active_flag', 1)
            ->group_start()->like('flow_code',$term)->or_like('flow_name',$term)->group_end()
            ->order_by('flow_name')->limit(10)->get()->result_array();
        return array_map(function($row) {
            return array('id'=>(string)$row['flow_code'],'value'=>(string)$row['flow_code'],
                'label'=>(string)$row['flow_name'].' — '.(string)$row['flow_code']);
        },$rows);
    }

    public function approval_employee_suggestions($term)
    {
        if (!$this->available() || !$this->db->field_exists('employee_id','app_user')) { return array(); }
        $term=substr(trim((string)$term),0,80);
        if (strlen($term)<2) { return array(); }
        $rows=$this->db->select('e.employee_id,e.employee_code,e.employee_name,COUNT(DISTINCT u.user_id) AS account_count',FALSE)
            ->from('hr_employee e')
            ->join('app_user u','u.employee_id=e.employee_id AND u.tenant_id=e.tenant_id AND u.block_flag=0','inner')
            ->join('app_role r','r.role_id=u.role_id AND r.tenant_id=u.tenant_id AND r.active_flag=1','inner')
            ->where('e.tenant_id',$this->tenant)
            ->group_start()->like('e.employee_code',$term)->or_like('e.employee_name',$term)->group_end()
            ->group_by(array('e.employee_id','e.employee_code','e.employee_name'))
            ->having('COUNT(DISTINCT u.user_id) = 1',NULL,FALSE)
            ->order_by('e.employee_name')->limit(10)->get()->result_array();
        return array_map(function($row) {
            return array('id'=>(int)$row['employee_id'],'value'=>(int)$row['employee_id'],
                'label'=>(string)$row['employee_name'].' ('.(string)$row['employee_code'].')');
        },$rows);
    }

    public function approval_organization_suggestions($term)
    {
        if (!$this->available()) { return array(); }
        $term=substr(trim((string)$term),0,80);
        if (strlen($term)<2) { return array(); }
        $rows=$this->db->select('org_id,org_code,org_name')->from('hr_org')
            ->where('tenant_id',$this->tenant)
            ->group_start()->like('org_code',$term)->or_like('org_name',$term)->group_end()
            ->order_by('org_name')->limit(10)->get()->result_array();
        return array_map(function($row) {
            return array('id'=>(int)$row['org_id'],'value'=>(int)$row['org_id'],
                'label'=>(string)$row['org_code'].' — '.(string)$row['org_name']);
        },$rows);
    }

    public function count_approval_errors($search = '', $filters = array())
    {
        if (!$this->available()) { return FALSE; }
        $search=substr(trim((string)$search),0,120);
        $this->db->from('app_approval_error x')
            ->join('app_approval_request r','r.request_id=x.request_id AND r.tenant_id=x.tenant_id','inner')
            ->join('app_user u','u.user_id=r.requester_user_id AND u.tenant_id=r.tenant_id','left')
            ->join('hr_org o','o.org_id=x.org_id AND o.tenant_id=x.tenant_id','left')
            ->where(array('x.tenant_id'=>$this->tenant,'x.status'=>'open'));
        if ($search!=='') {
            $this->db->group_start()->like('r.subject',$search)->or_like('r.flow_code',$search)
                ->or_like('x.error_code',$search)->or_like('x.error_message',$search)->group_end();
        }
        $this->apply_approval_error_filters($filters);
        return (int)$this->db->count_all_results();
    }

    public function approval_errors($search = '', $limit = NULL, $offset = 0, $sort = 'create_on', $direction = 'DESC', $filters = array())
    {
        if (!$this->available()) { return array(); }
        $search=substr(trim((string)$search),0,120);
        $this->db->select('x.error_id,x.request_id,x.flow_id,x.step_number,x.org_id,x.error_code,x.error_message,x.create_on,r.flow_code,r.subject,r.details,r.requester_user_id,u.username AS requester_name,f.flow_name,o.org_code,o.org_name')
            ->from('app_approval_error x')
            ->join('app_approval_request r','r.request_id=x.request_id AND r.tenant_id=x.tenant_id','inner')
            ->join('app_user u','u.user_id=r.requester_user_id AND u.tenant_id=r.tenant_id','left')
            ->join('sys_approval_flow f','f.flow_code=r.flow_code','left')
            ->join('hr_org o','o.org_id=x.org_id AND o.tenant_id=x.tenant_id','left')
            ->where(array('x.tenant_id'=>$this->tenant,'x.status'=>'open'));
        if ($search!=='') {
            $this->db->group_start()->like('r.subject',$search)->or_like('r.flow_code',$search)
                ->or_like('x.error_code',$search)->or_like('x.error_message',$search)->group_end();
        }
        $this->apply_approval_error_filters($filters);
        $sort=in_array($sort,array('subject','flow_code','error_code','create_on'),TRUE)?$sort:'create_on';
        $sort=$sort==='subject'?'r.subject':($sort==='flow_code'?'r.flow_code':($sort==='error_code'?'x.error_code':'x.create_on'));
        $this->db->order_by($sort,strtoupper($direction)==='ASC'?'ASC':'DESC');
        if ($limit!==NULL) { $this->db->limit(min(50,max(1,(int)$limit)),max(0,(int)$offset)); }
        return $this->db->get()->result_array();
    }

    private function apply_approval_error_filters($filters)
    {
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        foreach (array(
            'subject' => 'r.subject',
            'requester_name' => 'u.username',
            'flow_code' => 'r.flow_code',
            'error_code' => 'x.error_code',
        ) as $name => $column) {
            if (isset($fields[$name]) && $fields[$name] !== '') {
                $this->db->like($column, $fields[$name]);
            }
        }
        if (isset($fields['org']) && $fields['org'] !== '') {
            $this->db->group_start()
                ->like('o.org_code', $fields['org'])
                ->or_like('o.org_name', $fields['org'])
                ->group_end();
        }
        if (!empty($filters['date_ranges']['create_on'])) {
            $range = $filters['date_ranges']['create_on'];
            if (!empty($range['start'])) { $this->db->where('x.create_on >=', $range['start']); }
            if (!empty($range['end'])) { $this->db->where('x.create_on <=', $range['end'] . ' 23:59:59'); }
        }
    }

    public function assign_approval_error($error_id, $employee_id)
    {
        if (!$this->available() || (int)$error_id<1 || (int)$employee_id<1) { return 'error'; }
        $account=$this->employee_user((int)$employee_id);
        if (!$account['ok']) { return 'employee_unavailable'; }
        $error=$this->db->where(array('tenant_id'=>$this->tenant,'error_id'=>(int)$error_id,'status'=>'open'))
            ->limit(1)->get('app_approval_error')->row_array();
        if (!$error) { return 'not_found'; }
        $request=$this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$error['request_id'],'status'=>'needs_assignment'))
            ->limit(1)->get('app_approval_request')->row_array();
        if (!$request) { return 'not_found'; }

        $now=date('Y-m-d H:i:s');
        $this->db->trans_begin();
        $this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$request['request_id'],'status'=>'needs_assignment'))
            ->update('app_approval_request',array('status'=>'pending','current_approver_user_id'=>(int)$account['user_id'],'update_on'=>$now));
        if ($this->db->affected_rows()!==1) { $this->db->trans_rollback(); return 'not_found'; }
        $this->db->where(array('tenant_id'=>$this->tenant,'error_id'=>(int)$error_id,'status'=>'open'))
            ->update('app_approval_error',array('status'=>'resolved','assigned_employee_id'=>(int)$employee_id,
                'assigned_approver_user_id'=>(int)$account['user_id'],'handled_by'=>$this->user,'update_on'=>$now,'resolved_on'=>$now));
        if ($this->db->affected_rows()!==1
            || !$this->send_approval_links((int)$request['request_id'],(int)$account['user_id'],(string)$request['subject'])
            || $this->db->trans_status()===FALSE) {
            $this->db->trans_rollback();
            return 'error';
        }
        $this->db->trans_commit();
        return $this->db->trans_status()===FALSE ? 'error' : 'routed';
    }

    public function submit($flow_code, $subject, $details, $source_type = NULL, $source_id = NULL, $requester_user_id = NULL)
    {
        $flow_code=trim((string)$flow_code); $subject=trim((string)$subject); $details=trim((string)$details);
        if (!$this->available() || $flow_code === '' || strlen($flow_code)>32
            || !preg_match('/\A[A-Za-z0-9_.-]+\z/',$flow_code)
            || !$subject || strlen($subject)>180 || strlen($details)>10000
            || !in_array($flow_code, $this->accessible_flow_codes(), TRUE)) { return FALSE; }
        $requester_user_id = $requester_user_id === NULL ? (int) $this->user : (int) $requester_user_id;
        $requester = $this->db->select('user_id,employee_id')->where(array(
            'tenant_id'=>$this->tenant,'user_id'=>$requester_user_id,
        ))->limit(1)->get('app_user')->row_array();
        if (!$requester) { return FALSE; }
        $requester_employee_id = !empty($requester['employee_id']) ? (int) $requester['employee_id'] : NULL;
        $submitted_by = (int) $this->user;
        $flow=$this->db->select('flow_code,flow_name')->from('sys_approval_flow')
            ->where(array('flow_code'=>$flow_code,'active_flag'=>1))->limit(1)->get()->row_array();
        if (!$flow) { return FALSE; }
        $override=$this->flow_override($flow_code);
        $flow_id=$override ? (int)$override['flow_id'] : NULL;
        $steps=$this->effective_steps($flow_code);
        $created=date('Y-m-d H:i:s');
        if (!$steps) {
            $this->db->trans_begin();
            $inserted=$this->db->insert('app_approval_request',array(
                'tenant_id'=>$this->tenant,'flow_id'=>$flow_id,'flow_code'=>$flow_code,
                'requester_user_id'=>$requester_user_id,'requester_employee_id'=>$requester_employee_id,
                'submitted_by'=>$submitted_by,
                'subject'=>$subject,'details'=>$details,'status'=>'approved','current_step'=>0,
                'current_approver_user_id'=>NULL,'create_on'=>$created,'update_on'=>$created,
                'source_type'=>$source_type !== NULL ? (string)$source_type : NULL,
                'source_id'=>$source_id !== NULL ? (int)$source_id : NULL,
            ));
            if (!$inserted || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); return FALSE; }
            $request_id=(int)$this->db->insert_id();
            $this->finalize_source(array(
                'source_type'=>$source_type,'source_id'=>$source_id,'status'=>'approved',
            ));
            if ($this->db->trans_status()===FALSE) { $this->db->trans_rollback(); return FALSE; }
            $this->db->trans_commit();
            return $this->db->trans_status()===FALSE ? FALSE : array('status'=>'auto_approved','request_id'=>$request_id);
        }
        $step=isset($steps[0])?$steps[0]:NULL;
        $resolution=$step ? $this->resolve_approver($step,$requester_employee_id ?: 0,0) : array(
            'ok'=>FALSE,'code'=>'empty_flow','message'=>'The active approval flow has no configured steps.',
        );
        $request_status=$resolution['ok'] ? 'pending' : 'needs_assignment';
        $this->db->trans_begin();
        $inserted=$this->db->insert('app_approval_request',array(
            'tenant_id'=>$this->tenant,'flow_id'=>$flow_id,'flow_code'=>$flow_code,
            'requester_user_id'=>$requester_user_id,'requester_employee_id'=>$requester_employee_id,
            'submitted_by'=>$submitted_by,
            'subject'=>$subject,'details'=>$details,'status'=>$request_status,'current_step'=>1,
            'current_approver_user_id'=>$resolution['ok'] ? (int)$resolution['user_id'] : NULL,
            'create_on'=>$created,'update_on'=>$created,
            'source_type'=>$source_type !== NULL ? (string)$source_type : NULL,
            'source_id'=>$source_id !== NULL ? (int)$source_id : NULL,
        ));
        if (!$inserted) { $this->db->trans_rollback(); return FALSE; }
        $request_id=(int)$this->db->insert_id();
        if (!$resolution['ok']
            && !$this->insert_approval_error($request_id,$flow_id,1,$resolution['code'],$resolution['message'])) {
            $this->db->trans_rollback(); return FALSE;
        }
        if ($this->db->trans_status()===FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        if ($this->db->trans_status()===FALSE) { return FALSE; }
        if (!$resolution['ok']) { return array('status'=>'needs_assignment','request_id'=>$request_id); }
        if ($this->deliver_approval($request_id,(int)$resolution['user_id'],$subject)) {
            return array('status'=>'submitted','request_id'=>$request_id);
        }
        if (!$this->flag_request_error($request_id,$flow_id,1,'notification_failed','Approval notification could not be delivered.')) { return FALSE; }
        return array('status'=>'needs_assignment','request_id'=>$request_id);
    }

    private function create_approval_token($request_id,$approver,$decision)
    {
        try { $token=bin2hex(random_bytes(32)); } catch (Exception $e) { return FALSE; }
        $expires=date('Y-m-d H:i:s',time()+86400);
        $ok=$this->db->insert('app_approval_link_token',array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'approver_user_id'=>(int)$approver,'decision'=>$decision,'token_hash'=>hash('sha256',$token),'expires_at'=>$expires,'used_at'=>NULL,'create_on'=>date('Y-m-d H:i:s')));
        return $ok ? $token : FALSE;
    }

    private function notify_approver($approver,$subject,$view_token)
    {
        $fields=array();
        foreach ((array)$this->db->field_data('app_notification') as $field) { if (isset($field->name)) { $fields[(string)$field->name]=TRUE; } }
        if (!isset($fields['user_id'],$fields['title'],$fields['message'])) { return FALSE; }
        $base=rtrim(site_url('admin/system/approval'),'/');
        $message='Approval diperlukan: '.$subject."\nLihat: ".$base.'?approval_token='.rawurlencode($view_token).'&approval_decision=view';
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
        if (!$request) { return FALSE; }
        $requester_name='';
        if (!empty($request['requester_user_id'])) {
            $requester=$this->db->select('full_name,username')->where(array('tenant_id'=>$this->tenant,'user_id'=>(int)$request['requester_user_id']))->limit(1)->get('app_user')->row_array();
            if ($requester) {
                $requester_name=trim((string)$requester['full_name']) !== '' ? (string)$requester['full_name'] : (string)$requester['username'];
            }
        }
        $submitted_by_name='';
        if (!empty($request['submitted_by'])) {
            $submitter=$this->db->select('full_name,username')->where(array('tenant_id'=>$this->tenant,'user_id'=>(int)$request['submitted_by']))->limit(1)->get('app_user')->row_array();
            if ($submitter) {
                $submitted_by_name=trim((string)$submitter['full_name']) !== '' ? (string)$submitter['full_name'] : (string)$submitter['username'];
            }
        }
        return array(
            'request_id'=>(int)$request['request_id'],
            'subject'=>(string)$request['subject'],
            'details'=>(string)$request['details'],
            'decision'=>(string)$row['decision'],
            'requester_name'=>$requester_name,
            'submitted_by_name'=>$submitted_by_name,
            'create_on'=>(string)$request['create_on'],
            'flow_code'=>(string)$request['flow_code'],
        );
    }

    public function decide_approval_token($token,$decision,$note='')
    {
        $request=$this->approval_token_request($token);
        $decision=(string)$decision;
        $note=trim((string)$note);
        if (!$request || !in_array($decision,array('approved','rejected','revise'),TRUE)
            || ($request['decision']!=='view' && $request['decision']!==$decision)
            || ($decision==='revise' && $note==='')) { return FALSE; }
        if (!$this->decide($request['request_id'],$decision,$note)) { return FALSE; }
        $this->db->where(array('tenant_id'=>$this->tenant,'approver_user_id'=>$this->user,'token_hash'=>hash('sha256',$token)))->where('used_at IS NULL',NULL,FALSE)->update('app_approval_link_token',array('used_at'=>date('Y-m-d H:i:s')));
        return $this->db->affected_rows()===1;
    }

    public function decide($request_id, $decision, $note)
    {
        if (!in_array($decision,array('approved','rejected','revise'),TRUE)) { return FALSE; }
        $note = trim((string)$note);
        if ($decision === 'revise' && $note === '') { return FALSE; }
        $this->db->trans_begin();
        $request=$this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'status'=>'pending'))->get('app_approval_request')->row_array();
        if (!$request) { $this->db->trans_rollback(); return FALSE; }
        $current_step=(int)$request['current_step'];
        $step=$this->effective_step((string)$request['flow_code'],$current_step);
        $assigned_user=isset($request['current_approver_user_id'])?(int)$request['current_approver_user_id']:0;
        if (($assigned_user>0 && $assigned_user!==$this->user)
            || ($assigned_user<1 && (!$step || (int)$step['approver_user_id']!==$this->user))) {
            $this->db->trans_rollback(); return FALSE;
        }
        if (!$step || !$this->db->insert('app_approval_action',array(
            'tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'step_number'=>$current_step,
            'approver_user_id'=>$this->user,'decision'=>$decision,'note'=>$note,'create_on'=>date('Y-m-d H:i:s'),
        ))) { $this->db->trans_rollback(); return FALSE; }

        $next_step=NULL;
        $resolution=NULL;
        $new_status=$decision==='rejected'?'rejected':($decision==='revise'?'revise':'approved');
        $next_number=$current_step;
        $next_user=NULL;
        if ($decision==='approved') {
            $next_number=$current_step+1;
            $next_step=$this->effective_step((string)$request['flow_code'],$next_number);
            if ($next_step) {
                $resolution=$this->resolve_approver(
                    $next_step,
                    isset($request['requester_employee_id'])?(int)$request['requester_employee_id']:0,
                    $this->user,
                    isset($step['approver_type']) && $step['approver_type']==='organization' ? (int)$step['org_id'] : 0
                );
                if ($resolution['ok']) {
                    $new_status='pending';
                    $next_user=(int)$resolution['user_id'];
                } else {
                    $new_status='needs_assignment';
                }
            }
        }
        $this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'status'=>'pending','current_step'=>$current_step))
            ->update('app_approval_request',array('status'=>$new_status,'current_step'=>$next_number,
                'current_approver_user_id'=>$next_user,'update_on'=>date('Y-m-d H:i:s')));
        if ($this->db->affected_rows()!==1) { $this->db->trans_rollback(); return FALSE; }
        if ($next_step && $resolution && !$resolution['ok']
            && !$this->insert_approval_error((int)$request_id,(int)$request['flow_id'],$next_number,
                $resolution['code'],$resolution['message'],isset($resolution['org_id'])?$resolution['org_id']:NULL)) {
            $this->db->trans_rollback(); return FALSE;
        }
        if ($new_status === 'approved' || $new_status === 'revise' || $new_status === 'rejected') {
            $this->finalize_source(array(
                'source_type'=>isset($request['source_type']) ? $request['source_type'] : NULL,
                'source_id'=>isset($request['source_id']) ? $request['source_id'] : NULL,
                'status'=>$new_status,
            ));
        }
        if ($this->db->trans_status()===FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        if ($this->db->trans_status()===FALSE) { return FALSE; }
        if ($next_step && $resolution && $resolution['ok']
            && !$this->deliver_approval((int)$request_id,$next_user,(string)$request['subject'])) {
            return $this->flag_request_error((int)$request_id,(int)$request['flow_id'],$next_number,
                'notification_failed','Approval notification could not be delivered.',
                isset($resolution['org_id'])?$resolution['org_id']:NULL);
        }
        return TRUE;
    }

    private function finalize_source($request)
    {
        $type = isset($request['source_type']) ? (string)$request['source_type'] : '';
        $id = isset($request['source_id']) ? (int)$request['source_id'] : 0;
        $status = isset($request['status']) ? (string)$request['status'] : '';
        $targets = array(
            'fin_transaction' => array('table' => 'fin_transaction', 'id' => 'transaction_id'),
            'fin_cash_transfer' => array('table' => 'fin_cash_transfer', 'id' => 'cash_transfer_id'),
            'fin_journal_entry' => array('table' => 'fin_journal_entry', 'id' => 'journal_entry_id'),
            'fin_sales_invoice' => array('table' => 'fin_sales_invoice', 'id' => 'sales_invoice_id'),
            'fin_purchase_bill' => array('table' => 'fin_purchase_bill', 'id' => 'purchase_bill_id'),
            'fin_customer_payment' => array('table' => 'fin_customer_payment', 'id' => 'customer_payment_id'),
            'fin_supplier_payment' => array('table' => 'fin_supplier_payment', 'id' => 'supplier_payment_id'),
            'fin_bank_reconciliation' => array('table' => 'fin_bank_reconciliation', 'id' => 'reconciliation_id'),
        );
        if (!isset($targets[$type]) || $id < 1
            || !in_array($status, array('approved','revise','rejected'), TRUE)
            || !$this->db->table_exists($targets[$type]['table'])) {
            return TRUE;
        }
        $this->db->where(array('tenant_id'=>$this->tenant,$targets[$type]['id']=>$id))
            ->where_in('status', array('pending_approval','revise'))
            ->update($targets[$type]['table'], array('status'=>$status));
        return $this->db->trans_status() !== FALSE;
    }

    private function deliver_approval($request_id,$approver,$subject)
    {
        if (!$this->db->table_exists('app_approval_link_token') || !$this->db->table_exists('app_notification')) { return FALSE; }
        $this->db->trans_begin();
        $sent=$this->send_approval_links($request_id,$approver,$subject);
        if (!$sent || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        return $this->db->trans_status()!==FALSE;
    }

    private function send_approval_links($request_id,$approver,$subject)
    {
        if (!$this->db->table_exists('app_approval_link_token') || !$this->db->table_exists('app_notification')) { return FALSE; }
        $view_token=$this->create_approval_token($request_id,$approver,'view');
        return $view_token && $this->notify_approver($approver,$subject,$view_token);
    }

    public function users()
    {
        if (!$this->available()) { return array(); }
        return $this->db->select('user_id, username')->where('tenant_id',$this->tenant)->order_by('username')->get('app_user')->result_array();
    }

    /** Resolve a configured step to exactly one active, tenant-local app user. */
    private function resolve_approver($step, $requester_employee_id, $previous_approver_user_id, $previous_org_id = 0)
    {
        $type = isset($step['approver_type']) ? (string)$step['approver_type'] : 'user';
        if ($type === 'user') {
            $user_id = isset($step['approver_user_id']) ? (int)$step['approver_user_id'] : 0;
            if ($user_id > 0 && $this->user_exists($user_id)) {
                return array('ok'=>TRUE,'user_id'=>$user_id,'employee_id'=>0,'org_id'=>NULL);
            }
            return array('ok'=>FALSE,'code'=>'legacy_approver_missing','message'=>'The configured approver account is unavailable.','org_id'=>NULL);
        }
        if ($type === 'employee') {
            $employee_id = isset($step['approver_employee_id']) ? (int)$step['approver_employee_id'] : 0;
            $account = $this->employee_user($employee_id);
            if (!$account['ok']) { return $account; }
            return array('ok'=>TRUE,'user_id'=>$account['user_id'],'employee_id'=>$employee_id,'org_id'=>NULL);
        }
        if ($type === 'organization') {
            $org_id = isset($step['org_id']) ? (int)$step['org_id'] : 0;
            $org = $org_id > 0 ? $this->hierarchy_org($org_id) : FALSE;
            if (!is_array($org)) {
                return array('ok'=>FALSE,'code'=>'organization_missing','message'=>'The selected organization is unavailable.','org_id'=>$org_id ?: NULL);
            }
            $employee_id = isset($org['head_employee_id']) ? (int)$org['head_employee_id'] : 0;
            if ($employee_id < 1) {
                return array('ok'=>FALSE,'code'=>'organization_head_missing','message'=>'The selected organization has no assigned head.','org_id'=>$org_id);
            }
            $account = $this->employee_user($employee_id);
            if (!$account['ok']) { $account['org_id']=$org_id; return $account; }
            return array('ok'=>TRUE,'user_id'=>$account['user_id'],'employee_id'=>$employee_id,'org_id'=>$org_id);
        }
        if ($type === 'superior') {
            $employee_id = 0;
            if ((int)$previous_approver_user_id > 0) {
                $previous = $this->db->select('employee_id')->where(array(
                    'tenant_id'=>$this->tenant,'user_id'=>(int)$previous_approver_user_id,
                ))->limit(1)->get('app_user')->row_array();
                $employee_id = is_array($previous) && isset($previous['employee_id']) ? (int)$previous['employee_id'] : 0;
                if ($employee_id < 1) {
                    return array('ok'=>FALSE,'code'=>'approver_employee_unlinked','message'=>'The previous approver is not linked to an employee record.','org_id'=>NULL);
                }
            } else {
                $employee_id = (int)$requester_employee_id;
            }
            if ($employee_id < 1 || !$this->hierarchy_employee_exists($employee_id)) {
                return array('ok'=>FALSE,'code'=>'requester_employee_missing','message'=>'The requester is not linked to an employee record.','org_id'=>NULL);
            }
            if ((int)$previous_org_id>0) {
                $previous_org=$this->hierarchy_org((int)$previous_org_id);
                if (!is_array($previous_org)) {
                    return array('ok'=>FALSE,'code'=>'organization_missing','message'=>'The organization from the previous approval step is unavailable.','org_id'=>(int)$previous_org_id);
                }
                $parent_id=isset($previous_org['parent_org'])?(int)$previous_org['parent_org']:0;
                if ($parent_id<1) {
                    return array('ok'=>FALSE,'code'=>'organization_parent_missing','message'=>'The selected organization has no parent organization to approve this step.','org_id'=>(int)$previous_org_id);
                }
                $org=$this->hierarchy_org($parent_id);
                if (!is_array($org)) {
                    return array('ok'=>FALSE,'code'=>'organization_parent_missing','message'=>'The parent organization could not be found.','org_id'=>$parent_id);
                }
            } else {
                $headed_orgs = $this->db->select('org_id,parent_org,head_employee_id')
                    ->from('hr_org')
                    ->where(array('tenant_id'=>$this->tenant,'head_employee_id'=>$employee_id))
                    ->order_by('org_id','ASC')->get()->result_array();
                $memberships = $this->db->distinct()
                    ->select('o.org_id,o.parent_org,o.head_employee_id')
                    ->from('hr_org_member m')->join('hr_org o','o.org_id=m.org_id','inner')
                    ->where(array('o.tenant_id'=>$this->tenant,'m.employee_id'=>$employee_id))
                    ->get()->result_array();
                if (count($headed_orgs)>1) {
                    return array('ok'=>FALSE,'code'=>'organization_membership_ambiguous','message'=>'The requester or previous approver leads multiple organizations.','org_id'=>NULL);
                }
                if (count($headed_orgs)===1) {
                    // A leader is approved by the head of the parent organization.
                    $org = $headed_orgs[0];
                } elseif (!$memberships) {
                    return array('ok'=>FALSE,'code'=>'organization_membership_missing','message'=>'No organization membership was found for the requester or previous approver.','org_id'=>NULL);
                } elseif (count($memberships)>1) {
                    return array('ok'=>FALSE,'code'=>'organization_membership_ambiguous','message'=>'The requester or previous approver belongs to multiple organizations.','org_id'=>NULL);
                } else {
                    $org = $memberships[0];
                }
            }
            $visited = array();
            for ($depth=0;$depth<100 && !empty($org['org_id']);$depth++) {
                $org_id=(int)$org['org_id'];
                if (isset($visited[$org_id])) { break; }
                $visited[$org_id]=TRUE;
                $head_id=isset($org['head_employee_id'])?(int)$org['head_employee_id']:0;
                if ($head_id>0 && $head_id!==$employee_id) {
                    $account=$this->employee_user($head_id);
                    if (!$account['ok']) { $account['org_id']=$org_id; return $account; }
                    return array('ok'=>TRUE,'user_id'=>$account['user_id'],'employee_id'=>$head_id,'org_id'=>$org_id);
                }
                $parent_id=isset($org['parent_org'])?(int)$org['parent_org']:0;
                if ($parent_id<1) { break; }
                $org=$this->hierarchy_org($parent_id);
                if (!is_array($org)) { break; }
            }
            return array('ok'=>FALSE,'code'=>'organization_superior_missing','message'=>'No superior could be resolved from the organization hierarchy.','org_id'=>isset($memberships[0]['org_id'])?(int)$memberships[0]['org_id']:NULL);
        }
        return array('ok'=>FALSE,'code'=>'invalid_approver_type','message'=>'The approval step has an unsupported approver type.','org_id'=>isset($step['org_id'])?(int)$step['org_id']:NULL);
    }

    private function employee_user($employee_id)
    {
        $employee_id=(int)$employee_id;
        if ($employee_id<1 || !$this->hierarchy_employee_exists($employee_id) || !$this->db->field_exists('employee_id','app_user')) {
            return array('ok'=>FALSE,'code'=>'employee_account_missing','message'=>'The selected employee has no active user account.','org_id'=>NULL);
        }
        $query=$this->db->select('u.user_id')
            ->from('app_user u')->join('app_role r','r.role_id=u.role_id AND r.tenant_id=u.tenant_id','inner')
            ->where(array('u.tenant_id'=>$this->tenant,'u.employee_id'=>$employee_id,'u.block_flag'=>0,'r.active_flag'=>1))
            ->limit(2)->get();
        if ($query===FALSE || $query->num_rows()===0) {
            return array('ok'=>FALSE,'code'=>'employee_account_missing','message'=>'The selected employee has no active user account.','org_id'=>NULL);
        }
        if ($query->num_rows()!==1) {
            return array('ok'=>FALSE,'code'=>'employee_account_ambiguous','message'=>'The selected employee is linked to multiple active user accounts.','org_id'=>NULL);
        }
        return array('ok'=>TRUE,'user_id'=>(int)$query->row()->user_id,'employee_id'=>$employee_id,'org_id'=>NULL);
    }

    private function insert_approval_error($request_id, $flow_id, $step_number, $error_code, $message, $org_id = NULL)
    {
        return $this->db->insert('app_approval_error',array(
            'tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'flow_id'=>$flow_id ? (int)$flow_id : NULL,
            'step_number'=>max(1,(int)$step_number),'org_id'=>$org_id ? (int)$org_id : NULL,
            'error_code'=>substr((string)$error_code,0,64),'error_message'=>substr((string)$message,0,2000),
            'status'=>'open','create_on'=>date('Y-m-d H:i:s'),
        ));
    }

    private function flag_request_error($request_id, $flow_id, $step_number, $error_code, $message, $org_id = NULL)
    {
        $this->db->trans_begin();
        $this->db->where(array('tenant_id'=>$this->tenant,'request_id'=>(int)$request_id,'status'=>'pending'))
            ->update('app_approval_request',array('status'=>'needs_assignment','current_approver_user_id'=>NULL,'update_on'=>date('Y-m-d H:i:s')));
        if ($this->db->affected_rows()!==1
            || !$this->insert_approval_error($request_id,$flow_id,$step_number,$error_code,$message,$org_id)
            || $this->db->trans_status()===FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return $this->db->trans_status()!==FALSE;
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
