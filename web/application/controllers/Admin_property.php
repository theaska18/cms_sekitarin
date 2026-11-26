<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Property menu controller; inherits the authenticated admin shell. */
class Admin_property extends Admin
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('finance_filter_panel');
        $this->load->library('finance_list_table');
    }

    public function property_index()
    {
        if (!$this->has_admin_menu_code('sys_property')) {
            $this->render_property_access_denied('admin/property/index', '/admin/system/property');
            return;
        }
        $can_add = $this->has_admin_access_code('sys_property_add');
        $can_update = $this->has_admin_access_code('sys_property_update');
        $can_delete = $this->has_admin_access_code('sys_property_delete');
        $store = $this->property_store();
        $filters = array(
            'search' => $this->property_filter($this->input->get('search', TRUE)),
        );
        $sort = $this->property_sort_field($this->input->get('sort', TRUE));
        $direction = $this->property_sort_direction($this->input->get('direction', TRUE));
        $per_page = $this->property_page_size();
        $page = $this->property_page_number();
        $total_records = 0;
        $property_records = array();
        $backend_available = $store !== NULL && $store->schema_available();
        if ($backend_available) {
            $total_records = $store->count($filters);
            if ($total_records === FALSE) {
                $backend_available = FALSE;
                $total_records = 0;
            } else {
                $page_count = max(1, (int) ceil($total_records / $per_page));
                $page = min($page, $page_count);
                $property_records = $store->page($filters, $per_page, ($page - 1) * $per_page, $sort, $direction);
                if ($property_records === FALSE) {
                    $backend_available = FALSE;
                    $total_records = 0;
                    $property_records = array();
                }
            }
        }
        $page_count = max(1, (int) ceil($total_records / $per_page));
        $status = $this->session->flashdata('admin_property_status');
        $this->render_admin(
            'admin/property/index',
            'property',
            '/admin/system/property',
            array(
                'filters' => $filters,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $per_page,
                'per_page_choices' => array(50, 100, 200),
                'current_page' => $page,
                'page_count' => $page_count,
                'total_records' => $total_records,
                'property_records' => $property_records,
                'backend_available' => $backend_available,
                'write_available' => $this->property_write_available($store),
                'can_add' => $can_add,
                'can_update' => $can_update,
                'can_delete' => $can_delete,
                'status_key' => is_string($status) ? $status : '',
                'finance_filter_config' => $this->property_filter_config(),
            )
        );
    }

    private function property_filter_config()
    {
        $labels = $this->lang->language;
        return $this->finance_filter_panel->config(array(
            'date' => FALSE,
            'date_preset' => 'none',
            'status_preset' => 'none',
            'target' => '#property-data-table',
            'field_filters' => array(
                array('type' => 'text', 'name' => 'property_code', 'label' => isset($labels['property_code_label']) ? $labels['property_code_label'] : 'Code'),
                array('type' => 'text', 'name' => 'property_name', 'label' => isset($labels['property_name_label']) ? $labels['property_name_label'] : 'Name'),
                array('type' => 'text', 'name' => 'description', 'label' => isset($labels['property_description_label']) ? $labels['property_description_label'] : 'Description'),
                array('type' => 'text', 'name' => 'value', 'label' => isset($labels['property_value_label']) ? $labels['property_value_label'] : 'Value'),
            ),
        ));
    }

    private function property_list_filters($search = '')
    {
        $panel = $this->finance_filter_panel->filters($this->property_filter_config());
        return array(
            'search' => $this->property_filter($search),
            'fields' => $panel['fields'],
        );
    }

    /** DataTables server-side endpoint for the property listing. */
    public function property_data()
    {
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code('sys_property')) {
            $this->datatable_server->error(403);
            return;
        }

        $store = $this->property_store();
        if ($store === NULL || !$store->schema_available()) {
            $this->datatable_server->error(503);
            return;
        }

        $this->datatable_server->respond(
            array(1 => 'property_code', 2 => 'property_name'),
            function () use ($store) {
                return $store->count($this->property_list_filters(''));
            },
            function ($search) use ($store) {
                return $store->count($this->property_list_filters($search));
            },
            function ($search, $length, $start, $order, $direction) use ($store) {
                return $store->page(
                    $this->property_list_filters($search),
                    $length,
                    $start,
                    $this->property_sort_field($order),
                    $this->property_sort_direction($direction)
                );
            },
            array('max_length' => 200, 'max_search_length' => 120, 'default_order' => 'property_code')
        );
    }

    public function property_new()
    {
        if (!$this->has_admin_access_code('sys_property_add')) {
            $this->render_property_access_denied('admin/property/form', '/admin/system/property/new');
            return;
        }
        $store = $this->property_store();
        $backend_available = $store !== NULL && $store->schema_available();
        $write_available = $this->property_write_available($store);
        $record = array('property_code' => '', 'property_name' => '', 'description' => '', 'value' => '');
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Property writes are unavailable.', 503);
                return;
            }
            $record = $this->property_form_values(TRUE);
            $errors = $this->validate_property_values($record, $store, TRUE);
            if (!$errors) {
                if ($store->insert($record)) {
                    $this->session->set_flashdata('admin_property_status', 'created');
                    redirect('/admin/system/property', 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
        }
        $this->render_admin(
            'admin/property/form',
            'property',
            '/admin/system/property/new',
            array(
                'property_mode' => 'new',
                'property_code' => '',
                'property_record' => $record,
                'property_fields' => $store ? $store->fields() : array(),
                'property_errors' => $errors,
                'backend_available' => $backend_available,
                'write_available' => $write_available,
            )
        );
    }

    public function property_change($property_code)
    {
        if (!$this->has_admin_access_code('sys_property_update')) {
            $this->render_property_access_denied('admin/property/form', '/admin/system/property/change');
            return;
        }
        if (!$this->is_valid_property_code($property_code)) {
            show_404();
            return;
        }
        $store = $this->property_store();
        $backend_available = $store !== NULL && $store->schema_available();
        $write_available = $this->property_write_available($store);
        $record = $backend_available ? $store->find($property_code) : NULL;
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
                show_error('Property writes are unavailable.', 503);
                return;
            }
            $values = $this->property_form_values(FALSE);
            $values['property_code'] = $property_code;
            $errors = $this->validate_property_values($values, $store, FALSE);
            if (!$errors) {
                $result = $store->update($property_code, $values);
                if ($result === 'updated' || $result === 'unchanged') {
                    $this->session->set_flashdata('admin_property_status', 'saved');
                    redirect('/admin/system/property', 'location', 303);
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
            'admin/property/form',
            'property',
            '/admin/system/property/change/' . rawurlencode($property_code),
            array(
                'property_mode' => 'change',
                'property_code' => $property_code,
                'property_record' => $record,
                'property_fields' => $store ? $store->fields() : array(),
                'property_errors' => $errors,
                'backend_available' => $backend_available,
                'write_available' => $write_available,
            )
        );
    }

    public function property_delete()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_access_code('sys_property_delete')) {
            $this->render_property_access_denied('admin/property/index', '/admin/system/property');
            return;
        }
        $store = $this->property_store();
        if (!$this->property_write_available($store)) {
            show_error('Property writes are unavailable.', 503);
            return;
        }
        $property_code = $this->input->post('property_code', FALSE);
        if (!$this->is_valid_property_code($property_code)) {
            $this->session->set_flashdata('admin_property_status', 'invalid');
            redirect('/admin/system/property', 'location', 303);
            return;
        }
        $result = $store->delete($property_code);
        $this->session->set_flashdata('admin_property_status', $result === 'deleted' ? 'deleted' : ($result === 'not_found' ? 'not_found' : 'delete_error'));
        redirect('/admin/system/property', 'location', 303);
    }

    private function render_property_access_denied($view, $path)
    {
        $this->output->set_status_header(403);
        $this->render_admin($view, 'property', $path, array('access_denied' => TRUE));
    }

    private function property_store()
    {
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
            return NULL;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('property_store');
        return $this->property_store;
    }

    private function property_write_available($store)
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development'
            && $this->current_user !== NULL
            && (bool) config_item('csrf_protection')
            && $store !== NULL
            && $store->schema_available();
    }

    private function property_form_values($include_code)
    {
        $names = $include_code
            ? array('property_code', 'property_name', 'description', 'value')
            : array('property_name', 'description', 'value');
        $values = array();
        foreach ($names as $name) {
            $value = $this->input->post($name, FALSE);
            $values[$name] = is_string($value) ? trim($value) : '';
            if ($name === 'property_code') {
                $values[$name] = strtolower(preg_replace('/\s+/', '', $values[$name]));
            }
        }
        return $values;
    }

    private function validate_property_values($values, $store, $include_code)
    {
        $errors = array();
        if ($include_code && !$this->is_valid_property_code($values['property_code'])) {
            $errors[] = 'invalid';
        }
        if (!isset($values['property_name']) || trim($values['property_name']) === '') {
            $errors[] = 'name_required';
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

    private function property_filter($value)
    {
        return is_string($value) ? substr(trim($value), 0, 120) : '';
    }

    private function property_sort_field($value)
    {
        return is_string($value) && in_array($value, array('property_code', 'property_name'), TRUE)
            ? $value
            : 'property_code';
    }

    private function property_sort_direction($value)
    {
        return is_string($value) && strtoupper($value) === 'DESC' ? 'DESC' : 'ASC';
    }

    private function property_page_size()
    {
        $requested = $this->input->get('per_page', TRUE);
        if (!is_string($requested) || !preg_match('/^\d{1,9}$/', $requested)) {
            return 50;
        }
        $requested = (int) $requested;
        if ($requested > 200) {
            return 200;
        }
        if ($requested <= 50) {
            return 50;
        }
        return $requested <= 100 ? 100 : 200;
    }

    private function property_page_number()
    {
        $requested = $this->input->get('page', TRUE);
        if (!is_string($requested) || !preg_match('/^\d{1,10}$/', $requested)) {
            return 1;
        }
        return max(1, (int) $requested);
    }

    private function is_valid_property_code($property_code)
    {
        return is_string($property_code)
            && $property_code !== ''
            && strlen($property_code) <= 255
            && strpos($property_code, '/') === FALSE
            && strpos($property_code, '\\') === FALSE
            && !preg_match('/[\x00-\x1F\x7F]/', $property_code);
    }
}
