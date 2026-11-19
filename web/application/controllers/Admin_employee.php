<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Tenant-scoped employee management under the HR menu. */
class Admin_employee extends Admin
{
    private $employee_store_instance;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('finance_filter_panel');
        $this->load->library('finance_list_table');
    }

    public function employee_index()
    {
        if (!$this->has_admin_menu_code('hr_employee')) { $this->employee_access_denied(); return; }
        $store = $this->employee_store();
        $filter_mode = $this->input->get('filter_mode', TRUE) === 'columns' ? 'columns' : 'search';
        $search = $filter_mode === 'search' ? trim((string) $this->input->get('search', TRUE)) : '';
        if (strlen($search) > 120) { $search = substr($search, 0, 120); }
        $filter_code = $filter_mode === 'columns' ? trim((string) $this->input->get('employee_code', TRUE)) : '';
        $filter_name = $filter_mode === 'columns' ? trim((string) $this->input->get('employee_name', TRUE)) : '';
        if (strlen($filter_code) > 80) { $filter_code = substr($filter_code, 0, 80); }
        if (strlen($filter_name) > 120) { $filter_name = substr($filter_name, 0, 120); }
        $sort = $this->input->get('sort', TRUE);
        if (!in_array($sort, array('employee_code', 'employee_name'), TRUE)) { $sort = 'employee_name'; }
        $direction = strtoupper((string) $this->input->get('direction', TRUE)) === 'DESC' ? 'DESC' : 'ASC';
        $per_page = $this->employee_page_size();
        $page = $this->employee_page_number();
        $tenant_id = isset($this->current_user['tenant_id']) ? (int) $this->current_user['tenant_id'] : 0;
        $available = $store !== NULL && $store->schema_available();
        $total = 0;
        $records = array();
        if ($available) {
            $total = $store->count($search, $tenant_id, $filter_code, $filter_name);
            if ($total === FALSE) { $available = FALSE; $total = 0; }
            else {
                $page_count = max(1, (int) ceil($total / $per_page));
                $page = min($page, $page_count);
                $records = $store->page($search, $per_page, ($page - 1) * $per_page, $sort, $direction, $tenant_id, $filter_code, $filter_name);
                if ($records === FALSE) { $available = FALSE; $records = array(); $total = 0; }
            }
        }
        $this->render_admin('admin/employee/index', 'employee_heading', '/admin/hr/employee', array(
            'employee_records' => $records,
            'employee_search' => $search,
            'employee_filter_mode' => $filter_mode,
            'employee_filter_code' => $filter_code,
            'employee_filter_name' => $filter_name,
            'employee_sort' => $sort,
            'employee_direction' => $direction,
            'employee_total' => $total,
            'employee_page' => $page,
            'employee_per_page' => $per_page,
            'employee_page_count' => max(1, (int) ceil($total / $per_page)),
            'employee_available' => $available,
            'employee_write_available' => $this->employee_write_available($store),
            'employee_can_add' => $this->has_admin_access_code('hr_employee_add'),
            'employee_can_update' => $this->has_admin_access_code('hr_employee_update'),
            'employee_can_delete' => $this->has_admin_access_code('hr_employee_delete'),
            'employee_status' => (string) $this->session->flashdata('admin_employee_status'),
            'finance_filter_config' => $this->employee_filter_config($store),
        ));
    }

    public function employee_data()
    {
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code('hr_employee')) {
            $this->datatable_server->error(403);
            return;
        }
        $store = $this->employee_store();
        $tenant_id = isset($this->current_user['tenant_id']) ? (int) $this->current_user['tenant_id'] : 0;
        if ($store === NULL || !$store->schema_available() || $tenant_id < 1) {
            $this->datatable_server->error(503);
            return;
        }
        $state = $this->employee_list_state('');
        $this->datatable_server->respond(
            array(1 => 'employee_code', 2 => 'employee_name'),
            function () use ($store, $tenant_id, $state) {
                return $store->count($state['search'], $tenant_id, '', '', $state['filters']);
            },
            function ($search) use ($store, $tenant_id) {
                $state = $this->employee_list_state($search);
                return $store->count($state['search'], $tenant_id, '', '', $state['filters']);
            },
            function ($search, $length, $start, $order, $direction) use ($store, $tenant_id) {
                $state = $this->employee_list_state($search);
                return $store->page($state['search'], $length, $start, $order, $direction, $tenant_id, '', '', $state['filters']);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'employee_name')
        );
    }

    private function employee_filter_config($store = NULL)
    {
        $labels = $this->lang->language;
        $genders = array();
        if ($store !== NULL) {
            $rows = $store->gender_options();
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $genders[(string) $row['option_code']] = (string) $row['option_name'];
                }
            }
        }
        return $this->finance_filter_panel->config(array(
            'date' => FALSE,
            'date_preset' => 'none',
            'status_preset' => 'none',
            'target' => '#employee-data-table',
            'field_filters' => array(
                array('type' => 'text', 'name' => 'employee_code', 'label' => isset($labels['employee_code_label']) ? $labels['employee_code_label'] : 'Code'),
                array('type' => 'text', 'name' => 'employee_name', 'label' => isset($labels['employee_name_label']) ? $labels['employee_name_label'] : 'Name'),
                array('type' => 'text', 'name' => 'identity_id', 'label' => isset($labels['employee_identity_label']) ? $labels['employee_identity_label'] : 'Identity'),
                array('type' => 'select', 'name' => 'gender', 'label' => isset($labels['employee_gender_label']) ? $labels['employee_gender_label'] : 'Gender', 'options' => $genders),
                array('type' => 'text', 'name' => 'birth_place', 'label' => isset($labels['employee_birth_place_label']) ? $labels['employee_birth_place_label'] : 'Birth place'),
                array('type' => 'range', 'name' => 'age', 'label' => isset($labels['employee_age_label']) ? $labels['employee_age_label'] : 'Age', 'min_name' => 'age_min', 'max_name' => 'age_max'),
            ),
        ));
    }

    private function employee_list_state($search = '')
    {
        $store = $this->employee_store();
        $panel = $this->finance_filter_panel->filters($this->employee_filter_config($store));
        $fields = $panel['fields'];
        return array(
            'search' => substr(trim((string) $search), 0, 120),
            'filters' => array(
                'employee_code' => isset($fields['employee_code']) ? $fields['employee_code'] : '',
                'employee_name' => isset($fields['employee_name']) ? $fields['employee_name'] : '',
                'identity_id' => isset($fields['identity_id']) ? $fields['identity_id'] : '',
                'gender' => isset($fields['gender']) ? $fields['gender'] : '',
                'birth_place' => isset($fields['birth_place']) ? $fields['birth_place'] : '',
                'age' => isset($fields['age']) && is_array($fields['age']) ? $fields['age'] : array('min' => '', 'max' => ''),
            ),
        );
    }

    public function employee_new()
    {
        $this->employee_form(NULL);
    }

    public function employee_change($employee_id)
    {
        if (!ctype_digit((string) $employee_id) || (int) $employee_id < 1) { show_404(); return; }
        $this->employee_form((int) $employee_id);
    }

    public function employee_delete()
    {
        if (!$this->has_admin_access_code('hr_employee_delete')) { $this->employee_access_denied(); return; }
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $store = $this->employee_store();
        if (!$this->employee_write_available($store)) { show_error('Employee writes are unavailable.', 503); return; }
        $id = $this->input->post('employee_id', TRUE);
        $tenant_id = (int) $this->current_user['tenant_id'];
        $old = ctype_digit((string) $id) ? $store->find($id, $tenant_id) : NULL;
        if (!$old || !$store->delete((int) $id, $tenant_id)) {
            $this->session->set_flashdata('admin_employee_status', 'delete_error');
        } else {
            $this->employee_remove_image(isset($old['image']) ? $old['image'] : NULL);
            $this->session->set_flashdata('admin_employee_status', 'deleted');
        }
        redirect('/admin/hr/employee', 'location', 303);
    }

    /** Serve the signed-in user's own employee photo without requiring the HR menu. */
    public function employee_profile_photo($filename)
    {
        if (!is_string($filename) || !preg_match('/\\A[A-Za-z0-9_-]+\\.(?:jpe?g|png|gif|webp)\\z/i', $filename)) { show_404(); return; }
        $employee_id = isset($this->current_user['employee_id']) ? (int) $this->current_user['employee_id'] : 0;
        $employee_record = NULL;
        if ($employee_id > 0) {
            $this->load->library('employee_store');
            if ($this->employee_store->schema_available()) { $employee_record = $this->employee_store->find($employee_id, (int) $this->current_user['tenant_id']); }
        }
        if (!$employee_record || !isset($employee_record['image']) || !hash_equals((string) $employee_record['image'], $filename)) { show_404(); return; }
        $path = $this->employee_store->image_path($filename);
        $image = $path && is_file($path) ? @getimagesize($path) : FALSE;
        if (!$image || !in_array($image['mime'], array('image/jpeg', 'image/png', 'image/gif', 'image/webp'), TRUE)) { show_404(); return; }
        $this->output->set_header('Cache-Control: private, no-store, max-age=0');
        $this->output->set_header('X-Content-Type-Options: nosniff');
        $this->output->set_content_type($image['mime'])->set_output(file_get_contents($path));
    }

    /** Serve photos only after normal admin and tenant authorization. */
    public function employee_photo($employee_id)
    {
        if (!$this->has_admin_menu_code('hr_employee')) { show_404(); return; }
        $store = $this->employee_store();
        $tenant_id = isset($this->current_user['tenant_id']) ? (int) $this->current_user['tenant_id'] : 0;
        $employee = $store && $store->schema_available() ? $store->find($employee_id, $tenant_id) : NULL;
        $path = $employee ? $store->image_path(isset($employee['image']) ? $employee['image'] : NULL) : FALSE;
        $image = $path && is_file($path) ? @getimagesize($path) : FALSE;
        if (!$image || !in_array($image['mime'], array('image/jpeg', 'image/png', 'image/gif', 'image/webp'), TRUE)) { show_404(); return; }
        $this->output->set_header('Cache-Control: private, no-store, max-age=0');
        $this->output->set_header('X-Content-Type-Options: nosniff');
        $this->output->set_content_type($image['mime'])->set_output(file_get_contents($path));
    }

    private function employee_form($employee_id)
    {
        $access = $employee_id === NULL ? 'hr_employee_add' : 'hr_employee_update';
        if (!$this->has_admin_access_code($access)) { $this->employee_access_denied(); return; }
        $store = $this->employee_store();
        $tenant_id = (int) $this->current_user['tenant_id'];
        $write_available = $this->employee_write_available($store);
        $employee_auto_numbered = FALSE;
        $application_sequence_store = NULL;
        $record = array(
            'employee_code' => '', 'identity_id' => '', 'employee_name' => '',
            'email_address' => '', 'gender' => '', 'birth_date' => '', 'birth_place' => '', 'image' => NULL,
        );
        $errors = array();
        if ($employee_id === NULL && $store !== NULL && $store->schema_available()) {
            $this->load->library('application_sequence_store');
            $application_sequence_store = $this->application_sequence_store;
            $employee_sequence = $application_sequence_store->sequence_config($tenant_id, 'employee_code');
            if (is_array($employee_sequence) && (string) $employee_sequence['use_flag'] === '1') {
                $employee_auto_numbered = TRUE;
                $preview = $application_sequence_store->preview_code($tenant_id, 'employee_code');
                if (is_array($preview)) {
                    $record['employee_code'] = $preview['code'];
                } else {
                    $errors[] = 'sequence_generation';
                }
            }
        }
        if ($employee_id !== NULL) {
            $existing = $store && $store->schema_available() ? $store->find($employee_id, $tenant_id) : FALSE;
            if (!$existing) { show_404(); return; }
            foreach ($record as $field => $value) { $record[$field] = isset($existing[$field]) ? (string) $existing[$field] : $value; }
        }
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) { show_error('Employee writes are unavailable.', 503); return; }
            foreach (array_keys($record) as $field) {
                if ($field === 'image') { continue; }
                if ($field === 'employee_code' && $employee_id === NULL && $employee_auto_numbered) { continue; }
                $record[$field] = trim((string) $this->input->post($field, TRUE));
            }
            $record['employee_name'] = $this->employee_title_case($record['employee_name']);
            $specs = $store->field_specs();
            foreach (array('employee_code', 'identity_id', 'employee_name', 'email_address', 'gender', 'birth_date', 'birth_place') as $field) {
                if ($field === 'employee_code' && $employee_id === NULL && $employee_auto_numbered) { continue; }
                if ($record[$field] === '') { $errors[] = $field . '_required'; }
                $max = isset($specs[$field]['max_length']) ? (int) $specs[$field]['max_length'] : 0;
                $length = function_exists('mb_strlen') ? mb_strlen($record[$field], 'UTF-8') : strlen($record[$field]);
                if ($max > 0 && $length > $max) { $errors[] = $field . '_too_long'; }
            }
            if ($record['email_address'] !== '' && !filter_var($record['email_address'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'email_invalid'; }
            $valid_date = $this->employee_valid_date($record['birth_date']);
            if (!$valid_date) { $errors[] = 'birth_date_invalid'; }
            elseif ($record['birth_date'] > date('Y-m-d')) { $errors[] = 'birth_date_future'; }
            $valid_gender = FALSE;
            foreach ($store->gender_options() as $option) {
                if (isset($option['option_code']) && hash_equals((string) $option['option_code'], $record['gender'])) { $valid_gender = TRUE; break; }
            }
            if (!$valid_gender) { $errors[] = 'gender_invalid'; }
            if (!($employee_id === NULL && $employee_auto_numbered)
                && $record['employee_code'] !== '' && $store->code_exists($record['employee_code'], $tenant_id, $employee_id ?: 0)) {
                $errors[] = 'employee_code_duplicate';
            }

            $new_image = NULL;
            $upload = isset($_FILES['image']) ? $_FILES['image'] : NULL;
            $has_upload = $upload && isset($upload['error']) && $upload['error'] !== UPLOAD_ERR_NO_FILE;
            if ($has_upload && !$errors) {
                $upload_dir = APPPATH . 'cache/employee-images/';
                if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0750, TRUE) && !is_dir($upload_dir)) { $errors[] = 'image_invalid'; }
                elseif (!is_writable($upload_dir)) { $errors[] = 'image_invalid'; }
                else {
                    $this->load->library('upload');
                    $config = array(
                        'upload_path' => $upload_dir,
                        'allowed_types' => 'jpg|jpeg|png|gif|webp',
                        'max_size' => 2048,
                        'max_width' => 5000,
                        'max_height' => 5000,
                        'file_ext_tolower' => TRUE,
                        'encrypt_name' => TRUE,
                        'remove_spaces' => TRUE,
                    );
                    $this->upload->initialize($config, TRUE);
                    if (!$this->upload->do_upload('image')) { $errors[] = 'image_invalid'; }
                    else {
                        $uploaded = $this->upload->data();
                        $new_image = basename($uploaded['file_name']);
                        $image_path = $store->image_path($new_image);
                        $image_meta = $image_path ? @getimagesize($image_path) : FALSE;
                        if (!$image_meta || !in_array($image_meta['mime'], array('image/jpeg', 'image/png', 'image/gif', 'image/webp'), TRUE)) {
                            if ($image_path && is_file($image_path)) { @unlink($image_path); }
                            $new_image = NULL;
                            $errors[] = 'image_invalid';
                        } else { $record['image'] = $new_image; }
                    }
                }
            }

            if (!$errors) {
                $ok = FALSE;
                if ($employee_id === NULL && $employee_auto_numbered && $application_sequence_store !== NULL) {
                    $previous_db_debug = $this->db->db_debug;
                    $this->db->db_debug = FALSE;
                    $this->db->trans_begin();
                    $next = $application_sequence_store->next_code_for_update($tenant_id, 'employee_code');
                    if (!is_array($next)) {
                        $this->db->trans_rollback();
                        $errors[] = 'sequence_generation';
                    } else {
                        $max_code_length = isset($specs['employee_code']['max_length']) ? (int) $specs['employee_code']['max_length'] : 0;
                        $available_code = FALSE;
                        for ($attempt = 0; is_array($next) && $attempt < 1000; $attempt++) {
                            $record['employee_code'] = $next['code'];
                            $code_length = function_exists('mb_strlen')
                                ? mb_strlen($record['employee_code'], 'UTF-8')
                                : strlen($record['employee_code']);
                            if ($max_code_length > 0 && $code_length > $max_code_length) {
                                $errors[] = 'employee_code_too_long';
                                break;
                            }
                            if (!$store->code_exists($record['employee_code'], $tenant_id)) {
                                $available_code = TRUE;
                                break;
                            }
                            $next = $application_sequence_store->next_code_after($next);
                        }
                        if (!$errors && !$available_code) {
                            $errors[] = 'sequence_exhausted';
                        }
                        if ($errors) {
                            $this->db->trans_rollback();
                        } else {
                            $inserted = $store->insert($record, $tenant_id);
                            $advanced = $inserted && $application_sequence_store->advance_sequence(
                                $tenant_id,
                                'employee_code',
                                $next
                            );
                            if ($inserted && $advanced && $this->db->trans_status() !== FALSE) {
                                $this->db->trans_commit();
                                $ok = TRUE;
                            } else {
                                $this->db->trans_rollback();
                                $errors[] = 'save_error';
                            }
                        }
                    }
                    $this->db->db_debug = $previous_db_debug;
                } else {
                    $ok = $employee_id === NULL
                        ? $store->insert($record, $tenant_id)
                        : $store->update($employee_id, $tenant_id, $record);
                    if (!$ok) { $errors[] = 'save_error'; }
                }
                if ($ok) {
                    if ($employee_id !== NULL && isset($existing['image']) && $existing['image'] !== $record['image']) { $this->employee_remove_image($existing['image']); }
                    $this->session->set_flashdata('admin_employee_status', $employee_id === NULL ? 'created' : 'saved');
                    redirect('/admin/hr/employee', 'location', 303);
                    return;
                }
                if ($new_image !== NULL) { $this->employee_remove_image($new_image); }
            }
        }
        $this->render_admin('admin/employee/form', 'employee_heading', $employee_id === NULL ? '/admin/hr/employee/new' : '/admin/hr/employee/change/' . $employee_id, array(
            'employee_mode' => $employee_id === NULL ? 'new' : 'edit',
            'employee_auto_numbered' => $employee_id === NULL && $employee_auto_numbered,
            'employee_id' => $employee_id,
            'employee_record' => $record,
            'employee_errors' => $errors,
            'employee_gender_options' => $store ? $store->gender_options() : array(),
            'employee_available' => $store !== NULL && $store->schema_available(),
            'employee_write_available' => $write_available,
        ));
    }

    /** Uppercase the first character of each word while preserving the rest. */
    private function employee_title_case($value)
    {
        return preg_replace_callback('/(^|\s)(\S)/u', function ($matches) {
            $first = function_exists('mb_strtoupper')
                ? mb_strtoupper($matches[2], 'UTF-8')
                : strtoupper($matches[2]);
            return $matches[1] . $first;
        }, (string) $value);
    }

    private function employee_valid_date($value)
    {
        if (!is_string($value) || !preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value)) { return FALSE; }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        $errors = DateTime::getLastErrors();
        return $date && $date->format('Y-m-d') === $value && ($errors === FALSE || (!$errors['warning_count'] && !$errors['error_count']));
    }

    private function employee_remove_image($filename)
    {
        $store = $this->employee_store();
        $path = $store ? $store->image_path($filename) : FALSE;
        if ($path && is_file($path)) { @unlink($path); }
    }

    private function employee_store()
    {
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') { return NULL; }
        if ($this->employee_store_instance === NULL) {
            $this->load->database();
            $this->load->library('employee_store');
            $this->employee_store_instance = $this->employee_store;
        }
        return $this->employee_store_instance;
    }

    private function employee_write_available($store)
    {
        $csrf_enabled = isset($this->config) && $this->config->item('csrf_protection');
        return $store !== NULL && $store->writes_supported() && $csrf_enabled;
    }

    private function employee_page_size()
    {
        $value = (int) $this->input->get('per_page', TRUE);
        return in_array($value, array(10, 25, 50), TRUE) ? $value : 10;
    }

    private function employee_page_number()
    {
        $value = (int) $this->input->get('page', TRUE);
        return max(1, min(100000, $value));
    }

    private function employee_access_denied()
    {
        $this->output->set_status_header(403);
        $this->render_admin('admin/employee/access_denied', 'employee_heading', '/admin/hr/employee');
    }
}
