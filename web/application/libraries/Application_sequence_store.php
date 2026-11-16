<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped overrides of system sequence templates. */
class Application_sequence_store
{
    private $db;
    private $format_generator;
    private $schema_checked = FALSE;
    private $schema_available = FALSE;
    private $sort_columns = array(
        'sequence_code' => 's.sequence_code',
        'sequence_name' => 's.sequence_name',
        'repeat_type' => 'COALESCE(a.repeat_type, s.repeat_type)',
        'format' => 'COALESCE(a.format, s.format)',
        'last_value' => 'a.last_value',
        'use_flag' => 'CASE WHEN a.sequence_id IS NULL THEN \'0\' ELSE a.use_flag END',
    );

    public function __construct()
    {
        $CI =& get_instance();
        $this->db = $CI->db;
        $CI->load->library('sequence_format');
        $this->format_generator = $CI->sequence_format;
    }

    public function schema_available()
    {
        if ($this->schema_checked) {
            return $this->schema_available;
        }
        $this->schema_checked = TRUE;
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $application_fields = $this->db->list_fields('app_sequence');
        $sequence_fields = $this->db->list_fields('sys_sequence');
        $sequence_menu_fields = $this->db->list_fields('sys_sequence_menu');
        $option_fields = $this->db->list_fields('sys_parameter_option');
        $this->db->db_debug = $previous_db_debug;
        $application_fields = is_array($application_fields) ? $application_fields : array();
        $sequence_fields = is_array($sequence_fields) ? $sequence_fields : array();
        $sequence_menu_fields = is_array($sequence_menu_fields) ? $sequence_menu_fields : array();
        $option_fields = is_array($option_fields) ? $option_fields : array();

        $this->schema_available =
            count(array_diff(
                array('sequence_id', 'tenant_id', 'sequence_code', 'repeat_type', 'format', 'last_value', 'last_repeat', 'use_flag'),
                $application_fields
            )) === 0
            && count(array_diff(array('sequence_code', 'sequence_name', 'repeat_type', 'format'), $sequence_fields)) === 0
            && count(array_diff(array('sequence_ccode', 'menu_code'), $sequence_menu_fields)) === 0
            && count(array_diff(array('option_code', 'option_name', 'parameter_code', 'active_flag', 'line'), $option_fields)) === 0;
        return $this->schema_available;
    }

    public function repeat_type_options()
    {
        if (!$this->schema_available()) {
            return FALSE;
        }
        $query = $this->db->select('option_code, option_name')
            ->from('sys_parameter_option')
            ->where('parameter_code', 'repeat_type')
            ->where('active_flag', 1)
            ->order_by('line', 'ASC')
            ->order_by('option_code', 'ASC')
            ->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function sequence_config($tenant_id, $sequence_code)
    {
        if (!$this->schema_available() || (int) $tenant_id < 1 || !is_string($sequence_code) || $sequence_code === '') {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $query = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('sequence_code', $sequence_code)
            ->limit(1)
            ->get('app_sequence');
        $this->db->db_debug = $previous_db_debug;
        if ($query === FALSE) {
            return FALSE;
        }
        return $query->num_rows() ? $query->row_array() : NULL;
    }

    /** Preview a code without consuming the counter. */
    public function preview_code($tenant_id, $sequence_code)
    {
        $row = $this->sequence_config($tenant_id, $sequence_code);
        if ($row === FALSE) {
            return FALSE;
        }
        if ($row === NULL || (string) $row['use_flag'] !== '1') {
            return NULL;
        }
        return $this->next_code_from_row($row);
    }

    /** Lock and calculate the next value; caller must commit/rollback its transaction. */
    public function next_code_for_update($tenant_id, $sequence_code)
    {
        if (!$this->schema_available() || (int) $tenant_id < 1 || !is_string($sequence_code) || $sequence_code === '') {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $query = $this->db->query(
            'SELECT sequence_code, repeat_type, format, `last_value`, last_repeat, use_flag '
            . 'FROM app_sequence WHERE tenant_id = ? AND sequence_code = ? LIMIT 1 FOR UPDATE',
            array((int) $tenant_id, $sequence_code)
        );
        $this->db->db_debug = $previous_db_debug;
        if ($query === FALSE) {
            return FALSE;
        }
        if (!$query->num_rows() || (string) $query->row()->use_flag !== '1') {
            return NULL;
        }
        $next = $this->next_code_from_row($query->row_array());
        if (is_array($next)) {
            $next['format'] = (string) $query->row()->format;
        }
        return $next;
    }

    /** Advance a locked in-memory candidate after its generated code is already used. */
    public function next_code_after($current)
    {
        if (!is_array($current) || !isset($current['last_value'], $current['last_repeat'], $current['format'])) {
            return FALSE;
        }
        $next_value = (int) $current['last_value'] + 1;
        if ($next_value < 1 || $next_value > 2147483647) {
            return FALSE;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $current['last_repeat']);
        if (!$date instanceof DateTimeImmutable) {
            return FALSE;
        }
        $code = $this->format_generator->generate((string) $current['format'], $next_value, $date);
        if (!is_string($code) || $code === '') {
            return FALSE;
        }
        $current['code'] = $code;
        $current['last_value'] = $next_value;
        return $current;
    }

    public function advance_sequence($tenant_id, $sequence_code, $next)
    {
        if (!$this->schema_available() || (int) $tenant_id < 1 || !is_string($sequence_code)
            || !is_array($next) || !isset($next['last_value'], $next['last_repeat'])) {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('sequence_code', $sequence_code)
            ->update('app_sequence', array(
                'last_value' => (int) $next['last_value'],
                'last_repeat' => $next['last_repeat'],
            ));
        $error = $this->db->error();
        $this->db->db_debug = $previous_db_debug;
        return $result === TRUE && (!is_array($error) || empty($error['code']))
            && $this->db->affected_rows() === 1;
    }

    public function count($tenant_id, $menu_codes, $search = '')
    {
        if (!$this->schema_available() || (int) $tenant_id < 1 || !is_array($menu_codes)) {
            return FALSE;
        }
        if (!$menu_codes) {
            return 0;
        }
        $this->sequence_query($tenant_id, $menu_codes);
        $this->apply_search($search);
        $query = $this->db->select('COUNT(DISTINCT s.sequence_code) AS total', FALSE)->get();
        if ($query === FALSE) {
            return FALSE;
        }
        return (int) $query->row()->total;
    }

    public function page($tenant_id, $menu_codes, $search, $length, $start, $sort, $direction)
    {
        if (!$this->schema_available() || (int) $tenant_id < 1 || !is_array($menu_codes)) {
            return FALSE;
        }
        if (!$menu_codes) {
            return array();
        }
        $sort = isset($this->sort_columns[$sort]) ? $sort : 'sequence_code';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $length = max(1, min(50, (int) $length));
        $start = max(0, (int) $start);
        $this->sequence_query($tenant_id, $menu_codes);
        $this->db->distinct();
        $this->db->select(
            's.sequence_code, s.sequence_name, ' .
            'COALESCE(a.repeat_type, s.repeat_type) AS repeat_type, ' .
            'COALESCE(a.format, s.format) AS format, a.last_value, ' .
            'CASE WHEN a.sequence_id IS NULL THEN \'0\' ELSE a.use_flag END AS use_flag, ' .
            'CASE WHEN a.sequence_id IS NULL THEN 0 ELSE 1 END AS configured',
            FALSE
        );
        $this->apply_search($search);
        $this->db->order_by($this->sort_columns[$sort], $direction, FALSE);
        if ($sort !== 'sequence_code') {
            $this->db->order_by('s.sequence_code', 'ASC');
        }
        $query = $this->db->limit($length, $start)->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find_for_tenant($tenant_id, $sequence_code, $menu_codes)
    {
        if (!$this->schema_available() || (int) $tenant_id < 1 || !is_string($sequence_code)
            || $sequence_code === '' || !is_array($menu_codes) || !$menu_codes) {
            return FALSE;
        }
        $this->db->select(
            's.sequence_code, s.sequence_name, ' .
            'COALESCE(a.repeat_type, s.repeat_type) AS repeat_type, ' .
            'COALESCE(a.format, s.format) AS format, ' .
            'COALESCE(a.last_value, 0) AS `last_value`, ' .
            'CASE WHEN a.sequence_id IS NULL THEN \'0\' ELSE a.use_flag END AS use_flag, ' .
            'CASE WHEN a.sequence_id IS NULL THEN 0 ELSE 1 END AS configured',
            FALSE
        );
        $this->sequence_query($tenant_id, $menu_codes);
        $query = $this->db->where('s.sequence_code', $sequence_code)
            ->limit(1)
            ->get();
        if ($query === FALSE) {
            return FALSE;
        }
        return $query->num_rows() ? $query->row_array() : NULL;
    }

    /** Save the four tenant-editable fields, inserting an override when it is absent. */
    public function save_for_tenant($tenant_id, $sequence_code, $values)
    {
        if (!$this->schema_available() || (int) $tenant_id < 1 || !is_string($sequence_code)
            || $sequence_code === '' || !is_array($values)) {
            return FALSE;
        }
        if (!isset($values['format']) || !$this->format_generator->is_valid((string) $values['format'])) {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->db->query(
            'INSERT INTO app_sequence (tenant_id, sequence_code, repeat_type, format, `last_value`, last_repeat, use_flag) '
            . 'VALUES (?, ?, ?, ?, ?, NULL, ?) '
            . 'ON DUPLICATE KEY UPDATE repeat_type = VALUES(repeat_type), format = VALUES(format), '
            . '`last_value` = VALUES(`last_value`), use_flag = VALUES(use_flag)',
            array(
                (int) $tenant_id,
                $sequence_code,
                $values['repeat_type'],
                $values['format'],
                $values['last_value'],
                $values['use_flag'],
            )
        );
        $error = $this->db->error();
        $this->db->db_debug = $previous_db_debug;
        return $result === TRUE && (!is_array($error) || empty($error['code']));
    }

    /** Restore only the tenant override's repeat type and format from its template. */
    public function reset_to_template($tenant_id, $sequence_code, $menu_codes)
    {
        if (!$this->schema_available() || (int) $tenant_id < 1 || !is_string($sequence_code)
            || $sequence_code === '' || !is_array($menu_codes) || !$menu_codes) {
            return FALSE;
        }
        $template = $this->db->select('s.repeat_type, s.format')
            ->from('sys_sequence AS s')
            ->join(
                'sys_sequence_menu AS sm',
                'sm.sequence_ccode = s.sequence_code',
                'inner',
                FALSE
            )
            ->where('s.sequence_code', $sequence_code)
            ->where_in('sm.menu_code', $menu_codes)
            ->limit(1)
            ->get();
        if ($template === FALSE || !$template->num_rows()) {
            return FALSE;
        }
        $existing = $this->db->select('sequence_id')
            ->where('tenant_id', (int) $tenant_id)
            ->where('sequence_code', $sequence_code)
            ->limit(1)
            ->get('app_sequence');
        if ($existing === FALSE || !$existing->num_rows()) {
            return FALSE;
        }
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('sequence_code', $sequence_code)
            ->update('app_sequence', array(
                'repeat_type' => (string) $template->row()->repeat_type,
                'format' => (string) $template->row()->format,
            ));
        $error = $this->db->error();
        $this->db->db_debug = $previous_db_debug;
        return $result === TRUE && (!is_array($error) || empty($error['code']));
    }

    private function sequence_query($tenant_id, $menu_codes)
    {
        $this->db->from('sys_sequence AS s');
        $this->db->join(
            'sys_sequence_menu AS sm',
            'sm.sequence_ccode = s.sequence_code',
            'inner',
            FALSE
        );
        $this->db->join(
            'app_sequence AS a',
            'a.sequence_code = s.sequence_code AND a.tenant_id = ' . (int) $tenant_id,
            'left',
            FALSE
        );
        $this->db->where_in('sm.menu_code', $menu_codes);
    }

    private function apply_search($search)
    {
        $search = is_string($search) ? trim(substr($search, 0, 120)) : '';
        if ($search === '') {
            return;
        }
        $this->db->group_start()
            ->like('s.sequence_code', $search)
            ->or_like('s.sequence_name', $search)
            ->or_like('COALESCE(a.repeat_type, s.repeat_type)', $search, 'both', FALSE)
            ->or_like('COALESCE(a.format, s.format)', $search, 'both', FALSE)
            ->or_like('a.last_value', $search)
            ->or_like('a.use_flag', $search)
            ->group_end();
    }

    private function next_code_from_row($row)
    {
        $repeat_type = isset($row['repeat_type']) ? (string) $row['repeat_type'] : '';
        $format = isset($row['format']) ? (string) $row['format'] : '';
        if ($format === '' || !in_array($repeat_type, array(
            'repeat_type_no', 'repeat_type_daily', 'repeat_type_weekly',
            'repeat_type_monthly', 'repeat_type_yearly',
        ), TRUE)) {
            return FALSE;
        }

        $today = new DateTimeImmutable('today');
        $current_period = $this->repeat_period($repeat_type, $today);
        $last_repeat = isset($row['last_repeat']) && is_string($row['last_repeat']) ? $row['last_repeat'] : '';
        $last_period = '';
        if ($last_repeat !== '' && $last_repeat !== '0000-00-00') {
            $last_date = DateTimeImmutable::createFromFormat('!Y-m-d', $last_repeat);
            if ($last_date instanceof DateTimeImmutable) {
                $last_period = $this->repeat_period($repeat_type, $last_date);
            }
        }
        $last_value = max(0, (int) $row['last_value']);
        $next_value = $repeat_type === 'repeat_type_no' || $last_period === $current_period
            ? $last_value + 1
            : 1;
        if ($next_value < 1 || $next_value > 2147483647) {
            return FALSE;
        }
        $code = $this->format_generator->generate($format, $next_value, $today);
        if (!is_string($code) || $code === '') {
            return FALSE;
        }
        return array(
            'code' => $code,
            'last_value' => $next_value,
            'last_repeat' => $today->format('Y-m-d'),
            'format' => $format,
        );
    }

    private function repeat_period($repeat_type, $date)
    {
        if ($repeat_type === 'repeat_type_daily') return $date->format('Y-m-d');
        if ($repeat_type === 'repeat_type_weekly') return $date->format('o-W');
        if ($repeat_type === 'repeat_type_monthly') return $date->format('Y-m');
        if ($repeat_type === 'repeat_type_yearly') return $date->format('Y');
        return '';
    }

}
