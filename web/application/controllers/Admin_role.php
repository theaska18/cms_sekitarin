<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Authenticated CRUD pages for tenant-scoped application roles. */
class Admin_role extends Admin
{
    const ADMIN_ROLE_PATH = '/admin/application/role';

    private function require_role_access($access_code = NULL, $message_key = 'role_no_access')
    {
        if (!$this->has_admin_menu_code('app_role')) {
            $this->role_deny_access('role_no_access');
            return FALSE;
        }
        if ($access_code !== NULL && !$this->has_admin_access_code($access_code)) {
            $this->role_deny_access($message_key);
            return FALSE;
        }
        return TRUE;
    }

    private function role_deny_access($message_key = 'role_no_access')
    {
        $this->output->set_status_header(403);
        $this->render_admin(
            'admin/role/access_denied',
            'role',
            self::ADMIN_ROLE_PATH,
            array('role_access_message_key' => $message_key)
        );
    }

    public function role_index()
    {
        if (!$this->require_role_access()) {
            return;
        }
        $tenant_access = $this->has_admin_access_code('app_role_tenant');
        $store = $this->role_store();
        $filter_mode = $this->input->get('filter_mode', TRUE) === 'columns' ? 'columns' : 'search';
        $default_tenant_id = $store ? $store->tenant_id() : FALSE;
        $tenant_options = $tenant_access && $store ? $store->tenant_options() : array();
        $selected_tenant_id = $default_tenant_id;
        if ($filter_mode === 'columns') {
            $requested_tenant = $this->input->get('tenant_id', TRUE);
            if ($tenant_access && $store && $store->tenant_exists($requested_tenant)) {
                $selected_tenant_id = (int) $requested_tenant;
            }
        }
        $filters = array();
        foreach (array('role_code', 'role_name', 'description') as $filter_name) {
            $filters[$filter_name] = $filter_mode === 'columns'
                ? $this->role_search($this->input->get($filter_name, TRUE)) : '';
        }
        $active_filter = $filter_mode === 'columns' ? $this->input->get('active_flag', TRUE) : NULL;
        $filters['active_flag'] = in_array((string) $active_filter, array('0', '1'), TRUE) ? (string) $active_filter : '';
        $search = $filter_mode === 'search' ? $this->role_search($this->input->get('search', TRUE)) : '';
        $sort = $this->role_sort($this->input->get('sort', TRUE));
        $direction = strtoupper((string) $this->input->get('direction', TRUE)) === 'DESC' ? 'DESC' : 'ASC';
        $per_page = $this->role_page_size();
        $page = $this->role_page_number();
        $available = $store !== NULL && $store->schema_available() && is_array($tenant_options)
            && $selected_tenant_id !== FALSE;
        $total = $available ? $store->count($search, $selected_tenant_id, $filters) : 0;
        if ($total === FALSE) {
            $available = FALSE;
            $total = 0;
        }
        $page_count = max(1, (int) ceil($total / $per_page));
        $page = min($page, $page_count);
        $records = $available ? $store->page($search, $per_page, ($page - 1) * $per_page, $sort, $direction, $selected_tenant_id, $filters) : array();
        if ($records === FALSE) {
            $available = FALSE;
            $records = array();
            $total = 0;
            $page_count = 1;
            $page = 1;
        }
        $status = $this->session->flashdata('admin_role_status');
        $this->render_admin('admin/role/index', 'role', '/admin/application/role', array(
            'role_records' => $records,
            'role_search' => $search,
            'role_filter_mode' => $filter_mode,
            'role_filter_tenant' => (int) $selected_tenant_id,
            'role_filters' => $filters,
            'role_tenants' => is_array($tenant_options) ? $tenant_options : array(),
            'role_selected_tenant' => (int) $selected_tenant_id,
            'role_default_tenant' => (int) $default_tenant_id,
            'role_sort' => $sort,
            'role_direction' => $direction,
            'role_page' => $page,
            'role_page_count' => $page_count,
            'role_total' => $total,
            'role_per_page' => $per_page,
            'role_per_page_choices' => array(10, 25, 50),
            'role_backend_available' => $available,
            'role_write_available' => $this->role_write_available($store),
            'role_create_allowed' => $this->has_admin_access_code('app_role_add'),
            'role_create_available' => $this->has_admin_access_code('app_role_add') && $this->role_write_available($store)
                && (int) $selected_tenant_id === (int) $default_tenant_id,
            'role_update_available' => $this->has_admin_access_code('app_role_update') && $this->role_write_available($store),
            'role_delete_available' => $this->has_admin_access_code('app_role_delete') && $this->role_write_available($store),
            'role_menu_access_available' => $this->has_admin_access_code('app_role_menu_access') && $this->role_write_available($store),
            'role_has_actions' => $this->role_write_available($store) && (
                $this->has_admin_access_code('app_role_update')
                || $this->has_admin_access_code('app_role_delete')
                || $this->has_admin_access_code('app_role_menu_access')
            ),
            'role_tenant_access' => $tenant_access,
            'role_status_key' => is_string($status) ? $status : '',
        ));
    }

    public function role_new()
    {
        if (!$this->require_role_access('app_role_add', 'role_add_denied')) {
            return;
        }
        $tenant_access = $this->has_admin_access_code('app_role_tenant');
        $store = $this->role_store();
        $available = $store !== NULL && $store->schema_available();
        $write_available = $this->role_write_available($store);
        $default_tenant_id = $store ? $store->tenant_id() : FALSE;
        $record = array(
            'tenant_id' => $default_tenant_id !== FALSE ? (string) $default_tenant_id : '',
            'role_code' => '', 'role_name' => '', 'description' => '', 'active_flag' => 1,
        );
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Role writes are unavailable.', 503);
                return;
            }
            $record = $this->role_form_values(TRUE);
            if (!$tenant_access) {
                $record['tenant_id'] = (string) $default_tenant_id;
            }
            $errors = $this->validate_role($record, $store, TRUE);
            if (!$errors && $store->insert($record)) {
                $this->session->set_flashdata('admin_role_status', 'created');
                redirect('/admin/application/role', 'location', 303);
                return;
            }
            if (!$errors) {
                $errors[] = 'save_error';
            }
        }
        $this->render_role_form($store, $available, $write_available, 'new', '', $record, $errors);
    }

    public function role_change($role_code = '', $tenant_id = NULL)
    {
        if (!$this->require_role_access('app_role_update', 'role_update_denied')) {
            return;
        }
        if (!$this->has_admin_access_code('app_role_tenant')) {
            $tenant_id = (int) $this->current_user['tenant_id'];
        }
        $role_code = $this->role_code($role_code);
        if ($tenant_id !== NULL && (!is_numeric($tenant_id) || (string) (int) $tenant_id !== (string) $tenant_id || (int) $tenant_id < 1)) {
            show_404();
            return;
        }
        $store = $this->role_store();
        $available = $store !== NULL && $store->schema_available();
        $write_available = $this->role_write_available($store);
        $record = $available ? $store->find($role_code, $tenant_id) : NULL;
        if ($record === FALSE) {
            $available = FALSE;
            $record = NULL;
        }
        if ($available && $record === NULL) {
            show_404();
            return;
        }
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Role writes are unavailable.', 503);
                return;
            }
            $record = array_merge($record, $this->role_form_values(FALSE));
            $errors = $this->validate_role($record, $store, FALSE, $role_code);
            if (!$errors && $store->update($role_code, $record, $record['tenant_id'])) {
                $this->session->set_flashdata('admin_role_status', 'saved');
                redirect('/admin/application/role', 'location', 303);
                return;
            }
            if (!$errors) {
                $errors[] = 'save_error';
            }
        }
        $this->render_role_form($store, $available, $write_available, 'edit', $role_code, $record ?: array(), $errors);
    }

    public function role_delete()
    {
        if (!$this->require_role_access('app_role_delete', 'role_delete_denied')) {
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Role deletion requires POST.', 405);
            return;
        }
        $store = $this->role_store();
        if (!$this->role_write_available($store)) {
            show_error('Role writes are unavailable.', 503);
            return;
        }
        $role_code = $this->role_code($this->input->post('role_code', TRUE));
        $tenant_id = $this->input->post('tenant_id', TRUE);
        if (!$this->has_admin_access_code('app_role_tenant')) {
            $tenant_id = (int) $this->current_user['tenant_id'];
        }
        $role = $store->find($role_code, $tenant_id);
        if ($role_code === '' || !is_array($role) || !$store->delete($role_code, $tenant_id)) {
            $this->session->set_flashdata('admin_role_status', 'delete_error');
        } else {
            $this->session->set_flashdata('admin_role_status', 'deleted');
        }
        redirect('/admin/application/role', 'location', 303);
    }

    private function render_role_form($store, $available, $write_available, $mode, $role_code, $record, $errors)
    {
        $tenant_access = $this->has_admin_access_code('app_role_tenant');
        $status = $this->session->flashdata('admin_role_status');
        $role_tenants = $mode === 'new' && $tenant_access && $store ? $store->tenant_options() : array();
        if ($mode === 'new' && $tenant_access && !is_array($role_tenants)) {
            $role_tenants = array();
            $available = FALSE;
            $write_available = FALSE;
        }
        $role_tenant_default_available = FALSE;
        if ($mode === 'new') {
            if (!$tenant_access && $store && isset($record['tenant_id'])) {
                $role_tenant_default_available = $store->tenant_exists($record['tenant_id']);
            } else {
                foreach ($role_tenants as $tenant) {
                    if (isset($tenant['tenant_id'], $record['tenant_id'])
                        && (string) $tenant['tenant_id'] === (string) $record['tenant_id']) {
                        $role_tenant_default_available = TRUE;
                        break;
                    }
                }
            }
            if (!$role_tenant_default_available) {
                $write_available = FALSE;
            }
        }
        $role_form_action = '/admin/application/role/' . ($mode === 'new' ? 'new' : 'change/' . rawurlencode($role_code) . '/' . rawurlencode((string) $record['tenant_id']));
        $this->render_admin('admin/role/form', 'role', $role_form_action, array(
            'role_mode' => $mode,
            'role_code' => $role_code,
            'role_record' => $record,
            'role_tenants' => $role_tenants,
            'role_tenant_default_available' => $role_tenant_default_available,
            'role_field_specs' => $store ? $store->field_specs() : array(),
            'role_errors' => $errors,
            'role_backend_available' => $available,
            'role_write_available' => $write_available,
            'role_tenant_access' => $tenant_access,
            'role_status_key' => is_string($status) ? $status : '',
        ));
    }

    public function role_menu($role_code, $tenant_id = NULL)
    {
        if (!$this->require_role_access('app_role_menu_access', 'role_menu_access_denied')) {
            return;
        }
        if (!$this->has_admin_access_code('app_role_tenant')) {
            $tenant_id = (int) $this->current_user['tenant_id'];
        }
        $role_code = $this->role_code($role_code);
        if ($tenant_id !== NULL && (!is_numeric($tenant_id) || (string) (int) $tenant_id !== (string) $tenant_id || (int) $tenant_id < 1)) {
            show_404();
            return;
        }
        if ($role_code === '' || !preg_match('/^[a-zA-Z0-9._-]+$/', $role_code)) {
            show_404();
            return;
        }

        $role_store = $this->role_store();
        if ($role_store === NULL || !$role_store->schema_available()) {
            show_error('Role data is currently unavailable.', 503);
            return;
        }
        $role = $role_store->find($role_code, $tenant_id);
        if ($role === FALSE || $role === NULL || !is_array($role)) {
            show_404();
            return;
        }

        $this->load->library('role_menu_access_store');
        $role_menu_store = $this->role_menu_access_store;
        $role_state = $role_menu_store->state($role['role_id']);
        $catalog = $this->role_menu_allowed_catalog($role);
        $groups = $catalog === FALSE ? array() : $catalog[0];
        $allowed_state = $catalog === FALSE ? array() : $catalog[1];

        $write_available = $this->role_write_available($role_store)
            && $role_state !== FALSE && $catalog !== FALSE
            && $role_menu_store->schema_available() && !empty($allowed_state);
        $status = $this->session->flashdata('admin_role_status');
        $error = '';
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Role menu changes are unavailable.', 503);
                return;
            }
            $posted_menus = $this->input->post('menu_codes');
            $posted_accesses = $this->input->post('access_codes');
            $posted_menus = is_array($posted_menus) ? $posted_menus : array();
            $posted_accesses = is_array($posted_accesses) ? $posted_accesses : array();
            if ($role_menu_store->save($role['role_id'], $posted_menus, $posted_accesses, $allowed_state)) {
                $this->session->set_flashdata('admin_role_status', 'menu_saved');
                redirect('/admin/application/role/menu/' . rawurlencode((string) $role['role_code']) . '/' . (int) $role['tenant_id']);
                return;
            }
            $error = 'menu_save_error';
            $role_state = array();
            foreach ($posted_menus as $menu_code) {
                if (is_string($menu_code) && isset($allowed_state[$menu_code])) {
                    $role_state[$menu_code] = array();
                    $posted = isset($posted_accesses[$menu_code]) && is_array($posted_accesses[$menu_code])
                        ? $posted_accesses[$menu_code] : array();
                    foreach ($posted as $access_code) {
                        if (is_string($access_code) && isset($allowed_state[$menu_code][$access_code])) {
                            $role_state[$menu_code][$access_code] = TRUE;
                        }
                    }
                }
            }
        }

        $this->load->library('menu_access_catalog');
        $role_menu_rows = $this->menu_access_catalog->rows(
            $groups,
            is_array($role_state) ? $role_state : array(),
            $allowed_state
        );
        $role_menu_base = '/admin/application/role/menu/' . rawurlencode((string) $role['role_code']) . '/' . (int) $role['tenant_id'];

        $this->render_admin('admin/role/menu', 'role', $role_menu_base, array(
            'role_record' => $role,
            'role_menu_groups' => $groups,
            'role_menu_selected' => is_array($role_state) ? $role_state : array(),
            'role_menu_label' => $this->lang->line('admin_menu'),
            'role_menu_backend_available' => $role_state !== FALSE && $catalog !== FALSE,
            'role_menu_write_available' => $write_available,
            'role_menu_error_key' => $error,
            'role_menu_status_key' => $status === 'menu_saved' ? 'menu_saved' : '',
            'menu_access_rows' => $role_menu_rows,
            'menu_access_table_id' => 'role-menu-table',
            'menu_access_datatable_url' => $role_menu_base . '/data',
            'menu_access_toggle_url' => $role_menu_base . '/toggle',
            'menu_access_write_available' => $write_available,
        ));
    }

    /** DataTables server-side endpoint for one role's tenant-scoped menu catalog. */
    public function role_menu_data($role_code, $tenant_id = NULL)
    {
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code('app_role') || !$this->has_admin_access_code('app_role_menu_access')) {
            $this->datatable_server->error(403);
            return;
        }
        $role = $this->role_menu_record($role_code, $tenant_id);
        if ($role === NULL) {
            $this->datatable_server->error(404);
            return;
        }
        $role_store = $this->role_store();
        if ($role_store === NULL || !$role_store->schema_available()) {
            $this->datatable_server->error(503);
            return;
        }
        $this->load->library('role_menu_access_store');
        $role_menu_store = $this->role_menu_access_store;
        $role_state = $role_menu_store->state($role['role_id']);
        $catalog = $this->role_menu_allowed_catalog($role);
        if ($role_state === FALSE || $catalog === FALSE) {
            $this->datatable_server->error(503);
            return;
        }
        $groups = $catalog[0];
        $allowed_state = $catalog[1];
        $this->load->library('menu_access_catalog');
        $columns = $this->menu_access_catalog;
        $rows = $columns->rows($groups, $role_state, $allowed_state);
        $write_available = $this->role_write_available($role_store);
        $this->datatable_server->respond(
            array(0 => 'group', 1 => 'menu'),
            function () use ($rows) {
                return count($rows);
            },
            function ($search) use ($rows, $columns) {
                return count($columns->filter($rows, $search));
            },
            function ($search, $length, $start, $order, $direction) use ($rows, $columns, $write_available) {
                $filtered = $columns->filter($rows, $search);
                $sorted = $columns->sort($filtered, $order, $direction);
                $page = $columns->slice($sorted, $length, $start);

                return $this->menu_access_payload($page, $write_available);
            },
            array('max_length' => 100, 'max_search_length' => 120, 'default_order' => 'group')
        );
    }

    /** Toggle one role menu or access checkbox inside the tenant-scoped catalog. */
    public function role_menu_toggle($role_code, $tenant_id = NULL)
    {
        if (!$this->has_admin_menu_code('app_role') || !$this->has_admin_access_code('app_role_menu_access')) {
            $this->menu_access_json(403, array('saved' => FALSE));
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') {
            $this->menu_access_json(405, array('saved' => FALSE));
            return;
        }
        $role = $this->role_menu_record($role_code, $tenant_id);
        if ($role === NULL) {
            $this->menu_access_json(404, array('saved' => FALSE));
            return;
        }
        $role_store = $this->role_store();
        $this->load->library('role_menu_access_store');
        $role_menu_store = $this->role_menu_access_store;
        if (!$this->role_write_available($role_store) || !$role_menu_store->schema_available()) {
            $this->menu_access_json(503, array('saved' => FALSE));
            return;
        }
        $catalog = $this->role_menu_allowed_catalog($role);
        if ($catalog === FALSE) {
            $this->menu_access_json(503, array('saved' => FALSE));
            return;
        }
        $allowed_state = $catalog[1];
        $menu_code = $this->input->post('menu_code', FALSE);
        $access_code = $this->input->post('access_code', FALSE);
        $checked = $this->input->post('checked', FALSE) === '1';
        if (!is_string($menu_code) || $menu_code === '' || !array_key_exists($menu_code, $allowed_state)) {
            $this->menu_access_json(422, array('saved' => FALSE));
            return;
        }
        if (is_string($access_code) && $access_code !== '') {
            if (!isset($allowed_state[$menu_code][$access_code])) {
                $this->menu_access_json(422, array('saved' => FALSE));
                return;
            }
            $saved = $role_menu_store->toggle_access(
                (int) $role['role_id'],
                $menu_code,
                $access_code,
                $checked,
                $allowed_state
            );
        } else {
            $saved = $role_menu_store->toggle_menu(
                (int) $role['role_id'],
                $menu_code,
                $checked,
                $allowed_state
            );
        }
        $this->menu_access_json($saved ? 200 : 500, array('saved' => (bool) $saved));
    }

    /** Resolve and authorize the role/tenant pair used by the menu-access endpoints. */
    private function role_menu_record($role_code, $tenant_id)
    {
        if (!$this->has_admin_access_code('app_role_tenant')) {
            $tenant_id = (int) $this->current_user['tenant_id'];
        }
        $role_code = $this->role_code($role_code);
        if ($tenant_id !== NULL && (!is_numeric($tenant_id)
            || (string) (int) $tenant_id !== (string) $tenant_id || (int) $tenant_id < 1)) {
            return NULL;
        }
        if ($role_code === '' || !preg_match('/^[a-zA-Z0-9._-]+$/', $role_code)) {
            return NULL;
        }
        $role_store = $this->role_store();
        if ($role_store === NULL || !$role_store->schema_available()) {
            return NULL;
        }
        $role = $role_store->find($role_code, $tenant_id);

        return is_array($role) ? $role : NULL;
    }

    /** Manifest menus and accesses the role's tenant has enabled. */
    private function role_menu_allowed_catalog($role)
    {
        $this->load->library('tenant_menu_access_store');
        $this->load->library('admin_menu_manifest');
        $tenant_state = $this->tenant_menu_access_store->state((int) $role['tenant_id']);
        if ($tenant_state === FALSE) {
            return FALSE;
        }
        $manifest_groups = $this->admin_menu_manifest->load(
            APPPATH . 'views/admin/menu_manifest/manifest.xml'
        );
        $groups = array();
        $allowed_state = array();
        foreach ($manifest_groups as $group) {
            $visible_group = $group;
            $visible_group['items'] = array();
            foreach ($group['items'] as $item) {
                $menu_code = (string) $item['code'];
                if (!isset($tenant_state[$menu_code])) {
                    continue;
                }
                $visible_item = $item;
                $visible_item['accesses'] = array();
                $allowed_state[$menu_code] = array();
                foreach ($item['accesses'] as $access) {
                    $access_code = (string) $access['access_code'];
                    if (!isset($tenant_state[$menu_code][$access_code])) {
                        continue;
                    }
                    $visible_item['accesses'][] = $access;
                    $allowed_state[$menu_code][$access_code] = TRUE;
                }
                $visible_group['items'][] = $visible_item;
            }
            if ($visible_group['items']) {
                $groups[] = $visible_group;
            }
        }

        return array($groups, $allowed_state);
    }

    /** Render menu/access cells for DataTables rows through the shared partials. */
    private function menu_access_payload($rows, $write_available)
    {
        $payload = array();
        foreach ($rows as $row) {
            $payload[] = array(
                'menu' => $this->load->view('admin/menu_access/_menu_cell', array(
                    'menu_access_row' => $row,
                    'menu_access_write_available' => $write_available,
                ), TRUE),
                'group' => html_escape($row['group_label']),
                'accesses' => $this->load->view('admin/menu_access/_access_cell', array(
                    'menu_access_row' => $row,
                    'menu_access_write_available' => $write_available,
                ), TRUE),
            );
        }

        return $payload;
    }

    private function menu_access_json($status, $payload)
    {
        $payload['csrf'] = array(
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash(),
        );
        $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function role_store()
    {
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
            return NULL;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('role_store');
        return $this->role_store;
    }

    private function role_write_available($store)
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development'
            && $this->current_user !== NULL
            && (bool) config_item('csrf_protection')
            && $store !== NULL
            && $store->schema_available();
    }

    private function role_form_values($include_code)
    {
        $names = $include_code ? array('tenant_id', 'role_code', 'role_name', 'description', 'active_flag') : array('role_name', 'description', 'active_flag');
        $values = array();
        foreach ($names as $name) {
            $value = $this->input->post($name, TRUE);
            $values[$name] = is_string($value) ? trim($value) : '';
        }
        if ($include_code) {
            $values['role_code'] = strtolower(preg_replace('/\s+/', '', $values['role_code']));
        }
        $values['active_flag'] = $values['active_flag'] === '1' ? 1 : 0;
        return $values;
    }

    private function validate_role($record, $store, $include_code, $except_role_code = NULL)
    {
        $errors = array();
        $specs = $store->field_specs();
            if ($include_code) {
                $tenant_valid = $store->tenant_exists($record['tenant_id']);
                if (!$tenant_valid) {
                    $errors[] = 'tenant_invalid';
                }
                $code = $record['role_code'];
                if ($code === '' || !preg_match('/^[a-zA-Z0-9._-]+$/', $code)) {
                    $errors[] = 'code_invalid';
                } elseif ($this->role_too_long($code, $specs, 'role_code')) {
                    $errors[] = 'code_too_long';
                } elseif ($tenant_valid) {
                    $duplicate = $store->code_exists($code, $record['tenant_id']);
                    if ($duplicate === NULL || $duplicate === TRUE) {
                        $errors[] = $duplicate === NULL ? 'save_error' : 'code_duplicate';
                    }
                }
            }
        if ($record['role_name'] === '') {
            $errors[] = 'name_required';
        } elseif ($this->role_too_long($record['role_name'], $specs, 'role_name')) {
            $errors[] = 'name_too_long';
        }
        if ($this->role_too_long($record['description'], $specs, 'description')) {
            $errors[] = 'description_too_long';
        }
        if (!in_array((int) $record['active_flag'], array(0, 1), TRUE)) {
            $errors[] = 'invalid';
        }
        return $errors;
    }

    private function role_too_long($value, $specs, $field)
    {
        return isset($specs[$field]['max_length']) && $specs[$field]['max_length'] > 0
            && function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') > $specs[$field]['max_length']
            : (isset($specs[$field]['max_length']) && $specs[$field]['max_length'] > 0 && strlen($value) > $specs[$field]['max_length']);
    }

    private function role_search($value)
    {
        return is_string($value) ? trim(substr($value, 0, 100)) : '';
    }

    private function role_sort($value)
    {
        return in_array($value, array('role_code', 'role_name', 'active_flag'), TRUE) ? $value : 'role_code';
    }

    private function role_page_size()
    {
        $size = (int) $this->input->get('per_page', TRUE);
        return in_array($size, array(10, 25, 50), TRUE) ? $size : 10;
    }

    private function role_page_number()
    {
        return max(1, (int) $this->input->get('page', TRUE));
    }

    private function role_code($value)
    {
        return is_string($value) ? trim(rawurldecode($value)) : '';
    }
}
