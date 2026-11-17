<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Global approval flow template administration. */
class Admin_approval_flow_template extends Admin
{
    const TEMPLATE_PATH = '/admin/system/approval_flow_template';
    const MENU_CODE = 'sys_approval_flow_template';

    public function approval_flow_template_index()
    {
        if (!$this->has_admin_menu_code(self::MENU_CODE)) {
            $this->deny_access();
            return;
        }
        $store = $this->template_store();
        $backend_available = $store !== NULL && $store->schema_available();
        $this->render_admin(
            'admin/approval_flow_template/index',
            'approval_flow_template_heading',
            self::TEMPLATE_PATH,
            array(
                'approval_flow_template_backend_available' => $backend_available,
                'approval_flow_template_write_available' => $this->write_available($store),
                'approval_flow_template_can_add' => $this->has_admin_access_code('sys_approval_flow_template_add'),
                'approval_flow_template_can_update' => $this->has_admin_access_code('sys_approval_flow_template_update'),
                'approval_flow_template_can_delete' => $this->has_admin_access_code('sys_approval_flow_template_delete'),
                'approval_flow_template_status' => (string) $this->session->flashdata('approval_flow_template_status'),
            )
        );
    }

    /** Server-side DataTables endpoint for the template list. */
    public function approval_flow_template_data()
    {
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code(self::MENU_CODE)) {
            $this->datatable_server->error(403);
            return;
        }
        $store = $this->template_store();
        if ($store === NULL || !$store->schema_available()) {
            $this->datatable_server->error(503);
            return;
        }
        $this->datatable_server->respond(
            array(1 => 'flow_code', 2 => 'flow_name', 3 => 'description', 5 => 'active_flag'),
            function () use ($store) {
                return $store->count('');
            },
            function ($search) use ($store) {
                return $store->count($search);
            },
            function ($search, $length, $start, $order, $direction) use ($store) {
                return $store->page($search, $length, $start, $order, $direction);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'flow_code')
        );
    }

    public function approval_flow_template_new()
    {
        if (!$this->has_admin_menu_code(self::MENU_CODE)) {
            $this->deny_access();
            return;
        }
        if (!$this->has_admin_access_code('sys_approval_flow_template_add')) {
            $this->deny_access('approval_flow_template_add_denied');
            return;
        }
        $store = $this->template_store();
        $backend_available = $store !== NULL && $store->schema_available();
        $write_available = $this->write_available($store);
        $record = array(
            'flow_code' => '',
            'flow_name' => '',
            'description' => '',
            'active_flag' => 1,
            'menu_codes' => array(),
            'steps' => array(array('approver_type' => 'superior')),
        );
        $errors = array();
        $menu_allow_list = $this->menu_allow_list();
        if ($write_available && $this->input->method(TRUE) === 'POST') {
            $record = $this->form_values($store);
            $errors = $this->validate_values($record, $store, TRUE, $menu_allow_list);
            if (!$errors) {
                if ($store->save(
                    $record['flow_code'],
                    $record,
                    $record['menu_codes'],
                    $record['steps'],
                    $menu_allow_list,
                    TRUE
                )) {
                    $this->session->set_flashdata('approval_flow_template_status', 'created');
                    redirect(self::TEMPLATE_PATH, 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
        }
        $this->render_template_form('new', $record, $errors, $store, $menu_allow_list, array(
            'backend_available' => $backend_available,
            'write_available' => $write_available,
        ));
    }

    public function approval_flow_template_change($flow_code = '')
    {
        if (!$this->has_admin_menu_code(self::MENU_CODE)) {
            $this->deny_access();
            return;
        }
        if (!$this->has_admin_access_code('sys_approval_flow_template_update')) {
            $this->deny_access('approval_flow_template_update_denied');
            return;
        }
        $store = $this->template_store();
        if ($store === NULL || !$store->schema_available()) {
            show_error('Approval flow template storage is unavailable.', 503);
            return;
        }
        $record = $store->find((string) $flow_code);
        if ($record === FALSE) {
            show_error('Approval flow template storage is unavailable.', 503);
            return;
        }
        if ($record === NULL) {
            show_404();
            return;
        }
        $write_available = $this->write_available($store);
        $errors = array();
        $menu_allow_list = $this->menu_allow_list();
        if ($write_available && $this->input->method(TRUE) === 'POST') {
            $record = $this->form_values($store);
            $record['flow_code'] = (string) $flow_code;
            $errors = $this->validate_values($record, $store, FALSE, $menu_allow_list);
            if (!$errors) {
                if ($store->save(
                    $record['flow_code'],
                    $record,
                    $record['menu_codes'],
                    $record['steps'],
                    $menu_allow_list,
                    FALSE
                )) {
                    $this->session->set_flashdata('approval_flow_template_status', 'updated');
                    redirect(self::TEMPLATE_PATH, 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
        }
        $this->render_template_form('edit', $record, $errors, $store, $menu_allow_list, array(
            'backend_available' => TRUE,
            'write_available' => $write_available,
        ));
    }

    public function approval_flow_template_delete()
    {
        if (!$this->has_admin_menu_code(self::MENU_CODE)
            || !$this->has_admin_access_code('sys_approval_flow_template_delete')) {
            $this->deny_access();
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $flow_code = $this->input->post('flow_code', TRUE);
        $store = $this->template_store();
        $deleted = is_string($flow_code) && $flow_code !== ''
            && $store !== NULL && $store->schema_available() && $store->delete($flow_code);
        $this->session->set_flashdata(
            'approval_flow_template_status',
            $deleted ? 'deleted' : 'delete_error'
        );
        redirect(self::TEMPLATE_PATH, 'location', 303);
    }

    /** Search manifest menus available to the current admin. */
    public function approval_flow_template_menu_lookup()
    {
        if (!$this->has_admin_menu_code(self::MENU_CODE)) {
            $this->json_output(array(), 403);
            return;
        }
        $term = $this->input->get('term', TRUE);
        $term = is_string($term) ? trim($term) : '';
        $length = function_exists('mb_strlen') ? mb_strlen($term, 'UTF-8') : strlen($term);
        if ($length < 2 || $length > 120) {
            $this->json_output(array());
            return;
        }
        $needle = function_exists('mb_strtolower') ? mb_strtolower($term, 'UTF-8') : strtolower($term);
        $results = array();
        foreach ($this->menu_allow_list() as $code => $label) {
            $haystack = function_exists('mb_strtolower') ? mb_strtolower($label . ' ' . $code, 'UTF-8') : strtolower($label . ' ' . $code);
            if (strpos($haystack, $needle) === FALSE) {
                continue;
            }
            $results[] = array('id' => $code, 'label' => $label, 'value' => $label);
            if (count($results) >= 20) {
                break;
            }
        }
        $this->json_output($results);
    }

    public function approval_flow_template_employee_search()
    {
        $this->search_suggestion('employee_suggestions');
    }

    public function approval_flow_template_organization_search()
    {
        $this->search_suggestion('organization_suggestions');
    }

    private function search_suggestion($method)
    {
        if (!$this->has_admin_menu_code(self::MENU_CODE)
            || (!$this->has_admin_access_code('sys_approval_flow_template_add')
                && !$this->has_admin_access_code('sys_approval_flow_template_update'))) {
            $this->json_output(array(), 403);
            return;
        }
        $term = $this->input->get('q', TRUE);
        $term = is_string($term) ? trim($term) : '';
        $length = function_exists('mb_strlen') ? mb_strlen($term, 'UTF-8') : strlen($term);
        if ($length < 2 || $length > 120) {
            $this->json_output(array());
            return;
        }
        $store = $this->template_store();
        $rows = $store !== NULL && $store->schema_available() ? $store->{$method}($term) : array();
        $this->json_output(is_array($rows) ? $rows : array());
    }

    private function render_template_form($mode, $record, $errors, $store, $menu_allow_list, $flags)
    {
        $is_edit = $mode === 'edit';
        $menu_labels = array();
        foreach ((array) $record['menu_codes'] as $menu_code) {
            if (isset($menu_allow_list[$menu_code])) {
                $menu_labels[$menu_code] = $menu_allow_list[$menu_code];
            }
        }
        $this->render_admin(
            'admin/approval_flow_template/form',
            $is_edit ? 'approval_flow_template_edit_title' : 'approval_flow_template_new_title',
            $is_edit
                ? self::TEMPLATE_PATH . '/change/' . rawurlencode((string) $record['flow_code'])
                : self::TEMPLATE_PATH . '/new',
            array(
                'approval_flow_template_is_edit' => $is_edit,
                'approval_flow_template_record' => $record,
                'approval_flow_template_errors' => is_array($errors) ? $errors : array(),
                'approval_flow_template_steps' => isset($record['steps']) && is_array($record['steps']) ? $record['steps'] : array(),
                'approval_flow_template_menu_labels' => $menu_labels,
                'approval_flow_template_backend_available' => !empty($flags['backend_available']),
                'approval_flow_template_write_available' => !empty($flags['write_available']),
                'approval_flow_template_field_specs' => $store !== NULL ? $store->field_specs() : array(),
                'approval_flow_template_employee_search_url' => self::TEMPLATE_PATH . '/employee_search',
                'approval_flow_template_organization_search_url' => self::TEMPLATE_PATH . '/organization_search',
                'approval_flow_template_menu_lookup_url' => self::TEMPLATE_PATH . '/menu_lookup',
            )
        );
    }

    private function form_values($store)
    {
        $code = $this->input->post('flow_code', TRUE);
        $name = $this->input->post('flow_name', TRUE);
        $description = $this->input->post('description', TRUE);
        $active = $this->input->post('active_flag', TRUE);
        $menus = $this->input->post('menu_codes', FALSE);
        $steps = $this->input->post('steps', FALSE);
        return array(
            'flow_code' => $store->normalize_code($code),
            'flow_name' => is_string($name) ? trim($name) : '',
            'description' => is_string($description) ? trim($description) : '',
            'active_flag' => $active === '1' ? 1 : 0,
            'menu_codes' => is_array($menus) ? array_values(array_filter($menus, 'is_string')) : array(),
            'steps' => is_array($steps) ? array_values($steps) : array(),
        );
    }

    private function validate_values($record, $store, $is_new, $menu_allow_list)
    {
        $errors = array();
        $code = isset($record['flow_code']) ? (string) $record['flow_code'] : '';
        if ($code === '') {
            $errors[] = 'code_required';
        } elseif (!preg_match('/\A[a-z0-9][a-z0-9_.-]{0,31}\z/', $code)) {
            $errors[] = 'code_invalid';
        } elseif ($is_new && $store->find($code) !== NULL) {
            $errors[] = 'code_duplicate';
        }
        $name = isset($record['flow_name']) ? (string) $record['flow_name'] : '';
        if ($name === '') {
            $errors[] = 'name_required';
        } elseif (strlen($name) > 120) {
            $errors[] = 'name_too_long';
        }
        if (isset($record['description']) && strlen((string) $record['description']) > 256) {
            $errors[] = 'description_too_long';
        }
        $menus = isset($record['menu_codes']) && is_array($record['menu_codes']) ? $record['menu_codes'] : array();
        if (!$menus) {
            $errors[] = 'menu_required';
        }
        foreach ($menus as $menu_code) {
            if (!isset($menu_allow_list[$menu_code])) {
                $errors[] = 'menu_invalid';
                break;
            }
        }
        $steps = isset($record['steps']) && is_array($record['steps']) ? $record['steps'] : array();
        if (!$steps) {
            $errors[] = 'step_required';
        } elseif (count($steps) > 20) {
            $errors[] = 'step_limit';
        }
        foreach ($steps as $step) {
            $type = is_array($step) && isset($step['approver_type']) ? (string) $step['approver_type'] : '';
            if (!in_array($type, array('superior', 'organization', 'employee', 'user'), TRUE)) {
                $errors[] = 'step_type_invalid';
                break;
            }
            if ($type === 'organization' && (!isset($step['org_id']) || !ctype_digit((string) $step['org_id']))) {
                $errors[] = 'step_org_invalid';
                break;
            }
            if ($type === 'employee' && (!isset($step['approver_employee_id']) || !ctype_digit((string) $step['approver_employee_id']))) {
                $errors[] = 'step_employee_invalid';
                break;
            }
            if ($type === 'user' && (!isset($step['approver_user_id']) || (int) $step['approver_user_id'] < 1)) {
                $errors[] = 'step_user_invalid';
                break;
            }
        }
        return array_values(array_unique($errors));
    }

    private function menu_allow_list()
    {
        $this->load->library('admin_menu_manifest');
        $groups = $this->admin_menu_manifest->load(APPPATH . 'views/admin/menu_manifest/manifest.xml');
        $allowed = isset($this->current_user['menu_codes']) && is_array($this->current_user['menu_codes'])
            ? array_fill_keys($this->current_user['menu_codes'], TRUE)
            : array();
        $result = array();
        foreach ($groups as $group) {
            $group_key = isset($group['label_key']) ? (string) $group['label_key'] : '';
            $group_translation = preg_match('/^admin_[a-z0-9_]+$/', $group_key) ? $this->lang->line($group_key) : FALSE;
            $group_label = $this->admin_menu_manifest->resolve_label(
                $group_translation,
                isset($group['name']) ? (string) $group['name'] : ''
            );
            foreach ($group['items'] as $item) {
                if (!isset($item['code']) || !isset($allowed[$item['code']])) {
                    continue;
                }
                $item_key = isset($item['label_key']) ? (string) $item['label_key'] : '';
                $item_translation = preg_match('/^admin_[a-z0-9_]+$/', $item_key) ? $this->lang->line($item_key) : FALSE;
                $item_label = $this->admin_menu_manifest->resolve_label(
                    $item_translation,
                    isset($item['name']) ? (string) $item['name'] : ''
                );
                $result[$item['code']] = $group_label . ' / ' . $item_label . ' (' . $item['code'] . ')';
            }
        }
        return $result;
    }

    private function template_store()
    {
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('approval_flow_template_store');
        return $this->approval_flow_template_store;
    }

    private function write_available($store)
    {
        return $store !== NULL
            && $store->schema_available()
            && $this->current_user !== NULL
            && (bool) config_item('csrf_protection');
    }

    private function deny_access($message_key = 'approval_flow_template_no_access')
    {
        $this->output->set_status_header(403);
        $this->render_admin(
            'admin/approval_flow_template/access_denied',
            'approval_flow_template_heading',
            self::TEMPLATE_PATH,
            array('approval_flow_template_access_message_key' => $message_key)
        );
    }

    private function json_output($payload, $status = 200)
    {
        $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_status_header((int) $status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload));
    }
}
