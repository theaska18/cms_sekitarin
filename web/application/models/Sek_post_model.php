<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Posts, carousel media, likes, and comments. */
class Sek_post_model extends CI_Model
{
    public function find($post_id)
    {
        return $this->db->select('p.*, a.type_code, a.handle, a.display_name, a.avatar_media_id, '
                . 'a.status AS account_status, t.label_id')
            ->from('sek_post p')
            ->join('sek_account a', 'a.account_id = p.account_id', 'inner')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('p.post_id', (int) $post_id)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function create($user_id, $account_id, $caption, $media_ids)
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('sek_post', array(
            'user_id' => (int) $user_id,
            'account_id' => (int) $account_id,
            'caption' => $caption !== NULL && trim((string) $caption) !== '' ? trim((string) $caption) : NULL,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $post_id = (int) $this->db->insert_id();
        $order = 0;
        foreach (array_values($media_ids) as $media_id) {
            $this->db->insert('sek_post_media', array(
                'post_id' => $post_id,
                'media_id' => (int) $media_id,
                'sort_order' => $order++,
            ));
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? $post_id : NULL;
    }

    public function update($post_id, $caption, $media_ids = NULL)
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->where('post_id', (int) $post_id)
            ->update('sek_post', array(
                'caption' => $caption !== NULL && trim((string) $caption) !== ''
                    ? trim((string) $caption) : NULL,
                'updated_at' => $now,
            ));
        if (is_array($media_ids)) {
            $this->db->where('post_id', (int) $post_id)->delete('sek_post_media');
            $order = 0;
            foreach (array_values($media_ids) as $media_id) {
                $this->db->insert('sek_post_media', array(
                    'post_id' => (int) $post_id,
                    'media_id' => (int) $media_id,
                    'sort_order' => $order++,
                ));
            }
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function soft_delete($post_id)
    {
        return $this->db->where('post_id', (int) $post_id)
            ->update('sek_post', array(
                'status' => 'deleted',
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ));
    }

    public function feed($account_ids, $before_id, $limit)
    {
        if (!$account_ids) {
            return array();
        }
        $this->db->select('p.*, a.type_code, a.handle, a.display_name, a.avatar_media_id, '
                . 'a.status AS account_status, t.label_id')
            ->from('sek_post p')
            ->join('sek_account a', 'a.account_id = p.account_id', 'inner')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('p.status', 'active')
            ->where('a.status', 'active')
            ->where_in('p.account_id', array_map('intval', $account_ids));
        if ($before_id !== NULL && (int) $before_id > 0) {
            $this->db->where('p.post_id <', (int) $before_id);
        }
        return $this->db->order_by('p.post_id', 'DESC')
            ->limit(max(1, min(50, (int) $limit)) + 1)
            ->get()
            ->result_array();
    }

    public function profile_posts($account_id, $before_id, $limit)
    {
        $this->db->select('p.*, a.type_code, a.handle, a.display_name, a.avatar_media_id, '
                . 'a.status AS account_status, t.label_id')
            ->from('sek_post p')
            ->join('sek_account a', 'a.account_id = p.account_id', 'inner')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('p.status', 'active')
            ->where('p.account_id', (int) $account_id)
            ->where('a.status', 'active');
        if ($before_id !== NULL && (int) $before_id > 0) {
            $this->db->where('p.post_id <', (int) $before_id);
        }
        return $this->db->order_by('p.post_id', 'DESC')
            ->limit(max(1, min(50, (int) $limit)) + 1)
            ->get()
            ->result_array();
    }

    public function media_for($post_ids)
    {
        if (!$post_ids) {
            return array();
        }
        $rows = $this->db->select('pm.post_id, pm.sort_order, m.media_id, m.purpose, m.mime, m.size_bytes, '
                . 'm.width, m.height, m.status')
            ->from('sek_post_media pm')
            ->join('sek_media m', 'm.media_id = pm.media_id', 'inner')
            ->where_in('pm.post_id', array_map('intval', $post_ids))
            ->order_by('pm.post_id', 'ASC')
            ->order_by('pm.sort_order', 'ASC')
            ->get()
            ->result_array();
        $grouped = array();
        foreach ($rows as $row) {
            $grouped[(int) $row['post_id']][] = $row;
        }
        return $grouped;
    }

    public function counts($post_ids, $viewer_user_id)
    {
        if (!$post_ids) {
            return array();
        }
        $post_ids = array_map('intval', $post_ids);
        $result = array();
        foreach ($post_ids as $post_id) {
            $result[$post_id] = array('like_count' => 0, 'comment_count' => 0, 'liked' => FALSE);
        }

        $likes = $this->db->select('post_id, COUNT(*) AS total')
            ->where_in('post_id', $post_ids)
            ->group_by('post_id')
            ->get('sek_post_like')
            ->result_array();
        foreach ($likes as $row) {
            $result[(int) $row['post_id']]['like_count'] = (int) $row['total'];
        }

        $comments = $this->db->select('post_id, COUNT(*) AS total')
            ->where_in('post_id', $post_ids)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->group_by('post_id')
            ->get('sek_post_comment')
            ->result_array();
        foreach ($comments as $row) {
            $result[(int) $row['post_id']]['comment_count'] = (int) $row['total'];
        }

        $liked = $this->db->select('post_id')
            ->where_in('post_id', $post_ids)
            ->where('user_id', (int) $viewer_user_id)
            ->get('sek_post_like')
            ->result_array();
        foreach ($liked as $row) {
            $result[(int) $row['post_id']]['liked'] = TRUE;
        }
        return $result;
    }

    public function like($post_id, $user_id)
    {
        $existing = $this->db->where('post_id', (int) $post_id)
            ->where('user_id', (int) $user_id)
            ->count_all_results('sek_post_like');
        if ($existing > 0) {
            return TRUE;
        }
        return $this->db->insert('sek_post_like', array(
            'post_id' => (int) $post_id,
            'user_id' => (int) $user_id,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ));
    }

    public function unlike($post_id, $user_id)
    {
        return $this->db->where('post_id', (int) $post_id)
            ->where('user_id', (int) $user_id)
            ->delete('sek_post_like');
    }

    public function comments($post_id, $before_id, $limit)
    {
        $this->db->select('c.*, u.display_name')
            ->from('sek_post_comment c')
            ->join('sek_user u', 'u.user_id = c.user_id', 'inner')
            ->where('c.post_id', (int) $post_id)
            ->where('c.deleted_at IS NULL', NULL, FALSE);
        if ($before_id !== NULL && (int) $before_id > 0) {
            $this->db->where('c.comment_id <', (int) $before_id);
        }
        return $this->db->order_by('c.comment_id', 'DESC')
            ->limit(max(1, min(100, (int) $limit)) + 1)
            ->get()
            ->result_array();
    }

    public function comment($comment_id)
    {
        return $this->db->where('comment_id', (int) $comment_id)
            ->limit(1)
            ->get('sek_post_comment')
            ->row_array();
    }

    public function add_comment($post_id, $user_id, $body)
    {
        $this->db->insert('sek_post_comment', array(
            'post_id' => (int) $post_id,
            'user_id' => (int) $user_id,
            'body' => (string) $body,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function delete_comment($comment_id)
    {
        return $this->db->where('comment_id', (int) $comment_id)
            ->update('sek_post_comment', array('deleted_at' => gmdate('Y-m-d H:i:s')));
    }
}
