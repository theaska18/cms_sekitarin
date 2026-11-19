<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped draft and posting operations for manual income/expense transactions. */
class Finance_transaction_store
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

    public function available()
    {
        foreach (array(
            'fin_transaction' => array('transaction_id', 'tenant_id', 'cash_account_id', 'transaction_number', 'transaction_type', 'transaction_on', 'currency_code', 'exchange_rate', 'subtotal', 'tax_total', 'total_amount', 'base_total_amount', 'status', 'approval_request_id'),
            'fin_transaction_line' => array('transaction_line_id', 'tenant_id', 'transaction_id', 'account_id', 'description', 'quantity', 'unit_price', 'line_subtotal', 'tax_rate_snapshot', 'tax_amount', 'line_total', 'base_line_total', 'base_tax_amount'),
            'fin_journal_entry' => array('journal_entry_id', 'tenant_id', 'period_id', 'journal_number', 'journal_date', 'source_type', 'source_id', 'description', 'status', 'debit_total', 'credit_total', 'created_by', 'posted_on', 'posted_by'),
            'fin_journal_line' => array('journal_line_id', 'tenant_id', 'journal_entry_id', 'account_id', 'line_number', 'currency_code', 'exchange_rate', 'debit_amount', 'credit_amount', 'debit_base_amount', 'credit_base_amount'),
        ) as $table => $required) {
            if (!$this->db->table_exists($table)) {
                return FALSE;
            }
            $fields = $this->db->list_fields($table);
            if (!is_array($fields) || array_diff($required, $fields)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function all_available()
    {
        foreach (array(
            'fin_sales_invoice' => array('sales_invoice_id','tenant_id','invoice_number','invoice_date','total_amount','currency_code','status'),
            'fin_purchase_bill' => array('purchase_bill_id','tenant_id','bill_number','bill_date','total_amount','currency_code','status'),
            'fin_customer_payment' => array('customer_payment_id','tenant_id','payment_number','payment_date','amount','currency_code','status'),
            'fin_supplier_payment' => array('supplier_payment_id','tenant_id','payment_number','payment_date','amount','currency_code','status'),
            'fin_cash_transfer' => array('cash_transfer_id','tenant_id','transfer_number','transfer_date','from_amount','from_currency','status'),
            'fin_settings' => array('tenant_id','base_currency'),
        ) as $table => $required) {
            if (!$this->db->table_exists($table) || !is_array($fields = $this->db->list_fields($table))
                || array_diff($required, $fields)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function count($tenant_id, $type = NULL, $search = '', $filters = array())
    {
        if (!$this->available()) {
            return FALSE;
        }
        if ($type === 'all') {
            list($sql, $binds) = $this->all_transactions_sql($tenant_id);
            $where = array();
            if (trim((string) $search) !== '') {
                $where[] = '(transaction_number LIKE ? OR description LIKE ? OR transaction_type LIKE ?)';
                $needle = '%' . $search . '%';
                array_push($binds, $needle, $needle, $needle);
            }
            if (!empty($filters['date_ranges'])) {
                foreach ($filters['date_ranges'] as $column => $range) {
                    if (!empty($range['start'])) { $where[] = $column . ' >= ?'; $binds[] = $range['start']; }
                    if (!empty($range['end'])) { $where[] = $column . ' <= ?'; $binds[] = $range['end']; }
                }
            } else {
                if (!empty($filters['start_on'])) { $where[] = 'transaction_on >= ?'; $binds[] = $filters['start_on']; }
                if (!empty($filters['end_on'])) { $where[] = 'transaction_on <= ?'; $binds[] = $filters['end_on']; }
            }
            if (!empty($filters['status'])) { $where[] = 'status = ?'; $binds[] = $filters['status']; }
            $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
            if (!empty($fields['transaction_type'])) { $where[] = 'transaction_type = ?'; $binds[] = $fields['transaction_type']; }
            if (!empty($fields['amount_min'])) { $where[] = 'total_amount >= ?'; $binds[] = $fields['amount_min']; }
            if (!empty($fields['amount_max'])) { $where[] = 'total_amount <= ?'; $binds[] = $fields['amount_max']; }
            if (!empty($fields['currency_code'])) { $where[] = 'currency_code = ?'; $binds[] = $fields['currency_code']; }
            if (!empty($fields['created_by'])) { $where[] = 'created_by = ?'; $binds[] = (int) $fields['created_by']; }
            $sql = 'SELECT * FROM (' . $sql . ') all_finance' . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
            $result = $this->db->query('SELECT COUNT(*) AS row_count FROM (' . $sql . ') finance_count', $binds);
            return $result === FALSE ? FALSE : (int) $result->row()->row_count;
        }
        $this->db->from('fin_transaction')->where('tenant_id', (int) $tenant_id);
        if ($type !== NULL) {
            $this->db->where('transaction_type', $type);
        }
        if (trim((string) $search) !== '') {
            $this->db->group_start()
                ->like('transaction_number', $search)
                ->or_like('description', $search)
                ->group_end();
        }
        $this->apply_filters('', $filters);
        $count = $this->db->count_all_results();
        return $count === FALSE ? FALSE : (int) $count;
    }

    public function page($tenant_id, $type, $search, $limit, $offset, $order, $direction, $filters = array())
    {
        if (!$this->available()) {
            return FALSE;
        }
        if ($type === 'all') {
            list($sql, $binds) = $this->all_transactions_sql($tenant_id);
            $allowed = array('transaction_number','transaction_type','transaction_on','create_on','total_amount','currency_code','status','created_by_name');
            $order = in_array($order, $allowed, TRUE) ? $order : 'transaction_on';
            $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
            $sql = 'SELECT a.*, COALESCE(NULLIF(u.full_name, \'\'), u.username) AS created_by_name,'
                . ' COALESCE(NULLIF(au.full_name, \'\'), au.username) AS current_approver_name'
                . ' FROM (' . $sql . ') a'
                . ' LEFT JOIN app_user u ON u.user_id = a.created_by AND u.tenant_id = a.tenant_id'
                . ' LEFT JOIN app_approval_request ar ON ar.request_id = a.approval_request_id AND ar.tenant_id = a.tenant_id'
                . ' LEFT JOIN app_user au ON au.user_id = ar.current_approver_user_id AND au.tenant_id = ar.tenant_id';
            $where = array();
            if (trim((string) $search) !== '') {
                $where[] = '(transaction_number LIKE ? OR description LIKE ? OR transaction_type LIKE ?)';
                $needle = '%' . $search . '%';
                array_push($binds, $needle, $needle, $needle);
            }
            if (!empty($filters['date_ranges'])) {
                foreach ($filters['date_ranges'] as $column => $range) {
                    if (!empty($range['start'])) { $where[] = $column . ' >= ?'; $binds[] = $range['start']; }
                    if (!empty($range['end'])) { $where[] = $column . ' <= ?'; $binds[] = $range['end']; }
                }
            } else {
                if (!empty($filters['start_on'])) { $where[] = 'transaction_on >= ?'; $binds[] = $filters['start_on']; }
                if (!empty($filters['end_on'])) { $where[] = 'transaction_on <= ?'; $binds[] = $filters['end_on']; }
            }
            if (!empty($filters['status'])) { $where[] = 'status = ?'; $binds[] = $filters['status']; }
            $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
            if (!empty($fields['transaction_type'])) { $where[] = 'transaction_type = ?'; $binds[] = $fields['transaction_type']; }
            if (!empty($fields['amount_min'])) { $where[] = 'total_amount >= ?'; $binds[] = $fields['amount_min']; }
            if (!empty($fields['amount_max'])) { $where[] = 'total_amount <= ?'; $binds[] = $fields['amount_max']; }
            if (!empty($fields['currency_code'])) { $where[] = 'currency_code = ?'; $binds[] = $fields['currency_code']; }
            if (!empty($fields['created_by'])) { $where[] = 'created_by = ?'; $binds[] = (int) $fields['created_by']; }
            $sql = 'SELECT * FROM (' . $sql . ') all_finance' . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
            $sql .= ' ORDER BY ' . $order . ' ' . $direction . ', transaction_id DESC LIMIT '
                . min(50, max(1, (int) $limit)) . ' OFFSET ' . max(0, (int) $offset);
            $result = $this->db->query($sql, $binds);
            return $result === FALSE ? FALSE : $result->result_array();
        }
        $allowed = array('transaction_number', 'transaction_type', 'description', 'transaction_on', 'create_on', 'total_amount', 'currency_code', 'status', 'created_by_name');
        $order = in_array($order, $allowed, TRUE) ? $order : 'transaction_on';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $revise_note = "(SELECT a.note FROM app_approval_action a"
            . " WHERE a.tenant_id = t.tenant_id AND a.request_id = t.approval_request_id"
            . " AND a.decision = 'revise' ORDER BY a.action_id DESC LIMIT 1) AS revise_note";
        $this->db->select('t.transaction_id,t.transaction_number,t.transaction_type,t.transaction_on,t.create_on,t.total_amount,t.currency_code,t.status,t.description,t.approval_request_id,'
            . "COALESCE(NULLIF(u.full_name, ''), u.username) AS created_by_name,"
            . "COALESCE(NULLIF(au.full_name, ''), au.username) AS current_approver_name,"
            . $revise_note, FALSE)
            ->from('fin_transaction t')
            ->join('app_user u', 'u.user_id = t.created_by AND u.tenant_id = t.tenant_id', 'left')
            ->join('app_approval_request ar', 'ar.request_id = t.approval_request_id AND ar.tenant_id = t.tenant_id', 'left')
            ->join('app_user au', 'au.user_id = ar.current_approver_user_id AND au.tenant_id = ar.tenant_id', 'left')
            ->where('t.tenant_id', (int) $tenant_id);
        if ($type !== NULL) {
            $this->db->where('t.transaction_type', $type);
        }
        $this->apply_filters('t.', $filters);
        if (trim((string) $search) !== '') {
            $this->db->group_start()
                ->like('t.transaction_number', $search)
                ->or_like('t.description', $search)
                ->group_end();
        }
        $order_column = $order === 'created_by_name' ? 'created_by_name' : 't.' . $order;
        $query = $this->db->order_by($order_column, $direction)
            ->order_by('t.transaction_id', 'DESC')
            ->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))
            ->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find($tenant_id, $id)
    {
        if (!$this->available()) {
            return FALSE;
        }
        $header = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('transaction_id', (int) $id)->limit(1)->get('fin_transaction');
        if ($header === FALSE) {
            return FALSE;
        }
        if ($header->num_rows() === 0) {
            return NULL;
        }
        $record = $header->row_array();
        $line = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('transaction_id', (int) $id)->limit(1)->get('fin_transaction_line');
        if ($line === FALSE) {
            return FALSE;
        }
        $record['line'] = $line->num_rows() ? $line->row_array() : NULL;
        return $record;
    }

    private function transaction_number_exists($tenant_id, $number)
    {
        return $this->db->from('fin_transaction')
            ->where('tenant_id', (int) $tenant_id)
            ->where('transaction_number', (string) $number)
            ->count_all_results() > 0;
    }

    /** Save a single-line draft only; a posted transaction is immutable. */
    public function save_draft($tenant_id, $user_id, $type, $id, $values)
    {
        if (!$this->available() || !in_array($type, array('income', 'expense'), TRUE)) {
            return 'unavailable';
        }
        $cash = $this->tenant_row('fin_cash_account', 'cash_account_id', $tenant_id, $values['cash_account_id']);
        $cash_gl_account = $cash
            ? $this->tenant_row('fin_account', 'account_id', $tenant_id, $cash['account_id'])
            : NULL;
        if (!$cash || (int) $cash['active_flag'] !== 1 || !$cash_gl_account
            || (int) $cash_gl_account['active_flag'] !== 1 || (int) $cash_gl_account['posting_flag'] !== 1 || $cash_gl_account['account_type'] !== 'asset') {
            return 'cash_account_invalid';
        }
        $account = $this->tenant_row('fin_account', 'account_id', $tenant_id, $values['account_id']);
        $expected_type = $type === 'income' ? 'revenue' : 'expense';
        if (!$account || (int) $account['active_flag'] !== 1 || (int) $account['posting_flag'] !== 1 || $account['account_type'] !== $expected_type) {
            return 'account_invalid';
        }
        $category_id = !empty($values['category_id']) ? (int) $values['category_id'] : NULL;
        if ($category_id !== NULL) {
            $category = $this->tenant_row('fin_category', 'category_id', $tenant_id, $category_id);
            if (!$category || (int) $category['active_flag'] !== 1 || $category['category_type'] !== $type) {
                return 'category_invalid';
            }
        }
        $department_id = !empty($values['department_id']) ? (int) $values['department_id'] : NULL;
        if ($department_id !== NULL && !$this->tenant_row('fin_department', 'department_id', $tenant_id, $department_id)) {
            return 'department_invalid';
        }

        $amount = round((float) $values['amount'], 6);
        if ($amount <= 0) {
            return 'amount_invalid';
        }
        $previous_line = NULL;
        if ($id !== NULL) {
            $previous_line = $this->db->select('tax_id,tax_rate_snapshot')
                ->where('tenant_id', (int) $tenant_id)
                ->where('transaction_id', (int) $id)
                ->limit(1)->get('fin_transaction_line')->row_array();
        }
        $tax = NULL;
        $tax_rate = 0.0;
        if (!empty($values['tax_id'])) {
            $tax = $this->tenant_row('fin_tax', 'tax_id', $tenant_id, (int) $values['tax_id']);
            if (!$tax || (int) $tax['active_flag'] !== 1) {
                return 'tax_invalid';
            }
            $tax_rate = (float) $tax['rate_percent'];
            if ($previous_line && (int) $previous_line['tax_id'] === (int) $tax['tax_id']
                && isset($previous_line['tax_rate_snapshot']) && is_numeric($previous_line['tax_rate_snapshot'])) {
                $tax_rate = (float) $previous_line['tax_rate_snapshot'];
            }
        }
        $tax_amount = round($amount * $tax_rate / 100, 6);
        $total = round($amount + $tax_amount, 6);
        $currency = strtoupper((string) $cash['currency_code']);
        $settings = $this->db->where('tenant_id', (int) $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows()
            ? strtoupper((string) $settings->row()->base_currency)
            : '';
        $rate = (float) $values['exchange_rate'];
        if ($base_currency === '' || $rate <= 0 || ($currency === $base_currency && abs($rate - 1) > 0.0000001)) {
            return 'exchange_rate_invalid';
        }
        $base_subtotal = round($amount * $rate, 6);
        $base_tax = round($tax_amount * $rate, 6);
        $base_total = round($total * $rate, 6);

        $this->db->trans_begin();
        if ($id !== NULL) {
            $existing = $this->db->where('tenant_id', (int) $tenant_id)
                ->where('transaction_id', (int) $id)->limit(1)->get('fin_transaction');
            if ($existing === FALSE || !$existing->num_rows()) {
                $this->db->trans_rollback();
                return 'not_found';
            }
            $existing = $existing->row_array();
            if (!in_array($existing['status'], array('draft', 'revise'), TRUE)
                || $existing['transaction_type'] !== $type) {
                $this->db->trans_rollback();
                return 'not_editable';
            }
            $header = array(
                'cash_account_id' => (int) $cash['cash_account_id'],
                'transaction_on' => $values['transaction_on'],
                'currency_code' => $currency,
                'exchange_rate' => $rate,
                'subtotal' => $amount,
                'tax_total' => $tax_amount,
                'total_amount' => $total,
                'base_total_amount' => $base_total,
                'description' => $values['description'],
            );
            $this->db->where('tenant_id', (int) $tenant_id)
                ->where('transaction_id', (int) $id)
                ->where_in('status', array('draft', 'revise'))
                ->update('fin_transaction', $header);
            $transaction_id = (int) $id;
            $this->db->where('tenant_id', (int) $tenant_id)
                ->where('transaction_id', $transaction_id)->delete('fin_transaction_line');
        } else {
            $CI =& get_instance();
            $CI->load->library('application_sequence_store');
            $sequence_code = $type === 'income' ? 'income_code' : 'expenses_code';
            $next_code = $CI->application_sequence_store->next_code_for_tenant($tenant_id, $sequence_code);
            if (!is_array($next_code) || !isset($next_code['code']) || $next_code['code'] === '') {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
            for ($attempt = 0; $attempt < 10 && $this->transaction_number_exists($tenant_id, (string) $next_code['code']); $attempt++) {
                $next_code = $CI->application_sequence_store->next_code_after($next_code);
                if (!is_array($next_code) || !isset($next_code['code'])) {
                    $this->db->trans_rollback();
                    return 'sequence_unavailable';
                }
            }
            if ($this->transaction_number_exists($tenant_id, (string) $next_code['code'])) {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
            $this->db->insert('fin_transaction', array(
                'tenant_id' => (int) $tenant_id,
                'cash_account_id' => (int) $cash['cash_account_id'],
                'transaction_number' => (string) $next_code['code'],
                'transaction_type' => $type,
                'transaction_on' => $values['transaction_on'],
                'currency_code' => $currency,
                'exchange_rate' => $rate,
                'subtotal' => $amount,
                'tax_total' => $tax_amount,
                'total_amount' => $total,
                'base_total_amount' => $base_total,
                'status' => 'draft',
                'description' => $values['description'],
                'create_on' => date('Y-m-d H:i:s'),
                'created_by' => (int) $user_id,
            ));
            $transaction_id = (int) $this->db->insert_id();
            if ($transaction_id < 1) {
                $this->db->trans_rollback();
                return 'save_error';
            }
            if (!$CI->application_sequence_store->advance_sequence($tenant_id, $sequence_code, $next_code)) {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
        }

        $this->db->insert('fin_transaction_line', array(
            'tenant_id' => (int) $tenant_id,
            'transaction_id' => $transaction_id,
            'category_id' => $category_id,
            'account_id' => (int) $account['account_id'],
            'department_id' => $department_id,
            'tax_id' => $tax ? (int) $tax['tax_id'] : NULL,
            'description' => $values['description'] !== '' ? $values['description'] : $account['account_name'],
            'quantity' => 1,
            'unit_price' => $amount,
            'line_subtotal' => $amount,
            'tax_rate_snapshot' => $tax_rate,
            'tax_amount' => $tax_amount,
            'line_total' => $total,
            'base_line_total' => $base_total,
            'base_tax_amount' => $base_tax,
        ));
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return 'save_error';
        }
        $this->db->trans_commit();
        return 'saved';
    }

    public function post($tenant_id, $user_id, $id)
    {
        if (!$this->available()) {
            return 'unavailable';
        }
        $record = $this->find($tenant_id, $id);
        if (!is_array($record)) {
            return $record === NULL ? 'not_found' : 'unavailable';
        }
        if ($record['status'] !== 'approved' || !$record['line']) {
            return 'not_postable';
        }
        $line = $record['line'];
        $cash = $this->tenant_row('fin_cash_account', 'cash_account_id', $tenant_id, $record['cash_account_id']);
        $cash_account = $cash ? $this->tenant_row('fin_account', 'account_id', $tenant_id, $cash['account_id']) : NULL;
        $account = $this->tenant_row('fin_account', 'account_id', $tenant_id, $line['account_id']);
        $expected_account_type = $record['transaction_type'] === 'income' ? 'revenue' : 'expense';
        if (!$cash || (int) $cash['active_flag'] !== 1 || !$cash_account
            || (int) $cash_account['active_flag'] !== 1 || (int) $cash_account['posting_flag'] !== 1 || !$account
            || (int) $account['active_flag'] !== 1 || (int) $account['posting_flag'] !== 1 || $account['account_type'] !== $expected_account_type) {
            return 'account_invalid';
        }
        $period_query = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('start_on <=', $record['transaction_on'])
            ->where('end_on >=', $record['transaction_on'])
            ->where('status', 'open')->get('fin_accounting_period');
        if ($period_query === FALSE || $period_query->num_rows() === 0) {
            return 'period_closed';
        }
        if ($period_query->num_rows() !== 1) {
            return 'period_ambiguous';
        }
        $period_id = (int) $period_query->row()->period_id;
        $tax_account = NULL;
        if (!empty($line['tax_id']) && (float) $line['tax_amount'] > 0) {
            $tax_row = $this->tenant_row('fin_tax', 'tax_id', $tenant_id, $line['tax_id']);
            if (!$tax_row || empty($tax_row['account_id'])) {
                return 'tax_account_missing';
            }
            $tax_account = $this->tenant_row('fin_account', 'account_id', $tenant_id, $tax_row['account_id']);
            if (!$tax_account || (int) $tax_account['active_flag'] !== 1 || (int) $tax_account['posting_flag'] !== 1) {
                return 'tax_account_missing';
            }
        }

        $base_total = (float) $record['base_total_amount'];
        $base_subtotal = round((float) $line['base_line_total'] - (float) $line['base_tax_amount'], 6);
        $base_tax = round($base_total - $base_subtotal, 6);
        $debit_total = $record['transaction_type'] === 'income' ? $base_total : $base_total;
        $credit_total = $base_total;
        $this->db->trans_begin();
        $locked = $this->db->query(
            'SELECT status FROM fin_transaction WHERE tenant_id = ? AND transaction_id = ? LIMIT 1 FOR UPDATE',
            array((int) $tenant_id, (int) $id)
        );
        if ($locked === FALSE || !$locked->num_rows() || (string) $locked->row()->status !== 'approved') {
            $this->db->trans_rollback();
            return 'not_postable';
        }
        $CI =& get_instance();
        if ($record['transaction_type'] === 'expense') {
            $CI->load->library('finance_balance_guard');
            $balance_check = $CI->finance_balance_guard->can_debit(
                $tenant_id,
                (int) $record['cash_account_id'],
                (float) $record['total_amount'],
                (string) $record['currency_code']
            );
            if ($balance_check !== 'ok') {
                $this->db->trans_rollback();
                return $balance_check;
            }
        }
        $CI->load->library('application_sequence_store');
        $next_journal = $CI->application_sequence_store->next_code_for_tenant(
            $tenant_id,
            'journal_expenses_income',
            FALSE
        );
        if (!is_array($next_journal) || !isset($next_journal['code']) || $next_journal['code'] === '') {
            $this->db->trans_rollback();
            return 'sequence_unavailable';
        }
        $journal_number = (string) $next_journal['code'];
        $existing = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('source_type', 'transaction')
            ->where('source_id', (int) $record['transaction_id'])
            ->limit(1)->get('fin_journal_entry');
        if ($existing === FALSE || $existing->num_rows()) {
            $this->db->trans_rollback();
            return 'already_posted';
        }
        $this->db->insert('fin_journal_entry', array(
            'tenant_id' => (int) $tenant_id,
            'period_id' => $period_id,
            'journal_number' => $journal_number,
            'journal_date' => $record['transaction_on'],
            'source_type' => 'transaction',
            'source_id' => (int) $record['transaction_id'],
            'description' => 'Posted ' . $record['transaction_number'],
            'status' => 'posted',
            'debit_total' => $debit_total,
            'credit_total' => $credit_total,
            'create_on' => date('Y-m-d H:i:s'),
            'created_by' => (int) $user_id,
            'posted_on' => date('Y-m-d H:i:s'),
            'posted_by' => (int) $user_id,
        ));
        $journal_id = (int) $this->db->insert_id();
        if ($journal_id < 1
            || !$CI->application_sequence_store->advance_sequence($tenant_id, 'journal_expenses_income', $next_journal)) {
            $this->db->trans_rollback();
            return 'post_error';
        }
        $income = $record['transaction_type'] === 'income';
        $lines = array(
            array(
                'account_id' => (int) $cash_account['account_id'],
                'description' => $record['description'],
                'debit_amount' => $income ? (float) $record['total_amount'] : 0,
                'credit_amount' => $income ? 0 : (float) $record['total_amount'],
                'debit_base_amount' => $income ? $base_total : 0,
                'credit_base_amount' => $income ? 0 : $base_total,
            ),
            array(
                'account_id' => (int) $account['account_id'],
                'description' => $line['description'],
                'debit_amount' => $income ? 0 : (float) $line['line_subtotal'],
                'credit_amount' => $income ? (float) $line['line_subtotal'] : 0,
                'debit_base_amount' => $income ? 0 : $base_subtotal,
                'credit_base_amount' => $income ? $base_subtotal : 0,
            ),
        );
        if ($tax_account && $base_tax > 0) {
            $lines[] = array(
                'account_id' => (int) $tax_account['account_id'],
                'description' => 'Tax: ' . $line['description'],
                'debit_amount' => $income ? 0 : (float) $line['tax_amount'],
                'credit_amount' => $income ? (float) $line['tax_amount'] : 0,
                'debit_base_amount' => $income ? 0 : $base_tax,
                'credit_base_amount' => $income ? $base_tax : 0,
            );
        }
        foreach ($lines as $index => $journal_line) {
            $journal_line['tenant_id'] = (int) $tenant_id;
            $journal_line['journal_entry_id'] = $journal_id;
            $journal_line['department_id'] = !empty($line['department_id']) ? (int) $line['department_id'] : NULL;
            $journal_line['line_number'] = $index + 1;
            $journal_line['currency_code'] = $record['currency_code'];
            $journal_line['exchange_rate'] = $record['exchange_rate'];
            $this->db->insert('fin_journal_line', $journal_line);
        }
        $this->db->where('tenant_id', (int) $tenant_id)
            ->where('transaction_id', (int) $record['transaction_id'])
            ->where('status', 'approved')
            ->update('fin_transaction', array(
                'status' => 'posted',
                'posted_on' => date('Y-m-d H:i:s'),
                'posted_by' => (int) $user_id,
            ));
        if ($this->db->trans_status() === FALSE || (int) $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return 'post_error';
        }
        $this->db->trans_commit();
        return 'posted';
    }

    public function cash_accounts($tenant_id)
    {
        return $this->active_options('fin_cash_account', 'cash_account_id', 'account_code', 'account_name', $tenant_id);
    }

    public function accounts($tenant_id, $type)
    {
        $this->db->select('account_id,account_code,account_name')->from('fin_account')
            ->where('tenant_id', (int) $tenant_id)->where('account_type', $type)->where('active_flag', 1)->where('posting_flag', 1)
            ->order_by('account_code', 'ASC')->limit(100);
        $query = $this->db->get();
        return $query === FALSE ? array() : $query->result_array();
    }

    public function categories($tenant_id, $type)
    {
        return $this->active_options('fin_category', 'category_id', 'category_code', 'category_name', $tenant_id, array('category_type' => $type));
    }

    public function taxes($tenant_id)
    {
        $this->db->select('tax_id,tax_code,tax_name,rate_percent')->from('fin_tax')
            ->where('tenant_id', (int) $tenant_id)->where('active_flag', 1)->order_by('tax_code', 'ASC')->limit(100);
        $query = $this->db->get();
        return $query === FALSE ? array() : $query->result_array();
    }

    public function lookup($tenant_id, $kind, $term)
    {
        $map = $this->lookup_map($kind);
        if ($map === NULL) {
            return array();
        }
        $this->db->select($map['id'] . ',' . $map['code'] . ',' . $map['name'])
            ->from($map['table'])
            ->where('tenant_id', (int) $tenant_id)
            ->where('active_flag', 1);
        if ($map['table'] === 'fin_account') {
            $this->db->where('posting_flag', 1);
        }
        if (isset($map['type'])) {
            $this->db->where($map['type_column'], $map['type']);
        }
        if (isset($map['extra'])) {
            foreach ($map['extra'] as $column => $value) {
                $this->db->where($column, $value);
            }
        }
        if ($term !== '') {
            $this->db->group_start()
                ->like($map['code'], $term)
                ->or_like($map['name'], $term)
                ->group_end();
        }
        $query = $this->db->order_by($map['code'], 'ASC')->limit(20)->get();
        if ($query === FALSE) {
            return array();
        }
        $results = array();
        foreach ($query->result_array() as $row) {
            $results[] = array(
                'id' => (int) $row[$map['id']],
                'label' => $row[$map['code']] . ' — ' . $row[$map['name']],
                'value' => $row[$map['code']] . ' — ' . $row[$map['name']],
            );
        }
        return $results;
    }

    public function lookup_label($tenant_id, $kind, $id)
    {
        $map = $this->lookup_map($kind);
        if ($map === NULL || (int) $id < 1) {
            return '';
        }
        $this->db->select($map['code'] . ',' . $map['name'])->from($map['table'])
            ->where('tenant_id', (int) $tenant_id)
            ->where($map['id'], (int) $id);
        if (isset($map['type'])) {
            $this->db->where($map['type_column'], $map['type']);
        }
        $query = $this->db->limit(1)->get();
        if ($query === FALSE || !$query->num_rows()) {
            return '';
        }
        $row = $query->row_array();
        return $row[$map['code']] . ' — ' . $row[$map['name']];
    }

    private function active_options($table, $id, $code, $name, $tenant_id, $filters = array())
    {
        $this->db->select($id . ',' . $code . ',' . $name)->from($table)
            ->where('tenant_id', (int) $tenant_id)->where('active_flag', 1);
        foreach ($filters as $column => $value) {
            $this->db->where($column, $value);
        }
        $query = $this->db->order_by($code, 'ASC')->limit(100)->get();
        return $query === FALSE ? array() : $query->result_array();
    }

    private function tenant_row($table, $id_column, $tenant_id, $id)
    {
        if (!$this->db->table_exists($table)) {
            return NULL;
        }
        $query = $this->db->where('tenant_id', (int) $tenant_id)
            ->where($id_column, (int) $id)->limit(1)->get($table);
        return $query !== FALSE && $query->num_rows() === 1 ? $query->row_array() : NULL;
    }

    private function lookup_map($kind)
    {
        $maps = array(
            'cash_account' => array('table' => 'fin_cash_account', 'id' => 'cash_account_id', 'code' => 'account_code', 'name' => 'account_name'),
            'revenue_account' => array('table' => 'fin_account', 'id' => 'account_id', 'code' => 'account_code', 'name' => 'account_name', 'type_column' => 'account_type', 'type' => 'revenue'),
            'expense_account' => array('table' => 'fin_account', 'id' => 'account_id', 'code' => 'account_code', 'name' => 'account_name', 'type_column' => 'account_type', 'type' => 'expense'),
            'income_category' => array('table' => 'fin_category', 'id' => 'category_id', 'code' => 'category_code', 'name' => 'category_name', 'type_column' => 'category_type', 'type' => 'income'),
            'expense_category' => array('table' => 'fin_category', 'id' => 'category_id', 'code' => 'category_code', 'name' => 'category_name', 'type_column' => 'category_type', 'type' => 'expense'),
            'tax' => array('table' => 'fin_tax', 'id' => 'tax_id', 'code' => 'tax_code', 'name' => 'tax_name'),
            'department' => array('table' => 'fin_department', 'id' => 'department_id', 'code' => 'department_code', 'name' => 'department_name'),
        );
        return isset($maps[$kind]) ? $maps[$kind] : NULL;
    }

    private function apply_filters($alias, $filters)
    {
        if (!empty($filters['date_ranges'])) {
            foreach ($filters['date_ranges'] as $column => $range) {
                if (!empty($range['start'])) $this->db->where($alias . $column . ' >=', $range['start']);
                if (!empty($range['end'])) $this->db->where($alias . $column . ' <=', $range['end']);
            }
        } else {
            if (!empty($filters['start_on'])) $this->db->where($alias . 'transaction_on >=', $filters['start_on']);
            if (!empty($filters['end_on'])) $this->db->where($alias . 'transaction_on <=', $filters['end_on']);
        }
        if (!empty($filters['status'])) {
            $this->db->where($alias . 'status', $filters['status']);
        }
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        if (!empty($fields['transaction_type'])) $this->db->where($alias . 'transaction_type', $fields['transaction_type']);
        if (!empty($fields['description'])) $this->db->like($alias . 'description', $fields['description']);
        if (!empty($fields['amount_min'])) $this->db->where($alias . 'total_amount >=', $fields['amount_min']);
        if (!empty($fields['amount_max'])) $this->db->where($alias . 'total_amount <=', $fields['amount_max']);
        if (!empty($fields['currency_code'])) $this->db->where($alias . 'currency_code', $fields['currency_code']);
        if (!empty($fields['created_by'])) $this->db->where($alias . 'created_by', (int) $fields['created_by']);
    }

    private function all_transactions_sql($tenant_id)
    {
        $sql = 'SELECT transaction_id,transaction_number,transaction_type,transaction_on,total_amount,currency_code,status,description,created_by,create_on,approval_request_id,tenant_id FROM fin_transaction WHERE tenant_id=? '
            . 'UNION ALL SELECT sales_invoice_id,invoice_number,"sales_invoice",invoice_date,total_amount,currency_code,status,memo,created_by,create_on,NULL,tenant_id FROM fin_sales_invoice WHERE tenant_id=? '
            . 'UNION ALL SELECT purchase_bill_id,bill_number,"purchase_bill",bill_date,total_amount,currency_code,status,memo,created_by,create_on,NULL,tenant_id FROM fin_purchase_bill WHERE tenant_id=? '
            . 'UNION ALL SELECT customer_payment_id,payment_number,"customer_payment",payment_date,amount,currency_code,status,reference,created_by,create_on,NULL,tenant_id FROM fin_customer_payment WHERE tenant_id=? '
            . 'UNION ALL SELECT supplier_payment_id,payment_number,"supplier_payment",payment_date,amount,currency_code,status,reference,created_by,create_on,NULL,tenant_id FROM fin_supplier_payment WHERE tenant_id=? '
            . 'UNION ALL SELECT cash_transfer_id,transfer_number,"cash_transfer",transfer_date,from_amount,from_currency,status,memo,created_by,create_on,approval_request_id,tenant_id FROM fin_cash_transfer WHERE tenant_id=? '
            . 'UNION ALL SELECT je.journal_entry_id,je.journal_number,je.source_type,je.journal_date,je.debit_total,s.base_currency,je.status,je.description,je.created_by,je.create_on,je.approval_request_id,je.tenant_id FROM fin_journal_entry je JOIN fin_settings s ON s.tenant_id=je.tenant_id WHERE je.tenant_id=? AND je.source_type IN ("manual","opening_balance")';
        return array($sql, array_fill(0, 7, (int) $tenant_id));
    }
}
