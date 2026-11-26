<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Mobile identity persistence for Sekitar. */
class Sek_user_model extends CI_Model
{
    public function find($user_id)
    {
        return $this->db->select('user_id, email, password_hash, email_verified_at, display_name, status, last_login_at, created_at, updated_at')
            ->where('user_id', (int) $user_id)
            ->limit(1)
            ->get('sek_user')
            ->row_array();
    }

    public function find_by_email($email)
    {
        return $this->db->select('user_id, email, password_hash, email_verified_at, display_name, status, last_login_at, created_at, updated_at')
            ->where('email', strtolower(trim((string) $email)))
            ->limit(1)
            ->get('sek_user')
            ->row_array();
    }

    public function email_exists($email)
    {
        return $this->db->where('email', strtolower(trim((string) $email)))
            ->count_all_results('sek_user') > 0;
    }

    /**
     * Insert the user and its default personal account atomically.
     * Returns array(user_id, account_id, handle) or NULL on failure.
     */
    public function create_with_personal_account($email, $password_hash, $display_name, $handle)
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->trans_start();

        $this->db->insert('sek_user', array(
            'email' => strtolower(trim((string) $email)),
            'password_hash' => (string) $password_hash,
            'display_name' => trim((string) $display_name),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $user_id = (int) $this->db->insert_id();

        $this->db->insert('sek_account', array(
            'user_id' => $user_id,
            'type_code' => 'personal',
            'handle' => (string) $handle,
            'display_name' => trim((string) $display_name),
            'service_radius_m' => 5000,
            'is_online' => 0,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $account_id = (int) $this->db->insert_id();

        $this->db->insert('sek_account_member', array(
            'account_id' => $account_id,
            'user_id' => $user_id,
            'role' => 'owner',
            'status' => 'active',
            'created_at' => $now,
        ));

        $this->db->trans_complete();
        if (!$this->db->trans_status() || $user_id <= 0 || $account_id <= 0) {
            return NULL;
        }

        return array('user_id' => $user_id, 'account_id' => $account_id, 'handle' => (string) $handle);
    }

    public function touch_login($user_id)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->update('sek_user', array('last_login_at' => gmdate('Y-m-d H:i:s')));
    }

    public function mark_email_verified($user_id)
    {
        $now = gmdate('Y-m-d H:i:s');
        return $this->db->where('user_id', (int) $user_id)
            ->update('sek_user', array('email_verified_at' => $now, 'updated_at' => $now));
    }

    public function set_password($user_id, $password_hash)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->update('sek_user', array(
                'password_hash' => (string) $password_hash,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ));
    }

    public function update_profile($user_id, $display_name)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->update('sek_user', array(
                'display_name' => trim((string) $display_name),
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ));
    }

    /** Create a single-use email token and return the raw value. */
    public function create_token($user_id, $purpose, $ttl_seconds)
    {
        $raw = bin2hex(random_bytes(32));
        $now = gmdate('Y-m-d H:i:s');
        $this->db->insert('sek_user_token', array(
            'user_id' => (int) $user_id,
            'purpose' => (string) $purpose,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + (int) $ttl_seconds),
            'created_at' => $now,
        ));
        return $raw;
    }

    /** Consume a valid token; returns the user_id or NULL. */
    public function consume_token($purpose, $raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return NULL;
        }
        $now = gmdate('Y-m-d H:i:s');
        $row = $this->db->select('token_id, user_id')
            ->where('token_hash', hash('sha256', $raw))
            ->where('purpose', (string) $purpose)
            ->where('used_at IS NULL', NULL, FALSE)
            ->where('expires_at >', $now)
            ->limit(2)
            ->get('sek_user_token')
            ->row_array();
        if (!is_array($row)) {
            return NULL;
        }
        $this->db->where('token_id', (int) $row['token_id'])
            ->where('used_at IS NULL', NULL, FALSE)
            ->update('sek_user_token', array('used_at' => $now));
        return (int) $row['user_id'];
    }

    public static function hash_password($password)
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash((string) $password, PASSWORD_ARGON2ID);
        }
        return password_hash((string) $password, PASSWORD_BCRYPT);
    }
}
