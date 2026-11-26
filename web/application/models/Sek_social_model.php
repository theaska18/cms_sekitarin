<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Contacts, blocks, and reports for Sekitar. */
class Sek_social_model extends CI_Model
{
    public function list_contacts($user_id)
    {
        return $this->db->select('c.contact_id, c.account_id, c.alias, c.created_at, '
                . 'a.type_code, a.handle, a.display_name, a.bio, a.avatar_media_id, a.is_online, '
                . 'a.verified_at, t.label_id')
            ->from('sek_contact c')
            ->join('sek_account a', 'a.account_id = c.account_id', 'inner')
            ->join('sek_account_type t', 't.type_code = a.type_code', 'inner')
            ->where('c.owner_user_id', (int) $user_id)
            ->where('a.status', 'active')
            ->order_by('c.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function find_contact($contact_id, $user_id)
    {
        return $this->db->where('contact_id', (int) $contact_id)
            ->where('owner_user_id', (int) $user_id)
            ->limit(1)
            ->get('sek_contact')
            ->row_array();
    }

    public function contact_exists($user_id, $account_id)
    {
        return $this->db->where('owner_user_id', (int) $user_id)
            ->where('account_id', (int) $account_id)
            ->limit(1)
            ->get('sek_contact')
            ->row_array();
    }

    public function add_contact($user_id, $account_id, $alias = NULL)
    {
        $this->db->insert('sek_contact', array(
            'owner_user_id' => (int) $user_id,
            'account_id' => (int) $account_id,
            'alias' => $alias !== NULL && trim((string) $alias) !== '' ? trim((string) $alias) : NULL,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function update_contact($contact_id, $alias)
    {
        return $this->db->where('contact_id', (int) $contact_id)
            ->update('sek_contact', array(
                'alias' => $alias !== NULL && trim((string) $alias) !== '' ? trim((string) $alias) : NULL,
            ));
    }

    public function delete_contact($contact_id, $user_id)
    {
        return $this->db->where('contact_id', (int) $contact_id)
            ->where('owner_user_id', (int) $user_id)
            ->delete('sek_contact');
    }

    public function list_blocks($account_id)
    {
        return $this->db->select('b.blocker_account_id, b.blocked_account_id, b.reason, b.created_at, '
                . 'a.handle, a.display_name, a.avatar_media_id, a.type_code')
            ->from('sek_block b')
            ->join('sek_account a', 'a.account_id = b.blocked_account_id', 'inner')
            ->where('b.blocker_account_id', (int) $account_id)
            ->order_by('b.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function add_block($blocker_account_id, $blocked_account_id, $reason = NULL)
    {
        $existing = $this->db->where('blocker_account_id', (int) $blocker_account_id)
            ->where('blocked_account_id', (int) $blocked_account_id)
            ->count_all_results('sek_block');
        if ($existing > 0) {
            return TRUE;
        }
        return $this->db->insert('sek_block', array(
            'blocker_account_id' => (int) $blocker_account_id,
            'blocked_account_id' => (int) $blocked_account_id,
            'reason' => $reason !== NULL && trim((string) $reason) !== '' ? trim((string) $reason) : NULL,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ));
    }

    public function remove_block($blocker_account_id, $blocked_account_id)
    {
        return $this->db->where('blocker_account_id', (int) $blocker_account_id)
            ->where('blocked_account_id', (int) $blocked_account_id)
            ->delete('sek_block');
    }

    public function create_report($user_id, $account_id, $category, $description = NULL)
    {
        $this->db->insert('sek_report', array(
            'reporter_user_id' => (int) $user_id,
            'target_account_id' => (int) $account_id,
            'category' => (string) $category,
            'description' => $description !== NULL && trim((string) $description) !== ''
                ? trim((string) $description) : NULL,
            'status' => 'open',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function list_reports($user_id)
    {
        return $this->db->select('r.report_id, r.target_account_id, r.category, r.description, r.status, '
                . 'r.created_at, r.reviewed_at, a.handle, a.display_name, a.type_code')
            ->from('sek_report r')
            ->join('sek_account a', 'a.account_id = r.target_account_id', 'inner')
            ->where('r.reporter_user_id', (int) $user_id)
            ->order_by('r.created_at', 'DESC')
            ->get()
            ->result_array();
    }
}
