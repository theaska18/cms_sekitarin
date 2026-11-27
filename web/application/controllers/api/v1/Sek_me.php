<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Current user profile, devices, and consent history. */
class Sek_me extends Sek_api_base
{
    public function index()
    {
        if (!$this->require_method(array('GET', 'PATCH'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'PATCH') {
            $this->update_profile();
            return;
        }

        $user = $this->sek_user_model->find($this->api_auth->user_id());
        $accounts = array();
        foreach ($this->sek_account_model->list_for_user($this->api_auth->user_id()) as $account) {
            $accounts[] = $this->account_payload($account, TRUE);
        }
        $this->api_response->ok(array(
            'user' => $this->user_payload($user),
            'accounts' => $accounts,
            'active_account_id' => $this->active_account_id(),
            'consents' => $this->latest_consents($this->api_auth->user_id()),
            'unread_notifications' => $this->sek_notification_model->unread_count($this->api_auth->user_id(), TRUE),
            'unread_chat_notifications' => $this->sek_notification_model->unread_chat_count($this->api_auth->user_id()),
        ));
    }

    public function devices()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $rows = $this->db->select('device_id, device_uuid, platform, app_version, active_account_id, last_seen_at, created_at')
            ->where('user_id', $this->api_auth->user_id())
            ->where('revoked_at IS NULL', NULL, FALSE)
            ->order_by('created_at', 'ASC')
            ->get('sek_device')
            ->result_array();
        $current = $this->api_auth->device();
        $data = array();
        foreach ($rows as $row) {
            $data[] = array(
                'device_id' => (int) $row['device_id'],
                'device_uuid' => (string) $row['device_uuid'],
                'platform' => (string) $row['platform'],
                'app_version' => $row['app_version'],
                'active_account_id' => $row['active_account_id'] !== NULL ? (int) $row['active_account_id'] : NULL,
                'current' => (int) $row['device_id'] === (int) $current['device_id'],
                'last_seen_at' => $this->utc($row['last_seen_at']),
                'created_at' => $this->utc($row['created_at']),
            );
        }
        $this->api_response->ok(array('data' => $data));
    }

    public function delete_device($device_id = 0)
    {
        if (!$this->require_method('DELETE')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $device = $this->db->select('device_id')
            ->where('device_id', (int) $device_id)
            ->where('user_id', $this->api_auth->user_id())
            ->limit(1)
            ->get('sek_device')
            ->row_array();
        if (!is_array($device)) {
            $this->api_response->error('not_found', 'Perangkat tidak ditemukan.', 404);
            return;
        }
        $this->api_auth->revoke_device((int) $device['device_id'], $this->api_auth->user_id());
        $this->api_response->ok(array('status' => 'revoked'));
    }

    public function push_token()
    {
        if (!$this->require_method(array('POST', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $device = $this->api_auth->device();
        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->db->where('device_id', (int) $device['device_id'])
                ->update('sek_device', array('push_token' => NULL));
            $this->api_response->ok(array('status' => 'cleared'));
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $token = isset($body['push_token']) ? trim((string) $body['push_token']) : '';
        if ($token === '' || strlen($token) > 255) {
            $this->api_response->error('validation_error', 'push_token wajib (maks 255 karakter).', 422);
            return;
        }
        $this->db->where('device_id', (int) $device['device_id'])
            ->update('sek_device', array('push_token' => $token));
        $this->api_response->ok(array('status' => 'registered'));
    }

    public function consents()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $rows = $this->db->where('user_id', $this->api_auth->user_id())
            ->order_by('created_at', 'DESC')
            ->get('sek_consent')
            ->result_array();
        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->consent_payload($row);
        }
        $this->api_response->ok(array('data' => $data, 'latest' => $this->latest_consents($this->api_auth->user_id())));
    }

    public function consent($scope = '')
    {
        if (!$this->require_method('PUT')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $scope = strtolower(trim((string) $scope));
        if (!in_array($scope, array('location', 'notifications', 'marketing'), TRUE)) {
            $this->api_response->error('validation_error', 'Scope consent tidak dikenal.', 422);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        if (!array_key_exists('granted', $body)) {
            $this->api_response->error('validation_error', 'Field granted wajib diisi.', 422, array('granted' => 'Wajib diisi.'));
            return;
        }
        $granted = $this->api_validation->bool_value($body['granted']);
        $policy = isset($body['policy_version']) ? trim((string) $body['policy_version']) : '1.0';
        if ($policy === '' || strlen($policy) > 20) {
            $this->api_response->error('validation_error', 'policy_version tidak valid.', 422);
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $device = $this->api_auth->device();
        $this->db->insert('sek_consent', array(
            'user_id' => $this->api_auth->user_id(),
            'scope' => $scope,
            'granted' => $granted ? 1 : 0,
            'policy_version' => $policy,
            'device_id' => (int) $device['device_id'],
            'granted_at' => $granted ? $now : NULL,
            'revoked_at' => $granted ? NULL : $now,
            'created_at' => $now,
        ));

        if ($scope === 'location' && !$granted) {
            $accounts = $this->sek_account_model->list_for_user($this->api_auth->user_id());
            foreach ($accounts as $account) {
                $this->db->where('account_id', (int) $account['account_id'])
                    ->update('sek_account', array('is_online' => 0, 'updated_at' => $now));
                $this->db->where('account_id', (int) $account['account_id'])->delete('sek_account_presence');
            }
        }

        $this->api_response->ok(array(
            'scope' => $scope,
            'granted' => $granted,
            'policy_version' => $policy,
            'consent' => $this->latest_consents($this->api_auth->user_id()),
        ));
    }

    private function update_profile()
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $fields = array();
        if (array_key_exists('display_name', $body)) {
            $name = trim((string) $body['display_name']);
            if ($name === '' || mb_strlen($name) > 80) {
                $this->api_response->error('validation_error', 'display_name tidak valid.', 422, array('display_name' => '1–80 karakter.'));
                return;
            }
            $fields['display_name'] = $name;
        }

        if (array_key_exists('password', $body)) {
            $password = (string) $body['password'];
            $current = isset($body['current_password']) ? (string) $body['current_password'] : '';
            if (mb_strlen($password) < 8 || mb_strlen($password) > 200) {
                $this->api_response->error('validation_error', 'Password baru minimal 8 karakter.', 422, array('password' => 'Minimal 8 karakter.'));
                return;
            }
            $user = $this->sek_user_model->find($this->api_auth->user_id());
            if (!password_verify($current, (string) $user['password_hash'])) {
                $this->api_response->error('invalid_credentials', 'Password saat ini salah.', 422, array('current_password' => 'Salah.'));
                return;
            }
            $this->sek_user_model->set_password($this->api_auth->user_id(), Sek_user_model::hash_password($password));
        }

        if (isset($fields['display_name'])) {
            $this->sek_user_model->update_profile($this->api_auth->user_id(), $fields['display_name']);
            $this->db->where('user_id', $this->api_auth->user_id())
                ->where('type_code', 'personal')
                ->update('sek_account', array(
                    'display_name' => $fields['display_name'],
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ));
        }
        $user = $this->sek_user_model->find($this->api_auth->user_id());
        $this->api_response->ok(array('user' => $this->user_payload($user)));
    }

    private function latest_consents($user_id)
    {
        $rows = $this->db->where('user_id', (int) $user_id)
            ->order_by('created_at', 'DESC')
            ->get('sek_consent')
            ->result_array();
        $latest = array();
        foreach ($rows as $row) {
            if (!array_key_exists($row['scope'], $latest)) {
                $latest[$row['scope']] = $this->consent_payload($row);
            }
        }
        return $latest;
    }

    private function consent_payload($row)
    {
        return array(
            'scope' => (string) $row['scope'],
            'granted' => (int) $row['granted'] === 1,
            'policy_version' => (string) $row['policy_version'],
            'granted_at' => $this->utc($row['granted_at']),
            'revoked_at' => $this->utc($row['revoked_at']),
            'created_at' => $this->utc($row['created_at']),
        );
    }
}
