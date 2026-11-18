<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** System -> Update page and one-click update action. */
class Admin_update extends Admin
{
    public function update_index()
    {
        $store = $this->update_store();
        if (!$this->has_admin_menu_code('sys_update')) {
            $this->load_update_language();
            $this->render_admin(
                'admin/system/update/index',
                'update_heading',
                '/admin/system/update',
                array('access_denied' => TRUE)
            );
            return;
        }

        $check = $this->session->flashdata('admin_update_check');
        if (!is_array($check)) {
            $check = $store->check();
        }
        $result = $this->session->flashdata('admin_update_result');
        $notice = (string) $this->session->flashdata('admin_update_notice');
        $history = $store->history(25);
        $history_migrations = array();
        foreach ($history as $entry) {
            $history_migrations[(int) $entry['update_id']] = $store->migrations_for((int) $entry['update_id']);
        }

        $this->load_update_language();
        $this->render_admin(
            'admin/system/update/index',
            'update_heading',
            '/admin/system/update',
            array(
                'access_denied' => FALSE,
                'update_check' => $check,
                'update_result' => is_array($result) ? $result : NULL,
                'update_notice' => $notice,
                'update_active' => $store->active_update(),
                'update_history' => $history,
                'update_history_migrations' => $history_migrations,
                'update_can_run' => $this->has_admin_access_code('sys_update_run'),
                'update_link' => $store->update_link(),
                'update_checks' => $store->runner_checks(),
                'update_schema_available' => $store->schema_available(),
                'csrf_token_name' => $this->security->get_csrf_token_name(),
                'csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    public function update_check()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            $this->output->set_header('Allow: POST')->set_status_header(405);
            return;
        }
        if (!$this->has_admin_menu_code('sys_update')) {
            $this->output->set_status_header(403);
            show_error('You do not have permission to check for updates.', 403);
            return;
        }
        $check = $this->update_store()->check();
        $this->session->set_flashdata('admin_update_check', $check);
        $this->session->set_flashdata('admin_update_notice', $check['ok'] ? 'checked' : 'check_failed');
        redirect('/admin/system/update', 'location', 303);
    }

    public function update_schema()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            $this->output->set_header('Allow: POST')->set_status_header(405);
            return;
        }
        if (!$this->has_admin_access_code('sys_update_run')) {
            $this->output->set_status_header(403);
            show_error('You do not have permission to prepare the update tables.', 403);
            return;
        }

        $error = $this->update_store()->bootstrap_schema();
        $this->session->set_flashdata('admin_update_notice', $error === '' ? 'schema_ready' : 'schema_failed');
        redirect('/admin/system/update', 'location', 303);
    }

    public function update_run()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            $this->output->set_header('Allow: POST')->set_status_header(405);
            return;
        }
        if (!$this->has_admin_access_code('sys_update_run')) {
            $this->output->set_status_header(403);
            show_error('You do not have permission to run updates.', 403);
            return;
        }

        $store = $this->update_store();
        $check = $store->check();
        if (!$check['ok']) {
            $this->session->set_flashdata('admin_update_check', $check);
            $this->session->set_flashdata('admin_update_notice', 'check_failed');
            redirect('/admin/system/update', 'location', 303);
            return;
        }
        if (empty($check['update_available']) || empty($check['release'])) {
            $this->session->set_flashdata('admin_update_check', $check);
            $this->session->set_flashdata('admin_update_notice', 'no_update');
            redirect('/admin/system/update', 'location', 303);
            return;
        }

        $result = $store->run($check['release'], (int) $this->current_user['user_id']);
        if (is_string($result['log']) && strlen($result['log']) > 12000) {
            $result['log'] = substr($result['log'], -12000);
        }
        $this->session->set_flashdata('admin_update_result', $result);
        $this->session->set_flashdata('admin_update_notice', $result['ok'] ? 'updated' : 'update_failed');
        redirect('/admin/system/update', 'location', 303);
    }

    private function update_store()
    {
        $this->load->library('update_store');
        return $this->update_store;
    }

    private function load_update_language()
    {
        $locale = $this->resolve_admin_locale();
        if (!is_file(APPPATH . 'language/' . $locale . '/update_lang.php')) {
            $locale = 'english';
        }
        $this->lang->load('update', $locale);
    }
}
