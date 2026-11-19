<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Narrow, schema-checked data access for the sys_property admin screen. */
class Property_store
{
    private $db;
    private $columns = array();
    private $schema_checked = FALSE;
    private $schema_valid = FALSE;
    private $web_name_loaded = FALSE;
    private $web_name_value = '';

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
    }

    /** Require the known key/name columns and a primary key before any access. */
    public function schema_available()
    {
        if ($this->schema_checked) {
            return $this->schema_valid;
        }
        $this->schema_checked = TRUE;
        $fields = $this->db->field_data('sys_property');
        if (!is_array($fields) || !$fields) {
            return FALSE;
        }
        foreach ($fields as $field) {
            if (isset($field->name)) {
                $this->columns[$field->name] = $field;
            }
        }
        $this->schema_valid = isset($this->columns['property_code'], $this->columns['property_name'])
            && !empty($this->columns['property_code']->primary_key);
        return $this->schema_valid;
    }

    public function fields()
    {
        if (!$this->schema_available()) {
            return array();
        }
        $result = array();
        foreach (array('property_code', 'property_name', 'description', 'value') as $name) {
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
        $this->db->from('sys_property');
        $this->apply_filters($filters);
        $count = $this->db->count_all_results();
        return $count === FALSE ? FALSE : (int) $count;
    }

    public function page($filters, $limit, $offset, $sort = 'property_code', $direction = 'ASC')
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $select = array('property_code', 'property_name');
        foreach (array('description', 'value') as $optional_column) {
            if (isset($this->columns[$optional_column])) {
                $select[] = $optional_column;
            }
        }
        $query = $this->db->select(implode(', ', $select))
            ->from('sys_property');
        $this->apply_filters($filters);
        $sort_column = $sort === 'property_name' ? 'property_name' : 'property_code';
        $sort_direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $query = $this->db->order_by($sort_column, $sort_direction);
        if ($sort_column === 'property_name') {
            $query->order_by('property_code', 'ASC');
        }
        $query = $query->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))
            ->get();
        if ($query === FALSE) {
            return FALSE;
        }
        $records = $query->result_array();
        foreach ($records as &$record) {
            foreach (array('description', 'value') as $optional_column) {
                if (!array_key_exists($optional_column, $record)) {
                    $record[$optional_column] = '';
                }
            }
        }
        unset($record);
        return $records;
    }

    public function find($property_code)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $property_code = strtolower($property_code);
        $select = implode(', ', array_keys($this->fields()));
        $query = $this->db->select($select)
            ->where('property_code', $property_code)
            ->limit(1)
            ->get('sys_property');
        if ($query === FALSE) {
            return FALSE;
        }
        return $query->num_rows() ? $query->row_array() : NULL;
    }

    /**
     * Whether public landing pages should be rendered.
     * Missing/unavailable configuration preserves the current public behavior.
     */
    public function landing_page_enabled()
    {
        $setting = $this->find('landing_page');
        if (!is_array($setting) || !isset($setting['value'])) {
            return TRUE;
        }
        return strtoupper(trim((string) $setting['value'])) !== 'N';
    }

    /** Brand name managed from System -> Property (web_name). */
    public function web_name($default = 'CMSNivo')
    {
        if ($this->web_name_loaded) {
            return $this->web_name_value;
        }
        $this->web_name_loaded = TRUE;
        $setting = $this->find('web_name');
        $value = is_array($setting) && isset($setting['value']) ? trim((string) $setting['value']) : '';
        if ($value === '') {
            $value = is_string($default) && trim($default) !== '' ? trim($default) : 'CMSNivo';
        }
        $this->web_name_value = $value;
        return $this->web_name_value;
    }

    public function insert($data)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $allowed = $this->writable_data($data, TRUE);
        if (!isset($allowed['property_code'], $allowed['property_name'])) {
            return FALSE;
        }
        return $this->db->insert('sys_property', $allowed) === TRUE;
    }

    /** Returns updated, unchanged, not_found, or error. */
    public function update($property_code, $data)
    {
        if (!$this->schema_available()) {
            return 'error';
        }
        $allowed = $this->writable_data($data, FALSE);
        if (!$allowed) {
            return 'error';
        }
        $property_code = strtolower($property_code);
        $ok = $this->db->where('property_code', $property_code)
            ->update('sys_property', $allowed);
        if ($ok !== TRUE) {
            return 'error';
        }
        if ((int) $this->db->affected_rows() > 0) {
            return 'updated';
        }
        $existing = $this->find($property_code);
        return $existing === FALSE ? 'error' : ($existing === NULL ? 'not_found' : 'unchanged');
    }

    /** Returns deleted, not_found, or error; predicate is the primary key only. */
    public function delete($property_code)
    {
        if (!$this->schema_available()) {
            return 'error';
        }
        $property_code = strtolower($property_code);
        $ok = $this->db->where('property_code', $property_code)->delete('sys_property');
        if ($ok !== TRUE) {
            return 'error';
        }
        $affected = (int) $this->db->affected_rows();
        if ($affected === 1) {
            return 'deleted';
        }
        return $affected === 0 ? 'not_found' : 'error';
    }

    private function writable_data($data, $include_code)
    {
        $allowed_names = $include_code
            ? array('property_code', 'property_name', 'description', 'value')
            : array('property_name', 'description', 'value');
        $result = array();
        foreach ($allowed_names as $name) {
            if (isset($this->columns[$name]) && array_key_exists($name, $data)) {
                $value = $data[$name];
                if ($name === 'property_code') {
                    if (!is_string($value)) {
                        return array();
                    }
                    $value = strtolower(preg_replace('/\s+/', '', $value));
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
                ->like('property_code', $filters['search'])
                ->or_like('property_name', $filters['search'])
                ->group_end();
        }
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        foreach (array('property_code', 'property_name', 'description', 'value') as $name) {
            if (!isset($this->columns[$name]) || !isset($fields[$name]) || $fields[$name] === '') {
                continue;
            }
            $this->db->like($name, $fields[$name]);
        }
    }
}
