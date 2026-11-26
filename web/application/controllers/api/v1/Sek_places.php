<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Account base location, live presence, and nearby map queries. */
class Sek_places extends Sek_api_base
{
    public function location($account_id = 0)
    {
        if (!$this->require_method('PUT')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->accessible_account((int) $account_id);
        if ($account === NULL) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        if (!$this->valid_lat(isset($body['lat']) ? $body['lat'] : NULL)
            || !$this->valid_lng(isset($body['lng']) ? $body['lng'] : NULL)) {
            $this->api_response->error('validation_error', 'lat/lng tidak valid.', 422);
            return;
        }
        $address = isset($body['address']) ? trim((string) $body['address']) : NULL;
        if ($address !== NULL && mb_strlen($address) > 255) {
            $this->api_response->error('validation_error', 'Alamat maksimal 255 karakter.', 422);
            return;
        }
        $source = isset($body['source']) ? strtolower(trim((string) $body['source'])) : 'manual';
        if (!in_array($source, array('manual', 'gps', 'geocode'), TRUE)) {
            $this->api_response->error('validation_error', 'source tidak valid.', 422);
            return;
        }
        $this->sek_account_model->set_location(
            (int) $account['account_id'],
            (float) $body['lat'],
            (float) $body['lng'],
            $address,
            $source
        );
        $location = $this->sek_account_model->location((int) $account['account_id']);
        $this->api_response->ok(array('location' => $this->location_payload($location)));
    }

    public function presence($account_id = 0)
    {
        if (!$this->require_method(array('GET', 'PUT', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->accessible_account((int) $account_id);
        if ($account === NULL) {
            return;
        }
        $method = strtoupper((string) $this->input->method(TRUE));

        if ($method === 'GET') {
            $presence = $this->sek_account_model->presence((int) $account['account_id']);
            $this->api_response->ok(array('presence' => is_array($presence) ? $this->presence_payload($presence) : NULL));
            return;
        }
        if ($method === 'DELETE') {
            $this->sek_account_model->delete_presence((int) $account['account_id']);
            $this->api_response->ok(array('status' => 'offline'));
            return;
        }

        if ((int) $account['allows_presence'] !== 1) {
            $this->api_response->error('unprocessable', 'Tipe akun ini tidak memiliki presence realtime.', 422);
            return;
        }
        if (!$this->location_consent_granted()) {
            $this->api_response->error('forbidden', 'Persetujuan lokasi belum diberikan.', 403);
            return;
        }
        if (!$this->api_auth->rate_limit('presence|' . (int) $account['account_id'], 1, 5, 5)) {
            $this->api_response->error('rate_limited', 'Perbarui presence maksimal sekali per 5 detik.', 429);
            return;
        }

        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        if (!$this->valid_lat(isset($body['lat']) ? $body['lat'] : NULL)
            || !$this->valid_lng(isset($body['lng']) ? $body['lng'] : NULL)) {
            $this->api_response->error('validation_error', 'lat/lng tidak valid.', 422);
            return;
        }
        $accuracy = isset($body['accuracy_m']) ? max(0, min(10000, (int) $body['accuracy_m'])) : NULL;
        $heading = isset($body['heading']) ? max(0, min(359, (int) $body['heading'])) : NULL;
        $speed = isset($body['speed_mps']) ? max(0, min(200, (float) $body['speed_mps'])) : NULL;

        $this->sek_account_model->upsert_presence(
            (int) $account['account_id'],
            (float) $body['lat'],
            (float) $body['lng'],
            $accuracy,
            $heading,
            $speed
        );
        $presence = $this->sek_account_model->presence((int) $account['account_id']);
        $this->api_response->ok(array('presence' => $this->presence_payload($presence)));
    }

    public function nearby()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $lat = $this->input->get('lat', TRUE);
        $lng = $this->input->get('lng', TRUE);
        if (!$this->valid_lat($lat) || !$this->valid_lng($lng)) {
            $this->api_response->error('validation_error', 'Query lat/lng wajib dan valid.', 422);
            return;
        }
        $radius = (int) $this->input->get('radius', TRUE);
        $radius = $radius > 0 ? max(100, min(5000, $radius)) : 1000;
        $limit = (int) $this->input->get('limit', TRUE);
        $limit = $limit > 0 ? min(100, $limit) : 50;
        $online_only = $this->api_validation->bool_value($this->input->get('online', TRUE));

        $types = array();
        $raw_types = trim((string) $this->input->get('types', TRUE));
        if ($raw_types !== '') {
            $known = array();
            foreach ($this->sek_account_model->types() as $type) {
                $known[] = (string) $type['type_code'];
            }
            foreach (explode(',', $raw_types) as $type_code) {
                $type_code = strtolower(trim($type_code));
                if ($type_code !== '' && in_array($type_code, $known, TRUE)) {
                    $types[] = $type_code;
                }
            }
            if (!$types) {
                $this->api_response->error('validation_error', 'types tidak valid.', 422);
                return;
            }
        }

        $blocked = $this->sek_account_model->blocked_account_ids($this->viewer_account_id());
        $rows = $this->sek_account_model->nearby(
            (float) $lat,
            (float) $lng,
            $radius,
            $types,
            $online_only,
            $blocked,
            $limit
        );

        $data = array();
        $viewer_user_id = $this->api_auth->user_id();
        foreach ($rows as $row) {
            $data[] = array(
                'account_id' => (int) $row['account_id'],
                'is_own' => (int) $row['user_id'] === (int) $viewer_user_id,
                'type_code' => (string) $row['type_code'],
                'type_label' => (string) $row['label_id'],
                'handle' => (string) $row['handle'],
                'display_name' => (string) $row['display_name'],
                'bio' => $row['bio'],
                'avatar_media_id' => $row['avatar_media_id'] !== NULL ? (int) $row['avatar_media_id'] : NULL,
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lng'],
                'distance_m' => (int) round((float) $row['distance_m']),
                'is_online' => (int) $row['is_online'] === 1,
                'source' => (string) $row['source'],
                'verified' => !empty($row['verified_at']),
                'last_seen_at' => $this->utc($row['last_seen_at']),
            );
        }
        $this->api_response->ok(array(
            'data' => $data,
            'radius_m' => $radius,
            'center' => array('lat' => (float) $lat, 'lng' => (float) $lng),
        ));
    }

    private function accessible_account($account_id)
    {
        $account = $this->sek_account_model->find((int) $account_id);
        if (!is_array($account) || (string) $account['status'] === 'archived') {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return NULL;
        }
        if ($this->sek_account_model->member_role((int) $account_id, $this->api_auth->user_id()) === NULL) {
            $this->api_response->error('forbidden', 'Anda tidak memiliki akses ke akun ini.', 403);
            return NULL;
        }
        return $account;
    }

    private function location_consent_granted()
    {
        $row = $this->db->where('user_id', $this->api_auth->user_id())
            ->where('scope', 'location')
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get('sek_consent')
            ->row_array();
        return is_array($row) && (int) $row['granted'] === 1;
    }

    private function location_payload($row)
    {
        return array(
            'lat' => (float) $row['lat'],
            'lng' => (float) $row['lng'],
            'address' => $row['address'],
            'source' => (string) $row['source'],
            'updated_at' => $this->utc($row['updated_at']),
        );
    }

    private function presence_payload($row)
    {
        return array(
            'account_id' => (int) $row['account_id'],
            'lat' => (float) $row['lat'],
            'lng' => (float) $row['lng'],
            'accuracy_m' => $row['accuracy_m'] !== NULL ? (int) $row['accuracy_m'] : NULL,
            'heading' => $row['heading'] !== NULL ? (int) $row['heading'] : NULL,
            'speed_mps' => $row['speed_mps'] !== NULL ? (float) $row['speed_mps'] : NULL,
            'is_online' => (int) $row['is_online'] === 1,
            'updated_at' => $this->utc($row['updated_at']),
        );
    }
}
