<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/Admin.php';

class Admin_approval extends Admin
{
    public function approval_index()
    {
        if (!$this->has_admin_menu_code('sys_approval')) { show_error('Access denied', 403); return; }
        $this->load->library('approval_workflow_store');
        $store=$this->approval_workflow_store;
        $token=$this->input->get('approval_token',TRUE);
        if ($token !== NULL) {
            $decision=(string)$this->input->get('approval_decision',TRUE);
            $request=$store->approval_token_request((string)$token);
            if (!$request || !in_array($decision,array('approved','rejected'),TRUE) || ($request && $request['decision']!==$decision)) { show_error('Tautan persetujuan tidak valid, kedaluwarsa, atau sudah digunakan.', 410); return; }
            if ($this->input->method(TRUE)==='POST') {
                $posted_token=(string)$this->input->post('approval_token',TRUE);
                $posted_decision=(string)$this->input->post('approval_decision',TRUE);
                $ok=hash_equals((string)$token,$posted_token) && $decision===$posted_decision && $store->decide_approval_token($posted_token,$posted_decision);
                if (!$ok) { show_error('Persetujuan tidak dapat diproses. Tautan mungkin sudah digunakan atau kedaluwarsa.', 409); return; }
                $this->session->set_flashdata('admin_approval_status','decided');
                redirect('/admin/system/approval','location',303);
                return;
            }
            $escape=function($value) { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); };
            $label=$decision==='approved'?'Setuju':'Tidak setuju';
            $html='<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Konfirmasi persetujuan</title><body><main><h1>Konfirmasi keputusan</h1><p><strong>'.$escape($request['subject']).'</strong></p><p>'.$escape($request['details']).'</p><form method="post" action="'.htmlspecialchars(site_url('admin/system/approval').'?approval_token='.rawurlencode($token).'&approval_decision='.rawurlencode($decision),ENT_QUOTES,'UTF-8').'"> <input type="hidden" name="'.htmlspecialchars($this->security->get_csrf_token_name(),ENT_QUOTES,'UTF-8').'" value="'.htmlspecialchars($this->security->get_csrf_hash(),ENT_QUOTES,'UTF-8').'"> <input type="hidden" name="approval_token" value="'.$escape($token).'"><input type="hidden" name="approval_decision" value="'.$escape($decision).'"><button type="submit">Konfirmasi '.$label.'</button></form></main></body></html>';
            $this->output->set_content_type('text/html','utf-8')->set_output($html);
            return;
        }
        $available=$store->available();
        $can_add = $this->has_admin_access_code('sys_approval_add');
        $can_update = $this->has_admin_access_code('sys_approval_update');
        $this->render_admin('admin/approval/index', 'admin_approval', '/admin/system/approval', array(
            'approval_requests'=>$available && $can_update ? $store->pending():array(),
            'approval_flows_for_request'=>$available && $can_add ? $store->active_flows():array(),
            'approval_available'=>$available,
            'approval_can_add'=>$can_add,
            'approval_can_update'=>$can_update,
            'approval_status'=>(string)$this->session->flashdata('admin_approval_status'),
        ));
    }

    public function approval_submit()
    {
        if (!$this->has_admin_access_code('sys_approval_add')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE)!=='POST') { show_404(); return; }
        $this->load->library('approval_workflow_store'); $store=$this->approval_workflow_store;
        $ok=$store->available() && $store->submit($this->input->post('flow_id',TRUE),$this->input->post('subject',TRUE),$this->input->post('details',TRUE));
        $this->session->set_flashdata('admin_approval_status',$ok?'submitted':'error'); redirect('/admin/system/approval','location',303);
    }

    public function approval_decide()
    {
        if (!$this->has_admin_access_code('sys_approval_update')) { show_error('Access denied', 403); return; }
        if ($this->input->method(TRUE)!=='POST') { show_404(); return; }
        $this->load->library('approval_workflow_store'); $store=$this->approval_workflow_store;
        $ok=$store->available() && $store->decide($this->input->post('request_id',TRUE),$this->input->post('decision',TRUE),$this->input->post('note',TRUE));
        $this->session->set_flashdata('admin_approval_status',$ok?'decided':'error'); redirect('/admin/system/approval','location',303);
    }
}
