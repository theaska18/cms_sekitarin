<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Account list, creation, profile updates, activation, and search. */
class Sek_accounts extends Sek_api_base
{
    public function index()
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'POST') {
            $this->create_account();
            return;
        }
        $data = array();
        foreach ($this->sek_account_model->list_for_user($this->api_auth->user_id()) as $account) {
            $data[] = $this->account_payload($account, TRUE);
        }
        $this->api_response->ok(array(
            'data' => $data,
            'active_account_id' => $this->active_account_id(),
        ));
    }

    public function show($account_id = 0)
    {
        if (!$this->require_method(array('GET', 'PATCH', 'DELETE'))) {
            return;
        }
        $method = strtoupper((string) $this->input->method(TRUE));
        if ($method === 'GET') {
            $this->show_account((int) $account_id);
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->account_context_for((int) $account_id);
        if ($account === NULL) {
            return;
        }
        if ($method === 'DELETE') {
            $this->archive_account($account);
            return;
        }
        $this->update_account($account);
    }

    public function activate($account_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->account_context_for((int) $account_id);
        if ($account === NULL) {
            return;
        }
        $device = $this->api_auth->device();
        $this->db->where('device_id', (int) $device['device_id'])
            ->update('sek_device', array('active_account_id' => (int) $account['account_id']));
        $this->api_response->ok(array(
            'status' => 'activated',
            'active_account_id' => (int) $account['account_id'],
            'account' => $this->account_payload($account, TRUE),
        ));
    }

    public function handle_available()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        $handle = $this->sek_account_model->normalize_handle($this->input->get('handle', TRUE));
        if (!$this->sek_account_model->valid_handle($handle)) {
            $this->api_response->error('validation_error', 'Username 3–40 karakter: huruf kecil, angka, titik, atau garis bawah.', 422, array('handle' => 'Format tidak valid.'));
            return;
        }
        if (!$this->api_auth->rate_limit('handle_check', 120, 3600, 300)) {
            $this->api_response->error('rate_limited', 'Terlalu banyak pengecekan username.', 429);
            return;
        }
        $this->api_response->ok(array(
            'handle' => $handle,
            'available' => !$this->sek_account_model->handle_exists($handle),
        ));
    }

    public function lookup()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $handle = $this->sek_account_model->normalize_handle($this->input->get('handle', TRUE));
        if (!$this->sek_account_model->valid_handle($handle)) {
            $this->api_response->error('validation_error', 'Username tidak valid.', 422);
            return;
        }
        $account = $this->sek_account_model->find_by_handle($handle);
        if (!is_array($account) || (string) $account['status'] !== 'active') {
            $this->api_response->error('not_found', 'Akun dengan username tersebut tidak ditemukan.', 404);
            return;
        }
        $viewer_account = $this->viewer_account_id();
        if ($viewer_account !== NULL && $this->sek_account_model->blocked_between($viewer_account, (int) $account['account_id'])) {
            $this->api_response->error('not_found', 'Akun dengan username tersebut tidak ditemukan.', 404);
            return;
        }
        $private = $this->sek_account_model->member_role((int) $account['account_id'], $this->api_auth->user_id()) !== NULL;
        $this->api_response->ok(array(
            'account' => $this->account_payload($account, $private),
        ));
    }

    public function search()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $query = trim((string) $this->input->get('q', TRUE));
        if (mb_strlen($query) < 2) {
            $this->api_response->error('validation_error', 'Kata kunci minimal 2 karakter.', 422, array('q' => 'Minimal 2 karakter.'));
            return;
        }
        $limit = (int) $this->input->get('limit', TRUE);
        $limit = $limit > 0 ? min(20, $limit) : 20;
        $data = array();
        foreach ($this->sek_account_model->search($query, $limit, $this->viewer_account_id()) as $account) {
            $data[] = $this->account_payload($account);
        }
        $this->api_response->ok(array('data' => $data));
    }

    public function hours($account_id = 0)
    {
        if (!$this->require_method(array('GET', 'PUT'))) {
            return;
        }
        $account = $this->sek_account_model->find((int) $account_id);
        $method = strtoupper((string) $this->input->method(TRUE));

        if ($method === 'GET') {
            if (!is_array($account) || (string) $account['status'] !== 'active') {
                $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
                return;
            }
            $data = array();
            foreach ($this->sek_account_model->hours((int) $account_id) as $row) {
                $data[] = $this->hours_payload($row);
            }
            $this->api_response->ok(array('data' => $data));
            return;
        }

        if (!$this->require_auth()) {
            return;
        }
        if (!is_array($account)) {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return;
        }
        $role = $this->sek_account_model->member_role((int) $account_id, $this->api_auth->user_id());
        if (!in_array($role, array('owner', 'manager'), TRUE)) {
            $this->api_response->error('forbidden', 'Hanya owner atau manager yang dapat mengubah jam buka.', 403);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $rows = isset($body['hours']) && is_array($body['hours']) ? $body['hours'] : array();
        $errors = array();
        $seen = array();
        $clean = array();
        foreach ($rows as $index => $row) {
            if (!is_array($row) || !isset($row['weekday'])) {
                $errors['hours.' . $index] = 'Format jadwal tidak valid.';
                continue;
            }
            $weekday = (int) $row['weekday'];
            if ($weekday < 0 || $weekday > 6 || isset($seen[$weekday])) {
                $errors['hours.' . $index . '.weekday'] = 'Hari tidak valid atau duplikat.';
                continue;
            }
            $seen[$weekday] = TRUE;
            $closed = !empty($row['is_closed']);
            $open = isset($row['open_time']) ? (string) $row['open_time'] : '';
            $close = isset($row['close_time']) ? (string) $row['close_time'] : '';
            if (!$closed) {
                if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $open)
                    || !preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $close)) {
                    $errors['hours.' . $index . '.open_time'] = 'Jam harus berformat HH:MM.';
                    continue;
                }
                if (strlen($open) === 5) {
                    $open .= ':00';
                }
                if (strlen($close) === 5) {
                    $close .= ':00';
                }
                if ($open >= $close) {
                    $errors['hours.' . $index . '.close_time'] = 'Jam tutup harus setelah jam buka.';
                    continue;
                }
            } else {
                $open = $open !== '' ? $open : '00:00:00';
                $close = $close !== '' ? $close : '00:00:00';
            }
            $clean[] = array(
                'weekday' => $weekday,
                'open_time' => $open,
                'close_time' => $close,
                'is_closed' => $closed,
            );
        }
        if ($errors) {
            $this->api_response->error('validation_error', 'Jadwal buka tidak valid.', 422, $errors);
            return;
        }
        $this->sek_account_model->replace_hours((int) $account_id, $clean);
        $data = array();
        foreach ($this->sek_account_model->hours((int) $account_id) as $row) {
            $data[] = $this->hours_payload($row);
        }
        $this->api_response->ok(array('data' => $data));
    }

    private function hours_payload($row)
    {
        return array(
            'weekday' => (int) $row['weekday'],
            'open_time' => substr((string) $row['open_time'], 0, 5),
            'close_time' => substr((string) $row['close_time'], 0, 5),
            'is_closed' => (int) $row['is_closed'] === 1,
        );
    }

    private function show_account($account_id)
    {
        $account = $this->sek_account_model->find($account_id);
        if (!is_array($account) || (string) $account['status'] !== 'active') {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return;
        }
        $viewer = $this->api_auth->authenticate(FALSE);
        $private = is_array($viewer)
            && $this->sek_account_model->member_role($account_id, (int) $viewer['user_id']) !== NULL;
        if (is_array($viewer) && !$private) {
            $viewer_account = $this->viewer_account_id();
            if ($viewer_account !== NULL && $this->sek_account_model->blocked_between($viewer_account, $account_id)) {
                $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
                return;
            }
        }
        $payload = $this->account_payload($account, $private);
        if ($private || $account['type_code'] !== 'personal') {
            $location = $this->sek_account_model->location($account_id);
            if (is_array($location)) {
                $payload['location'] = array(
                    'lat' => (float) $location['lat'],
                    'lng' => (float) $location['lng'],
                    'address' => $location['address'],
                    'source' => (string) $location['source'],
                    'updated_at' => $this->utc($location['updated_at']),
                );
            }
        }
        $hours = array();
        foreach ($this->sek_account_model->hours($account_id) as $row) {
            $hours[] = $this->hours_payload($row);
        }
        $payload['hours'] = $hours;
        $this->api_response->ok(array('account' => $payload));
    }

    private function create_account()
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $errors = $this->api_validation->check($body, array(
            'type_code' => 'required|max:32',
            'display_name' => 'required|min:1|max:120',
            'bio' => 'max:500',
            'address' => 'max:255',
            'phone' => 'max:32',
        ));
        $type = $this->sek_account_model->type(isset($body['type_code']) ? (string) $body['type_code'] : '');
        if (!is_array($type) || (int) $type['is_active'] !== 1) {
            $errors['type_code'] = 'Tipe akun tidak dikenal.';
        } elseif ((string) $type['type_code'] === 'personal') {
            $errors['type_code'] = 'Akun personal dibuat otomatis saat registrasi.';
        }

        $handle = isset($body['handle']) ? strtolower(trim((string) $body['handle'])) : '';
        if ($handle !== '' && !preg_match('/^[a-z0-9._]{3,40}$/', $handle)) {
            $errors['handle'] = 'Handle 3–40 karakter: huruf kecil, angka, titik, atau garis bawah.';
        }
        if ($handle === '') {
            $handle = $this->sek_account_model->suggest_handle(
                isset($body['display_name']) ? (string) $body['display_name'] : 'usaha'
            );
        }
        if ($this->sek_account_model->handle_exists($handle)) {
            $errors['handle'] = 'Handle sudah dipakai.';
        }

        $radius = isset($body['service_radius_m']) ? (int) $body['service_radius_m'] : 5000;
        if ($radius < 100 || $radius > 50000) {
            $errors['service_radius_m'] = 'Radius layanan 100–50000 meter.';
        }
        $location_mode = isset($body['location_mode'])
            ? strtolower(trim((string) $body['location_mode']))
            : 'fixed';
        if (!in_array($location_mode, array('fixed', 'realtime'), TRUE)) {
            $errors['location_mode'] = 'location_mode harus fixed atau realtime.';
        }

        $location = isset($body['location']) && is_array($body['location']) ? $body['location'] : array();
        if (!$this->valid_lat(isset($location['lat']) ? $location['lat'] : NULL)
            || !$this->valid_lng(isset($location['lng']) ? $location['lng'] : NULL)) {
            $errors['location'] = 'Lokasi lat/lng wajib dan valid.';
        }
        if ($errors) {
            $this->api_response->error('validation_error', 'Data akun tidak valid.', 422, $errors);
            return;
        }

        $account_id = $this->sek_account_model->create($this->api_auth->user_id(), array(
            'type_code' => (string) $type['type_code'],
            'handle' => $handle,
            'display_name' => trim((string) $body['display_name']),
            'bio' => isset($body['bio']) ? trim((string) $body['bio']) : NULL,
            'phone' => isset($body['phone']) ? trim((string) $body['phone']) : NULL,
            'address' => isset($body['address']) ? trim((string) $body['address']) : NULL,
            'service_radius_m' => $radius,
            'location_mode' => $location_mode,
            'lat' => (float) $location['lat'],
            'lng' => (float) $location['lng'],
            'source' => 'manual',
        ));
        if ($account_id === NULL) {
            $this->api_response->error('conflict', 'Akun gagal dibuat. Handle mungkin sudah dipakai.', 409);
            return;
        }

        $account = $this->sek_account_model->find($account_id);
        $account['role'] = 'owner';
        $payload = $this->account_payload($account, TRUE);
        $location_row = $this->sek_account_model->location($account_id);
        if (is_array($location_row)) {
            $payload['location'] = array(
                'lat' => (float) $location_row['lat'],
                'lng' => (float) $location_row['lng'],
                'address' => $location_row['address'],
                'source' => (string) $location_row['source'],
            );
        }
        $this->api_response->json(array('account' => $payload), 201);
    }

    private function update_account($account)
    {
        $role = $this->sek_account_model->member_role((int) $account['account_id'], $this->api_auth->user_id());
        if (!in_array($role, array('owner', 'manager'), TRUE)) {
            $this->api_response->error('forbidden', 'Hanya owner atau manager yang dapat mengubah profil.', 403);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }

        $fields = array();
        foreach (array('display_name' => 120, 'bio' => 500, 'address' => 255, 'phone' => 32) as $key => $max) {
            if (!array_key_exists($key, $body)) {
                continue;
            }
            $value = trim((string) $body[$key]);
            if ($key === 'display_name' && $value === '') {
                $this->api_response->error('validation_error', 'display_name tidak boleh kosong.', 422);
                return;
            }
            if (mb_strlen($value) > $max) {
                $this->api_response->error('validation_error', $key . ' terlalu panjang.', 422);
                return;
            }
            $fields[$key] = $value !== '' ? $value : NULL;
        }

        if (array_key_exists('handle', $body)) {
            $handle = strtolower(trim((string) $body['handle']));
            if (!preg_match('/^[a-z0-9._]{3,40}$/', $handle)) {
                $this->api_response->error('validation_error', 'Handle tidak valid.', 422, array('handle' => '3–40 karakter.'));
                return;
            }
            if ($handle !== (string) $account['handle'] && $this->sek_account_model->handle_exists($handle)) {
                $this->api_response->error('conflict', 'Handle sudah dipakai.', 409, array('handle' => 'Sudah dipakai.'));
                return;
            }
            $fields['handle'] = $handle;
        }

        if (array_key_exists('service_radius_m', $body)) {
            $radius = (int) $body['service_radius_m'];
            if ($radius < 100 || $radius > 50000) {
                $this->api_response->error('validation_error', 'Radius layanan 100–50000 meter.', 422);
                return;
            }
            $fields['service_radius_m'] = $radius;
        }

        if (array_key_exists('location_mode', $body)) {
            $mode = strtolower(trim((string) $body['location_mode']));
            if (!in_array($mode, array('fixed', 'realtime'), TRUE)) {
                $this->api_response->error('validation_error', 'location_mode harus fixed atau realtime.', 422);
                return;
            }
            $fields['location_mode'] = $mode;
        }

        if (array_key_exists('is_active', $body)) {
            $active = $this->api_validation->bool_value($body['is_active']);
            $fields['is_active'] = $active ? 1 : 0;
            $fields['active_until'] = $active
                ? gmdate('Y-m-d H:i:s', time() + 86400)
                : NULL;
        }

        foreach (array('avatar_media_id', 'cover_media_id') as $media_field) {
            if (!array_key_exists($media_field, $body)) {
                continue;
            }
            $media_id = (int) $body[$media_field];
            if ($media_id > 0 && !$this->sek_account_model->media_belongs_to($media_id, $this->api_auth->user_id())) {
                $this->api_response->error('validation_error', 'Media tidak ditemukan.', 422, array($media_field => 'Tidak valid.'));
                return;
            }
            $fields[$media_field] = $media_id > 0 ? $media_id : NULL;
        }

        if ($fields) {
            $this->sek_account_model->update((int) $account['account_id'], $fields);
        }

        if (isset($body['location']) && is_array($body['location'])) {
            $location = $body['location'];
            if (!$this->valid_lat(isset($location['lat']) ? $location['lat'] : NULL)
                || !$this->valid_lng(isset($location['lng']) ? $location['lng'] : NULL)) {
                $this->api_response->error('validation_error', 'Lokasi lat/lng tidak valid.', 422);
                return;
            }
            $this->sek_account_model->set_location(
                (int) $account['account_id'],
                (float) $location['lat'],
                (float) $location['lng'],
                isset($location['address']) ? (string) $location['address'] : (isset($fields['address']) ? $fields['address'] : NULL),
                isset($location['source']) ? (string) $location['source'] : 'manual'
            );
        }

        $fresh = $this->sek_account_model->find((int) $account['account_id']);
        $fresh['role'] = $role;
        $payload = $this->account_payload($fresh, TRUE);
        $location_row = $this->sek_account_model->location((int) $account['account_id']);
        if (is_array($location_row)) {
            $payload['location'] = array(
                'lat' => (float) $location_row['lat'],
                'lng' => (float) $location_row['lng'],
                'address' => $location_row['address'],
                'source' => (string) $location_row['source'],
                'updated_at' => $this->utc($location_row['updated_at']),
            );
        }
        $this->api_response->ok(array('account' => $payload));
    }

    private function archive_account($account)
    {
        $role = $this->sek_account_model->member_role((int) $account['account_id'], $this->api_auth->user_id());
        if ($role !== 'owner') {
            $this->api_response->error('forbidden', 'Hanya owner yang dapat mengarsipkan akun.', 403);
            return;
        }
        if ((string) $account['type_code'] === 'personal') {
            $this->api_response->error('unprocessable', 'Akun personal tidak dapat diarsipkan.', 422);
            return;
        }
        $this->sek_account_model->update((int) $account['account_id'], array('status' => 'archived', 'is_online' => 0));
        $this->db->where('account_id', (int) $account['account_id'])->delete('sek_account_presence');
        $this->api_response->ok(array('status' => 'archived'));
    }

    private function account_context_for($account_id)
    {
        $account = $this->sek_account_model->find($account_id);
        if (!is_array($account) || (string) $account['status'] === 'archived') {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return NULL;
        }
        if ($this->sek_account_model->member_role($account_id, $this->api_auth->user_id()) === NULL) {
            $this->api_response->error('forbidden', 'Anda tidak memiliki akses ke akun ini.', 403);
            return NULL;
        }
        return $account;
    }
}
