<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped persistence for HR employee records. */
class Employee_store
{
    private $db;
    private $checked = FALSE;
    private $valid = FALSE;
    private $columns = array();
    private $tenant_cache = NULL;

    private $required_fields = array(
        'employee_id', 'tenant_id', 'employee_code', 'identity_id', 'employee_name',
        'email_address', 'gender', 'create_on', 'birth_date', 'birth_place'
    );

    private $input_fields = array(
        'employee_code', 'identity_id', 'employee_name', 'email_address',
        'gender', 'birth_date', 'birth_place', 'image'
    );

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            $CI->load->database();
            $db = $CI->db;
        }
        $this->db = $db;
    }

    public function schema_available()
    {
        if ($this->checked) { return $this->valid; }
        $this->checked = TRUE;
        $fields = $this->db->field_data('hr_employee');
        if (!is_array($fields) || !$fields) { return FALSE; }
        $definition_query = $this->db->query('SHOW COLUMNS FROM `hr_employee`');
        if ($definition_query === FALSE) { return FALSE; }
        $definitions = $definition_query->result_array();
        if (!is_array($definitions) || !$definitions) { return FALSE; }
        $definition_by_name = array();
        foreach ($definitions as $definition) {
            if (!isset($definition['Field'], $definition['Null']) || !array_key_exists('Default', $definition)
                || !isset($definition['Extra'])) { return FALSE; }
            $definition_by_name[$definition['Field']] = $definition;
        }
        foreach ($fields as $field) {
            if (!isset($field->name) || !isset($definition_by_name[$field->name])) { return FALSE; }
            $definition = $definition_by_name[$field->name];
            $field->null = $definition['Null'];
            $field->default = $definition['Default'];
            $field->auto_increment = stripos((string) $definition['Extra'], 'auto_increment') !== FALSE;
            $this->columns[$field->name] = $field;
        }
        foreach ($this->required_fields as $required) {
            if (!isset($this->columns[$required])) { return FALSE; }
        }
        $this->valid = $this->tenant_id() !== FALSE;
        return $this->valid;
    }

    /** Read the sole tenant scope from the authenticated session; fail closed. */
    public function tenant_id()
    {
        if ($this->tenant_cache !== NULL) { return $this->tenant_cache; }
        $CI =& get_instance();
        $session_user = $CI->session->userdata('admin_user');
        if (!is_array($session_user) || !isset($session_user['tenant_id'])
            || !is_numeric($session_user['tenant_id']) || (int) $session_user['tenant_id'] < 1) {
            $this->tenant_cache = FALSE;
            return FALSE;
        }
        $this->tenant_cache = (int) $session_user['tenant_id'];
        return $this->tenant_cache;
    }

    public function field_specs()
    {
        if (!$this->schema_available()) { return array(); }
        $specs = array();
        foreach (array('employee_code', 'identity_id', 'employee_name', 'email_address', 'birth_place') as $name) {
            $field = $this->columns[$name];
            $specs[$name] = array('max_length' => isset($field->max_length) ? (int) $field->max_length : 0);
        }
        return $specs;
    }

    /** Only enable writes when every mandatory column can be populated safely. */
    public function writes_supported()
    {
        if (!$this->schema_available() || !$this->gender_options()) { return FALSE; }
        $populated = array(
            'employee_id', 'tenant_id', 'employee_code', 'identity_id', 'employee_name',
            'email_address', 'gender', 'create_on', 'update_on', 'birth_date',
            'birth_place', 'image'
        );
        foreach ($this->columns as $name => $field) {
            $nullable = isset($field->null) && strtoupper((string) $field->null) === 'YES';
            $has_default = isset($field->default) && $field->default !== NULL;
            $auto = isset($field->auto_increment) && $field->auto_increment;
            if (!$nullable && !$has_default && !$auto && !in_array($name, $populated, TRUE)) { return FALSE; }
        }
        return TRUE;
    }

    /** Active gender values accepted by the hr_employee foreign key. */
    public function gender_options()
    {
        if (!$this->schema_available() || !$this->db->table_exists('sys_parameter_option')) { return array(); }
        $query = $this->db->select('option_code, option_name')
            ->where('parameter_code', 'gender')->where('active_flag', 1)
            ->order_by('line', 'ASC')->get('sys_parameter_option');
        return $query === FALSE ? array() : $query->result_array();
    }

    public function count($search, $tenant_id, $filter_code = '', $filter_name = '')
    {
        if (!$this->schema_available() || (int) $tenant_id !== $this->tenant_id()) { return FALSE; }
        $this->db->from('hr_employee')->where('tenant_id', (int) $tenant_id);
        if ($search !== '') {
            $this->db->group_start()->like('employee_code', $search)->or_like('identity_id', $search)
                ->or_like('employee_name', $search)->or_like('email_address', $search)->or_like('birth_place', $search)->group_end();
        }
        if ($filter_code !== '') { $this->db->like('employee_code', $filter_code); }
        if ($filter_name !== '') { $this->db->like('employee_name', $filter_name); }
        $query = $this->db->get();
        return $query === FALSE ? FALSE : (int) $query->num_rows();
    }

    public function page($search, $limit, $offset, $sort, $direction, $tenant_id, $filter_code = '', $filter_name = '')
    {
        if (!$this->schema_available() || (int) $tenant_id !== $this->tenant_id()) { return FALSE; }
        $sort_fields = array('employee_code', 'employee_name');
        if (!in_array($sort, $sort_fields, TRUE)) { $sort = 'employee_name'; }
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->db->select('hr_employee.*, sys_parameter_option.option_name AS gender_name', FALSE)
            ->from('hr_employee')->join('sys_parameter_option', 'sys_parameter_option.option_code = hr_employee.gender', 'left')
            ->where('hr_employee.tenant_id', (int) $tenant_id);
        if ($search !== '') {
            $this->db->group_start()->like('hr_employee.employee_code', $search)->or_like('hr_employee.identity_id', $search)
                ->or_like('hr_employee.employee_name', $search)->or_like('hr_employee.email_address', $search)
                ->or_like('hr_employee.birth_place', $search)->group_end();
        }
        if ($filter_code !== '') { $this->db->like('hr_employee.employee_code', $filter_code); }
        if ($filter_name !== '') { $this->db->like('hr_employee.employee_name', $filter_name); }
        $query = $this->db->order_by('hr_employee.' . $sort, $direction)->limit((int) $limit, (int) $offset)->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find($employee_id, $tenant_id)
    {
        if (!$this->schema_available() || !ctype_digit((string) $employee_id) || (int) $employee_id < 1
            || (int) $tenant_id !== $this->tenant_id()) { return NULL; }
        $query = $this->db->where('employee_id', (int) $employee_id)
            ->where('tenant_id', (int) $tenant_id)->limit(1)->get('hr_employee');
        return $query === FALSE ? FALSE : $query->row_array();
    }

    public function code_exists($code, $tenant_id, $exclude_id = 0)
    {
        if (!$this->schema_available() || (int) $tenant_id !== $this->tenant_id()) { return TRUE; }
        $this->db->from('hr_employee')->where('tenant_id', (int) $tenant_id)->where('employee_code', $code);
        if ((int) $exclude_id > 0) { $this->db->where('employee_id !=', (int) $exclude_id); }
        return $this->db->count_all_results() > 0;
    }

    public function insert($record, $tenant_id)
    {
        if (!$this->writes_supported() || (int) $tenant_id !== $this->tenant_id()) { return FALSE; }
        $data = $this->employee_data($record);
        $data['tenant_id'] = (int) $tenant_id;
        $data['create_on'] = date('Y-m-d H:i:s');
        return $this->db->insert('hr_employee', $data);
    }

    public function update($employee_id, $tenant_id, $record)
    {
        if (!$this->writes_supported() || (int) $tenant_id !== $this->tenant_id() || !$this->find($employee_id, $tenant_id)) { return FALSE; }
        $data = $this->employee_data($record);
        $data['update_on'] = date('Y-m-d H:i:s');
        return $this->db->where('employee_id', (int) $employee_id)->where('tenant_id', (int) $tenant_id)
            ->update('hr_employee', $data);
    }

    public function delete($employee_id, $tenant_id)
    {
        if (!$this->writes_supported() || (int) $tenant_id !== $this->tenant_id() || !$this->find($employee_id, $tenant_id)) { return FALSE; }
        return $this->db->where('employee_id', (int) $employee_id)->where('tenant_id', (int) $tenant_id)
            ->delete('hr_employee');
    }

    /** Store only known columns; never allow caller-supplied system values. */
    private function employee_data($record)
    {
        $data = array();
        foreach ($this->input_fields as $field) {
            if (isset($this->columns[$field])) { $data[$field] = isset($record[$field]) && $record[$field] !== '' ? $record[$field] : NULL; }
        }
        return $data;
    }

    /** Images live in application/cache, outside the public document root path. */
    public function image_path($filename)
    {
        if (!is_string($filename) || !preg_match('/\A[A-Za-z0-9_-]+\.(?:jpe?g|png|gif|webp)\z/i', $filename)) { return FALSE; }
        return APPPATH . 'cache/employee-images/' . $filename;
    }
}
