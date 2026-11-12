<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

class Admin_notifications extends Admin
{
    const PAGE_SIZE = 20;

    public function index($page = 1)
    {
        $page = max(1, (int) $page);
        if ($this->input->get('page', TRUE) !== NULL) {
            $requested_page = $this->input->get('page', TRUE);
            if (ctype_digit((string) $requested_page)) {
                $page = max(1, (int) $requested_page);
            }
        }

        $data = array(
            'notifications' => array(),
            'notifications_page' => $page,
            'notifications_total_pages' => 1,
            'notifications_error' => FALSE,
        );
        $selected_id = $this->input->get('selected', TRUE);
        if ($selected_id !== NULL && ctype_digit((string) $selected_id)) {
            $data['selected_notification_id'] = max(0, (int) $selected_id);
        }

        // Authenticated identity is sourced from Admin's server-side session validation.
        $user_id = isset($this->current_user['user_id']) ? (int) $this->current_user['user_id'] : 0;
        if ($user_id < 1) {
            show_error('Not Found', 404);
            return;
        }

        $db = $this->load->database('default', TRUE);
        $data['notifications_error'] = TRUE;
        if ($db->table_exists('app_notification')) {
            $fields = array();
            foreach ((array) $db->field_data('app_notification') as $field) {
                if (isset($field->name)) {
                    $fields[(string) $field->name] = TRUE;
                }
            }

            if (isset($fields['user_id'], $fields['notification_id'], $fields['title'], $fields['message'])) {
                $count = $db->from('app_notification')->where('user_id', $user_id)->count_all_results();
                $total_pages = max(1, (int) ceil($count / self::PAGE_SIZE));
                $page = min($page, $total_pages);
                $data['notifications_page'] = $page;
                $data['notifications_total_pages'] = $total_pages;

                $select = array('notification_id', 'title', 'message');
                $order_date_field = NULL;
                foreach (array('create_on', 'created_at', 'created_date', 'date_created') as $candidate) {
                    if (isset($fields[$candidate])) {
                        $select[] = $candidate;
                        $order_date_field = $candidate;
                        break;
                    }
                }
                $read_flag_field = isset($fields['read_flag']) ? 'read_flag' : NULL;
                if ($read_flag_field !== NULL) {
                    $select[] = $read_flag_field;
                    $selected_notification_id = isset($data['selected_notification_id']) ? (int) $data['selected_notification_id'] : 0;
                    if ($selected_notification_id > 0) {
                        // Only mark a notification belonging to the authenticated user as read.
                        $db->where('notification_id', $selected_notification_id)
                            ->where('user_id', $user_id)
                            ->update('app_notification', array('read_flag' => 1));
                    }
                }
                $query = $db->select(implode(', ', $select), FALSE)
                    ->from('app_notification')
                    ->where('user_id', $user_id);
                if ($order_date_field !== NULL) {
                    $query->order_by($order_date_field, 'DESC');
                }
                $result = $query->order_by('notification_id', 'DESC')
                    ->limit(self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE)
                    ->get();
                if ($result !== FALSE) {
                    $data['notifications_error'] = FALSE;
                    foreach ($result->result_array() as $row) {
                        $data['notifications'][] = array(
                            'id' => isset($row['notification_id']) ? (int) $row['notification_id'] : 0,
                            'title' => isset($row['title']) ? (string) $row['title'] : '',
                            'message' => isset($row['message']) ? (string) $row['message'] : '',
                            'created_at' => $order_date_field !== NULL && isset($row[$order_date_field]) ? (string) $row[$order_date_field] : '',
                            'read_flag' => $read_flag_field !== NULL && isset($row[$read_flag_field]) ? (string) $row[$read_flag_field] : '0',
                        );
                    }
                }
            }
        }

        $this->render_admin('admin/notifications/index', 'notifications_heading', '/admin/notifications', $data);
    }

    public function mark_viewed()
    {
        if (strtoupper((string) $this->input->method(TRUE)) !== 'POST') {
            $this->output->set_status_header(405);
            $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => FALSE)));
            return;
        }

        $user_id = isset($this->current_user['user_id']) ? (int) $this->current_user['user_id'] : 0;
        if ($user_id < 1) {
            $this->output->set_status_header(401);
            $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => FALSE)));
            return;
        }

        $db = $this->load->database('default', TRUE);
        if (!$db->table_exists('app_notification')) {
            $this->output->set_status_header(503);
            $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => FALSE)));
            return;
        }

        $fields = array();
        foreach ((array) $db->field_data('app_notification') as $field) {
            if (isset($field->name)) {
                $fields[(string) $field->name] = TRUE;
            }
        }
        if (!isset($fields['user_id'], $fields['view_flag'])) {
            $this->output->set_status_header(503);
            $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => FALSE)));
            return;
        }

        $updated = $db->where('user_id', $user_id)
            ->where('view_flag', 0)
            ->update('app_notification', array('view_flag' => 1));
        if ($updated === FALSE) {
            $this->output->set_status_header(500);
            $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => FALSE)));
            return;
        }

        $this->output->set_content_type('application/json')->set_output(json_encode(array(
            'success' => TRUE,
            'csrf_hash' => $this->security->get_csrf_hash(),
        )));
    }

    public function delete()
    {
        if (strtoupper((string) $this->input->method(TRUE)) !== 'POST') {
            $this->output->set_status_header(405);
            return;
        }
        $user_id = isset($this->current_user['user_id']) ? (int) $this->current_user['user_id'] : 0;
        $notification_id = $this->input->post('notification_id', TRUE);
        if ($user_id < 1 || $notification_id === NULL || !ctype_digit((string) $notification_id) || (int) $notification_id < 1) {
            show_error('Not Found', 404);
            return;
        }
        $db = $this->load->database('default', TRUE);
        if (!$db->table_exists('app_notification')) {
            show_error('Service Unavailable', 503);
            return;
        }
        $fields = array();
        foreach ((array) $db->field_data('app_notification') as $field) {
            if (isset($field->name)) { $fields[(string) $field->name] = TRUE; }
        }
        if (!isset($fields['notification_id'], $fields['user_id'])) {
            show_error('Service Unavailable', 503);
            return;
        }
        $deleted = $db->where('notification_id', (int) $notification_id)
            ->where('user_id', $user_id)
            ->delete('app_notification');
        if ($deleted === FALSE) {
            show_error('Unable to delete notification', 500);
            return;
        }
        redirect('/admin/notifications');
    }
}
