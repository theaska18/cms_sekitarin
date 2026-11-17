<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Single-line sales invoice and purchase bill drafts with explicit ledger posting. */
class Finance_document_store
{
    private $db;

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            $db = $CI->db;
        }
        $this->db = $db;
    }

    public function available($kind)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL) {
            return FALSE;
        }
        $required = array(
            $spec['header'] => array($spec['id'], 'tenant_id', $spec['person_id'], $spec['number'], $spec['date'], 'currency_code', 'exchange_rate', 'base_total_amount', 'status'),
            $spec['line_table'] => array($spec['line_id'], 'tenant_id', $spec['id'], 'account_id', 'line_subtotal', 'tax_amount', 'line_total', 'base_line_total'),
            'fin_account' => array('tenant_id', 'account_id', 'account_type', 'active_flag'),
            'fin_journal_entry' => array('tenant_id', 'journal_entry_id', 'source_type', 'source_id'),
            'fin_journal_line' => array('tenant_id', 'journal_entry_id', 'account_id', 'debit_base_amount', 'credit_base_amount'),
            'fin_settings' => array('tenant_id', $spec['control_account']),
            'fin_tax' => array('tenant_id', 'tax_id', 'rate_percent', 'account_id', 'active_flag'),
        );
        $required[$spec['person_table']] = array('tenant_id', $spec['person_id'], 'active_flag');
        foreach ($required as $table => $columns) {
            if (!$this->db->table_exists($table)) {
                return FALSE;
            }
            $fields = $this->db->list_fields($table);
            if (!is_array($fields) || array_diff($columns, $fields)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function count($tenant_id, $kind, $search = '')
    {
        if (!$this->available($kind)) {
            return FALSE;
        }
        $spec = $this->spec($kind);
        $this->db->from($spec['header'])->where('tenant_id', (int) $tenant_id);
        if (trim((string) $search) !== '') {
            $this->db->group_start()->like($spec['number'], $search)->or_like('memo', $search)->group_end();
        }
        $count = $this->db->count_all_results();
        return $count === FALSE ? FALSE : (int) $count;
    }

    public function page($tenant_id, $kind, $search, $limit, $offset, $order, $direction)
    {
        if (!$this->available($kind)) {
            return FALSE;
        }
        $spec = $this->spec($kind);
        $allowed = array($spec['number'], $spec['date'], 'due_date', 'total_amount', 'currency_code', 'status');
        $order = in_array($order, $allowed, TRUE) ? $order : $spec['date'];
        $this->db->select('d.' . $spec['id'] . ',d.' . $spec['number'] . ',d.' . $spec['date'] . ',d.due_date,d.total_amount,d.currency_code,d.status,d.memo,d.approval_request_id,'
            . "COALESCE(NULLIF(u.full_name, ''), u.username) AS created_by_name,"
            . "COALESCE(NULLIF(au.full_name, ''), au.username) AS current_approver_name,"
            . "(SELECT a.note FROM app_approval_action a WHERE a.tenant_id = d.tenant_id AND a.request_id = d.approval_request_id AND a.decision = 'revise' ORDER BY a.action_id DESC LIMIT 1) AS revise_note", FALSE)
            ->from($spec['header'] . ' d')
            ->join('app_user u', 'u.user_id = d.created_by AND u.tenant_id = d.tenant_id', 'left')
            ->join('app_approval_request ar', 'ar.request_id = d.approval_request_id AND ar.tenant_id = d.tenant_id', 'left')
            ->join('app_user au', 'au.user_id = ar.current_approver_user_id AND au.tenant_id = ar.tenant_id', 'left')
            ->where('d.tenant_id', (int) $tenant_id);
        if (trim((string) $search) !== '') {
            $this->db->group_start()->like('d.' . $spec['number'], $search)->or_like('d.memo', $search)->group_end();
        }
        $order_column = in_array($order, array('created_by_name'), TRUE) ? $order : 'd.' . $order;
        $query = $this->db->order_by($order_column, strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC')
            ->order_by('d.' . $spec['id'], 'DESC')->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find($tenant_id, $kind, $id)
    {
        if (!$this->available($kind)) {
            return FALSE;
        }
        $spec = $this->spec($kind);
        $header = $this->db->where('tenant_id', (int) $tenant_id)->where($spec['id'], (int) $id)->limit(1)->get($spec['header']);
        if ($header === FALSE) {
            return FALSE;
        }
        if (!$header->num_rows()) {
            return NULL;
        }
        $row = $header->row_array();
        $line = $this->db->where('tenant_id', (int) $tenant_id)->where($spec['id'], (int) $id)->limit(1)->get($spec['line_table']);
        if ($line === FALSE) {
            return FALSE;
        }
        $row['line'] = $line->num_rows() ? $line->row_array() : NULL;
        return $row;
    }

    public function save_draft($tenant_id, $user_id, $kind, $id, $values)
    {
        if (!$this->available($kind)) {
            return 'unavailable';
        }
        $spec = $this->spec($kind);
        $person = $this->tenant_row($spec['person_table'], $spec['person_id'], $tenant_id, $values['person_id']);
        $account = $this->tenant_row('fin_account', 'account_id', $tenant_id, $values['account_id']);
        $expected_type = $kind === 'sales' ? 'revenue' : 'expense';
        if (!$person || (int) $person['active_flag'] !== 1) {
            return 'party_invalid';
        }
        if (!$account || (int) $account['active_flag'] !== 1 || (int) $account['posting_flag'] !== 1 || $account['account_type'] !== $expected_type) {
            return 'account_invalid';
        }
        $settings = $this->db->where('tenant_id', (int) $tenant_id)->limit(1)->get('fin_settings');
        if ($settings === FALSE || !$settings->num_rows()) {
            return 'settings_missing';
        }
        $settings = $settings->row_array();
        if (empty($settings[$spec['control_account']])) {
            return 'control_account_missing';
        }
        $currency = strtoupper((string) $values['currency_code']);
        $base_currency = strtoupper((string) $settings['base_currency']);
        $rate = (float) $values['exchange_rate'];
        if (!preg_match('/^[A-Z]{3}$/', $currency) || $rate <= 0
            || ($currency === $base_currency && abs($rate - 1) > 0.0000001)) {
            return 'exchange_rate_invalid';
        }
        $subtotal = round((float) $values['amount'], 6);
        $tax = NULL;
        $tax_rate = 0.0;
        if (!empty($values['tax_id'])) {
            $tax = $this->tenant_row('fin_tax', 'tax_id', $tenant_id, $values['tax_id']);
            if (!$tax || (int) $tax['active_flag'] !== 1) {
                return 'tax_invalid';
            }
            $tax_rate = (float) $tax['rate_percent'];
        }
        $tax_amount = round($subtotal * $tax_rate / 100, 6);
        $total = round($subtotal + $tax_amount, 6);
        $base_subtotal = round($subtotal * $rate, 6);
        $base_tax = round($tax_amount * $rate, 6);
        $base_total = round($total * $rate, 6);

        $this->db->trans_begin();
        if ($id !== NULL) {
            $existing = $this->find($tenant_id, $kind, $id);
            if (!is_array($existing)) {
                $this->db->trans_rollback();
                return $existing === NULL ? 'not_found' : 'unavailable';
            }
            if (!in_array((string) $existing['status'], array('draft', 'revise'), TRUE)) {
                $this->db->trans_rollback();
                return 'not_editable';
            }
            $header_values = $this->header_values($spec, $tenant_id, $values, $currency, $rate, $subtotal, $tax_amount, $total, $base_total);
            $this->db->where('tenant_id', (int) $tenant_id)->where($spec['id'], (int) $id)
                ->update($spec['header'], $header_values);
            $doc_id = (int) $id;
            $this->db->where('tenant_id', (int) $tenant_id)->where($spec['id'], $doc_id)->delete($spec['line_table']);
        } else {
            $header_values = $this->header_values($spec, $tenant_id, $values, $currency, $rate, $subtotal, $tax_amount, $total, $base_total);
            $CI =& get_instance();
            $CI->load->library('application_sequence_store');
            $sequence_code = $kind === 'sales' ? 'sales_invoice_code' : 'purchase_bill_code';
            $next_code = $CI->application_sequence_store->next_code_for_tenant($tenant_id, $sequence_code);
            if (!is_array($next_code) || !isset($next_code['code']) || $next_code['code'] === '') {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
            for ($attempt = 0; $attempt < 10 && $this->number_exists($spec['header'], $spec['number'], $tenant_id, (string) $next_code['code']); $attempt++) {
                $next_code = $CI->application_sequence_store->next_code_after($next_code);
                if (!is_array($next_code) || !isset($next_code['code'])) {
                    $this->db->trans_rollback();
                    return 'sequence_unavailable';
                }
            }
            if ($this->number_exists($spec['header'], $spec['number'], $tenant_id, (string) $next_code['code'])) {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
            $header_values[$spec['number']] = (string) $next_code['code'];
            $header_values['status'] = 'draft';
            $header_values['create_on'] = date('Y-m-d H:i:s');
            $header_values['created_by'] = (int) $user_id;
            $this->db->insert($spec['header'], $header_values);
            $doc_id = (int) $this->db->insert_id();
            if ($doc_id < 1 || !$CI->application_sequence_store->advance_sequence($tenant_id, $sequence_code, $next_code)) {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
        }
        $line_values = array(
            'tenant_id' => (int) $tenant_id,
            $spec['id'] => $doc_id,
            'account_id' => (int) $account['account_id'],
            'department_id' => !empty($values['department_id']) ? (int) $values['department_id'] : NULL,
            'tax_id' => $tax ? (int) $tax['tax_id'] : NULL,
            'description' => $values['description'],
            'quantity' => 1,
            'unit_price' => $subtotal,
            'tax_rate_snapshot' => $tax_rate,
            'line_subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'line_total' => $total,
            'base_line_total' => $base_total,
        );
        $this->db->insert($spec['line_table'], $line_values);
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return 'save_error';
        }
        $this->db->trans_commit();
        return 'saved';
    }

    public function post($tenant_id, $user_id, $kind, $id)
    {
        if (!$this->available($kind)) {
            return 'unavailable';
        }
        $spec = $this->spec($kind);
        $doc = $this->find($tenant_id, $kind, $id);
        if (!is_array($doc)) {
            return $doc === NULL ? 'not_found' : 'unavailable';
        }
        if ($doc['status'] !== 'approved' || !$doc['line']) {
            return 'not_postable';
        }
        $settings = $this->db->where('tenant_id', (int) $tenant_id)->limit(1)->get('fin_settings');
        if ($settings === FALSE || !$settings->num_rows()) {
            return 'settings_missing';
        }
        $settings = $settings->row_array();
        $control_id = !empty($settings[$spec['control_account']]) ? (int) $settings[$spec['control_account']] : 0;
        $control = $control_id ? $this->tenant_row('fin_account', 'account_id', $tenant_id, $control_id) : NULL;
        $line = $doc['line'];
        $account = $this->tenant_row('fin_account', 'account_id', $tenant_id, $line['account_id']);
        $expected_type = $kind === 'sales' ? 'revenue' : 'expense';
        $control_type = $kind === 'sales' ? 'asset' : 'liability';
        if (!$control || (int) $control['active_flag'] !== 1 || $control['account_type'] !== $control_type || !$account
            || (int) $account['active_flag'] !== 1 || (int) $account['posting_flag'] !== 1 || $account['account_type'] !== $expected_type) {
            return 'account_invalid';
        }
        $tax_account = NULL;
        if (!empty($line['tax_id']) && (float) $line['tax_amount'] > 0) {
            $tax = $this->tenant_row('fin_tax', 'tax_id', $tenant_id, $line['tax_id']);
            $tax_account = $tax && !empty($tax['account_id'])
                ? $this->tenant_row('fin_account', 'account_id', $tenant_id, $tax['account_id'])
                : NULL;
            if (!$tax_account || (int) $tax_account['active_flag'] !== 1 || (int) $tax_account['posting_flag'] !== 1) {
                return 'tax_account_missing';
            }
        }
        $period = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('start_on <=', $doc[$spec['date']])->where('end_on >=', $doc[$spec['date']])
            ->where('status', 'open')->get('fin_accounting_period');
        if ($period === FALSE || $period->num_rows() === 0) {
            return 'period_closed';
        }
        if ($period->num_rows() !== 1) {
            return 'period_ambiguous';
        }
        $base_total = (float) $doc['base_total_amount'];
        $base_tax = isset($line['base_tax_amount']) ? (float) $line['base_tax_amount'] : round((float) $line['tax_amount'] * (float) $doc['exchange_rate'], 6);
        $base_subtotal = round($base_total - $base_tax, 6);
        $this->db->trans_begin();
        $duplicate = $this->db->where('tenant_id', (int) $tenant_id)->where('source_type', $spec['source_type'])
            ->where('source_id', (int) $id)->limit(1)->get('fin_journal_entry');
        if ($duplicate === FALSE || $duplicate->num_rows()) {
            $this->db->trans_rollback();
            return 'already_posted';
        }
        $journal_number = ($kind === 'sales' ? 'SI-' : 'PB-') . (int) $id;
        $this->db->insert('fin_journal_entry', array(
            'tenant_id' => (int) $tenant_id,
            'period_id' => (int) $period->row()->period_id,
            'journal_number' => $journal_number,
            'journal_date' => $doc[$spec['date']],
            'source_type' => $spec['source_type'],
            'source_id' => (int) $id,
            'description' => 'Posted ' . $doc[$spec['number']],
            'status' => 'posted',
            'debit_total' => $base_total,
            'credit_total' => $base_total,
            'create_on' => date('Y-m-d H:i:s'),
            'created_by' => (int) $user_id,
            'posted_on' => date('Y-m-d H:i:s'),
            'posted_by' => (int) $user_id,
        ));
        $journal_id = (int) $this->db->insert_id();
        if ($journal_id < 1) {
            $this->db->trans_rollback();
            return 'post_error';
        }
        $sales = $kind === 'sales';
        $journal_lines = array(
            array(
                'account_id' => (int) $control['account_id'],
                'description' => $doc[$spec['number']],
                'debit_amount' => $sales ? (float) $doc['total_amount'] : 0,
                'credit_amount' => $sales ? 0 : (float) $doc['total_amount'],
                'debit_base_amount' => $sales ? $base_total : 0,
                'credit_base_amount' => $sales ? 0 : $base_total,
            ),
            array(
                'account_id' => (int) $account['account_id'],
                'description' => $line['description'],
                'debit_amount' => $sales ? 0 : (float) $line['line_subtotal'],
                'credit_amount' => $sales ? (float) $line['line_subtotal'] : 0,
                'debit_base_amount' => $sales ? 0 : $base_subtotal,
                'credit_base_amount' => $sales ? $base_subtotal : 0,
            ),
        );
        if ($tax_account && $base_tax > 0) {
            $journal_lines[] = array(
                'account_id' => (int) $tax_account['account_id'],
                'description' => 'Tax - ' . $doc[$spec['number']],
                'debit_amount' => $sales ? 0 : (float) $line['tax_amount'],
                'credit_amount' => $sales ? (float) $line['tax_amount'] : 0,
                'debit_base_amount' => $sales ? 0 : $base_tax,
                'credit_base_amount' => $sales ? $base_tax : 0,
            );
        }
        foreach ($journal_lines as $index => $journal_line) {
            $this->db->insert('fin_journal_line', array_merge($journal_line, array(
                'tenant_id' => (int) $tenant_id,
                'journal_entry_id' => $journal_id,
                'department_id' => !empty($line['department_id']) ? (int) $line['department_id'] : NULL,
                'line_number' => $index + 1,
                'currency_code' => $doc['currency_code'],
                'exchange_rate' => $doc['exchange_rate'],
            )));
        }
        $this->db->where('tenant_id', (int) $tenant_id)->where($spec['id'], (int) $id)->where('status', 'approved')
            ->update($spec['header'], array('status' => 'posted', 'posted_on' => date('Y-m-d H:i:s'), 'posted_by' => (int) $user_id));
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return 'post_error';
        }
        $this->db->trans_commit();
        return 'posted';
    }

    public function lookup($tenant_id, $document_kind, $kind, $term)
    {
        $spec = $this->spec($document_kind);
        if ($spec === NULL) {
            return array();
        }
        $maps = array(
            'party' => array('table' => $spec['person_table'], 'id' => $spec['person_id'], 'code' => $spec['person_code'], 'name' => $spec['person_name']),
            'account' => array('table' => 'fin_account', 'id' => 'account_id', 'code' => 'account_code', 'name' => 'account_name', 'type' => $spec['account_type']),
            'tax' => array('table' => 'fin_tax', 'id' => 'tax_id', 'code' => 'tax_code', 'name' => 'tax_name'),
        );
        $map = $maps[$kind] ?? NULL;
        if ($map === NULL) {
            return array();
        }
        $this->db->select($map['id'] . ',' . $map['code'] . ',' . $map['name'])->from($map['table'])
            ->where('tenant_id', (int) $tenant_id)->where('active_flag', 1);
        if ($map['table'] === 'fin_account') {
            $this->db->where('posting_flag', 1);
        }
        if (isset($map['type'])) {
            $this->db->where('account_type', $map['type']);
        }
        if ($term !== '') {
            $this->db->group_start()->like($map['code'], $term)->or_like($map['name'], $term)->group_end();
        }
        $query = $this->db->order_by($map['code'], 'ASC')->limit(20)->get();
        $results = array();
        if ($query !== FALSE) {
            foreach ($query->result_array() as $row) {
                $results[] = array('id' => (int) $row[$map['id']], 'label' => $row[$map['code']] . ' — ' . $row[$map['name']], 'value' => $row[$map['code']] . ' — ' . $row[$map['name']]);
            }
        }
        return $results;
    }

    public function lookup_label($tenant_id, $document_kind, $kind, $id)
    {
        $spec = $this->spec($document_kind);
        if ($spec === NULL) {
            return '';
        }
        $maps = array(
            'party' => array('table' => $spec['person_table'], 'id' => $spec['person_id'], 'code' => $spec['person_code'], 'name' => $spec['person_name']),
            'account' => array('table' => 'fin_account', 'id' => 'account_id', 'code' => 'account_code', 'name' => 'account_name'),
            'tax' => array('table' => 'fin_tax', 'id' => 'tax_id', 'code' => 'tax_code', 'name' => 'tax_name'),
        );
        $map = $maps[$kind] ?? NULL;
        if ($map === NULL) {
            return '';
        }
        $query = $this->db->select($map['code'] . ',' . $map['name'])->where('tenant_id', (int) $tenant_id)
            ->where($map['id'], (int) $id)->limit(1)->get($map['table']);
        if ($query === FALSE || !$query->num_rows()) {
            return '';
        }
        $row = $query->row_array();
        return $row[$map['code']] . ' — ' . $row[$map['name']];
    }

    private function number_exists($table, $column, $tenant_id, $number)
    {
        return $this->db->from($table)->where('tenant_id', (int) $tenant_id)
            ->where($column, (string) $number)->count_all_results() > 0;
    }

    private function header_values($spec, $tenant_id, $values, $currency, $rate, $subtotal, $tax_amount, $total, $base_total)
    {
        $header = array(
            'tenant_id' => (int) $tenant_id,
            $spec['person_id'] => (int) $values['person_id'],
            $spec['date'] => $values['document_date'],
            'due_date' => $values['due_date'] !== '' ? $values['due_date'] : NULL,
            'currency_code' => $currency,
            'exchange_rate' => $rate,
            'tax_inclusive' => 0,
            'subtotal' => $subtotal,
            'tax_total' => $tax_amount,
            'total_amount' => $total,
            'base_total_amount' => $base_total,
            'status' => 'draft',
            'memo' => $values['memo'],
        );
        return $header;
    }

    private function spec($kind)
    {
        if ($kind === 'sales') {
            return array(
                'header' => 'fin_sales_invoice', 'id' => 'sales_invoice_id', 'line_table' => 'fin_sales_invoice_line',
                'line_id' => 'sales_invoice_line_id', 'number' => 'invoice_number', 'date' => 'invoice_date',
                'person_id' => 'customer_id', 'person_table' => 'fin_customer', 'person_code' => 'customer_code',
                'person_name' => 'customer_name', 'control_account' => 'receivable_account_id',
                'account_type' => 'revenue', 'source_type' => 'sales_invoice',
            );
        }
        if ($kind === 'purchase') {
            return array(
                'header' => 'fin_purchase_bill', 'id' => 'purchase_bill_id', 'line_table' => 'fin_purchase_bill_line',
                'line_id' => 'purchase_bill_line_id', 'number' => 'bill_number', 'date' => 'bill_date',
                'person_id' => 'supplier_id', 'person_table' => 'fin_supplier', 'person_code' => 'supplier_code',
                'person_name' => 'supplier_name', 'control_account' => 'payable_account_id',
                'account_type' => 'expense', 'source_type' => 'purchase_bill',
            );
        }
        return NULL;
    }

    private function tenant_row($table, $id_column, $tenant_id, $id)
    {
        $query = $this->db->where('tenant_id', (int) $tenant_id)->where($id_column, (int) $id)->limit(1)->get($table);
        return $query !== FALSE && $query->num_rows() === 1 ? $query->row_array() : NULL;
    }
}
