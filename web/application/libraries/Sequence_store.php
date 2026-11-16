<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Data access for the system sequence list and create form. */
class Sequence_store
{
    private $db;
    private $schema_checked = FALSE;
    private $schema_available = FALSE;
    private $sequence_fields = array();

    private $sort_columns = array(
        'sequence_code' => 's.sequence_code',
        'sequence_name' => 's.sequence_name',
        'description' => 's.description',
        'repeat_type' => 'COALESCE(o.option_name, s.repeat_type)',
        'menu_codes' => 'COALESCE(sm.menu_codes, \'\')',
        'format' => 's.format',
    );

    public function __construct()
    {
        $this->db = get_instance()->db;
    }

    public function schema_available()
    {
        return $this->has_expected_schema();
    }

    public function field_specs()
    {
        if (!$this->has_expected_schema()) {
            return array();
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $column_query = $this->db->query('SHOW FULL COLUMNS FROM `sys_sequence`');
        $this->db->db_debug = $previous_db_debug;
        if ($column_query === FALSE) {
            return array();
        }
        $columns = array();
        foreach ($column_query->result_array() as $column) {
            $columns[$column['Field']] = $column;
        }
        $specs = array();
        foreach ($this->db->field_data('sys_sequence') as $field) {
            $column = isset($columns[$field->name]) ? $columns[$field->name] : array();
            $sql_type = isset($column['Type']) ? strtolower($column['Type']) : strtolower($field->type);
            $nullable = isset($column['Null']) && strtoupper($column['Null']) === 'YES';
            $default = array_key_exists('Default', $column) ? $column['Default'] : NULL;
            $extra = isset($column['Extra']) ? strtolower($column['Extra']) : '';
            $max_length = isset($field->max_length) ? (int) $field->max_length : 0;
            if ($max_length < 1 && preg_match('/^(?:var)?char\\((\\d+)\\)/', $sql_type, $match)) {
                $max_length = (int) $match[1];
            }
            if ($max_length < 1) {
                $text_limits = array('tinytext' => 255, 'text' => 65535, 'mediumtext' => 16777215, 'longtext' => 4294967295);
                $base_type = preg_replace('/\\(.*/', '', $sql_type);
                if (isset($text_limits[$base_type])) {
                    $max_length = $text_limits[$base_type];
                }
            }
            $numeric_bounds = $this->integer_bounds($sql_type);
            $specs[$field->name] = array(
                'type' => $field->type,
                'max_length' => $max_length,
                'primary_key' => !empty($field->primary_key),
                'sql_type' => $sql_type,
                'nullable' => $nullable,
                'default' => $default,
                'required' => !$nullable && $default === NULL && strpos($extra, 'auto_increment') === FALSE,
                'minimum' => $numeric_bounds === NULL ? NULL : $numeric_bounds[0],
                'maximum' => $numeric_bounds === NULL ? NULL : $numeric_bounds[1],
            );
        }
        return $specs;
    }

    private function integer_bounds($sql_type)
    {
        if (!preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)\\b/', $sql_type, $match)) {
            return NULL;
        }
        $maxima = array(
            'tinyint' => '127', 'smallint' => '32767', 'mediumint' => '8388607',
            'int' => '2147483647', 'integer' => '2147483647', 'bigint' => '9223372036854775807',
        );
        $unsigned = strpos($sql_type, 'unsigned') !== FALSE;
        $maximum = $unsigned
            ? array(
                'tinyint' => '255', 'smallint' => '65535', 'mediumint' => '16777215',
                'int' => '4294967295', 'integer' => '4294967295', 'bigint' => '18446744073709551615',
            )[$match[1]]
            : $maxima[$match[1]];
        $minimum = $unsigned ? '0' : '-' . $maxima[$match[1]];
        if (!$unsigned && $match[1] === 'bigint') {
            $minimum = '-9223372036854775808';
        } elseif (!$unsigned) {
            $minimum = '-' . (string) ((int) $maxima[$match[1]] + 1);
        }
        return array($minimum, $maximum);
    }

    public function repeat_type_options()
    {
        if (!$this->has_expected_schema()) {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $query = $this->db->select('option_code, option_name')
            ->from('sys_parameter_option')
            ->where('parameter_code', 'repeat_type')
            ->order_by('line', 'ASC')
            ->order_by('option_code', 'ASC')
            ->get();
        $this->db->db_debug = $previous_db_debug;
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function insert($record)
    {
        if (!$this->has_expected_schema() || !is_array($record)
            || !isset($record['menu_codes']) || !is_array($record['menu_codes']) || !$record['menu_codes']) {
            return FALSE;
        }
        $menu_codes = $this->normalize_menu_codes($record['menu_codes']);
        if (!$menu_codes) {
            return FALSE;
        }
        unset($record['menu_codes']);
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();
        $result = $this->db->insert('sys_sequence', $record);
        $sequence_code = (string) $record['sequence_code'];
        if ($result === TRUE) {
            foreach ($menu_codes as $menu_code) {
                if (!$this->db->insert('sys_sequence_menu', array(
                    'sequence_ccode' => $sequence_code,
                    'menu_code' => $menu_code,
                ))) {
                    $result = FALSE;
                    break;
                }
            }
        }
        if ($result !== TRUE || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $result = FALSE;
        } else {
            $this->db->trans_commit();
        }
        $this->db->db_debug = $previous_db_debug;
        return $result === TRUE;
    }

    public function find($sequence_code)
    {
        if (!$this->has_expected_schema() || !is_string($sequence_code) || $sequence_code === '') {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $query = $this->db->get_where('sys_sequence', array('sequence_code' => $sequence_code), 1);
        if ($query !== FALSE && $query->num_rows() === 1) {
            $sequence = $query->row_array();
            $menu = $this->db->select('menu_code')->where('sequence_ccode', $sequence_code)
                ->order_by('menu_code', 'ASC')->get('sys_sequence_menu');
            if ($menu === FALSE) {
                $this->db->db_debug = $previous_db_debug;
                return FALSE;
            }
            $sequence['menu_codes'] = array_map('strval', array_column($menu->result_array(), 'menu_code'));
            $this->db->db_debug = $previous_db_debug;
            return $sequence;
        }
        $this->db->db_debug = $previous_db_debug;
        return $query === FALSE ? FALSE : NULL;
    }

    public function update($sequence_code, $record)
    {
        if (!$this->has_expected_schema() || !is_string($sequence_code) || $sequence_code === '' || !is_array($record)) {
            return FALSE;
        }
        $menu_codes = isset($record['menu_codes']) && is_array($record['menu_codes'])
            ? $this->normalize_menu_codes($record['menu_codes'])
            : array();
        unset($record['menu_codes']);
        if (!$menu_codes) {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();
        $this->db->where('sequence_ccode', $sequence_code)->delete('sys_sequence_menu');
        $result = $this->db->where('sequence_code', $sequence_code)->update('sys_sequence', $record);
        if ($result === TRUE) {
            foreach ($menu_codes as $menu_code) {
                if (!$this->db->insert('sys_sequence_menu', array(
                    'sequence_ccode' => isset($record['sequence_code']) ? (string) $record['sequence_code'] : $sequence_code,
                    'menu_code' => $menu_code,
                ))) {
                    $result = FALSE;
                    break;
                }
            }
        }
        if ($result !== TRUE || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $result = FALSE;
        } else {
            $this->db->trans_commit();
        }
        $this->db->db_debug = $previous_db_debug;
        return $result === TRUE;
    }

    public function delete($sequence_code)
    {
        if (!$this->has_expected_schema() || !is_string($sequence_code) || $sequence_code === '') {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();
        $this->db->where('sequence_ccode', $sequence_code)->delete('sys_sequence_menu');
        $this->db->where('sequence_code', $sequence_code);
        $result = $this->db->delete('sys_sequence');
        if ($result !== TRUE || $this->db->trans_status() === FALSE || $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            $result = FALSE;
        } else {
            $this->db->trans_commit();
        }
        $this->db->db_debug = $previous_db_debug;
        return $result === TRUE;
    }

    public function count($search = '')
    {
        if (!$this->has_expected_schema()) {
            return FALSE;
        }

        $this->db->select('COUNT(*) AS total', FALSE);
        $this->from_sequence();
        $this->apply_search($search);
        $query = $this->db->get();
        if ($query === FALSE) {
            return FALSE;
        }

        $row = $query->row_array();
        return $row === NULL ? FALSE : (int) $row['total'];
    }

    public function page($search, $length, $start, $sort, $direction)
    {
        if (!$this->has_expected_schema()) {
            return FALSE;
        }

        $sort = isset($this->sort_columns[$sort]) ? $sort : 'sequence_code';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $length = max(1, min(50, (int) $length));
        $offset = max(0, (int) $start);

        $this->db->select(
            's.sequence_code, s.sequence_name, s.description, ' .
            'COALESCE(o.option_name, s.repeat_type) AS repeat_type, ' .
            'COALESCE(sm.menu_codes, \'\') AS menu_codes, s.format',
            FALSE
        );
        $this->from_sequence();
        $this->apply_search($search);
        $this->db->order_by($this->sort_columns[$sort], $direction, FALSE);
        $this->db->limit($length, $offset);
        $query = $this->db->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    private function from_sequence()
    {
        $this->db->from('sys_sequence AS s');
        $this->db->join(
            'sys_parameter_option AS o',
            "o.option_code = s.repeat_type AND o.parameter_code = 'repeat_type'",
            'left',
            FALSE
        );
        $this->db->join(
            '(SELECT sequence_ccode, GROUP_CONCAT(menu_code ORDER BY menu_code SEPARATOR \',\') AS menu_codes '
                . 'FROM sys_sequence_menu GROUP BY sequence_ccode) AS sm',
            'sm.sequence_ccode = s.sequence_code',
            'left',
            FALSE
        );
    }

    private function apply_search($search)
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $this->db->group_start();
        $this->db->like('s.sequence_code', $search);
        $this->db->or_like('s.sequence_name', $search);
        $this->db->or_like('s.description', $search);
        $this->db->or_like('s.repeat_type', $search);
        $this->db->or_like('o.option_name', $search);
        $this->db->or_like('sm.menu_codes', $search);
        if (in_array('last_value', $this->sequence_fields, TRUE)) {
            $this->db->or_like('s.last_value', $search);
        }
        if (in_array('last_sequence', $this->sequence_fields, TRUE)) {
            $this->db->or_like('s.last_sequence', $search);
        }
        $this->db->or_like('s.format', $search);
        $this->db->group_end();
    }

    private function has_expected_schema()
    {
        if ($this->schema_checked) {
            return $this->schema_available;
        }
        $this->schema_checked = TRUE;

        // An absent table must produce a normal unavailable state, not a DB error page.
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $sequence_fields = $this->db->list_fields('sys_sequence');
        $option_fields = $this->db->list_fields('sys_parameter_option');
        $sequence_menu_fields = $this->db->list_fields('sys_sequence_menu');
        $this->db->db_debug = $previous_db_debug;
        $sequence_fields = is_array($sequence_fields) ? $sequence_fields : array();
        $option_fields = is_array($option_fields) ? $option_fields : array();
        $sequence_menu_fields = is_array($sequence_menu_fields) ? $sequence_menu_fields : array();
        $this->sequence_fields = $sequence_fields;

        $required_sequence_fields = array(
            'sequence_code', 'sequence_name', 'description', 'repeat_type', 'format',
        );
        $this->schema_available = count(array_diff($required_sequence_fields, $sequence_fields)) === 0
            && count(array_diff(array('option_code', 'option_name', 'parameter_code', 'line'), $option_fields)) === 0
            && count(array_diff(array('sequence_ccode', 'menu_code'), $sequence_menu_fields)) === 0;
        return $this->schema_available;
    }

    private function normalize_menu_codes($menu_codes)
    {
        if (count($menu_codes) > 50) {
            return array();
        }
        $normalized = array();
        foreach ($menu_codes as $menu_code) {
            if (is_string($menu_code) && $menu_code !== '') {
                $normalized[$menu_code] = $menu_code;
            }
        }
        return array_values($normalized);
    }
}
