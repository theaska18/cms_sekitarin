<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Finance setup/master-data pages; accounting workflows are implemented separately. */
class Admin_finance extends Admin
{
    public function index($slug = '')
    {
        if ($slug === 'dashboard') {
            $this->finance_dashboard();
            return;
        }
        if (in_array($slug, array('all_transactions', 'income', 'expenses'), TRUE)) {
            $this->finance_transaction_index($slug);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_index($slug);
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_index($slug);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_index($slug);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_index();
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_index();
            return;
        }
        if ($this->finance_report_menu_code($slug) !== NULL) {
            $this->finance_report_index($slug);
            return;
        }
        $resource = $this->finance_resource($slug);
        if ($resource === NULL) {
            $this->module('finance', $slug);
            return;
        }
        if (!$this->finance_menu_allowed($resource)) {
            $this->finance_forbidden();
            return;
        }

        $this->load->library('finance_store');
        $store = $this->finance_store;
        $available = $store->available($resource);
        foreach ($resource['columns'] as &$column) {
            $column['label_key'] = isset($column['label_key'])
                ? $column['label_key']
                : 'finance_resource_column_' . $column['name'];
            foreach ($resource['fields'] as $field) {
                if ($field['name'] === $column['name'] && !empty($field['options'])) {
                    $column['value_label_keys'] = array();
                    foreach ($field['options'] as $option => $option_label) {
                        $column['value_label_keys'][$option] = isset($field['option_label_keys'][$option])
                            ? $field['option_label_keys'][$option]
                            : 'finance_resource_option_' . $field['name'] . '_' . $option;
                    }
                    break;
                }
            }
        }
        unset($column);
        $this->render_admin(
            'admin/finance/resource_index',
            $resource['title_key'],
            '/admin/finance/' . $resource['slug'],
            array(
                'finance_resource' => $resource,
                'finance_available' => $available,
                'finance_can_add' => $this->has_admin_access_code($resource['menu_code'] . '_add')
                    && !($slug === 'financial_settings' && $available
                        && $store->count($resource, (int) $this->current_user['tenant_id']) > 0),
                'finance_can_update' => $this->has_admin_access_code($resource['menu_code'] . '_update'),
                'finance_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
            )
        );
    }

    public function data($slug = '')
    {
        if (in_array($slug, array('all_transactions', 'income', 'expenses'), TRUE)) {
            $this->finance_transaction_data($slug);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_data($slug);
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_data($slug);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_data($slug);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_data();
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_data();
            return;
        }
        if ($this->finance_report_menu_code($slug) !== NULL) {
            $this->finance_report_data($slug);
            return;
        }
        $resource = $this->finance_resource($slug);
        $this->load->library('datatable_server');
        if ($resource === NULL || !$this->finance_menu_allowed($resource)) {
            $this->datatable_server->error(403);
            return;
        }

        $this->load->library('finance_store');
        $store = $this->finance_store;
        if (!$store->available($resource)) {
            $this->datatable_server->error(503);
            return;
        }

        $columns = array();
        $column_offset = $this->has_admin_access_code($resource['menu_code'] . '_update') ? 1 : 0;
        foreach ($resource['columns'] as $index => $column) {
            $columns[$index + $column_offset] = $column['name'];
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $resource, $tenant_id) {
                return $store->count($resource, $tenant_id);
            },
            function ($search) use ($store, $resource, $tenant_id) {
                return $store->count($resource, $tenant_id, $search);
            },
            function ($search, $length, $start, $order, $direction) use ($store, $resource, $tenant_id) {
                return $store->page($resource, $tenant_id, $search, $length, $start, $order, $direction);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => $resource['columns'][0]['name'])
        );
    }

    public function new_record($slug = '')
    {
        if (in_array($slug, array('income', 'expenses'), TRUE)) {
            $this->finance_transaction_form($slug, NULL);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_form($slug, NULL);
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_form($slug, NULL);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_form($slug, NULL);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_form(NULL);
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_form(NULL);
            return;
        }
        $resource = $this->finance_resource($slug);
        if ($resource === NULL || !$this->finance_menu_allowed($resource)) {
            $this->finance_forbidden();
            return;
        }
        if (!$this->has_admin_access_code($resource['menu_code'] . '_add')) {
            $this->finance_forbidden();
            return;
        }
        $this->finance_form($resource, NULL);
    }

    public function edit($slug = '', $id = NULL)
    {
        if (in_array($slug, array('income', 'expenses'), TRUE)) {
            $this->finance_transaction_form($slug, $id);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_form($slug, $id);
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_form($slug, $id);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_form($slug, $id);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_form($id);
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_form($id);
            return;
        }
        $resource = $this->finance_resource($slug);
        if ($resource === NULL || !$this->finance_menu_allowed($resource)) {
            $this->finance_forbidden();
            return;
        }
        if (!$this->has_admin_access_code($resource['menu_code'] . '_update')
            || !is_numeric($id) || (int) $id < 1) {
            $this->finance_forbidden();
            return;
        }
        $this->finance_form($resource, (int) $id);
    }

    public function post($slug = '', $id = NULL)
    {
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_post($slug, $id);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_post($slug, $id);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_post($id);
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_complete($id);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_post($slug, $id);
            return;
        }
        if (!in_array($slug, array('income', 'expenses'), TRUE)
            || !is_numeric($id) || (int) $id < 1
            || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $menu_code = $slug === 'income' ? 'fin_income' : 'fin_expenses';
        if (!$this->has_admin_menu_code($menu_code) || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_transaction_store');
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->finance_transaction_store->post(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            (int) $id
        );
        $this->db->db_debug = $previous_db_debug;
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    public function lookup($slug = '', $kind = '')
    {
        if ($slug === 'bank_reconciliation') {
            if (!$this->has_admin_menu_code('fin_bank_reconciliation')) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            if ($kind === 'cash_account') {
                $this->load->library('finance_transfer_store');
                $rows = $this->finance_transfer_store->lookup_cash((int)$this->current_user['tenant_id'], $term);
            } elseif ($kind === 'journal_line') {
                $cash_id = $this->input->get('cash_account_id', TRUE);
                $this->load->library('finance_reconciliation_store');
                $rows = $this->finance_reconciliation_store->lookup_lines((int)$this->current_user['tenant_id'], (int)$cash_id, $term);
            } else {
                $this->output->set_status_header(404);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($rows, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if ($slug === 'cash_bank_transfer' && $kind === 'cash_account') {
            if (!$this->has_admin_menu_code('fin_cash_bank_transfer')) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $this->load->library('finance_transfer_store');
            $rows = $this->finance_transfer_store->lookup_cash(
                (int) $this->current_user['tenant_id'],
                is_string($term) && strlen($term) <= 120 ? $term : ''
            );
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($rows, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $menu_code = $this->finance_payment_menu_code($slug);
            if (!$this->has_admin_menu_code($menu_code) || !in_array($kind, array('cash_account', 'document'), TRUE)) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->load->library('finance_payment_store');
            $results = $this->finance_payment_store->lookup(
                (int) $this->current_user['tenant_id'],
                $kind === 'cash_account' ? 'cash_account' : ($slug === 'customer_payments' ? 'customer' : 'supplier'),
                $term
            );
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $menu_code = $this->finance_document_menu_code($slug);
            if (!$this->has_admin_menu_code($menu_code)) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $allowed = array('party', 'account', 'tax');
            if (!in_array($kind, $allowed, TRUE)) {
                $this->output->set_status_header(404);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->load->library('finance_document_store');
            $results = $this->finance_document_store->lookup(
                (int) $this->current_user['tenant_id'],
                $slug === 'sales_invoices' ? 'sales' : 'purchase',
                $kind,
                $term
            );
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if ($slug === 'financial_settings' && $kind === 'account') {
            if (!$this->has_admin_menu_code('fin_financial_settings')) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->load->library('finance_journal_store');
            $results = $this->finance_journal_store->lookup_accounts((int) $this->current_user['tenant_id'], $term);
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE) && $kind === 'account') {
            $menu_code = $this->finance_journal_menu_code($slug);
            if (!$this->has_admin_menu_code($menu_code)) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->load->library('finance_journal_store');
            $results = $this->finance_journal_store->lookup_accounts((int) $this->current_user['tenant_id'], $term);
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (!in_array($slug, array('income', 'expenses'), TRUE)) {
            show_404();
            return;
        }
        $menu_code = $slug === 'income' ? 'fin_income' : 'fin_expenses';
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
            return;
        }
        $term = $this->input->get('term', TRUE);
        if (!is_string($term) || strlen($term) > 120) {
            $term = '';
        }
        $this->load->library('finance_transaction_store');
        $results = $this->finance_transaction_store->lookup(
            (int) $this->current_user['tenant_id'],
            $kind,
            $term
        );
        $this->output->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    private function finance_transaction_index($slug)
    {
        $menu_code = $this->finance_transaction_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_transaction_store');
        $store = $this->finance_transaction_store;
        $this->render_admin(
            'admin/finance/transaction_index',
            'finance_' . $slug,
            '/admin/finance/' . $slug,
            array(
                'finance_transaction_slug' => $slug,
                'finance_transaction_available' => $store->available()
                    && ($slug !== 'all_transactions' || $store->all_available()),
                'finance_transaction_can_add' => $slug !== 'all_transactions'
                    && $this->has_admin_access_code($menu_code . '_add'),
                'finance_transaction_can_update' => $slug !== 'all_transactions'
                    && $this->has_admin_access_code($menu_code . '_update'),
                'finance_transaction_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
            )
        );
    }

    private function finance_transaction_data($slug)
    {
        $menu_code = $this->finance_transaction_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $this->load->library('finance_transaction_store');
        $store = $this->finance_transaction_store;
        if (!$store->available() || ($slug === 'all_transactions' && !$store->all_available())) {
            $this->datatable_server->error(503);
            return;
        }
        $type = $slug === 'income' ? 'income' : ($slug === 'expenses' ? 'expense' : 'all');
        $tenant_id = (int) $this->current_user['tenant_id'];
        $columns = array(
            1 => 'transaction_number',
            2 => 'transaction_type',
            3 => 'transaction_on',
            4 => 'total_amount',
            5 => 'currency_code',
            6 => 'status',
        );
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $tenant_id, $type) {
                return $store->count($tenant_id, $type);
            },
            function ($search) use ($store, $tenant_id, $type) {
                return $store->count($tenant_id, $type, $search);
            },
            function ($search, $length, $start, $order, $direction) use ($store, $tenant_id, $type) {
                return $store->page($tenant_id, $type, $search, $length, $start, $order, $direction);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'transaction_on')
        );
    }

    private function finance_transaction_form($slug, $id)
    {
        $menu_code = $this->finance_transaction_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $is_new = $id === NULL;
        $required_access = $menu_code . ($is_new ? '_add' : '_update');
        if (!$this->has_admin_access_code($required_access)
            || (!$is_new && (!is_numeric($id) || (int) $id < 1))) {
            $this->finance_forbidden();
            return;
        }

        $type = $slug === 'income' ? 'income' : 'expense';
        $this->load->library('finance_transaction_store');
        $store = $this->finance_transaction_store;
        $available = $store->available();
        $record = array(
            'transaction_on' => date('Y-m-d'),
            'transaction_type' => $type,
            'cash_account_id' => '',
            'account_id' => '',
            'category_id' => '',
            'tax_id' => '',
            'department_id' => '',
            'amount' => '',
            'exchange_rate' => '1',
            'description' => '',
            'status' => 'draft',
        );
        $lookup_labels = array();
        if (!$is_new && $available) {
            $saved = $store->find((int) $this->current_user['tenant_id'], (int) $id);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE || $saved['transaction_type'] !== $type) {
                $available = FALSE;
            } elseif ($saved['status'] !== 'draft') {
                show_error('Posted Finance transactions cannot be edited.', 409);
                return;
            } else {
                $record = array_merge($record, $saved);
                if (!empty($saved['line'])) {
                    $record['account_id'] = $saved['line']['account_id'];
                    $record['category_id'] = $saved['line']['category_id'];
                    $record['tax_id'] = $saved['line']['tax_id'];
                    $record['department_id'] = $saved['line']['department_id'];
                    $record['amount'] = $saved['line']['line_subtotal'];
                    $record['description'] = $saved['line']['description'];
                }
                $record['exchange_rate'] = $saved['exchange_rate'];
                $lookup_labels = $this->finance_transaction_lookup_labels($store, $record);
            }
        }

        $errors = array();
        $write_available = $available && $this->finance_write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->finance_transaction_values();
            $errors = $this->finance_transaction_validate($values);
            if (!$errors) {
                $previous_db_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft(
                    (int) $this->current_user['tenant_id'],
                    (int) $this->current_user['user_id'],
                    $type,
                    $is_new ? NULL : (int) $id,
                    $values
                );
                $this->db->db_debug = $previous_db_debug;
                if ($result === 'saved') {
                    $this->session->set_flashdata('finance_status', 'draft_saved');
                    redirect('/admin/finance/' . $slug, 'location', 303);
                    return;
                }
                $errors[] = 'transaction_' . $result;
            }
            $record = array_merge($record, $values);
            $lookup_labels = $this->finance_transaction_lookup_labels($store, $record);
        }

        $path = '/admin/finance/' . $slug . ($is_new ? '/new' : '/edit/' . (int) $id);
        $this->render_admin(
            'admin/finance/transaction_form',
            'finance_' . $slug,
            $path,
            array(
                'finance_transaction_slug' => $slug,
                'finance_transaction_type' => $type,
                'finance_transaction_record' => $record,
                'finance_transaction_id' => $is_new ? NULL : (int) $id,
                'finance_transaction_errors' => $errors,
                'finance_transaction_available' => $available,
                'finance_transaction_write_available' => $write_available,
                'finance_transaction_lookup_labels' => $lookup_labels,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_transaction_values()
    {
        $values = array();
        foreach (array('transaction_on', 'cash_account_id', 'account_id', 'category_id', 'tax_id', 'department_id', 'amount', 'exchange_rate', 'description') as $key) {
            $raw = $this->input->post($key, TRUE);
            $values[$key] = is_scalar($raw) ? trim((string) $raw) : '';
        }
        foreach (array('category_id', 'tax_id', 'department_id') as $optional_id) {
            $values[$optional_id] = $values[$optional_id] === '' ? NULL : (int) $values[$optional_id];
        }
        $values['cash_account_id'] = (int) $values['cash_account_id'];
        $values['account_id'] = (int) $values['account_id'];
        return $values;
    }

    private function finance_transaction_validate($values)
    {
        $errors = array();
        foreach (array('cash_account_id', 'account_id') as $id) {
            if ($values[$id] < 1) {
                $errors[] = $id . '_required';
            }
        }
        $date = DateTime::createFromFormat('!Y-m-d', $values['transaction_on']);
        if ($date === FALSE || $date->format('Y-m-d') !== $values['transaction_on']) {
            $errors[] = 'transaction_on_invalid';
        }
        if (!preg_match('/^\d{1,14}(?:\.\d{1,6})?$/', $values['amount']) || (float) $values['amount'] <= 0) {
            $errors[] = 'amount_invalid';
        }
        if (!preg_match('/^\d{1,10}(?:\.\d{1,10})?$/', $values['exchange_rate']) || (float) $values['exchange_rate'] <= 0) {
            $errors[] = 'exchange_rate_invalid';
        }
        if (strlen($values['description']) > 240) {
            $errors[] = 'description_too_long';
        }
        return array_values(array_unique($errors));
    }

    private function finance_transaction_lookup_labels($store, $record)
    {
        $types = array(
            'cash_account_id' => 'cash_account',
            'account_id' => isset($record['transaction_type']) && $record['transaction_type'] === 'income' ? 'revenue_account' : 'expense_account',
            'category_id' => isset($record['transaction_type']) && $record['transaction_type'] === 'income' ? 'income_category' : 'expense_category',
            'tax_id' => 'tax',
            'department_id' => 'department',
        );
        $labels = array();
        foreach ($types as $field => $kind) {
            $labels[$field] = !empty($record[$field])
                ? $store->lookup_label((int) $this->current_user['tenant_id'], $kind, $record[$field])
                : '';
        }
        return $labels;
    }

    private function finance_transaction_menu_code($slug)
    {
        if ($slug === 'all_transactions') {
            return 'fin_all_transactions';
        }
        return $slug === 'income' ? 'fin_income' : 'fin_expenses';
    }

    private function finance_journal_index($slug)
    {
        $menu_code = $this->finance_journal_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_journal_store');
        $this->render_admin(
            'admin/finance/journal_index',
            $slug === 'opening_balances' ? 'finance_opening_balances' : 'finance_journal_entries',
            '/admin/finance/' . $slug,
            array(
                'finance_journal_slug' => $slug,
                'finance_journal_available' => $this->finance_journal_store->available(),
                'finance_journal_can_add' => $this->has_admin_access_code($menu_code . '_add'),
                'finance_journal_can_update' => $this->has_admin_access_code($menu_code . '_update'),
                'finance_journal_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
            )
        );
    }

    private function finance_journal_data($slug)
    {
        $menu_code = $this->finance_journal_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $this->load->library('finance_journal_store');
        $store = $this->finance_journal_store;
        if (!$store->available()) {
            $this->datatable_server->error(503);
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $source_type = $slug === 'opening_balances' ? 'opening_balance' : 'manual';
        $this->datatable_server->respond(
            array(1 => 'journal_number', 2 => 'journal_date', 3 => 'description', 4 => 'debit_total', 5 => 'status'),
            function () use ($store, $tenant_id, $source_type) {
                return $store->count($tenant_id, $source_type);
            },
            function ($search) use ($store, $tenant_id, $source_type) {
                return $store->count($tenant_id, $source_type, $search);
            },
            function ($search, $length, $offset, $order, $direction) use ($store, $tenant_id, $source_type) {
                return $store->page($tenant_id, $source_type, $search, $length, $offset, $order, $direction);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'journal_date')
        );
    }

    private function finance_journal_post($slug, $id)
    {
        $menu_code = $this->finance_journal_menu_code($slug);
        if ($menu_code === NULL || !is_numeric($id) || (int) $id < 1
            || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code($menu_code) || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_journal_store');
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->finance_journal_store->post(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            (int) $id,
            $slug === 'opening_balances' ? 'opening_balance' : 'manual'
        );
        $this->db->db_debug = $previous_db_debug;
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    private function finance_journal_form($slug, $id)
    {
        $menu_code = $this->finance_journal_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $is_new = $id === NULL;
        if (!$this->has_admin_access_code($menu_code . ($is_new ? '_add' : '_update'))
            || (!$is_new && (!is_numeric($id) || (int) $id < 1))) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_journal_store');
        $store = $this->finance_journal_store;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $source_type = $slug === 'opening_balances' ? 'opening_balance' : 'manual';
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $record = array(
            'journal_date' => date('Y-m-d'),
            'description' => '',
            'lines' => array(
                array('account_id' => '', 'description' => '', 'currency_code' => $base_currency, 'exchange_rate' => '1', 'debit_amount' => '', 'credit_amount' => ''),
                array('account_id' => '', 'description' => '', 'currency_code' => $base_currency, 'exchange_rate' => '1', 'debit_amount' => '', 'credit_amount' => ''),
            ),
        );
        $account_labels = array();
        $available = $store->available();
        if (!$is_new && $available) {
            $saved = $store->find($tenant_id, (int) $id, $source_type);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE) {
                $available = FALSE;
            } elseif ($saved['status'] !== 'draft') {
                show_error('Posted journal entries cannot be edited.', 409);
                return;
            } else {
                $record = array_merge($record, $saved);
                foreach ($record['lines'] as $index => $line) {
                    $account_labels[$index] = !empty($line['account_id'])
                        ? $store->account_label($tenant_id, $line['account_id'])
                        : '';
                }
            }
        }
        $errors = array();
        $write_available = $available && $this->finance_write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->finance_journal_values($base_currency);
            $errors = $this->finance_journal_validate($values, $base_currency);
            if (!$errors) {
                $previous_db_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft(
                    $tenant_id,
                    (int) $this->current_user['user_id'],
                    $source_type,
                    $is_new ? NULL : (int) $id,
                    $values['journal_date'],
                    $values['description'],
                    $values['lines']
                );
                $this->db->db_debug = $previous_db_debug;
                if ($result === 'saved') {
                    $this->session->set_flashdata('finance_status', 'draft_saved');
                    redirect('/admin/finance/' . $slug, 'location', 303);
                    return;
                }
                $errors[] = 'journal_' . $result;
            }
            $record = array_merge($record, $values);
            foreach ($record['lines'] as $index => $line) {
                $account_labels[$index] = !empty($line['account_id']) ? $store->account_label($tenant_id, $line['account_id']) : '';
            }
        }
        $this->render_admin(
            'admin/finance/journal_form',
            $slug === 'opening_balances' ? 'finance_opening_balances' : 'finance_journal_entries',
            '/admin/finance/' . $slug . ($is_new ? '/new' : '/edit/' . (int) $id),
            array(
                'finance_journal_slug' => $slug,
                'finance_journal_record' => $record,
                'finance_journal_id' => $is_new ? NULL : (int) $id,
                'finance_journal_errors' => $errors,
                'finance_journal_available' => $available,
                'finance_journal_write_available' => $write_available,
                'finance_journal_base_currency' => $base_currency,
                'finance_journal_account_labels' => $account_labels,
                'finance_lookup_mode' => FALSE,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_journal_values($base_currency)
    {
        $date = $this->input->post('journal_date', TRUE);
        $description = $this->input->post('description', TRUE);
        $raw_lines = $this->input->post('lines', TRUE);
        $lines = array();
        if (is_array($raw_lines)) {
            foreach (array_slice($raw_lines, 0, 30) as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $read = function ($key) use ($line) {
                    return isset($line[$key]) && is_scalar($line[$key]) ? trim((string) $line[$key]) : '';
                };
                if ($read('account_id') === '' && $read('debit_amount') === '' && $read('credit_amount') === '') {
                    continue;
                }
                $lines[] = array(
                    'account_id' => (int) $read('account_id'),
                    'description' => $read('description'),
                    'currency_code' => strtoupper($read('currency_code')),
                    'exchange_rate' => $read('exchange_rate') === '' ? '1' : $read('exchange_rate'),
                    'debit_amount' => $read('debit_amount') === '' ? '0' : $read('debit_amount'),
                    'credit_amount' => $read('credit_amount') === '' ? '0' : $read('credit_amount'),
                );
            }
        }
        return array(
            'journal_date' => is_string($date) ? trim($date) : '',
            'description' => is_string($description) ? trim($description) : '',
            'lines' => $lines,
        );
    }

    private function finance_journal_validate($values, $base_currency)
    {
        $errors = array();
        $date = DateTime::createFromFormat('!Y-m-d', $values['journal_date']);
        if ($date === FALSE || $date->format('Y-m-d') !== $values['journal_date']) {
            $errors[] = 'journal_date_invalid';
        }
        if ($values['description'] === '' || strlen($values['description']) > 240) {
            $errors[] = 'description_invalid';
        }
        if (count($values['lines']) < 2) {
            $errors[] = 'journal_lines_required';
        }
        foreach ($values['lines'] as $index => $line) {
            $debit = $line['debit_amount'];
            $credit = $line['credit_amount'];
            if ((int) $line['account_id'] < 1 || !preg_match('/^[A-Z]{3}$/', $line['currency_code'])
                || !preg_match('/^\d{1,10}(?:\.\d{1,10})?$/', $line['exchange_rate'])
                || (float) $line['exchange_rate'] <= 0
                || !preg_match('/^\d{1,14}(?:\.\d{1,6})?$/', $debit)
                || !preg_match('/^\d{1,14}(?:\.\d{1,6})?$/', $credit)
                || (((float) $debit > 0) === ((float) $credit > 0))) {
                $errors[] = 'journal_line_' . ($index + 1) . '_invalid';
            }
            if ($line['currency_code'] === strtoupper($base_currency)
                && abs((float) $line['exchange_rate'] - 1) > 0.0000001) {
                $errors[] = 'journal_line_' . ($index + 1) . '_base_rate_invalid';
            }
        }
        return array_values(array_unique($errors));
    }

    private function finance_journal_menu_code($slug)
    {
        return $slug === 'journal_entries' ? 'fin_journal_entries'
            : ($slug === 'opening_balances' ? 'fin_opening_balances' : NULL);
    }

    private function finance_document_index($slug)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'sales_invoices' ? 'sales' : 'purchase';
        $this->load->library('finance_document_store');
        $this->render_admin(
            'admin/finance/document_index',
            'finance_' . $slug,
            '/admin/finance/' . $slug,
            array(
                'finance_document_slug' => $slug,
                'finance_document_kind' => $kind,
                'finance_document_available' => $this->finance_document_store->available($kind),
                'finance_document_can_add' => $this->has_admin_access_code($menu_code . '_add'),
                'finance_document_can_update' => $this->has_admin_access_code($menu_code . '_update'),
                'finance_document_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
            )
        );
    }

    private function finance_document_data($slug)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $kind = $slug === 'sales_invoices' ? 'sales' : 'purchase';
        $this->load->library('finance_document_store');
        $store = $this->finance_document_store;
        if (!$store->available($kind)) {
            $this->datatable_server->error(503);
            return;
        }
        $spec = $kind === 'sales'
            ? array('invoice_number', 'invoice_date', 'due_date')
            : array('bill_number', 'bill_date', 'due_date');
        $number_column = $spec[0];
        $date_column = $spec[1];
        $columns = array(1 => $number_column, 2 => $date_column, 3 => 'due_date', 4 => 'total_amount', 5 => 'currency_code', 6 => 'status');
        $tenant_id = (int) $this->current_user['tenant_id'];
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $tenant_id, $kind) { return $store->count($tenant_id, $kind); },
            function ($search) use ($store, $tenant_id, $kind) { return $store->count($tenant_id, $kind, $search); },
            function ($search, $length, $offset, $order, $direction) use ($store, $tenant_id, $kind) {
                return $store->page($tenant_id, $kind, $search, $length, $offset, $order, $direction);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => $date_column)
        );
    }

    private function finance_document_post($slug, $id)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        if ($menu_code === NULL || !is_numeric($id) || (int) $id < 1 || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code($menu_code) || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_document_store');
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->finance_document_store->post(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            $slug === 'sales_invoices' ? 'sales' : 'purchase',
            (int) $id
        );
        $this->db->db_debug = $previous_db_debug;
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    private function finance_document_form($slug, $id)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'sales_invoices' ? 'sales' : 'purchase';
        $is_new = $id === NULL;
        if (!$this->has_admin_access_code($menu_code . ($is_new ? '_add' : '_update'))
            || (!$is_new && (!is_numeric($id) || (int) $id < 1))) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_document_store');
        $store = $this->finance_document_store;
        $available = $store->available($kind);
        $record = array(
            'document_date' => date('Y-m-d'), 'due_date' => '', 'person_id' => '',
            'account_id' => '', 'tax_id' => '', 'department_id' => '',
            'currency_code' => '', 'exchange_rate' => '1', 'amount' => '', 'description' => '', 'memo' => '',
        );
        $labels = array();
        if (!$is_new && $available) {
            $saved = $store->find((int) $this->current_user['tenant_id'], $kind, (int) $id);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE) {
                $available = FALSE;
            } elseif ($saved['status'] !== 'draft') {
                show_error('Posted Finance documents cannot be edited.', 409);
                return;
            } else {
                $spec = $kind === 'sales'
                    ? array('number' => 'invoice_number', 'date' => 'invoice_date')
                    : array('number' => 'bill_number', 'date' => 'bill_date');
                $record = array_merge($record, $saved);
                $record['document_date'] = $saved[$spec['date']];
                if (!empty($saved['line'])) {
                    $record['account_id'] = $saved['line']['account_id'];
                    $record['tax_id'] = $saved['line']['tax_id'];
                    $record['department_id'] = $saved['line']['department_id'];
                    $record['amount'] = $saved['line']['line_subtotal'];
                    $record['description'] = $saved['line']['description'];
                }
                foreach (array('party', 'account', 'tax') as $lookup_kind) {
                    $field = $lookup_kind === 'party' ? 'person_id' : ($lookup_kind === 'account' ? 'account_id' : 'tax_id');
                    $labels[$field] = !empty($record[$field])
                        ? $store->lookup_label((int) $this->current_user['tenant_id'], $kind, $lookup_kind, $record[$field])
                        : '';
                }
            }
        }
        $errors = array();
        $write_available = $available && $this->finance_write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->finance_document_values();
            $errors = $this->finance_document_validate($values);
            if (!$errors) {
                $previous_db_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft(
                    (int) $this->current_user['tenant_id'],
                    (int) $this->current_user['user_id'],
                    $kind,
                    $is_new ? NULL : (int) $id,
                    $values
                );
                $this->db->db_debug = $previous_db_debug;
                if ($result === 'saved') {
                    $this->session->set_flashdata('finance_status', 'draft_saved');
                    redirect('/admin/finance/' . $slug, 'location', 303);
                    return;
                }
                $errors[] = 'document_' . $result;
            }
            $record = array_merge($record, $values);
            foreach (array('party', 'account', 'tax') as $lookup_kind) {
                $field = $lookup_kind === 'party' ? 'person_id' : ($lookup_kind === 'account' ? 'account_id' : 'tax_id');
                $labels[$field] = !empty($record[$field])
                    ? $store->lookup_label((int) $this->current_user['tenant_id'], $kind, $lookup_kind, $record[$field])
                    : '';
            }
        }
        $this->render_admin(
            'admin/finance/document_form',
            'finance_' . $slug,
            '/admin/finance/' . $slug . ($is_new ? '/new' : '/edit/' . (int) $id),
            array(
                'finance_document_slug' => $slug,
                'finance_document_kind' => $kind,
                'finance_document_record' => $record,
                'finance_document_id' => $is_new ? NULL : (int) $id,
                'finance_document_errors' => $errors,
                'finance_document_available' => $available,
                'finance_document_write_available' => $write_available,
                'finance_document_lookup_labels' => $labels,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_document_values()
    {
        $values = array();
        foreach (array('document_date', 'due_date', 'person_id', 'account_id', 'tax_id', 'department_id', 'currency_code', 'exchange_rate', 'amount', 'description', 'memo') as $name) {
            $raw = $this->input->post($name, TRUE);
            $values[$name] = is_scalar($raw) ? trim((string) $raw) : '';
        }
        foreach (array('tax_id', 'department_id') as $optional) {
            $values[$optional] = $values[$optional] === '' ? NULL : (int) $values[$optional];
        }
        $values['person_id'] = (int) $values['person_id'];
        $values['account_id'] = (int) $values['account_id'];
        $values['currency_code'] = strtoupper($values['currency_code']);
        return $values;
    }

    private function finance_document_validate($values)
    {
        $errors = array();
        foreach (array('person_id', 'account_id') as $id) {
            if ($values[$id] < 1) {
                $errors[] = $id . '_required';
            }
        }
        foreach (array('document_date', 'due_date') as $date_key) {
            if ($values[$date_key] === '' && $date_key === 'due_date') {
                continue;
            }
            $date = DateTime::createFromFormat('!Y-m-d', $values[$date_key]);
            if ($date === FALSE || $date->format('Y-m-d') !== $values[$date_key]) {
                $errors[] = $date_key . '_invalid';
            }
        }
        if ($values['due_date'] !== '' && $values['due_date'] < $values['document_date']) {
            $errors[] = 'due_date_invalid';
        }
        if (!preg_match('/^[A-Z]{3}$/', $values['currency_code'])) {
            $errors[] = 'currency_invalid';
        }
        if (!preg_match('/^\d{1,10}(?:\.\d{1,10})?$/', $values['exchange_rate']) || (float) $values['exchange_rate'] <= 0) {
            $errors[] = 'exchange_rate_invalid';
        }
        if (!preg_match('/^\d{1,14}(?:\.\d{1,6})?$/', $values['amount']) || (float) $values['amount'] <= 0) {
            $errors[] = 'amount_invalid';
        }
        if (strlen($values['description']) > 240 || strlen($values['memo']) > 10000) {
            $errors[] = 'text_too_long';
        }
        return array_values(array_unique($errors));
    }

    private function finance_document_menu_code($slug)
    {
        return $slug === 'sales_invoices' ? 'fin_sales_invoices'
            : ($slug === 'purchase_bills' ? 'fin_purchase_bills' : NULL);
    }

    private function finance_payment_index($slug)
    {
        $menu_code = $this->finance_payment_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'customer_payments' ? 'customer' : 'supplier';
        $this->load->library('finance_payment_store');
        $this->render_admin('admin/finance/payment_index', 'finance_' . $slug, '/admin/finance/' . $slug, array(
            'finance_payment_slug' => $slug,
            'finance_payment_kind' => $kind,
            'finance_payment_available' => $this->finance_payment_store->available($kind),
            'finance_payment_can_add' => $this->has_admin_access_code($menu_code . '_add'),
            'finance_payment_can_update' => $this->has_admin_access_code($menu_code . '_update'),
            'finance_payment_write_available' => $this->finance_write_available(),
            'finance_status' => (string) $this->session->flashdata('finance_status'),
        ));
    }

    private function finance_payment_data($slug)
    {
        $menu_code = $this->finance_payment_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $kind = $slug === 'customer_payments' ? 'customer' : 'supplier';
        $this->load->library('finance_payment_store');
        $store = $this->finance_payment_store;
        if (!$store->available($kind)) {
            $this->datatable_server->error(503);
            return;
        }
        $tenant = (int) $this->current_user['tenant_id'];
        $this->datatable_server->respond(
            array(1 => 'payment_number', 2 => 'payment_date', 3 => 'amount', 4 => 'currency_code', 5 => 'reference', 6 => 'status'),
            function () use ($store, $tenant, $kind) { return $store->count($tenant, $kind); },
            function ($search) use ($store, $tenant, $kind) { return $store->count($tenant, $kind, $search); },
            function ($search, $length, $offset, $order, $direction) use ($store, $tenant, $kind) {
                return $store->page($tenant, $kind, $search, $length, $offset, $order, $direction);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'payment_date')
        );
    }

    private function finance_payment_form($slug, $id)
    {
        $menu_code = $this->finance_payment_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'customer_payments' ? 'customer' : 'supplier';
        $is_new = $id === NULL;
        if (!$this->has_admin_access_code($menu_code . ($is_new ? '_add' : '_update'))
            || (!$is_new && (!is_numeric($id) || (int) $id < 1))) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_payment_store');
        $store = $this->finance_payment_store;
        $available = $store->available($kind);
        $record = array('payment_date' => date('Y-m-d'), 'document_id' => '', 'cash_account_id' => '', 'amount' => '', 'exchange_rate' => '1', 'reference' => '', 'status' => 'draft');
        $labels = array('document_id' => '', 'cash_account_id' => '');
        $tenant = (int) $this->current_user['tenant_id'];
        if (!$is_new && $available) {
            $saved = $store->find($tenant, $kind, (int) $id);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE) {
                $available = FALSE;
            } elseif ($saved['status'] !== 'draft') {
                show_error('Posted payments cannot be edited.', 409);
                return;
            } else {
                $record = array_merge($record, $saved);
                if (!empty($saved['allocation'])) $record['document_id'] = $saved['allocation'][$kind === 'customer' ? 'sales_invoice_id' : 'purchase_bill_id'];
                $labels['document_id'] = !empty($record['document_id']) ? $store->lookup_label($tenant, $kind, $record['document_id']) : '';
                $labels['cash_account_id'] = !empty($record['cash_account_id']) ? $store->lookup_label($tenant, 'cash_account', $record['cash_account_id']) : '';
            }
        }
        $errors = array();
        $write_available = $available && $this->finance_write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = array();
            foreach (array('payment_date','document_id','cash_account_id','amount','exchange_rate','reference') as $key) {
                $raw = $this->input->post($key, TRUE);
                $values[$key] = is_scalar($raw) ? trim((string) $raw) : '';
            }
            $values['document_id'] = (int) $values['document_id'];
            $values['cash_account_id'] = (int) $values['cash_account_id'];
            $errors = $this->finance_payment_validate($values);
            if (!$errors) {
                $previous = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft($tenant, (int) $this->current_user['user_id'], $kind, $is_new ? NULL : (int) $id, $values);
                $this->db->db_debug = $previous;
                if ($result === 'saved') {
                    $this->session->set_flashdata('finance_status', 'draft_saved');
                    redirect('/admin/finance/' . $slug, 'location', 303);
                    return;
                }
                $errors[] = 'payment_' . $result;
            }
            $record = array_merge($record, $values);
            $labels['document_id'] = !empty($record['document_id']) ? $store->lookup_label($tenant, $kind, $record['document_id']) : '';
            $labels['cash_account_id'] = !empty($record['cash_account_id']) ? $store->lookup_label($tenant, 'cash_account', $record['cash_account_id']) : '';
        }
        $this->render_admin('admin/finance/payment_form', 'finance_' . $slug,
            '/admin/finance/' . $slug . ($is_new ? '/new' : '/edit/' . (int) $id), array(
                'finance_payment_slug' => $slug,
                'finance_payment_kind' => $kind,
                'finance_payment_record' => $record,
                'finance_payment_id' => $is_new ? NULL : (int) $id,
                'finance_payment_labels' => $labels,
                'finance_payment_errors' => $errors,
                'finance_payment_available' => $available,
                'finance_payment_write_available' => $write_available,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_payment_post($slug, $id)
    {
        $menu_code = $this->finance_payment_menu_code($slug);
        if ($menu_code === NULL || !is_numeric($id) || (int) $id < 1 || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code($menu_code) || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_payment_store');
        $previous = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->finance_payment_store->post(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            $slug === 'customer_payments' ? 'customer' : 'supplier',
            (int) $id
        );
        $this->db->db_debug = $previous;
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    private function finance_payment_validate($values)
    {
        $errors = array();
        foreach (array('document_id','cash_account_id') as $id) if ($values[$id] < 1) $errors[] = $id . '_required';
        $date = DateTime::createFromFormat('!Y-m-d', $values['payment_date']);
        if ($date === FALSE || $date->format('Y-m-d') !== $values['payment_date']) $errors[] = 'payment_date_invalid';
        if (!preg_match('/^\d{1,14}(?:\.\d{1,6})?$/', $values['amount']) || (float) $values['amount'] <= 0) $errors[] = 'amount_invalid';
        if (!preg_match('/^\d{1,10}(?:\.\d{1,10})?$/', $values['exchange_rate']) || (float) $values['exchange_rate'] <= 0) $errors[] = 'exchange_rate_invalid';
        if (strlen($values['reference']) > 80) $errors[] = 'reference_too_long';
        return array_values(array_unique($errors));
    }

    private function finance_payment_menu_code($slug)
    {
        return $slug === 'customer_payments' ? 'fin_customer_payments'
            : ($slug === 'supplier_payments' ? 'fin_supplier_payments' : NULL);
    }

    private function finance_transfer_index()
    {
        $menu = 'fin_cash_bank_transfer';
        if (!$this->has_admin_menu_code($menu)) { $this->finance_forbidden(); return; }
        $this->load->library('finance_transfer_store');
        $this->render_admin('admin/finance/transfer_index','finance_cash_bank_transfer','/admin/finance/cash_bank_transfer',array(
            'finance_transfer_available'=>$this->finance_transfer_store->available(),
            'finance_transfer_can_add'=>$this->has_admin_access_code($menu.'_add'),
            'finance_transfer_can_update'=>$this->has_admin_access_code($menu.'_update'),
            'finance_transfer_write_available'=>$this->finance_write_available(),
            'finance_status'=>(string)$this->session->flashdata('finance_status'),
        ));
    }

    private function finance_transfer_data()
    {
        $this->load->library('datatable_server');
        if(!$this->has_admin_menu_code('fin_cash_bank_transfer')){$this->datatable_server->error(403);return;}
        $this->load->library('finance_transfer_store');$store=$this->finance_transfer_store;
        if(!$store->available()){$this->datatable_server->error(503);return;}
        $tenant=(int)$this->current_user['tenant_id'];
        $this->datatable_server->respond(
            array(1=>'transfer_number',2=>'transfer_date',3=>'from_amount',4=>'to_amount',5=>'status'),
            function()use($store,$tenant){return $store->count($tenant);},
            function($search)use($store,$tenant){return $store->count($tenant,$search);},
            function($search,$length,$offset,$order,$direction)use($store,$tenant){return $store->page($tenant,$search,$length,$offset,$order,$direction);},
            array('max_length'=>50,'max_search_length'=>120,'default_order'=>'transfer_date')
        );
    }

    private function finance_transfer_form($id)
    {
        $menu='fin_cash_bank_transfer';$is_new=$id===NULL;
        if(!$this->has_admin_menu_code($menu)||!$this->has_admin_access_code($menu.($is_new?'_add':'_update'))){$this->finance_forbidden();return;}
        $this->load->library('finance_transfer_store');$store=$this->finance_transfer_store;
        $record=array('transfer_date'=>date('Y-m-d'),'from_cash_account_id'=>'','to_cash_account_id'=>'','from_amount'=>'','to_amount'=>'','from_exchange_rate'=>'1','to_exchange_rate'=>'1','memo'=>'','status'=>'draft');
        $labels=array('from_cash_account_id'=>'','to_cash_account_id'=>'');$available=$store->available();
        if(!$is_new&&$available){$saved=$store->find((int)$this->current_user['tenant_id'],(int)$id);if($saved===NULL){show_404();return;}if($saved===FALSE){$available=FALSE;}elseif($saved['status']!=='draft'){show_error('Posted transfers cannot be edited.',409);return;}else{$record=array_merge($record,$saved);foreach(array('from_cash_account_id','to_cash_account_id') as $f)$labels[$f]=$store->cash_label((int)$this->current_user['tenant_id'],$record[$f]);}}
        $errors=array();$write=$available&&$this->finance_write_available();
        if($this->input->method(TRUE)==='POST'){
            if(!$write){show_error('Finance writes are unavailable.',503);return;}
            $values=array();foreach(array('transfer_date','from_cash_account_id','to_cash_account_id','from_amount','to_amount','from_exchange_rate','to_exchange_rate','memo') as $key){$raw=$this->input->post($key,TRUE);$values[$key]=is_scalar($raw)?trim((string)$raw):'';}
            $values['from_cash_account_id']=(int)$values['from_cash_account_id'];$values['to_cash_account_id']=(int)$values['to_cash_account_id'];
            $date=DateTime::createFromFormat('!Y-m-d',$values['transfer_date']);
            if($date===FALSE||$date->format('Y-m-d')!==$values['transfer_date'])$errors[]='transfer_date_invalid';
            foreach(array('from_amount','to_amount') as $a)if(!preg_match('/^\d{1,14}(?:\.\d{1,6})?$/',$values[$a])||(float)$values[$a]<=0)$errors[]=$a.'_invalid';
            foreach(array('from_exchange_rate','to_exchange_rate') as $r)if(!preg_match('/^\d{1,10}(?:\.\d{1,10})?$/',$values[$r])||(float)$values[$r]<=0)$errors[]=$r.'_invalid';
            if(!$values['from_cash_account_id']||!$values['to_cash_account_id'])$errors[]='cash_account_required';
            if(strlen($values['memo'])>10000)$errors[]='memo_too_long';
            if(!$errors){$previous=$this->db->db_debug;$this->db->db_debug=FALSE;$result=$store->save_draft((int)$this->current_user['tenant_id'],(int)$this->current_user['user_id'],$is_new?NULL:(int)$id,$values);$this->db->db_debug=$previous;
                if($result==='saved'){$this->session->set_flashdata('finance_status','draft_saved');redirect('/admin/finance/cash_bank_transfer','location',303);return;}
                $errors[]='transfer_'.$result;
            }
            $record=array_merge($record,$values);foreach(array('from_cash_account_id','to_cash_account_id') as $f)$labels[$f]=$store->cash_label((int)$this->current_user['tenant_id'],$record[$f]);
        }
        $this->render_admin('admin/finance/transfer_form','finance_cash_bank_transfer','/admin/finance/cash_bank_transfer'.($is_new?'/new':'/edit/'.(int)$id),array(
            'finance_transfer_record'=>$record,'finance_transfer_id'=>$is_new?NULL:(int)$id,'finance_transfer_labels'=>$labels,
            'finance_transfer_errors'=>$errors,'finance_transfer_available'=>$available,'finance_transfer_write_available'=>$write,
            'finance_csrf_name'=>$this->security->get_csrf_token_name(),'finance_csrf_hash'=>$this->security->get_csrf_hash(),
        ));
    }

    private function finance_transfer_post($id)
    {
        if(!is_numeric($id)||(int)$id<1||$this->input->method(TRUE)!=='POST'){show_404();return;}
        if(!$this->has_admin_menu_code('fin_cash_bank_transfer')||!$this->has_admin_access_code('fin_cash_bank_transfer_update')){$this->finance_forbidden();return;}
        $this->load->library('finance_transfer_store');$previous=$this->db->db_debug;$this->db->db_debug=FALSE;
        $result=$this->finance_transfer_store->post((int)$this->current_user['tenant_id'],(int)$this->current_user['user_id'],(int)$id);
        $this->db->db_debug=$previous;$this->session->set_flashdata('finance_status',$result);
        redirect('/admin/finance/cash_bank_transfer','location',303);
    }

    private function finance_reconciliation_index()
    {
        $menu='fin_bank_reconciliation';
        if(!$this->has_admin_menu_code($menu)){$this->finance_forbidden();return;}
        $this->load->library('finance_reconciliation_store');$store=$this->finance_reconciliation_store;
        $this->render_admin('admin/finance/reconciliation_index','finance_bank_reconciliation','/admin/finance/bank_reconciliation',array(
            'finance_reconciliation_available'=>$store->available(),
            'finance_reconciliation_can_add'=>$this->has_admin_access_code($menu.'_add'),
            'finance_reconciliation_can_update'=>$this->has_admin_access_code($menu.'_update'),
            'finance_reconciliation_write_available'=>$this->finance_write_available(),
            'finance_status'=>(string)$this->session->flashdata('finance_status'),
        ));
    }

    private function finance_reconciliation_data()
    {
        $this->load->library('datatable_server');
        if(!$this->has_admin_menu_code('fin_bank_reconciliation')){$this->datatable_server->error(403);return;}
        $this->load->library('finance_reconciliation_store');$store=$this->finance_reconciliation_store;
        if(!$store->available()){$this->datatable_server->error(503);return;}
        $tenant=(int)$this->current_user['tenant_id'];
        $this->datatable_server->respond(
            array(1=>'statement_date',2=>'statement_reference',3=>'closing_balance',4=>'status'),
            function()use($store,$tenant){return $store->count($tenant);},
            function($search)use($store,$tenant){return $store->count($tenant,$search);},
            function($search,$length,$offset,$order,$direction)use($store,$tenant){return $store->page($tenant,$search,$length,$offset,$order,$direction);},
            array('max_length'=>50,'max_search_length'=>120,'default_order'=>'statement_date')
        );
    }

    private function finance_reconciliation_form($id)
    {
        $menu='fin_bank_reconciliation';$is_new=$id===NULL;
        if(!$this->has_admin_menu_code($menu)||!$this->has_admin_access_code($menu.($is_new?'_add':'_update'))){$this->finance_forbidden();return;}
        $this->load->library('finance_reconciliation_store');$store=$this->finance_reconciliation_store;
        $record=array('cash_account_id'=>'','statement_date'=>date('Y-m-d'),'statement_reference'=>'','opening_balance'=>'','closing_balance'=>'','lines'=>array());
        $labels=array('cash_account_id'=>'','lines'=>array());$available=$store->available();
        if(!$is_new&&$available){
            $saved=$store->find((int)$this->current_user['tenant_id'],(int)$id);
            if($saved===NULL){show_404();return;}
            if($saved===FALSE){$available=FALSE;}
            elseif($saved['status']!=='draft'){show_error('Completed reconciliations cannot be edited.',409);return;}
            else{$record=array_merge($record,$saved);$this->load->library('finance_transfer_store');$labels['cash_account_id']=$this->finance_transfer_store->cash_label((int)$this->current_user['tenant_id'],$record['cash_account_id']);foreach($record['lines'] as $i=>$line)$labels['lines'][$i]=$store->line_label((int)$this->current_user['tenant_id'],$line['journal_line_id']);}
        }
        $errors=array();$write=$available&&$this->finance_write_available();
        if($this->input->method(TRUE)==='POST'){
            if(!$write){show_error('Finance writes are unavailable.',503);return;}
            $raw=array();foreach(array('cash_account_id','statement_date','statement_reference','opening_balance','closing_balance') as $key){$v=$this->input->post($key,TRUE);$raw[$key]=is_scalar($v)?trim((string)$v):'';}
            $raw['cash_account_id']=(int)$raw['cash_account_id'];$lines=$this->input->post('lines',TRUE);$clean=array();
            if(is_array($lines))foreach(array_slice($lines,0,100) as $line){if(!is_array($line))continue;$read=function($k)use($line){return isset($line[$k])&&is_scalar($line[$k])?trim((string)$line[$k]):'';};if($read('journal_line_id')==='')continue;$clean[]=array('journal_line_id'=>(int)$read('journal_line_id'),'statement_on'=>$read('statement_on'),'description'=>$read('description'),'statement_amount'=>$read('statement_amount'));}
            $d=DateTime::createFromFormat('!Y-m-d',$raw['statement_date']);
            if(!$raw['cash_account_id'])$errors[]='cash_account_required';
            if($d===FALSE||$d->format('Y-m-d')!==$raw['statement_date'])$errors[]='statement_date_invalid';
            foreach(array('opening_balance','closing_balance') as $k)if(!preg_match('/^-?\d{1,14}(?:\.\d{1,6})?$/',$raw[$k]))$errors[]=$k.'_invalid';
            if($raw['statement_reference']===''||strlen($raw['statement_reference'])>80)$errors[]='statement_reference_invalid';
            if(count($clean)<1)$errors[]='statement_lines_required';
            foreach($clean as $line){$ld=DateTime::createFromFormat('!Y-m-d',$line['statement_on']);if($ld===FALSE||$ld->format('Y-m-d')!==$line['statement_on']||$line['statement_on']>$raw['statement_date'])$errors[]='statement_line_date_invalid';if(!preg_match('/^-?\d{1,14}(?:\.\d{1,6})?$/',$line['statement_amount']))$errors[]='statement_line_amount_invalid';}
            $values=array_merge($raw,array('lines'=>$clean));
            if(!$errors){$previous=$this->db->db_debug;$this->db->db_debug=FALSE;$result=$store->save_draft((int)$this->current_user['tenant_id'],(int)$this->current_user['user_id'],$is_new?NULL:(int)$id,$values);$this->db->db_debug=$previous;if($result==='saved'){$this->session->set_flashdata('finance_status','draft_saved');redirect('/admin/finance/bank_reconciliation','location',303);return;}$errors[]='reconciliation_'.$result;}
            $record=array_merge($record,$values);$this->load->library('finance_transfer_store');$labels['cash_account_id']=$store->cash_label((int)$this->current_user['tenant_id'],$record['cash_account_id']);foreach($record['lines'] as $i=>$line)$labels['lines'][$i]=$store->line_label((int)$this->current_user['tenant_id'],$line['journal_line_id']);
        }
        $this->render_admin('admin/finance/reconciliation_form','finance_bank_reconciliation','/admin/finance/bank_reconciliation'.($is_new?'/new':'/edit/'.(int)$id),array(
            'finance_reconciliation_record'=>$record,'finance_reconciliation_id'=>$is_new?NULL:(int)$id,'finance_reconciliation_labels'=>$labels,
            'finance_reconciliation_errors'=>$errors,'finance_reconciliation_available'=>$available,'finance_reconciliation_write_available'=>$write,
            'finance_csrf_name'=>$this->security->get_csrf_token_name(),'finance_csrf_hash'=>$this->security->get_csrf_hash(),
        ));
    }

    private function finance_reconciliation_complete($id)
    {
        if(!is_numeric($id)||(int)$id<1||$this->input->method(TRUE)!=='POST'){show_404();return;}
        if(!$this->has_admin_menu_code('fin_bank_reconciliation')||!$this->has_admin_access_code('fin_bank_reconciliation_update')){$this->finance_forbidden();return;}
        $this->load->library('finance_reconciliation_store');$previous=$this->db->db_debug;$this->db->db_debug=FALSE;
        $result=$this->finance_reconciliation_store->complete((int)$this->current_user['tenant_id'],(int)$this->current_user['user_id'],(int)$id);
        $this->db->db_debug=$previous;$this->session->set_flashdata('finance_status',$result);
        redirect('/admin/finance/bank_reconciliation','location',303);
    }

    private function finance_report_index($slug)
    {
        $menu_code = $this->finance_report_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $filters = $this->finance_report_dates();
        $this->load->library('finance_report_store');
        $columns = $this->finance_report_columns($slug);
        foreach ($columns as &$column) {
            $column['label_key'] = 'finance_report_column_' . $column['name'];
        }
        unset($column);
        $title_key = 'finance_' . $slug;
        $this->render_admin(
            'admin/finance/report_index',
            $title_key,
            '/admin/finance/' . $slug,
            array(
                'finance_report_slug' => $slug,
                'finance_report_columns' => $columns,
                'finance_report_filters' => $filters,
                'finance_report_errors' => isset($filters['errors']) ? $filters['errors'] : array(),
                'finance_report_available' => $this->finance_report_store->available($slug),
            )
        );
    }

    private function finance_dashboard()
    {
        if (!$this->has_admin_menu_code('fin_dashboard')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_report_store');
        $summary = $this->finance_report_store->dashboard((int) $this->current_user['tenant_id']);
        $this->render_admin(
            'admin/finance/dashboard',
            'finance_dashboard',
            '/admin/finance/dashboard',
            array('finance_dashboard_data' => $summary)
        );
    }

    private function finance_report_data($slug)
    {
        $menu_code = $this->finance_report_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $filters = $this->finance_report_dates();
        if (!empty($filters['errors'])) {
            $this->datatable_server->error(400);
            return;
        }
        $this->load->library('finance_report_store');
        $store = $this->finance_report_store;
        if (!$store->available($slug)) {
            $this->datatable_server->error(503);
            return;
        }
        $columns = array();
        foreach ($this->finance_report_columns($slug) as $index => $column) {
            $columns[$index] = $column['name'];
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $start = $filters['start_on'];
        $end = $filters['end_on'];
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $slug, $tenant_id, $start, $end) {
                return $store->count($slug, $tenant_id, $start, $end);
            },
            function ($search) use ($store, $slug, $tenant_id, $start, $end) {
                return $store->count($slug, $tenant_id, $start, $end, $search);
            },
            function ($search, $length, $offset, $order, $direction) use ($store, $slug, $tenant_id, $start, $end) {
                return $store->page($slug, $tenant_id, $start, $end, $search, $length, $offset, $order, $direction);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => $this->finance_report_columns($slug)[0]['name'])
        );
    }

    private function finance_report_dates()
    {
        $today = date('Y-m-d');
        $default_start = date('Y-01-01');
        $start = $this->input->get('start_on', TRUE);
        $end = $this->input->get('end_on', TRUE);
        $start = is_string($start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) ? $start : $default_start;
        $end = is_string($end) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) ? $end : $today;
        $errors = array();
        foreach (array($start, $end) as $date_value) {
            $date = DateTime::createFromFormat('!Y-m-d', $date_value);
            if ($date === FALSE || $date->format('Y-m-d') !== $date_value) {
                $errors[] = 'invalid_date';
            }
        }
        if ($start > $end) {
            $errors[] = 'date_range_invalid';
        }
        return array('start_on' => $start, 'end_on' => $end, 'errors' => array_values(array_unique($errors)));
    }

    private function finance_report_menu_code($slug)
    {
        $codes = array(
            'profit_loss' => 'fin_profit_loss',
            'balance_sheet' => 'fin_balance_sheet',
            'cash_flow' => 'fin_cash_flow',
            'trial_balance' => 'fin_trial_balance',
            'ar_report' => 'fin_ar_report',
            'ap_report' => 'fin_ap_report',
            'general_ledger' => 'fin_general_ledger',
        );
        return isset($codes[$slug]) ? $codes[$slug] : NULL;
    }

    private function finance_report_columns($slug)
    {
        $columns = array(
            'general_ledger' => array(
                array('name' => 'journal_date', 'label' => 'Date'),
                array('name' => 'journal_number', 'label' => 'Journal'),
                array('name' => 'account_code', 'label' => 'Account'),
                array('name' => 'account_name', 'label' => 'Account name'),
                array('name' => 'description', 'label' => 'Description'),
                array('name' => 'currency_code', 'label' => 'Currency'),
                array('name' => 'debit_base_amount', 'label' => 'Debit (base)'),
                array('name' => 'credit_base_amount', 'label' => 'Credit (base)'),
            ),
            'cash_flow' => array(
                array('name' => 'period', 'label' => 'Month'),
                array('name' => 'account_code', 'label' => 'Cash/bank code'),
                array('name' => 'account_name', 'label' => 'Cash/bank account'),
                array('name' => 'cash_in', 'label' => 'Cash in (base)'),
                array('name' => 'cash_out', 'label' => 'Cash out (base)'),
            ),
            'ar_report' => array(
                array('name' => 'invoice_number', 'label' => 'Invoice'),
                array('name' => 'customer_name', 'label' => 'Customer'),
                array('name' => 'invoice_date', 'label' => 'Invoice date'),
                array('name' => 'due_date', 'label' => 'Due date'),
                array('name' => 'currency_code', 'label' => 'Currency'),
                array('name' => 'billed', 'label' => 'Billed (base)'),
                array('name' => 'paid', 'label' => 'Paid (base)'),
                array('name' => 'outstanding', 'label' => 'Outstanding (base)'),
            ),
            'ap_report' => array(
                array('name' => 'bill_number', 'label' => 'Bill'),
                array('name' => 'supplier_name', 'label' => 'Supplier'),
                array('name' => 'bill_date', 'label' => 'Bill date'),
                array('name' => 'due_date', 'label' => 'Due date'),
                array('name' => 'currency_code', 'label' => 'Currency'),
                array('name' => 'billed', 'label' => 'Billed (base)'),
                array('name' => 'paid', 'label' => 'Paid (base)'),
                array('name' => 'outstanding', 'label' => 'Outstanding (base)'),
            ),
        );
        if (isset($columns[$slug])) {
            return $columns[$slug];
        }
        return array(
            array('name' => 'account_code', 'label' => 'Account'),
            array('name' => 'account_name', 'label' => 'Account name'),
            array('name' => 'account_type', 'label' => 'Type'),
            array('name' => 'debit_total', 'label' => 'Debits (base)'),
            array('name' => 'credit_total', 'label' => 'Credits (base)'),
            array('name' => 'net_debit', 'label' => in_array($slug, array('profit_loss', 'balance_sheet'), TRUE) ? 'Balance (base)' : 'Net debit (base)'),
        );
    }

    private function finance_form($resource, $id)
    {
        $this->load->library('finance_store');
        $store = $this->finance_store;
        $available = $store->available($resource);
        $record = array();
        foreach ($resource['fields'] as $field) {
            $record[$field['name']] = isset($field['default']) ? $field['default'] : '';
        }
        foreach ($resource['columns'] as &$column) {
            if (!isset($column['label_key'])) {
                $column['label_key'] = 'finance_resource_column_' . $column['name'];
            }
        }
        unset($column);
        foreach ($resource['fields'] as &$field) {
            if (!isset($field['label_key'])) {
                $field['label_key'] = 'finance_resource_field_' . $field['name'];
            }
            if (!empty($field['options']) && !isset($field['option_label_keys'])) {
                $field['option_label_keys'] = array();
                foreach ($field['options'] as $option => $option_label) {
                    $field['option_label_keys'][$option] = 'finance_resource_option_' . $field['name'] . '_' . $option;
                }
            }
        }
        unset($field);
        $lookup_labels = array();
        if ($id !== NULL && $available) {
            $saved = $store->find($resource, (int) $this->current_user['tenant_id'], $id);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE) {
                $available = FALSE;
            } else {
                $record = array_merge($record, $saved);
                foreach ($resource['fields'] as $field) {
                    if ($field['type'] === 'account_lookup' && !empty($record[$field['name']])) {
                        $lookup_labels[$field['name']] = $store->account_label(
                            (int) $this->current_user['tenant_id'],
                            $record[$field['name']]
                        );
                    }
                }
            }
        }

        $errors = array();
        $write_available = $this->finance_write_available() && $available;
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->finance_form_values($resource, $id === NULL);
            $errors = $this->finance_validate(
                $resource,
                $values,
                $id === NULL,
                $store,
                (int) $this->current_user['tenant_id']
            );
            if (!$errors) {
                $tenant_id = (int) $this->current_user['tenant_id'];
                $user_id = (int) $this->current_user['user_id'];
                $previous_db_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $saved = $id === NULL
                    ? $store->insert($resource, $tenant_id, $values, $user_id)
                    : $store->update($resource, $tenant_id, $id, $values, $user_id);
                $this->db->db_debug = $previous_db_debug;
                if ($saved === TRUE || $saved === 'updated' || $saved === 'unchanged') {
                    $this->session->set_flashdata('finance_status', $id === NULL ? 'created' : 'saved');
                    redirect('/admin/finance/' . $resource['slug'], 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
            $record = array_merge($record, $values);
            foreach ($resource['fields'] as $field) {
                if ($field['type'] === 'account_lookup' && !empty($record[$field['name']])) {
                    $lookup_labels[$field['name']] = $store->account_label(
                        (int) $this->current_user['tenant_id'],
                        $record[$field['name']]
                    );
                }
            }
        }

        $path = '/admin/finance/' . $resource['slug'] . ($id === NULL ? '/new' : '/edit/' . $id);
        $this->render_admin(
            'admin/finance/resource_form',
            $resource['title_key'],
            $path,
            array(
                'finance_resource' => $resource,
                'finance_record' => $record,
                'finance_errors' => $errors,
                'finance_id' => $id,
                'finance_available' => $available,
                'finance_write_available' => $write_available,
                'finance_lookup_labels' => $lookup_labels,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_form_values($resource, $include_code)
    {
        $values = array();
        foreach ($resource['fields'] as $field) {
            if (!$include_code && !empty($field['immutable_on_update'])) {
                continue;
            }
            $name = $field['name'];
            $raw = $this->input->post($name, TRUE);
            $value = is_scalar($raw) ? trim((string) $raw) : '';
            if ($value === '' && in_array($field['type'], array('number', 'account_lookup'), TRUE) && empty($field['required'])) {
                $values[$name] = NULL;
                continue;
            }
            if (isset($field['normalize']) && $field['normalize'] === 'code') {
                $value = strtolower(preg_replace('/\s+/', '', $value));
            } elseif (isset($field['normalize']) && $field['normalize'] === 'name') {
                $value = $this->finance_normalize_name($value);
            } elseif (isset($field['normalize']) && $field['normalize'] === 'currency') {
                $value = strtoupper($value);
            }
            $values[$name] = $value;
        }
        if (isset($resource['defaults'])) {
            $values = array_merge($resource['defaults'], $values);
        }
        return $values;
    }

    private function finance_validate($resource, $values, $include_code, $store, $tenant_id)
    {
        $errors = array();
        foreach ($resource['fields'] as $field) {
            $name = $field['name'];
            if (!$include_code && !empty($field['immutable_on_update'])) {
                continue;
            }
            $value = isset($values[$name]) ? (string) $values[$name] : '';
            if (!empty($field['required']) && $value === '') {
                $errors[] = $name . '_required';
                continue;
            }
            if ($value !== '' && isset($field['maxlength']) && strlen($value) > (int) $field['maxlength']) {
                $errors[] = $name . '_too_long';
            }
            if ($value !== '' && $field['type'] === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === FALSE) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'currency' && !preg_match('/^[A-Z]{3}$/', $value)) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'decimal'
                && !preg_match('/^\d{1,14}(?:\.\d{1,6})?$/', $value)) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'number' && !preg_match('/^\d+$/', $value)) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'number' && (int) $value < 1) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'account_lookup'
                && (!preg_match('/^\d+$/', $value) || (int) $value < 1)) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && isset($field['max']) && is_numeric($value) && (float) $value > (float) $field['max']) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && isset($field['reference'])
                && !$store->related_exists(
                    $field['reference']['table'],
                    $field['reference']['id'],
                    $tenant_id,
                    $value
                )) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'date') {
                $date = DateTime::createFromFormat('!Y-m-d', $value);
                if ($date === FALSE || $date->format('Y-m-d') !== $value) {
                    $errors[] = $name . '_invalid';
                }
            }
            if ($value !== '' && isset($field['options'])
                && !array_key_exists($value, $field['options'])) {
                $errors[] = $name . '_invalid';
            }
        }
        return array_values(array_unique($errors));
    }

    private function finance_normalize_name($value)
    {
        return preg_replace_callback('/(^|\s)(\S)/u', function ($match) {
            $first = function_exists('mb_strtoupper')
                ? mb_strtoupper($match[2], 'UTF-8')
                : strtoupper($match[2]);
            return $match[1] . $first;
        }, $value);
    }

    private function finance_menu_allowed($resource)
    {
        return isset($this->current_user['tenant_id'])
            && $this->has_admin_menu_code($resource['menu_code']);
    }

    private function finance_write_available()
    {
        return $this->current_user !== NULL && (bool) config_item('csrf_protection');
    }

    private function finance_forbidden()
    {
        $this->output->set_status_header(403);
        show_error('You do not have permission to access this Finance page.', 403);
    }

    private function finance_resource($slug)
    {
        if (!is_string($slug)) {
            return NULL;
        }
        $resources = $this->finance_resources();
        return isset($resources[$slug]) ? $resources[$slug] : NULL;
    }

    private function finance_resources()
    {
        $active = array('name' => 'active_flag', 'label' => 'Active', 'type' => 'select', 'default' => '1',
            'options' => array('1' => 'Active', '0' => 'Inactive'));
        $resources = array(
            'chart_of_accounts' => array(
                'slug' => 'chart_of_accounts', 'menu_code' => 'fin_chart_of_accounts', 'table' => 'fin_account',
                'id' => 'account_id', 'title_key' => 'finance_chart_of_accounts',
                'search' => array('account_code', 'account_name'),
                'columns' => array(
                    array('name' => 'account_code', 'label' => 'Code'),
                    array('name' => 'account_name', 'label' => 'Account'),
                    array('name' => 'account_type', 'label' => 'Type'),
                    array('name' => 'normal_balance', 'label' => 'Normal balance'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'account_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'account_name', 'label' => 'Account name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 120, 'normalize' => 'name'),
                    array('name' => 'account_type', 'label' => 'Account type', 'type' => 'select', 'required' => TRUE,
                        'options' => array('asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense')),
                    array('name' => 'normal_balance', 'label' => 'Normal balance', 'type' => 'select', 'required' => TRUE,
                        'options' => array('debit' => 'Debit', 'credit' => 'Credit')),
                    array('name' => 'parent_account_id', 'label' => 'Parent account ID', 'type' => 'number',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id')),
                    array('name' => 'description', 'label' => 'Description', 'type' => 'textarea'),
                    $active,
                ),
            ),
            'customers' => array(
                'slug' => 'customers', 'menu_code' => 'fin_customers', 'table' => 'fin_customer',
                'id' => 'customer_id', 'title_key' => 'finance_customers',
                'search' => array('customer_code', 'customer_name', 'email_address'),
                'columns' => array(
                    array('name' => 'customer_code', 'label' => 'Code'),
                    array('name' => 'customer_name', 'label' => 'Customer'),
                    array('name' => 'email_address', 'label' => 'Email'),
                    array('name' => 'phone_number', 'label' => 'Phone'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'customer_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'customer_name', 'label' => 'Customer name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 160, 'normalize' => 'name'),
                    array('name' => 'email_address', 'label' => 'Email', 'type' => 'email', 'maxlength' => 160),
                    array('name' => 'phone_number', 'label' => 'Phone', 'type' => 'text', 'maxlength' => 32),
                    array('name' => 'tax_number', 'label' => 'Tax number', 'type' => 'text', 'maxlength' => 64),
                    array('name' => 'billing_address', 'label' => 'Billing address', 'type' => 'textarea'),
                    $active,
                ),
            ),
            'suppliers' => array(
                'slug' => 'suppliers', 'menu_code' => 'fin_suppliers', 'table' => 'fin_supplier',
                'id' => 'supplier_id', 'title_key' => 'finance_suppliers',
                'search' => array('supplier_code', 'supplier_name', 'email_address'),
                'columns' => array(
                    array('name' => 'supplier_code', 'label' => 'Code'),
                    array('name' => 'supplier_name', 'label' => 'Supplier'),
                    array('name' => 'email_address', 'label' => 'Email'),
                    array('name' => 'phone_number', 'label' => 'Phone'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'supplier_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'supplier_name', 'label' => 'Supplier name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 160, 'normalize' => 'name'),
                    array('name' => 'email_address', 'label' => 'Email', 'type' => 'email', 'maxlength' => 160),
                    array('name' => 'phone_number', 'label' => 'Phone', 'type' => 'text', 'maxlength' => 32),
                    array('name' => 'tax_number', 'label' => 'Tax number', 'type' => 'text', 'maxlength' => 64),
                    array('name' => 'billing_address', 'label' => 'Billing address', 'type' => 'textarea'),
                    $active,
                ),
            ),
            'categories' => array(
                'slug' => 'categories', 'menu_code' => 'fin_categories', 'table' => 'fin_category',
                'id' => 'category_id', 'title_key' => 'finance_categories',
                'search' => array('category_code', 'category_name', 'category_type'),
                'columns' => array(
                    array('name' => 'category_code', 'label' => 'Code'),
                    array('name' => 'category_name', 'label' => 'Category'),
                    array('name' => 'category_type', 'label' => 'Type'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'category_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'category_name', 'label' => 'Category name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 100, 'normalize' => 'name'),
                    array('name' => 'category_type', 'label' => 'Category type', 'type' => 'select', 'required' => TRUE,
                        'options' => array('income' => 'Income', 'expense' => 'Expense')),
                    $active,
                ),
            ),
            'departments' => array(
                'slug' => 'departments', 'menu_code' => 'fin_departments', 'table' => 'fin_department',
                'id' => 'department_id', 'title_key' => 'finance_departments',
                'search' => array('department_code', 'department_name'),
                'columns' => array(
                    array('name' => 'department_code', 'label' => 'Code'),
                    array('name' => 'department_name', 'label' => 'Department'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'department_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'department_name', 'label' => 'Department name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 100, 'normalize' => 'name'),
                    $active,
                ),
            ),
            'tax_configuration' => array(
                'slug' => 'tax_configuration', 'menu_code' => 'fin_tax_configuration', 'table' => 'fin_tax',
                'id' => 'tax_id', 'title_key' => 'finance_tax_configuration',
                'search' => array('tax_code', 'tax_name'),
                'columns' => array(
                    array('name' => 'tax_code', 'label' => 'Code'),
                    array('name' => 'tax_name', 'label' => 'Tax'),
                    array('name' => 'rate_percent', 'label' => 'Rate %'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'tax_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'tax_name', 'label' => 'Tax name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 100, 'normalize' => 'name'),
                    array('name' => 'rate_percent', 'label' => 'Rate percent', 'type' => 'decimal', 'required' => TRUE, 'max' => 100),
                    array('name' => 'account_id', 'label' => 'Tax account ID', 'type' => 'number',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id')),
                    $active,
                ),
            ),
            'fiscal_year' => array(
                'slug' => 'fiscal_year', 'menu_code' => 'fin_fiscal_year', 'table' => 'fin_fiscal_year',
                'id' => 'fiscal_year_id', 'title_key' => 'finance_fiscal_year',
                'search' => array('year_code', 'year_name'),
                'columns' => array(
                    array('name' => 'year_code', 'label' => 'Code'),
                    array('name' => 'year_name', 'label' => 'Fiscal year'),
                    array('name' => 'start_on', 'label' => 'Start'),
                    array('name' => 'end_on', 'label' => 'End'),
                    array('name' => 'status', 'label' => 'Status'),
                ),
                'fields' => array(
                    array('name' => 'year_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'year_name', 'label' => 'Fiscal year name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 80, 'normalize' => 'name'),
                    array('name' => 'start_on', 'label' => 'Start date', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'end_on', 'label' => 'End date', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'status', 'label' => 'Status', 'type' => 'select', 'default' => 'open',
                        'options' => array('open' => 'Open', 'closed' => 'Closed')),
                ),
            ),
            'accounting_periods' => array(
                'slug' => 'accounting_periods', 'menu_code' => 'fin_accounting_periods', 'table' => 'fin_accounting_period',
                'id' => 'period_id', 'title_key' => 'finance_accounting_periods',
                'search' => array('period_name', 'status'),
                'columns' => array(
                    array('name' => 'period_number', 'label' => 'Number'),
                    array('name' => 'period_name', 'label' => 'Period'),
                    array('name' => 'start_on', 'label' => 'Start'),
                    array('name' => 'end_on', 'label' => 'End'),
                    array('name' => 'status', 'label' => 'Status'),
                ),
                'fields' => array(
                    array('name' => 'fiscal_year_id', 'label' => 'Fiscal year ID', 'type' => 'number', 'required' => TRUE,
                        'reference' => array('table' => 'fin_fiscal_year', 'id' => 'fiscal_year_id')),
                    array('name' => 'period_number', 'label' => 'Period number', 'type' => 'number', 'required' => TRUE),
                    array('name' => 'period_name', 'label' => 'Period name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 80, 'normalize' => 'name'),
                    array('name' => 'start_on', 'label' => 'Start date', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'end_on', 'label' => 'End date', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'status', 'label' => 'Status', 'type' => 'select', 'default' => 'open',
                        'options' => array('open' => 'Open', 'closed' => 'Closed')),
                ),
            ),
            'cash_accounts' => array(
                'slug' => 'cash_accounts', 'menu_code' => 'fin_cash_accounts', 'table' => 'fin_cash_account',
                'id' => 'cash_account_id', 'title_key' => 'finance_cash_accounts',
                'search' => array('account_code', 'account_name', 'institution_name'),
                'columns' => array(
                    array('name' => 'account_code', 'label' => 'Code'),
                    array('name' => 'account_name', 'label' => 'Account'),
                    array('name' => 'account_kind', 'label' => 'Kind'),
                    array('name' => 'currency_code', 'label' => 'Currency'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'account_id', 'label' => 'Chart of accounts ID', 'type' => 'number', 'required' => TRUE,
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id')),
                    array('name' => 'account_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'account_name', 'label' => 'Account name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 120, 'normalize' => 'name'),
                    array('name' => 'account_kind', 'label' => 'Kind', 'type' => 'select', 'required' => TRUE,
                        'options' => array('cash' => 'Cash', 'bank' => 'Bank')),
                    array('name' => 'currency_code', 'label' => 'Currency code', 'type' => 'currency', 'required' => TRUE, 'maxlength' => 3, 'normalize' => 'currency'),
                    array('name' => 'institution_name', 'label' => 'Bank / institution', 'type' => 'text', 'maxlength' => 120),
                    $active,
                ),
            ),
            'financial_settings' => array(
                'slug' => 'financial_settings', 'menu_code' => 'fin_financial_settings', 'table' => 'fin_settings',
                'id' => 'settings_id', 'title_key' => 'finance_financial_settings',
                'search' => array('base_currency'),
                'columns' => array(
                    array('name' => 'base_currency', 'label' => 'Base currency', 'label_key' => 'finance_column_base_currency'),
                    array('name' => 'tax_inclusive_default', 'label' => 'Tax inclusive', 'label_key' => 'finance_column_tax_inclusive'),
                ),
                'fields' => array(
                    array('name' => 'base_currency', 'label' => 'Base currency (ISO code)', 'label_key' => 'finance_field_base_currency', 'type' => 'currency', 'required' => TRUE, 'maxlength' => 3, 'normalize' => 'currency',
                        'hint' => '3-letter ISO code of the accounting currency, e.g. IDR or USD. All journals and reports convert to this currency.',
                        'hint_key' => 'finance_field_base_currency_hint'),
                    array('name' => 'tax_inclusive_default', 'label' => 'Tax inclusive by default', 'label_key' => 'finance_field_tax_inclusive_default', 'type' => 'select', 'default' => '0',
                        'options' => array('0' => 'No — tax exclusive', '1' => 'Yes — tax inclusive'),
                        'option_label_keys' => array('0' => 'finance_option_tax_exclusive', '1' => 'finance_option_tax_inclusive'),
                        'hint' => 'Determines whether sales/purchase document totals include tax by default.',
                        'hint_key' => 'finance_field_tax_inclusive_default_hint'),
                    array('name' => 'receivable_account_id', 'label' => 'Accounts receivable control account', 'label_key' => 'finance_field_receivable_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Asset account for customer receivable balances. Recommended: 1100 Piutang Usaha.',
                        'hint_key' => 'finance_field_receivable_account_id_hint'),
                    array('name' => 'payable_account_id', 'label' => 'Accounts payable control account', 'label_key' => 'finance_field_payable_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Liability account for supplier payable balances. Recommended: 2000 Utang Usaha.',
                        'hint_key' => 'finance_field_payable_account_id_hint'),
                    array('name' => 'fx_gain_account_id', 'label' => 'Foreign exchange gain account', 'label_key' => 'finance_field_fx_gain_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Revenue account for exchange-rate gains. Recommended: 8000 Keuntungan Selisih Kurs.',
                        'hint_key' => 'finance_field_fx_gain_account_id_hint'),
                    array('name' => 'fx_loss_account_id', 'label' => 'Foreign exchange loss account', 'label_key' => 'finance_field_fx_loss_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Expense account for exchange-rate losses. Recommended: 8100 Kerugian Selisih Kurs.',
                        'hint_key' => 'finance_field_fx_loss_account_id_hint'),
                ),
            ),
            'numbering_config' => array(
                'slug' => 'numbering_config', 'menu_code' => 'fin_numbering_config', 'table' => 'fin_numbering',
                'id' => 'numbering_id', 'title_key' => 'finance_numbering_config',
                'search' => array('document_type', 'prefix'),
                'columns' => array(
                    array('name' => 'document_type', 'label' => 'Document type'),
                    array('name' => 'prefix', 'label' => 'Prefix'),
                    array('name' => 'next_number', 'label' => 'Next number'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'document_type', 'label' => 'Document type', 'type' => 'text', 'required' => TRUE, 'maxlength' => 40, 'normalize' => 'code'),
                    array('name' => 'prefix', 'label' => 'Prefix', 'type' => 'text', 'required' => TRUE, 'maxlength' => 24),
                    array('name' => 'next_number', 'label' => 'Next number', 'type' => 'number', 'required' => TRUE, 'default' => '1'),
                    array('name' => 'number_width', 'label' => 'Number width', 'type' => 'number', 'required' => TRUE, 'default' => '6'),
                    $active,
                ),
            ),
        );

        $cash_fields = array();
        foreach ($resources['cash_accounts']['fields'] as $field) {
            if ($field['name'] !== 'account_kind') {
                $cash_fields[] = $field;
            }
        }
        $resources['cash_accounts']['fields'] = $cash_fields;
        $resources['cash_accounts']['scope'] = array('account_kind' => 'cash');
        $resources['cash_accounts']['defaults'] = array('account_kind' => 'cash');

        $bank = $resources['cash_accounts'];
        $bank['slug'] = 'bank_accounts';
        $bank['menu_code'] = 'fin_bank_accounts';
        $bank['title_key'] = 'finance_bank_accounts';
        $bank['scope'] = array('account_kind' => 'bank');
        $bank['defaults'] = array('account_kind' => 'bank');
        $resources['bank_accounts'] = $bank;
        return $resources;
    }
}
