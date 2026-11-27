<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Shared plumbing for the Sekitar /api/v1 controllers. */
class Sek_api_base extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(array('api_response', 'api_auth', 'api_validation', 'sek_mailer', 'sek_realtime', 'sek_notifier'));
        $this->load->model(array('sek_user_model', 'sek_account_model', 'sek_media_model', 'sek_social_model', 'sek_chat_model', 'sek_post_model', 'sek_notification_model', 'sek_order_model'));
    }

    /** Decode the JSON request body. Sends 400 and returns NULL when invalid. */
    protected function body()
    {
        $raw = (string) $this->input->raw_input_stream;
        if (strlen($raw) > 1048576) {
            $this->api_response->error('payload_too_large', 'Body permintaan terlalu besar.', 413);
            return NULL;
        }
        if (trim($raw) === '') {
            return array();
        }
        $payload = json_decode($raw, TRUE);
        if (!is_array($payload)) {
            $this->api_response->error('invalid_json', 'Body harus berupa JSON yang valid.', 400);
            return NULL;
        }
        return $payload;
    }

    protected function require_method($allowed)
    {
        $allowed = is_array($allowed) ? $allowed : array($allowed);
        $method = strtoupper((string) $this->input->method(TRUE));
        if (in_array($method, $allowed, TRUE)) {
            return TRUE;
        }
        $this->api_response->json(
            array('error' => array('code' => 'method_not_allowed', 'message' => 'Metode tidak diizinkan.')),
            405,
            implode(', ', $allowed)
        );
        return FALSE;
    }

    /** Authenticate the request. Sends 401 and returns FALSE on failure. */
    protected function require_auth()
    {
        return $this->api_auth->authenticate(TRUE) !== NULL;
    }

    /**
     * Resolve X-Account-Id and verify active membership.
     * Sends 400/403 and returns NULL when required and invalid.
     */
    protected function account_context($required = TRUE)
    {
        $raw = trim((string) $this->input->get_request_header('X-Account-Id', TRUE));
        if ($raw === '') {
            if ($required) {
                $this->api_response->error('account_required', 'Header X-Account-Id diperlukan.', 400);
            }
            return NULL;
        }
        if (!ctype_digit($raw)) {
            $this->api_response->error('validation_error', 'X-Account-Id tidak valid.', 400);
            return NULL;
        }
        $account = $this->sek_account_model->find((int) $raw);
        $user_id = $this->api_auth->user_id();
        if (!is_array($account) || $user_id === NULL
            || $this->sek_account_model->member_role((int) $account['account_id'], $user_id) === NULL
            || (string) $account['status'] === 'archived') {
            $this->api_response->error('forbidden', 'Anda tidak memiliki akses ke akun ini.', 403);
            return NULL;
        }
        return $account;
    }

    /** Active account from X-Account-Id when valid, otherwise the device default. */
    protected function viewer_account_id()
    {
        $raw = trim((string) $this->input->get_request_header('X-Account-Id', TRUE));
        $user_id = $this->api_auth->user_id();
        if ($raw !== '' && ctype_digit($raw) && $user_id !== NULL
            && $this->sek_account_model->member_role((int) $raw, $user_id) !== NULL) {
            return (int) $raw;
        }
        return $this->active_account_id();
    }

    protected function active_account_id()
    {
        $device = $this->api_auth->device();
        $user_id = $this->api_auth->user_id();
        if (!is_array($device) || $user_id === NULL) {
            return NULL;
        }
        if (!empty($device['active_account_id'])
            && $this->sek_account_model->member_role((int) $device['active_account_id'], $user_id) !== NULL) {
            return (int) $device['active_account_id'];
        }
        return $this->sek_account_model->primary_account($user_id);
    }

    protected function user_payload($user)
    {
        return array(
            'user_id' => (int) $user['user_id'],
            'email' => (string) $user['email'],
            'display_name' => (string) $user['display_name'],
            'email_verified' => !empty($user['email_verified_at']),
            'created_at' => $this->utc($user['created_at']),
        );
    }

    protected function account_payload($account, $private = FALSE)
    {
        $payload = array(
            'account_id' => (int) $account['account_id'],
            'type_code' => (string) $account['type_code'],
            'handle' => (string) $account['handle'],
            'display_name' => (string) $account['display_name'],
            'bio' => isset($account['bio']) && $account['bio'] !== NULL ? (string) $account['bio'] : NULL,
            'avatar_media_id' => !empty($account['avatar_media_id']) ? (int) $account['avatar_media_id'] : NULL,
            'cover_media_id' => !empty($account['cover_media_id']) ? (int) $account['cover_media_id'] : NULL,
            'address' => isset($account['address']) && $account['address'] !== NULL ? (string) $account['address'] : NULL,
            'service_radius_m' => isset($account['service_radius_m']) ? (int) $account['service_radius_m'] : 0,
            'location_mode' => isset($account['location_mode']) ? (string) $account['location_mode'] : 'fixed',
            'is_online' => $this->account_is_online($account),
            'is_active' => $this->account_is_active($account),
            'active_until' => isset($account['active_until']) ? $this->utc($account['active_until']) : NULL,
            'verified' => !empty($account['verified_at']),
            'status' => isset($account['status']) ? (string) $account['status'] : 'active',
            'created_at' => $this->utc(isset($account['created_at']) ? $account['created_at'] : NULL),
            'updated_at' => $this->utc(isset($account['updated_at']) ? $account['updated_at'] : NULL),
        );
        if (isset($account['label_id'])) {
            $payload['type_label'] = (string) $account['label_id'];
        }
        if (array_key_exists('allows_presence', $account)) {
            $payload['allows_presence'] = (int) $account['allows_presence'] === 1;
        }
        if ($private) {
            $payload['phone'] = isset($account['phone']) && $account['phone'] !== NULL ? (string) $account['phone'] : NULL;
            if (isset($account['role'])) {
                $payload['role'] = (string) $account['role'];
            }
        }
        return $payload;
    }

    protected function account_business_available($account)
    {
        if ((string) ($account['type_code'] ?? 'personal') === 'personal') {
            return $this->account_is_active($account);
        }
        if (!$this->account_is_active($account)) {
            return FALSE;
        }
        $personal = $this->sek_account_model->personal_for_user((int) $account['user_id']);
        return is_array($personal) && $this->account_is_online($personal);
    }

    protected function account_is_online($account)
    {
        $now = time();
        $presence = !empty($account['presence_updated_at'])
            ? strtotime((string) $account['presence_updated_at']) : 0;
        if (!empty($account['is_online']) && $presence >= $now - 120) {
            return TRUE;
        }
        $location = !empty($account['location_updated_at'])
            ? strtotime((string) $account['location_updated_at']) : 0;
        return $location >= $now - 120;
    }

    protected function account_is_active($account)
    {
        if (isset($account['is_active']) && (int) $account['is_active'] !== 1) {
            return FALSE;
        }
        if (empty($account['active_until'])) {
            return TRUE;
        }
        return (string) $account['active_until'] > gmdate('Y-m-d H:i:s');
    }

    protected function message_payload($message, $counts = array(), $viewer_user_id = 0, $reaction_summary = array())
    {
        $deleted = !empty($message['deleted_at']);
        $message_id = (int) $message['message_id'];
        $receipt = isset($counts[$message_id]) ? $counts[$message_id] : array('delivered' => 0, 'read' => 0);
        $payload = array(
            'message_id' => $message_id,
            'conversation_id' => (int) $message['conversation_id'],
            'sender_user_id' => (int) $message['sender_user_id'],
            'sender_account_id' => $message['sender_account_id'] !== NULL ? (int) $message['sender_account_id'] : NULL,
            'sender_name' => isset($message['sender_name']) ? $message['sender_name'] : NULL,
            'type' => (string) $message['type'],
            'body' => $deleted ? NULL : $message['body'],
            'media_id' => !$deleted && $message['media_id'] !== NULL ? (int) $message['media_id'] : NULL,
            'location' => (!$deleted && $message['latitude'] !== NULL && $message['longitude'] !== NULL) ? array(
                'lat' => (float) $message['latitude'],
                'lng' => (float) $message['longitude'],
                'label' => $message['location_label'],
            ) : NULL,
            'reply_to_message_id' => $message['reply_to_message_id'] !== NULL ? (int) $message['reply_to_message_id'] : NULL,
            'delivered_count' => (int) $receipt['delivered'],
            'read_count' => (int) $receipt['read'],
            'reactions' => isset($reaction_summary['reactions']) ? $reaction_summary['reactions'] : array(),
            'my_reaction' => isset($reaction_summary['my_reaction']) ? $reaction_summary['my_reaction'] : NULL,
            'edited_at' => $this->utc($message['edited_at']),
            'deleted_at' => $this->utc($message['deleted_at']),
            'created_at' => $this->utc($message['created_at']),
        );
        return $payload;
    }

    protected function token_payload($tokens)
    {
        return array(
            'access_token' => $tokens['access_token'],
            'access_expires_at' => $this->utc($tokens['access_expires_at']),
            'refresh_token' => $tokens['refresh_token'],
            'refresh_expires_at' => $this->utc($tokens['refresh_expires_at']),
            'active_account_id' => $tokens['active_account_id'] !== NULL ? (int) $tokens['active_account_id'] : NULL,
        );
    }

    protected function utc($value)
    {
        $iso = app_utc_iso($value);
        return $iso !== NULL ? $iso : $value;
    }

    protected function valid_lat($value)
    {
        return is_numeric($value) && (float) $value >= -90 && (float) $value <= 90;
    }

    protected function valid_lng($value)
    {
        return is_numeric($value) && (float) $value >= -180 && (float) $value <= 180;
    }
}
