<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Authenticated CRUD controller for system Parameters. */
class Admin_parameter extends Admin
{
    public function parameter_index()
    {
        $filters = array('search' => $this->parameter_filter($this->input->get('search', TRUE)));
        $per_page = $this->parameter_page_size();
        $page = $this->parameter_page_number();
        $sort = $this->parameter_sort_field($this->input->get('sort', TRUE));
        $direction = $this->parameter_sort_direction($this->input->get('direction', TRUE));
        $store = $this->parameter_store();
        $backend_available = $store !== NULL && $store->schema_available();
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
            'backend_available' => $backend_available,
            'write_available' => $this->parameter_write_available($store),
            'status_key' => (string) $this->session->flashdata('admin_parameter_status'),
        ));
    }

    public function parameter_new()
    {
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
        $this->render_form('new', '', $record, $options, $errors, $backend_available, $write_available, $store);
    }

    public function parameter_change($parameter_code)
    {
        if (!$this->is_valid_code($parameter_code)) {
            show_404();
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
        $this->render_form('edit', $parameter_code, $record, $options, $errors, $backend_available, $write_available, $store);
    }

    public function parameter_delete()
    {
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

    private function render_form($mode, $parameter_code, $record, $options, $errors, $backend_available, $write_available, $store)
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
