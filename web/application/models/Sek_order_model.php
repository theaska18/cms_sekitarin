<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Service catalog and order state machine. */
class Sek_order_model extends CI_Model
{
    public function services($account_id, $active_only = TRUE)
    {
        $this->db->where('account_id', (int) $account_id);
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        return $this->db->order_by('kind', 'ASC')->order_by('name', 'ASC')
            ->get('sek_service')
            ->result_array();
    }

    public function find_service($service_id)
    {
        return $this->db->where('service_id', (int) $service_id)
            ->limit(1)
            ->get('sek_service')
            ->row_array();
    }

    public function create_service($account_id, $data)
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->insert('sek_service', array(
            'account_id' => (int) $account_id,
            'name' => (string) $data['name'],
            'description' => isset($data['description']) ? $data['description'] : NULL,
            'price' => round((float) $data['price'], 2),
            'currency' => isset($data['currency']) ? strtoupper((string) $data['currency']) : 'IDR',
            'unit' => isset($data['unit']) ? (string) $data['unit'] : 'item',
            'kind' => (string) $data['kind'],
            'duration_minutes' => isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : NULL,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        return (int) $this->db->insert_id();
    }

    public function update_service($service_id, $fields)
    {
        if (!$fields) {
            return TRUE;
        }
        $fields['updated_at'] = gmdate('Y-m-d H:i:s');
        return $this->db->where('service_id', (int) $service_id)
            ->update('sek_service', $fields);
    }

    public function archive_service($service_id)
    {
        return $this->db->where('service_id', (int) $service_id)
            ->update('sek_service', array('is_active' => 0, 'updated_at' => gmdate('Y-m-d H:i:s')));
    }

    public function create_order($customer_user_id, $customer_account_id, $provider_account_id, $type, $items, $data)
    {
        $now = gmdate('Y-m-d H:i:s');
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['subtotal'];
        }
        $this->db->trans_start();
        $this->db->insert('sek_order', array(
            'code' => $this->unique_code(),
            'type' => (string) $type,
            'customer_user_id' => (int) $customer_user_id,
            'customer_account_id' => $customer_account_id !== NULL ? (int) $customer_account_id : NULL,
            'provider_account_id' => (int) $provider_account_id,
            'status' => 'pending',
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
            'currency' => isset($data['currency']) ? strtoupper((string) $data['currency']) : 'IDR',
            'payment_method' => isset($data['payment_method']) ? (string) $data['payment_method'] : 'cash',
            'notes' => isset($data['notes']) ? $data['notes'] : NULL,
            'delivery_address' => isset($data['delivery_address']) ? $data['delivery_address'] : NULL,
            'delivery_lat' => isset($data['delivery_lat']) ? $data['delivery_lat'] : NULL,
            'delivery_lng' => isset($data['delivery_lng']) ? $data['delivery_lng'] : NULL,
            'pickup_address' => isset($data['pickup_address']) ? $data['pickup_address'] : NULL,
            'pickup_lat' => isset($data['pickup_lat']) ? $data['pickup_lat'] : NULL,
            'pickup_lng' => isset($data['pickup_lng']) ? $data['pickup_lng'] : NULL,
            'dropoff_address' => isset($data['dropoff_address']) ? $data['dropoff_address'] : NULL,
            'dropoff_lat' => isset($data['dropoff_lat']) ? $data['dropoff_lat'] : NULL,
            'dropoff_lng' => isset($data['dropoff_lng']) ? $data['dropoff_lng'] : NULL,
            'scheduled_at' => isset($data['scheduled_at']) ? $data['scheduled_at'] : NULL,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $order_id = (int) $this->db->insert_id();
        foreach ($items as $item) {
            $this->db->insert('sek_order_item', array(
                'order_id' => $order_id,
                'service_id' => isset($item['service_id']) ? (int) $item['service_id'] : NULL,
                'name' => (string) $item['name'],
                'price' => round((float) $item['price'], 2),
                'quantity' => (int) $item['quantity'],
                'subtotal' => round((float) $item['subtotal'], 2),
            ));
        }
        $this->db->insert('sek_order_event', array(
            'order_id' => $order_id,
            'status' => 'pending',
            'actor_user_id' => (int) $customer_user_id,
            'note' => 'Order dibuat.',
            'created_at' => $now,
        ));
        $this->db->trans_complete();
        return $this->db->trans_status() ? $order_id : NULL;
    }

    public function find_order($order_id)
    {
        return $this->db->select('o.*, a.handle AS provider_handle, a.display_name AS provider_name, '
                . 'a.type_code AS provider_type, a.avatar_media_id AS provider_avatar_media_id, '
                . 'u.display_name AS customer_name')
            ->from('sek_order o')
            ->join('sek_account a', 'a.account_id = o.provider_account_id', 'inner')
            ->join('sek_user u', 'u.user_id = o.customer_user_id', 'inner')
            ->where('o.order_id', (int) $order_id)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function list_for_customer($user_id, $status, $before_id, $limit)
    {
        $query = $this->base_order_query();
        $query->where('o.customer_user_id', (int) $user_id);
        if ($status !== NULL && $status !== '') {
            $query->where('o.status', (string) $status);
        }
        if ((int) $before_id > 0) {
            $query->where('o.order_id <', (int) $before_id);
        }
        return $query->limit(max(1, min(50, (int) $limit)) + 1)->get()->result_array();
    }

    public function list_for_provider($account_id, $status, $before_id, $limit)
    {
        $query = $this->base_order_query();
        $query->where('o.provider_account_id', (int) $account_id);
        if ($status !== NULL && $status !== '') {
            $query->where('o.status', (string) $status);
        }
        if ((int) $before_id > 0) {
            $query->where('o.order_id <', (int) $before_id);
        }
        return $query->limit(max(1, min(50, (int) $limit)) + 1)->get()->result_array();
    }

    public function items_for($order_ids)
    {
        if (!$order_ids) {
            return array();
        }
        $rows = $this->db->where_in('order_id', array_map('intval', $order_ids))
            ->order_by('item_id', 'ASC')
            ->get('sek_order_item')
            ->result_array();
        $grouped = array();
        foreach ($rows as $row) {
            $grouped[(int) $row['order_id']][] = $row;
        }
        return $grouped;
    }

    public function items($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)
            ->order_by('item_id', 'ASC')
            ->get('sek_order_item')
            ->result_array();
    }

    public function events($order_id)
    {
        return $this->db->select('e.*, u.display_name AS actor_name')
            ->from('sek_order_event e')
            ->join('sek_user u', 'u.user_id = e.actor_user_id', 'left')
            ->where('e.order_id', (int) $order_id)
            ->order_by('e.event_id', 'ASC')
            ->get()
            ->result_array();
    }

    public function transition($order_id, $status, $actor_user_id, $note = NULL, $fields = array())
    {
        $now = gmdate('Y-m-d H:i:s');
        $update = array_merge($fields, array('status' => (string) $status, 'updated_at' => $now));
        if ($status === 'accepted') {
            $update['accepted_at'] = $now;
        } elseif ($status === 'in_progress' || $status === 'delivering') {
            $update['started_at'] = $now;
        } elseif ($status === 'completed') {
            $update['completed_at'] = $now;
        } elseif ($status === 'cancelled') {
            $update['cancelled_at'] = $now;
        }

        $this->db->trans_start();
        $this->db->where('order_id', (int) $order_id)->update('sek_order', $update);
        $this->db->insert('sek_order_event', array(
            'order_id' => (int) $order_id,
            'status' => (string) $status,
            'actor_user_id' => (int) $actor_user_id,
            'note' => $note !== NULL ? mb_substr((string) $note, 0, 255) : NULL,
            'created_at' => $now,
        ));
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function set_payment($order_id, $status)
    {
        return $this->db->where('order_id', (int) $order_id)
            ->update('sek_order', array(
                'payment_status' => (string) $status,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ));
    }

    private function base_order_query()
    {
        return $this->db->select('o.*, a.handle AS provider_handle, a.display_name AS provider_name, '
                . 'a.type_code AS provider_type, a.avatar_media_id AS provider_avatar_media_id, '
                . 'u.display_name AS customer_name')
            ->from('sek_order o')
            ->join('sek_account a', 'a.account_id = o.provider_account_id', 'inner')
            ->join('sek_user u', 'u.user_id = o.customer_user_id', 'inner')
            ->order_by('o.order_id', 'DESC');
    }

    private function unique_code()
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = 'ORD-' . gmdate('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $existing = $this->db->where('code', $code)->count_all_results('sek_order');
            if ($existing === 0) {
                return $code;
            }
        }
        return 'ORD-' . gmdate('ymdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }
}
