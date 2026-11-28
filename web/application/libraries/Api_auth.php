<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Per-device token authentication for the Sekitar mobile API.
 * Access tokens live for one hour; refresh tokens are long-lived (10 years)
 * and stay valid across refreshes, like a messenger app session. Refreshing
 * only rotates the short-lived access token so a stale or duplicated refresh
 * request can never end the session. Sessions end on logout/revoke or when
 * the user account is disabled. Only SHA-256 hashes are stored.
 */
class Api_auth
{
    const ACCESS_TTL = 3600;
    const REFRESH_TTL = 315360000;

    private $CI;
    private $user = NULL;
    private $device = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->library('api_response');
        $this->CI->load->model('sek_user_model');
    }

    public function hash_token($token)
    {
        return hash('sha256', (string) $token);
    }

    public function random_token()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Resolve the bearer token. When $required is false an absent/invalid token
     * simply yields NULL; when true a 401 JSON response is sent.
     */
    public function authenticate($required = TRUE)
    {
        $this->user = NULL;
        $this->device = NULL;

        $token = $this->bearer_token();
        if ($token === '') {
            if ($required) {
                $this->CI->api_response->error('unauthenticated', 'Token akses diperlukan.', 401);
            }
            return NULL;
        }

        $row = $this->CI->db
            ->select('d.device_id, d.user_id, d.device_uuid, d.platform, d.active_account_id, '
                . 'd.access_expires_at, d.revoked_at, u.email, u.display_name, u.email_verified_at, u.status, u.created_at')
            ->from('sek_device d')
            ->join('sek_user u', 'u.user_id = d.user_id', 'inner')
            ->where('d.access_token_hash', $this->hash_token($token))
            ->limit(2)
            ->get()
            ->row_array();

        $now = gmdate('Y-m-d H:i:s');
        $valid = is_array($row)
            && empty($row['revoked_at'])
            && (string) $row['access_expires_at'] > $now
            && (string) $row['status'] === 'active';

        if (!$valid) {
            if ($required) {
                $this->CI->api_response->error('unauthenticated', 'Sesi tidak valid atau sudah berakhir.', 401);
            }
            return NULL;
        }

        $this->user = array(
            'user_id' => (int) $row['user_id'],
            'email' => (string) $row['email'],
            'display_name' => (string) $row['display_name'],
            'email_verified_at' => $row['email_verified_at'],
            'status' => (string) $row['status'],
            'created_at' => $row['created_at'],
        );
        $this->device = array(
            'device_id' => (int) $row['device_id'],
            'device_uuid' => (string) $row['device_uuid'],
            'platform' => (string) $row['platform'],
            'active_account_id' => $row['active_account_id'] !== NULL ? (int) $row['active_account_id'] : NULL,
        );

        $this->CI->db->where('device_id', $this->device['device_id'])
            ->update('sek_device', array('last_seen_at' => $now));

        return $this->user;
    }

    public function user()
    {
        return $this->user;
    }

    public function device()
    {
        return $this->device;
    }

    public function user_id()
    {
        return $this->user !== NULL ? (int) $this->user['user_id'] : NULL;
    }

    /**
     * Create or replace the session row for a device. Returns raw tokens once.
     */
    public function issue_tokens($user_id, $device_uuid, $platform, $app_version = NULL, $push_token = NULL, $active_account_id = NULL)
    {
        $access = $this->random_token();
        $refresh = $this->random_token();
        $now = gmdate('Y-m-d H:i:s');
        $data = array(
            'user_id' => (int) $user_id,
            'device_uuid' => (string) $device_uuid,
            'platform' => (string) $platform,
            'app_version' => $app_version !== NULL ? (string) $app_version : NULL,
            'push_token' => $push_token !== NULL ? (string) $push_token : NULL,
            'access_token_hash' => $this->hash_token($access),
            'access_expires_at' => gmdate('Y-m-d H:i:s', time() + self::ACCESS_TTL),
            'refresh_token_hash' => $this->hash_token($refresh),
            'refresh_expires_at' => gmdate('Y-m-d H:i:s', time() + self::REFRESH_TTL),
            'active_account_id' => $active_account_id !== NULL ? (int) $active_account_id : NULL,
            'revoked_at' => NULL,
            'last_seen_at' => $now,
        );

        $existing = $this->CI->db
            ->select('device_id, active_account_id')
            ->where('user_id', (int) $user_id)
            ->where('device_uuid', (string) $device_uuid)
            ->limit(1)
            ->get('sek_device')
            ->row_array();

        if (is_array($existing)) {
            if ($active_account_id === NULL) {
                $data['active_account_id'] = $existing['active_account_id'] !== NULL
                    ? (int) $existing['active_account_id']
                    : NULL;
            }
            $this->CI->db->where('device_id', (int) $existing['device_id'])
                ->update('sek_device', $data);
            $device_id = (int) $existing['device_id'];
        } else {
            $data['created_at'] = $now;
            $this->CI->db->insert('sek_device', $data);
            $device_id = (int) $this->CI->db->insert_id();
        }

        return array(
            'device_id' => $device_id,
            'access_token' => $access,
            'access_expires_at' => $data['access_expires_at'],
            'refresh_token' => $refresh,
            'refresh_expires_at' => $data['refresh_expires_at'],
            'active_account_id' => $data['active_account_id'],
        );
    }

    /**
     * Exchange a refresh token for a new access token. The refresh token is
     * intentionally kept stable (only its expiry slides forward) so retries,
     * app restarts, and multiple in-flight requests can never invalidate the
     * session by presenting an already-rotated token.
     */
    public function rotate($refresh_token, $device_uuid)
    {
        $row = $this->CI->db
            ->select('device_id, user_id, active_account_id, refresh_expires_at, revoked_at')
            ->from('sek_device')
            ->where('refresh_token_hash', $this->hash_token($refresh_token))
            ->where('device_uuid', (string) $device_uuid)
            ->limit(2)
            ->get()
            ->row_array();

        $now = gmdate('Y-m-d H:i:s');
        if (!is_array($row) || !empty($row['revoked_at']) || (string) $row['refresh_expires_at'] <= $now) {
            return NULL;
        }

        $user = $this->CI->sek_user_model->find((int) $row['user_id']);
        if (!is_array($user) || (string) $user['status'] !== 'active') {
            return NULL;
        }

        $access = $this->random_token();
        $data = array(
            'access_token_hash' => $this->hash_token($access),
            'access_expires_at' => gmdate('Y-m-d H:i:s', time() + self::ACCESS_TTL),
            'refresh_expires_at' => gmdate('Y-m-d H:i:s', time() + self::REFRESH_TTL),
            'last_seen_at' => $now,
        );
        $this->CI->db->where('device_id', (int) $row['device_id'])
            ->where('revoked_at IS NULL', NULL, FALSE)
            ->update('sek_device', $data);

        return array(
            'device_id' => (int) $row['device_id'],
            'access_token' => $access,
            'access_expires_at' => $data['access_expires_at'],
            'refresh_token' => (string) $refresh_token,
            'refresh_expires_at' => $data['refresh_expires_at'],
            'active_account_id' => $row['active_account_id'] !== NULL ? (int) $row['active_account_id'] : NULL,
        );
    }

    public function revoke_device($device_id, $user_id = NULL)
    {
        $this->CI->db->where('device_id', (int) $device_id);
        if ($user_id !== NULL) {
            $this->CI->db->where('user_id', (int) $user_id);
        }
        return $this->CI->db->update('sek_device', array(
            'revoked_at' => gmdate('Y-m-d H:i:s'),
            'active_account_id' => NULL,
        ));
    }

    public function revoke_all_for_user($user_id)
    {
        return $this->CI->db->where('user_id', (int) $user_id)
            ->where('revoked_at IS NULL', NULL, FALSE)
            ->update('sek_device', array('revoked_at' => gmdate('Y-m-d H:i:s')));
    }

    /**
     * Simple fixed-window limiter keyed by bucket and client IP.
     * Returns false when the caller should answer 429.
     */
    public function rate_limit($bucket, $max, $window_seconds, $block_seconds = 0)
    {
        $key = hash('sha256', (string) $bucket . '|' . $this->CI->input->ip_address());
        $now = gmdate('Y-m-d H:i:s');
        $row = $this->CI->db->where('rate_key', $key)->limit(1)->get('sek_rate_limit')->row_array();

        if (!is_array($row)) {
            $this->CI->db->insert('sek_rate_limit', array(
                'rate_key' => $key,
                'attempts' => 1,
                'window_started_at' => $now,
                'blocked_until' => NULL,
            ));
            return TRUE;
        }

        if (!empty($row['blocked_until']) && (string) $row['blocked_until'] > $now) {
            return FALSE;
        }

        $window_end = gmdate('Y-m-d H:i:s', strtotime((string) $row['window_started_at']) + (int) $window_seconds);
        if ($window_end <= $now) {
            $this->CI->db->where('rate_key', $key)->update('sek_rate_limit', array(
                'attempts' => 1,
                'window_started_at' => $now,
                'blocked_until' => NULL,
            ));
            return TRUE;
        }

        $attempts = (int) $row['attempts'] + 1;
        $blocked_until = NULL;
        $blocked = $attempts > (int) $max && (int) $block_seconds > 0;
        if ($blocked) {
            $blocked_until = gmdate('Y-m-d H:i:s', time() + (int) $block_seconds);
        }
        $this->CI->db->where('rate_key', $key)->update('sek_rate_limit', array(
            'attempts' => $attempts,
            'blocked_until' => $blocked_until,
        ));

        return !$blocked;
    }

    public function client_ip()
    {
        return (string) $this->CI->input->ip_address();
    }

    private function bearer_token()
    {
        $authorization = trim((string) $this->CI->input->get_request_header('Authorization', TRUE));
        if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
}
