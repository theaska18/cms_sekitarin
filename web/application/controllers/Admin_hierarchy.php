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
            'hierarchy_can_delete' => $this->has_admin_access_code('hr_hierarchy_delete'),
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
        $auto_org_code = FALSE;
        $sequence_store = NULL;
        $errors = array();
        if (!$is_edit && $available) {
            $this->load->library('application_sequence_store');
            $sequence_store = $this->application_sequence_store;
            $sequence = $sequence_store->sequence_config((int) $this->current_user['tenant_id'], 'org_code');
            if (is_array($sequence) && (string) $sequence['use_flag'] === '1') {
                $auto_org_code = TRUE;
                $preview = $sequence_store->preview_code((int) $this->current_user['tenant_id'], 'org_code');
                for ($attempt = 0; is_array($preview) && $attempt < 1000; $attempt++) {
                    if (!$store->hierarchy_org_code_exists($preview['code'])) {
                        break;
                    }
                    $preview = $sequence_store->next_code_after($preview);
                }
                if (is_array($preview) && !$store->hierarchy_org_code_exists($preview['code'])) {
                    $record['org_code'] = $preview['code'];
                } else {
                    $errors[] = is_array($preview) ? 'sequence_exhausted' : 'sequence_generation';
                }
            }
        }
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
        if ($this->input->method(TRUE) === 'POST') {
            $record['org_id'] = trim((string)$this->input->post('org_id', TRUE));
            if (!$auto_org_code || $is_edit) {
                $record['org_code'] = trim((string) $this->input->post('org_code', TRUE));
            }
            $record['org_name'] = trim((string) $this->input->post('org_name', TRUE));
            $record['parent_org'] = trim((string) $this->input->post('parent_org', TRUE));
            $record['head_employee_id'] = trim((string)$this->input->post('head_employee_id', TRUE));
            $members = $this->input->post('members', TRUE);
            $members = is_array($members) ? array_values($members) : array();
            if (!$available) { show_error('Organization data is unavailable.', 503); return; }
            if (!$auto_org_code && ($record['org_code'] === '' || strlen($record['org_code']) > 32)) { $errors[] = 'org_code'; }
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
                if (!$is_edit && $auto_org_code && $sequence_store !== NULL) {
                    $created = $this->hierarchy_create_with_sequence(
                        $store,
                        $sequence_store,
                        (int) $this->current_user['tenant_id'],
                        $record['parent_org'] === '' ? NULL : (int) $record['parent_org'],
                        $record['org_name'],
                        $save_members,
                        $record['head_employee_id'] === '' ? NULL : (int) $record['head_employee_id']
                    );
                    if (is_array($created) && isset($created['org_id'])) {
                        $record['org_id'] = (string) $created['org_id'];
                        $record['org_code'] = (string) $created['org_code'];
                        $this->session->set_flashdata('admin_hierarchy_status', 'created');
                        redirect('/admin/hr/hierarchy', 'location', 303);
                        return;
                    }
                    $errors[] = is_array($created) && isset($created['error']) ? $created['error'] : 'save';
                    $record['org_code'] = is_array($created) && isset($created['org_code']) ? $created['org_code'] : $record['org_code'];
                } else {
                    $saved = $is_edit
                        ? $store->update_org((int)$record['org_id'], $record['parent_org'] === '' ? NULL : (int)$record['parent_org'], $record['org_code'], $record['org_name'], $record['head_employee_id'] === '' ? NULL : (int)$record['head_employee_id'])
                        : ($record['parent_org'] === '' ? $store->create_root_org($record['org_code'], $record['org_name']) : $store->create_child_org((int)$record['parent_org'], $record['org_code'], $record['org_name']));
                    if ($saved && ($is_edit || ctype_digit((string)$saved))
                        && $store->sync_org_members((int)($is_edit ? $record['org_id'] : $saved), $save_members)
                        && (!$is_edit || $record['head_employee_id'] === '' || $store->set_org_head((int)$record['org_id'], (int)$record['head_employee_id']))) {
                        if (!$is_edit) { $record['org_id'] = (string)$saved; }
                        $this->session->set_flashdata('admin_hierarchy_status', $is_edit ? 'saved' : 'created');
                        redirect('/admin/hr/hierarchy', 'location', 303);
                        return;
                    }
                    $errors[] = 'save';
                }
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
            'hierarchy_auto_org_code' => !$is_edit && $auto_org_code,
        ));
    }

    /** Create an organization and advance its tenant sequence atomically. */
    private function hierarchy_create_with_sequence($store, $sequence_store, $tenant_id, $parent, $name, $members, $head)
    {
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();
        $next = $sequence_store->next_code_for_update((int)$tenant_id, 'org_code');
        if (!is_array($next)) {
            $this->db->trans_rollback();
            $this->db->db_debug = $previous_db_debug;
            return array('error' => 'sequence_generation');
        }

        $org_id = FALSE;
        $create_failed = FALSE;
        for ($attempt = 0; is_array($next) && $attempt < 1000; $attempt++) {
            $code = (string)$next['code'];
            if (strlen($code) > 32) {
                $this->db->trans_rollback();
                $this->db->db_debug = $previous_db_debug;
                return array('error' => 'org_code');
            }
            if (!$store->hierarchy_org_code_exists($code)) {
                $org_id = $parent === NULL
                    ? $store->create_root_org($code, $name)
                    : $store->create_child_org((int)$parent, $code, $name);
                $create_failed = !is_numeric($org_id) || (int)$org_id < 1;
                break;
            }
            $next = $sequence_store->next_code_after($next);
        }
        if (!is_numeric($org_id) || (int)$org_id < 1) {
            $this->db->trans_rollback();
            $this->db->db_debug = $previous_db_debug;
            return array('error' => $create_failed ? 'save' : (is_array($next) ? 'sequence_exhausted' : 'sequence_generation'));
        }

        $members_ok = !$this->db->table_exists('hr_org_member') && empty($members)
            ? TRUE
            : $store->sync_org_members((int)$org_id, $members);
        $head_ok = $head === NULL || $store->set_org_head((int)$org_id, (int)$head);
        $sequence_ok = $members_ok && $head_ok
            && $sequence_store->advance_sequence((int)$tenant_id, 'org_code', $next);
        if ($sequence_ok && $this->db->trans_status() !== FALSE) {
            $this->db->trans_commit();
            $this->db->db_debug = $previous_db_debug;
            return array('org_id' => (int)$org_id, 'org_code' => (string)$next['code']);
        }
        $this->db->trans_rollback();
        $this->db->db_debug = $previous_db_debug;
        return array('error' => 'save');
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

    /** Return the current CSRF pair so long-lived hierarchy pages can refresh before delete. */
    public function hierarchy_csrf_token()
    {
        if (!$this->has_admin_menu_code('hr_hierarchy')
            || !$this->has_admin_access_code('hr_hierarchy_delete')) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json', 'utf-8')->set_output('{}');
            return;
        }
        if ($this->input->method(TRUE) !== 'GET') {
            $this->output->set_status_header(405);
            $this->output->set_content_type('application/json', 'utf-8')->set_output('{}');
            return;
        }
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(array(
                'name' => $this->security->get_csrf_token_name(),
                'hash' => $this->security->get_csrf_hash(),
            )));
    }

    public function action()
    {
        $action = $this->input->post('action', TRUE);
        $access_code = $action === 'child' ? 'hr_hierarchy_add'
            : ($action === 'delete' ? 'hr_hierarchy_delete' : 'hr_hierarchy_update');
        if (!$this->has_admin_menu_code('hr_hierarchy') || !$this->has_admin_access_code($access_code)) {
            show_error('Access denied', 403);
            return;
        }
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $this->load->library('approval_workflow_store');
        $org = $this->input->post('org_id', TRUE);
        $employee = $this->input->post('employee_id', TRUE);
        $valid_org = ctype_digit((string)$org) && (int)$org > 0;
        if ($valid_org && $action === 'head') { $ok = ctype_digit((string)$employee) && $this->approval_workflow_store->set_org_head((int)$org,(int)$employee); }
        elseif ($valid_org && $action === 'member') { $ok = ctype_digit((string)$employee) && $this->approval_workflow_store->add_org_member((int)$org,(int)$employee); }
        elseif ($valid_org && $action === 'child') {
            $code = $this->input->post('org_code', TRUE);
            $name = $this->input->post('org_name', TRUE);
            $this->load->library('application_sequence_store');
            $tenant_id = (int) $this->current_user['tenant_id'];
            $sequence = $this->application_sequence_store->sequence_config($tenant_id, 'org_code');
            if (is_array($sequence) && (string)$sequence['use_flag'] === '1') {
                $created = $this->hierarchy_create_with_sequence(
                    $this->approval_workflow_store,
                    $this->application_sequence_store,
                    $tenant_id,
                    (int)$org,
                    (string)$name,
                    array(),
                    NULL
                );
                $ok = is_array($created) && isset($created['org_id']);
            } else {
                $ok = $this->approval_workflow_store->create_child_org((int)$org, $code, $name);
            }
        }
        elseif ($valid_org && $action === 'delete') { $ok = $this->approval_workflow_store->delete_org_leaf((int)$org); }
        else { $ok = FALSE; }
        $this->session->set_flashdata('admin_hierarchy_status', $ok ? ($action === 'delete' ? 'deleted' : ($action === 'child' ? 'created' : 'saved')) : 'error');
        redirect('/admin/hr/hierarchy', 'location', 303);
    }
}
