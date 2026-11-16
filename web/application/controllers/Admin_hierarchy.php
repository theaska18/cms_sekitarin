<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/Admin.php';

class Admin_hierarchy extends Admin
{
    public function hierarchy_index()
    {
        if (!$this->has_admin_menu_code('hr_hierarchy')) { show_error('Access denied', 403); return; }
        $this->load->library('approval_workflow_store');
        $store = $this->approval_workflow_store;
        $available = $store->hierarchy_available();
        $status = $this->session->flashdata('admin_hierarchy_status');
        $this->render_admin('admin/hierarchy/index', 'admin_hierarchy', '/admin/hr/hierarchy', array(
            'hierarchy_rows' => $available ? $store->hierarchy() : array(),
            'hierarchy_available' => $available,
            'hierarchy_status' => is_string($status) ? $status : '',
            'hierarchy_can_update' => $this->has_admin_access_code('hr_hierarchy_update'),
            'hierarchy_can_add' => $this->has_admin_access_code('hr_hierarchy_add'),
        ));
    }

    public function hierarchy_new()
    {
        $is_edit = $this->input->get('org_id', TRUE) !== NULL || $this->input->post('org_id', TRUE) !== NULL;
        $permission = $is_edit ? 'hr_hierarchy_update' : 'hr_hierarchy_add';
        if (!$this->has_admin_access_code($permission)) { show_error('Access denied', 403); return; }
        $this->load->library('approval_workflow_store');
        $store = $this->approval_workflow_store;
        $available = $store->hierarchy_available();
        $record = array('org_id' => '', 'org_code' => '', 'org_name' => '', 'parent_org' => '', 'head_employee_id' => '');
        $members = array();
        if ($this->input->method(TRUE) !== 'POST') {
            $org_id = $this->input->get('org_id', TRUE);
            if ($org_id !== NULL) {
                if (!ctype_digit((string)$org_id) || !(($found = $store->hierarchy_org((int)$org_id)))) { show_404(); return; }
                $record = array_merge($record, $found);
                foreach (array('org_id','parent_org','head_employee_id') as $key) { $record[$key] = $record[$key] === NULL ? '' : (string)$record[$key]; }
                $members = $store->hierarchy_org_members((int)$org_id);
            }
            $parent = $this->input->get('parent_org', TRUE);
            if (!$is_edit && $parent !== NULL && ctype_digit((string) $parent) && $store->hierarchy_org_exists((int) $parent)) {
                $record['parent_org'] = (string) (int) $parent;
            }
        }
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            $record['org_id'] = trim((string)$this->input->post('org_id', TRUE));
            $record['org_code'] = trim((string) $this->input->post('org_code', TRUE));
            $record['org_name'] = trim((string) $this->input->post('org_name', TRUE));
            $record['parent_org'] = trim((string) $this->input->post('parent_org', TRUE));
            $record['head_employee_id'] = trim((string)$this->input->post('head_employee_id', TRUE));
            $members = $this->input->post('members', TRUE);
            $members = is_array($members) ? array_values($members) : array();
            if (!$available) { show_error('Organization data is unavailable.', 503); return; }
            if ($record['org_code'] === '' || strlen($record['org_code']) > 32) { $errors[] = 'org_code'; }
            if ($record['org_name'] === '' || (function_exists('mb_strlen') ? mb_strlen($record['org_name'], 'UTF-8') : strlen($record['org_name'])) > 64) { $errors[] = 'org_name'; }
            if ($record['parent_org'] !== '' && (!ctype_digit($record['parent_org']) || !$store->hierarchy_org_exists((int) $record['parent_org']))) { $errors[] = 'parent_org'; }
            $current = NULL;
            if ($is_edit && ctype_digit($record['org_id'])) { $current = $store->hierarchy_org((int)$record['org_id']); }
            if ($is_edit && is_array($current)) {
                $record['org_code'] = trim((string)$current['org_code']);
                $record['parent_org'] = $current['parent_org'] === NULL ? '' : (string)(int)$current['parent_org'];
            }
            if ($record['head_employee_id'] === '' && $is_edit && !empty($current['head_employee_id'])) { $record['head_employee_id'] = (string)$current['head_employee_id']; }
            if ($record['head_employee_id'] !== '' && (!ctype_digit($record['head_employee_id']) || !$store->hierarchy_employee_exists((int)$record['head_employee_id']))) { $errors[] = 'head_employee_id'; }
            $unique_members = array();
            $save_members = array();
            foreach ($members as $member) {
                if (!is_array($member)) { $errors[] = 'members'; continue; }
                $employee_id = isset($member['employee_id']) ? trim((string)$member['employee_id']) : '';
                $job_title = isset($member['job_title']) ? trim((string)$member['job_title']) : '';
                if ($employee_id === '') {
                    if ($job_title !== '') { $errors[] = 'members'; }
                    continue;
                }
                if (!ctype_digit($employee_id) || !$store->hierarchy_employee_exists((int)$employee_id)) {
                    $errors[] = 'members';
                    continue;
                }
                if (isset($unique_members[(int)$employee_id])) {
                    if (!in_array('members_duplicate', $errors, TRUE)) { $errors[] = 'members_duplicate'; }
                    continue;
                }
                $unique_members[(int)$employee_id] = TRUE;
                if ((function_exists('mb_strlen') ? mb_strlen($job_title, 'UTF-8') : strlen($job_title)) > 64) {
                    if (!in_array('job_title', $errors, TRUE)) { $errors[] = 'job_title'; }
                    continue;
                }
                $save_members[] = array('employee_id' => (string)(int)$employee_id, 'job_title' => $job_title);
            }
            if ($is_edit && (!ctype_digit($record['org_id']) || !$store->hierarchy_org_exists((int)$record['org_id']))) { show_404(); return; }
            if (!$errors) {
                $saved = $is_edit
                    ? $store->update_org((int)$record['org_id'], $record['parent_org'] === '' ? NULL : (int)$record['parent_org'], $record['org_code'], $record['org_name'], $record['head_employee_id'] === '' ? NULL : (int)$record['head_employee_id'])
                    : ($record['parent_org'] === '' ? $store->create_root_org($record['org_code'], $record['org_name']) : $store->create_child_org((int)$record['parent_org'], $record['org_code'], $record['org_name']));
                if ($saved && ($is_edit || ctype_digit((string)$saved)) && $store->sync_org_members((int)($is_edit ? $record['org_id'] : $saved), $save_members) && (!$is_edit || $record['head_employee_id'] === '' || $store->set_org_head((int)$record['org_id'], (int)$record['head_employee_id']))) {
                    if (!$is_edit) { $record['org_id'] = (string)$saved; }
                    $this->session->set_flashdata('admin_hierarchy_status', $is_edit ? 'saved' : 'created');
                    redirect('/admin/hr/hierarchy', 'location', 303);
                    return;
                }
                $errors[] = 'save';
            }
        }
        if (!$available) { show_error('Organization data is unavailable.', 503); return; }
        $this->render_admin('admin/hierarchy/new', 'admin_hierarchy', '/admin/hr/hierarchy/new', array(
            'hierarchy_available' => $available,
            'hierarchy_record' => $record,
            'hierarchy_errors' => $errors,
            'hierarchy_rows' => $store->hierarchy(),
            'hierarchy_employees' => $store->employees(),
            'hierarchy_members' => $members,
            'hierarchy_is_edit' => $is_edit,
        ));
    }

    public function hierarchy_save()
    {
        if (!$this->has_admin_access_code('hr_hierarchy_update')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $this->load->library('approval_workflow_store');
        $org = $this->input->post('org_id', TRUE);
        $parent = $this->input->post('parent_org', TRUE);
        $ok = ctype_digit((string)$org) && ($parent === '' || ctype_digit((string)$parent))
            && $this->approval_workflow_store->hierarchy_available()
            && $this->approval_workflow_store->set_parent_org((int)$org, (int)$parent);
        $this->session->set_flashdata('admin_hierarchy_status', $ok ? 'saved' : 'error');
        redirect('/admin/hr/hierarchy', 'location', 303);
    }

    public function employee_search()
    {
        if (!$this->has_admin_menu_code('hr_hierarchy')) { show_error('Access denied', 403); return; }
        $term = trim((string)$this->input->get('q', TRUE));
        if (strlen($term) < 2 || strlen($term) > 100) {
            return $this->output->set_content_type('application/json')->set_output('[]');
        }
        $this->load->library('approval_workflow_store');
        $rows = $this->approval_workflow_store->hierarchy_employees($term);
        $this->output->set_content_type('application/json')->set_output(json_encode(array_map(function($row) {
            return array('id'=>(int)$row['employee_id'],'label'=>$row['employee_name'].' ('.$row['employee_code'].')');
        }, $rows)));
    }

    public function hierarchy_employee_search()
    {
        return $this->employee_search();
    }

    public function action()
    {
        if (!$this->has_admin_access_code('hr_hierarchy_update')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $this->load->library('approval_workflow_store');
        $action = $this->input->post('action', TRUE);
        $org = $this->input->post('org_id', TRUE);
        $employee = $this->input->post('employee_id', TRUE);
        $valid_org = ctype_digit((string)$org) && (int)$org > 0;
        if ($valid_org && $action === 'head') { $ok = ctype_digit((string)$employee) && $this->approval_workflow_store->set_org_head((int)$org,(int)$employee); }
        elseif ($valid_org && $action === 'member') { $ok = ctype_digit((string)$employee) && $this->approval_workflow_store->add_org_member((int)$org,(int)$employee); }
        elseif ($valid_org && $action === 'child') {
            $code = $this->input->post('org_code', TRUE);
            $name = $this->input->post('org_name', TRUE);
            $ok = $this->approval_workflow_store->create_child_org((int)$org, $code, $name);
        }
        elseif ($valid_org && $action === 'delete') { $ok = $this->approval_workflow_store->delete_org_leaf((int)$org); }
        else { $ok = FALSE; }
        $this->session->set_flashdata('admin_hierarchy_status', $ok ? ($action === 'delete' ? 'deleted' : ($action === 'child' ? 'created' : 'saved')) : 'error');
        redirect('/admin/hr/hierarchy', 'location', 303);
    }
}
