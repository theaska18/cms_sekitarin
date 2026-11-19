<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Sequence create controller, using the authenticated admin shell. */
class Admin_sequence extends Admin
{
    const ADMIN_SEQUENCE_PATH = '/admin/system/sequence';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('finance_filter_panel');
        $this->load->library('finance_list_table');
    }

    private function sequence_deny_access($message_key = 'sequence_no_access', $requested_path = NULL)
    {
        $requested_path = is_string($requested_path) && $requested_path !== ''
            ? $requested_path
            : self::ADMIN_SEQUENCE_PATH;
        $title_key = $requested_path === '/admin/application/sequence'
            ? 'sequence_heading'
            : 'sequence_template_heading';
        $this->output->set_status_header(403);
        $this->render_admin(
            'admin/sequence/access_denied',
            $title_key,
            $requested_path,
            array(
                'sequence_access_message_key' => $message_key,
                'sequence_access_title_key' => $title_key,
            )
        );
    }

    public function sequence_index()
    {
        if (trim($this->uri->uri_string(), '/') === 'admin/application/sequence') {
            $this->application_sequence_index();
            return;
        }
        if (!$this->has_admin_menu_code('sys_sequence')) {
            $this->sequence_deny_access('sequence_no_access');
            return;
        }
        $module = array(
            'key' => 'sequence_template',
            'group_key' => 'system',
            'description_key' => 'system_sequence_description',
            'focus_key' => 'system_sequence_focus',
        );
        $requested_path = '/admin/system/sequence';
        $this->load->database();
        $this->load->library('sequence_store');
        $backend_available = $this->sequence_store->schema_available();

        $this->render_admin(
            'admin/sequence/index',
            'sequence_template_heading',
            $requested_path,
            array(
                'module' => $module,
                'backend_available' => $backend_available,
                'write_available' => $this->current_user !== NULL && (bool) config_item('csrf_protection'),
                'sequence_add_allowed' => $this->has_admin_access_code('sys_sequence_add'),
                'sequence_update_allowed' => $this->has_admin_access_code('sys_sequence_update'),
                'sequence_delete_allowed' => $this->has_admin_access_code('sys_sequence_delete'),
                'sequence_actions_available' => $this->has_admin_access_code('sys_sequence_update') || $this->has_admin_access_code('sys_sequence_delete'),
                'sequence_notice' => (string) $this->session->flashdata('sequence_notice'),
                'finance_filter_config' => $this->sequence_filter_config($this->sequence_store),
            )
        );
    }

    private function sequence_filter_config($store = NULL)
    {
        $labels = $this->lang->language;
        $repeat_options = array();
        $rows = $store !== NULL ? $store->repeat_type_options() : FALSE;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $repeat_options[(string) $row['option_code']] = (string) $row['option_name'];
            }
        }
        return $this->finance_filter_panel->config(array(
            'date' => FALSE,
            'date_preset' => 'none',
            'status_preset' => 'none',
            'target' => '#sequence-data-table',
            'field_filters' => array(
                array('type' => 'text', 'name' => 'sequence_code', 'label' => isset($labels['sequence_sequence_code']) ? $labels['sequence_sequence_code'] : 'Code'),
                array('type' => 'text', 'name' => 'sequence_name', 'label' => isset($labels['sequence_sequence_name']) ? $labels['sequence_sequence_name'] : 'Name'),
                array('type' => 'text', 'name' => 'description', 'label' => isset($labels['sequence_description']) ? $labels['sequence_description'] : 'Description'),
                array('type' => 'select', 'name' => 'repeat_type', 'label' => isset($labels['sequence_repeat_type']) ? $labels['sequence_repeat_type'] : 'Repeat', 'options' => $repeat_options),
                array('type' => 'text', 'name' => 'menu_codes', 'label' => isset($labels['sequence_menu']) ? $labels['sequence_menu'] : 'Menu'),
                array('type' => 'text', 'name' => 'format', 'label' => isset($labels['sequence_format']) ? $labels['sequence_format'] : 'Format'),
            ),
        ));
    }

    private function sequence_list_filters($search = '')
    {
        $panel = $this->finance_filter_panel->filters($this->sequence_filter_config($this->sequence_store));
        return array('search' => (string) $search, 'fields' => $panel['fields']);
    }

    /** DataTables server-side endpoint; the shared parser bounds paging and ordering. */
    public function sequence_data()
    {
        if (trim($this->uri->uri_string(), '/') === 'admin/application/sequence/data') {
            $this->application_sequence_data();
            return;
        }
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code('sys_sequence')) {
            $this->datatable_server->error(403);
            return;
        }
        $this->load->database();
        $this->load->library('sequence_store');
        $store = $this->sequence_store;
        if (!$store->schema_available()) {
            $this->datatable_server->error(503);
            return;
        }
        $menu_labels = array();
        foreach ($this->sequence_manifest_items() as $item) {
            $menu_labels[$item['code']] = $item['group'] . ' / ' . $item['label'] . ' (' . $item['code'] . ')';
        }
        $this->datatable_server->respond(
            array(
                1 => 'sequence_code',
                2 => 'sequence_name',
                3 => 'description',
                4 => 'repeat_type',
                5 => 'menu_codes',
                6 => 'format',
            ),
            function () use ($store) {
                return $store->count('', $this->sequence_list_filters(''));
            },
            function ($search) use ($store) {
                return $store->count($search, $this->sequence_list_filters($search));
            },
            function ($search, $length, $start, $order, $direction) use ($store, $menu_labels) {
                $records = $store->page($search, $length, $start, $order, $direction, $this->sequence_list_filters($search));
                if ($records === FALSE) {
                    return FALSE;
                }
                foreach ($records as &$record) {
                    $codes = isset($record['menu_codes']) ? explode(',', (string) $record['menu_codes']) : array();
                    $record['menu_label'] = implode(', ', array_map(function ($code) use ($menu_labels) {
                        return isset($menu_labels[$code]) ? $menu_labels[$code] : $code;
                    }, $codes));
                }
                unset($record);
                return $records;
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'sequence_code')
        );
    }

    /** Tenant-scoped list of accessible system templates and existing overrides. */
    private function application_sequence_index()
    {
        if (!$this->has_admin_menu_code('app_sequence')) {
            $this->sequence_deny_access('sequence_no_access', '/admin/application/sequence');
            return;
        }
        $this->load->database();
        $this->load->library('application_sequence_store');
        $store = $this->application_sequence_store;
        $backend_available = $store->schema_available();
        $this->render_admin(
            'admin/sequence/application_index',
            'sequence_heading',
            '/admin/application/sequence',
            array(
                'backend_available' => $backend_available,
                'write_available' => $backend_available && $this->current_user !== NULL && (bool) config_item('csrf_protection'),
                'sequence_update_allowed' => $this->has_admin_access_code('app_sequence_update'),
                'sequence_set_default_allowed' => $this->has_admin_access_code('app_sequence_set_default'),
                'sequence_notice' => (string) $this->session->flashdata('sequence_notice'),
                'finance_filter_config' => $this->application_sequence_filter_config($store),
            )
        );
    }

    private function application_sequence_filter_config($store = NULL)
    {
        $labels = $this->lang->language;
        $repeat_options = array();
        $rows = $store !== NULL ? $store->repeat_type_options() : FALSE;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $repeat_options[(string) $row['option_code']] = (string) $row['option_name'];
            }
        }
        return $this->finance_filter_panel->config(array(
            'date' => FALSE,
            'date_preset' => 'none',
            'status_preset' => 'none',
            'target' => '#application-sequence-data-table',
            'field_filters' => array(
                array('type' => 'text', 'name' => 'sequence_code', 'label' => isset($labels['sequence_sequence_code']) ? $labels['sequence_sequence_code'] : 'Code'),
                array('type' => 'text', 'name' => 'sequence_name', 'label' => isset($labels['sequence_sequence_name']) ? $labels['sequence_sequence_name'] : 'Name'),
                array('type' => 'select', 'name' => 'repeat_type', 'label' => isset($labels['sequence_repeat_type']) ? $labels['sequence_repeat_type'] : 'Repeat', 'options' => $repeat_options),
                array('type' => 'text', 'name' => 'format', 'label' => isset($labels['sequence_format']) ? $labels['sequence_format'] : 'Format'),
                array('type' => 'range', 'name' => 'last_value', 'label' => isset($labels['sequence_last_value']) ? $labels['sequence_last_value'] : 'Last value', 'min_name' => 'last_value_min', 'max_name' => 'last_value_max'),
                array('type' => 'select', 'name' => 'use_flag', 'label' => isset($labels['sequence_use_flag']) ? $labels['sequence_use_flag'] : 'Use', 'options' => array(
                    '1' => isset($labels['sequence_use_flag_enabled']) ? $labels['sequence_use_flag_enabled'] : 'Enabled',
                    '0' => isset($labels['sequence_use_flag_disabled']) ? $labels['sequence_use_flag_disabled'] : 'Disabled',
                )),
            ),
        ));
    }

    private function application_sequence_list_filters($search = '')
    {
        $panel = $this->finance_filter_panel->filters($this->application_sequence_filter_config($this->application_sequence_store));
        return array('search' => (string) $search, 'fields' => $panel['fields']);
    }

    /** DataTables endpoint scoped by effective tenant/role menu visibility. */
    private function application_sequence_data()
    {
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code('app_sequence')) {
            $this->datatable_server->error(403);
            return;
        }
        $this->load->database();
        $this->load->library('application_sequence_store');
        $store = $this->application_sequence_store;
        if (!$store->schema_available()) {
            $this->datatable_server->error(503);
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $menu_codes = isset($this->current_user['menu_codes']) && is_array($this->current_user['menu_codes'])
            ? $this->current_user['menu_codes']
            : array();
        $this->datatable_server->respond(
            array(
                1 => 'sequence_code',
                2 => 'sequence_name',
                3 => 'repeat_type',
                4 => 'format',
                5 => 'last_value',
                6 => 'use_flag',
            ),
            function () use ($store, $tenant_id, $menu_codes) {
                return $store->count($tenant_id, $menu_codes, '', $this->application_sequence_list_filters(''));
            },
            function ($search) use ($store, $tenant_id, $menu_codes) {
                return $store->count($tenant_id, $menu_codes, $search, $this->application_sequence_list_filters($search));
            },
            function ($search, $length, $start, $order, $direction) use ($store, $tenant_id, $menu_codes) {
                return $store->page($tenant_id, $menu_codes, $search, $length, $start, $order, $direction, $this->application_sequence_list_filters($search));
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'sequence_code')
        );
    }

    public function sequence_new()
    {
        if (strpos(trim($this->uri->uri_string(), '/'), 'admin/application/sequence/') === 0) {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code('sys_sequence') || !$this->has_admin_access_code('sys_sequence_add')) {
            $this->sequence_deny_access('sequence_no_access');
            return;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('sequence_store');
        $store = $this->sequence_store;
        $backend_available = $store->schema_available();
        $field_specs = $store->field_specs();
        if (!$field_specs) {
            $backend_available = FALSE;
        }
        $repeat_type_options = $store->repeat_type_options();
        if ($repeat_type_options === FALSE) {
            $repeat_type_options = array();
            $backend_available = FALSE;
        }
        $write_available = $backend_available
            && $this->current_user !== NULL
            && (bool) config_item('csrf_protection');
        $record = $this->empty_sequence_record($field_specs);
        $sequence_menu_labels = $this->sequence_menu_labels($record['menu_codes']);
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Sequence writes are unavailable.', 503);
                return;
            }
            $record = array_merge($record, $this->sequence_form_values());
            $errors = $this->validate_sequence_values($record, $field_specs, $repeat_type_options);
            $sequence_menu_labels = $this->sequence_menu_labels($record['menu_codes']);
            if (!$errors) {
                $insert_record = $this->sequence_insert_record($record, $field_specs);
                if ($store->insert($insert_record)) {
                    $this->session->set_flashdata('sequence_notice', 'sequence_created');
                    redirect('/admin/system/sequence', 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
        }
        $this->render_admin(
            'admin/sequence/form',
            'sequence_form_new',
            '/admin/system/sequence/new',
            array(
                'sequence_record' => $record,
                'sequence_field_specs' => $field_specs,
                'sequence_repeat_type_options' => $repeat_type_options,
                'sequence_menu_labels' => $sequence_menu_labels,
                'sequence_errors' => $errors,
                'sequence_backend_available' => $backend_available,
                'sequence_write_available' => $write_available,
            )
        );
    }

    public function sequence_change($sequence_code)
    {
        if (strpos(trim($this->uri->uri_string(), '/'), 'admin/application/sequence/change/') === 0) {
            $this->application_sequence_change($sequence_code);
            return;
        }
        if (!$this->has_admin_menu_code('sys_sequence') || !$this->has_admin_access_code('sys_sequence_update')) {
            $this->sequence_deny_access('sequence_no_access');
            return;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('sequence_store');
        $store = $this->sequence_store;
        $backend_available = $store->schema_available();
        $field_specs = $store->field_specs();
        $saved_record = $store->find((string) $sequence_code);
        if (!$backend_available || !$field_specs) {
            show_error('Sequence storage is unavailable.', 503);
            return;
        }
        if ($saved_record === FALSE) {
            show_error('Sequence storage is unavailable.', 503);
            return;
        }
        if ($saved_record === NULL) {
            show_404();
            return;
        }
        $repeat_type_options = $store->repeat_type_options();
        if ($repeat_type_options === FALSE) {
            show_error('Sequence options are unavailable.', 503);
            return;
        }
        $write_available = $this->current_user !== NULL && (bool) config_item('csrf_protection');
        $record = $this->sequence_record_for_form($saved_record, $field_specs);
        $sequence_menu_labels = $this->sequence_menu_labels($record['menu_codes']);
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Sequence writes are unavailable.', 503);
                return;
            }
            $record = array_merge($record, $this->sequence_form_values());
            $errors = $this->validate_sequence_values($record, $field_specs, $repeat_type_options);
            $sequence_menu_labels = $this->sequence_menu_labels($record['menu_codes']);
            if (!$errors) {
                $update_record = $this->sequence_insert_record($record, $field_specs);
                if ($store->update((string) $sequence_code, $update_record)) {
                    $this->session->set_flashdata('sequence_notice', 'sequence_saved');
                    redirect('/admin/system/sequence', 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
        }
        $this->render_admin(
            'admin/sequence/form',
            'sequence_form_edit',
            '/admin/system/sequence/change/' . rawurlencode((string) $sequence_code),
            array(
                'sequence_record' => $record,
                'sequence_field_specs' => $field_specs,
                'sequence_repeat_type_options' => $repeat_type_options,
                'sequence_menu_labels' => $sequence_menu_labels,
                'sequence_errors' => $errors,
                'sequence_backend_available' => $backend_available,
                'sequence_write_available' => $write_available,
                'sequence_is_edit' => TRUE,
                'sequence_original_code' => (string) $sequence_code,
            )
        );
    }

    /** Update a tenant override, initializing it from the template when first saved. */
    private function application_sequence_change($sequence_code)
    {
        if (!$this->has_admin_menu_code('app_sequence')
            || !$this->has_admin_access_code('app_sequence_update')) {
            $this->sequence_deny_access('sequence_no_access');
            return;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('application_sequence_store');
        $store = $this->application_sequence_store;
        if (!$store->schema_available()) {
            show_error('Application sequence storage is unavailable.', 503);
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $menu_codes = isset($this->current_user['menu_codes']) && is_array($this->current_user['menu_codes'])
            ? $this->current_user['menu_codes']
            : array();
        $record = $store->find_for_tenant($tenant_id, (string) $sequence_code, $menu_codes);
        if ($record === FALSE) {
            show_error('Application sequence storage is unavailable.', 503);
            return;
        }
        if ($record === NULL) {
            show_404();
            return;
        }
        $repeat_type_options = $store->repeat_type_options();
        if ($repeat_type_options === FALSE) {
            show_error('Application sequence options are unavailable.', 503);
            return;
        }
        $write_available = $this->current_user !== NULL && (bool) config_item('csrf_protection');
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Application sequence writes are unavailable.', 503);
                return;
            }
            $posted_last_value = $this->input->post('last_value', TRUE);
            $last_value = is_scalar($posted_last_value) ? trim((string) $posted_last_value) : '';
            $posted_format = $this->input->post('format', FALSE);
            $format = is_string($posted_format) ? trim($posted_format) : '';
            $posted_repeat_type = $this->input->post('repeat_type', TRUE);
            $repeat_type = is_string($posted_repeat_type) ? trim($posted_repeat_type) : '';
            $use_flag = $this->input->post('use_flag') === '1' ? '1' : '0';
            $record['last_value'] = $last_value;
            $record['format'] = $format;
            $record['repeat_type'] = $repeat_type;
            $record['use_flag'] = $use_flag;

            if ($last_value === '' || !preg_match('/^\d+$/', $last_value)
                || $this->compare_integer_strings($last_value, '2147483647') > 0) {
                $errors[] = 'last_value_invalid';
            }
            if ($format === '') {
                $errors[] = 'format_required';
            } elseif (strlen($format) > 45) {
                $errors[] = 'format_too_long';
            } else {
                $this->load->library('sequence_format');
                if (!$this->sequence_format->is_valid($format)) {
                    $errors[] = 'format_invalid';
                }
            }
            $valid_repeat_types = array_map(function ($option) {
                return isset($option['option_code']) ? (string) $option['option_code'] : '';
            }, $repeat_type_options);
            if (!in_array($repeat_type, $valid_repeat_types, TRUE)) {
                $errors[] = 'repeat_type_invalid';
            }
            if (!$errors && $store->save_for_tenant($tenant_id, (string) $sequence_code, array(
                'last_value' => (int) $last_value,
                'format' => $format,
                'repeat_type' => $repeat_type,
                'use_flag' => $use_flag,
            ))) {
                $this->session->set_flashdata('sequence_notice', 'sequence_application_saved');
                redirect('/admin/application/sequence', 'location', 303);
                return;
            } elseif (!$errors) {
                $errors[] = 'save_error';
            }
        }

        $this->render_admin(
            'admin/sequence/application_form',
            'sequence_form_edit',
            '/admin/application/sequence/change/' . rawurlencode((string) $sequence_code),
            array(
                'sequence_record' => $record,
                'sequence_repeat_type_options' => $repeat_type_options,
                'sequence_errors' => $errors,
                'sequence_write_available' => $write_available,
            )
        );
    }

    /** Reset tenant-specific format and repeat interval from the sys_sequence template. */
    public function sequence_set_default($sequence_code)
    {
        $path = trim($this->uri->uri_string(), '/');
        if (strpos($path, 'admin/application/sequence/set_default/') !== 0) {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code('app_sequence')
            || !$this->has_admin_access_code('app_sequence_set_default')) {
            $this->sequence_deny_access('sequence_no_access', '/admin/application/sequence');
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed.', 405);
            return;
        }
        if ($this->current_user === NULL || !(bool) config_item('csrf_protection')) {
            show_error('Application sequence writes are unavailable.', 503);
            return;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('application_sequence_store');
        $store = $this->application_sequence_store;
        $menu_codes = isset($this->current_user['menu_codes']) && is_array($this->current_user['menu_codes'])
            ? $this->current_user['menu_codes']
            : array();
        $result = $store->schema_available()
            && $store->reset_to_template((int) $this->current_user['tenant_id'], (string) $sequence_code, $menu_codes);
        $this->session->set_flashdata('sequence_notice', $result ? 'sequence_default_saved' : 'sequence_default_error');
        redirect('/admin/application/sequence', 'location', 303);
    }

    public function sequence_delete()
    {
        if (strpos(trim($this->uri->uri_string(), '/'), 'admin/application/sequence/') === 0) {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code('sys_sequence') || !$this->has_admin_access_code('sys_sequence_delete')) {
            $this->sequence_deny_access('sequence_no_access');
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed.', 405);
            return;
        }
        if ($this->current_user === NULL || !(bool) config_item('csrf_protection')) {
            show_error('Sequence writes are unavailable.', 503);
            return;
        }
        $sequence_code = $this->input->post('sequence_code', TRUE);
        if (!is_string($sequence_code) || $sequence_code === '') {
            $this->session->set_flashdata('sequence_notice', 'sequence_delete_error');
            redirect('/admin/system/sequence', 'location', 303);
            return;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('sequence_store');
        $result = $this->sequence_store->delete($sequence_code);
        $this->session->set_flashdata('sequence_notice', $result ? 'sequence_deleted' : 'sequence_delete_error');
        redirect('/admin/system/sequence', 'location', 303);
    }

    /** Search active manifest menus available to this tenant/role. */
    public function sequence_menu_lookup()
    {
        if (!$this->has_admin_menu_code('sys_sequence')) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
            return;
        }
        $term = $this->input->get('term', TRUE);
        $length = is_string($term) && function_exists('mb_strlen')
            ? mb_strlen($term, 'UTF-8')
            : (is_string($term) ? strlen($term) : 0);
        if ($length < 2 || $length > 120) {
            $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
            return;
        }
        $allowed = isset($this->current_user['menu_codes']) && is_array($this->current_user['menu_codes'])
            ? array_fill_keys($this->current_user['menu_codes'], TRUE)
            : array();
        $needle = function_exists('mb_strtolower') ? mb_strtolower($term, 'UTF-8') : strtolower($term);
        $results = array();
        foreach ($this->sequence_manifest_items() as $item) {
            if (!isset($allowed[$item['code']])) {
                continue;
            }
            $haystack = $item['code'] . ' ' . $item['label'] . ' ' . $item['group'];
            $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
            if (strpos($haystack, $needle) === FALSE) {
                continue;
            }
            $label = $item['group'] . ' / ' . $item['label'] . ' (' . $item['code'] . ')';
            $results[] = array('id' => $item['code'], 'label' => $label, 'value' => $label);
            if (count($results) >= 20) {
                break;
            }
        }
        $this->output->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    private function sequence_manifest_items()
    {
        $this->load->library('admin_menu_manifest');
        $groups = $this->admin_menu_manifest->load(APPPATH . 'views/admin/menu_manifest/manifest.xml');
        $items = array();
        foreach ($groups as $group) {
            $group_translation = preg_match('/^admin_[a-z0-9_]+$/', $group['label_key'])
                ? $this->lang->line($group['label_key'])
                : FALSE;
            $group_label = $this->admin_menu_manifest->resolve_label($group_translation, $group['name'] ?: $group['code']);
            foreach ($group['items'] as $item) {
                $item_translation = preg_match('/^admin_[a-z0-9_]+$/', $item['label_key'])
                    ? $this->lang->line($item['label_key'])
                    : FALSE;
                $items[] = array(
                    'code' => (string) $item['code'],
                    'label' => $this->admin_menu_manifest->resolve_label($item_translation, $item['name'] ?: $item['code']),
                    'group' => $group_label,
                );
            }
        }
        return $items;
    }

    private function sequence_menu_labels($menu_codes)
    {
        if (!is_array($menu_codes)) {
            return array();
        }
        $items = array();
        foreach ($this->sequence_manifest_items() as $item) {
            if (in_array($item['code'], $menu_codes, TRUE)) {
                $items[$item['code']] = $item['group'] . ' / ' . $item['label'] . ' (' . $item['code'] . ')';
            }
        }
        foreach ($menu_codes as $menu_code) {
            if (is_string($menu_code) && !isset($items[$menu_code])) {
                $items[$menu_code] = $menu_code;
            }
        }
        return $items;
    }

    private function sequence_record_for_form($saved_record, $field_specs)
    {
        $record = $this->empty_sequence_record($field_specs);
        foreach ($record as $name => $unused) {
            if ($name === 'menu_codes') {
                $record[$name] = isset($saved_record[$name]) && is_array($saved_record[$name])
                    ? array_values(array_unique($saved_record[$name]))
                    : array();
            } elseif (array_key_exists($name, $saved_record) && $saved_record[$name] !== NULL) {
                $record[$name] = (string) $saved_record[$name];
            }
        }
        if (isset($field_specs['last_sequence'], $record['last_sequence']) && $record['last_sequence'] !== '') {
            if ($this->sequence_uses_date_only($field_specs['last_sequence'])) {
                $record['last_sequence'] = substr($record['last_sequence'], 0, 10);
            } elseif (preg_match('/^(\\d{4}-\\d{2}-\\d{2})[ T](\\d{2}:\\d{2})/', $record['last_sequence'], $match)) {
                $record['last_sequence'] = $match[1] . 'T' . $match[2];
            }
        }
        return $record;
    }

    private function empty_sequence_record($field_specs)
    {
        $names = array(
            'sequence_code', 'sequence_name', 'description', 'repeat_type',
            'menu_codes', 'last_value', 'last_sequence', 'format',
        );
        $record = array();
        foreach ($names as $name) {
            $default = isset($field_specs[$name]) ? $field_specs[$name]['default'] : NULL;
            $record[$name] = is_scalar($default) ? (string) $default : '';
            if ($name === 'menu_codes') {
                $record[$name] = array();
            }
            if ($name === 'last_value' && $record[$name] === '') {
                $record[$name] = isset($field_specs[$name]['minimum']) && $field_specs[$name]['minimum'] !== NULL
                    ? (string) $field_specs[$name]['minimum']
                    : '0';
            }
            if ($name === 'last_sequence' && $record[$name] === '') {
                $record[$name] = $this->sequence_uses_date_only(isset($field_specs[$name]) ? $field_specs[$name] : array())
                    ? date('Y-m-d')
                    : date('Y-m-d\TH:i');
            }
            if ($name === 'last_sequence' && !$this->sequence_uses_date_only(isset($field_specs[$name]) ? $field_specs[$name] : array())
                && preg_match('/^(\\d{4}-\\d{2}-\\d{2})[ T](\\d{2}:\\d{2})/', $record[$name], $match)) {
                $record[$name] = $match[1] . 'T' . $match[2];
            }
        }
        return $record;
    }

    private function sequence_insert_record($record, $field_specs)
    {
        $insert_record = array();
        foreach ($record as $name => $value) {
            if ($name === 'menu_codes') {
                $insert_record[$name] = $value;
                continue;
            }
            if (!isset($field_specs[$name])) {
                continue;
            }
            if ($name === 'last_sequence' && $value !== '') {
                $date_format = $this->sequence_uses_date_only($field_specs[$name]) ? '!Y-m-d' : '!Y-m-d\\TH:i';
                $date = DateTime::createFromFormat($date_format, $value);
                $value = $this->sequence_uses_date_only($field_specs[$name])
                    ? $date->format('Y-m-d')
                    : $date->format('Y-m-d H:i:s');
            }
            if ($value === '' && isset($field_specs[$name])) {
                if (!empty($field_specs[$name]['nullable'])) {
                    $value = NULL;
                } elseif (empty($field_specs[$name]['required']) && $field_specs[$name]['default'] !== NULL) {
                    continue;
                }
            }
            $insert_record[$name] = $value;
        }
        return $insert_record;
    }

    private function sequence_form_values()
    {
        $names = array(
            'sequence_code', 'sequence_name', 'description', 'repeat_type',
            'format',
        );
        $values = array();
        foreach ($names as $name) {
            $value = $this->input->post($name, FALSE);
            $values[$name] = is_string($value) ? trim($value) : '';
        }
        $submitted_menu_codes = $this->input->post('menu_codes');
        $values['menu_codes'] = array();
        if (is_array($submitted_menu_codes)) {
            foreach (array_slice($submitted_menu_codes, 0, 50) as $menu_code) {
                if (is_string($menu_code)) {
                    $menu_code = trim($menu_code);
                    if ($menu_code !== '') {
                        $values['menu_codes'][$menu_code] = $menu_code;
                    }
                }
            }
            $values['menu_codes'] = array_values($values['menu_codes']);
        }
        $values['sequence_code'] = strtolower(preg_replace('/\\s+/', '', $values['sequence_code']));
        if (function_exists('mb_convert_case')) {
            $values['sequence_name'] = mb_convert_case(mb_strtolower($values['sequence_name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        } else {
            $values['sequence_name'] = ucwords(strtolower($values['sequence_name']));
        }
        return $values;
    }

    private function validate_sequence_values($record, $field_specs, $repeat_type_options)
    {
        $errors = array();
        foreach ($record as $name => $value) {
            if (isset($field_specs[$name]) && !empty($field_specs[$name]['required']) && $value === '') {
                $errors[] = $name . '_required';
            }
        }
        foreach ($record as $name => $value) {
            if (!isset($field_specs[$name])) {
                continue;
            }
            $max = isset($field_specs[$name]['max_length']) ? (int) $field_specs[$name]['max_length'] : 0;
            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if ($max > 0 && $length > $max) {
                $errors[] = $name . '_too_long';
            }
        }
        if ($record['sequence_code'] !== '' && preg_match('/[\\x00-\\x20\\x7F]/', $record['sequence_code'])) {
            $errors[] = 'sequence_code_invalid';
        }
        if ($record['format'] !== '') {
            $this->load->library('sequence_format');
            if (!$this->sequence_format->is_valid($record['format'])) {
                $errors[] = 'format_invalid';
            }
        }
        $allowed_menu_codes = isset($this->current_user['menu_codes']) && is_array($this->current_user['menu_codes'])
            ? array_fill_keys($this->current_user['menu_codes'], TRUE)
            : array();
        $manifest_codes = array_fill_keys(array_column($this->sequence_manifest_items(), 'code'), TRUE);
        if (empty($record['menu_codes']) || !is_array($record['menu_codes'])) {
            $errors[] = 'menu_code_required';
        } else {
            if (count($record['menu_codes']) > 50) {
                $errors[] = 'menu_code_invalid';
            }
            foreach ($record['menu_codes'] as $menu_code) {
                if (!is_string($menu_code) || !isset($manifest_codes[$menu_code])
                    || !isset($allowed_menu_codes[$menu_code])) {
                    $errors[] = 'menu_code_invalid';
                    break;
                }
            }
        }
        if (isset($field_specs['last_value'])) {
            if ($record['last_value'] !== '' && !preg_match('/^-?\\d+$/', $record['last_value'])) {
                $errors[] = 'last_value_invalid';
            } elseif ($record['last_value'] !== '' && !$this->within_integer_range($record['last_value'], $field_specs['last_value'])) {
                $errors[] = 'last_value_invalid';
            }
        }
        if (isset($field_specs['last_sequence']) && $record['last_sequence'] !== '') {
            $date_format = $this->sequence_uses_date_only($field_specs['last_sequence']) ? '!Y-m-d' : '!Y-m-d\\TH:i';
            $date = DateTime::createFromFormat($date_format, $record['last_sequence']);
            $date_errors = DateTime::getLastErrors();
            if ($date === FALSE || ($date_errors !== FALSE && ($date_errors['warning_count'] || $date_errors['error_count']))) {
                $errors[] = 'last_sequence_invalid';
            }
        }
        $valid_options = array();
        foreach ($repeat_type_options as $option) {
            if (isset($option['option_code'])) {
                $valid_options[] = (string) $option['option_code'];
            }
        }
        if ($record['repeat_type'] !== '' && !in_array($record['repeat_type'], $valid_options, TRUE)) {
            $errors[] = 'repeat_type_invalid';
        }
        return array_values(array_unique($errors));
    }

    private function sequence_uses_date_only($spec)
    {
        $sql_type = isset($spec['sql_type']) ? strtolower($spec['sql_type']) : '';
        return preg_match('/^date(?:\\s|$|\\()/', $sql_type) === 1
            && !preg_match('/^(?:datetime|timestamp)\\b/', $sql_type);
    }

    private function within_integer_range($value, $spec)
    {
        $sql_type = isset($spec['sql_type']) ? strtolower($spec['sql_type']) : '';
        if (!preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)\\b/', $sql_type, $match)) {
            return TRUE;
        }
        $maxima = array(
            'tinyint' => '127', 'smallint' => '32767', 'mediumint' => '8388607',
            'int' => '2147483647', 'integer' => '2147483647', 'bigint' => '9223372036854775807',
        );
        $max = $maxima[$match[1]];
        $unsigned = strpos($sql_type, 'unsigned') !== FALSE;
        $minimum = $unsigned ? '0' : '-' . (string) ((int) $max + 1);
        if ($match[1] === 'bigint' && !$unsigned) {
            $minimum = '-9223372036854775808';
        }
        if ($unsigned) {
            $max = array(
                'tinyint' => '255', 'smallint' => '65535', 'mediumint' => '16777215',
                'int' => '4294967295', 'integer' => '4294967295', 'bigint' => '18446744073709551615',
            )[$match[1]];
        }
        return $this->compare_integer_strings($value, $minimum) >= 0
            && $this->compare_integer_strings($value, $max) <= 0;
    }

    private function compare_integer_strings($left, $right)
    {
        $left = (string) $left;
        $right = (string) $right;
        $left_negative = isset($left[0]) && $left[0] === '-';
        $right_negative = isset($right[0]) && $right[0] === '-';
        if ($left_negative !== $right_negative) {
            return $left_negative ? -1 : 1;
        }
        $left_digits = ltrim($left_negative ? substr($left, 1) : $left, '0');
        $right_digits = ltrim($right_negative ? substr($right, 1) : $right, '0');
        $left_digits = $left_digits === '' ? '0' : $left_digits;
        $right_digits = $right_digits === '' ? '0' : $right_digits;
        $comparison = strlen($left_digits) === strlen($right_digits)
            ? strcmp($left_digits, $right_digits)
            : (strlen($left_digits) < strlen($right_digits) ? -1 : 1);
        return $left_negative ? -$comparison : $comparison;
    }
}
