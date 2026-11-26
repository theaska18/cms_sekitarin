<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Authenticated CRUD controller for system Parameters. */
class Admin_parameter extends Admin
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('finance_filter_panel');
        $this->load->library('finance_list_table');
    }

    public function parameter_index()
    {
        $menu_access = $this->has_admin_menu_code('sys_parameter');
        $add_allowed = $menu_access && $this->has_admin_access_code('sys_parameter_add');
        $update_allowed = $menu_access && $this->has_admin_access_code('sys_parameter_update');
        $delete_allowed = $menu_access && $this->has_admin_access_code('sys_parameter_delete');
        $actions_available = $update_allowed || $delete_allowed;
        $filters = array('search' => $this->parameter_filter($this->input->get('search', TRUE)));
        $per_page = $this->parameter_page_size();
        $page = $this->parameter_page_number();
        $sort = $this->parameter_sort_field($this->input->get('sort', TRUE));
        $direction = $this->parameter_sort_direction($this->input->get('direction', TRUE));
        $store = $menu_access ? $this->parameter_store() : NULL;
        $backend_available = $menu_access && $store !== NULL && $store->schema_available();
        $total_records = 0;
        $parameter_records = array();
        if ($backend_available) {
            $total_records = $store->count($filters);
            if ($total_records === FALSE) {
                $backend_available = FALSE;
                $total_records = 0;
            } else {
                $page_count = max(1, (int) ceil($total_records / $per_page));
                $page = min($page, $page_count);
                $parameter_records = $store->page($filters, $per_page, ($page - 1) * $per_page, $sort, $direction);
                if ($parameter_records === FALSE) {
                    $backend_available = FALSE;
                    $parameter_records = array();
                    $total_records = 0;
                }
            }
        }
        $this->render_admin('admin/parameter/index', 'parameter', '/admin/system/parameter', array(
            'filters' => $filters,
            'search' => $filters['search'],
            'per_page' => $per_page,
            'per_page_choices' => array(10, 25, 50),
            'sort' => $sort,
            'direction' => $direction,
            'current_page' => $page,
            'page_count' => max(1, (int) ceil($total_records / $per_page)),
            'total_records' => $total_records,
            'parameter_records' => $parameter_records,
            'menu_access' => $menu_access,
            'add_allowed' => $add_allowed,
            'update_allowed' => $update_allowed,
            'delete_allowed' => $delete_allowed,
            'actions_available' => $actions_available,
            'backend_available' => $backend_available,
            'write_available' => $this->parameter_write_available($store),
            'status_key' => (string) $this->session->flashdata('admin_parameter_status'),
            'finance_filter_config' => $this->parameter_filter_config(),
        ));
    }

    public function parameter_data()
    {
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code('sys_parameter')) {
            $this->datatable_server->error(403);
            return;
        }
        $store = $this->parameter_store();
        if ($store === NULL || !$store->schema_available()) {
            $this->datatable_server->error(503);
            return;
        }
        $this->datatable_server->respond(
            array(1 => 'parameter_code', 2 => 'parameter_name', 3 => 'description', 4 => 'summary'),
            function () use ($store) {
                return $store->count($this->parameter_list_filters(''));
            },
            function ($search) use ($store) {
                return $store->count($this->parameter_list_filters($search));
            },
            function ($search, $length, $start, $order, $direction) use ($store) {
                return $store->page(
                    $this->parameter_list_filters($search),
                    $length,
                    $start,
                    in_array($order, array('parameter_code', 'parameter_name'), TRUE) ? $order : 'parameter_code',
                    strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'
                );
            },
            array('max_length' => 200, 'max_search_length' => 120, 'default_order' => 'parameter_code')
        );
    }

    private function parameter_filter_config()
    {
        $labels = $this->lang->language;
        return $this->finance_filter_panel->config(array(
            'date' => FALSE,
            'date_preset' => 'none',
            'status_preset' => 'none',
            'target' => '#parameter-data-table',
            'field_filters' => array(
                array('type' => 'text', 'name' => 'parameter_code', 'label' => isset($labels['parameter_code_label']) ? $labels['parameter_code_label'] : 'Code'),
                array('type' => 'text', 'name' => 'parameter_name', 'label' => isset($labels['parameter_name_label']) ? $labels['parameter_name_label'] : 'Name'),
                array('type' => 'text', 'name' => 'description', 'label' => isset($labels['parameter_description_label']) ? $labels['parameter_description_label'] : 'Description'),
                array('type' => 'text', 'name' => 'summary', 'label' => isset($labels['parameter_summary_label']) ? $labels['parameter_summary_label'] : 'Summary'),
            ),
        ));
    }

    private function parameter_list_filters($search = '')
    {
        $panel = $this->finance_filter_panel->filters($this->parameter_filter_config());
        return array(
            'search' => $this->parameter_filter($search),
            'fields' => $panel['fields'],
        );
    }

    public function parameter_new()
    {
        $menu_access = $this->has_admin_menu_code('sys_parameter');
        $form_access_denied = !$menu_access || !$this->has_admin_access_code('sys_parameter_add');
        if ($form_access_denied) {
            $this->render_form('new', '', array(), array($this->empty_option()), array(), FALSE, FALSE, NULL, TRUE, $menu_access ? 'parameter_add_denied' : 'parameter_no_access');
            return;
        }
        $store = $this->parameter_store();
        $backend_available = $store !== NULL && $store->schema_available();
        $write_available = $this->parameter_write_available($store);
        $record = array('parameter_code' => '', 'parameter_name' => '', 'description' => '');
        $options = array($this->empty_option());
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Parameter writes are unavailable.', 503);
                return;
            }
            $record = $this->parameter_form_values(TRUE);
            $options = $this->normalize_options();
            $errors = $this->validate_values($record, $options, $store, TRUE, NULL);
            if (!$errors && $store->create($record, $options)) {
                $this->session->set_flashdata('admin_parameter_status', 'created');
                redirect('/admin/system/parameter', 'location', 303);
                return;
            }
            if (!$errors) {
                $errors[] = 'save_error';
            }
        }
        $this->render_form('new', '', $record, $options, $errors, $backend_available, $write_available, $store, FALSE, '', FALSE);
    }

    public function parameter_change($parameter_code)
    {
        if (!$this->is_valid_code($parameter_code)) {
            show_404();
            return;
        }
        $menu_access = $this->has_admin_menu_code('sys_parameter');
        $form_access_denied = !$menu_access || !$this->has_admin_access_code('sys_parameter_update');
        $delete_denied = $menu_access && !$this->has_admin_access_code('sys_parameter_delete');
        if ($form_access_denied) {
            $this->render_form('edit', $parameter_code, array(), array($this->empty_option()), array(), FALSE, FALSE, NULL, TRUE, $menu_access ? 'parameter_update_denied' : 'parameter_no_access', $delete_denied);
            return;
        }
        $store = $this->parameter_store();
        $backend_available = $store !== NULL && $store->schema_available();
        $write_available = $this->parameter_write_available($store);
        $record = $backend_available ? $store->find($parameter_code) : NULL;
        if ($record === FALSE) {
            $backend_available = FALSE;
            $record = NULL;
        }
        if ($backend_available && $record === NULL) {
            show_404();
            return;
        }
        $options = $record && !empty($record['options']) ? $record['options'] : array($this->empty_option());
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available || !$backend_available) {
                show_error('Parameter writes are unavailable.', 503);
                return;
            }
            $values = $this->parameter_form_values(FALSE);
            $submitted_options = $this->normalize_options();
            $errors = $this->validate_values($values, $submitted_options, $store, FALSE, $parameter_code);
            if (!$errors && $store->update($parameter_code, $values, $submitted_options)) {
                $this->session->set_flashdata('admin_parameter_status', 'saved');
                redirect('/admin/system/parameter', 'location', 303);
                return;
            }
            if (!$errors) {
                $errors[] = 'save_error';
            }
            $record = array_merge($record, $values);
            $options = $submitted_options ? $submitted_options : array($this->empty_option());
        }
        $this->render_form('edit', $parameter_code, $record, $options, $errors, $backend_available, $write_available, $store, FALSE, '', $delete_denied);
    }

    public function parameter_delete()
    {
        if (!$this->has_admin_menu_code('sys_parameter') || !$this->has_admin_access_code('sys_parameter_delete')) {
            show_error('You do not have access to Parameters.', 403);
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $store = $this->parameter_store();
        if (!$this->parameter_write_available($store)) {
            show_error('Parameter writes are unavailable.', 503);
            return;
        }
        $parameter_code = $this->input->post('parameter_code', TRUE);
        if (!$this->is_valid_code($parameter_code)) {
            $this->session->set_flashdata('admin_parameter_status', 'invalid');
        } else {
            $result = $store->delete($parameter_code);
            $this->session->set_flashdata('admin_parameter_status', $result === 'deleted' ? 'deleted' : ($result === 'not_found' ? 'not_found' : 'delete_error'));
        }
        redirect('/admin/system/parameter', 'location', 303);
    }

    private function render_form($mode, $parameter_code, $record, $options, $errors, $backend_available, $write_available, $store, $form_access_denied = FALSE, $form_access_message_key = '', $delete_denied = FALSE)
    {
        $path = $mode === 'new' ? '/admin/system/parameter/new' : '/admin/system/parameter/change/' . rawurlencode($parameter_code);
        $this->render_admin('admin/parameter/form', 'parameter', $path, array(
            'parameter_mode' => $mode,
            'parameter_code' => $parameter_code,
            'parameter_record' => $record,
            'parameter_options' => $options,
            'parameter_errors' => $errors,
            'parameter_fields' => $store ? $store->fields() : array(),
            'backend_available' => $backend_available,
            'write_available' => $write_available,
            'form_access_denied' => $form_access_denied,
            'form_access_message_key' => $form_access_message_key,
            'delete_denied' => $delete_denied,
        ));
    }

    private function parameter_store()
    {
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
            return NULL;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('parameter_store');
        return $this->parameter_store;
    }

    private function parameter_write_available($store)
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development'
            && $this->current_user !== NULL
            && (bool) config_item('csrf_protection')
            && $store !== NULL
            && $store->schema_available();
    }

    private function parameter_form_values($include_code)
    {
        $names = $include_code ? array('parameter_code', 'parameter_name', 'description') : array('parameter_name', 'description');
        $values = array();
        foreach ($names as $name) {
            $value = $this->input->post($name, FALSE);
            $values[$name] = is_string($value) ? trim($value) : '';
            if ($name === 'parameter_code') {
                $values[$name] = strtolower(preg_replace('/\s+/', '', $values[$name]));
            }
        }
        return $values;
    }

    private function normalize_options()
    {
        $posted = $this->input->post('options', FALSE);
        if (!is_array($posted)) {
            return array();
        }
        $options = array();
        foreach (array_slice(array_values($posted), 0, 500) as $option) {
            if (!is_array($option)) {
                continue;
            }
            $code = isset($option['option_code']) && is_string($option['option_code']) ? $option['option_code'] : '';
            $name = isset($option['option_name']) && is_string($option['option_name']) ? $option['option_name'] : '';
            $options[] = array(
                'option_code' => strtolower(preg_replace('/\s+/', '', trim($code))),
                'option_name' => trim($name),
                'active_flag' => isset($option['active_flag']) && in_array((string) $option['active_flag'], array('1', 'on', 'yes'), TRUE) ? 1 : 0,
            );
        }
        return $options;
    }

    private function validate_values($record, $options, $store, $include_code, $exclude_parameter_code)
    {
        $errors = array();
        if ($include_code && !$this->is_valid_code(isset($record['parameter_code']) ? $record['parameter_code'] : '')) {
            $errors[] = 'invalid';
        }
        if (trim(isset($record['parameter_name']) ? $record['parameter_name'] : '') === '') {
            $errors[] = 'name_required';
        }
        if (count($options) < 1) {
            $errors[] = 'option_minimum';
        }
        $fields = $store->fields();
        foreach (array('parameter_code', 'parameter_name', 'description') as $field) {
            if (!array_key_exists($field, $record) || !isset($fields[$field])) {
                continue;
            }
            $max = (int) $fields[$field]['max_length'];
            if ($max > 0 && strlen($record[$field]) > $max) {
                $errors[] = 'invalid';
            }
        }
        $codes = array();
        foreach ($options as $option) {
            if (!$this->is_valid_code($option['option_code']) || $option['option_name'] === '') {
                $errors[] = 'invalid';
            }
            foreach (array('option_code', 'option_name') as $field) {
                $max = isset($fields[$field]['max_length']) ? (int) $fields[$field]['max_length'] : 0;
                if ($max > 0 && strlen($option[$field]) > $max) {
                    $errors[] = 'invalid';
                }
            }
            if ($option['option_code'] !== '') {
                if (in_array($option['option_code'], $codes, TRUE)) {
                    $errors[] = 'option_duplicate';
                }
                $codes[] = $option['option_code'];
            }
        }
        if ($codes && $store->option_codes_available($codes, $exclude_parameter_code) !== TRUE) {
            $errors[] = 'option_duplicate';
        }
        return array_values(array_unique($errors));
    }

    private function empty_option()
    {
        return array('option_code' => '', 'option_name' => '', 'active_flag' => 1);
    }

    private function parameter_filter($value)
    {
        return is_string($value) ? substr(trim($value), 0, 120) : '';
    }

    private function parameter_sort_field($value)
    {
        return is_string($value) && in_array($value, array('parameter_code', 'parameter_name'), TRUE)
            ? $value
            : 'parameter_code';
    }

    private function parameter_sort_direction($value)
    {
        return is_string($value) && strtoupper($value) === 'DESC' ? 'DESC' : 'ASC';
    }

    private function parameter_page_size()
    {
        $requested = $this->input->get('per_page', TRUE);
        if (!is_string($requested) || !preg_match('/^\\d{1,9}$/', $requested)) {
            return 10;
        }
        $requested = (int) $requested;
        if ($requested > 50) { return 50; }
        if ($requested <= 10) { return 10; }
        return $requested <= 25 ? 25 : 50;
    }

    private function parameter_page_number()
    {
        $requested = $this->input->get('page', TRUE);
        return is_string($requested) && preg_match('/^\\d{1,10}$/', $requested) ? max(1, (int) $requested) : 1;
    }

    private function is_valid_code($code)
    {
        return is_string($code) && preg_match('/^[a-z0-9][a-z0-9_.-]{0,31}$/', $code) === 1;
    }
}
