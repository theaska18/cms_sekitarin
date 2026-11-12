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
        foreach (array('heading', 'intro', 'unavailable', 'empty', 'empty_hint', 'pagination', 'previous', 'next') as $key) {
            $data['notifications_' . $key] = $this->lang->line('notifications_' . $key);
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
                foreach (array('created_at', 'created_date', 'date_created') as $candidate) {
                    if (isset($fields[$candidate])) {
                        $select[] = $candidate;
                        $order_date_field = $candidate;
                        break;
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
                        );
                    }
                }
            }
        }

        $this->render_admin('admin/notifications/index', 'notifications_heading', '/admin/notifications', $data);
    }
}
