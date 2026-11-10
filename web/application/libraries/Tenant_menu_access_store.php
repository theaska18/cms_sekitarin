<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Schema-checked persistence for tenant menu and access selections. */
class Tenant_menu_access_store
{
    private $db;
    private $checked = FALSE;
    private $available = FALSE;

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
            return $this->available;
        }
        $this->checked = TRUE;
        $expected = array(
            'sys_tenant' => array('tenant_id'),
            'sys_tenant_menu' => array('tenant_menu_id', 'tenant_id', 'menu_code'),
            'sys_tenant_menu_access' => array('tenant_menu_access_id', 'tenant_menu_id', 'access_code'),
        );
        foreach ($expected as $table => $required) {
            $fields = $this->db->field_data($table);
            if (!is_array($fields) || !$fields) {
                return FALSE;
            }
            $found = array();
            foreach ($fields as $field) {
                if (isset($field->name)) {
                    $found[$field->name] = TRUE;
                }
            }
            foreach ($required as $column) {
                if (!isset($found[$column])) {
                    return FALSE;
                }
            }
        }
        return $this->available = TRUE;
    }

    /** Return selected menu/access codes keyed by menu code for one tenant. */
    public function state($tenant_id)
    {
        if (!$this->schema_available() || !is_numeric($tenant_id) || (int) $tenant_id < 1) {
            return FALSE;
        }
        $tenant_id = (int) $tenant_id;
        $tenant_query = $this->db->select('tenant_id')
            ->from('sys_tenant')
            ->where('tenant_id', $tenant_id)
            ->limit(1)
            ->get();
        if ($tenant_query === FALSE) {
            return FALSE;
        }
        if (!$tenant_query->result_array()) {
            return array();
        }
        $query = $this->db->select('m.menu_code, a.access_code')
            ->from('sys_tenant_menu m')
            ->join('sys_tenant_menu_access a', 'a.tenant_menu_id = m.tenant_menu_id', 'left')
            ->where('m.tenant_id', $tenant_id)
            ->get();
        if ($query === FALSE) {
            return FALSE;
        }
        $state = array();
        foreach ($query->result_array() as $row) {
            $menu_code = (string) $row['menu_code'];
            if (!isset($state[$menu_code])) {
                $state[$menu_code] = array();
            }
            if (isset($row['access_code']) && $row['access_code'] !== NULL) {
                $state[$menu_code][(string) $row['access_code']] = TRUE;
            }
        }
        return $state;
    }

    /** Replace the tenant's selection atomically; input is constrained by manifest. */
    public function save($tenant_id, $menu_codes, $access_codes, $manifest_access)
    {
        if (!$this->schema_available() || !is_numeric($tenant_id) || (int) $tenant_id < 1
            || !is_array($menu_codes) || !is_array($access_codes) || !is_array($manifest_access)) {
            return FALSE;
        }
        $allowed = array();
        foreach ($manifest_access as $menu_code => $accesses) {
            if (!is_string($menu_code) || !is_array($accesses)) {
                return FALSE;
            }
            $allowed[$menu_code] = array();
            foreach ($accesses as $access_code) {
                if (is_string($access_code)) {
                    $allowed[$menu_code][$access_code] = TRUE;
                }
            }
        }
        $selected = array();
        foreach ($menu_codes as $menu_code) {
            if (!is_string($menu_code) || !isset($allowed[$menu_code])) {
                return FALSE;
            }
            $selected[$menu_code] = TRUE;
        }
        $selected_accesses = array();
        foreach ($selected as $menu_code => $_) {
            $submitted = isset($access_codes[$menu_code]) ? $access_codes[$menu_code] : array();
            if (!is_array($submitted)) {
                return FALSE;
            }
            $selected_accesses[$menu_code] = array();
            foreach ($submitted as $access_code) {
                if (!is_string($access_code) || !isset($allowed[$menu_code][$access_code])) {
                    return FALSE;
                }
                $selected_accesses[$menu_code][$access_code] = TRUE;
            }
        }

        $this->db->trans_begin();
        $existing = $this->db->select('tenant_menu_id')->where('tenant_id', (int) $tenant_id)->get('sys_tenant_menu');
        if ($existing === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        foreach ($existing->result_array() as $row) {
            if ($this->db->where('tenant_menu_id', (int) $row['tenant_menu_id'])->delete('sys_tenant_menu_access') !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }
        if ($this->db->where('tenant_id', (int) $tenant_id)->delete('sys_tenant_menu') !== TRUE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        foreach ($selected as $menu_code => $_) {
            if ($this->db->insert('sys_tenant_menu', array(
                'tenant_id' => (int) $tenant_id,
                'menu_code' => $menu_code,
            )) !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
            $tenant_menu_id = (int) $this->db->insert_id();
            foreach ($selected_accesses[$menu_code] as $access_code => $_selected) {
                if ($this->db->insert('sys_tenant_menu_access', array(
                    'tenant_menu_id' => $tenant_menu_id,
                    'access_code' => $access_code,
                )) !== TRUE) {
                    $this->db->trans_rollback();
                    return FALSE;
                }
            }
        }
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return $this->db->trans_status();
    }
}
