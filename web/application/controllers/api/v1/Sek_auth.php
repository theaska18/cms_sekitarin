<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Registration, login, token rotation, and email/password recovery. */
class Sek_auth extends Sek_api_base
{
    public function register()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }

        $errors = $this->api_validation->check($body, array(
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|max:200',
            'display_name' => 'required|min:1|max:80',
        ));
        $username = $this->sek_account_model->normalize_handle(
            isset($body['username']) ? (string) $body['username'] : ''
        );
        if ($username !== '' && !$this->sek_account_model->valid_handle($username)) {
            $errors['username'] = 'Username 3–40 karakter: huruf kecil, angka, titik, atau garis bawah.';
        }
        $confirmation = isset($body['password_confirmation']) ? (string) $body['password_confirmation'] : NULL;
        if ($confirmation !== NULL && !hash_equals((string) $body['password'], $confirmation)) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak sama.';
        }
        $device = isset($body['device']) && is_array($body['device']) ? $body['device'] : array();
        $device_uuid = isset($device['device_uuid']) ? trim((string) $device['device_uuid']) : '';
        $platform = isset($device['platform']) ? strtolower(trim((string) $device['platform'])) : '';
        if ($device_uuid === '' || strlen($device_uuid) > 64) {
            $errors['device.device_uuid'] = 'device_uuid wajib diisi (maks 64 karakter).';
        }
        if (!in_array($platform, array('ios', 'android'), TRUE)) {
            $errors['device.platform'] = 'platform harus ios atau android.';
        }
        if ($errors) {
            $this->api_response->error('validation_error', 'Data registrasi tidak valid.', 422, $errors);
            return;
        }

        $email = strtolower(trim((string) $body['email']));
        if (!$this->api_auth->rate_limit('register|' . $email, 10, 3600, 900)) {
            $this->api_response->error('rate_limited', 'Terlalu banyak percobaan. Coba lagi nanti.', 429);
            return;
        }
        if ($this->sek_user_model->email_exists($email)) {
            $this->api_response->error('conflict', 'Email sudah terdaftar.', 409, array('email' => 'Email sudah terdaftar.'));
            return;
        }
        if ($username !== '' && $this->sek_account_model->handle_exists($username, 'personal')) {
            $this->api_response->error('conflict', 'Username sudah dipakai.', 409, array('username' => 'Username sudah dipakai.'));
            return;
        }

        $local = strstr($email, '@', TRUE);
        $handle = $username !== ''
            ? $username
            : $this->sek_account_model->suggest_handle($local !== FALSE ? $local : $email, 'personal');
        $created = $this->sek_user_model->create_with_personal_account(
            $email,
            Sek_user_model::hash_password((string) $body['password']),
            (string) $body['display_name'],
            $handle
        );
        if ($created === NULL) {
            $this->api_response->error('conflict', 'Registrasi gagal. Email atau handle mungkin sudah dipakai.', 409);
            return;
        }

        $verify_token = $this->sek_user_model->create_token($created['user_id'], 'verify_email', 86400);
        $this->sek_mailer->send_token($email, 'verify_email', $verify_token, 86400);

        $tokens = $this->api_auth->issue_tokens(
            $created['user_id'],
            $device_uuid,
            $platform,
            isset($device['app_version']) ? (string) $device['app_version'] : NULL,
            isset($device['push_token']) ? (string) $device['push_token'] : NULL,
            $created['account_id']
        );

        $payload = $this->session_payload($created['user_id'], $tokens);
        $payload['email_verified'] = FALSE;
        if ($this->dev_tokens_enabled()) {
            $payload['dev_verification_token'] = $verify_token;
        }
        $this->api_response->json($payload, 201);
    }

    public function login()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $errors = $this->api_validation->check($body, array(
            'email' => 'required|email|max:190',
            'password' => 'required|min:1|max:200',
        ));
        $device = isset($body['device']) && is_array($body['device']) ? $body['device'] : array();
        $device_uuid = isset($device['device_uuid']) ? trim((string) $device['device_uuid']) : '';
        $platform = isset($device['platform']) ? strtolower(trim((string) $device['platform'])) : '';
        if ($device_uuid === '' || strlen($device_uuid) > 64) {
            $errors['device.device_uuid'] = 'device_uuid wajib diisi (maks 64 karakter).';
        }
        if (!in_array($platform, array('ios', 'android'), TRUE)) {
            $errors['device.platform'] = 'platform harus ios atau android.';
        }
        if ($errors) {
            $this->api_response->error('validation_error', 'Data login tidak valid.', 422, $errors);
            return;
        }

        $email = strtolower(trim((string) $body['email']));
        if (!$this->api_auth->rate_limit('login|' . $email, 10, 900, 900)) {
            $this->api_response->error('rate_limited', 'Terlalu banyak percobaan login. Coba lagi nanti.', 429);
            return;
        }

        $user = $this->sek_user_model->find_by_email($email);
        if (!is_array($user) || !password_verify((string) $body['password'], (string) $user['password_hash'])) {
            $this->api_response->error('invalid_credentials', 'Email atau password salah.', 401);
            return;
        }
        if ((string) $user['status'] !== 'active') {
            $this->api_response->error('forbidden', 'Akun tidak aktif.', 403);
            return;
        }

        $this->sek_user_model->touch_login((int) $user['user_id']);
        $active = $this->sek_account_model->primary_account((int) $user['user_id']);
        $tokens = $this->api_auth->issue_tokens(
            (int) $user['user_id'],
            $device_uuid,
            $platform,
            isset($device['app_version']) ? (string) $device['app_version'] : NULL,
            isset($device['push_token']) ? (string) $device['push_token'] : NULL,
            $active
        );

        $payload = $this->session_payload((int) $user['user_id'], $tokens);
        $payload['email_verified'] = !empty($user['email_verified_at']);
        $this->api_response->ok($payload);
    }

    public function refresh()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $errors = $this->api_validation->check($body, array(
            'refresh_token' => 'required|max:200',
            'device_uuid' => 'required|max:64',
        ));
        if ($errors) {
            $this->api_response->error('validation_error', 'Data refresh tidak valid.', 422, $errors);
            return;
        }
        if (!$this->api_auth->rate_limit('refresh|' . (string) $body['device_uuid'], 60, 3600, 300)) {
            $this->api_response->error('rate_limited', 'Terlalu banyak refresh token.', 429);
            return;
        }

        $tokens = $this->api_auth->rotate((string) $body['refresh_token'], (string) $body['device_uuid']);
        if ($tokens === NULL) {
            $this->api_response->error('unauthenticated', 'Refresh token tidak valid atau sudah berakhir.', 401);
            return;
        }
        $this->api_response->ok($this->token_payload($tokens));
    }

    public function logout()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $device = $this->api_auth->device();
        $this->api_auth->revoke_device((int) $device['device_id'], $this->api_auth->user_id());
        $this->api_response->ok(array('status' => 'logged_out'));
    }

    public function verify_email()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $errors = $this->api_validation->check($body, array('token' => 'required|max:200'));
        if ($errors) {
            $this->api_response->error('validation_error', 'Token wajib diisi.', 422, $errors);
            return;
        }
        $user_id = $this->sek_user_model->consume_token('verify_email', (string) $body['token']);
        if ($user_id === NULL) {
            $this->api_response->error('invalid_token', 'Token verifikasi tidak valid atau sudah berakhir.', 422);
            return;
        }
        $this->sek_user_model->mark_email_verified($user_id);
        $this->api_response->ok(array('status' => 'verified', 'email_verified' => TRUE));
    }

    public function resend_verification()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $user = $this->sek_user_model->find($this->api_auth->user_id());
        if (!empty($user['email_verified_at'])) {
            $this->api_response->ok(array('status' => 'already_verified', 'email_verified' => TRUE));
            return;
        }
        if (!$this->api_auth->rate_limit('resend|' . $user['user_id'], 3, 3600, 1800)) {
            $this->api_response->error('rate_limited', 'Terlalu banyak permintaan. Coba lagi nanti.', 429);
            return;
        }
        $token = $this->sek_user_model->create_token((int) $user['user_id'], 'verify_email', 86400);
        $this->sek_mailer->send_token($user['email'], 'verify_email', $token, 86400);
        $payload = array('status' => 'sent');
        if ($this->dev_tokens_enabled()) {
            $payload['dev_verification_token'] = $token;
        }
        $this->api_response->json($payload, 202);
    }

    public function forgot_password()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $errors = $this->api_validation->check($body, array('email' => 'required|email|max:190'));
        if ($errors) {
            $this->api_response->error('validation_error', 'Email tidak valid.', 422, $errors);
            return;
        }
        $email = strtolower(trim((string) $body['email']));
        if (!$this->api_auth->rate_limit('forgot|' . $email, 5, 3600, 1800)) {
            $this->api_response->error('rate_limited', 'Terlalu banyak permintaan. Coba lagi nanti.', 429);
            return;
        }

        $payload = array('status' => 'accepted');
        $user = $this->sek_user_model->find_by_email($email);
        if (is_array($user) && (string) $user['status'] === 'active') {
            $token = $this->sek_user_model->create_token((int) $user['user_id'], 'reset_password', 3600);
            $this->sek_mailer->send_token($email, 'reset_password', $token, 3600);
            if ($this->dev_tokens_enabled()) {
                $payload['dev_reset_token'] = $token;
            }
        }
        $this->api_response->json($payload, 202);
    }

    public function reset_password()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $errors = $this->api_validation->check($body, array(
            'token' => 'required|max:200',
            'password' => 'required|min:8|max:200',
        ));
        if ($errors) {
            $this->api_response->error('validation_error', 'Data reset password tidak valid.', 422, $errors);
            return;
        }
        $user_id = $this->sek_user_model->consume_token('reset_password', (string) $body['token']);
        if ($user_id === NULL) {
            $this->api_response->error('invalid_token', 'Token reset tidak valid atau sudah berakhir.', 422);
            return;
        }
        $this->sek_user_model->set_password($user_id, Sek_user_model::hash_password((string) $body['password']));
        $this->api_auth->revoke_all_for_user($user_id);
        $this->api_response->ok(array('status' => 'password_reset'));
    }

    private function session_payload($user_id, $tokens)
    {
        $user = $this->sek_user_model->find((int) $user_id);
        $accounts = array();
        foreach ($this->sek_account_model->list_for_user((int) $user_id) as $account) {
            $accounts[] = $this->account_payload($account, TRUE);
        }
        return array_merge($this->token_payload($tokens), array(
            'token_type' => 'Bearer',
            'user' => $this->user_payload($user),
            'accounts' => $accounts,
            'active_account_id' => $tokens['active_account_id'] !== NULL ? (int) $tokens['active_account_id'] : NULL,
        ));
    }

    private function dev_tokens_enabled()
    {
        return ENVIRONMENT !== 'production';
    }
}
