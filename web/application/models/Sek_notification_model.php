<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** In-app notification feed. */
class Sek_notification_model extends CI_Model
{
    public function list_for_user($user_id, $before_id, $limit, $include_chat = FALSE)
    {
        $this->db->where('user_id', (int) $user_id);
        if (!$include_chat) {
            $this->db->where('type !=', 'chat_message');
        }
        if ($before_id !== NULL && (int) $before_id > 0) {
            $this->db->where('notification_id <', (int) $before_id);
        }
        return $this->db->order_by('notification_id', 'DESC')
            ->limit(max(1, min(50, (int) $limit)) + 1)
            ->get('sek_notification')
            ->result_array();
    }

    public function unread_count($user_id, $exclude_chat = FALSE)
    {
        $this->db->where('user_id', (int) $user_id)
            ->where('read_at IS NULL', NULL, FALSE);
        if ($exclude_chat) {
            $this->db->where('type !=', 'chat_message');
        }
        return (int) $this->db->count_all_results('sek_notification');
    }

    public function unread_chat_count($user_id)
    {
        return (int) $this->db->where('user_id', (int) $user_id)
            ->where('read_at IS NULL', NULL, FALSE)
            ->where('type', 'chat_message')
            ->count_all_results('sek_notification');
    }

    public function find($notification_id, $user_id)
    {
        return $this->db->where('notification_id', (int) $notification_id)
            ->where('user_id', (int) $user_id)
            ->limit(1)
            ->get('sek_notification')
            ->row_array();
    }

    public function mark_read($notification_id, $user_id)
    {
        return $this->db->where('notification_id', (int) $notification_id)
            ->where('user_id', (int) $user_id)
            ->update('sek_notification', array('read_at' => gmdate('Y-m-d H:i:s')));
    }

    public function mark_all_read($user_id)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->where('read_at IS NULL', NULL, FALSE)
            ->update('sek_notification', array('read_at' => gmdate('Y-m-d H:i:s')));
    }
}
