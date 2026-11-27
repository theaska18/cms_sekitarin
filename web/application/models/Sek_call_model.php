<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** One-to-one and group voice/video calls powered by Jitsi. */
class Sek_call_model extends CI_Model
{
    const RING_TIMEOUT = 45;
    const GROUP_STALE_HOURS = 4;

    public function create($fields)
    {
        $fields['created_at'] = gmdate('Y-m-d H:i:s');
        if (!isset($fields['status'])) {
            $fields['status'] = 'ringing';
        }
        $this->db->insert('sek_call', $fields);
        return (int) $this->db->insert_id();
    }

    public function find($call_id)
    {
        return $this->db->where('call_id', (int) $call_id)
            ->limit(1)
            ->get('sek_call')
            ->row_array();
    }

    public function for_user($call_id, $user_id)
    {
        return $this->db->where('call_id', (int) $call_id)
            ->where(
                '(caller_user_id = ' . (int) $user_id . ' OR callee_user_id = ' . (int) $user_id . ')',
                NULL,
                FALSE
            )
            ->limit(1)
            ->get('sek_call')
            ->row_array();
    }

    public function list_for_user($user_id, $before_id, $limit)
    {
        $this->db->where('scope', 'direct')
            ->where(
                '(caller_user_id = ' . (int) $user_id . ' OR callee_user_id = ' . (int) $user_id . ')',
                NULL,
                FALSE
            );
        if ($before_id > 0) {
            $this->db->where('call_id <', (int) $before_id);
        }
        return $this->db->order_by('call_id', 'DESC')
            ->limit(max(1, min(100, (int) $limit)) + 1)
            ->get('sek_call')
            ->result_array();
    }

    public function active_for_user($user_id)
    {
        return $this->db->where('scope', 'direct')
            ->where(
                '(caller_user_id = ' . (int) $user_id . ' OR callee_user_id = ' . (int) $user_id . ')',
                NULL,
                FALSE
            )
            ->where_in('status', array('ringing', 'answered'))
            ->order_by('call_id', 'DESC')
            ->limit(1)
            ->get('sek_call')
            ->row_array();
    }

    public function update($call_id, $fields)
    {
        return $this->db->where('call_id', (int) $call_id)
            ->update('sek_call', $fields);
    }

    /** Mark unanswered direct calls as missed. Returns the affected rows. */
    public function expire_stale($seconds = self::RING_TIMEOUT)
    {
        $seconds = max(10, (int) $seconds);
        $rows = $this->db->where('scope', 'direct')
            ->where('status', 'ringing')
            ->where('created_at < UTC_TIMESTAMP() - INTERVAL ' . $seconds . ' SECOND', NULL, FALSE)
            ->get('sek_call')
            ->result_array();
        if (!$rows) {
            return array();
        }
        $ids = array_map(function ($row) { return (int) $row['call_id']; }, $rows);
        $this->db->where_in('call_id', $ids)
            ->update('sek_call', array(
                'status' => 'missed',
                'ended_at' => gmdate('Y-m-d H:i:s'),
            ));
        return $rows;
    }

    /** Close group calls whose last participant left hours ago. */
    public function expire_stale_groups($hours = self::GROUP_STALE_HOURS)
    {
        $hours = max(1, (int) $hours);
        $rows = $this->db->where('scope', 'group')
            ->where('status', 'active')
            ->where('created_at < UTC_TIMESTAMP() - INTERVAL ' . $hours . ' HOUR', NULL, FALSE)
            ->get('sek_call')
            ->result_array();
        if (!$rows) {
            return array();
        }
        $ids = array_map(function ($row) { return (int) $row['call_id']; }, $rows);
        $this->db->where_in('call_id', $ids)
            ->update('sek_call', array(
                'status' => 'ended',
                'ended_at' => gmdate('Y-m-d H:i:s'),
            ));
        return $rows;
    }

    public function recent_by_caller($user_id, $seconds = 60)
    {
        return (int) $this->db->where('caller_user_id', (int) $user_id)
            ->where('created_at > UTC_TIMESTAMP() - INTERVAL ' . max(1, (int) $seconds) . ' SECOND', NULL, FALSE)
            ->count_all_results('sek_call');
    }

    // ----- Group calls -----------------------------------------------------

    public function active_group_call($conversation_id)
    {
        return $this->db->where('scope', 'group')
            ->where('conversation_id', (int) $conversation_id)
            ->where('status', 'active')
            ->order_by('call_id', 'DESC')
            ->limit(1)
            ->get('sek_call')
            ->row_array();
    }

    public function join_participant($call_id, $user_id, $account_id)
    {
        $now = gmdate('Y-m-d H:i:s');
        $existing = $this->db->where('call_id', (int) $call_id)
            ->where('user_id', (int) $user_id)
            ->limit(1)
            ->get('sek_call_participant')
            ->row_array();
        if (is_array($existing)) {
            return $this->db->where('call_id', (int) $call_id)
                ->where('user_id', (int) $user_id)
                ->update('sek_call_participant', array(
                    'left_at' => NULL,
                    'joined_at' => $now,
                    'account_id' => $account_id !== NULL ? (int) $account_id : NULL,
                ));
        }
        return $this->db->insert('sek_call_participant', array(
            'call_id' => (int) $call_id,
            'user_id' => (int) $user_id,
            'account_id' => $account_id !== NULL ? (int) $account_id : NULL,
            'joined_at' => $now,
        ));
    }

    public function leave_participant($call_id, $user_id)
    {
        return $this->db->where('call_id', (int) $call_id)
            ->where('user_id', (int) $user_id)
            ->where('left_at IS NULL', NULL, FALSE)
            ->update('sek_call_participant', array('left_at' => gmdate('Y-m-d H:i:s')));
    }

    public function active_participant_count($call_id)
    {
        return (int) $this->db->where('call_id', (int) $call_id)
            ->where('left_at IS NULL', NULL, FALSE)
            ->count_all_results('sek_call_participant');
    }

    public function is_participant($call_id, $user_id)
    {
        return (int) $this->db->where('call_id', (int) $call_id)
            ->where('user_id', (int) $user_id)
            ->where('left_at IS NULL', NULL, FALSE)
            ->count_all_results('sek_call_participant') > 0;
    }

    public function participant_count_for_calls($conversation_id)
    {
        $call = $this->active_group_call($conversation_id);
        if (!is_array($call)) {
            return NULL;
        }
        return array(
            'call' => $call,
            'participant_count' => $this->active_participant_count((int) $call['call_id']),
        );
    }
}
