<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Service/product/ride catalog per business account. */
class Sek_services extends Sek_api_base
{
    public function index($account_id = 0)
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $account = $this->sek_account_model->find((int) $account_id);
        if (!is_array($account) || (string) $account['status'] !== 'active') {
            $this->api_response->error('not_found', 'Akun tidak ditemukan.', 404);
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'GET') {
            $active_only = !$this->api_validation->bool_value($this->input->get('include_inactive', TRUE));
            $data = array();
            foreach ($this->sek_order_model->services((int) $account_id, $active_only) as $service) {
                $data[] = $this->service_payload($service);
            }
            $this->api_response->ok(array('data' => $data));
            return;
        }

        if (!in_array($this->sek_account_model->member_role((int) $account_id, $this->api_auth->user_id()), array('owner', 'manager'), TRUE)) {
            $this->api_response->error('forbidden', 'Hanya owner atau manager yang dapat mengubah katalog.', 403);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $clean = $this->validated($body);
        if (is_string($clean)) {
            $this->api_response->error('validation_error', $clean, 422);
            return;
        }
        $service_id = $this->sek_order_model->create_service((int) $account_id, $clean);
        $service = $this->sek_order_model->find_service($service_id);
        $this->api_response->json(array('service' => $this->service_payload($service)), 201);
    }

    public function show($service_id = 0)
    {
        if (!$this->require_method(array('PATCH', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $service = $this->sek_order_model->find_service((int) $service_id);
        if (!is_array($service)) {
            $this->api_response->error('not_found', 'Layanan tidak ditemukan.', 404);
            return;
        }
        if (!in_array($this->sek_account_model->member_role((int) $service['account_id'], $this->api_auth->user_id()), array('owner', 'manager'), TRUE)) {
            $this->api_response->error('forbidden', 'Hanya owner atau manager yang dapat mengubah katalog.', 403);
            return;
        }

        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->sek_order_model->archive_service((int) $service_id);
            $this->api_response->ok(array('status' => 'archived', 'service_id' => (int) $service_id));
            return;
        }

        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $clean = $this->validated($body, TRUE);
        if (is_string($clean)) {
            $this->api_response->error('validation_error', $clean, 422);
            return;
        }
        $this->sek_order_model->update_service((int) $service_id, $clean);
        $fresh = $this->sek_order_model->find_service((int) $service_id);
        $this->api_response->ok(array('service' => $this->service_payload($fresh)));
    }

    private function validated($body, $partial = FALSE)
    {
        $fields = array();
        if (!$partial || array_key_exists('name', $body)) {
            $name = isset($body['name']) ? trim((string) $body['name']) : '';
            if ($name === '' || mb_strlen($name) > 120) {
                return 'Nama layanan wajib (maks 120 karakter).';
            }
            $fields['name'] = $name;
        }
        if (!$partial || array_key_exists('price', $body)) {
            if (!isset($body['price']) || !is_numeric($body['price']) || (float) $body['price'] < 0) {
                return 'Harga tidak valid.';
            }
            $fields['price'] = round((float) $body['price'], 2);
        }
        if (!$partial || array_key_exists('kind', $body)) {
            $kind = isset($body['kind']) ? strtolower(trim((string) $body['kind'])) : 'service';
            if (!in_array($kind, array('product', 'service', 'ride'), TRUE)) {
                return 'kind harus product, service, atau ride.';
            }
            $fields['kind'] = $kind;
        }
        foreach (array('description' => 500, 'unit' => 20) as $key => $max) {
            if (array_key_exists($key, $body)) {
                $value = trim((string) $body[$key]);
                if (mb_strlen($value) > $max) {
                    return $key . ' terlalu panjang.';
                }
                $fields[$key] = $value !== '' ? $value : NULL;
            }
        }
        if (array_key_exists('currency', $body)) {
            $currency = strtoupper(trim((string) $body['currency']));
            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                return 'currency harus 3 huruf.';
            }
            $fields['currency'] = $currency;
        }
        if (array_key_exists('duration_minutes', $body)) {
            $duration = (int) $body['duration_minutes'];
            if ($duration < 0 || $duration > 1440) {
                return 'duration_minutes 0–1440.';
            }
            $fields['duration_minutes'] = $duration > 0 ? $duration : NULL;
        }
        if (!$partial && !isset($fields['kind'])) {
            $fields['kind'] = 'service';
        }
        if (!$partial && !isset($fields['currency'])) {
            $fields['currency'] = 'IDR';
        }
        if (!$partial && !isset($fields['unit'])) {
            $fields['unit'] = 'item';
        }
        return $fields;
    }

    private function service_payload($service)
    {
        return array(
            'service_id' => (int) $service['service_id'],
            'account_id' => (int) $service['account_id'],
            'name' => (string) $service['name'],
            'description' => $service['description'],
            'price' => (float) $service['price'],
            'currency' => (string) $service['currency'],
            'unit' => (string) $service['unit'],
            'kind' => (string) $service['kind'],
            'duration_minutes' => $service['duration_minutes'] !== NULL ? (int) $service['duration_minutes'] : NULL,
            'is_active' => (int) $service['is_active'] === 1,
            'created_at' => $this->utc($service['created_at']),
            'updated_at' => $this->utc($service['updated_at']),
        );
    }
}
