<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Schema-checked, narrowly scoped data access for sys_tenant. */
class Tenant_store
{
    private $db;
    private $columns = array();
    private $schema_checked = FALSE;
    private $schema_valid = FALSE;

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
        $fields = $this->db->field_data('sys_tenant');
        if (!is_array($fields) || !$fields) {
            return FALSE;
        }
        foreach ($fields as $field) {
            if (isset($field->name)) {
                $this->columns[$field->name] = $field;
            }
        }
        $this->schema_valid = isset(
            $this->columns['tenant_id'], $this->columns['tenant_code'], $this->columns['tenant_name'],
            $this->columns['email_address'], $this->columns['phone_number']
        ) && !empty($this->columns['tenant_id']->primary_key);
        return $this->schema_valid;
    }

    public function fields()
    {
        if (!$this->schema_available()) {
            return array();
        }
        $result = array();
        foreach (array('tenant_code', 'tenant_name', 'address', 'email_address', 'phone_number', 'due_flag', 'due_on') as $name) {
            if (isset($this->columns[$name])) {
                $field = $this->columns[$name];
                $result[$name] = array(
                    'name' => $name,
                    'max_length' => isset($field->max_length) ? (int) $field->max_length : 0,
                );
            }
        }
        return $result;
    }

    public function count($filters)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $this->db->from('sys_tenant');
        $this->apply_filters($filters);
        $count = $this->db->count_all_results();
        return $count === FALSE ? FALSE : (int) $count;
    }

    public function page($filters, $limit, $offset, $sort = 'tenant_code', $direction = 'ASC')
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $query = $this->db->select('tenant_code, tenant_name, email_address, phone_number, due_on')
            ->from('sys_tenant');
        $this->apply_filters($filters);
        $sort_column = in_array($sort, array('tenant_name', 'email_address', 'phone_number', 'due_on'), TRUE) ? $sort : 'tenant_code';
        $sort_direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $query = $this->db->order_by($sort_column, $sort_direction);
        if ($sort_column !== 'tenant_code') {
            $query->order_by('tenant_code', 'ASC');
        }
        $query = $query->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find($tenant_code)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $select = implode(', ', array_merge(array('tenant_id'), array_keys($this->fields())));
        $query = $this->db->select($select)->where('tenant_code', $tenant_code)->limit(2)->get('sys_tenant');
        if ($query === FALSE || $query->num_rows() > 1) {
            return FALSE;
        }
        return $query->num_rows() ? $query->row_array() : NULL;
    }

    public function insert($data)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $allowed = $this->writable_data($data, TRUE);
        if (!isset($allowed['tenant_code'], $allowed['tenant_name'], $allowed['email_address'], $allowed['phone_number'])) {
            return FALSE;
        }
        return $this->db->insert('sys_tenant', $allowed) === TRUE;
    }

    public function update($tenant_code, $data)
    {
        if (!$this->schema_available()) {
            return 'error';
        }
        $allowed = $this->writable_data($data, FALSE);
        if (!$allowed) {
            return 'error';
        }
        $existing = $this->find($tenant_code);
        if ($existing === FALSE) {
            return 'error';
        }
        if ($existing === NULL) {
            return 'not_found';
        }
        $ok = $this->db->where('tenant_code', $tenant_code)->update('sys_tenant', $allowed);
        if ($ok !== TRUE) {
            return 'error';
        }
        return (int) $this->db->affected_rows() > 0 ? 'updated' : 'unchanged';
    }

    public function delete($tenant_code)
    {
        if (!$this->schema_available()) {
            return 'error';
        }
        $existing = $this->find($tenant_code);
        if ($existing === FALSE) {
            return 'error';
        }
        if ($existing === NULL) {
            return 'not_found';
        }
        $ok = $this->db->where('tenant_code', $tenant_code)->delete('sys_tenant');
        if ($ok !== TRUE) {
            return 'error';
        }
        $affected = (int) $this->db->affected_rows();
        return $affected === 1 ? 'deleted' : ($affected === 0 ? 'not_found' : 'error');
    }

    private function writable_data($data, $include_code)
    {
        $allowed_names = $include_code
            ? array('tenant_code', 'tenant_name', 'address', 'email_address', 'phone_number', 'due_flag', 'due_on')
            : array('tenant_name', 'address', 'email_address', 'phone_number', 'due_flag', 'due_on');
        $result = array();
        foreach ($allowed_names as $name) {
            if (isset($this->columns[$name]) && array_key_exists($name, $data)) {
                $value = $data[$name];
                if ($name === 'due_on' && $value === '') {
                    $value = NULL;
                }
                $result[$name] = $value;
            }
        }
        return $result;
    }

    private function apply_filters($filters)
    {
        if (isset($filters['search']) && $filters['search'] !== '') {
            $this->db->group_start()
                ->like('tenant_code', $filters['search'])
                ->or_like('tenant_name', $filters['search'])
                ->or_like('email_address', $filters['search'])
                ->or_like('phone_number', $filters['search'])
                ->group_end();
        }
        if (!empty($filters['date_ranges']['due_on'])) {
            $range = $filters['date_ranges']['due_on'];
            if (!empty($range['start'])) {
                $this->db->where('due_on >=', $range['start']);
            }
            if (!empty($range['end'])) {
                $this->db->where('due_on <=', $range['end']);
            }
        }
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        foreach (array('tenant_code', 'tenant_name', 'email_address', 'phone_number') as $name) {
            if (isset($fields[$name]) && $fields[$name] !== '') {
                $this->db->like($name, $fields[$name]);
            }
        }
    }
}
