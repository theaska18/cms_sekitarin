<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** In-app notification feed and read state. */
class Sek_notifications extends Sek_api_base
{
    public function index()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $limit = (int) $this->input->get('limit', TRUE);
        $limit = $limit > 0 ? min(50, $limit) : 20;
        $before = (int) $this->input->get('before_id', TRUE);
        $rows = $this->sek_notification_model->list_for_user($this->api_auth->user_id(), $before, $limit);
        $has_more = count($rows) > $limit;
        if ($has_more) {
            $rows = array_slice($rows, 0, $limit);
        }
        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->notification_payload($row);
        }
        $this->api_response->ok(array(
            'data' => $data,
            'unread_count' => $this->sek_notification_model->unread_count($this->api_auth->user_id()),
            'has_more' => $has_more,
        ));
    }

    public function read($notification_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $notification = $this->sek_notification_model->find((int) $notification_id, $this->api_auth->user_id());
        if (!is_array($notification)) {
            $this->api_response->error('not_found', 'Notifikasi tidak ditemukan.', 404);
            return;
        }
        $this->sek_notification_model->mark_read((int) $notification_id, $this->api_auth->user_id());
        $this->api_response->ok(array(
            'status' => 'read',
            'notification_id' => (int) $notification_id,
            'unread_count' => $this->sek_notification_model->unread_count($this->api_auth->user_id()),
        ));
    }

    public function read_all()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $this->sek_notification_model->mark_all_read($this->api_auth->user_id());
        $this->api_response->ok(array('status' => 'read_all', 'unread_count' => 0));
    }

    private function notification_payload($row)
    {
        $data = array();
        if (!empty($row['data_json'])) {
            $decoded = json_decode((string) $row['data_json'], TRUE);
            $data = is_array($decoded) ? $decoded : array();
        }
        return array(
            'notification_id' => (int) $row['notification_id'],
            'type' => (string) $row['type'],
            'title' => (string) $row['title'],
            'body' => (string) $row['body'],
            'data' => $data,
            'read' => !empty($row['read_at']),
            'read_at' => $this->utc($row['read_at']),
            'created_at' => $this->utc($row['created_at']),
        );
    }
}
