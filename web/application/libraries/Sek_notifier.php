<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Create in-app notifications and enqueue push messages per device. */
class Sek_notifier
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function notify_user($user_id, $type, $title, $body, $data = array())
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return NULL;
        }
        $now = gmdate('Y-m-d H:i:s');
        $json = $data ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : NULL;
        $this->CI->db->insert('sek_notification', array(
            'user_id' => $user_id,
            'type' => (string) $type,
            'title' => mb_substr((string) $title, 0, 150),
            'body' => mb_substr((string) $body, 0, 500),
            'data_json' => $json,
            'created_at' => $now,
        ));
        $notification_id = (int) $this->CI->db->insert_id();

        $devices = $this->CI->db->select('device_id, push_token')
            ->where('user_id', $user_id)
            ->where('revoked_at IS NULL', NULL, FALSE)
            ->where('push_token IS NOT NULL', NULL, FALSE)
            ->where('push_token !=', '')
            ->get('sek_device')
            ->result_array();
        foreach ($devices as $device) {
            $this->CI->db->insert('sek_push_queue', array(
                'notification_id' => $notification_id,
                'user_id' => $user_id,
                'device_id' => (int) $device['device_id'],
                'push_token' => (string) $device['push_token'],
                'title' => mb_substr((string) $title, 0, 150),
                'body' => mb_substr((string) $body, 0, 500),
                'data_json' => $json,
                'status' => 'pending',
                'next_attempt_at' => $now,
                'created_at' => $now,
            ));
        }
        return $notification_id;
    }

    public function notify_users($user_ids, $type, $title, $body, $data = array())
    {
        $sent = array();
        foreach (array_unique(array_map('intval', $user_ids)) as $user_id) {
            if ($user_id > 0 && !isset($sent[$user_id])) {
                $this->notify_user($user_id, $type, $title, $body, $data);
                $sent[$user_id] = TRUE;
            }
        }
        return $sent;
    }

    /**
     * Enqueue a push-only message (no in-app notification row).
     * Used for call invitations/cancellations so nothing lingers in the feed.
     */
    public function push_users($user_ids, $title, $body, $data = array())
    {
        $now = gmdate('Y-m-d H:i:s');
        $json = $data ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : NULL;
        $sent = array();
        foreach (array_unique(array_map('intval', $user_ids)) as $user_id) {
            if ($user_id <= 0 || isset($sent[$user_id])) {
                continue;
            }
            $devices = $this->CI->db->select('device_id, push_token')
                ->where('user_id', $user_id)
                ->where('revoked_at IS NULL', NULL, FALSE)
                ->where('push_token IS NOT NULL', NULL, FALSE)
                ->where('push_token !=', '')
                ->get('sek_device')
                ->result_array();
            foreach ($devices as $device) {
                $this->CI->db->insert('sek_push_queue', array(
                    'notification_id' => NULL,
                    'user_id' => $user_id,
                    'device_id' => (int) $device['device_id'],
                    'push_token' => (string) $device['push_token'],
                    'title' => mb_substr((string) $title, 0, 150),
                    'body' => mb_substr((string) $body, 0, 500),
                    'data_json' => $json,
                    'status' => 'pending',
                    'next_attempt_at' => $now,
                    'created_at' => $now,
                ));
            }
            $sent[$user_id] = TRUE;
        }
        return $sent;
    }
}
