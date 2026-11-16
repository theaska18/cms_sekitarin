<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/Admin.php';

class Admin_approvalFlow extends Admin
{
    public function approvalFlow_index()
    {
        if (!$this->has_admin_menu_code('hr_approval_flow')) { show_error('Access denied', 403); return; }
        $this->load->library('approval_workflow_store');
        $store = $this->approval_workflow_store;
        $available = $store->available();
        $search = substr(trim((string)$this->input->get('search', TRUE)), 0, 120);
        $per_page = (int)$this->input->get('per_page');
        if (!in_array($per_page, array(10,25,50), TRUE)) { $per_page = 10; }
        $total = $available ? (int)$store->count_flows($search) : 0;
        $pages = max(1, (int)ceil($total / $per_page));
        $page = min($pages, max(1, (int)$this->input->get('page')));
        $this->render_admin('admin/approval_flow/index', 'admin_approval_flow', '/admin/hr/approvalFlow', array(
            'approval_flows' => $available ? $store->flows($search, $per_page, ($page-1)*$per_page) : array(),
            'approval_flow_available' => $available,
            'approval_flow_status' => (string)$this->session->flashdata('admin_approval_flow_status'),
            'approval_flow_can_add' => $this->has_admin_access_code('hr_approval_flow_add'),
            'approval_flow_can_update' => $this->has_admin_access_code('hr_approval_flow_update'),
            'approval_flow_can_delete' => $this->has_admin_access_code('hr_approval_flow_delete'),
            'filters' => array('search'=>$search), 'page'=>$page, 'per_page'=>$per_page, 'total'=>$total, 'pages'=>$pages,
        ));
    }

    public function approvalFlow_add()
    {
        if (!$this->has_admin_menu_code('hr_approval_flow')) { show_error('Access denied', 403); return; }
        if (!$this->has_admin_access_code('hr_approval_flow_add')) { show_error('Access denied', 403); return; }
        $this->load->library('approval_workflow_store');
        $store = $this->approval_workflow_store;
        $available = $store->available();
        $this->render_admin('admin/approval_flow/add', 'approval_flow', '/admin/hr/approvalFlow/add', array(
            'approval_users' => $available ? $store->users() : array(),
            'approval_flow_available' => $available,
        ));
    }

    public function approvalFlow_edit($flow_id = 0)
    {
        if (!$this->has_admin_menu_code('hr_approval_flow')) { show_error('Access denied', 403); return; }
        if (!$this->has_admin_access_code('hr_approval_flow_update')) { show_error('Access denied', 403); return; }
        $this->load->library('approval_workflow_store');
        $store = $this->approval_workflow_store;
        $flow = $store->flow((int)$flow_id);
        if (!$flow) { show_404(); return; }
        $this->render_admin('admin/approval_flow/add', 'approval_flow', '/admin/hr/approvalFlow/edit/'.$flow_id, array(
            'approval_users'=>$store->users(), 'approval_flow_available'=>$store->available(), 'approval_flow'=>$flow,
        ));
    }

    public function approvalFlow_save()
    {
        $flow_id = (int)$this->input->post('flow_id');
        if ($flow_id) {
            if (!$this->has_admin_access_code('hr_approval_flow_update')) { show_error('Access denied', 403); return; }
        } else {
            if (!$this->has_admin_access_code('hr_approval_flow_add')) { show_error('Access denied', 403); return; }
        }
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $this->load->library('approval_workflow_store');
        $approvers = $this->input->post('approver_user_id');
        $flow = $this->approval_workflow_store->available() ? $this->approval_workflow_store->save_flow(
            $this->input->post('flow_name', TRUE), $this->input->post('request_type', TRUE), $approvers, $flow_id
        ) : FALSE;
        $this->session->set_flashdata('admin_approval_flow_status', $flow ? ($flow_id ? 'updated' : 'saved') : 'error');
        redirect('/admin/hr/approvalFlow', 'location', 303);
    }

    public function approvalFlow_delete($flow_id = 0)
    {
        if (!$this->has_admin_access_code('hr_approval_flow_delete')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $this->load->library('approval_workflow_store');
        $result = $this->approval_workflow_store->delete_flow((int)$flow_id);
        $this->session->set_flashdata('admin_approval_flow_status', $result);
        redirect('/admin/hr/approvalFlow', 'location', 303);
    }
}
