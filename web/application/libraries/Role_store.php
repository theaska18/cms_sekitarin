<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped persistence for application roles. */
class Role_store
{
    private $db;
    private $schema_checked = FALSE;
    private $schema_valid = FALSE;
    private $columns = array();
    private $tenant_id_cache = NULL;

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
        if ($this->schema_checked) {
            return $this->schema_valid;
        }
        $this->schema_checked = TRUE;
        foreach (array('app_role', 'sys_tenant') as $table) {
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
        $this->schema_valid = isset(
            $this->columns['app_role']['role_id'], $this->columns['app_role']['role_code'],
            $this->columns['app_role']['role_name'], $this->columns['app_role']['tenant_id'],
            $this->columns['app_role']['description'], $this->columns['app_role']['active_flag'],
            $this->columns['app_role']['create_on'], $this->columns['app_role']['update_on'],
            $this->columns['sys_tenant']['tenant_id'], $this->columns['sys_tenant']['tenant_code'],
            $this->columns['sys_tenant']['tenant_name']
        ) && $this->tenant_id() !== FALSE;
        return $this->schema_valid;
    }

    public function tenant_options()
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $query = $this->db->select('tenant_id, tenant_code, tenant_name')
            ->order_by('tenant_code', 'ASC')->get('sys_tenant');
        return $query === FALSE ? FALSE : $query->result_array();
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

    public function field_specs()
    {
        if (!$this->schema_available()) {
            return array();
        }
        $specs = array();
        foreach (array('role_code', 'role_name', 'description') as $name) {
            $field = $this->columns['app_role'][$name];
            $specs[$name] = array(
                'max_length' => isset($field->max_length) ? (int) $field->max_length : 0,
                'nullable' => isset($field->null) && strtoupper((string) $field->null) === 'YES',
            );
        }
        return $specs;
    }

    public function tenant_id()
    {
        if ($this->tenant_id_cache !== NULL) {
            return $this->tenant_id_cache;
        }
        $CI =& get_instance();
        $session_user = $CI->session->userdata('admin_user');
        if (!is_array($session_user) || !isset($session_user['tenant_id'])
            || !is_numeric($session_user['tenant_id']) || (int) $session_user['tenant_id'] < 1) {
            $this->tenant_id_cache = FALSE;
            return FALSE;
        }
        $this->tenant_id_cache = (int) $session_user['tenant_id'];
        return $this->tenant_id_cache;
    }

    public function count($search = '', $tenant_id = NULL, $filters = array())
    {
        $tenant_id = $tenant_id === NULL ? $this->tenant_id() : $tenant_id;
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        $this->db->from('app_role')->where('tenant_id', (int) $tenant_id);
        $this->apply_search($search);
        $this->apply_column_filters($filters);
        $query = $this->db->get();
        return $query ? (int) $query->num_rows() : FALSE;
    }

    public function page($search, $limit, $offset, $sort, $direction, $tenant_id = NULL, $filters = array())
    {
        $tenant_id = $tenant_id === NULL ? $this->tenant_id() : $tenant_id;
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        $sort_fields = array('role_code', 'role_name', 'active_flag');
        if (!in_array($sort, $sort_fields, TRUE)) {
            $sort = 'role_code';
        }
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->db->select('app_role.role_id, app_role.tenant_id, app_role.role_code, app_role.role_name, app_role.description, app_role.active_flag, sys_tenant.tenant_code, sys_tenant.tenant_name')
            ->from('app_role')
            ->join('sys_tenant', 'sys_tenant.tenant_id = app_role.tenant_id', 'left')
            ->where('app_role.tenant_id', (int) $tenant_id);
        $this->apply_search($search);
        $this->apply_column_filters($filters);
        $query = $this->db->order_by('app_role.' . $sort, $direction)
            ->limit((int) $limit, (int) $offset)->get();
        return $query ? $query->result_array() : FALSE;
    }

    public function find($role_code, $tenant_id = NULL)
    {
        $tenant_id = $tenant_id === NULL ? $this->tenant_id() : $tenant_id;
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        $query = $this->db->select('role_id, tenant_id, role_code, role_name, description, active_flag')
            ->where('tenant_id', (int) $tenant_id)->where('role_code', $role_code)
            ->limit(2)->get('app_role');
        if (!$query) {
            return FALSE;
        }
        return $query->num_rows() === 1 ? $query->row_array() : NULL;
    }

    /**
     * Return TRUE when the code exists, FALSE when it does not, or NULL on query failure.
     */
    public function code_exists($role_code, $tenant_id = NULL)
    {
        if ($tenant_id === NULL) {
            $tenant_id = $this->tenant_id();
        }
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        $this->db->from('app_role')->where('tenant_id', (int) $tenant_id)->where('role_code', $role_code);
        $query = $this->db->get();
        if ($query === FALSE) {
            return NULL;
        }
        return $query->num_rows() > 0;
    }

    public function insert($record)
    {
        $tenant_id = isset($record['tenant_id']) ? $record['tenant_id'] : NULL;
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        $now = date('Y-m-d H:i:s');
        return $this->db->insert('app_role', array(
            'tenant_id' => (int) $record['tenant_id'],
            'role_code' => $record['role_code'],
            'role_name' => $record['role_name'],
            'description' => $record['description'],
            'active_flag' => (int) $record['active_flag'],
            'create_on' => $now,
            'update_on' => $now,
        )) === TRUE;
    }

    public function update($role_code, $record, $tenant_id = NULL)
    {
        $tenant_id = $tenant_id === NULL ? $this->tenant_id() : $tenant_id;
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        return $this->db->where('tenant_id', (int) $tenant_id)->where('role_code', $role_code)
            ->update('app_role', array(
                'role_name' => $record['role_name'],
                'description' => $record['description'],
                'active_flag' => (int) $record['active_flag'],
                'update_on' => date('Y-m-d H:i:s'),
            )) === TRUE;
    }

    public function delete($role_code, $tenant_id = NULL)
    {
        $tenant_id = $tenant_id === NULL ? $this->tenant_id() : $tenant_id;
        if (!$this->tenant_exists($tenant_id)) {
            return FALSE;
        }
        return $this->db->where('tenant_id', (int) $tenant_id)->where('role_code', $role_code)
            ->delete('app_role') === TRUE;
    }

    private function apply_search($search)
    {
        $search = trim((string) $search);
        if ($search !== '') {
            $this->db->group_start()
                ->like('app_role.role_code', $search)
                ->or_like('app_role.role_name', $search)
                ->or_like('app_role.description', $search)
                ->group_end();
        }
    }

    public function apply_column_filters($filters)
    {
        if (!is_array($filters)) {
            return;
        }
        foreach (array('role_code', 'role_name', 'description') as $field) {
            if (isset($filters[$field]) && is_string($filters[$field]) && trim($filters[$field]) !== '') {
                $this->db->like('app_role.' . $field, trim(substr($filters[$field], 0, 100)));
            }
        }
        if (isset($filters['active_flag']) && in_array((string) $filters['active_flag'], array('0', '1'), TRUE)) {
            $this->db->where('app_role.active_flag', (int) $filters['active_flag']);
        }
    }
}
