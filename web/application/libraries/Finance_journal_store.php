<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped manual journal drafts, opening balances, and explicit posting. */
class Finance_journal_store
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
        $required = array(
            'fin_journal_entry' => array('journal_entry_id', 'tenant_id', 'period_id', 'journal_number', 'journal_date', 'source_type', 'status', 'debit_total', 'credit_total'),
            'fin_journal_line' => array('journal_line_id', 'tenant_id', 'journal_entry_id', 'account_id', 'line_number', 'currency_code', 'exchange_rate', 'debit_amount', 'credit_amount', 'debit_base_amount', 'credit_base_amount'),
            'fin_accounting_period' => array('period_id', 'tenant_id', 'start_on', 'end_on', 'status'),
            'fin_account' => array('account_id', 'tenant_id', 'account_code', 'account_name', 'active_flag'),
            'fin_settings' => array('tenant_id', 'base_currency'),
        );
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

    public function count($tenant_id, $source_type, $search = '')
    {
        if (!$this->available()) {
            return FALSE;
        }
        $this->db->from('fin_journal_entry')->where('tenant_id', (int) $tenant_id)
            ->where('source_type', $source_type);
        $this->apply_search($search);
        $count = $this->db->count_all_results();
        return $count === FALSE ? FALSE : (int) $count;
    }

    public function page($tenant_id, $source_type, $search, $limit, $offset, $order, $direction)
    {
        if (!$this->available()) {
            return FALSE;
        }
        $allowed = array('journal_number', 'journal_date', 'description', 'debit_total', 'status');
        $order = in_array($order, $allowed, TRUE) ? $order : 'journal_date';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $this->db->select('journal_entry_id,journal_number,journal_date,description,debit_total,credit_total,status')
            ->from('fin_journal_entry')->where('tenant_id', (int) $tenant_id)->where('source_type', $source_type);
        $this->apply_search($search);
        $query = $this->db->order_by($order, $direction)->order_by('journal_entry_id', 'DESC')
            ->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find($tenant_id, $id, $source_type)
    {
        if (!$this->available()) {
            return FALSE;
        }
        $header = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('journal_entry_id', (int) $id)->where('source_type', $source_type)
            ->limit(1)->get('fin_journal_entry');
        if ($header === FALSE) {
            return FALSE;
        }
        if (!$header->num_rows()) {
            return NULL;
        }
        $row = $header->row_array();
        $lines = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('journal_entry_id', (int) $id)->order_by('line_number', 'ASC')
            ->get('fin_journal_line');
        if ($lines === FALSE) {
            return FALSE;
        }
        $row['lines'] = $lines->result_array();
        return $row;
    }

    public function save_draft($tenant_id, $user_id, $source_type, $id, $date, $description, $lines)
    {
        if (!$this->available() || !in_array($source_type, array('manual', 'opening_balance'), TRUE)
            || !is_array($lines) || count($lines) < 2 || count($lines) > 30) {
            return 'invalid';
        }
        $settings = $this->db->where('tenant_id', (int) $tenant_id)->limit(1)->get('fin_settings');
        if ($settings === FALSE || !$settings->num_rows()) {
            return 'base_currency_missing';
        }
        $base_currency = strtoupper((string) $settings->row()->base_currency);
        $clean_lines = array();
        $debit_total = 0.0;
        $credit_total = 0.0;
        foreach ($lines as $line) {
            $account = $this->tenant_row('fin_account', 'account_id', $tenant_id, $line['account_id']);
            $currency = strtoupper((string) $line['currency_code']);
            $rate = (float) $line['exchange_rate'];
            $debit = round((float) $line['debit_amount'], 6);
            $credit = round((float) $line['credit_amount'], 6);
            if (!$account || (int) $account['active_flag'] !== 1
                || !preg_match('/^[A-Z]{3}$/', $currency)
                || $rate <= 0 || ($currency === $base_currency && abs($rate - 1) > 0.0000001)
                || (($debit > 0) === ($credit > 0))) {
                return 'line_invalid';
            }
            $base_debit = round($debit * $rate, 6);
            $base_credit = round($credit * $rate, 6);
            $debit_total += $base_debit;
            $credit_total += $base_credit;
            $clean_lines[] = array(
                'account_id' => (int) $account['account_id'],
                'department_id' => !empty($line['department_id']) ? (int) $line['department_id'] : NULL,
                'description' => trim((string) $line['description']),
                'currency_code' => $currency,
                'exchange_rate' => $rate,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'debit_base_amount' => $base_debit,
                'credit_base_amount' => $base_credit,
            );
        }

        $this->db->trans_begin();
        if ($id !== NULL) {
            $existing = $this->find($tenant_id, $id, $source_type);
            if (!is_array($existing)) {
                $this->db->trans_rollback();
                return $existing === NULL ? 'not_found' : 'unavailable';
            }
            if ($existing['status'] !== 'draft') {
                $this->db->trans_rollback();
                return 'not_editable';
            }
            $this->db->where('tenant_id', (int) $tenant_id)->where('journal_entry_id', (int) $id)
                ->update('fin_journal_entry', array(
                    'journal_date' => $date,
                    'description' => $description,
                    'debit_total' => $debit_total,
                    'credit_total' => $credit_total,
                ));
            $entry_id = (int) $id;
            $this->db->where('tenant_id', (int) $tenant_id)->where('journal_entry_id', $entry_id)
                ->delete('fin_journal_line');
        } else {
            $this->db->insert('fin_journal_entry', array(
                'tenant_id' => (int) $tenant_id,
                'period_id' => NULL,
                'journal_number' => 'TMP-' . strtoupper($source_type) . '-' . bin2hex(random_bytes(6)),
                'journal_date' => $date,
                'source_type' => $source_type,
                'source_id' => NULL,
                'description' => $description,
                'status' => 'draft',
                'debit_total' => $debit_total,
                'credit_total' => $credit_total,
                'create_on' => date('Y-m-d H:i:s'),
                'created_by' => (int) $user_id,
            ));
            $entry_id = (int) $this->db->insert_id();
            if ($entry_id < 1) {
                $this->db->trans_rollback();
                return 'save_error';
            }
            $prefix = $source_type === 'opening_balance' ? 'OB-' : 'MAN-';
            $this->db->where('tenant_id', (int) $tenant_id)->where('journal_entry_id', $entry_id)
                ->update('fin_journal_entry', array('journal_number' => $prefix . $entry_id));
        }
        foreach ($clean_lines as $index => $line) {
            $line['tenant_id'] = (int) $tenant_id;
            $line['journal_entry_id'] = $entry_id;
            $line['line_number'] = $index + 1;
            $this->db->insert('fin_journal_line', $line);
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return 'save_error';
        }
        $this->db->trans_commit();
        return 'saved';
    }

    public function post($tenant_id, $user_id, $id, $source_type)
    {
        $entry = $this->find($tenant_id, $id, $source_type);
        if (!is_array($entry)) {
            return $entry === NULL ? 'not_found' : 'unavailable';
        }
        if ($entry['status'] !== 'draft' || count($entry['lines']) < 2) {
            return 'not_postable';
        }
        $debits = 0.0;
        $credits = 0.0;
        foreach ($entry['lines'] as $line) {
            $account = $this->tenant_row('fin_account', 'account_id', $tenant_id, $line['account_id']);
            if (!$account || (int) $account['active_flag'] !== 1) {
                return 'account_invalid';
            }
            $debits += (float) $line['debit_base_amount'];
            $credits += (float) $line['credit_base_amount'];
        }
        if ($debits <= 0 || abs($debits - $credits) > 0.000001) {
            return 'unbalanced';
        }
        $period = $this->db->where('tenant_id', (int) $tenant_id)
            ->where('start_on <=', $entry['journal_date'])
            ->where('end_on >=', $entry['journal_date'])
            ->where('status', 'open')->get('fin_accounting_period');
        if ($period === FALSE || !$period->num_rows()) {
            return 'period_closed';
        }
        if ($period->num_rows() !== 1) {
            return 'period_ambiguous';
        }
        $this->db->trans_begin();
        if ($source_type === 'manual') {
            $CI =& get_instance();
            $CI->load->library('finance_balance_guard');
            $balance_check = $CI->finance_balance_guard->validate_journal_lines($tenant_id, $entry['lines']);
            if ($balance_check !== 'ok') {
                $this->db->trans_rollback();
                return $balance_check;
            }
        }
        $this->db->where('tenant_id', (int) $tenant_id)
            ->where('journal_entry_id', (int) $id)
            ->where('status', 'draft')
            ->update('fin_journal_entry', array(
                'period_id' => (int) $period->row()->period_id,
                'status' => 'posted',
                'debit_total' => $debits,
                'credit_total' => $credits,
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

    public function lookup_accounts($tenant_id, $term)
    {
        $this->db->select('account_id,account_code,account_name')->from('fin_account')
            ->where('tenant_id', (int) $tenant_id)->where('active_flag', 1);
        if ($term !== '') {
            $this->db->group_start()->like('account_code', $term)->or_like('account_name', $term)->group_end();
        }
        $this->db->order_by('account_code', 'ASC')->limit(20);
        $query = $this->db->get();
        $results = array();
        if ($query !== FALSE) {
            foreach ($query->result_array() as $row) {
                $results[] = array(
                    'id' => (int) $row['account_id'],
                    'label' => $row['account_code'] . ' — ' . $row['account_name'],
                    'value' => $row['account_code'] . ' — ' . $row['account_name'],
                );
            }
        }
        return $results;
    }

    public function account_label($tenant_id, $id)
    {
        $row = $this->tenant_row('fin_account', 'account_id', $tenant_id, $id);
        return $row ? $row['account_code'] . ' — ' . $row['account_name'] : '';
    }

    private function apply_search($search)
    {
        if (is_string($search) && trim($search) !== '') {
            $this->db->group_start()->like('journal_number', $search)
                ->or_like('description', $search)->group_end();
        }
    }

    private function tenant_row($table, $id_column, $tenant_id, $id)
    {
        $query = $this->db->where('tenant_id', (int) $tenant_id)
            ->where($id_column, (int) $id)->limit(1)->get($table);
        return $query !== FALSE && $query->num_rows() === 1 ? $query->row_array() : NULL;
    }
}
