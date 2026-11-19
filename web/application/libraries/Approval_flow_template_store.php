<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Schema-checked data access for global approval flow templates. */
class Approval_flow_template_store
{
    private $db;
    private $schema_checked = FALSE;
    private $schema_valid = FALSE;
    private $fields = array();
    private $tenant_id = 0;

    private $sort_columns = array(
        'flow_code' => 'f.flow_code',
        'flow_name' => 'f.flow_name',
        'description' => 'f.description',
        'active_flag' => 'f.active_flag',
    );

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            if (!isset($CI->db)) {
                $CI->load->database();
            }
            $db = $CI->db;
        }
        $this->db = $db;
        $CI =& get_instance();
        $session = isset($CI->session) ? $CI->session->userdata('admin_user') : NULL;
        $this->tenant_id = is_array($session) && isset($session['tenant_id']) ? (int) $session['tenant_id'] : 0;
    }

    public function schema_available()
    {
        if ($this->schema_checked) {
            return $this->schema_valid;
        }
        $this->schema_checked = TRUE;
        $expected = array(
            'sys_approval_flow' => array('flow_code', 'flow_name', 'description', 'active_flag', 'created_by', 'create_on', 'update_on'),
            'sys_approval_flow_step' => array('flow_step_id', 'flow_code', 'step_number', 'approver_user_id', 'create_on', 'approver_type', 'approver_employee_id', 'org_id'),
            'sys_approval_flow_menu' => array('flow_menu_id', 'flow_code', 'menu_code'),
        );
        foreach ($expected as $table => $columns) {
            if (!$this->db->table_exists($table)) {
                return FALSE;
            }
            foreach ($columns as $column) {
                if (!$this->db->field_exists($column, $table)) {
                    return FALSE;
                }
            }
        }
        $this->fields = $this->db->field_data('sys_approval_flow');
        return $this->schema_valid = TRUE;
    }

    public function field_specs()
    {
        if (!$this->schema_available()) {
            return array();
        }
        $specs = array();
        foreach ((array) $this->fields as $field) {
            if (isset($field->name)) {
                $specs[$field->name] = array(
                    'max_length' => isset($field->max_length) ? (int) $field->max_length : 0,
                );
            }
        }
        return $specs;
    }

    public function count($search, $filters = array())
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $this->db->from('sys_approval_flow f');
        $this->apply_search($search);
        $this->apply_field_filters($filters);
        $count = $this->db->count_all_results();
        return $count === FALSE ? FALSE : (int) $count;
    }

    public function page($search, $limit, $offset, $sort, $direction, $filters = array())
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $menu_codes = "(SELECT GROUP_CONCAT(m.menu_code ORDER BY m.menu_code SEPARATOR ', ')"
            . " FROM sys_approval_flow_menu m WHERE m.flow_code = f.flow_code) AS menu_codes";
        $query = $this->db->select('f.flow_code, f.flow_name, f.description, f.active_flag, ' . $menu_codes, FALSE)
            ->from('sys_approval_flow f');
        $this->apply_search($search);
        $this->apply_field_filters($filters);
        $sort_column = isset($this->sort_columns[$sort]) ? $this->sort_columns[$sort] : 'f.flow_code';
        $query->order_by($sort_column, strtoupper((string) $direction) === 'DESC' ? 'DESC' : 'ASC')
            ->limit(max(1, min(100, (int) $limit)), max(0, (int) $offset));
        $result = $query->get();
        if ($result === FALSE) {
            return FALSE;
        }
        $records = $result->result_array();
        foreach ($records as &$record) {
            $record['active_flag'] = (int) $record['active_flag'] === 1 ? 1 : 0;
            $record['menu_codes'] = isset($record['menu_codes']) ? (string) $record['menu_codes'] : '';
        }
        unset($record);
        return $records;
    }

    public function find($flow_code)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $flow_code = (string) $flow_code;
        $query = $this->db->select('flow_code, flow_name, description, active_flag, created_by, create_on, update_on')
            ->from('sys_approval_flow')->where('flow_code', $flow_code)->limit(1)->get();
        if ($query === FALSE) {
            return FALSE;
        }
        if ($query->num_rows() !== 1) {
            return NULL;
        }
        $record = $query->row_array();
        $record['menu_codes'] = $this->menu_codes($flow_code);
        $record['steps'] = $this->steps($flow_code);
        if ($record['menu_codes'] === FALSE || $record['steps'] === FALSE) {
            return FALSE;
        }
        return $record;
    }

    public function menu_codes($flow_code)
    {
        $query = $this->db->select('menu_code')->from('sys_approval_flow_menu')
            ->where('flow_code', (string) $flow_code)->order_by('menu_code', 'ASC')->get();
        if ($query === FALSE) {
            return FALSE;
        }
        $codes = array();
        foreach ($query->result_array() as $row) {
            $codes[] = (string) $row['menu_code'];
        }
        return $codes;
    }

    public function steps($flow_code)
    {
        $query = $this->db->select('flow_step_id, flow_code, step_number, approver_type, approver_user_id, approver_employee_id, org_id')
            ->from('sys_approval_flow_step')->where('flow_code', (string) $flow_code)
            ->order_by('step_number', 'ASC')->get();
        if ($query === FALSE) {
            return FALSE;
        }
        $steps = $query->result_array();
        foreach ($steps as &$step) {
            $step['approver_employee_id'] = isset($step['approver_employee_id']) ? (string) $step['approver_employee_id'] : '';
            $step['org_id'] = isset($step['org_id']) ? (string) $step['org_id'] : '';
            $step['employee_name'] = '';
            $step['employee_code'] = '';
            $step['org_name'] = '';
            $step['org_code'] = '';
            $step['username'] = '';
            if ($step['approver_employee_id'] !== '' && $this->db->table_exists('hr_employee')) {
                $employee = $this->db->select('employee_name, employee_code')->from('hr_employee')
                    ->where('tenant_id', $this->tenant_id)
                    ->where('employee_id', (int) $step['approver_employee_id'])->limit(1)->get();
                if ($employee !== FALSE && $employee->num_rows() === 1) {
                    $step['employee_name'] = (string) $employee->row()->employee_name;
                    $step['employee_code'] = (string) $employee->row()->employee_code;
                }
            }
            if ($step['org_id'] !== '' && $this->db->table_exists('hr_org')) {
                $org = $this->db->select('org_name, org_code')->from('hr_org')
                    ->where('tenant_id', $this->tenant_id)
                    ->where('org_id', (int) $step['org_id'])->limit(1)->get();
                if ($org !== FALSE && $org->num_rows() === 1) {
                    $step['org_name'] = (string) $org->row()->org_name;
                    $step['org_code'] = (string) $org->row()->org_code;
                }
            }
            if (!empty($step['approver_user_id'])) {
                $user = $this->db->select('username')->from('app_user')
                    ->where('tenant_id', $this->tenant_id)
                    ->where('user_id', (int) $step['approver_user_id'])->limit(1)->get();
                if ($user !== FALSE && $user->num_rows() === 1) {
                    $step['username'] = (string) $user->row()->username;
                }
            }
        }
        unset($step);
        return $steps;
    }

    public function save($flow_code, $values, $menu_codes, $steps, $allowed_menus, $is_new)
    {
        if (!$this->schema_available() || !is_array($values) || !is_array($menu_codes)
            || !is_array($steps) || !is_array($allowed_menus)) {
            return FALSE;
        }
        $flow_code = $this->normalize_code($flow_code);
        if ($flow_code === '' || strlen($flow_code) > 32) {
            return FALSE;
        }
        $flow_name = isset($values['flow_name']) ? trim((string) $values['flow_name']) : '';
        $description = isset($values['description']) ? trim((string) $values['description']) : '';
        $active_flag = !empty($values['active_flag']) ? 1 : 0;
        if ($flow_name === '' || strlen($flow_name) > 120 || strlen($description) > 256) {
            return FALSE;
        }

        $selected_menus = array();
        foreach ($menu_codes as $menu_code) {
            if (!is_string($menu_code) || !isset($allowed_menus[$menu_code]) || isset($selected_menus[$menu_code])) {
                return FALSE;
            }
            $selected_menus[$menu_code] = TRUE;
        }
        if (!$selected_menus) {
            return FALSE;
        }

        $normalized_steps = $this->normalize_steps($steps);
        if ($normalized_steps === FALSE) {
            return FALSE;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();
        if ($is_new) {
            if ($this->db->insert('sys_approval_flow', array(
                'flow_code' => $flow_code,
                'flow_name' => $flow_name,
                'description' => $description === '' ? NULL : $description,
                'active_flag' => $active_flag,
                'created_by' => $this->current_user_id(),
                'create_on' => $now,
            )) !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
        } else {
            $this->db->where('flow_code', $flow_code)->update('sys_approval_flow', array(
                'flow_name' => $flow_name,
                'description' => $description === '' ? NULL : $description,
                'active_flag' => $active_flag,
                'update_on' => $now,
            ));
            if ($this->db->affected_rows() < 0) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }

        if ($this->db->where('flow_code', $flow_code)->delete('sys_approval_flow_step') !== TRUE
            || $this->db->where('flow_code', $flow_code)->delete('sys_approval_flow_menu') !== TRUE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        foreach ($normalized_steps as $index => $step) {
            $step['flow_code'] = $flow_code;
            $step['step_number'] = $index + 1;
            $step['create_on'] = $now;
            if ($this->db->insert('sys_approval_flow_step', $step) !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }
        foreach (array_keys($selected_menus) as $menu_code) {
            if ($this->db->insert('sys_approval_flow_menu', array(
                'flow_code' => $flow_code,
                'menu_code' => $menu_code,
            )) !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return TRUE;
    }

    public function delete($flow_code)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $flow_code = (string) $flow_code;
        if ($flow_code === '') {
            return FALSE;
        }
        $this->db->trans_begin();
        if ($this->db->where('flow_code', $flow_code)->delete('sys_approval_flow_step') !== TRUE
            || $this->db->where('flow_code', $flow_code)->delete('sys_approval_flow_menu') !== TRUE
            || $this->db->where('flow_code', $flow_code)->delete('sys_approval_flow') !== TRUE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return TRUE;
    }

    public function employee_suggestions($term)
    {
        if (!$this->schema_available() || $this->tenant_id < 1 || !$this->db->table_exists('hr_employee')) {
            return array();
        }
        $term = trim((string) $term);
        if ($term === '') {
            return array();
        }
        $rows = $this->db->select('employee_id, employee_code, employee_name')
            ->from('hr_employee')->where('tenant_id', $this->tenant_id)
            ->group_start()->like('employee_code', $term)->or_like('employee_name', $term)->group_end()
            ->order_by('employee_name', 'ASC')->limit(20)->get();
        if ($rows === FALSE) {
            return array();
        }
        $results = array();
        foreach ($rows->result_array() as $row) {
            $label = trim((string) $row['employee_name'] . ' (' . $row['employee_code'] . ')');
            $results[] = array('id' => (string) $row['employee_id'], 'label' => $label, 'value' => $label);
        }
        return $results;
    }

    public function organization_suggestions($term)
    {
        if (!$this->schema_available() || $this->tenant_id < 1 || !$this->db->table_exists('hr_org')) {
            return array();
        }
        $term = trim((string) $term);
        if ($term === '') {
            return array();
        }
        $columns = $this->db->list_fields('hr_org');
        $name_column = in_array('org_name', $columns, TRUE) ? 'org_name' : NULL;
        $code_column = in_array('org_code', $columns, TRUE) ? 'org_code' : NULL;
        if ($name_column === NULL && $code_column === NULL) {
            return array();
        }
        $this->db->select('org_id' . ($code_column !== NULL ? ', ' . $code_column : '') . ($name_column !== NULL ? ', ' . $name_column : ''), FALSE)
            ->from('hr_org')->where('tenant_id', $this->tenant_id);
        $this->db->group_start();
        if ($code_column !== NULL) {
            $this->db->like($code_column, $term);
        }
        if ($name_column !== NULL) {
            if ($code_column !== NULL) {
                $this->db->or_like($name_column, $term);
            } else {
                $this->db->like($name_column, $term);
            }
        }
        $this->db->group_end();
        $rows = $this->db->order_by($name_column !== NULL ? $name_column : $code_column, 'ASC')->limit(20)->get();
        if ($rows === FALSE) {
            return array();
        }
        $results = array();
        foreach ($rows->result_array() as $row) {
            $label = trim(
                (isset($row['org_code']) ? $row['org_code'] . ' — ' : '')
                . (isset($row['org_name']) ? $row['org_name'] : '')
            );
            $results[] = array('id' => (string) $row['org_id'], 'label' => $label, 'value' => $label);
        }
        return $results;
    }

    public function normalize_code($value)
    {
        return strtolower(preg_replace('/\s+/', '', trim((string) $value)));
    }

    private function normalize_steps($steps)
    {
        if (!is_array($steps) || count($steps) > 20) {
            return FALSE;
        }
        $normalized = array();
        foreach (array_values($steps) as $step) {
            if (!is_array($step)) {
                return FALSE;
            }
            $type = isset($step['approver_type']) ? trim((string) $step['approver_type']) : '';
            $employee_id = isset($step['approver_employee_id']) ? trim((string) $step['approver_employee_id']) : '';
            $org_id = isset($step['org_id']) ? trim((string) $step['org_id']) : '';
            $user_id = 0;
            if ($type === 'superior') {
                $employee_id = '';
                $org_id = '';
            } elseif ($type === 'organization') {
                if (!ctype_digit($org_id) || !$this->organization_exists((int) $org_id)) {
                    return FALSE;
                }
                $employee_id = '';
            } elseif ($type === 'employee') {
                if (!ctype_digit($employee_id) || !$this->employee_exists((int) $employee_id)) {
                    return FALSE;
                }
                $user_id = $this->employee_user_id((int) $employee_id);
                if ($user_id < 1) {
                    return FALSE;
                }
                $org_id = '';
            } elseif ($type === 'user') {
                $user_id = isset($step['approver_user_id']) ? (int) $step['approver_user_id'] : 0;
                if ($user_id < 1 || !$this->user_exists($user_id)) {
                    return FALSE;
                }
                $employee_id = '';
                $org_id = '';
            } else {
                return FALSE;
            }
            $normalized[] = array(
                'approver_type' => $type,
                'approver_user_id' => $user_id > 0 ? $user_id : NULL,
                'approver_employee_id' => $employee_id === '' ? NULL : (int) $employee_id,
                'org_id' => $org_id === '' ? NULL : (int) $org_id,
            );
        }
        return $normalized;
    }

    private function organization_exists($org_id)
    {
        if ($this->tenant_id < 1 || !$this->db->table_exists('hr_org')) {
            return FALSE;
        }
        $count = $this->db->from('hr_org')->where('tenant_id', $this->tenant_id)
            ->where('org_id', (int) $org_id)->count_all_results();
        return $count === 1;
    }

    private function employee_exists($employee_id)
    {
        if ($this->tenant_id < 1 || !$this->db->table_exists('hr_employee')) {
            return FALSE;
        }
        $count = $this->db->from('hr_employee')->where('tenant_id', $this->tenant_id)
            ->where('employee_id', (int) $employee_id)->count_all_results();
        return $count === 1;
    }

    private function employee_user_id($employee_id)
    {
        if ($this->tenant_id < 1 || !$this->db->table_exists('app_user')) {
            return 0;
        }
        $query = $this->db->select('user_id')->from('app_user')
            ->where('tenant_id', $this->tenant_id)->where('employee_id', (int) $employee_id)
            ->limit(1)->get();
        if ($query === FALSE || $query->num_rows() !== 1) {
            return 0;
        }
        return (int) $query->row()->user_id;
    }

    private function user_exists($user_id)
    {
        if ($this->tenant_id < 1) {
            return FALSE;
        }
        $count = $this->db->from('app_user')->where('tenant_id', $this->tenant_id)
            ->where('user_id', (int) $user_id)->count_all_results();
        return $count === 1;
    }

    private function current_user_id()
    {
        $CI =& get_instance();
        $session = isset($CI->session) ? $CI->session->userdata('admin_user') : NULL;
        return is_array($session) && isset($session['user_id']) ? (int) $session['user_id'] : 0;
    }

    private function apply_field_filters($filters)
    {
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        foreach (array(
            'flow_code' => 'f.flow_code',
            'flow_name' => 'f.flow_name',
            'description' => 'f.description',
        ) as $name => $column) {
            if (isset($fields[$name]) && $fields[$name] !== '') {
                $this->db->like($column, $fields[$name]);
            }
        }
        if (isset($fields['menu_codes']) && $fields['menu_codes'] !== '') {
            $literal = $this->db->escape($fields['menu_codes']);
            $this->db->where(
                "EXISTS (SELECT 1 FROM sys_approval_flow_menu m WHERE m.flow_code = f.flow_code AND LOCATE(" . $literal . ", m.menu_code) > 0)",
                NULL,
                FALSE
            );
        }
        if (isset($fields['active_flag']) && $fields['active_flag'] !== '') {
            $this->db->where('f.active_flag', (int) $fields['active_flag']);
        }
    }

    private function apply_search($search)
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }
        $this->db->group_start()
            ->like('f.flow_code', $search)
            ->or_like('f.flow_name', $search)
            ->or_like('f.description', $search)
            ->group_end();
    }
}
