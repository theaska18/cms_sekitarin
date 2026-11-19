<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Approval-backed cancellation requests for Finance records. */
class Finance_cancellation_store
{
    private $db;
    private $sources = array(
        'fin_transaction' => array('table' => 'fin_transaction', 'id' => 'transaction_id', 'journal_source' => 'transaction'),
        'fin_sales_invoice' => array('table' => 'fin_sales_invoice', 'id' => 'sales_invoice_id', 'journal_source' => 'sales_invoice',
            'payment_table' => 'fin_customer_payment', 'payment_id' => 'customer_payment_id', 'payment_allocation' => 'fin_customer_payment_allocation'),
        'fin_purchase_bill' => array('table' => 'fin_purchase_bill', 'id' => 'purchase_bill_id', 'journal_source' => 'purchase_bill',
            'payment_table' => 'fin_supplier_payment', 'payment_id' => 'supplier_payment_id', 'payment_allocation' => 'fin_supplier_payment_allocation'),
        'fin_customer_payment' => array('table' => 'fin_customer_payment', 'id' => 'customer_payment_id', 'journal_source' => 'customer_payment'),
        'fin_supplier_payment' => array('table' => 'fin_supplier_payment', 'id' => 'supplier_payment_id', 'journal_source' => 'supplier_payment'),
        'fin_cash_transfer' => array('table' => 'fin_cash_transfer', 'id' => 'cash_transfer_id', 'journal_source' => 'cash_transfer'),
        'fin_bank_reconciliation' => array('table' => 'fin_bank_reconciliation', 'id' => 'reconciliation_id', 'journal_source' => 'bank_reconciliation'),
        'fin_journal_entry' => array('table' => 'fin_journal_entry', 'id' => 'journal_entry_id', 'journal_source' => ''),
    );

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            if (!isset($CI->db)) { $CI->load->database(); }
            $db = $CI->db;
        }
        $this->db = $db;
    }

    public function available()
    {
        return $this->db->table_exists('fin_cancellation')
            && $this->db->field_exists('request_action', 'app_approval_request');
    }

    public function request($tenant_id, $user_id, $source_type, $source_id, $menu_code, $reason)
    {
        if (!$this->available() || (int) $tenant_id < 1 || (int) $user_id < 1
            || !isset($this->sources[$source_type]) || (int) $source_id < 1) {
            return array('ok' => FALSE, 'error' => 'invalid');
        }
        $reason = trim((string) $reason);
        if ($reason === '' || strlen($reason) > 1000) {
            return array('ok' => FALSE, 'error' => 'reason');
        }
        $spec = $this->sources[$source_type];
        $row = $this->db->where(array('tenant_id' => (int) $tenant_id, $spec['id'] => (int) $source_id))
            ->limit(1)->get($spec['table'])->row_array();
        if (!$row) {
            return array('ok' => FALSE, 'error' => 'not_found');
        }
        $status = isset($row['status']) ? (string) $row['status'] : '';
        if (!in_array($status, array('approved', 'posted', 'completed'), TRUE)) {
            return array('ok' => FALSE, 'error' => 'status');
        }
        $pending = $this->db->where(array(
            'tenant_id' => (int) $tenant_id, 'source_type' => $source_type,
            'source_id' => (int) $source_id, 'status' => 'pending',
        ))->count_all_results('fin_cancellation');
        if ($pending > 0) {
            return array('ok' => FALSE, 'error' => 'pending');
        }
        if ($this->linked_payments_exist($tenant_id, $source_type, (int) $source_id)) {
            return array('ok' => FALSE, 'error' => 'has_payments');
        }
        $period_status = $this->reversal_period_status($tenant_id, $source_type, (int) $source_id, $status);
        if ($period_status !== 'ok') {
            return array('ok' => FALSE, 'error' => $period_status);
        }
        $balance_check = $this->validate_reversal_balance($tenant_id, $source_type, (int) $source_id, $status);
        if ($balance_check !== 'ok') {
            return array('ok' => FALSE, 'error' => $balance_check);
        }
        $flow_code = $this->cancel_flow($menu_code);
        if ($flow_code === '') {
            return array('ok' => FALSE, 'error' => 'no_flow');
        }
        $CI =& get_instance();
        $CI->load->library('approval_workflow_store');
        $this->db->trans_begin();
        $inserted = $this->db->insert('fin_cancellation', array(
            'tenant_id' => (int) $tenant_id,
            'source_type' => $source_type,
            'source_id' => (int) $source_id,
            'menu_code' => (string) $menu_code,
            'previous_status' => $status,
            'status' => 'pending',
            'reason' => $reason,
            'requested_by' => (int) $user_id,
            'requested_on' => date('Y-m-d H:i:s'),
        ));
        $cancellation_id = (int) $this->db->insert_id();
        if ($inserted !== TRUE || $cancellation_id < 1) {
            $this->db->trans_rollback();
            return array('ok' => FALSE, 'error' => 'save');
        }
        $result = $CI->approval_workflow_store->submit(
            $flow_code,
            'Pembatalan ' . $this->label($source_type, $row),
            $reason,
            $source_type,
            (int) $source_id,
            (int) $user_id,
            'cancel'
        );
        if (!is_array($result) || empty($result['request_id'])) {
            $this->db->trans_rollback();
            return array('ok' => FALSE, 'error' => 'approval');
        }
        $request_id = (int) $result['request_id'];
        $this->db->where('cancellation_id', $cancellation_id)->update('fin_cancellation', array(
            'approval_request_id' => $request_id,
        ));
        // A flow without approver steps is approved automatically. Finalize it here
        // because the cancellation row is linked to the request only after submit().
        if (isset($result['status']) && $result['status'] === 'auto_approved') {
            if ($this->finalize_for_request($request_id, 'approved') === FALSE) {
                $balance_check = $this->validate_reversal_balance($tenant_id, $source_type, (int) $source_id, $status);
                $this->db->trans_rollback();
                return array('ok' => FALSE, 'error' => $balance_check !== 'ok' ? $balance_check : 'approval');
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return array('ok' => FALSE, 'error' => 'approval');
            }
            $this->db->trans_commit();
            return array('ok' => TRUE, 'error' => '', 'request_id' => $request_id, 'auto_approved' => TRUE);
        }
        $this->db->where(array('tenant_id' => (int) $tenant_id, $spec['id'] => (int) $source_id))
            ->where('status', $status)
            ->update($spec['table'], array('status' => 'cancel_pending'));
        if ($this->db->trans_status() === FALSE || (int) $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return array('ok' => FALSE, 'error' => 'pending');
        }
        $this->db->trans_commit();
        return array('ok' => TRUE, 'error' => '', 'request_id' => $request_id);
    }

    /** Called from the approval workflow when a cancellation request is decided. */
    public function finalize_for_request($request_id, $decision)
    {
        if (!$this->available() || (int) $request_id < 1) { return TRUE; }
        $cancellation = $this->db->where('approval_request_id', (int) $request_id)
            ->limit(1)->get('fin_cancellation')->row_array();
        if (!$cancellation || !isset($this->sources[$cancellation['source_type']])) {
            return TRUE;
        }
        $spec = $this->sources[$cancellation['source_type']];
        $tenant_id = (int) $cancellation['tenant_id'];
        $source_id = (int) $cancellation['source_id'];
        $decision = (string) $decision;
        if ($decision === 'approved') {
            $this->db->trans_begin();
            if ($this->linked_payments_exist($tenant_id, (string) $cancellation['source_type'], $source_id)) {
                $this->db->trans_rollback();
                return FALSE;
            }
            $period_status = $this->reversal_period_status(
                $tenant_id,
                (string) $cancellation['source_type'],
                $source_id,
                (string) $cancellation['previous_status']
            );
            if ($period_status !== 'ok') {
                $this->db->trans_rollback();
                return FALSE;
            }
            $balance_check = $this->validate_reversal_balance(
                $tenant_id,
                (string) $cancellation['source_type'],
                $source_id,
                (string) $cancellation['previous_status']
            );
            if ($balance_check !== 'ok') {
                $this->db->trans_rollback();
                return FALSE;
            }
            $reversal_id = $this->create_reversal($tenant_id, $cancellation);
            if ($reversal_id === FALSE) {
                $this->db->trans_rollback();
                $this->db->where(array('tenant_id' => $tenant_id, $spec['id'] => $source_id))
                    ->where('status', 'cancel_pending')
                    ->update($spec['table'], array('status' => (string) $cancellation['previous_status']));
                $this->db->where('cancellation_id', (int) $cancellation['cancellation_id'])
                    ->update('fin_cancellation', array('status' => 'failed', 'decided_on' => date('Y-m-d H:i:s')));
                return FALSE;
            }
            $this->db->where(array('tenant_id' => $tenant_id, $spec['id'] => $source_id))
                ->update($spec['table'], array('status' => 'cancelled'));
            $this->db->where('cancellation_id', (int) $cancellation['cancellation_id'])->update('fin_cancellation', array(
                'status' => 'approved',
                'decided_on' => date('Y-m-d H:i:s'),
                'reversal_journal_id' => $reversal_id ? (int) $reversal_id : NULL,
            ));
            $this->sync_integration_inbox($tenant_id, (string) $cancellation['source_type'], $source_id);
            $this->db->trans_commit();
            return $this->db->trans_status() !== FALSE;
        }
        $this->db->where(array('tenant_id' => $tenant_id, $spec['id'] => $source_id))
            ->where('status', 'cancel_pending')
            ->update($spec['table'], array('status' => (string) $cancellation['previous_status']));
        $this->db->where('cancellation_id', (int) $cancellation['cancellation_id'])->update('fin_cancellation', array(
            'status' => $decision === 'revise' ? 'revise' : 'rejected',
            'decided_on' => date('Y-m-d H:i:s'),
        ));
        return TRUE;
    }

    public function pending_for_source($tenant_id, $source_type, $source_id)
    {
        if (!$this->available()) { return NULL; }
        $row = $this->db->where(array('tenant_id' => (int) $tenant_id, 'source_type' => $source_type,
            'source_id' => (int) $source_id, 'status' => 'pending'))->limit(1)->get('fin_cancellation')->row_array();
        return $row ? $row : NULL;
    }

    private function cancel_flow($menu_code)
    {
        $menu_code = trim((string) $menu_code);
        if ($menu_code === '') { return ''; }
        $query = $this->db->select('f.flow_code')
            ->from('sys_approval_flow f')
            ->join('sys_approval_flow_menu m', 'm.flow_code = f.flow_code', 'inner')
            ->where('m.menu_code', $menu_code)
            ->where('f.active_flag', 1)
            ->like('f.flow_code', 'cancel')
            ->order_by('f.flow_code', 'ASC')->limit(1)->get();
        if ($query !== FALSE && $query->num_rows()) {
            return (string) $query->row()->flow_code;
        }
        $fallback = $this->db->select('flow_code')->from('sys_approval_flow')
            ->where('flow_code', 'finance_cancel')->where('active_flag', 1)->limit(1)->get();
        return $fallback !== FALSE && $fallback->num_rows() ? 'finance_cancel' : '';
    }

    /** Keep integration inbox rows in sync with a cancelled draft document. */
    private function sync_integration_inbox($tenant_id, $source_type, $source_id)
    {
        $targets = array(
            'fin_sales_invoice' => array('table' => 'fin_sales_invoice_inbox', 'id' => 'sales_invoice_id'),
            'fin_purchase_bill' => array('table' => 'fin_purchase_bill_inbox', 'id' => 'purchase_bill_id'),
        );
        if (!isset($targets[$source_type])) {
            return;
        }
        $target = $targets[$source_type];
        if (!$this->db->table_exists($target['table'])
            || !$this->db->field_exists('cancelled_at', $target['table'])) {
            return;
        }
        $this->db->where(array(
            'tenant_id' => (int) $tenant_id,
            $target['id'] => (int) $source_id,
        ))
            ->where('status !=', 'cancelled')
            ->update($target['table'], array(
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
            ));
    }

    /** Invoices and bills cannot be cancelled while active payments are still allocated. */
    private function linked_payments_exist($tenant_id, $source_type, $source_id)
    {
        if (!isset($this->sources[$source_type])) {
            return FALSE;
        }
        $spec = $this->sources[$source_type];
        if (empty($spec['payment_table']) || empty($spec['payment_id']) || empty($spec['payment_allocation'])) {
            return FALSE;
        }
        $count = $this->db->from($spec['payment_allocation'] . ' a')
            ->join($spec['payment_table'] . ' p', 'p.' . $spec['payment_id'] . '=a.' . $spec['payment_id'] . ' AND p.tenant_id=a.tenant_id')
            ->where('a.tenant_id', (int) $tenant_id)
            ->where('a.' . $spec['id'], (int) $source_id)
            ->where_in('p.status', array('draft', 'pending_approval', 'approved', 'revise', 'posted'))
            ->count_all_results();
        return $count === FALSE ? TRUE : $count > 0;
    }

    /**
     * Reversal journals post in the current open period. If no period covers
     * today, fall back to the original journal date when that period is open.
     * This allows correcting prior periods without reopening them.
     */
    private function reversal_target($tenant_id, $original_date)
    {
        $tenant_id = (int) $tenant_id;
        $original_date = (string) $original_date;
        foreach (array(date('Y-m-d'), $original_date) as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $periods = $this->db->select('period_id,start_on,end_on')
                ->from('fin_accounting_period')
                ->where('tenant_id', $tenant_id)
                ->where('start_on <=', $candidate)
                ->where('end_on >=', $candidate)
                ->where('status', 'open')
                ->order_by('period_id', 'ASC')->get();
            if ($periods === FALSE) {
                continue;
            }
            if ($periods->num_rows() === 1) {
                return array('status' => 'ok', 'date' => $candidate, 'period_id' => (int) $periods->row()->period_id);
            }
            if ($periods->num_rows() > 1 && $candidate === date('Y-m-d')) {
                return array('status' => 'period_ambiguous', 'date' => $candidate, 'period_id' => 0);
            }
        }
        return array('status' => 'period_closed', 'date' => '', 'period_id' => 0);
    }

    /** Reversal journals must land in exactly one open accounting period. */
    private function reversal_period_status($tenant_id, $source_type, $source_id, $previous_status)
    {
        $context = $this->reversal_context($tenant_id, $source_type, $source_id, $previous_status);
        if (empty($context['needed']) || empty($context['original']['journal_date'])) {
            return 'ok';
        }
        $target = $this->reversal_target($tenant_id, (string) $context['original']['journal_date']);
        return $target['status'];
    }

    /** Validate that posting the reversal would not take a cash/bank account below zero. */
    private function validate_reversal_balance($tenant_id, $source_type, $source_id, $previous_status)
    {
        $context = $this->reversal_context($tenant_id, $source_type, $source_id, $previous_status);
        if (empty($context['needed'])) {
            return 'ok';
        }
        $CI =& get_instance();
        $CI->load->library('finance_balance_guard');
        return $CI->finance_balance_guard->validate_journal_lines($tenant_id, $context['lines']);
    }

    /** Collect the original journal and its mirrored reversal lines. */
    private function reversal_context($tenant_id, $source_type, $source_id, $previous_status)
    {
        if (!isset($this->sources[$source_type])
            || in_array((string) $previous_status, array('approved', 'draft', 'revise'), TRUE)) {
            return array('needed' => FALSE);
        }
        $spec = $this->sources[$source_type];
        if ($source_type === 'fin_journal_entry') {
            $original = $this->db->where(array('tenant_id' => $tenant_id, 'journal_entry_id' => $source_id))
                ->limit(1)->get('fin_journal_entry')->row_array();
        } else {
            $original = $this->db->where(array('tenant_id' => $tenant_id,
                'source_type' => $spec['journal_source'], 'source_id' => $source_id))
                ->limit(1)->get('fin_journal_entry')->row_array();
        }
        if (!$original || (string) $original['status'] !== 'posted') {
            return array('needed' => FALSE);
        }
        $original_id = (int) $original['journal_entry_id'];
        $existing = $this->db->where(array('tenant_id' => $tenant_id,
            'source_type' => 'reversal', 'source_id' => $original_id))
            ->limit(1)->get('fin_journal_entry')->row_array();
        if ($existing) {
            return array('needed' => FALSE, 'existing_id' => (int) $existing['journal_entry_id']);
        }
        $original_lines = $this->db->where(array('tenant_id' => $tenant_id, 'journal_entry_id' => $original_id))
            ->order_by('line_number', 'ASC')->get('fin_journal_line')->result_array();
        $lines = array();
        foreach ($original_lines as $line) {
            $lines[] = array(
                'account_id' => (int) $line['account_id'],
                'currency_code' => (string) $line['currency_code'],
                'debit_amount' => (float) $line['credit_amount'],
                'credit_amount' => (float) $line['debit_amount'],
            );
        }
        return array(
            'needed' => TRUE,
            'original' => $original,
            'original_lines' => $original_lines,
            'lines' => $lines,
        );
    }

    private function create_reversal($tenant_id, $cancellation)
    {
        $source_type = (string) $cancellation['source_type'];
        $source_id = (int) $cancellation['source_id'];
        $context = $this->reversal_context(
            $tenant_id,
            $source_type,
            $source_id,
            (string) $cancellation['previous_status']
        );
        if (empty($context['needed'])) {
            return isset($context['existing_id']) ? (int) $context['existing_id'] : NULL;
        }
        $original = $context['original'];
        $target = $this->reversal_target($tenant_id, (string) $original['journal_date']);
        if ($target['status'] !== 'ok') {
            return FALSE;
        }
        $original_id = (int) $original['journal_entry_id'];
        $number = 'CNL-' . substr((string) $original['journal_number'], 0, 40);
        $suffix = 1;
        while ($this->db->where(array('tenant_id' => $tenant_id, 'journal_number' => $number))
            ->count_all_results('fin_journal_entry') > 0) {
            $number = 'CNL-' . substr((string) $original['journal_number'], 0, 34) . '-' . $suffix++;
        }
        $now = date('Y-m-d H:i:s');
        $inserted = $this->db->insert('fin_journal_entry', array(
            'tenant_id' => $tenant_id,
            'period_id' => (int) $target['period_id'],
            'journal_number' => $number,
            'journal_date' => (string) $target['date'],
            'source_type' => 'reversal',
            'source_id' => $original_id,
            'description' => 'Reversal ' . (string) $original['journal_number'],
            'status' => 'posted',
            'debit_total' => (string) $original['credit_total'],
            'credit_total' => (string) $original['debit_total'],
            'create_on' => $now,
            'created_by' => (int) $cancellation['requested_by'],
            'posted_on' => $now,
            'posted_by' => (int) $cancellation['requested_by'],
            'reversed_entry_id' => $original_id,
        ));
        if ($inserted !== TRUE) { return FALSE; }
        $reversal_id = (int) $this->db->insert_id();
        $lines = $context['original_lines'];
        foreach ($lines as $line) {
            if (!$this->db->insert('fin_journal_line', array(
                'tenant_id' => $tenant_id,
                'journal_entry_id' => $reversal_id,
                'account_id' => (int) $line['account_id'],
                'department_id' => $line['department_id'],
                'line_number' => (int) $line['line_number'],
                'description' => 'Reversal ' . (string) $line['description'],
                'currency_code' => $line['currency_code'],
                'exchange_rate' => $line['exchange_rate'],
                'debit_amount' => $line['credit_amount'],
                'credit_amount' => $line['debit_amount'],
                'debit_base_amount' => $line['credit_base_amount'],
                'credit_base_amount' => $line['debit_base_amount'],
            ))) {
                return FALSE;
            }
        }
        $this->db->where(array('tenant_id' => $tenant_id, 'journal_entry_id' => $original_id))
            ->update('fin_journal_entry', array('reversed_entry_id' => $reversal_id));
        return $reversal_id;
    }

    private function label($source_type, $row)
    {
        foreach (array('transaction_number', 'invoice_number', 'bill_number', 'payment_number',
            'transfer_number', 'reconciliation_number', 'journal_number') as $field) {
            if (isset($row[$field]) && $row[$field] !== '') { return (string) $row[$field]; }
        }
        return $source_type . ' #' . (isset($row['id']) ? $row['id'] : '');
    }
}
