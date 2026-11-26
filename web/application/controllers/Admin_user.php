<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Tenant-scoped administration for application users. */
class Admin_user extends Admin
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('finance_filter_panel');
        $this->load->library('finance_list_table');
    }

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
            'finance_filter_config' => $this->user_filter_config($store, $tenant_id, !empty($access['user_can_tenant'])),
        ), $access));
    }

    public function user_data()
    {
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code('app_user')) {
            $this->datatable_server->error(403);
            return;
        }
        $store = $this->user_store();
        if ($store === NULL || !$store->schema_available()) {
            $this->datatable_server->error(503);
            return;
        }
        $state = $this->user_list_state('');
        $this->datatable_server->respond(
            array(1 => 'tenant_id', 2 => 'user_code', 3 => 'user_name', 4 => 'full_name', 5 => 'role_name', 6 => 'employee_name', 7 => 'block_flag'),
            function () use ($store, $state) {
                return $store->count($state['search'], $state['tenant'], '', '', $state['filters']);
            },
            function ($search) use ($store) {
                $state = $this->user_list_state($search);
                return $store->count($state['search'], $state['tenant'], '', '', $state['filters']);
            },
            function ($search, $length, $start, $order, $direction) use ($store) {
                $state = $this->user_list_state($search);
                return $store->page($state['search'], $length, $start, $order, $direction, $state['tenant'], '', '', $state['filters']);
            },
            array('max_length' => 200, 'max_search_length' => 120, 'default_order' => 'user_code')
        );
    }

    private function user_filter_config($store = NULL, $tenant_id = FALSE, $tenant_access = FALSE)
    {
        $labels = $this->lang->language;
        $tenants = array();
        $roles = array();
        $tenant_options = $tenant_access && $store ? $store->tenant_options() : FALSE;
        if (is_array($tenant_options)) {
            foreach ($tenant_options as $tenant) {
                $tenants[(string) $tenant['tenant_id']] = $tenant['tenant_code'] . ' — ' . $tenant['tenant_name'];
            }
        }
        $role_options = $store && $tenant_id !== FALSE ? $store->role_options($tenant_id) : FALSE;
        if (is_array($role_options)) {
            foreach ($role_options as $role) {
                $roles[(string) $role['role_id']] = $role['role_name'];
            }
        }
        $field_filters = array();
        if ($tenant_access) {
            $field_filters[] = array('type' => 'select', 'name' => 'tenant_id', 'label' => isset($labels['user_tenant']) ? $labels['user_tenant'] : 'Tenant', 'options' => $tenants);
        }
        $field_filters[] = array('type' => 'text', 'name' => 'user_code', 'label' => isset($labels['user_code']) ? $labels['user_code'] : 'User code');
        $field_filters[] = array('type' => 'text', 'name' => 'username', 'label' => isset($labels['user_name']) ? $labels['user_name'] : 'Username');
        $field_filters[] = array('type' => 'text', 'name' => 'full_name', 'label' => isset($labels['user_full_name']) ? $labels['user_full_name'] : 'Full name');
        $field_filters[] = array('type' => 'select', 'name' => 'role_id', 'label' => isset($labels['user_role']) ? $labels['user_role'] : 'Role', 'options' => $roles);
        $field_filters[] = array('type' => 'text', 'name' => 'employee_name', 'label' => isset($labels['user_employee']) ? $labels['user_employee'] : 'Employee');
        $field_filters[] = array('type' => 'select', 'name' => 'block_flag', 'label' => isset($labels['user_block_status']) ? $labels['user_block_status'] : 'Blocked', 'options' => array(
            '0' => isset($labels['user_no']) ? $labels['user_no'] : 'No',
            '1' => isset($labels['user_yes']) ? $labels['user_yes'] : 'Yes',
        ));
        $field_filters[] = array('type' => 'select', 'name' => 'api_secret', 'label' => isset($labels['user_api_secret']) ? $labels['user_api_secret'] : 'API secret', 'options' => array(
            '1' => isset($labels['user_yes']) ? $labels['user_yes'] : 'Yes',
            '0' => isset($labels['user_no']) ? $labels['user_no'] : 'No',
        ));
        return $this->finance_filter_panel->config(array(
            'date' => FALSE,
            'date_preset' => 'none',
            'status_preset' => 'none',
            'target' => '#user-data-table',
            'field_filters' => $field_filters,
        ));
    }

    private function user_list_state($search = '')
    {
        $store = $this->user_store();
        $tenant = $store ? $this->selected_tenant($store) : FALSE;
        $tenant_access = $this->has_admin_access_code('app_user_tenant');
        $panel = $this->finance_filter_panel->filters($this->user_filter_config($store, $tenant, $tenant_access));
        $fields = $panel['fields'];
        if ($tenant_access && isset($fields['tenant_id']) && $fields['tenant_id'] !== ''
            && $store && $store->tenant_exists($fields['tenant_id'])) {
            $tenant = (int) $fields['tenant_id'];
        }
        $role_id = isset($fields['role_id']) ? (string) $fields['role_id'] : '';
        if ($role_id !== '' && (!$store || $tenant === FALSE || !$store->role_belongs_to_tenant($role_id, $tenant))) {
            $role_id = '';
        }
        $filters = array(
            'user_code' => isset($fields['user_code']) ? $fields['user_code'] : '',
            'username' => isset($fields['username']) ? $fields['username'] : '',
            'full_name' => isset($fields['full_name']) ? $fields['full_name'] : '',
            'role_id' => $role_id,
            'employee_name' => isset($fields['employee_name']) ? $fields['employee_name'] : '',
            'block_flag' => isset($fields['block_flag']) ? $fields['block_flag'] : '',
            'api_secret' => isset($fields['api_secret']) ? $fields['api_secret'] : '',
        );
        return array('search' => $this->search_value($search), 'tenant' => $tenant, 'filters' => $filters);
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
        $default_tenant = $available ? $this->selected_tenant($store) : FALSE;
        $record = array('tenant_id' => $default_tenant === FALSE ? '' : (string) $default_tenant, 'user_code' => '', 'username' => '', 'role_id' => '', 'employee_flag' => 0, 'employee_id' => '', 'password' => '', 'access_api_flag' => 0, 'api_secret' => '', 'chat_flag' => 0);
        $errors = array();
        $sequence_state = $available && $default_tenant !== FALSE
            ? $this->user_code_sequence_state($default_tenant)
            : array('valid' => FALSE, 'automatic' => FALSE, 'preview' => '');
        if (!empty($sequence_state['automatic'])) {
            $record['user_code'] = (string) $sequence_state['preview'];
        }
        if ($available && $default_tenant !== FALSE && empty($sequence_state['valid'])) {
            $errors[] = 'sequence_generation';
        }
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) { show_error('User writes are unavailable.', 503); return; }
            $record = $this->form_values(TRUE);
            $sequence_state = $this->user_code_sequence_state($record['tenant_id']);
            $automatic_user_code = !empty($sequence_state['automatic']);
            if (!$automatic_user_code || empty($sequence_state['valid'])) {
                $record['user_code'] = $automatic_user_code ? (string) $sequence_state['preview'] : $record['user_code'];
            } else {
                $record['user_code'] = '';
            }
            $errors = empty($sequence_state['valid']) ? array('sequence_generation') : array();
            $errors = array_merge($errors, $this->validate_record($record, $store, TRUE, NULL, $automatic_user_code));
            if (!$errors && $automatic_user_code) {
                $this->db->db_debug = FALSE;
                $this->db->trans_begin();
                $next = $this->application_sequence_store->next_code_for_update((int) $record['tenant_id'], 'user_code');
                $code_available = FALSE;
                $specs = $store->field_specs();
                for ($attempt = 0; is_array($next) && $attempt < 1000; $attempt++) {
                    $record['user_code'] = $next['code'];
                    $max_code_length = isset($specs['user_code']['max_length']) ? (int) $specs['user_code']['max_length'] : 0;
                    $code_length = function_exists('mb_strlen') ? mb_strlen($record['user_code'], 'UTF-8') : strlen($record['user_code']);
                    if ($max_code_length > 0 && $code_length > $max_code_length) {
                        $errors[] = 'user_code_too_long';
                        break;
                    }
                    $duplicate = $store->user_code_exists($record['user_code'], $record['tenant_id']);
                    if ($duplicate === NULL) {
                        $errors[] = 'sequence_generation';
                        break;
                    }
                    if (!$duplicate) {
                        $code_available = TRUE;
                        break;
                    }
                    $next = $this->application_sequence_store->next_code_after($next);
                }
                if (!$errors && !$code_available) {
                    $errors[] = 'sequence_exhausted';
                }
                if (!$errors) {
                    $inserted = $store->insert($record);
                    $advanced = $inserted && $this->application_sequence_store->advance_sequence(
                        (int) $record['tenant_id'],
                        'user_code',
                        $next
                    );
                    if ($inserted && $advanced && $this->db->trans_status() !== FALSE) {
                        $this->db->trans_commit();
                    } else {
                        $this->db->trans_rollback();
                        $errors[] = 'save_error';
                    }
                } else {
                    $this->db->trans_rollback();
                }
            } elseif (!$errors) {
                if ($store->insert($record)) {
                    $this->session->set_flashdata('admin_user_status', 'created');
                    redirect('/admin/application/user', 'location', 303); return;
                }
                $errors[] = 'save_error';
            }
            if (!$errors && $automatic_user_code) {
                $this->session->set_flashdata('admin_user_status', 'created');
                redirect('/admin/application/user', 'location', 303); return;
            }
        }
        $roles = $available && $record['tenant_id'] !== '' ? $store->role_options($record['tenant_id']) : array();
        $this->render_user_form($store, $available, $write_available, 'new', NULL, $record, $errors, is_array($tenants) ? $tenants : array(), is_array($roles) ? $roles : array(), !empty($sequence_state['automatic']), isset($sequence_state['preview']) ? $sequence_state['preview'] : '');
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

    /** Check whether a selected tenant uses automatic user_code numbering. */
    public function user_sequence_config($tenant_id = '')
    {
        if (!$this->has_admin_menu_code('app_user') || !$this->has_admin_access_code('app_user_add')) {
            $this->json_response(array(), 403);
            return;
        }
        if ($this->input->method(TRUE) !== 'GET') {
            $this->json_response(array(), 405);
            return;
        }
        $store = $this->user_store();
        if (!$store || !$store->schema_available()) {
            $this->json_response(array(), 503);
            return;
        }
        if (!$this->has_admin_access_code('app_user_tenant')) {
            $tenant_id = $store->tenant_id();
        }
        if (!$store->tenant_exists($tenant_id)) {
            $this->json_response(array(), 400);
            return;
        }
        $state = $this->user_code_sequence_state((int) $tenant_id);
        if (empty($state['valid'])) {
            $this->json_response(array(), 503);
            return;
        }
        $this->json_response(array(
            'automatic' => !empty($state['automatic']),
            'preview' => isset($state['preview']) ? (string) $state['preview'] : '',
        ), 200);
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

    private function render_user_form($store, $available, $write_available, $mode, $user_id, $record, $errors, $tenants, $roles, $auto_user_code = FALSE, $user_code_preview = '')
    {
        $this->render_admin('admin/user/form', 'user_heading', $mode === 'new' ? '/admin/application/user/new' : '/admin/application/user/change/' . (int) $user_id, array(
            'user_mode' => $mode, 'user_id' => $user_id, 'user_record' => $record, 'user_errors' => $errors,
            'user_field_specs' => $available ? $store->field_specs() : array(), 'user_tenants' => $tenants,
            'user_roles' => $roles, 'user_backend_available' => $available, 'user_write_available' => $write_available,
            'user_code_auto_numbered' => $mode === 'new' && (bool) $auto_user_code,
            'user_code_preview' => (string) $user_code_preview,
            'user_has_chat_flag' => $available && $store->has_chat_flag(),
        ) + $this->user_access_flags());
    }

    /** Return tenant-scoped user_code generation state for initial render and tenant switching. */
    private function user_code_sequence_state($tenant_id)
    {
        if (!is_numeric($tenant_id) || (int) $tenant_id < 1) {
            return array('valid' => FALSE, 'automatic' => FALSE, 'preview' => '');
        }
        $store = $this->user_store();
        if (!$store || !$store->tenant_exists($tenant_id)) {
            return array('valid' => FALSE, 'automatic' => FALSE, 'preview' => '');
        }
        $this->load->library('application_sequence_store');
        $sequence = $this->application_sequence_store->sequence_config((int) $tenant_id, 'user_code');
        if ($sequence === FALSE) {
            return array('valid' => FALSE, 'automatic' => FALSE, 'preview' => '');
        }
        if ($sequence === NULL || (string) $sequence['use_flag'] !== '1') {
            return array('valid' => TRUE, 'automatic' => FALSE, 'preview' => '');
        }
        $preview = $this->application_sequence_store->preview_code((int) $tenant_id, 'user_code');
        if (!is_array($preview)) {
            return array('valid' => FALSE, 'automatic' => TRUE, 'preview' => '');
        }
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $duplicate = $store->user_code_exists($preview['code'], (int) $tenant_id);
            if ($duplicate === NULL) {
                return array('valid' => FALSE, 'automatic' => TRUE, 'preview' => '');
            }
            if (!$duplicate) {
                return array('valid' => TRUE, 'automatic' => TRUE, 'preview' => $preview['code']);
            }
            $preview = $this->application_sequence_store->next_code_after($preview);
            if (!is_array($preview)) {
                break;
            }
        }
        if (!is_array($preview)) {
            return array('valid' => FALSE, 'automatic' => TRUE, 'preview' => '');
        }
        return array('valid' => FALSE, 'automatic' => TRUE, 'preview' => '');
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

    private function validate_record(&$record, $store, $include_identity, $except_id = NULL, $auto_user_code = FALSE)
    {
        $errors = array(); $specs = $store->field_specs();
        if ($include_identity) {
            if (!$store->tenant_exists($record['tenant_id'])) { $errors[] = 'tenant_invalid'; }
            foreach (array('user_code', 'username') as $field) {
                if ($field === 'user_code' && $auto_user_code) { continue; }
                if ($record[$field] === '') { $errors[] = $field . '_required'; }
                elseif (!empty($specs[$field]['max_length']) && strlen($record[$field]) > $specs[$field]['max_length']) { $errors[] = $field . '_too_long'; }
            }
            if (!$auto_user_code) {
                $duplicate = $store->user_code_exists($record['user_code'], $record['tenant_id']);
                if ($duplicate === TRUE) { $errors[] = 'user_code_duplicate'; } elseif ($duplicate === NULL) { $errors[] = 'save_error'; }
            }
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
