<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped persistence for application users. */
class User_store
{
    private $db;
    private $checked = FALSE;
    private $valid = FALSE;
    private $columns = array();
    private $tenant_cache = NULL;

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            $db = $CI->db;
        }
        $this->db = $db;
    }

    public function schema_available()
    {
        if ($this->checked) {
            return $this->valid;
        }
        $this->checked = TRUE;
        foreach (array('app_user', 'app_role', 'sys_property', 'sys_tenant', 'hr_employee') as $table) {
            $fields = $this->db->field_data($table);
            if (!is_array($fields) || !$fields) {
                return FALSE;
            }
            foreach ($fields as $field) {
                if (isset($field->name)) {
                    $this->columns[$table][$field->name] = $field;
                }
            }
        }
        $required = array(
            'app_user' => array('user_id', 'tenant_id', 'user_code', 'username', 'role_id', 'emloyee_flag', 'employee_id', 'password', 'create_on', 'update_on', 'full_name', 'block_flag', 'access_api_flag', 'api_secret'),
            'app_role' => array('role_id', 'tenant_id', 'role_name', 'active_flag'),
            'sys_property' => array('property_code', 'value'),
            'sys_tenant' => array('tenant_id', 'tenant_code', 'tenant_name'),
            'hr_employee' => array('employee_id', 'tenant_id', 'employee_code', 'employee_name'),
        );
        foreach ($required as $table => $fields) {
            foreach ($fields as $field) {
                if (!isset($this->columns[$table][$field])) {
                    return FALSE;
                }
            }
        }
        $this->valid = $this->tenant_id() !== FALSE;
        return $this->valid;
    }

    public function tenant_id()
    {
        if ($this->tenant_cache !== NULL) {
            return $this->tenant_cache;
        }
        $query = $this->db->select('value')->where('property_code', 'tenant_id')->limit(2)->get('sys_property');
        if (!$query || $query->num_rows() !== 1) {
            return FALSE;
        }
        $value = trim((string) $query->row()->value);
        if (!preg_match('/^[1-9][0-9]*$/', $value)) {
            return FALSE;
        }
        $this->tenant_cache = (int) $value;
        return $this->tenant_cache;
    }

    public function tenant_exists($tenant_id)
    {
        if (!$this->schema_available() || !is_numeric($tenant_id) || (int) $tenant_id < 1
            || (string) (int) $tenant_id !== (string) $tenant_id) {
            return FALSE;
        }
        $query = $this->db->where('tenant_id', (int) $tenant_id)->limit(2)->get('sys_tenant');
        return $query !== FALSE && $query->num_rows() === 1;
    }

    public function tenant_options()
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $query = $this->db->select('tenant_id, tenant_code, tenant_name')->order_by('tenant_code', 'ASC')->get('sys_tenant');
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function field_specs()
    {
        if (!$this->schema_available()) {
            return array();
        }
        $specs = array();
        foreach (array('user_code', 'username', 'full_name', 'password', 'api_secret') as $field) {
            $column = $this->columns['app_user'][$field];
            $specs[$field] = array(
                'max_length' => isset($column->max_length) ? (int) $column->max_length : 0,
                'nullable' => isset($column->null) && strtoupper((string) $column->null) === 'YES',
            );
        }
        return $specs;
    }

    public function count($search = '', $tenant_id = NULL, $role_id = '', $block_flag = '')
    {
        $tenant_id = $tenant_id === NULL ? $this->tenant_id() : $tenant_id;
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        $this->db->from('app_user u')
            ->join('app_role r', 'r.role_id = u.role_id AND r.tenant_id = u.tenant_id', 'inner')
            ->join('hr_employee e', 'e.employee_id = u.employee_id AND e.tenant_id = u.tenant_id', 'left')
            ->where('u.tenant_id', (int) $tenant_id);
        $this->apply_search($search);
        $this->apply_filters($role_id, $block_flag);
        $query = $this->db->get();
        return $query ? (int) $query->num_rows() : FALSE;
    }

    public function page($search, $limit, $offset, $sort, $direction, $tenant_id = NULL, $role_id = '', $block_flag = '')
    {
        $tenant_id = $tenant_id === NULL ? $this->tenant_id() : $tenant_id;
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        $sort_fields = array(
            'user_code' => 'u.user_code', 'user_name' => 'u.username', 'full_name' => 'u.full_name',
            'role_name' => 'r.role_name', 'employee_name' => 'e.employee_name', 'block_flag' => 'u.block_flag',
        );
        if (!isset($sort_fields[$sort])) {
            $sort = 'user_code';
        }
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->db->select('u.user_id, u.tenant_id, u.user_code, u.username AS user_name, u.full_name, r.role_name, e.employee_name, u.block_flag')
            ->from('app_user u')->join('app_role r', 'r.role_id = u.role_id AND r.tenant_id = u.tenant_id', 'left')
            ->join('hr_employee e', 'e.employee_id = u.employee_id AND e.tenant_id = u.tenant_id', 'left')
            ->where('u.tenant_id', (int) $tenant_id);
        $this->apply_search($search);
        $this->apply_filters($role_id, $block_flag);
        $query = $this->db->order_by($sort_fields[$sort], $direction)
            ->limit((int) $limit, (int) $offset)->get();
        return $query ? $query->result_array() : FALSE;
    }

    public function find($user_id, $tenant_id = NULL)
    {
        $tenant_id = $tenant_id === NULL ? $this->tenant_id() : $tenant_id;
        if (!$this->tenant_exists($tenant_id) || !is_numeric($user_id) || (int) $user_id < 1) {
            return FALSE;
        }
        $query = $this->db->select('u.user_id, u.tenant_id, u.user_code, u.username, u.role_id, u.emloyee_flag AS employee_flag, u.employee_id, u.full_name, u.block_flag, u.access_api_flag, u.api_secret, e.employee_name')
            ->from('app_user u')->join('hr_employee e', 'e.employee_id = u.employee_id AND e.tenant_id = u.tenant_id', 'left')
            ->where('u.tenant_id', (int) $tenant_id)->where('u.user_id', (int) $user_id)->limit(2)->get();
        if (!$query) {
            return FALSE;
        }
        return $query->num_rows() === 1 ? $query->row_array() : NULL;
    }

    public function role_options($tenant_id)
    {
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        $query = $this->db->select('role_id, role_name')->where('tenant_id', (int) $tenant_id)
            ->where('active_flag', 1)->order_by('role_name', 'ASC')->get('app_role');
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function role_belongs_to_tenant($role_id, $tenant_id)
    {
        if (!$this->tenant_exists($tenant_id) || !is_numeric($role_id) || (int) $role_id < 1) {
            return FALSE;
        }
        $query = $this->db->where('role_id', (int) $role_id)->where('tenant_id', (int) $tenant_id)
            ->where('active_flag', 1)->limit(1)->get('app_role');
        return $query !== FALSE && $query->num_rows() === 1;
    }

    public function employee_options($tenant_id, $term, $except_user_id = NULL)
    {
        if (!$this->tenant_exists($tenant_id) || !is_string($term) || strlen(trim($term)) < 2) {
            return array();
        }
        $term = trim($term);
        $not_assigned = 'NOT EXISTS (SELECT 1 FROM app_user u WHERE u.employee_id = e.employee_id'
            . ' AND u.tenant_id = e.tenant_id AND u.emloyee_flag = 1 AND u.role_id IS NOT NULL';
        if (is_numeric($except_user_id) && (int) $except_user_id > 0) {
            $not_assigned .= ' AND u.user_id <> ' . (int) $except_user_id;
        }
        $not_assigned .= ')';
        $this->db->select('e.employee_id, e.employee_code, e.employee_name')->from('hr_employee e')
            ->where('e.tenant_id', (int) $tenant_id)->where($not_assigned, NULL, FALSE)->group_start()
            ->like('e.employee_code', $term)->or_like('e.employee_name', $term)->group_end()
            ->order_by('e.employee_name', 'ASC')->limit(10);
        $query = $this->db->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function employee_name_for($employee_id, $tenant_id)
    {
        if (!$this->employee_belongs_to_tenant($employee_id, $tenant_id)) {
            return FALSE;
        }
        $query = $this->db->select('employee_name')->where('employee_id', (int) $employee_id)
            ->where('tenant_id', (int) $tenant_id)->limit(1)->get('hr_employee');
        if ($query === FALSE || $query->num_rows() !== 1) {
            return FALSE;
        }
        $name = trim((string) $query->row()->employee_name);
        return $name === '' ? FALSE : $name;
    }

    public function employee_belongs_to_tenant($employee_id, $tenant_id)
    {
        if (!$this->tenant_exists($tenant_id) || !is_numeric($employee_id) || (int) $employee_id < 1) {
            return FALSE;
        }
        $query = $this->db->where('employee_id', (int) $employee_id)->where('tenant_id', (int) $tenant_id)->limit(1)->get('hr_employee');
        return $query !== FALSE && $query->num_rows() === 1;
    }

    public function user_code_exists($user_code, $tenant_id, $except_id = NULL)
    {
        $this->db->from('app_user')->where('tenant_id', (int) $tenant_id)->where('user_code', $user_code);
        if ($except_id !== NULL) {
            $this->db->where('user_id !=', (int) $except_id);
        }
        $query = $this->db->get();
        return $query === FALSE ? NULL : $query->num_rows() > 0;
    }

    public function username_exists($username, $except_id = NULL)
    {
        $this->db->from('app_user')->where('username', $username);
        if ($except_id !== NULL) {
            $this->db->where('user_id !=', (int) $except_id);
        }
        $query = $this->db->get();
        return $query === FALSE ? NULL : $query->num_rows() > 0;
    }

    private function normalize_full_name(&$record, $tenant_id)
    {
        if ((int) $record['employee_flag'] === 1) {
            $name = $this->employee_name_for($record['employee_id'], $tenant_id);
            if ($name === FALSE) {
                return FALSE;
            }
            $record['full_name'] = $name;
        } else {
            $record['full_name'] = trim((string) $record['full_name']);
            $record['employee_id'] = NULL;
            if ($record['full_name'] === '') {
                return FALSE;
            }
        }
        $specs = $this->field_specs();
        return empty($specs['full_name']['max_length']) || strlen($record['full_name']) <= $specs['full_name']['max_length'];
    }

    private function normalize_api_access(&$record)
    {
        $record['access_api_flag'] = isset($record['access_api_flag']) && (int) $record['access_api_flag'] === 1 ? 1 : 0;
        if ($record['access_api_flag'] === 0) {
            $record['api_secret'] = NULL;
            return TRUE;
        }
        $record['api_secret'] = isset($record['api_secret']) ? trim((string) $record['api_secret']) : '';
        $specs = $this->field_specs();
        return $record['api_secret'] !== '' && (empty($specs['api_secret']['max_length']) || strlen($record['api_secret']) <= $specs['api_secret']['max_length']);
    }

    public function insert($record)
    {
        if (!$this->tenant_exists($record['tenant_id']) || !$this->role_belongs_to_tenant($record['role_id'], $record['tenant_id'])
            || !$this->normalize_full_name($record, $record['tenant_id']) || !$this->normalize_api_access($record)) {
            return FALSE;
        }
        $data = array(
            'tenant_id' => (int) $record['tenant_id'], 'user_code' => $record['user_code'],
            'username' => $record['username'], 'full_name' => $record['full_name'],
            'role_id' => (int) $record['role_id'], 'emloyee_flag' => (int) $record['employee_flag'],
            'employee_id' => $record['employee_flag'] ? (int) $record['employee_id'] : NULL,
            'password' => password_hash($record['password'], PASSWORD_DEFAULT), 'block_flag' => 0,
            'access_api_flag' => (int) $record['access_api_flag'], 'api_secret' => $record['access_api_flag'] ? $record['api_secret'] : NULL,
            'create_on' => date('Y-m-d H:i:s'),
        );
        return $this->db->insert('app_user', $data) && $this->db->affected_rows() === 1;
    }

    /** Update is deliberately restricted to the four fields permitted by the UI. */
    public function update($user_id, $tenant_id, $record)
    {
        if (!$this->find($user_id, $tenant_id) || !$this->role_belongs_to_tenant($record['role_id'], $tenant_id)
            || !$this->normalize_full_name($record, $tenant_id) || !$this->normalize_api_access($record)) {
            return FALSE;
        }
        $data = array(
            'username' => $record['username'], 'full_name' => $record['full_name'], 'role_id' => (int) $record['role_id'],
            'emloyee_flag' => (int) $record['employee_flag'],
            'employee_id' => $record['employee_flag'] ? (int) $record['employee_id'] : NULL,
            'access_api_flag' => (int) $record['access_api_flag'], 'api_secret' => $record['access_api_flag'] ? $record['api_secret'] : NULL,
            'update_on' => date('Y-m-d H:i:s'),
        );
        return $this->db->where('user_id', (int) $user_id)->where('tenant_id', (int) $tenant_id)
            ->update('app_user', $data);
    }

    public function change_password($user_id, $tenant_id, $password)
    {
        if (!$this->find($user_id, $tenant_id)) {
            return FALSE;
        }
        return $this->db->where('user_id', (int) $user_id)->where('tenant_id', (int) $tenant_id)
            ->update('app_user', array('password' => password_hash($password, PASSWORD_DEFAULT), 'update_on' => date('Y-m-d H:i:s')));
    }

    public function set_blocked($user_id, $tenant_id, $blocked)
    {
        if (!$this->find($user_id, $tenant_id)) {
            return FALSE;
        }
        return $this->db->where('user_id', (int) $user_id)->where('tenant_id', (int) $tenant_id)
            ->update('app_user', array('block_flag' => $blocked ? 1 : 0, 'update_on' => date('Y-m-d H:i:s')));
    }

    private function apply_filters($role_id, $block_flag)
    {
        if (is_numeric($role_id) && (int) $role_id > 0) {
            $this->db->where('u.role_id', (int) $role_id);
        }
        if ($block_flag === '0' || $block_flag === '1') {
            $this->db->where('u.block_flag', (int) $block_flag);
        }
    }

    private function apply_search($search)
    {
        if ($search === '') {
            return;
        }
        $this->db->group_start()->like('u.user_code', $search)->or_like('u.username', $search)
            ->or_like('u.full_name', $search)->or_like('r.role_name', $search)->or_like('e.employee_name', $search)->group_end();
    }

    private function tenant_exists_query($tenant_id)
    {
        $query = $this->db->where('tenant_id', (int) $tenant_id)->limit(1)->get('sys_tenant');
        return $query !== FALSE && $query->num_rows() === 1;
    }
}
