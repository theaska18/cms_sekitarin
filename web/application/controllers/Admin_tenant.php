<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Tenant menu controller; inherits the authenticated admin shell. */
class Admin_tenant extends Admin
{
    public function tenant_index()
    {
        $menu_access = $this->has_admin_menu_code('sys_tenant');
        if (!$menu_access) {
            $this->tenant_deny_access('tenant_no_access');
            return;
        }
        $add_allowed = $this->has_admin_access_code('sys_tenant_add');
        $update_allowed = $this->has_admin_access_code('sys_tenant_update');
        $delete_allowed = $this->has_admin_access_code('sys_tenant_delete');
        $menu_access_allowed = $this->has_admin_access_code('sys_tenant_menu_access');
        $actions_available = $update_allowed || $delete_allowed || $menu_access_allowed;
        $store = $this->tenant_store();
        $filters = array(
            'search' => $this->tenant_filter($this->input->get('search', TRUE)),
        );
        $sort = $this->tenant_sort_field($this->input->get('sort', TRUE));
        $direction = $this->tenant_sort_direction($this->input->get('direction', TRUE));
        $per_page = $this->tenant_page_size();
        $page = $this->tenant_page_number();
        $total_records = 0;
        $tenant_records = array();
        $backend_available = $store !== NULL && $store->schema_available();
        if ($backend_available) {
            $total_records = $store->count($filters);
            if ($total_records === FALSE) {
                $backend_available = FALSE;
                $total_records = 0;
            } else {
                $page_count = max(1, (int) ceil($total_records / $per_page));
                $page = min($page, $page_count);
                $tenant_records = $store->page($filters, $per_page, ($page - 1) * $per_page, $sort, $direction);
                if ($tenant_records === FALSE) {
                    $backend_available = FALSE;
                    $total_records = 0;
                    $tenant_records = array();
                }
            }
        }
        $page_count = max(1, (int) ceil($total_records / $per_page));
        $status = $this->session->flashdata('admin_tenant_status');
        $this->render_admin(
            'admin/tenant/index',
            'tenant',
            '/admin/system/tenant',
            array(
                'filters' => $filters,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $per_page,
                'per_page_choices' => array(10, 25, 50),
                'current_page' => $page,
                'page_count' => $page_count,
                'total_records' => $total_records,
                'tenant_records' => $tenant_records,
                'backend_available' => $backend_available,
                'write_available' => $this->tenant_write_available($store),
                'tenant_add_authorized' => $add_allowed,
                'tenant_update_authorized' => $update_allowed,
                'tenant_delete_authorized' => $delete_allowed,
                'tenant_menu_access_authorized' => $menu_access_allowed,
                'tenant_has_actions' => $actions_available,
                'status_key' => is_string($status) ? $status : '',
            )
        );
    }

    public function tenant_new()
    {
        if (!$this->has_admin_menu_code('sys_tenant') || !$this->has_admin_access_code('sys_tenant_add')) {
            $this->tenant_deny_access($this->has_admin_menu_code('sys_tenant') ? 'tenant_add_denied' : 'tenant_no_access');
            return;
        }
        $store = $this->tenant_store();
        $backend_available = $store !== NULL && $store->schema_available();
        $write_available = $this->tenant_write_available($store);
        $record = array('tenant_code' => '', 'tenant_name' => '', 'address' => '', 'email_address' => '', 'phone_number' => '', 'due_flag' => 0, 'due_on' => '');
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Tenant writes are unavailable.', 503);
                return;
            }
            $record = $this->tenant_form_values(TRUE);
            $errors = $this->validate_tenant_values($record, $store, TRUE);
            if (!$errors) {
                if ($store->insert($record)) {
                    $this->session->set_flashdata('admin_tenant_status', 'created');
                    redirect('/admin/system/tenant', 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
        }
        $this->render_admin(
            'admin/tenant/form',
            'tenant',
            '/admin/system/tenant/new',
            array(
                'tenant_mode' => 'new',
                'tenant_code' => '',
                'tenant_record' => $record,
                'tenant_fields' => $store ? $store->fields() : array(),
                'tenant_errors' => $errors,
                'backend_available' => $backend_available,
                'write_available' => $write_available,
            )
        );
    }

    public function tenant_change($tenant_code)
    {
        if (!$this->has_admin_menu_code('sys_tenant') || !$this->has_admin_access_code('sys_tenant_update')) {
            $this->tenant_deny_access($this->has_admin_menu_code('sys_tenant') ? 'tenant_update_denied' : 'tenant_no_access');
            return;
        }
        if (!$this->is_valid_tenant_code($tenant_code)) {
            show_404();
            return;
        }
        $store = $this->tenant_store();
        $backend_available = $store !== NULL && $store->schema_available();
        $write_available = $this->tenant_write_available($store);
        $record = $backend_available ? $store->find($tenant_code) : NULL;
        if ($record === FALSE) {
            $backend_available = FALSE;
            $record = NULL;
        }
        if ($backend_available && $record === NULL) {
            show_404();
            return;
        }
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available || !$backend_available) {
                show_error('Tenant writes are unavailable.', 503);
                return;
            }
            $values = $this->tenant_form_values(FALSE);
            $values['tenant_code'] = $tenant_code;
            $errors = $this->validate_tenant_values($values, $store, FALSE);
            if (!$errors) {
                $result = $store->update($tenant_code, $values);
                if ($result === 'updated' || $result === 'unchanged') {
                    $this->session->set_flashdata('admin_tenant_status', 'saved');
                    redirect('/admin/system/tenant', 'location', 303);
                    return;
                }
                if ($result === 'not_found') {
                    show_404();
                    return;
                }
                $errors[] = 'save_error';
            }
            $record = array_merge($record, $values);
        }
        $this->render_admin(
            'admin/tenant/form',
            'tenant',
            '/admin/system/tenant/change/' . rawurlencode($tenant_code),
            array(
                'tenant_mode' => 'change',
                'tenant_code' => $tenant_code,
                'tenant_record' => $record,
                'tenant_fields' => $store ? $store->fields() : array(),
                'tenant_errors' => $errors,
                'backend_available' => $backend_available,
                'write_available' => $write_available,
            )
        );
    }

    public function tenant_delete()
    {
        if (!$this->has_admin_menu_code('sys_tenant') || !$this->has_admin_access_code('sys_tenant_delete')) {
            $this->tenant_deny_access($this->has_admin_menu_code('sys_tenant') ? 'tenant_delete_denied' : 'tenant_no_access');
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $store = $this->tenant_store();
        if (!$this->tenant_write_available($store)) {
            show_error('Tenant writes are unavailable.', 503);
            return;
        }
        $tenant_code = $this->input->post('tenant_code', FALSE);
        if (!$this->is_valid_tenant_code($tenant_code)) {
            $this->session->set_flashdata('admin_tenant_status', 'invalid');
            redirect('/admin/system/tenant', 'location', 303);
            return;
        }
        $result = $store->delete($tenant_code);
        $this->session->set_flashdata('admin_tenant_status', $result === 'deleted' ? 'deleted' : ($result === 'not_found' ? 'not_found' : 'delete_error'));
        redirect('/admin/system/tenant', 'location', 303);
    }

    public function tenant_menu($tenant_code)
    {
        if (!$this->has_admin_menu_code('sys_tenant') || !$this->has_admin_access_code('sys_tenant_menu_access')) {
            $this->tenant_deny_access($this->has_admin_menu_code('sys_tenant') ? 'tenant_menu_access_denied' : 'tenant_no_access');
            return;
        }
        if (!$this->is_valid_tenant_code($tenant_code)) {
            show_404();
            return;
        }
        $tenant_store = $this->tenant_store();
        $backend_available = $tenant_store !== NULL && $tenant_store->schema_available();
        $tenant = $backend_available ? $tenant_store->find($tenant_code) : NULL;
        if ($tenant === FALSE) {
            $backend_available = FALSE;
            $tenant = NULL;
        }
        if ($backend_available && $tenant === NULL) {
            show_404();
            return;
        }

        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('tenant_menu_access_store');
        $menu_store = $this->tenant_menu_access_store;
        $backend_available = $backend_available && $tenant !== NULL && $menu_store->schema_available();
        $write_available = $this->tenant_write_available($tenant_store)
            && $menu_store->schema_available();

        $this->load->library('admin_menu_manifest');
        $groups = $this->admin_menu_manifest->load(APPPATH . 'views/admin/menu_manifest/manifest.xml');
        $manifest_access = array();
        $menu_groups = array();
        foreach ($groups as $group) {
            $group_key = $group['label_key'];
            $group_translation = preg_match('/^admin_[a-z0-9_]+$/', $group_key)
                ? $this->lang->line($group_key)
                : FALSE;
            $group_fallback = $group['name'] !== '' ? $group['name'] : $group['code'];
            $group['label'] = $this->admin_menu_manifest->resolve_label($group_translation, $group_fallback);
            foreach ($group['items'] as $item) {
                $codes = array();
                foreach ($item['accesses'] as $access) {
                    $codes[] = $access['access_code'];
                }
                $manifest_access[$item['code']] = $codes;
            }
            $menu_groups[] = $group;
        }

        $errors = array();
        $status_key = $this->session->flashdata('admin_tenant_status');
        $selected_state = $backend_available ? $menu_store->state((int) $tenant['tenant_id']) : array();
        if ($selected_state === FALSE) {
            $backend_available = FALSE;
            $write_available = FALSE;
            $selected_state = array();
        }
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Tenant menu access writes are unavailable.', 503);
                return;
            }
            $posted_menus = $this->input->post('menu_codes', FALSE);
            $posted_access = $this->input->post('access_codes', FALSE);
            $posted_menus = is_array($posted_menus) ? $posted_menus : array();
            $posted_access = is_array($posted_access) ? $posted_access : array();
            if ($menu_store->save((int) $tenant['tenant_id'], $posted_menus, $posted_access, $manifest_access)) {
                $this->session->set_flashdata('admin_tenant_status', 'saved');
                redirect('/admin/system/tenant/menu/' . rawurlencode($tenant_code), 'location', 303);
                return;
            }
            $errors[] = 'save_error';
            $selected_state = array();
            foreach ($posted_menus as $menu_code) {
                if (is_string($menu_code) && isset($manifest_access[$menu_code])) {
                    $selected_state[$menu_code] = array();
                    $submitted = isset($posted_access[$menu_code]) && is_array($posted_access[$menu_code])
                        ? $posted_access[$menu_code] : array();
                    foreach ($submitted as $access_code) {
                        if (in_array($access_code, $manifest_access[$menu_code], TRUE)) {
                            $selected_state[$menu_code][$access_code] = TRUE;
                        }
                    }
                }
            }
        }

        foreach ($menu_groups as &$group) {
            foreach ($group['items'] as &$item) {
                $menu_code = $item['code'];
                $item['checked'] = isset($selected_state[$menu_code]);
                foreach ($item['accesses'] as &$access) {
                    $access['checked'] = $item['checked']
                        && isset($selected_state[$menu_code][$access['access_code']]);
                }
                unset($access);
            }
            unset($item);
        }
        unset($group);

        $this->render_admin(
            'admin/tenant/menu',
            'tenant',
            '/admin/system/tenant/menu/' . rawurlencode($tenant_code),
            array(
                'tenant_menu_record' => $tenant,
                'tenant_menu_groups' => $menu_groups,
                'tenant_menu_label' => $this->lang->line('admin_menu'),
                'tenant_menu_errors' => $errors,
                'tenant_menu_status_key' => is_string($status_key) ? $status_key : '',
                'tenant_menu_backend_available' => $backend_available,
                'tenant_menu_write_available' => $write_available,
            )
        );
    }

    private function tenant_store()
    {
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
            return NULL;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('tenant_store');
        return $this->tenant_store;
    }

    private function tenant_write_available($store)
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development'
            && $this->current_user !== NULL
            && (bool) config_item('csrf_protection')
            && $store !== NULL
            && $store->schema_available();
    }

    private function tenant_form_values($include_code)
    {
        $names = $include_code
            ? array('tenant_code', 'tenant_name', 'address', 'email_address', 'phone_number', 'due_flag', 'due_on')
            : array('tenant_name', 'address', 'email_address', 'phone_number', 'due_flag', 'due_on');
        $values = array();
        foreach ($names as $name) {
            $value = $this->input->post($name, FALSE);
            $values[$name] = is_string($value) ? trim($value) : '';
            if ($name === 'tenant_code') {
                $values[$name] = strtolower(preg_replace('/\s+/', '', $values[$name]));
            }
            if ($name === 'tenant_name') {
                $values[$name] = $this->tenant_title_case($values[$name]);
            } elseif ($name === 'due_flag') {
                $values[$name] = $values[$name] === '1' ? 1 : 0;
            }
        }
        if ((int) $values['due_flag'] === 0) {
            $values['due_on'] = '';
        }
        return $values;
    }

    private function tenant_title_case($value)
    {
        return preg_replace_callback('/(^|\\s)(\\S)/u', function ($matches) {
            $first_character = function_exists('mb_strtoupper')
                ? mb_strtoupper($matches[2], 'UTF-8')
                : strtoupper($matches[2]);
            return $matches[1] . $first_character;
        }, $value);
    }

    private function validate_tenant_values($values, $store, $include_code)
    {
        $errors = array();
        if ($include_code && !$this->is_valid_tenant_code($values['tenant_code'])) {
            $errors[] = 'invalid';
        }
        if (!isset($values['tenant_name']) || trim($values['tenant_name']) === '') {
            $errors[] = 'name_required';
        }
        if (!isset($values['email_address']) || !filter_var($values['email_address'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'invalid';
        }
        if (!isset($values['phone_number']) || trim($values['phone_number']) === '') {
            $errors[] = 'invalid';
        }
        if (isset($values['due_flag']) && (int) $values['due_flag'] === 1
            && (!isset($values['due_on']) || trim($values['due_on']) === '')) {
            $errors[] = 'due_on_required';
        }
        if (isset($values['due_on']) && $values['due_on'] !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $values['due_on']);
            if (!$date || $date->format('Y-m-d') !== $values['due_on']) {
                $errors[] = 'invalid';
            }
        }
        $fields = $store->fields();
        foreach ($values as $name => $value) {
            if (!isset($fields[$name])) {
                continue;
            }
            $max = (int) $fields[$name]['max_length'];
            if ($max > 0 && strlen($value) > $max) {
                $errors[] = 'invalid';
                break;
            }
        }
        return array_values(array_unique($errors));
    }

    private function tenant_filter($value)
    {
        return is_string($value) ? substr(trim($value), 0, 120) : '';
    }

    private function tenant_sort_field($value)
    {
        return is_string($value) && in_array($value, array('tenant_code', 'tenant_name'), TRUE)
            ? $value
            : 'tenant_code';
    }

    private function tenant_sort_direction($value)
    {
        return is_string($value) && strtoupper($value) === 'DESC' ? 'DESC' : 'ASC';
    }

    private function tenant_page_size()
    {
        $requested = $this->input->get('per_page', TRUE);
        if (!is_string($requested) || !preg_match('/^\d{1,9}$/', $requested)) {
            return 10;
        }
        $requested = (int) $requested;
        if ($requested > 50) {
            return 50;
        }
        if ($requested <= 10) {
            return 10;
        }
        return $requested <= 25 ? 25 : 50;
    }

    private function tenant_page_number()
    {
        $requested = $this->input->get('page', TRUE);
        if (!is_string($requested) || !preg_match('/^\d{1,10}$/', $requested)) {
            return 1;
        }
        return max(1, (int) $requested);
    }

    private function is_valid_tenant_code($tenant_code)
    {
        return is_string($tenant_code)
            && $tenant_code !== ''
            && strlen($tenant_code) <= 32
            && preg_match('/^[a-z0-9][a-z0-9._-]*$/', $tenant_code);
    }

    private function tenant_deny_access($message_key)
    {
        $this->output->set_status_header(403);
        $this->render_admin(
            'admin/tenant/access_denied',
            'tenant',
            '/admin/system/tenant',
            array('tenant_access_message_key' => $message_key)
        );
    }
}
