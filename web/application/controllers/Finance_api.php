<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** External invoice intake API. Authenticated by app_user.api_secret. */
class Finance_api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function sales_invoices()
    {
        $this->receive('sales');
    }

    public function purchase_bills()
    {
        $this->receive('purchase');
    }

    private function receive($kind)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            $this->json(array('status' => 'error', 'message' => 'POST required.'), 405, 'POST');
            return;
        }
        $user = $this->authenticate();
        if ($user === NULL) {
            $this->json(array('status' => 'error', 'message' => 'Invalid API secret.'), 401);
            return;
        }
        $raw = (string) $this->input->raw_input_stream;
        $payload = json_decode($raw, TRUE);
        if (!is_array($payload)) {
            $this->json(array('status' => 'error', 'message' => 'Invalid JSON body.'), 400);
            return;
        }

        $spec = $kind === 'sales'
            ? array('number' => 'invoice_number', 'party' => 'customer_name', 'code' => 'customer_code')
            : array('number' => 'bill_number', 'party' => 'supplier_name', 'code' => 'supplier_code');
        $number = isset($payload[$spec['number']]) ? trim((string) $payload[$spec['number']]) : '';
        $party = isset($payload[$spec['party']]) ? trim((string) $payload[$spec['party']]) : '';
        $party_code = isset($payload[$spec['code']]) ? trim((string) $payload[$spec['code']]) : '';
        $amount_raw = isset($payload['amount_before_tax']) ? trim((string) $payload['amount_before_tax']) : '';
        $tax_raw = isset($payload['tax_percent']) ? trim((string) $payload['tax_percent']) : '0';
        $description = isset($payload['description']) ? trim((string) $payload['description']) : '';

        $errors = array();
        if ($number === '' || strlen($number) > 48) {
            $errors[] = $spec['number'];
        }
        if ($party === '' || strlen($party) > 160) {
            $errors[] = $spec['party'];
        }
        if ($party_code !== '' && strlen($party_code) > 32) {
            $errors[] = $spec['code'];
        }
        if (!preg_match('/^\d{1,14}(?:\.\d{1,6})?$/', $amount_raw) || (float) $amount_raw <= 0) {
            $errors[] = 'amount_before_tax';
        }
        if (!preg_match('/^\d{1,3}(?:\.\d{1,6})?$/', $tax_raw) || (float) $tax_raw < 0 || (float) $tax_raw > 100) {
            $errors[] = 'tax_percent';
        }
        if ($description === '' || strlen($description) > 240) {
            $errors[] = 'description';
        }
        if ($errors) {
            $this->json(array(
                'status' => 'error',
                'message' => 'Validation failed.',
                'fields' => array_values(array_unique($errors)),
            ), 422);
            return;
        }

        $subtotal = round((float) $amount_raw, 6);
        $tax_percent = round((float) $tax_raw, 6);
        $tax_amount = round($subtotal * $tax_percent / 100, 6);
        $total = round($subtotal + $tax_amount, 6);

        $this->load->library('finance_integration_store');
        $store = $this->finance_integration_store;
        $tenant_id = (int) $user['tenant_id'];
        $existing = $store->find_number($kind, $tenant_id, $number);
        if ($existing !== NULL) {
            $this->json(array(
                'status' => 'duplicate',
                'message' => 'Document number already received.',
                'inbox_id' => (int) $existing[$kind === 'sales' ? 'sales_invoice_inbox_id' : 'purchase_bill_inbox_id'],
            ), 409);
            return;
        }

        $created = $store->create($kind, $tenant_id, (int) $user['user_id'], array(
            'number' => $number,
            'party_name' => $party,
            'party_code' => $party_code,
            'amount_before_tax' => $subtotal,
            'tax_percent' => $tax_percent,
            'tax_amount' => $tax_amount,
            'total_amount' => $total,
            'description' => $description,
            'payload_json' => $raw,
        ));
        if ($created !== TRUE) {
            $existing = $store->find_number($kind, $tenant_id, $number);
            if ($existing !== NULL) {
                $this->json(array(
                    'status' => 'duplicate',
                    'message' => 'Document number already received.',
                    'inbox_id' => (int) $existing[$kind === 'sales' ? 'sales_invoice_inbox_id' : 'purchase_bill_inbox_id'],
                ), 409);
                return;
            }
            $this->json(array('status' => 'error', 'message' => 'Unable to store submission.'), 500);
            return;
        }

        $inbox_id = (int) $this->db->insert_id();
        $this->json(array(
            'status' => 'accepted',
            'inbox_id' => $inbox_id,
            'review_status' => 'received',
            'document_type' => $kind === 'sales' ? 'sales_invoice' : 'purchase_bill',
            'document_number' => $number,
            'total_amount' => $total,
        ), 202);
    }

    private function authenticate()
    {
        $secret = '';
        $authorization = (string) $this->input->get_request_header('Authorization', TRUE);
        if (preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches)) {
            $secret = trim($matches[1]);
        }
        if ($secret === '') {
            $secret = trim((string) $this->input->get_request_header('X-API-Secret', TRUE));
        }
        if ($secret === '') {
            return NULL;
        }
        $query = $this->db->select('user_id, tenant_id, api_secret, access_api_flag, block_flag')
            ->from('app_user')
            ->where('api_secret', $secret)
            ->limit(2)
            ->get();
        if ($query === FALSE || $query->num_rows() !== 1) {
            return NULL;
        }
        $user = $query->row_array();
        if (!hash_equals((string) $user['api_secret'], $secret)
            || (int) $user['access_api_flag'] !== 1
            || (int) $user['block_flag'] !== 0) {
            return NULL;
        }
        return $user;
    }

    private function json($payload, $status, $allow = '')
    {
        if ($allow !== '') {
            $this->output->set_header('Allow: ' . $allow);
        }
        $this->output
            ->set_status_header($status)
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
