<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Customer/supplier payment drafts allocated to one posted invoice or bill. */
class Finance_payment_store
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
        if (!$spec) return FALSE;
        $checks = array(
            $spec['table'] => array($spec['id'], 'tenant_id', $spec['person_id'], 'cash_account_id', 'payment_number', 'payment_date', 'currency_code', 'exchange_rate', 'amount', 'base_amount', 'status'),
            $spec['allocation'] => array('tenant_id', $spec['id'], $spec['document_id'], 'amount', 'base_amount'),
            $spec['document_table'] => array('tenant_id', $spec['document_id'], $spec['person_id'], $spec['document_number'], 'currency_code', 'exchange_rate', 'total_amount', 'base_total_amount', 'status'),
            'fin_cash_account' => array('tenant_id', 'cash_account_id', 'account_id', 'currency_code', 'active_flag'),
            'fin_settings' => array('tenant_id', $spec['control'], 'fx_gain_account_id', 'fx_loss_account_id'),
        );
        foreach ($checks as $table => $columns) {
            if (!$this->db->table_exists($table) || !is_array($fields = $this->db->list_fields($table)) || array_diff($columns, $fields)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function count($tenant, $kind, $search = '')
    {
        if (!$this->available($kind)) return FALSE;
        $spec = $this->spec($kind);
        $this->db->from($spec['table'])->where('tenant_id', (int) $tenant);
        if (trim((string) $search) !== '') {
            $this->db->group_start()->like('payment_number', $search)->or_like('reference', $search)->group_end();
        }
        $n = $this->db->count_all_results();
        return $n === FALSE ? FALSE : (int) $n;
    }

    public function page($tenant, $kind, $search, $limit, $offset, $order, $direction)
    {
        if (!$this->available($kind)) return FALSE;
        $spec = $this->spec($kind);
        $allowed = array('payment_number', 'payment_date', 'amount', 'currency_code', 'status');
        $order = in_array($order, $allowed, TRUE) ? $order : 'payment_date';
        $this->db->select($spec['id'] . ',payment_number,payment_date,amount,currency_code,status,reference')
            ->from($spec['table'])->where('tenant_id', (int) $tenant);
        if (trim((string) $search) !== '') {
            $this->db->group_start()->like('payment_number', $search)->or_like('reference', $search)->group_end();
        }
        $query = $this->db->order_by($order, strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC')
            ->order_by($spec['id'], 'DESC')->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find($tenant, $kind, $id)
    {
        if (!$this->available($kind)) return FALSE;
        $spec = $this->spec($kind);
        $payment = $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->limit(1)->get($spec['table']);
        if ($payment === FALSE) return FALSE;
        if (!$payment->num_rows()) return NULL;
        $row = $payment->row_array();
        $allocation = $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->limit(1)->get($spec['allocation']);
        if ($allocation === FALSE) return FALSE;
        $row['allocation'] = $allocation->num_rows() ? $allocation->row_array() : NULL;
        return $row;
    }

    public function save_draft($tenant, $user, $kind, $id, $values)
    {
        if (!$this->available($kind)) return 'unavailable';
        $spec = $this->spec($kind);
        $doc = $this->tenant_row($spec['document_table'], $spec['document_id'], $tenant, $values['document_id']);
        $cash = $this->tenant_row('fin_cash_account', 'cash_account_id', $tenant, $values['cash_account_id']);
        if (!$doc || $doc['status'] !== 'posted') return 'document_invalid';
        if (!$cash || (int) $cash['active_flag'] !== 1 || $cash['currency_code'] !== $doc['currency_code']) return 'cash_account_invalid';
        if ($doc[$spec['person_id']] < 1) return 'party_invalid';
        $amount = round((float) $values['amount'], 6);
        $rate = (float) $values['exchange_rate'];
        if ($amount <= 0 || $rate <= 0) return 'amount_invalid';
        $allocation = $this->db->select('COALESCE(SUM(a.amount),0) AS allocated', FALSE)
            ->from($spec['allocation'] . ' a')
            ->join($spec['table'] . ' p', 'p.' . $spec['id'] . '=a.' . $spec['id'] . ' AND p.tenant_id=a.tenant_id')
            ->where('a.tenant_id', (int) $tenant)
            ->where('a.' . $spec['document_id'], (int) $values['document_id'])
            ->where('p.status', 'posted')->get();
        if ($allocation === FALSE) return 'unavailable';
        $outstanding = round((float) $doc['total_amount'] - (float) $allocation->row()->allocated, 6);
        if ($amount > $outstanding + 0.000001) return 'overpayment';
        $settings = $this->db->where('tenant_id', (int) $tenant)->limit(1)->get('fin_settings');
        if ($settings === FALSE || !$settings->num_rows()) return 'settings_missing';
        $base_currency = strtoupper((string) $settings->row()->base_currency);
        if ($doc['currency_code'] === $base_currency && abs($rate - 1) > 0.0000001) return 'exchange_rate_invalid';
        $payment_base = round($amount * $rate, 6);
        $allocation_base = round($amount * (float) $doc['exchange_rate'], 6);

        $this->db->trans_begin();
        if ($id !== NULL) {
            $existing = $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->limit(1)->get($spec['table']);
            if ($existing === FALSE || !$existing->num_rows() || $existing->row()->status !== 'draft') {
                $this->db->trans_rollback();
                return 'not_editable';
            }
            $payment_id = (int) $id;
            $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], $payment_id)->update($spec['table'], array(
                $spec['person_id'] => (int) $doc[$spec['person_id']],
                'cash_account_id' => (int) $cash['cash_account_id'],
                'payment_date' => $values['payment_date'],
                'currency_code' => $doc['currency_code'],
                'exchange_rate' => $rate,
                'amount' => $amount,
                'base_amount' => $payment_base,
                'reference' => $values['reference'],
            ));
            $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], $payment_id)->delete($spec['allocation']);
        } else {
            $this->db->insert($spec['table'], array(
                'tenant_id' => (int) $tenant,
                $spec['person_id'] => (int) $doc[$spec['person_id']],
                'cash_account_id' => (int) $cash['cash_account_id'],
                'payment_number' => 'TMP-' . strtoupper($kind) . '-' . bin2hex(random_bytes(6)),
                'payment_date' => $values['payment_date'],
                'currency_code' => $doc['currency_code'],
                'exchange_rate' => $rate,
                'amount' => $amount,
                'base_amount' => $payment_base,
                'status' => 'draft',
                'reference' => $values['reference'],
                'create_on' => date('Y-m-d H:i:s'),
                'created_by' => (int) $user,
            ));
            $payment_id = (int) $this->db->insert_id();
            if (!$payment_id) {
                $this->db->trans_rollback();
                return 'save_error';
            }
            $prefix = $kind === 'customer' ? 'REC-' : 'PAY-';
            $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], $payment_id)
                ->update($spec['table'], array('payment_number' => $prefix . date('Ymd') . '-' . $payment_id));
        }
        $this->db->insert($spec['allocation'], array(
            'tenant_id' => (int) $tenant,
            $spec['id'] => $payment_id,
            $spec['document_id'] => (int) $doc[$spec['document_id']],
            'amount' => $amount,
            'base_amount' => $allocation_base,
        ));
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return 'save_error';
        }
        $this->db->trans_commit();
        return 'saved';
    }

    public function post($tenant, $user, $kind, $id)
    {
        if (!$this->available($kind)) return 'unavailable';
        $spec = $this->spec($kind);
        $payment_q = $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->limit(1)->get($spec['table']);
        if ($payment_q === FALSE || !$payment_q->num_rows()) return 'not_found';
        $payment = $payment_q->row_array();
        if ($payment['status'] !== 'draft') return 'not_postable';
        $alloc_q = $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->limit(1)->get($spec['allocation']);
        if ($alloc_q === FALSE || !$alloc_q->num_rows()) return 'allocation_invalid';
        $alloc = $alloc_q->row_array();
        $doc = $this->tenant_row($spec['document_table'], $spec['document_id'], $tenant, $alloc[$spec['document_id']]);
        if (!$doc || $doc['status'] !== 'posted' || $doc['currency_code'] !== $payment['currency_code']
            || (int) $doc[$spec['person_id']] !== (int) $payment[$spec['person_id']]) return 'document_invalid';
        $posted_allocations = $this->db->select('COALESCE(SUM(a.amount),0) AS allocated', FALSE)
            ->from($spec['allocation'] . ' a')
            ->join($spec['table'] . ' p', 'p.' . $spec['id'] . '=a.' . $spec['id'] . ' AND p.tenant_id=a.tenant_id')
            ->where('a.tenant_id', (int) $tenant)->where('a.' . $spec['document_id'], (int) $doc[$spec['document_id']])
            ->where('p.status', 'posted')->get();
        if ($posted_allocations === FALSE
            || (float) $alloc['amount'] > (float) $doc['total_amount'] - (float) $posted_allocations->row()->allocated + 0.000001) return 'overpayment';
        $settings_q = $this->db->where('tenant_id', (int) $tenant)->limit(1)->get('fin_settings');
        if ($settings_q === FALSE || !$settings_q->num_rows()) return 'settings_missing';
        $settings = $settings_q->row_array();
        $control_id = (int) $settings[$spec['control']];
        $control = $this->tenant_row('fin_account', 'account_id', $tenant, $control_id);
        $cash = $this->tenant_row('fin_cash_account', 'cash_account_id', $tenant, $payment['cash_account_id']);
        $cash_gl = $cash ? $this->tenant_row('fin_account', 'account_id', $tenant, $cash['account_id']) : NULL;
        $control_type = $kind === 'customer' ? 'asset' : 'liability';
        if (!$control || !$cash || !$cash_gl || $control['account_type'] !== $control_type
            || $cash_gl['account_type'] !== 'asset'
            || (int) $control['active_flag'] !== 1 || (int) $cash['active_flag'] !== 1
            || (int) $cash_gl['active_flag'] !== 1
            || $cash['currency_code'] !== $payment['currency_code']) return 'account_invalid';
        $fx_difference = round((float) $payment['base_amount'] - (float) $alloc['base_amount'], 6);
        $fx_account = NULL;
        if (abs($fx_difference) > 0.000001) {
            $fx_id = $kind === 'customer'
                ? (int) ($fx_difference > 0 ? $settings['fx_gain_account_id'] : $settings['fx_loss_account_id'])
                : (int) ($fx_difference > 0 ? $settings['fx_loss_account_id'] : $settings['fx_gain_account_id']);
            $fx_account = $this->tenant_row('fin_account', 'account_id', $tenant, $fx_id);
            $gain = ($kind === 'customer') ? $fx_difference > 0 : $fx_difference < 0;
            $expected_fx_type = $gain ? 'revenue' : 'expense';
            if (!$fx_account || (int) $fx_account['active_flag'] !== 1 || $fx_account['account_type'] !== $expected_fx_type) return 'fx_account_missing';
        }
        $period = $this->db->where('tenant_id', (int) $tenant)->where('start_on <=', $payment['payment_date'])
            ->where('end_on >=', $payment['payment_date'])->where('status', 'open')->get('fin_accounting_period');
        if ($period === FALSE || $period->num_rows() !== 1) return $period && $period->num_rows() > 1 ? 'period_ambiguous' : 'period_closed';
        $debit = $kind === 'customer' ? (float) $payment['base_amount'] : (float) $alloc['base_amount'];
        $credit = $kind === 'customer' ? (float) $alloc['base_amount'] : (float) $payment['base_amount'];
        if ($fx_account) {
            if ($kind === 'customer') {
                if ($fx_difference > 0) $credit += $fx_difference; else $debit += abs($fx_difference);
            } else {
                if ($fx_difference > 0) $debit += $fx_difference; else $credit += abs($fx_difference);
            }
        }
        $this->db->trans_begin();
        $source = $spec['source_type'];
        $duplicate = $this->db->where('tenant_id', (int) $tenant)->where('source_type', $source)
            ->where('source_id', (int) $id)->limit(1)->get('fin_journal_entry');
        if ($duplicate === FALSE || $duplicate->num_rows()) {
            $this->db->trans_rollback();
            return 'already_posted';
        }
        $this->db->insert('fin_journal_entry', array(
            'tenant_id' => (int) $tenant, 'period_id' => (int) $period->row()->period_id,
            'journal_number' => ($kind === 'customer' ? 'RC-' : 'SP-') . (int) $id,
            'journal_date' => $payment['payment_date'], 'source_type' => $source, 'source_id' => (int) $id,
            'description' => 'Posted ' . $payment['payment_number'], 'status' => 'posted',
            'debit_total' => $debit, 'credit_total' => $credit, 'create_on' => date('Y-m-d H:i:s'),
            'created_by' => (int) $user, 'posted_on' => date('Y-m-d H:i:s'), 'posted_by' => (int) $user,
        ));
        $journal_id = (int) $this->db->insert_id();
        $base_lines = array(
            array('account_id' => (int) $cash_gl['account_id'], 'debit_amount' => $kind === 'customer' ? (float) $payment['amount'] : 0, 'credit_amount' => $kind === 'supplier' ? (float) $payment['amount'] : 0, 'debit_base_amount' => $kind === 'customer' ? (float) $payment['base_amount'] : 0, 'credit_base_amount' => $kind === 'supplier' ? (float) $payment['base_amount'] : 0, 'currency_code' => $payment['currency_code'], 'exchange_rate' => $payment['exchange_rate'], 'description' => $payment['payment_number']),
            array('account_id' => (int) $control['account_id'], 'debit_amount' => $kind === 'supplier' ? (float) $alloc['amount'] : 0, 'credit_amount' => $kind === 'customer' ? (float) $alloc['amount'] : 0, 'debit_base_amount' => $kind === 'supplier' ? (float) $alloc['base_amount'] : 0, 'credit_base_amount' => $kind === 'customer' ? (float) $alloc['base_amount'] : 0, 'currency_code' => $payment['currency_code'], 'exchange_rate' => $payment['exchange_rate'], 'description' => $payment['payment_number']),
        );
        if ($fx_account && abs($fx_difference) > 0.000001) {
            $gain = ($kind === 'customer') ? $fx_difference > 0 : $fx_difference < 0;
            $base_lines[] = array('account_id' => (int) $fx_account['account_id'], 'debit_amount' => $gain ? 0 : abs($fx_difference), 'credit_amount' => $gain ? abs($fx_difference) : 0, 'debit_base_amount' => $gain ? 0 : abs($fx_difference), 'credit_base_amount' => $gain ? abs($fx_difference) : 0, 'currency_code' => $settings['base_currency'], 'exchange_rate' => 1, 'description' => 'Realized FX');
        }
        foreach ($base_lines as $index => $line) {
            $this->db->insert('fin_journal_line', array(
                'tenant_id' => (int) $tenant, 'journal_entry_id' => $journal_id,
                'account_id' => $line['account_id'], 'line_number' => $index + 1,
                'description' => $line['description'], 'currency_code' => $line['currency_code'],
                'exchange_rate' => $line['exchange_rate'],
                'debit_amount' => $line['debit_amount'], 'credit_amount' => $line['credit_amount'],
                'debit_base_amount' => $line['debit_base_amount'], 'credit_base_amount' => $line['credit_base_amount'],
            ));
        }
        $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->where('status', 'draft')
            ->update($spec['table'], array('status' => 'posted', 'posted_on' => date('Y-m-d H:i:s'), 'posted_by' => (int) $user));
        if ($journal_id < 1 || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return 'post_error';
        }
        $this->db->trans_commit();
        return 'posted';
    }

    public function lookup($tenant, $kind, $term)
    {
        if ($kind === 'cash_account') {
            $this->db->select('cash_account_id,account_code,account_name,currency_code')->where('tenant_id', (int) $tenant)
                ->where('active_flag', 1);
            if ($term !== '') {
                $this->db->group_start()->like('account_code', $term)->or_like('account_name', $term)->group_end();
            }
            $query = $this->db->order_by('account_code', 'ASC')->limit(20)->get('fin_cash_account');
            $results = array();
            if ($query !== FALSE) foreach ($query->result_array() as $row) {
                $label = $row['account_code'] . ' — ' . $row['account_name'] . ' (' . $row['currency_code'] . ')';
                $results[] = array('id' => (int) $row['cash_account_id'], 'label' => $label, 'value' => $label, 'currency_code' => $row['currency_code']);
            }
            return $results;
        }
        $spec = $this->spec($kind);
        if (!$spec) return array();
        $this->db->select($spec['document_id'] . ',' . $spec['document_number'] . ',currency_code,total_amount')
            ->where('tenant_id', (int) $tenant)->where('status', 'posted');
        if ($term !== '') {
            $this->db->group_start()->like($spec['document_number'], $term)->group_end();
        }
        $query = $this->db->order_by($spec['document_number'], 'ASC')->limit(20)->get($spec['document_table']);
        $results = array();
        if ($query !== FALSE) foreach ($query->result_array() as $row) {
            $label = $row[$spec['document_number']] . ' — ' . $row['currency_code'] . ' ' . $row['total_amount'];
            $results[] = array('id' => (int) $row[$spec['document_id']], 'label' => $label, 'value' => $label, 'currency_code' => $row['currency_code']);
        }
        return $results;
    }

    public function lookup_label($tenant, $kind, $id)
    {
        if ($kind === 'cash_account') {
            $row = $this->tenant_row('fin_cash_account', 'cash_account_id', $tenant, $id);
            return $row ? $row['account_code'] . ' — ' . $row['account_name'] . ' (' . $row['currency_code'] . ')' : '';
        }
        $spec = $this->spec($kind);
        if (!$spec) return '';
        $query = $this->db->select($spec['document_number'] . ',currency_code')->where('tenant_id', (int) $tenant)
            ->where($spec['document_id'], (int) $id)->limit(1)->get($spec['document_table']);
        return $query !== FALSE && $query->num_rows()
            ? $query->row()->{$spec['document_number']} . ' — ' . $query->row()->currency_code
            : '';
    }

    private function spec($kind)
    {
        if ($kind === 'customer') return array('table'=>'fin_customer_payment','id'=>'customer_payment_id','allocation'=>'fin_customer_payment_allocation','document_table'=>'fin_sales_invoice','document_id'=>'sales_invoice_id','document_number'=>'invoice_number','person_id'=>'customer_id','control'=>'receivable_account_id','source_type'=>'customer_payment');
        if ($kind === 'supplier') return array('table'=>'fin_supplier_payment','id'=>'supplier_payment_id','allocation'=>'fin_supplier_payment_allocation','document_table'=>'fin_purchase_bill','document_id'=>'purchase_bill_id','document_number'=>'bill_number','person_id'=>'supplier_id','control'=>'payable_account_id','source_type'=>'supplier_payment');
        return NULL;
    }

    private function tenant_row($table, $id_column, $tenant, $id)
    {
        $query = $this->db->where('tenant_id', (int) $tenant)->where($id_column, (int) $id)->limit(1)->get($table);
        return $query !== FALSE && $query->num_rows() ? $query->row_array() : NULL;
    }
}
