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

    public function sales_invoices($inbox_id = NULL)
    {
        $this->dispatch('sales', $inbox_id);
    }

    public function purchase_bills($inbox_id = NULL)
    {
        $this->dispatch('purchase', $inbox_id);
    }

    private function dispatch($kind, $inbox_id)
    {
        $method = strtoupper((string) $this->input->method(TRUE));
        $has_path_id = $inbox_id !== NULL && trim((string) $inbox_id) !== '';

        if ($method === 'POST' && !$has_path_id) {
            $this->receive($kind);
            return;
        }
        if ($method === 'GET' || $method === 'DELETE') {
            $this->read_or_cancel($kind, $inbox_id, $method);
            return;
        }

        $allow = $has_path_id ? 'GET, DELETE' : 'GET, POST, DELETE';
        $this->json(array('status' => 'error', 'message' => 'Method not allowed.'), 405, $allow);
    }

    private function receive($kind)
    {
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

        $spec = $this->payload_spec($kind);
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
            $this->duplicate_response($kind, $existing);
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
                $this->duplicate_response($kind, $existing);
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

    private function read_or_cancel($kind, $path_id, $method)
    {
        $user = $this->authenticate();
        if ($user === NULL) {
            $this->json(array('status' => 'error', 'message' => 'Invalid API secret.'), 401);
            return;
        }

        $identifier = $this->resolve_identifier($kind, $path_id);
        if (isset($identifier['error'])) {
            $this->json(array('status' => 'error', 'message' => $identifier['error']), 400);
            return;
        }

        $this->load->library('finance_integration_store');
        $store = $this->finance_integration_store;
        $tenant_id = (int) $user['tenant_id'];
        $row = isset($identifier['id'])
            ? $store->find($kind, $tenant_id, $identifier['id'])
            : $store->find_number($kind, $tenant_id, $identifier['number']);

        if ($row === FALSE) {
            $this->json(array('status' => 'error', 'message' => 'Integration inbox is unavailable.'), 503);
            return;
        }
        if ($row === NULL) {
            $this->json(array('status' => 'error', 'message' => 'Submission not found.'), 404);
            return;
        }

        if ($method === 'GET') {
            $this->status_response($kind, $row, $store);
            return;
        }
        $this->cancel_response($kind, $row, $store, $tenant_id);
    }

    private function resolve_identifier($kind, $path_id)
    {
        $number_key = $kind === 'sales' ? 'invoice_number' : 'bill_number';
        $path_id = $path_id === NULL ? '' : trim((string) $path_id);
        $query_id = trim((string) $this->input->get('inbox_id', TRUE));
        $number = trim((string) $this->input->get($number_key, TRUE));

        $provided = 0;
        if ($path_id !== '') {
            $provided++;
        }
        if ($query_id !== '') {
            $provided++;
        }
        if ($number !== '') {
            $provided++;
        }
        if ($provided < 1) {
            return array('error' => 'Provide inbox_id or document number.');
        }
        if ($provided > 1) {
            return array('error' => 'Provide only one identifier.');
        }

        $id = $path_id !== '' ? $path_id : $query_id;
        if ($id !== '') {
            if (!ctype_digit($id) || (int) $id < 1) {
                return array('error' => 'inbox_id must be a positive integer.');
            }
            return array('id' => (int) $id);
        }
        if (strlen($number) > 48) {
            return array('error' => 'Document number is too long.');
        }
        return array('number' => $number);
    }

    private function status_response($kind, $row, $store)
    {
        $spec = $store->spec($kind);
        $draft_id = (int) $row[$spec['document_id']];
        $draft_status = $draft_id > 0 ? $store->document_status($kind, (int) $row['tenant_id'], $draft_id) : NULL;

        $this->json(array(
            'status' => 'ok',
            'inbox_id' => (int) $row[$spec['id']],
            'document_type' => $kind === 'sales' ? 'sales_invoice' : 'purchase_bill',
            'document_number' => (string) $row[$spec['number']],
            'party_name' => (string) $row[$spec['party_name']],
            'party_code' => $row[$spec['party_code']] !== NULL ? (string) $row[$spec['party_code']] : NULL,
            'amount_before_tax' => (float) $row['amount_before_tax'],
            'tax_percent' => (float) $row['tax_percent'],
            'tax_amount' => (float) $row['tax_amount'],
            'total_amount' => (float) $row['total_amount'],
            'review_status' => (string) $row['status'],
            'draft_created' => $draft_id > 0,
            'draft_id' => $draft_id > 0 ? $draft_id : NULL,
            'draft_status' => $draft_status,
            'received_at' => $row['received_at'],
            'processed_at' => $row['processed_at'],
            'cancelled_at' => array_key_exists('cancelled_at', $row) ? $row['cancelled_at'] : NULL,
        ), 200);
    }

    private function cancel_response($kind, $row, $store, $tenant_id)
    {
        $spec = $store->spec($kind);
        $inbox_id = (int) $row[$spec['id']];
        $draft_id = (int) $row[$spec['document_id']];

        if ($draft_id > 0) {
            $draft_status = $store->document_status($kind, $tenant_id, $draft_id);
            $this->json(array(
                'status' => 'error',
                'message' => $draft_status === 'posted' ? 'Document already posted.' : 'Draft already created.',
                'inbox_id' => $inbox_id,
                'draft_id' => $draft_id,
                'draft_status' => $draft_status,
            ), 409);
            return;
        }

        if ((string) $row['status'] === 'cancelled') {
            $this->json(array(
                'status' => 'cancelled',
                'review_status' => 'cancelled',
                'inbox_id' => $inbox_id,
                'cancelled_at' => array_key_exists('cancelled_at', $row) ? $row['cancelled_at'] : NULL,
                'already_cancelled' => TRUE,
            ), 200);
            return;
        }

        if ((string) $row['status'] !== 'received') {
            $this->json(array(
                'status' => 'error',
                'message' => 'Only received submissions can be cancelled.',
                'inbox_id' => $inbox_id,
                'review_status' => (string) $row['status'],
            ), 409);
            return;
        }

        $cancelled_at = date('Y-m-d H:i:s');
        if ($store->cancel($kind, $tenant_id, $inbox_id, $cancelled_at) !== TRUE) {
            $fresh = $store->find($kind, $tenant_id, $inbox_id);
            if (is_array($fresh) && (string) $fresh['status'] === 'cancelled') {
                $this->json(array(
                    'status' => 'cancelled',
                    'review_status' => 'cancelled',
                    'inbox_id' => $inbox_id,
                    'cancelled_at' => array_key_exists('cancelled_at', $fresh) ? $fresh['cancelled_at'] : NULL,
                    'already_cancelled' => TRUE,
                ), 200);
                return;
            }
            if (is_array($fresh) && (int) $fresh[$spec['document_id']] > 0) {
                $this->json(array(
                    'status' => 'error',
                    'message' => 'Draft already created.',
                    'inbox_id' => $inbox_id,
                    'draft_id' => (int) $fresh[$spec['document_id']],
                ), 409);
                return;
            }
            $this->json(array('status' => 'error', 'message' => 'Unable to cancel submission.'), 500);
            return;
        }

        $this->json(array(
            'status' => 'cancelled',
            'review_status' => 'cancelled',
            'inbox_id' => $inbox_id,
            'cancelled_at' => $cancelled_at,
        ), 200);
    }

    private function duplicate_response($kind, $row)
    {
        $key = $kind === 'sales' ? 'sales_invoice_inbox_id' : 'purchase_bill_inbox_id';
        $this->json(array(
            'status' => 'duplicate',
            'message' => 'Document number already received.',
            'inbox_id' => (int) $row[$key],
            'review_status' => (string) $row['status'],
        ), 409);
    }

    private function payload_spec($kind)
    {
        return $kind === 'sales'
            ? array('number' => 'invoice_number', 'party' => 'customer_name', 'code' => 'customer_code')
            : array('number' => 'bill_number', 'party' => 'supplier_name', 'code' => 'supplier_code');
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
