<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Schema-checked, transactional persistence for Parameter and its options. */
class Parameter_store
{
    private $db;
    private $columns = array();
    private $option_columns = array();
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
        $parent_fields = $this->db->field_data('sys_parameter');
        $option_fields = $this->db->field_data('sys_parameter_option');
        if (!is_array($parent_fields) || !$parent_fields || !is_array($option_fields) || !$option_fields) {
            return FALSE;
        }
        foreach ($parent_fields as $field) {
            if (isset($field->name)) {
                $this->columns[$field->name] = $field;
            }
        }
        foreach ($option_fields as $field) {
            if (isset($field->name)) {
                $this->option_columns[$field->name] = $field;
            }
        }
        $this->schema_valid = isset($this->columns['parameter_code'], $this->columns['parameter_name'], $this->columns['description'], $this->columns['summary'])
            && !empty($this->columns['parameter_code']->primary_key)
            && isset($this->option_columns['option_code'], $this->option_columns['parameter_code'], $this->option_columns['option_name'], $this->option_columns['active_flag'], $this->option_columns['line'])
            && !empty($this->option_columns['option_code']->primary_key);
        return $this->schema_valid;
    }

    public function fields()
    {
        if (!$this->schema_available()) {
            return array();
        }
        $result = array();
        foreach (array('parameter_code', 'parameter_name', 'description', 'summary') as $name) {
            $field = $this->columns[$name];
            $result[$name] = array('max_length' => isset($field->max_length) ? (int) $field->max_length : 0);
        }
        foreach (array('option_code', 'option_name') as $name) {
            $field = $this->option_columns[$name];
            $result[$name] = array('max_length' => isset($field->max_length) ? (int) $field->max_length : 0);
        }
        return $result;
    }

    public function count($filters)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $this->db->from('sys_parameter');
        $this->apply_filters($filters);
        $count = $this->db->count_all_results();
        return $count === FALSE ? FALSE : (int) $count;
    }

    public function page($filters, $limit, $offset, $sort = 'parameter_code', $direction = 'ASC')
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $query = $this->db->select('parameter_code, parameter_name, description, summary')->from('sys_parameter');
        $this->apply_filters($filters);
        $sort_column = $sort === 'parameter_name' ? 'parameter_name' : 'parameter_code';
        $sort_direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $query = $this->db->order_by($sort_column, $sort_direction);
        if ($sort_column === 'parameter_name') {
            $query->order_by('parameter_code', 'ASC');
        }
        $query = $query->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find($parameter_code)
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $parent = $this->db->where('parameter_code', $parameter_code)->limit(1)->get('sys_parameter');
        if ($parent === FALSE) {
            return FALSE;
        }
        if (!$parent->num_rows()) {
            return NULL;
        }
        $options = $this->db->where('parameter_code', $parameter_code)
            ->order_by('line', 'ASC')->order_by('option_code', 'ASC')->get('sys_parameter_option');
        if ($options === FALSE) {
            return FALSE;
        }
        $record = $parent->row_array();
        $record['options'] = $options->result_array();
        return $record;
    }

    /** Codes are checked globally; the current parameter's own rows are exempt on edit. */
    public function option_codes_available($codes, $exclude_parameter_code = NULL)
    {
        if (!$this->schema_available() || !$codes) {
            return FALSE;
        }
        $query = $this->db->select('option_code')->where_in('option_code', $codes);
        if ($exclude_parameter_code !== NULL) {
            $query->where('parameter_code !=', $exclude_parameter_code);
        }
        $existing = $query->get('sys_parameter_option');
        if ($existing === FALSE) {
            return FALSE;
        }
        return $existing->num_rows() === 0;
    }

    public function create($record, $options)
    {
        if (!$this->schema_available() || count($options) < 1) {
            return FALSE;
        }
        $record['summary'] = implode(', ', array_map(function ($option) { return $option['option_name']; }, $options));
        $this->db->trans_begin();
        $ok = $this->db->insert('sys_parameter', array(
            'parameter_code' => $record['parameter_code'],
            'parameter_name' => $record['parameter_name'],
            'description' => $record['description'],
            'summary' => $record['summary'],
        )) === TRUE;
        if ($ok) {
            $ok = $this->insert_options($record['parameter_code'], $options);
        }
        if (!$ok || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        return $this->db->trans_commit() === TRUE;
    }

    public function update($parameter_code, $record, $options)
    {
        if (!$this->schema_available() || count($options) < 1) {
            return FALSE;
        }
        $existing = $this->find($parameter_code);
        if ($existing === FALSE || $existing === NULL) {
            return FALSE;
        }
        $summary = implode(', ', array_map(function ($option) { return $option['option_name']; }, $options));
        $this->db->trans_begin();
        $ok = $this->db->where('parameter_code', $parameter_code)->update('sys_parameter', array(
            'parameter_name' => $record['parameter_name'],
            'description' => $record['description'],
            'summary' => $summary,
        )) === TRUE;
        if ($ok) {
            $ok = $this->db->where('parameter_code', $parameter_code)->delete('sys_parameter_option') === TRUE;
        }
        if ($ok) {
            $ok = $this->insert_options($parameter_code, $options);
        }
        if (!$ok || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        return $this->db->trans_commit() === TRUE;
    }

    public function delete($parameter_code)
    {
        if (!$this->schema_available()) {
            return 'error';
        }
        $ok = $this->db->where('parameter_code', $parameter_code)->delete('sys_parameter');
        if ($ok !== TRUE) {
            return 'error';
        }
        $affected = (int) $this->db->affected_rows();
        return $affected === 1 ? 'deleted' : ($affected === 0 ? 'not_found' : 'error');
    }

    private function insert_options($parameter_code, $options)
    {
        foreach (array_values($options) as $index => $option) {
            if ($this->db->insert('sys_parameter_option', array(
                'option_code' => $option['option_code'],
                'parameter_code' => $parameter_code,
                'option_name' => $option['option_name'],
                'active_flag' => $option['active_flag'],
                'line' => $index + 1,
            )) !== TRUE) {
                return FALSE;
            }
        }
        return TRUE;
    }

    private function apply_filters($filters)
    {
        $search = isset($filters['search']) && is_string($filters['search']) ? trim($filters['search']) : '';
        if ($search !== '') {
            $this->db->group_start()
                ->like('parameter_code', $search)
                ->or_like('parameter_name', $search)
                ->or_like('description', $search)
                ->or_like('summary', $search)
                ->group_end();
        }
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        foreach (array('parameter_code', 'parameter_name', 'description', 'summary') as $name) {
            if (isset($fields[$name]) && $fields[$name] !== '') {
                $this->db->like($name, $fields[$name]);
            }
        }
    }
}
