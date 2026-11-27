<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Local media records for Sekitar. */
class Sek_media_model extends CI_Model
{
    public function create($user_id, $purpose, $path, $mime, $size, $width = NULL, $height = NULL, $checksum = NULL)
    {
        $this->db->insert('sek_media', array(
            'owner_user_id' => (int) $user_id,
            'purpose' => (string) $purpose,
            'path' => (string) $path,
            'mime' => (string) $mime,
            'size_bytes' => (int) $size,
            'width' => $width !== NULL ? (int) $width : NULL,
            'height' => $height !== NULL ? (int) $height : NULL,
            'checksum_sha256' => $checksum,
            'status' => 'ready',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function find($media_id)
    {
        return $this->db->where('media_id', (int) $media_id)
            ->limit(1)
            ->get('sek_media')
            ->row_array();
    }

    public function delete($media_id)
    {
        return $this->db->where('media_id', (int) $media_id)->delete('sek_media');
    }

    public function is_post_media_visible($media_id)
    {
        return $this->db->select('pm.media_id')
            ->from('sek_post_media pm')
            ->join('sek_post p', 'p.post_id = pm.post_id', 'inner')
            ->join('sek_account a', 'a.account_id = p.account_id', 'inner')
            ->where('pm.media_id', (int) $media_id)
            ->where('p.status', 'active')
            ->where('a.status', 'active')
            ->limit(1)
            ->get()
            ->row_array() !== NULL;
    }

    public function is_chat_media_visible($media_id, $user_id)
    {
        if ((int) $user_id <= 0) {
            return FALSE;
        }
        return $this->db->select('m.message_id')
            ->from('sek_message m')
            ->join('sek_conversation_member cm', 'cm.conversation_id = m.conversation_id', 'inner')
            ->where('m.media_id', (int) $media_id)
            ->where('m.deleted_at IS NULL', NULL, FALSE)
            ->where('cm.user_id', (int) $user_id)
            ->where('cm.left_at IS NULL', NULL, FALSE)
            ->limit(1)
            ->get()
            ->row_array() !== NULL;
    }

    public function referenced_count($media_id)
    {
        $accounts = $this->db->where('avatar_media_id', (int) $media_id)
            ->or_where('cover_media_id', (int) $media_id)
            ->count_all_results('sek_account');
        $posts = $this->db->where('media_id', (int) $media_id)
            ->count_all_results('sek_post_media');
        $messages = $this->db->where('media_id', (int) $media_id)
            ->count_all_results('sek_message');
        return $accounts + $posts + $messages;
    }

    public function is_conversation_avatar_visible($media_id, $user_id)
    {
        return $this->db->from('sek_conversation c')
            ->join('sek_conversation_member m', 'm.conversation_id = c.conversation_id', 'inner')
            ->where('c.avatar_media_id', (int) $media_id)
            ->where('m.user_id', (int) $user_id)
            ->where('m.left_at IS NULL', NULL, FALSE)
            ->count_all_results() > 0;
    }

    public function used_by_active_account($media_id)
    {
        return $this->db->select('account_id')
            ->where('status', 'active')
            ->group_start()
                ->where('avatar_media_id', (int) $media_id)
                ->or_where('cover_media_id', (int) $media_id)
            ->group_end()
            ->limit(1)
            ->get('sek_account')
            ->row_array();
    }
}
