<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Food, service, and ride orders with a shared state machine. */
class Sek_orders extends Sek_api_base
{
    private $allowed_statuses = array('pending', 'accepted', 'in_progress', 'delivering', 'completed', 'cancelled');

    public function index()
    {
        if (!$this->require_method(array('GET', 'POST'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        if (strtoupper((string) $this->input->method(TRUE)) === 'POST') {
            $this->create_order();
            return;
        }

        $role = strtolower(trim((string) $this->input->get('role', TRUE)));
        $role = $role === 'provider' ? 'provider' : 'customer';
        $status = strtolower(trim((string) $this->input->get('status', TRUE)));
        if ($status !== '' && !in_array($status, $this->allowed_statuses, TRUE)) {
            $this->api_response->error('validation_error', 'status tidak valid.', 422);
            return;
        }
        $before = (int) $this->input->get('before_id', TRUE);
        $limit = (int) $this->input->get('limit', TRUE);
        $limit = $limit > 0 ? min(50, $limit) : 20;

        if ($role === 'provider') {
            $account = $this->account_context(TRUE);
            if ($account === NULL) {
                return;
            }
            $rows = $this->sek_order_model->list_for_provider((int) $account['account_id'], $status, $before, $limit);
        } else {
            $rows = $this->sek_order_model->list_for_customer($this->api_auth->user_id(), $status, $before, $limit);
        }
        $has_more = count($rows) > $limit;
        if ($has_more) {
            $rows = array_slice($rows, 0, $limit);
        }
        $this->api_response->ok(array(
            'data' => $this->order_payloads($rows),
            'has_more' => $has_more,
            'role' => $role,
        ));
    }

    public function show($order_id = 0)
    {
        if (!$this->require_method('GET')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $order = $this->participant_order((int) $order_id);
        if ($order === NULL) {
            return;
        }
        $payload = $this->order_payload($order);
        $payload['events'] = array();
        foreach ($this->sek_order_model->events((int) $order_id) as $event) {
            $payload['events'][] = array(
                'status' => (string) $event['status'],
                'actor_user_id' => $event['actor_user_id'] !== NULL ? (int) $event['actor_user_id'] : NULL,
                'actor_name' => $event['actor_name'],
                'note' => $event['note'],
                'created_at' => $this->utc($event['created_at']),
            );
        }
        $this->api_response->ok(array('order' => $payload));
    }

    public function accept($order_id = 0)
    {
        $this->provider_transition($order_id, 'accepted', array('pending'));
    }

    public function progress($order_id = 0)
    {
        $this->provider_transition($order_id, 'in_progress', array('accepted'));
    }

    public function deliver($order_id = 0)
    {
        $order = $this->provider_action_order((int) $order_id);
        if ($order === NULL) {
            return;
        }
        if ((string) $order['type'] !== 'food') {
            $this->api_response->error('unprocessable', 'Status delivering hanya untuk order makanan.', 422);
            return;
        }
        $this->transition($order, 'delivering', array('in_progress'), 'Pesanan sedang diantar.');
    }

    public function complete($order_id = 0)
    {
        $order = $this->provider_action_order((int) $order_id);
        if ($order === NULL) {
            return;
        }
        $this->transition($order, 'completed', array('accepted', 'in_progress', 'delivering'), 'Order selesai.');
    }

    public function cancel($order_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $order = $this->participant_order((int) $order_id);
        if ($order === NULL) {
            return;
        }
        if (!in_array((string) $order['status'], array('pending', 'accepted'), TRUE)) {
            $this->api_response->error('unprocessable', 'Order pada status ini tidak dapat dibatalkan.', 422);
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $reason = isset($body['reason']) ? trim((string) $body['reason']) : '';
        if (mb_strlen($reason) > 255) {
            $this->api_response->error('validation_error', 'Alasan maksimal 255 karakter.', 422);
            return;
        }
        $this->transition($order, 'cancelled', array('pending', 'accepted'), $reason !== '' ? $reason : 'Dibatalkan.');
    }

    public function payment($order_id = 0)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $order = $this->provider_action_order((int) $order_id);
        if ($order === NULL) {
            return;
        }
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $status = isset($body['status']) ? strtolower(trim((string) $body['status'])) : '';
        if (!in_array($status, array('paid', 'refunded'), TRUE)) {
            $this->api_response->error('validation_error', 'status pembayaran harus paid atau refunded.', 422);
            return;
        }
        $this->sek_order_model->set_payment((int) $order_id, $status);
        $fresh = $this->sek_order_model->find_order((int) $order_id);
        $this->notify_order($fresh, 'Pembayaran order', 'Status pembayaran ' . $order['code'] . ' menjadi ' . $status . '.', 'order_payment');
        $this->api_response->ok(array('order' => $this->order_payload($fresh)));
    }

    private function create_order()
    {
        $body = $this->body();
        if ($body === NULL) {
            return;
        }
        $type = isset($body['type']) ? strtolower(trim((string) $body['type'])) : '';
        if (!in_array($type, array('food', 'service', 'ride'), TRUE)) {
            $this->api_response->error('validation_error', 'type harus food, service, atau ride.', 422);
            return;
        }
        $provider_id = isset($body['provider_account_id']) ? (int) $body['provider_account_id'] : 0;
        $provider = $this->sek_account_model->find($provider_id);
        if (!is_array($provider) || (string) $provider['status'] !== 'active') {
            $this->api_response->error('not_found', 'Akun penyedia tidak ditemukan.', 404);
            return;
        }
        if ((int) $provider['user_id'] === (int) $this->api_auth->user_id()) {
            $this->api_response->error('unprocessable', 'Tidak dapat memesan dari akun sendiri.', 422);
            return;
        }
        $customer_account = $this->viewer_account_id();
        if ($customer_account !== NULL && $this->sek_account_model->blocked_between($customer_account, $provider_id)) {
            $this->api_response->error('forbidden', 'Akun penyedia ini diblokir.', 403);
            return;
        }

        $raw_items = isset($body['items']) && is_array($body['items']) ? $body['items'] : array();
        if (!$raw_items || count($raw_items) > 20) {
            $this->api_response->error('validation_error', 'items wajib (1–20 item).', 422);
            return;
        }
        $expected_kind = $type === 'food' ? 'product' : $type;
        $items = array();
        $currency = 'IDR';
        foreach ($raw_items as $index => $raw) {
            $service_id = isset($raw['service_id']) ? (int) $raw['service_id'] : 0;
            $quantity = isset($raw['quantity']) ? (int) $raw['quantity'] : 1;
            $service = $this->sek_order_model->find_service($service_id);
            if (!is_array($service)
                || (int) $service['account_id'] !== $provider_id
                || (int) $service['is_active'] !== 1
                || (string) $service['kind'] !== $expected_kind) {
                $this->api_response->error('validation_error', 'Item ke-' . $index . ' tidak valid untuk tipe order ini.', 422);
                return;
            }
            if ($quantity < 1 || $quantity > 99) {
                $this->api_response->error('validation_error', 'Quantity item harus 1–99.', 422);
                return;
            }
            $price = round((float) $service['price'], 2);
            $items[] = array(
                'service_id' => $service_id,
                'name' => (string) $service['name'],
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => round($price * $quantity, 2),
            );
            if ($index === 0) {
                $currency = (string) $service['currency'];
            }
        }
        if ($type === 'ride' && (count($items) !== 1 || $items[0]['quantity'] !== 1)) {
            $this->api_response->error('validation_error', 'Order ride memakai satu layanan dengan quantity 1.', 422);
            return;
        }

        $data = array('currency' => $currency);
        $payment = isset($body['payment_method']) ? strtolower(trim((string) $body['payment_method'])) : 'cash';
        if (!in_array($payment, array('cash', 'transfer'), TRUE)) {
            $this->api_response->error('validation_error', 'payment_method harus cash atau transfer.', 422);
            return;
        }
        $data['payment_method'] = $payment;
        foreach (array('notes' => 500, 'delivery_address' => 255, 'pickup_address' => 255, 'dropoff_address' => 255) as $key => $max) {
            if (isset($body[$key]) && trim((string) $body[$key]) !== '') {
                $value = trim((string) $body[$key]);
                if (mb_strlen($value) > $max) {
                    $this->api_response->error('validation_error', $key . ' terlalu panjang.', 422);
                    return;
                }
                $data[$key] = $value;
            }
        }
        foreach (array('delivery', 'pickup', 'dropoff') as $prefix) {
            if (!isset($body[$prefix]) || !is_array($body[$prefix])) {
                continue;
            }
            $point = $body[$prefix];
            if (!$this->valid_lat(isset($point['lat']) ? $point['lat'] : NULL)
                || !$this->valid_lng(isset($point['lng']) ? $point['lng'] : NULL)) {
                $this->api_response->error('validation_error', $prefix . ' lat/lng tidak valid.', 422);
                return;
            }
            $data[$prefix . '_lat'] = (float) $point['lat'];
            $data[$prefix . '_lng'] = (float) $point['lng'];
        }
        if ($type === 'ride' && (!isset($data['pickup_lat']) || !isset($data['dropoff_lat']))) {
            $this->api_response->error('validation_error', 'Order ride memerlukan pickup dan dropoff.', 422);
            return;
        }
        if (isset($body['scheduled_at']) && trim((string) $body['scheduled_at']) !== '') {
            $scheduled = $this->parse_utc((string) $body['scheduled_at']);
            if ($scheduled === NULL) {
                $this->api_response->error('validation_error', 'scheduled_at tidak valid.', 422);
                return;
            }
            $data['scheduled_at'] = $scheduled;
        }

        $order_id = $this->sek_order_model->create_order(
            $this->api_auth->user_id(),
            $customer_account,
            $provider_id,
            $type,
            $items,
            $data
        );
        if ($order_id === NULL) {
            $this->api_response->error('server_error', 'Order gagal dibuat.', 500);
            return;
        }
        $order = $this->sek_order_model->find_order($order_id);
        $this->notify_order($order, 'Order baru', 'Order ' . $order['code'] . ' menunggu konfirmasi.', 'order_new');
        $this->api_response->json(array('order' => $this->order_payload($order)), 201);
    }

    private function provider_transition($order_id, $to, $from)
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $order = $this->provider_action_order((int) $order_id);
        if ($order === NULL) {
            return;
        }
        $note = $to === 'accepted' ? 'Order diterima.' : 'Order diproses.';
        $this->transition($order, $to, $from, $note);
    }

    private function transition($order, $to, $from, $note)
    {
        if (!in_array((string) $order['status'], $from, TRUE)) {
            $this->api_response->error('unprocessable', 'Status order tidak mengizinkan aksi ini.', 422);
            return;
        }
        $this->sek_order_model->transition((int) $order['order_id'], $to, $this->api_auth->user_id(), $note);
        $fresh = $this->sek_order_model->find_order((int) $order['order_id']);
        $this->notify_order($fresh, 'Update order', 'Order ' . $fresh['code'] . ' sekarang ' . $to . '.', 'order_status');
        $this->api_response->ok(array('order' => $this->order_payload($fresh)));
    }

    private function participant_order($order_id)
    {
        $order = $this->sek_order_model->find_order($order_id);
        if (!is_array($order)) {
            $this->api_response->error('not_found', 'Order tidak ditemukan.', 404);
            return NULL;
        }
        $is_customer = (int) $order['customer_user_id'] === (int) $this->api_auth->user_id();
        $is_provider = $this->sek_account_model->member_role((int) $order['provider_account_id'], $this->api_auth->user_id()) !== NULL;
        if (!$is_customer && !$is_provider) {
            $this->api_response->error('not_found', 'Order tidak ditemukan.', 404);
            return NULL;
        }
        return $order;
    }

    private function provider_action_order($order_id)
    {
        if (!$this->require_method('POST')) {
            return NULL;
        }
        if (!$this->require_auth()) {
            return NULL;
        }
        $order = $this->sek_order_model->find_order($order_id);
        if (!is_array($order)) {
            $this->api_response->error('not_found', 'Order tidak ditemukan.', 404);
            return NULL;
        }
        if ($this->sek_account_model->member_role((int) $order['provider_account_id'], $this->api_auth->user_id()) === NULL) {
            $this->api_response->error('forbidden', 'Hanya penyedia yang dapat melakukan aksi ini.', 403);
            return NULL;
        }
        return $order;
    }

    private function notify_order($order, $title, $body, $type)
    {
        $users = array((int) $order['customer_user_id']);
        foreach ($this->sek_account_model->members((int) $order['provider_account_id']) as $member) {
            $users[] = (int) $member['user_id'];
        }
        $users = array_values(array_unique($users));
        $users = array_values(array_diff($users, array((int) $this->api_auth->user_id())));
        if ($users) {
            $this->sek_notifier->notify_users($users, $type, $title, $body, array(
                'order_id' => (int) $order['order_id'],
                'code' => (string) $order['code'],
                'status' => (string) $order['status'],
            ));
        }
    }

    private function order_payloads($rows)
    {
        if (!$rows) {
            return array();
        }
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int) $row['order_id'];
        }
        $items = $this->sek_order_model->items_for($ids);
        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->order_payload($row, $items);
        }
        return $data;
    }

    private function order_payload($order, $items_map = NULL)
    {
        $order_id = (int) $order['order_id'];
        if ($items_map === NULL) {
            $items_map = $this->sek_order_model->items_for(array($order_id));
        }
        $items = array();
        if (isset($items_map[$order_id])) {
            foreach ($items_map[$order_id] as $item) {
                $items[] = array(
                    'service_id' => $item['service_id'] !== NULL ? (int) $item['service_id'] : NULL,
                    'name' => (string) $item['name'],
                    'price' => (float) $item['price'],
                    'quantity' => (int) $item['quantity'],
                    'subtotal' => (float) $item['subtotal'],
                );
            }
        }
        return array(
            'order_id' => $order_id,
            'code' => (string) $order['code'],
            'type' => (string) $order['type'],
            'status' => (string) $order['status'],
            'payment_method' => (string) $order['payment_method'],
            'payment_status' => (string) $order['payment_status'],
            'subtotal' => (float) $order['subtotal'],
            'total' => (float) $order['total'],
            'currency' => (string) $order['currency'],
            'notes' => $order['notes'],
            'items' => $items,
            'provider' => array(
                'account_id' => (int) $order['provider_account_id'],
                'handle' => (string) $order['provider_handle'],
                'display_name' => (string) $order['provider_name'],
                'type_code' => (string) $order['provider_type'],
                'avatar_media_id' => $order['provider_avatar_media_id'] !== NULL ? (int) $order['provider_avatar_media_id'] : NULL,
            ),
            'customer' => array(
                'user_id' => (int) $order['customer_user_id'],
                'display_name' => (string) $order['customer_name'],
            ),
            'delivery' => $order['delivery_lat'] !== NULL ? array(
                'lat' => (float) $order['delivery_lat'],
                'lng' => (float) $order['delivery_lng'],
                'address' => $order['delivery_address'],
            ) : NULL,
            'pickup' => $order['pickup_lat'] !== NULL ? array(
                'lat' => (float) $order['pickup_lat'],
                'lng' => (float) $order['pickup_lng'],
                'address' => $order['pickup_address'],
            ) : NULL,
            'dropoff' => $order['dropoff_lat'] !== NULL ? array(
                'lat' => (float) $order['dropoff_lat'],
                'lng' => (float) $order['dropoff_lng'],
                'address' => $order['dropoff_address'],
            ) : NULL,
            'scheduled_at' => $this->utc($order['scheduled_at']),
            'cancel_reason' => $order['cancel_reason'],
            'created_at' => $this->utc($order['created_at']),
            'updated_at' => $this->utc($order['updated_at']),
            'accepted_at' => $this->utc($order['accepted_at']),
            'started_at' => $this->utc($order['started_at']),
            'completed_at' => $this->utc($order['completed_at']),
            'cancelled_at' => $this->utc($order['cancelled_at']),
        );
    }

    private function parse_utc($value)
    {
        $value = trim((string) $value);
        $value = str_replace('T', ' ', $value);
        $value = rtrim($value, 'Z');
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }
        $date = DateTime::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
        return $date !== FALSE ? $date->format('Y-m-d H:i:s') : NULL;
    }
}
