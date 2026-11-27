<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Conversations, messages, receipts, and shared live location. */
class Sek_chat_model extends CI_Model
{
    public function find($conversation_id)
    {
        return $this->db->where('conversation_id', (int) $conversation_id)
            ->limit(1)
            ->get('sek_conversation')
            ->row_array();
    }

    public function membership($conversation_id, $user_id, $active_only = TRUE)
    {
        $this->db->where('conversation_id', (int) $conversation_id)
            ->where('user_id', (int) $user_id);
        if ($active_only) {
            $this->db->where('left_at IS NULL', NULL, FALSE);
        }
        return $this->db->limit(1)->get('sek_conversation_member')->row_array();
    }

    public function members($conversation_id, $active_only = TRUE)
    {
        $this->db->select('m.user_id, m.role, m.last_read_message_id, m.joined_at, m.left_at, m.muted_at, '
                . 'u.email, u.display_name, pa.account_id AS personal_account_id, '
                . 'pa.handle AS personal_handle, pa.avatar_media_id AS personal_avatar_media_id')
            ->from('sek_conversation_member m')
            ->join('sek_user u', 'u.user_id = m.user_id', 'inner')
            ->join('sek_account pa', 'pa.user_id = m.user_id AND pa.type_code = "personal"', 'left')
            ->where('m.conversation_id', (int) $conversation_id);
        if ($active_only) {
            $this->db->where('m.left_at IS NULL', NULL, FALSE);
        }
        return $this->db->order_by('m.joined_at', 'ASC')->get()->result_array();
    }

    public function member_detail($conversation_id, $user_id)
    {
        return $this->db->select('m.user_id, m.role, m.last_read_message_id, m.joined_at, m.left_at, m.muted_at, '
                . 'u.email, u.display_name, pa.account_id AS personal_account_id, '
                . 'pa.handle AS personal_handle, pa.avatar_media_id AS personal_avatar_media_id')
            ->from('sek_conversation_member m')
            ->join('sek_user u', 'u.user_id = m.user_id', 'inner')
            ->join('sek_account pa', 'pa.user_id = m.user_id AND pa.type_code = "personal"', 'left')
            ->where('m.conversation_id', (int) $conversation_id)
            ->where('m.user_id', (int) $user_id)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function direct_key($user_a, $user_b)
    {
        $ids = array((int) $user_a, (int) $user_b);
        sort($ids, SORT_NUMERIC);
        return $ids[0] . ':' . $ids[1];
    }

    public function find_direct($user_a, $user_b)
    {
        return $this->db->where('type', 'direct')
            ->where('direct_key', $this->direct_key($user_a, $user_b))
            ->limit(1)
            ->get('sek_conversation')
            ->row_array();
    }

    public function find_direct_accounts($account_a, $account_b)
    {
        $ids = array((int) $account_a, (int) $account_b);
        sort($ids, SORT_NUMERIC);
        return $this->db->where('type', 'direct')
            ->where('direct_account_a_id', $ids[0])
            ->where('direct_account_b_id', $ids[1])
            ->limit(1)
            ->get('sek_conversation')
            ->row_array();
    }

    public function create_conversation($type, $creator_user_id, $title, $member_user_ids, $account_a = NULL, $account_b = NULL)
    {
        $now = gmdate('Y-m-d H:i:s');
        $member_user_ids = array_values(array_unique(array_map('intval', $member_user_ids)));
        $is_direct = (string) $type === 'direct';
        $account_pair = $is_direct && $account_a !== NULL && $account_b !== NULL
            ? array((int) $account_a, (int) $account_b)
            : NULL;
        if (is_array($account_pair)) {
            sort($account_pair, SORT_NUMERIC);
        }
        $this->db->trans_start();
        $this->db->insert('sek_conversation', array(
            'type' => (string) $type,
            // New direct chats are keyed by account pair; direct_key stays for
            // legacy chats that predate account-scoped conversations.
            'direct_key' => $is_direct && $account_pair === NULL
                ? $this->direct_key($creator_user_id, $member_user_ids[0])
                : NULL,
            'direct_account_a_id' => is_array($account_pair) ? $account_pair[0] : NULL,
            'direct_account_b_id' => is_array($account_pair) ? $account_pair[1] : NULL,
            'title' => $title !== NULL && trim((string) $title) !== '' ? trim((string) $title) : NULL,
            'created_by_user_id' => (int) $creator_user_id,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $conversation_id = (int) $this->db->insert_id();

        $all = array_merge(array((int) $creator_user_id), $member_user_ids);
        foreach (array_unique($all) as $user_id) {
            if ($user_id <= 0) {
                continue;
            }
            $this->db->insert('sek_conversation_member', array(
                'conversation_id' => $conversation_id,
                'user_id' => $user_id,
                'role' => $user_id === (int) $creator_user_id ? 'owner' : 'member',
                'joined_at' => $now,
            ));
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? $conversation_id : NULL;
    }

    public function update_conversation($conversation_id, $fields)
    {
        if (!$fields) {
            return TRUE;
        }
        $fields['updated_at'] = gmdate('Y-m-d H:i:s');
        return $this->db->where('conversation_id', (int) $conversation_id)
            ->update('sek_conversation', $fields);
    }

    public function add_member($conversation_id, $user_id, $role = 'member')
    {
        $existing = $this->membership($conversation_id, $user_id, FALSE);
        $now = gmdate('Y-m-d H:i:s');
        if (is_array($existing)) {
            return $this->db->where('conversation_id', (int) $conversation_id)
                ->where('user_id', (int) $user_id)
                ->update('sek_conversation_member', array('left_at' => NULL, 'role' => (string) $role));
        }
        return $this->db->insert('sek_conversation_member', array(
            'conversation_id' => (int) $conversation_id,
            'user_id' => (int) $user_id,
            'role' => (string) $role,
            'joined_at' => $now,
        ));
    }

    public function leave($conversation_id, $user_id)
    {
        return $this->db->where('conversation_id', (int) $conversation_id)
            ->where('user_id', (int) $user_id)
            ->where('left_at IS NULL', NULL, FALSE)
            ->update('sek_conversation_member', array('left_at' => gmdate('Y-m-d H:i:s')));
    }

    public function list_conversations($user_id, $limit = 50)
    {
        // All conversations for the user are shown in one list; the account
        // pair determines which identity each direct row belongs to.
        return $this->db->select('c.*, m.role, m.last_read_message_id, m.muted_at, m.pinned_at')
            ->from('sek_conversation c')
            ->join('sek_conversation_member m', 'm.conversation_id = c.conversation_id', 'inner')
            ->where('m.user_id', (int) $user_id)
            ->where('m.left_at IS NULL', NULL, FALSE)
            ->order_by('(m.pinned_at IS NOT NULL)', 'DESC', FALSE)
            ->order_by('COALESCE(c.last_message_at, c.created_at)', 'DESC', FALSE)
            ->limit(max(1, min(100, (int) $limit)))
            ->get()
            ->result_array();
    }

    public function set_pinned($conversation_id, $user_id, $pinned)
    {
        return $this->db->where('conversation_id', (int) $conversation_id)
            ->where('user_id', (int) $user_id)
            ->update('sek_conversation_member', array(
                'pinned_at' => $pinned ? gmdate('Y-m-d H:i:s') : NULL,
            ));
    }

    public function last_message($conversation_id, $after_message_id = 0, $viewer_user_id = 0)
    {
        $this->db->select('m.*, COALESCE(sa.display_name, su.display_name) AS sender_name')
            ->from('sek_message m')
            ->join('sek_user su', 'su.user_id = m.sender_user_id', 'left')
            ->join('sek_account sa', 'sa.account_id = m.sender_account_id', 'left')
            ->where('m.conversation_id', (int) $conversation_id);
        if ((int) $after_message_id > 0) {
            $this->db->where('m.message_id >', (int) $after_message_id);
        }
        if ((int) $viewer_user_id > 0) {
            $this->db->where(
                'NOT EXISTS (SELECT 1 FROM sek_message_hidden h WHERE h.message_id = m.message_id AND h.user_id = ' . (int) $viewer_user_id . ')',
                NULL,
                FALSE
            );
        }
        return $this->db->order_by('m.message_id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function max_message_id($conversation_id)
    {
        $row = $this->db->select_max('message_id')
            ->where('conversation_id', (int) $conversation_id)
            ->get('sek_message')
            ->row_array();
        return is_array($row) && $row['message_id'] !== NULL ? (int) $row['message_id'] : 0;
    }

    public function clear_history($conversation_id, $user_id, $message_id)
    {
        return $this->db->where('conversation_id', (int) $conversation_id)
            ->where('user_id', (int) $user_id)
            ->update('sek_conversation_member', array(
                'cleared_before_message_id' => (int) $message_id,
                'last_read_message_id' => (int) $message_id,
            ));
    }

    public function unread_count($conversation_id, $user_id, $last_read_message_id, $cleared_before_message_id = 0)
    {
        $after = max((int) $last_read_message_id, (int) $cleared_before_message_id);
        return (int) $this->db->where('conversation_id', (int) $conversation_id)
            ->where('sender_user_id !=', (int) $user_id)
            ->where('message_id >', $after)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->count_all_results('sek_message');
    }

    public function message_count($conversation_id)
    {
        return (int) $this->db->where('conversation_id', (int) $conversation_id)
            ->where('deleted_at IS NULL', NULL, FALSE)
            ->count_all_results('sek_message');
    }

    public function messages($conversation_id, $limit = 50, $before_id = NULL, $after_id = NULL, $cleared_before_id = 0, $viewer_user_id = 0)
    {
        $limit = max(1, min(100, (int) $limit));
        $hidden = (int) $viewer_user_id > 0
            ? 'NOT EXISTS (SELECT 1 FROM sek_message_hidden h WHERE h.message_id = m.message_id AND h.user_id = ' . (int) $viewer_user_id . ')'
            : NULL;
        $this->db->select('m.*, COALESCE(sa.display_name, su.display_name) AS sender_name')
            ->from('sek_message m')
            ->join('sek_user su', 'su.user_id = m.sender_user_id', 'left')
            ->join('sek_account sa', 'sa.account_id = m.sender_account_id', 'left')
            ->where('m.conversation_id', (int) $conversation_id);
        if ($hidden !== NULL) {
            $this->db->where($hidden, NULL, FALSE);
        }
        if ($after_id !== NULL && (int) $after_id > 0) {
            $this->db->where('m.message_id >', max((int) $after_id, (int) $cleared_before_id))
                ->order_by('m.message_id', 'ASC')
                ->limit($limit);
            return $this->db->get()->result_array();
        }
        if ((int) $cleared_before_id > 0) {
            $this->db->where('m.message_id >', (int) $cleared_before_id);
        }
        if ($before_id !== NULL && (int) $before_id > 0) {
            $this->db->where('m.message_id <', (int) $before_id);
        }
        $rows = $this->db->order_by('m.message_id', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
        return array_reverse($rows);
    }

    public function set_reaction($message_id, $user_id, $reaction)
    {
        if ((string) $reaction === '') {
            return $this->db->where('message_id', (int) $message_id)
                ->where('user_id', (int) $user_id)
                ->delete('sek_message_reaction');
        }
        return $this->db->query(
            'INSERT INTO sek_message_reaction (message_id, user_id, reaction, created_at) VALUES (?, ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = VALUES(created_at)',
            array((int) $message_id, (int) $user_id, (string) $reaction, gmdate('Y-m-d H:i:s'))
        );
    }

    public function reaction_summary($message_ids, $viewer_user_id = 0)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $message_ids)));
        if (!$ids) {
            return array();
        }
        $rows = $this->db->select('message_id, reaction, user_id')
            ->where_in('message_id', $ids)
            ->get('sek_message_reaction')
            ->result_array();
        $summary = array();
        foreach ($rows as $row) {
            $message_id = (int) $row['message_id'];
            if (!isset($summary[$message_id])) {
                $summary[$message_id] = array('reactions' => array(), 'my_reaction' => NULL);
            }
            $reaction = (string) $row['reaction'];
            $summary[$message_id]['reactions'][$reaction] =
                ($summary[$message_id]['reactions'][$reaction] ?? 0) + 1;
            if ((int) $row['user_id'] === (int) $viewer_user_id) {
                $summary[$message_id]['my_reaction'] = $reaction;
            }
        }
        return $summary;
    }

    public function hide_message($message_id, $user_id)
    {
        return $this->db->query(
            'INSERT INTO sek_message_hidden (message_id, user_id, hidden_at) VALUES (?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE hidden_at = VALUES(hidden_at)',
            array((int) $message_id, (int) $user_id, gmdate('Y-m-d H:i:s'))
        );
    }

    public function receipt_counts($message_ids)
    {
        if (!$message_ids) {
            return array();
        }
        $rows = $this->db->select('message_id, COUNT(*) AS delivered_count, '
                . 'SUM(read_at IS NOT NULL) AS read_count')
            ->where_in('message_id', array_map('intval', $message_ids))
            ->group_by('message_id')
            ->get('sek_message_receipt')
            ->result_array();
        $counts = array();
        foreach ($rows as $row) {
            $counts[(int) $row['message_id']] = array(
                'delivered' => (int) $row['delivered_count'],
                'read' => (int) $row['read_count'],
            );
        }
        return $counts;
    }

    public function mark_delivered($conversation_id, $user_id, $message_ids)
    {
        $message_ids = array_values(array_filter(array_map('intval', $message_ids)));
        if (!$message_ids) {
            return;
        }
        $now = gmdate('Y-m-d H:i:s');
        $this->db->query(
            'INSERT INTO sek_message_receipt (message_id, user_id, delivered_at) '
            . 'SELECT message_id, ?, ? FROM sek_message '
            . 'WHERE conversation_id = ? AND sender_user_id <> ? AND message_id IN (' . implode(',', array_fill(0, count($message_ids), '?')) . ') '
            . 'ON DUPLICATE KEY UPDATE delivered_at = COALESCE(delivered_at, VALUES(delivered_at))',
            array_merge(array((int) $user_id, $now, (int) $conversation_id, (int) $user_id), $message_ids)
        );
    }

    public function create_message($conversation_id, $sender_user_id, $sender_account_id, $data)
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('sek_message', array(
            'conversation_id' => (int) $conversation_id,
            'sender_user_id' => (int) $sender_user_id,
            'sender_account_id' => $sender_account_id !== NULL ? (int) $sender_account_id : NULL,
            'type' => (string) $data['type'],
            'body' => isset($data['body']) ? $data['body'] : NULL,
            'media_id' => isset($data['media_id']) ? $data['media_id'] : NULL,
            'latitude' => isset($data['latitude']) ? $data['latitude'] : NULL,
            'longitude' => isset($data['longitude']) ? $data['longitude'] : NULL,
            'location_label' => isset($data['location_label']) ? $data['location_label'] : NULL,
            'reply_to_message_id' => isset($data['reply_to_message_id']) ? $data['reply_to_message_id'] : NULL,
            'created_at' => $now,
        ));
        $message_id = (int) $this->db->insert_id();
        $this->db->where('conversation_id', (int) $conversation_id)
            ->update('sek_conversation', array('last_message_at' => $now, 'updated_at' => $now));
        $this->db->trans_complete();
        return $this->db->trans_status() ? $message_id : NULL;
    }

    public function message($message_id)
    {
        return $this->db->select('m.*, COALESCE(sa.display_name, su.display_name) AS sender_name')
            ->from('sek_message m')
            ->join('sek_user su', 'su.user_id = m.sender_user_id', 'left')
            ->join('sek_account sa', 'sa.account_id = m.sender_account_id', 'left')
            ->where('m.message_id', (int) $message_id)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function update_message($message_id, $body)
    {
        return $this->db->where('message_id', (int) $message_id)
            ->update('sek_message', array(
                'body' => (string) $body,
                'edited_at' => gmdate('Y-m-d H:i:s'),
            ));
    }

    public function delete_message($message_id)
    {
        return $this->db->where('message_id', (int) $message_id)
            ->update('sek_message', array('deleted_at' => gmdate('Y-m-d H:i:s')));
    }

    public function mark_read($conversation_id, $user_id, $message_id)
    {
        $member = $this->membership($conversation_id, $user_id);
        if (!is_array($member)) {
            return FALSE;
        }
        $previous = (int) $member['last_read_message_id'];
        $now = gmdate('Y-m-d H:i:s');
        $this->db->where('conversation_id', (int) $conversation_id)
            ->where('user_id', (int) $user_id)
            ->update('sek_conversation_member', array(
                'last_read_message_id' => max($previous, (int) $message_id),
            ));

        $this->db->query(
            'INSERT INTO sek_message_receipt (message_id, user_id, delivered_at, read_at) '
            . 'SELECT message_id, ?, ?, ? FROM sek_message '
            . 'WHERE conversation_id = ? AND sender_user_id <> ? AND message_id <= ? AND message_id > ? '
            . 'ON DUPLICATE KEY UPDATE delivered_at = VALUES(delivered_at), read_at = VALUES(read_at)',
            array((int) $user_id, $now, $now, (int) $conversation_id, (int) $user_id, (int) $message_id, $previous)
        );
        return TRUE;
    }

    public function live_location_upsert($conversation_id, $user_id, $lat, $lng, $accuracy, $expires_at)
    {
        return $this->db->query(
            'INSERT INTO sek_live_location (conversation_id, user_id, location, accuracy_m, expires_at, updated_at) '
            . 'VALUES (?, ?, ST_SRID(POINT(?, ?), 4326), ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE location = VALUES(location), accuracy_m = VALUES(accuracy_m), '
            . 'expires_at = VALUES(expires_at), updated_at = VALUES(updated_at)',
            array(
                (int) $conversation_id,
                (int) $user_id,
                (float) $lng,
                (float) $lat,
                $accuracy !== NULL ? (int) $accuracy : NULL,
                (string) $expires_at,
                gmdate('Y-m-d H:i:s'),
            )
        );
    }

    public function live_locations($conversation_id)
    {
        return $this->db->select('l.user_id, ST_X(l.location) AS lat, ST_Y(l.location) AS lng, '
                . 'l.accuracy_m, l.expires_at, l.updated_at, u.display_name')
            ->from('sek_live_location l')
            ->join('sek_user u', 'u.user_id = l.user_id', 'inner')
            ->where('l.conversation_id', (int) $conversation_id)
            ->where('l.expires_at >', gmdate('Y-m-d H:i:s'))
            ->get()
            ->result_array();
    }

    public function delete_live_location($conversation_id, $user_id)
    {
        return $this->db->where('conversation_id', (int) $conversation_id)
            ->where('user_id', (int) $user_id)
            ->delete('sek_live_location');
    }
}
