<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Tenant-scoped administration for application users. */
class Admin_user extends Admin
{
    private function user_access_denied()
    {
        $this->output->set_status_header(403);
        $this->render_admin('admin/user/access_denied', 'user_heading', '/admin/application/user', array());
    }

    private function require_user_access($access_code)
    {
        if (!$this->has_admin_menu_code('app_user') || !$this->has_admin_access_code($access_code)) {
            $this->user_access_denied();
            return FALSE;
        }
        return TRUE;
    }

    private function user_access_flags()
    {
        return array(
            'user_menu_access' => $this->has_admin_menu_code('app_user'),
            'user_can_add' => $this->has_admin_access_code('app_user_add'),
            'user_can_update' => $this->has_admin_access_code('app_user_update'),
            'user_can_delete' => $this->has_admin_access_code('app_user_delete'),
            'user_can_change_password' => $this->has_admin_access_code('app_user_change_password'),
            'user_can_block' => $this->has_admin_access_code('app_user_block'),
            'user_can_tenant' => $this->has_admin_access_code('app_user_tenant'),
        );
    }

    public function user_index()
    {
        if (!$this->has_admin_menu_code('app_user')) { $this->user_access_denied(); return; }
        $access = $this->user_access_flags();
        $store = $this->user_store();
        $tenant_id = $store ? $this->selected_tenant($store) : FALSE;
        $available = $store !== NULL && $store->schema_available() && $tenant_id !== FALSE;
        $filter_mode = $this->input->get('filter_mode', TRUE) === 'columns' ? 'columns' : 'search';
        $search = $filter_mode === 'search' ? $this->search_value($this->input->get('search', TRUE)) : '';
        $sort = $this->sort_value($this->input->get('sort', TRUE));
        $direction = strtoupper((string) $this->input->get('direction', TRUE)) === 'DESC' ? 'DESC' : 'ASC';
        $role_filter = $filter_mode === 'columns' ? trim((string) $this->input->get('role_id', TRUE)) : '';
        if ($role_filter !== '' && (!$available || !$store->role_belongs_to_tenant($role_filter, $tenant_id))) { $role_filter = ''; }
        $block_filter = $filter_mode === 'columns' ? (string) $this->input->get('block_flag', TRUE) : '';
        if (!in_array($block_filter, array('', '0', '1'), TRUE)) { $block_filter = ''; }
        $per_page = $this->page_size();
        $page = $this->page_number();
        $total = $available ? $store->count($search, $tenant_id, $role_filter, $block_filter) : 0;
        if ($total === FALSE) { $available = FALSE; $total = 0; }
        $page_count = max(1, (int) ceil($total / $per_page));
        $page = min($page, $page_count);
        $records = $available ? $store->page($search, $per_page, ($page - 1) * $per_page, $sort, $direction, $tenant_id, $role_filter, $block_filter) : array();
        if ($records === FALSE) { $available = FALSE; $records = array(); $total = 0; $page = 1; $page_count = 1; }
        $status = $this->session->flashdata('admin_user_status');
        $can_write = $this->user_write_available($store);
        $this->render_admin('admin/user/index', 'user_heading', '/admin/application/user', array_merge(array(
            'user_records' => $records, 'user_search' => $search, 'user_sort' => $sort,
            'user_direction' => $direction, 'user_page' => $page, 'user_page_count' => $page_count,
            'user_total' => $total, 'user_per_page' => $per_page, 'user_per_page_choices' => array(10, 25, 50),
            'user_tenant_id' => $tenant_id === FALSE ? 0 : (int) $tenant_id, 'user_tenants' => is_array($store ? $store->tenant_options() : FALSE) ? $store->tenant_options() : array(),
            'user_roles' => $available ? $store->role_options($tenant_id) : array(),
            'user_filter_role' => $role_filter, 'user_filter_block' => $block_filter, 'user_filter_mode' => $filter_mode,
            'user_backend_available' => $available, 'user_write_available' => $can_write,
            'user_status_key' => is_string($status) ? $status : '',
        ), $access));
    }

    public function user_new()
    {

        if (!$this->require_user_access('app_user_add')) {
            return;
        }
        $store = $this->user_store();
        $available = $store !== NULL && $store->schema_available();
        $write_available = $this->user_write_available($store);
        $tenants = $available ? $store->tenant_options() : FALSE;
        $default_tenant = $available ? $store->tenant_id() : FALSE;
        $record = array('tenant_id' => $default_tenant === FALSE ? '' : (string) $default_tenant, 'user_code' => '', 'username' => '', 'role_id' => '', 'employee_flag' => 0, 'employee_id' => '', 'password' => '', 'access_api_flag' => 0, 'api_secret' => '', 'chat_flag' => 0);
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) { show_error('User writes are unavailable.', 503); return; }
            $record = $this->form_values(TRUE);
            $errors = $this->validate_record($record, $store, TRUE);
            if (!$errors && $store->insert($record)) {
                $this->session->set_flashdata('admin_user_status', 'created');
                redirect('/admin/application/user', 'location', 303); return;
            }
            if (!$errors) { $errors[] = 'save_error'; }
        }
        $roles = $available && $record['tenant_id'] !== '' ? $store->role_options($record['tenant_id']) : array();
        $this->render_user_form($store, $available, $write_available, 'new', NULL, $record, $errors, is_array($tenants) ? $tenants : array(), is_array($roles) ? $roles : array());
    }

    public function user_change($user_id = '')
    {

        if (!$this->require_user_access('app_user_update')) {
            return;
        }
        $store = $this->user_store();
        $tenant_id = $store ? $this->selected_tenant($store) : FALSE;
        $available = $store !== NULL && $store->schema_available() && $tenant_id !== FALSE;
        $write_available = $this->user_write_available($store);
        $record = $available ? $store->find($user_id, $tenant_id) : NULL;
        if ($record === FALSE) { $available = FALSE; $record = NULL; }
        if ($available && $record === NULL) { show_404(); return; }
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) { show_error('User writes are unavailable.', 503); return; }
            $record = array_merge($record, $this->form_values(FALSE));
            $errors = $this->validate_record($record, $store, FALSE, (int) $user_id);
            if (!$errors && $store->update($user_id, $tenant_id, $record)) {
                $this->session->set_flashdata('admin_user_status', 'saved');
                redirect('/admin/application/user', 'location', 303); return;
            }
            if (!$errors) { $errors[] = 'save_error'; }
        }
        $roles = $available ? $store->role_options($tenant_id) : array();
        $this->render_user_form($store, $available, $write_available, 'edit', (int) $user_id, $record ?: array(), $errors, array(), is_array($roles) ? $roles : array());
    }

    public function user_password($user_id = '')
    {

        if (!$this->require_user_access('app_user_change_password')) {
            return;
        }
        $store = $this->user_store();
        $tenant_id = $store ? $this->selected_tenant($store) : FALSE;
        $available = $store !== NULL && $store->schema_available() && $tenant_id !== FALSE;
        $write_available = $this->user_write_available($store);
        $record = $available ? $store->find($user_id, $tenant_id) : NULL;
        if ($record === FALSE) { $available = FALSE; $record = NULL; }
        if ($available && $record === NULL) { show_404(); return; }
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) { show_error('Password changes are unavailable.', 503); return; }
            $password = $this->input->post('password', FALSE);
            $retry = $this->input->post('retry_password', FALSE);
            $password = is_string($password) ? $password : '';
            $retry = is_string($retry) ? $retry : '';
            if ($password === '') { $errors[] = 'password_required'; }
            if ($password !== $retry) { $errors[] = 'password_mismatch'; }
            $specs = $store->field_specs();
            if (!empty($specs['password']['max_length']) && strlen(password_hash($password, PASSWORD_DEFAULT)) > $specs['password']['max_length']) { $errors[] = 'password_too_long'; }
            if (!$errors && $store->change_password($user_id, $tenant_id, $password)) {
                $this->session->set_flashdata('admin_user_status', 'password_changed');
                redirect('/admin/application/user', 'location', 303); return;
            }
            if (!$errors) { $errors[] = 'save_error'; }
        }
        $this->render_admin('admin/user/password', 'user_heading', '/admin/application/user/password/' . (int) $user_id, array(
            'user_record' => $record ?: array(), 'user_backend_available' => $available,
            'user_write_available' => $write_available, 'user_errors' => $errors,
        ));
    }

    public function user_block()
    {

        if (!$this->require_user_access('app_user_block')) {
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') { show_error('User status changes require POST.', 405); return; }
        $store = $this->user_store();
        if (!$this->user_write_available($store)) { show_error('User status changes are unavailable.', 503); return; }
        $tenant_id = $this->selected_tenant($store);
        if ($tenant_id === FALSE) { show_error('User status changes are unavailable.', 503); return; }
        $user_id = $this->input->post('user_id', TRUE);
        $blocked = $this->input->post('block_flag', TRUE);
        if (!in_array((string) $blocked, array('0', '1'), TRUE)) { show_error('Invalid user status.', 400); return; }
        $record = $store->find($user_id, $tenant_id);
        if (!$record) { show_404(); return; }
        if ((int) $record['block_flag'] === (int) $blocked) { show_error('User status has changed; reload the list.', 409); return; }
        if (!$store->set_blocked($user_id, $tenant_id, (int) $blocked)) { show_error('Unable to update user status.', 500); return; }
        $this->session->set_flashdata('admin_user_status', $blocked === '1' ? 'blocked' : 'unblocked');
        redirect('/admin/application/user', 'location', 303);
    }

    public function role_options($tenant_id = '')
    {
        if (!$this->has_admin_menu_code('app_user') || (!$this->has_admin_access_code('app_user_add') && !$this->has_admin_access_code('app_user_update'))) { $this->json_response(array(), 403); return; }
        if (!$this->has_admin_access_code('app_user_tenant')) { $store = $this->user_store(); $tenant_id = $store ? $store->tenant_id() : FALSE; }
        $store = isset($store) ? $store : $this->user_store();
        if (!$store || !$store->schema_available() || !$store->tenant_exists($tenant_id)) { $this->json_response(array(), 400); return; }
        $options = $store->role_options($tenant_id);
        $this->json_response(is_array($options) ? $options : array(), $options === FALSE ? 500 : 200);
    }

    public function employee_lookup()
    {
        if (!$this->has_admin_menu_code('app_user') || (!$this->has_admin_access_code('app_user_add') && !$this->has_admin_access_code('app_user_update'))) { $this->json_response(array(), 403); return; }
        $store = $this->user_store();
        $term = $this->search_value($this->input->get('term', TRUE));
        $tenant_id = $this->has_admin_access_code('app_user_tenant') ? $this->input->get('tenant_id', TRUE) : ($store ? $store->tenant_id() : FALSE);
        if (!$store || !$store->schema_available() || !$store->tenant_exists($tenant_id) || strlen($term) < 2) { $this->json_response(array(), 200); return; }
        $except_user_id = $this->input->get('user_id', TRUE);
        $current = $store->find($except_user_id, $tenant_id);
        $except_user_id = is_array($current) ? (int) $current['user_id'] : NULL;
        $items = $store->employee_options($tenant_id, $term, $except_user_id);
        $this->json_response(is_array($items) ? $items : array(), $items === FALSE ? 500 : 200);
    }

    public function user_delete()
    {

        if (!$this->require_user_access('app_user_delete')) {
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') { show_error('Method not allowed.', 405); return; }
        $store = $this->user_store();
        $tenant_id = $store ? $this->selected_tenant($store) : FALSE;
        if (!$this->user_write_available($store) || $tenant_id === FALSE) { show_error('User writes are unavailable.', 503); return; }
        $deleted = $store->delete($this->input->post('user_id'), $tenant_id);
        redirect('/admin/application/user?tenant_id=' . (int) $tenant_id . '&status=' . ($deleted ? 'deleted' : 'error'));
    }

    public function api_secret()
    {
        if (!$this->require_user_access('app_user_update')) { return; }
        if ($this->input->method(TRUE) !== 'POST') { show_error('Method not allowed.', 405); return; }
        $store = $this->user_store();
        $tenant_id = $store ? $this->selected_tenant($store) : FALSE;
        if (!$this->user_write_available($store) || $tenant_id === FALSE) { show_error('User access is unavailable.', 503); return; }
        $secret = $store->api_secret($this->input->post('user_id'), $tenant_id);
        if ($secret === FALSE) { show_error('Secret unavailable.', 404); return; }
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')->set_header('Pragma: no-cache')->set_content_type('application/json')->set_output(json_encode(array('secret' => $secret, 'csrf' => array('name' => $this->security->get_csrf_token_name(), 'hash' => $this->security->get_csrf_hash()))));
    }

    private function selected_tenant($store)
    {
        if (!$this->has_admin_access_code('app_user_tenant')) { return $store->tenant_id(); }
        $candidate = $this->input->post('tenant_id', TRUE);
        if ($candidate === NULL || $candidate === '') { $candidate = $this->input->get('tenant_id', TRUE); }
        if ($candidate !== NULL && $candidate !== '' && $store->tenant_exists($candidate)) { return (int) $candidate; }
        return $store->tenant_id();
    }

    private function user_store()
    {
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') { return NULL; }
        $this->load->database(); $this->db->db_debug = FALSE;
        $this->load->library('user_store');
        return $this->user_store;
    }

    private function user_write_available($store)
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development' && $this->current_user !== NULL
            && (bool) config_item('csrf_protection') && $store !== NULL && $store->schema_available();
    }

    private function render_user_form($store, $available, $write_available, $mode, $user_id, $record, $errors, $tenants, $roles)
    {
        $this->render_admin('admin/user/form', 'user_heading', $mode === 'new' ? '/admin/application/user/new' : '/admin/application/user/change/' . (int) $user_id, array(
            'user_mode' => $mode, 'user_id' => $user_id, 'user_record' => $record, 'user_errors' => $errors,
            'user_field_specs' => $available ? $store->field_specs() : array(), 'user_tenants' => $tenants,
            'user_roles' => $roles, 'user_backend_available' => $available, 'user_write_available' => $write_available,
            'user_has_chat_flag' => $available && $store->has_chat_flag(),
        ) + $this->user_access_flags());
    }

    private function form_values($include_identity)
    {
        $values = array();
        if ($include_identity) {
            $posted_tenant = trim((string) $this->input->post('tenant_id', TRUE));
            if (!$this->has_admin_access_code('app_user_tenant')) {
                $store = $this->user_store();
                $posted_tenant = $store ? $store->tenant_id() : FALSE;
            }
            $values['tenant_id'] = $posted_tenant === FALSE ? '' : trim((string) $posted_tenant);
            $values['user_code'] = trim((string) $this->input->post('user_code', TRUE));
            $values['password'] = (string) $this->input->post('password', FALSE);
        }
        $values['username'] = preg_replace('/\s+/u', '', strtolower(trim((string) $this->input->post('username', TRUE))));
        $values['role_id'] = trim((string) $this->input->post('role_id', TRUE));
        $values['employee_flag'] = $this->input->post('employee_flag', TRUE) === '1' ? 1 : 0;
        $values['employee_id'] = trim((string) $this->input->post('employee_id', TRUE));
        $values['full_name'] = trim((string) $this->input->post('full_name', TRUE));
        $values['access_api_flag'] = $this->input->post('access_api_flag', TRUE) === '1' ? 1 : 0;
        $values['chat_flag'] = $this->input->post('chat_flag', TRUE) === '1' ? 1 : 0;
        $values['api_secret'] = trim((string) $this->input->post('api_secret', TRUE));
        return $values;
    }

    private function validate_record(&$record, $store, $include_identity, $except_id = NULL)
    {
        $errors = array(); $specs = $store->field_specs();
        if ($include_identity) {
            if (!$store->tenant_exists($record['tenant_id'])) { $errors[] = 'tenant_invalid'; }
            foreach (array('user_code', 'username') as $field) {
                if ($record[$field] === '') { $errors[] = $field . '_required'; }
                elseif (!empty($specs[$field]['max_length']) && strlen($record[$field]) > $specs[$field]['max_length']) { $errors[] = $field . '_too_long'; }
            }
            $duplicate = $store->user_code_exists($record['user_code'], $record['tenant_id']);
            if ($duplicate === TRUE) { $errors[] = 'user_code_duplicate'; } elseif ($duplicate === NULL) { $errors[] = 'save_error'; }
            $duplicate = $store->username_exists($record['username']);
            if ($duplicate === TRUE) { $errors[] = 'username_duplicate'; } elseif ($duplicate === NULL) { $errors[] = 'save_error'; }
            if (!is_string($record['password']) || $record['password'] === '') { $errors[] = 'password_required'; }
            elseif (!empty($specs['password']['max_length']) && strlen(password_hash($record['password'], PASSWORD_DEFAULT)) > $specs['password']['max_length']) { $errors[] = 'password_too_long'; }
        } else {
            if ($record['username'] === '') { $errors[] = 'username_required'; }
            elseif (!empty($specs['username']['max_length']) && strlen($record['username']) > $specs['username']['max_length']) { $errors[] = 'username_too_long'; }
            $duplicate = $store->username_exists($record['username'], $except_id);
            if ($duplicate === TRUE) { $errors[] = 'username_duplicate'; } elseif ($duplicate === NULL) { $errors[] = 'save_error'; }
        }
        if (!$store->role_belongs_to_tenant($record['role_id'], $record['tenant_id'])) { $errors[] = 'role_invalid'; }
        if ((int) $record['employee_flag'] === 1) {
            $employee_name = $store->employee_name_for($record['employee_id'], $record['tenant_id']);
            if ($employee_name === FALSE) { $errors[] = 'employee_invalid'; }
            else { $record['full_name'] = $employee_name; $record['employee_name'] = $employee_name; }
        } else {
            $record['employee_id'] = '';
            $record['full_name'] = trim((string) $record['full_name']);
            if ($record['full_name'] === '') { $errors[] = 'full_name_required'; }
            elseif (!empty($specs['full_name']['max_length']) && strlen($record['full_name']) > $specs['full_name']['max_length']) { $errors[] = 'full_name_too_long'; }
        }
        if ((int) $record['access_api_flag'] === 1) {
            $record['api_secret'] = trim((string) $record['api_secret']);
            if ($record['api_secret'] === '') { $errors[] = 'api_secret_required'; }
            elseif (!empty($specs['api_secret']['max_length']) && strlen($record['api_secret']) > $specs['api_secret']['max_length']) { $errors[] = 'api_secret_too_long'; }
        } else {
            $record['access_api_flag'] = 0;
            $record['api_secret'] = '';
        }
        return array_values(array_unique($errors));
    }

    private function json_response($payload, $status)
    {
        $this->output->set_status_header((int) $status)->set_content_type('application/json', 'utf-8')->set_output(json_encode($payload));
    }
    private function search_value($value) { return is_string($value) ? trim(substr($value, 0, 100)) : ''; }
    private function sort_value($value) { return in_array($value, array('user_code', 'user_name', 'full_name', 'role_name', 'employee_name', 'block_flag'), TRUE) ? $value : 'user_code'; }
    private function page_size() { $size = (int) $this->input->get('per_page', TRUE); return in_array($size, array(10, 25, 50), TRUE) ? $size : 10; }
    private function page_number() { return max(1, (int) $this->input->get('page', TRUE)); }
}
