<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Schema-checked role menu/access persistence. */
class Role_menu_access_store
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
            'app_role_menu' => array('role_menu_id', 'role_id', 'menu_code'),
            'app_role_menu_access' => array('role_menu_access_id', 'role_menu_id', 'access_code'),
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

    /** Return configured role menu/access codes keyed by menu code. */
    public function state($role_id)
    {
        if (!$this->schema_available() || !is_numeric($role_id) || (int) $role_id < 1) {
            return FALSE;
        }
        $query = $this->db->select('m.menu_code, a.access_code')
            ->from('app_role_menu m')
            ->join('app_role_menu_access a', 'a.role_menu_id = m.role_menu_id', 'left')
            ->where('m.role_id', (int) $role_id)
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

    /** Replace one role's selections; submitted codes must exist in the app menu catalog. */
    public function save($role_id, $menu_codes, $access_codes, $menu_catalog)
    {
        if (!$this->schema_available() || !is_numeric($role_id) || (int) $role_id < 1
            || !is_array($menu_codes) || !is_array($access_codes) || !is_array($menu_catalog)) {
            return FALSE;
        }
        $allowed = array();
        foreach ($menu_catalog as $menu_code => $accesses) {
            if (!is_string($menu_code) || !is_array($accesses)) {
                return FALSE;
            }
            $allowed[$menu_code] = array();
            foreach ($accesses as $access_code => $enabled) {
                if (is_string($access_code) && $enabled) {
                    $allowed[$menu_code][$access_code] = TRUE;
                }
            }
        }

        $selected = array();
        foreach ($menu_codes as $menu_code) {
            if (!is_string($menu_code) || !array_key_exists($menu_code, $allowed)) {
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

        $role_id = (int) $role_id;
        $this->db->trans_begin();
        $existing = $this->db->select('role_menu_id')->where('role_id', $role_id)->get('app_role_menu');
        if ($existing === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        foreach ($existing->result_array() as $row) {
            if ($this->db->where('role_menu_id', $row['role_menu_id'])->delete('app_role_menu_access') !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }
        if ($this->db->where('role_id', $role_id)->delete('app_role_menu') !== TRUE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        foreach ($selected as $menu_code => $_) {
            if ($this->db->insert('app_role_menu', array(
                'role_id' => $role_id,
                'menu_code' => $menu_code,
            )) !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
            $role_menu_id = $this->db->insert_id();
            foreach ($selected_accesses[$menu_code] as $access_code => $_selected) {
                if ($this->db->insert('app_role_menu_access', array(
                    'role_menu_id' => $role_menu_id,
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
        if (!$this->db->trans_status()) {
            return FALSE;
        }

        $saved = $this->state($role_id);
        $expected = array();
        foreach ($selected as $menu_code => $_) {
            $expected[$menu_code] = $selected_accesses[$menu_code];
        }
        ksort($saved);
        ksort($expected);
        foreach ($saved as &$accesses) {
            ksort($accesses);
        }
        unset($accesses);
        foreach ($expected as &$accesses) {
            ksort($accesses);
        }
        unset($accesses);
        return $saved === $expected;
    }

    /** Insert or remove a single role menu when the tenant allows it. */
    public function toggle_menu($role_id, $menu_code, $checked, $menu_catalog)
    {
        if (!$this->schema_available() || !is_numeric($role_id) || (int) $role_id < 1
            || !is_string($menu_code) || $menu_code === '' || !is_array($menu_catalog)
            || !array_key_exists($menu_code, $menu_catalog)) {
            return FALSE;
        }
        $role_id = (int) $role_id;
        $menu_id = $this->menu_row_id($role_id, $menu_code);
        if ($menu_id === FALSE) {
            return FALSE;
        }
        $this->db->trans_begin();
        if ($checked) {
            if ($menu_id === NULL && $this->db->insert('app_role_menu', array(
                'role_id' => $role_id,
                'menu_code' => $menu_code,
            )) !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
        } elseif ($menu_id !== NULL) {
            if ($this->db->where('role_menu_id', $menu_id)->delete('app_role_menu_access') !== TRUE
                || $this->db->where('role_menu_id', $menu_id)->delete('app_role_menu') !== TRUE) {
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

    /** Insert or remove one role access row when the tenant allows it. */
    public function toggle_access($role_id, $menu_code, $access_code, $checked, $menu_catalog)
    {
        if (!$this->schema_available() || !is_numeric($role_id) || (int) $role_id < 1
            || !is_string($menu_code) || $menu_code === '' || !is_string($access_code) || $access_code === ''
            || !is_array($menu_catalog) || !array_key_exists($menu_code, $menu_catalog)
            || !is_array($menu_catalog[$menu_code]) || !isset($menu_catalog[$menu_code][$access_code])) {
            return FALSE;
        }
        $role_id = (int) $role_id;
        $menu_id = $this->menu_row_id($role_id, $menu_code);
        if ($menu_id === FALSE) {
            return FALSE;
        }
        $this->db->trans_begin();
        if ($checked) {
            if ($menu_id === NULL) {
                if ($this->db->insert('app_role_menu', array(
                    'role_id' => $role_id,
                    'menu_code' => $menu_code,
                )) !== TRUE) {
                    $this->db->trans_rollback();
                    return FALSE;
                }
                $menu_id = (int) $this->db->insert_id();
            }
            $existing = $this->db->select('role_menu_access_id')
                ->where(array('role_menu_id' => $menu_id, 'access_code' => $access_code))
                ->limit(1)
                ->get('app_role_menu_access');
            if ($existing === FALSE) {
                $this->db->trans_rollback();
                return FALSE;
            }
            if (!$existing->row_array() && $this->db->insert('app_role_menu_access', array(
                'role_menu_id' => $menu_id,
                'access_code' => $access_code,
            )) !== TRUE) {
                $this->db->trans_rollback();
                return FALSE;
            }
        } elseif ($menu_id !== NULL) {
            if ($this->db->where(array('role_menu_id' => $menu_id, 'access_code' => $access_code))
                ->delete('app_role_menu_access') !== TRUE) {
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

    /** Return the role menu primary key, NULL when absent, FALSE on query failure. */
    private function menu_row_id($role_id, $menu_code)
    {
        $query = $this->db->select('role_menu_id')
            ->where(array('role_id' => (int) $role_id, 'menu_code' => $menu_code))
            ->limit(1)
            ->get('app_role_menu');
        if ($query === FALSE) {
            return FALSE;
        }
        $row = $query->row_array();

        return $row ? (int) $row['role_menu_id'] : NULL;
    }
}
