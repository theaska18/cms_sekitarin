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

    public function referenced_count($media_id)
    {
        return $this->db->where('avatar_media_id', (int) $media_id)
            ->or_where('cover_media_id', (int) $media_id)
            ->count_all_results('sek_account');
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
