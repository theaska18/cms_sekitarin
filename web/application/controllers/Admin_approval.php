<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/Admin.php';

class Admin_approval extends Admin
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('finance_filter_panel');
        $this->load->library('finance_list_table');
    }

    public function approval_index()
    {
        if (!$this->has_admin_menu_code('sys_approval')) { show_error('Access denied', 403); return; }
        $this->load->library('approval_workflow_store');
        $store=$this->approval_workflow_store;
        $token=$this->input->get('approval_token',TRUE);
        if ($token !== NULL) {
            $decision=(string)$this->input->get('approval_decision',TRUE);
            $request=$store->approval_token_request((string)$token);
            $token_decision=$request && isset($request['decision']) ? (string)$request['decision'] : '';
            $valid_view=$token_decision==='view' && $decision==='view';
            $valid_legacy=in_array($token_decision,array('approved','rejected'),TRUE) && $decision===$token_decision;
            if (!$request || (!$valid_view && !$valid_legacy)) { show_error('Tautan persetujuan tidak valid, kedaluwarsa, atau sudah digunakan.', 410); return; }
            if ($this->input->method(TRUE)==='POST') {
                $posted_token=(string)$this->input->post('approval_token',TRUE);
                $posted_decision=(string)$this->input->post('approval_decision',TRUE);
                $posted_note=(string)$this->input->post('note',TRUE);
                $valid_choice=$token_decision==='view'
                    ? in_array($posted_decision,array('approved','rejected','revise'),TRUE)
                    : $posted_decision===$token_decision;
                if ($posted_decision==='revise' && trim($posted_note)==='') {
                    show_error('Alasan revisi wajib diisi.', 422);
                    return;
                }
                $ok=hash_equals((string)$token,$posted_token) && $valid_choice && $store->decide_approval_token($posted_token,$posted_decision,$posted_note);
                if (!$ok) { show_error('Persetujuan tidak dapat diproses. Tautan mungkin sudah digunakan atau kedaluwarsa.', 409); return; }
                $this->session->set_flashdata('admin_approval_status','decided');
                redirect('/admin/system/approval','location',303);
                return;
            }
            $this->render_admin('admin/approval/confirm','admin_approval','/admin/system/approval',array(
                'approval_confirmation'=>$request,
                'approval_confirmation_token'=>(string)$token,
                'approval_confirmation_token_decision'=>$token_decision,
                'approval_confirmation_decision'=>$token_decision==='view'?NULL:$token_decision,
            ));
            return;
        }
        $available=$store->available();
        $can_add = $this->has_admin_access_code('sys_approval_submit_approval');
        $can_update = $available;
        $can_manage_errors = $this->has_admin_access_code('sys_approval_error');
        $hide_request_form = !$can_add;
        $this->render_admin('admin/approval/index', 'admin_approval', '/admin/system/approval', array(
            'approval_requests'=>$available && $can_update ? $store->pending():array(),
            'approval_my_requests_available'=>$available,
            'approval_error_count'=>$available && $can_manage_errors ? $store->count_approval_errors():0,
            'approval_available'=>$available,
            'approval_can_add'=>$can_add,
            'approval_hide_request_form'=>$hide_request_form,
            'approval_can_update'=>$can_update,
            'approval_can_manage_errors'=>$can_manage_errors,
            'approval_status'=>(string)$this->session->flashdata('admin_approval_status'),
            'approval_history_filter_config'=>$this->approval_history_filter_config(),
        ));
    }

    private function approval_history_filter_config()
    {
        $labels = $this->lang->language;
        return $this->finance_filter_panel->config(array(
            'date' => TRUE,
            'date_preset' => 'none',
            'date_fields' => array(
                array('key' => 'create_on', 'label_key' => 'finance_created_at', 'default' => FALSE),
            ),
            'status_preset' => 'none',
            'target' => '#my-approval-history-table',
            'field_filters' => array(
                array('type' => 'text', 'name' => 'subject', 'label' => isset($labels['admin_approval_subject']) ? $labels['admin_approval_subject'] : 'Request'),
                array('type' => 'text', 'name' => 'flow_code', 'label' => isset($labels['admin_approval_flow']) ? $labels['admin_approval_flow'] : 'Flow'),
                array('type' => 'select', 'name' => 'status', 'label' => isset($labels['admin_approval_status_label']) ? $labels['admin_approval_status_label'] : 'Status', 'options' => array(
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'revise' => 'Revisi',
                    'pending' => 'Menunggu persetujuan',
                    'needs_assignment' => 'Menunggu penetapan admin',
                )),
                array('type' => 'select', 'name' => 'step_status', 'label' => 'Checklist persetujuan', 'options' => array(
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'revise' => 'Revisi',
                    'pending' => 'Menunggu persetujuan',
                    'needs_assignment' => 'Menunggu penetapan admin',
                )),
            ),
        ));
    }

    private function approval_history_list_filters($search = '')
    {
        $panel = $this->finance_filter_panel->filters($this->approval_history_filter_config());
        return array('search' => (string) $search, 'date_ranges' => $panel['date_ranges'], 'fields' => $panel['fields']);
    }

    private function approval_error_filter_config()
    {
        $labels = $this->lang->language;
        return $this->finance_filter_panel->config(array(
            'date' => TRUE,
            'date_preset' => 'none',
            'date_fields' => array(
                array('key' => 'create_on', 'label_key' => 'finance_created_at', 'default' => FALSE),
            ),
            'status_preset' => 'none',
            'target' => '#approval-error-table',
            'field_filters' => array(
                array('type' => 'text', 'name' => 'subject', 'label' => isset($labels['admin_approval_subject']) ? $labels['admin_approval_subject'] : 'Request'),
                array('type' => 'text', 'name' => 'requester_name', 'label' => 'Pemohon'),
                array('type' => 'text', 'name' => 'flow_code', 'label' => isset($labels['admin_approval_flow']) ? $labels['admin_approval_flow'] : 'Flow'),
                array('type' => 'text', 'name' => 'org', 'label' => 'Organisasi'),
                array('type' => 'text', 'name' => 'error_code', 'label' => 'Penyebab'),
            ),
        ));
    }

    private function approval_error_list_filters($search = '')
    {
        $panel = $this->finance_filter_panel->filters($this->approval_error_filter_config());
        return array('search' => (string) $search, 'date_ranges' => $panel['date_ranges'], 'fields' => $panel['fields']);
    }

    public function approval_submit()
    {
        if (!$this->has_admin_access_code('sys_approval_submit_approval')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE)!=='POST') { show_404(); return; }
        $this->load->library('approval_workflow_store'); $store=$this->approval_workflow_store;
        $result=$store->available() ? $store->submit(
            $this->input->post('flow_code',TRUE),
            $this->input->post('subject',TRUE),
            $this->input->post('details',TRUE)
        ) : FALSE;
        $status=is_array($result)&&isset($result['status'])?(string)$result['status']:'error';
        $this->session->set_flashdata('admin_approval_status',$status);
        redirect('/admin/system/approval','location',303);
    }

    public function approval_flow_search()
    {
        if (!$this->has_admin_access_code('sys_approval_submit_approval')) { $this->output->set_status_header(403); $this->output->set_content_type('application/json')->set_output('[]'); return; }
        $term=trim((string)$this->input->get('q',TRUE));
        $this->load->library('approval_workflow_store');
        $rows=strlen($term)>=2&&$this->approval_workflow_store->available()
            ?$this->approval_workflow_store->flow_suggestions($term):array();
        $this->output->set_content_type('application/json')->set_output(json_encode($rows));
    }

    public function approval_my_request_data()
    {
        if (!$this->has_admin_menu_code('sys_approval')) {
            $this->load->library('datatable_server');
            $this->datatable_server->error(403);
            return;
        }
        $this->load->library('approval_workflow_store');
        $store=$this->approval_workflow_store;
        $this->load->library('datatable_server');
        if (!$store->available()) { $this->datatable_server->error(503); return; }
        $this->datatable_server->respond(
            array(0=>'subject',1=>'flow_code',2=>'status',4=>'create_on'),
            function() use ($store) { return $store->count_my_approval_requests('', $this->approval_history_list_filters('')); },
            function($search) use ($store) { return $store->count_my_approval_requests($search, $this->approval_history_list_filters($search)); },
            function($search,$length,$start,$order,$direction) use ($store) {
                return $store->my_approval_requests($search,$length,$start,$order,$direction, $this->approval_history_list_filters($search));
            },
            array('max_length'=>200,'max_search_length'=>120,'default_order'=>'create_on')
        );
    }

    public function approval_errors()
    {
        if (!$this->has_admin_menu_code('sys_approval')
            || !$this->has_admin_access_code('sys_approval_error')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE)==='POST') {
            $this->load->library('approval_workflow_store');
            $result=$this->approval_workflow_store->assign_approval_error(
                $this->input->post('error_id',TRUE),
                $this->input->post('employee_id',TRUE)
            );
            $this->session->set_flashdata('admin_approval_error_status',$result);
            redirect('/admin/system/approval/errors','location',303);
            return;
        }
        $this->load->library('approval_workflow_store');
        $store=$this->approval_workflow_store;
        $available=$store->available();
        $this->render_admin('admin/approval/errors','admin_approval','/admin/system/approval/errors',array(
            'approval_error_available'=>$available,
            'approval_error_count'=>$available?$store->count_approval_errors():0,
            'approval_error_status'=>(string)$this->session->flashdata('admin_approval_error_status'),
            'approval_error_filter_config'=>$this->approval_error_filter_config(),
        ));
    }

    public function approval_error_data()
    {
        if (!$this->has_admin_menu_code('sys_approval')
            || !$this->has_admin_access_code('sys_approval_error')) {
            $this->load->library('datatable_server');
            $this->datatable_server->error(403);
            return;
        }
        $this->load->library('approval_workflow_store');
        $store=$this->approval_workflow_store;
        $this->load->library('datatable_server');
        if (!$store->available()) { $this->datatable_server->error(503); return; }
        $this->datatable_server->respond(
            array(0=>'subject',4=>'error_code',5=>'create_on'),
            function() use ($store) { return $store->count_approval_errors('', $this->approval_error_list_filters('')); },
            function($search) use ($store) { return $store->count_approval_errors($search, $this->approval_error_list_filters($search)); },
            function($search,$length,$start,$order,$direction) use ($store) {
                return $store->approval_errors($search,$length,$start,$order,$direction, $this->approval_error_list_filters($search));
            },
            array('max_length'=>200,'max_search_length'=>120,'default_order'=>'create_on')
        );
    }

    public function approval_employee_search()
    {
        if (!$this->has_admin_menu_code('sys_approval')
            || !$this->has_admin_access_code('sys_approval_error')) { $this->output->set_status_header(403); $this->output->set_content_type('application/json')->set_output('[]'); return; }
        $term=trim((string)$this->input->get('q',TRUE));
        $this->load->library('approval_workflow_store');
        $rows=strlen($term)>=2&&$this->approval_workflow_store->available()
            ?$this->approval_workflow_store->approval_employee_suggestions($term):array();
        $this->output->set_content_type('application/json')->set_output(json_encode($rows));
    }

    public function approval_decide()
    {
        if (!$this->has_admin_menu_code('sys_approval')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE)!=='POST') { show_404(); return; }
        $this->load->library('approval_workflow_store'); $store=$this->approval_workflow_store;
        $ok=$store->available() && $store->decide($this->input->post('request_id',TRUE),$this->input->post('decision',TRUE),$this->input->post('note',TRUE));
        $this->session->set_flashdata('admin_approval_status',$ok?'decided':'error'); redirect('/admin/system/approval','location',303);
    }
}
