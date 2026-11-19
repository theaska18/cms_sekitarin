<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Finance integration inbox: review external invoices and convert them to drafts. */
class Admin_finance_integration extends Admin
{
    public function sales_invoice_index()
    {
        $this->render_inbox('sales');
    }

    public function purchase_bill_index()
    {
        $this->render_inbox('purchase');
    }

    public function sales_invoice_data()
    {
        $this->inbox_data('sales');
    }

    public function purchase_bill_data()
    {
        $this->inbox_data('purchase');
    }

    public function sales_invoice_draft($inbox_id = 0)
    {
        $this->draft('sales', $inbox_id);
    }

    public function purchase_bill_draft($inbox_id = 0)
    {
        $this->draft('purchase', $inbox_id);
    }

    private function render_inbox($kind)
    {
        $this->load->library('finance_integration_store');
        $spec = $this->finance_integration_store->spec($kind);
        if ($spec === NULL || !$this->has_admin_menu_code($spec['menu_code'])) {
            $this->forbidden();
            return;
        }
        $this->render_admin(
            'admin/finance/integration_index',
            'finance_' . ($kind === 'sales' ? 'sales_invoice_inbox' : 'purchase_bill_inbox'),
            '/admin/finance/' . ($kind === 'sales' ? 'sales_invoice_inbox' : 'purchase_bill_inbox'),
            array(
                'finance_integration_kind' => $kind,
                'finance_integration_slug' => $spec['slug'],
                'finance_integration_menu' => $spec['menu_code'],
                'finance_integration_available' => $this->finance_integration_store->available($kind),
                'finance_integration_can_process' => $this->has_admin_access_code($spec['menu_code'] . '_add'),
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function inbox_data($kind)
    {
        $this->load->library('datatable_server');
        $this->load->library('finance_integration_store');
        $spec = $this->finance_integration_store->spec($kind);
        if ($spec === NULL || !$this->has_admin_menu_code($spec['menu_code'])) {
            $this->datatable_server->error(403);
            return;
        }
        $store = $this->finance_integration_store;
        if (!$store->available($kind)) {
            $this->datatable_server->error(503);
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $filters = $this->inbox_filters();
        $columns = array(
            1 => 'received_at',
            2 => $spec['date'],
            3 => $spec['number'],
            4 => $spec['party_name'],
            5 => $spec['party_code'],
            6 => 'amount_before_tax',
            7 => 'tax_percent',
            8 => 'total_amount',
            9 => 'status',
        );
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $kind, $tenant_id, $filters) { return $store->count($kind, $tenant_id, '', $filters); },
            function ($search) use ($store, $kind, $tenant_id, $filters) { return $store->count($kind, $tenant_id, $search, $filters); },
            function ($search, $length, $offset, $order, $direction) use ($store, $kind, $tenant_id, $filters) {
                return $store->page($kind, $tenant_id, $search, $length, $offset, $order, $direction, $filters);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'received_at')
        );
    }

    private function draft($kind, $inbox_id)
    {
        $this->load->library('finance_integration_store');
        $this->load->library('finance_document_store');
        $spec = $this->finance_integration_store->spec($kind);
        $inbox_id = (int) $inbox_id;
        if ($spec === NULL || $inbox_id < 1) {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code($spec['menu_code']) || !$this->has_admin_access_code($spec['menu_code'] . '_add')) {
            $this->forbidden();
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $inbox = $this->finance_integration_store->find($kind, $tenant_id, $inbox_id);
        if ($inbox === FALSE) {
            show_error('Integration inbox is unavailable.', 503);
            return;
        }
        if ($inbox === NULL) {
            show_404();
            return;
        }
        $document_id = (int) $inbox[$spec['document_id']];
        if ($document_id > 0) {
            redirect('/admin/finance/' . $spec['slug'] . '/edit/' . $document_id, 'location', 303);
            return;
        }
        if ((string) $inbox['status'] === 'cancelled') {
            $this->output->set_status_header(409);
            show_error('This integration submission has been cancelled.', 409);
            return;
        }

        $store = $this->finance_document_store;
        $available = $store->available($kind);
        $base_currency = '';
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        if ($settings !== FALSE && $settings->num_rows()) {
            $base_currency = strtoupper((string) $settings->row()->base_currency);
        }
        $document_date = !empty($inbox[$spec['date']]) ? (string) $inbox[$spec['date']] : date('Y-m-d');
        $due_date = !empty($inbox['due_date']) ? (string) $inbox['due_date'] : '';
        $record = array(
            'document_date' => $document_date,
            'due_date' => $due_date,
            'person_id' => '',
            'account_id' => '',
            'tax_id' => '',
            'department_id' => '',
            'currency_code' => $base_currency,
            'exchange_rate' => '1.00',
            'amount' => $this->format_amount($inbox['amount_before_tax']),
            'description' => (string) $inbox['description'],
            'memo' => 'Integrasi ' . (string) $inbox[$spec['number']] . ' · ' . (string) $inbox[$spec['party_name']],
        );
        $lookup_labels = array('person_id' => '', 'account_id' => '', 'tax_id' => '');
        $errors = array();
        $write_available = $available && $this->write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->values();
            $values['amount'] = (string) $inbox['amount_before_tax'];
            $values['description'] = (string) $inbox['description'];
            $values['integration_number'] = (string) $inbox[$spec['number']];
            $values['integration_party_name'] = (string) $inbox[$spec['party_name']];
            $values['integration_party_code'] = $inbox[$spec['party_code']] !== NULL ? (string) $inbox[$spec['party_code']] : '';
            $errors = $this->validate($values, $kind);
            if (!$errors) {
                $previous_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft($tenant_id, (int) $this->current_user['user_id'], $kind, NULL, $values);
                $this->db->db_debug = $previous_debug;
                if ($result === 'saved') {
                    $document_id = (int) $store->last_saved_id;
                    if ($document_id > 0) {
                        $this->finance_integration_store->mark_processed($kind, $tenant_id, $inbox_id, $document_id);
                    }
                    $this->session->set_flashdata('finance_status', 'draft_saved');
                    redirect('/admin/finance/' . $spec['slug'] . '/edit/' . $document_id, 'location', 303);
                    return;
                }
                $errors[] = 'document_' . $result;
            }
            $record = array_merge($record, $values);
            $record['amount'] = $this->format_amount($inbox['amount_before_tax']);
            $record['description'] = (string) $inbox['description'];
            if ($record['exchange_rate'] !== '') {
                $record['exchange_rate'] = number_format((float) $record['exchange_rate'], 2, '.', '');
            }
            foreach (array('party', 'account', 'tax') as $lookup_kind) {
                $field = $lookup_kind === 'party' ? 'person_id' : ($lookup_kind === 'account' ? 'account_id' : 'tax_id');
                $lookup_labels[$field] = !empty($record[$field])
                    ? $store->lookup_label($tenant_id, $kind, $lookup_kind, $record[$field])
                    : '';
            }
        }

        $this->render_admin(
            'admin/finance/document_form',
            'finance_' . $spec['slug'],
            '/admin/finance/' . ($kind === 'sales' ? 'sales_invoice_inbox' : 'purchase_bill_inbox') . '/draft/' . $inbox_id,
            array(
                'finance_document_slug' => $spec['slug'],
                'finance_document_kind' => $kind,
                'finance_document_record' => $record,
                'finance_document_id' => NULL,
                'finance_document_errors' => $errors,
                'finance_document_available' => $available,
                'finance_document_write_available' => $write_available,
                'finance_document_lookup_labels' => $lookup_labels,
                'finance_document_amount_readonly' => TRUE,
                'finance_document_description_readonly' => TRUE,
                'finance_document_party_label' => $kind === 'sales' ? 'Nama Pelanggan' : 'Nama Pemasok',
                'finance_document_cancel_url' => '/admin/finance/' . ($kind === 'sales' ? 'sales_invoice_inbox' : 'purchase_bill_inbox'),
                'finance_document_integration_reference' => trim((string) $inbox[$spec['number']] . (!empty($inbox[$spec['date']]) ? ' · ' . (string) $inbox[$spec['date']] : '')),
                'finance_document_integration_party' => trim((string) $inbox[$spec['party_name']] . ($inbox[$spec['party_code']] !== NULL && $inbox[$spec['party_code']] !== '' ? ' (' . (string) $inbox[$spec['party_code']] . ')' : '')),
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function inbox_filters()
    {
        $today = date('Y-m-d');
        $default_start = date('Y-m-d', strtotime('-6 days'));
        $start = trim((string) $this->input->get('start_on', TRUE));
        $end = trim((string) $this->input->get('end_on', TRUE));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = $default_start;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = $today;
        if ($start > $end) { $start = $default_start; $end = $today; }
        $status = trim((string) $this->input->get('status', TRUE));
        if (!in_array($status, array('', 'received', 'processed', 'cancelled'), TRUE)) $status = '';
        return array('start_on' => $start, 'end_on' => $end, 'status' => $status, 'flag' => '');
    }

    private function format_amount($value)
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function values()
    {
        $values = array();
        foreach (array('document_date', 'due_date', 'person_id', 'account_id', 'tax_id', 'department_id', 'currency_code', 'exchange_rate', 'memo') as $name) {
            $raw = $this->input->post($name, TRUE);
            $values[$name] = is_scalar($raw) ? trim((string) $raw) : '';
        }
        $values['tax_id'] = $values['tax_id'] === '' ? NULL : (int) $values['tax_id'];
        $values['department_id'] = $values['department_id'] === '' ? NULL : (int) $values['department_id'];
        $values['person_id'] = (int) $values['person_id'];
        $values['account_id'] = (int) $values['account_id'];
        $values['currency_code'] = strtoupper($values['currency_code']);
        return $values;
    }

    private function validate($values, $kind = 'sales')
    {
        $errors = array();
        if ($values['person_id'] < 1) {
            $errors[] = 'person_id_required';
        }
        if ($values['account_id'] < 1) {
            $errors[] = 'account_id_required';
        }
        foreach (array('document_date', 'due_date') as $key) {
            if ($values[$key] === '' && $key === 'due_date') {
                continue;
            }
            $date = DateTime::createFromFormat('!Y-m-d', $values[$key]);
            if ($date === FALSE || $date->format('Y-m-d') !== $values[$key]) {
                $errors[] = $key . '_invalid';
            }
        }
        if ($values['due_date'] !== '' && $values['due_date'] < $values['document_date']) {
            $errors[] = 'due_date_invalid';
        }
        if (!preg_match('/^[A-Z]{3}$/', $values['currency_code'])) {
            $errors[] = 'currency_invalid';
        }
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $values['exchange_rate']) || (float) $values['exchange_rate'] <= 0) {
            $errors[] = 'exchange_rate_invalid';
        }
        if (strlen($values['memo']) > 10000) {
            $errors[] = 'text_too_long';
        }
        return array_values(array_unique($errors));
    }

    private function write_available()
    {
        return $this->current_user !== NULL && (bool) config_item('csrf_protection');
    }

    private function forbidden()
    {
        $this->output->set_status_header(403);
        show_error('You do not have permission to access this Finance page.', 403);
    }
}
