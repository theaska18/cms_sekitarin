<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/Admin.php';

/** HR approval flow step overrides for accessible system templates. */
class Admin_approvalFlow extends Admin
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('finance_filter_panel');
        $this->load->library('finance_list_table');
    }

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
            'approval_flow_can_update' => $this->has_admin_access_code('hr_approval_flow_update'),
            'filters' => array('search'=>$search), 'page'=>$page, 'per_page'=>$per_page, 'total'=>$total, 'pages'=>$pages,
            'finance_filter_config' => $this->approval_flow_filter_config(),
        ));
    }

    public function approvalFlow_data()
    {
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code('hr_approval_flow')) {
            $this->datatable_server->error(403);
            return;
        }
        $this->load->library('approval_workflow_store');
        $store = $this->approval_workflow_store;
        if (!$store->available()) {
            $this->datatable_server->error(503);
            return;
        }
        $state = $this->approval_flow_list_state('');
        $this->datatable_server->respond(
            array(1 => 'flow_code', 2 => 'flow_name', 3 => 'menu_codes', 4 => 'step_source', 5 => 'step_count', 6 => 'active_flag'),
            function () use ($store, $state) {
                return $store->count_flows($state['search'], $state['filters']);
            },
            function ($search) use ($store) {
                $state = $this->approval_flow_list_state($search);
                return $store->count_flows($state['search'], $state['filters']);
            },
            function ($search, $length, $start, $order, $direction) use ($store) {
                $state = $this->approval_flow_list_state($search);
                return $store->flows(
                    $state['search'],
                    $length,
                    $start,
                    in_array($order, array('flow_code', 'flow_name', 'active_flag'), TRUE) ? $order : 'flow_name',
                    $direction,
                    $state['filters']
                );
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'flow_name')
        );
    }

    private function approval_flow_filter_config()
    {
        $labels = $this->lang->language;
        return $this->finance_filter_panel->config(array(
            'date' => FALSE,
            'date_preset' => 'none',
            'status_preset' => 'none',
            'target' => '#approval-flow-data-table',
            'field_filters' => array(
                array('type' => 'text', 'name' => 'flow_code', 'label' => 'Kode alur'),
                array('type' => 'text', 'name' => 'flow_name', 'label' => 'Nama alur'),
                array('type' => 'text', 'name' => 'menu_codes', 'label' => 'Menu'),
                array('type' => 'select', 'name' => 'step_source', 'label' => 'Sumber tahap', 'options' => array('system' => 'Sistem', 'tenant' => 'Tenant')),
                array('type' => 'range', 'name' => 'step_count', 'label' => 'Tahap', 'min_name' => 'step_count_min', 'max_name' => 'step_count_max'),
                array('type' => 'select', 'name' => 'active_flag', 'label' => 'Status', 'options' => array('1' => 'Aktif', '0' => 'Nonaktif')),
            ),
        ));
    }

    private function approval_flow_list_state($search = '')
    {
        $panel = $this->finance_filter_panel->filters($this->approval_flow_filter_config());
        $fields = $panel['fields'];
        return array(
            'search' => substr(trim((string) $search), 0, 120),
            'filters' => array(
                'flow_code' => isset($fields['flow_code']) ? $fields['flow_code'] : '',
                'flow_name' => isset($fields['flow_name']) ? $fields['flow_name'] : '',
                'menu_codes' => isset($fields['menu_codes']) ? $fields['menu_codes'] : '',
                'step_source' => isset($fields['step_source']) ? $fields['step_source'] : '',
                'step_count' => isset($fields['step_count']) && is_array($fields['step_count']) ? $fields['step_count'] : array('min' => '', 'max' => ''),
                'active_flag' => isset($fields['active_flag']) ? $fields['active_flag'] : '',
            ),
        );
    }

    public function approvalFlow_edit($flow_code = '')
    {
        if (!$this->has_admin_menu_code('hr_approval_flow')) { show_error('Access denied', 403); return; }
        if (!$this->has_admin_access_code('hr_approval_flow_update')) { show_error('Access denied', 403); return; }
        $this->load->library('approval_workflow_store');
        $store = $this->approval_workflow_store;
        if (!$store->available()) { show_error('Approval flow storage is unavailable.', 503); return; }
        $flow = $store->template_flow((string)$flow_code);
        if ($flow === FALSE) { show_error('Approval flow storage is unavailable.', 503); return; }
        if ($flow === NULL) { show_404(); return; }
        $this->render_admin('admin/approval_flow/add', 'admin_approval_flow', '/admin/hr/approvalFlow/edit/'.rawurlencode((string)$flow['flow_code']), array(
            'approval_flow' => $flow,
            'approval_flow_steps' => isset($flow['steps']) ? $flow['steps'] : array(),
            'approval_flow_available' => TRUE,
            'approval_flow_employee_search_url' => '/admin/hr/approvalFlow/employee_search',
            'approval_flow_organization_search_url' => '/admin/hr/approvalFlow/organization_search',
        ));
    }

    public function approvalFlow_save()
    {
        if (!$this->has_admin_menu_code('hr_approval_flow')
            || !$this->has_admin_access_code('hr_approval_flow_update')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $this->load->library('approval_workflow_store');
        $store = $this->approval_workflow_store;
        $flow_code = $this->input->post('flow_code', TRUE);
        $steps = $this->input->post('steps', TRUE);
        $saved = $store->available() && is_string($flow_code) && $flow_code !== ''
            && $store->save_override($flow_code, $steps);
        $this->session->set_flashdata('admin_approval_flow_status', $saved ? 'saved' : 'error');
        redirect('/admin/hr/approvalFlow', 'location', 303);
    }

    public function approvalFlow_employee_search()
    {
        if (!$this->has_admin_menu_code('hr_approval_flow')
            || !$this->has_admin_access_code('hr_approval_flow_update')) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json')->set_output('[]');
            return;
        }
        $term = trim((string)$this->input->get('q', TRUE));
        $this->load->library('approval_workflow_store');
        $rows = strlen($term) >= 2 && $this->approval_workflow_store->available()
            ? $this->approval_workflow_store->approval_employee_suggestions($term)
            : array();
        $this->output->set_content_type('application/json')->set_output(json_encode($rows));
    }

    public function approvalFlow_organization_search()
    {
        if (!$this->has_admin_menu_code('hr_approval_flow')
            || !$this->has_admin_access_code('hr_approval_flow_update')) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json')->set_output('[]');
            return;
        }
        $term = trim((string)$this->input->get('q', TRUE));
        $this->load->library('approval_workflow_store');
        $rows = strlen($term) >= 2 && $this->approval_workflow_store->available()
            ? $this->approval_workflow_store->approval_organization_suggestions($term)
            : array();
        $this->output->set_content_type('application/json')->set_output(json_encode($rows));
    }
}
