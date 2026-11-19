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
            'fin_settings' => array('tenant_id', $spec['control'], 'fx_gain_account_id', 'fx_loss_account_id',
                'rounding_tolerance', 'rounding_gain_account_id', 'rounding_loss_account_id'),
        );
        foreach ($checks as $table => $columns) {
            if (!$this->db->table_exists($table) || !is_array($fields = $this->db->list_fields($table)) || array_diff($columns, $fields)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function count($tenant, $kind, $search = '', $filters = array())
    {
        if (!$this->available($kind)) return FALSE;
        $spec = $this->spec($kind);
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        $this->db->from($spec['table'] . ' p')
            ->join($spec['allocation'] . ' al', 'al.' . $spec['id'] . '=p.' . $spec['id'] . ' AND al.tenant_id=p.tenant_id', 'left')
            ->join($spec['document_table'] . ' doc', 'doc.' . $spec['document_id'] . '=al.' . $spec['document_id'] . ' AND doc.tenant_id=p.tenant_id', 'left')
            ->join($spec['person_table'] . ' dp', 'dp.' . $spec['person_id'] . '=doc.' . $spec['person_id'] . ' AND dp.tenant_id=p.tenant_id', 'left')
            ->where('p.tenant_id', (int) $tenant);
        if (!empty($filters['start_on'])) $this->db->where('p.payment_date >=', $filters['start_on']);
        if (!empty($filters['end_on'])) $this->db->where('p.payment_date <=', $filters['end_on']);
        if (!empty($filters['status'])) $this->db->where('p.status', $filters['status']);
        if (!empty($fields['document_number'])) $this->db->like('doc.' . $spec['document_number'], $fields['document_number']);
        if (!empty($fields['document_integration'])) $this->db->like('doc.integration_number', $fields['document_integration']);
        if (!empty($fields['person_name'])) $this->db->like('dp.' . $spec['person_name'], $fields['person_name']);
        if (!empty($fields['currency_code'])) $this->db->where('p.currency_code', strtoupper((string) $fields['currency_code']));
        if (!empty($fields['amount']['min'])) $this->db->where('p.amount >=', $fields['amount']['min']);
        if (!empty($fields['amount']['max'])) $this->db->where('p.amount <=', $fields['amount']['max']);
        if (trim((string) $search) !== '') {
            $this->db->group_start()->like('p.payment_number', $search)->or_like('p.reference', $search)
                ->or_like('doc.' . $spec['document_number'], $search)->or_like('dp.' . $spec['person_name'], $search)
                ->group_end();
        }
        $n = $this->db->count_all_results();
        return $n === FALSE ? FALSE : (int) $n;
    }

    public function page($tenant, $kind, $search, $limit, $offset, $order, $direction, $filters = array())
    {
        if (!$this->available($kind)) return FALSE;
        $spec = $this->spec($kind);
        $fields = isset($filters['fields']) && is_array($filters['fields']) ? $filters['fields'] : array();
        $allowed = array('payment_number', 'payment_date', 'amount', 'currency_code', 'status', 'document_number', 'integration_number', 'document_integration_number', 'person_name');
        $order = in_array($order, $allowed, TRUE) ? $order : 'payment_date';
        $this->db->select('p.' . $spec['id'] . ',p.payment_number,p.payment_date,p.amount,p.currency_code,p.status,p.reference,p.approval_request_id,'
            . 'doc.' . $spec['document_number'] . ' AS document_number,doc.integration_number AS document_integration_number,'
            . 'dp.' . $spec['person_name'] . ' AS person_name,'
            . "COALESCE(NULLIF(u.full_name, ''), u.username) AS created_by_name,"
            . "COALESCE(NULLIF(au.full_name, ''), au.username) AS current_approver_name,"
            . "(SELECT a.note FROM app_approval_action a WHERE a.tenant_id = p.tenant_id AND a.request_id = p.approval_request_id AND a.decision = 'revise' ORDER BY a.action_id DESC LIMIT 1) AS revise_note", FALSE)
            ->from($spec['table'] . ' p')
            ->join($spec['allocation'] . ' al', 'al.' . $spec['id'] . '=p.' . $spec['id'] . ' AND al.tenant_id=p.tenant_id', 'left')
            ->join($spec['document_table'] . ' doc', 'doc.' . $spec['document_id'] . '=al.' . $spec['document_id'] . ' AND doc.tenant_id=p.tenant_id', 'left')
            ->join($spec['person_table'] . ' dp', 'dp.' . $spec['person_id'] . '=doc.' . $spec['person_id'] . ' AND dp.tenant_id=p.tenant_id', 'left')
            ->join('app_user u', 'u.user_id = p.created_by AND u.tenant_id = p.tenant_id', 'left')
            ->join('app_approval_request ar', 'ar.request_id = p.approval_request_id AND ar.tenant_id = p.tenant_id', 'left')
            ->join('app_user au', 'au.user_id = ar.current_approver_user_id AND au.tenant_id = ar.tenant_id', 'left')
            ->where('p.tenant_id', (int) $tenant);
        if (!empty($filters['start_on'])) $this->db->where('p.payment_date >=', $filters['start_on']);
        if (!empty($filters['end_on'])) $this->db->where('p.payment_date <=', $filters['end_on']);
        if (!empty($filters['status'])) $this->db->where('p.status', $filters['status']);
        if (!empty($fields['document_number'])) $this->db->like('doc.' . $spec['document_number'], $fields['document_number']);
        if (!empty($fields['document_integration'])) $this->db->like('doc.integration_number', $fields['document_integration']);
        if (!empty($fields['person_name'])) $this->db->like('dp.' . $spec['person_name'], $fields['person_name']);
        if (!empty($fields['currency_code'])) $this->db->where('p.currency_code', strtoupper((string) $fields['currency_code']));
        if (!empty($fields['amount']['min'])) $this->db->where('p.amount >=', $fields['amount']['min']);
        if (!empty($fields['amount']['max'])) $this->db->where('p.amount <=', $fields['amount']['max']);
        if (trim((string) $search) !== '') {
            $this->db->group_start()->like('p.payment_number', $search)->or_like('p.reference', $search)
                ->or_like('doc.' . $spec['document_number'], $search)->or_like('dp.' . $spec['person_name'], $search)
                ->group_end();
        }
        $order_column = in_array($order, array('created_by_name'), TRUE) ? $order
            : ($order === 'document_number' ? 'doc.' . $spec['document_number']
                : (in_array($order, array('integration_number', 'document_integration_number'), TRUE) ? 'doc.integration_number'
                    : ($order === 'person_name' ? 'dp.' . $spec['person_name'] : 'p.' . $order)));
        $query = $this->db->order_by($order_column, strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC')
            ->order_by('p.' . $spec['id'], 'DESC')->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))->get();
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
        $settings = $this->db->where('tenant_id', (int) $tenant)->limit(1)->get('fin_settings');
        if ($settings === FALSE || !$settings->num_rows()) return 'settings_missing';
        $settings_row = $settings->row_array();
        $tolerance = max(0, round((float) $settings_row['rounding_tolerance'], 6));
        $base_currency = strtoupper((string) $settings_row['base_currency']);
        if ($doc['currency_code'] === $base_currency && abs($rate - 1) > 0.0000001) return 'exchange_rate_invalid';

        $this->db->trans_begin();
        // Lock the document so two concurrent drafts cannot reserve the same balance.
        $locked = $this->db->query(
            'SELECT * FROM `' . $spec['document_table'] . '` WHERE tenant_id = ? AND '
            . $spec['document_id'] . ' = ? LIMIT 1 FOR UPDATE',
            array((int) $tenant, (int) $values['document_id'])
        );
        if ($locked === FALSE || !$locked->num_rows()) {
            $this->db->trans_rollback();
            return 'document_invalid';
        }
        $doc = $locked->row_array();
        if ($doc['status'] !== 'posted') {
            $this->db->trans_rollback();
            return 'document_invalid';
        }
        $posted = $this->db->select('COALESCE(SUM(a.amount),0) AS allocated', FALSE)
            ->from($spec['allocation'] . ' a')
            ->join($spec['table'] . ' p', 'p.' . $spec['id'] . '=a.' . $spec['id'] . ' AND p.tenant_id=a.tenant_id')
            ->where('a.tenant_id', (int) $tenant)
            ->where('a.' . $spec['document_id'], (int) $values['document_id'])
            ->where('p.status', 'posted')->get();
        // Draft, revise, pending, and approved payments reserve the document balance
        // so two unposted payments cannot be allocated to the same outstanding amount.
        $reserved = $this->db->select('COALESCE(SUM(a.amount),0) AS allocated', FALSE)
            ->from($spec['allocation'] . ' a')
            ->join($spec['table'] . ' p', 'p.' . $spec['id'] . '=a.' . $spec['id'] . ' AND p.tenant_id=a.tenant_id')
            ->where('a.tenant_id', (int) $tenant)
            ->where('a.' . $spec['document_id'], (int) $values['document_id'])
            ->where_in('p.status', array('draft', 'revise', 'pending_approval', 'approved'));
        if ($id !== NULL) {
            $reserved->where('p.' . $spec['id'] . ' !=', (int) $id);
        }
        $reserved = $reserved->get();
        if ($posted === FALSE || $reserved === FALSE) {
            $this->db->trans_rollback();
            return 'unavailable';
        }
        $outstanding = round((float) $doc['total_amount'] - (float) $posted->row()->allocated - (float) $reserved->row()->allocated, 6);
        if ($outstanding <= 0.000001) {
            $this->db->trans_rollback();
            return 'overpayment';
        }
        if ($amount > $outstanding + $tolerance + 0.000001) {
            $this->db->trans_rollback();
            return 'overpayment';
        }
        $applied = $amount > $outstanding ? $outstanding : $amount;
        $rounding = round($amount - $applied, 6);
        if ($rounding > 0.000001) {
            $rounding_account = $kind === 'customer' ? 'rounding_gain_account_id' : 'rounding_loss_account_id';
            if (empty($settings_row[$rounding_account])) {
                $this->db->trans_rollback();
                return 'rounding_account_missing';
            }
        }
        $payment_base = round($amount * $rate, 6);
        $allocation_base = round($applied * (float) $doc['exchange_rate'], 6);

        if ($id !== NULL) {
            $existing = $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->limit(1)->get($spec['table']);
            if ($existing === FALSE || !$existing->num_rows() || !in_array((string) $existing->row()->status, array('draft', 'revise'), TRUE)) {
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
            $CI =& get_instance();
            $CI->load->library('application_sequence_store');
            $sequence_code = $kind === 'customer' ? 'customer_payment_code' : 'supplier_payment_code';
            $next_code = $CI->application_sequence_store->next_code_for_tenant($tenant, $sequence_code);
            if (!is_array($next_code) || !isset($next_code['code']) || $next_code['code'] === '') {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
            for ($attempt = 0; $attempt < 10 && $this->payment_number_exists($tenant, $spec['table'], (string) $next_code['code']); $attempt++) {
                $next_code = $CI->application_sequence_store->next_code_after($next_code);
                if (!is_array($next_code) || !isset($next_code['code'])) {
                    $this->db->trans_rollback();
                    return 'sequence_unavailable';
                }
            }
            if ($this->payment_number_exists($tenant, $spec['table'], (string) $next_code['code'])) {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
            $this->db->insert($spec['table'], array(
                'tenant_id' => (int) $tenant,
                $spec['person_id'] => (int) $doc[$spec['person_id']],
                'cash_account_id' => (int) $cash['cash_account_id'],
                'payment_number' => (string) $next_code['code'],
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
            if ($payment_id < 1 || !$CI->application_sequence_store->advance_sequence($tenant, $sequence_code, $next_code)) {
                $this->db->trans_rollback();
                return 'sequence_unavailable';
            }
        }
        $this->db->insert($spec['allocation'], array(
            'tenant_id' => (int) $tenant,
            $spec['id'] => $payment_id,
            $spec['document_id'] => (int) $doc[$spec['document_id']],
            'amount' => $applied,
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
        if ($payment['status'] !== 'approved') return 'not_postable';
        $alloc_q = $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->limit(1)->get($spec['allocation']);
        if ($alloc_q === FALSE || !$alloc_q->num_rows()) return 'allocation_invalid';
        $alloc = $alloc_q->row_array();
        $doc = $this->tenant_row($spec['document_table'], $spec['document_id'], $tenant, $alloc[$spec['document_id']]);
        if (!$doc || $doc['status'] !== 'posted' || $doc['currency_code'] !== $payment['currency_code']
            || (int) $doc[$spec['person_id']] !== (int) $payment[$spec['person_id']]) return 'document_invalid';
        $settings_q = $this->db->where('tenant_id', (int) $tenant)->limit(1)->get('fin_settings');
        if ($settings_q === FALSE || !$settings_q->num_rows()) return 'settings_missing';
        $settings = $settings_q->row_array();
        $tolerance = max(0, round((float) $settings['rounding_tolerance'], 6));
        $payment_amount = round((float) $payment['amount'], 6);
        $applied_amount = round((float) $alloc['amount'], 6);
        $rounding = round($payment_amount - $applied_amount, 6);
        if ($rounding < -0.000001) return 'allocation_invalid';
        if ($rounding > $tolerance + 0.000001) return 'overpayment';
        $applied_payment_base = round($applied_amount * (float) $payment['exchange_rate'], 6);
        $rounding_base = round((float) $payment['base_amount'] - $applied_payment_base, 6);
        $control_id = (int) $settings[$spec['control']];
        $control = $this->tenant_row('fin_account', 'account_id', $tenant, $control_id);
        $cash = $this->tenant_row('fin_cash_account', 'cash_account_id', $tenant, $payment['cash_account_id']);
        $cash_gl = $cash ? $this->tenant_row('fin_account', 'account_id', $tenant, $cash['account_id']) : NULL;
        $control_type = $kind === 'customer' ? 'asset' : 'liability';
        if (!$control || !$cash || !$cash_gl || $control['account_type'] !== $control_type
            || $cash_gl['account_type'] !== 'asset'
            || (int) $control['active_flag'] !== 1 || (int) $control['posting_flag'] !== 1 || (int) $cash['active_flag'] !== 1
            || (int) $cash_gl['active_flag'] !== 1 || (int) $cash_gl['posting_flag'] !== 1
            || $cash['currency_code'] !== $payment['currency_code']) return 'account_invalid';
        $fx_difference = round($applied_payment_base - (float) $alloc['base_amount'], 6);
        $fx_account = NULL;
        if (abs($fx_difference) > 0.000001) {
            $fx_id = $kind === 'customer'
                ? (int) ($fx_difference > 0 ? $settings['fx_gain_account_id'] : $settings['fx_loss_account_id'])
                : (int) ($fx_difference > 0 ? $settings['fx_loss_account_id'] : $settings['fx_gain_account_id']);
            $fx_account = $this->tenant_row('fin_account', 'account_id', $tenant, $fx_id);
            $gain = ($kind === 'customer') ? $fx_difference > 0 : $fx_difference < 0;
            $expected_fx_type = $gain ? 'revenue' : 'expense';
            if (!$fx_account || (int) $fx_account['active_flag'] !== 1 || (int) $fx_account['posting_flag'] !== 1 || $fx_account['account_type'] !== $expected_fx_type) return 'fx_account_missing';
        }
        $rounding_account = NULL;
        if (abs($rounding_base) > 0.000001) {
            $rounding_field = $kind === 'customer' ? 'rounding_gain_account_id' : 'rounding_loss_account_id';
            $rounding_account = $this->tenant_row('fin_account', 'account_id', $tenant, (int) $settings[$rounding_field]);
            $expected_rounding_type = $kind === 'customer' ? 'revenue' : 'expense';
            if (!$rounding_account || (int) $rounding_account['active_flag'] !== 1
                || (int) $rounding_account['posting_flag'] !== 1
                || $rounding_account['account_type'] !== $expected_rounding_type) return 'rounding_account_missing';
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
        if ($rounding_account && abs($rounding_base) > 0.000001) {
            if ($kind === 'customer') $credit += abs($rounding_base); else $debit += abs($rounding_base);
        }
        $this->db->trans_begin();
        $locked = $this->db->query(
            'SELECT * FROM `' . $spec['document_table'] . '` WHERE tenant_id = ? AND '
            . $spec['document_id'] . ' = ? LIMIT 1 FOR UPDATE',
            array((int) $tenant, (int) $doc[$spec['document_id']])
        );
        if ($locked === FALSE || !$locked->num_rows()) {
            $this->db->trans_rollback();
            return 'document_invalid';
        }
        $doc = $locked->row_array();
        if ($doc['status'] !== 'posted' || $doc['currency_code'] !== $payment['currency_code']) {
            $this->db->trans_rollback();
            return 'document_invalid';
        }
        $posted_allocations = $this->db->select('COALESCE(SUM(a.amount),0) AS allocated', FALSE)
            ->from($spec['allocation'] . ' a')
            ->join($spec['table'] . ' p', 'p.' . $spec['id'] . '=a.' . $spec['id'] . ' AND p.tenant_id=a.tenant_id')
            ->where('a.tenant_id', (int) $tenant)->where('a.' . $spec['document_id'], (int) $doc[$spec['document_id']])
            ->where('p.status', 'posted')->get();
        $active_reserved = $this->db->select('COALESCE(SUM(a.amount),0) AS allocated', FALSE)
            ->from($spec['allocation'] . ' a')
            ->join($spec['table'] . ' p', 'p.' . $spec['id'] . '=a.' . $spec['id'] . ' AND p.tenant_id=a.tenant_id')
            ->where('a.tenant_id', (int) $tenant)->where('a.' . $spec['document_id'], (int) $doc[$spec['document_id']])
            ->where_in('p.status', array('draft', 'revise', 'pending_approval', 'approved'))
            ->where('p.' . $spec['id'] . ' !=', (int) $id)->get();
        if ($posted_allocations === FALSE || $active_reserved === FALSE
            || (float) $alloc['amount'] > (float) $doc['total_amount'] - (float) $posted_allocations->row()->allocated - (float) $active_reserved->row()->allocated + 0.000001) {
            $this->db->trans_rollback();
            return 'overpayment';
        }
        if ($kind === 'supplier') {
            $CI =& get_instance();
            $CI->load->library('finance_balance_guard');
            $balance_check = $CI->finance_balance_guard->can_debit(
                $tenant,
                (int) $payment['cash_account_id'],
                (float) $payment['amount'],
                (string) $payment['currency_code']
            );
            if ($balance_check !== 'ok') {
                $this->db->trans_rollback();
                return $balance_check;
            }
        }
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
        if ($rounding_account && abs($rounding_base) > 0.000001) {
            $gain = $kind === 'customer';
            $base_lines[] = array('account_id' => (int) $rounding_account['account_id'], 'debit_amount' => $gain ? 0 : abs($rounding_base), 'credit_amount' => $gain ? abs($rounding_base) : 0, 'debit_base_amount' => $gain ? 0 : abs($rounding_base), 'credit_base_amount' => $gain ? abs($rounding_base) : 0, 'currency_code' => $settings['base_currency'], 'exchange_rate' => 1, 'description' => 'Rounding difference');
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
        $this->db->where('tenant_id', (int) $tenant)->where($spec['id'], (int) $id)->where('status', 'approved')
            ->update($spec['table'], array('status' => 'posted', 'posted_on' => date('Y-m-d H:i:s'), 'posted_by' => (int) $user));
        if ($journal_id < 1 || $this->db->trans_status() === FALSE || (int) $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return 'post_error';
        }
        $this->db->trans_commit();
        return 'posted';
    }

    /** Posted, in-process, and outstanding amounts for one invoice/bill. */
    public function document_financials($tenant, $kind, $document_id, $exclude_payment_id = NULL)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind) || (int) $document_id < 1) {
            return NULL;
        }
        $document = $this->tenant_row($spec['document_table'], $spec['document_id'], $tenant, $document_id);
        if (!$document) {
            return NULL;
        }
        $totals = $this->allocation_totals($tenant, $kind, array((int) $document_id), $exclude_payment_id);
        $by_status = isset($totals[(int) $document_id]) ? $totals[(int) $document_id] : array();
        $paid = 0.0;
        $pending = 0.0;
        foreach ($by_status as $status => $amount) {
            if ($status === 'posted') {
                $paid += (float) $amount;
            } elseif (in_array($status, array('draft', 'pending_approval', 'approved', 'revise'), TRUE)) {
                $pending += (float) $amount;
            }
        }
        $total = (float) $document['total_amount'];
        return array(
            'document_id' => (int) $document_id,
            'currency_code' => (string) $document['currency_code'],
            'total_amount' => $total,
            'paid_amount' => round($paid, 6),
            'pending_amount' => round($pending, 6),
            'outstanding_amount' => round($total - $paid, 6),
            'available_amount' => round($total - $paid - $pending, 6),
        );
    }

    /** Allocation totals grouped by document and payment status. */
    private function allocation_totals($tenant, $kind, $document_ids, $exclude_payment_id = NULL)
    {
        $spec = $this->spec($kind);
        $totals = array();
        if ($spec === NULL || !is_array($document_ids) || !$document_ids) {
            return $totals;
        }
        $document_ids = array_values(array_unique(array_map('intval', $document_ids)));
        $query = $this->db->select(
            'a.' . $spec['document_id'] . ' AS document_id,p.status,COALESCE(SUM(a.amount),0) AS amount',
            FALSE
        )
            ->from($spec['allocation'] . ' a')
            ->join($spec['table'] . ' p', 'p.' . $spec['id'] . '=a.' . $spec['id'] . ' AND p.tenant_id=a.tenant_id')
            ->where('a.tenant_id', (int) $tenant)
            ->where_in('a.' . $spec['document_id'], $document_ids);
        if ($exclude_payment_id !== NULL && (int) $exclude_payment_id > 0) {
            $this->db->where('p.' . $spec['id'] . ' !=', (int) $exclude_payment_id);
        }
        $query = $this->db->group_by(array('a.' . $spec['document_id'], 'p.status'))
            ->get();
        if ($query === FALSE) {
            return $totals;
        }
        foreach ($query->result_array() as $row) {
            $id = (int) $row['document_id'];
            if (!isset($totals[$id])) {
                $totals[$id] = array();
            }
            $totals[$id][(string) $row['status']] = (float) $row['amount'];
        }
        return $totals;
    }

    private function posted_paid_sql($spec, $alias)
    {
        return '(SELECT COALESCE(SUM(a.amount),0) FROM `' . $spec['allocation'] . '` a'
            . ' INNER JOIN `' . $spec['table'] . '` p'
            . ' ON p.' . $spec['id'] . '=a.' . $spec['id'] . ' AND p.tenant_id=a.tenant_id'
            . ' WHERE a.tenant_id=' . $alias . '.tenant_id AND a.' . $spec['document_id'] . '=' . $alias . '.' . $spec['document_id']
            . " AND p.status='posted')";
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
        $paid_sql = $this->posted_paid_sql($spec, 'd');
        $this->db->select('d.' . $spec['document_id'] . ',d.' . $spec['document_number'] . ',d.integration_number,d.currency_code,d.total_amount', FALSE)
            ->from($spec['document_table'] . ' d')
            ->where('d.tenant_id', (int) $tenant)->where('d.status', 'posted')
            ->where('d.total_amount - ' . $paid_sql . ' > 0.000001', NULL, FALSE);
        if ($term !== '') {
            $this->db->group_start()->like('d.' . $spec['document_number'], $term)
                ->or_like('d.integration_number', $term)->group_end();
        }
        $query = $this->db->order_by('d.' . $spec['document_number'], 'ASC')->limit(20)->get();
        if ($query === FALSE) {
            return array();
        }
        $rows = $query->result_array();
        $document_ids = array();
        foreach ($rows as $row) {
            $document_ids[] = (int) $row[$spec['document_id']];
        }
        $totals = $this->allocation_totals($tenant, $kind, $document_ids);
        $results = array();
        foreach ($rows as $row) {
            $label = $row[$spec['document_number']];
            if (!empty($row['integration_number'])) {
                $label .= ' — ' . $row['integration_number'];
            }
            $label .= ' — ' . $row['currency_code'] . ' ' . number_format((float) $row['total_amount'], 2, '.', '');
            $paid = 0.0;
            $pending = 0.0;
            $by_status = isset($totals[(int) $row[$spec['document_id']]]) ? $totals[(int) $row[$spec['document_id']]] : array();
            foreach ($by_status as $status => $amount) {
                if ($status === 'posted') {
                    $paid += (float) $amount;
                } elseif (in_array($status, array('draft', 'pending_approval', 'approved', 'revise'), TRUE)) {
                    $pending += (float) $amount;
                }
            }
            $outstanding = round((float) $row['total_amount'] - $paid, 6);
            if ($outstanding <= 0.000001) {
                continue;
            }
            $results[] = array(
                'id' => (int) $row[$spec['document_id']],
                'label' => $label,
                'value' => $label,
                'currency_code' => $row['currency_code'],
                'total_amount' => (float) $row['total_amount'],
                'paid_amount' => round($paid, 6),
                'pending_amount' => round($pending, 6),
                'outstanding_amount' => $outstanding,
                'available_amount' => round($outstanding - $pending, 6),
            );
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
        $query = $this->db->select($spec['document_number'] . ',integration_number,currency_code')->where('tenant_id', (int) $tenant)
            ->where($spec['document_id'], (int) $id)->limit(1)->get($spec['document_table']);
        if ($query === FALSE || !$query->num_rows()) {
            return '';
        }
        $row = $query->row_array();
        $label = (string) $row[$spec['document_number']];
        if (!empty($row['integration_number'])) {
            $label .= ' — ' . (string) $row['integration_number'];
        }
        return $label . ' — ' . (string) $row['currency_code'];
    }

    private function payment_number_exists($tenant, $table, $number)
    {
        return $this->db->from($table)->where('tenant_id', (int) $tenant)
            ->where('payment_number', (string) $number)->count_all_results() > 0;
    }

    private function spec($kind)
    {
        if ($kind === 'customer') return array('table'=>'fin_customer_payment','id'=>'customer_payment_id','allocation'=>'fin_customer_payment_allocation','document_table'=>'fin_sales_invoice','document_id'=>'sales_invoice_id','document_number'=>'invoice_number','person_id'=>'customer_id','person_table'=>'fin_customer','person_code'=>'customer_code','person_name'=>'customer_name','control'=>'receivable_account_id','source_type'=>'customer_payment');
        if ($kind === 'supplier') return array('table'=>'fin_supplier_payment','id'=>'supplier_payment_id','allocation'=>'fin_supplier_payment_allocation','document_table'=>'fin_purchase_bill','document_id'=>'purchase_bill_id','document_number'=>'bill_number','person_id'=>'supplier_id','person_table'=>'fin_supplier','person_code'=>'supplier_code','person_name'=>'supplier_name','control'=>'payable_account_id','source_type'=>'supplier_payment');
        return NULL;
    }

    private function tenant_row($table, $id_column, $tenant, $id)
    {
        $query = $this->db->where('tenant_id', (int) $tenant)->where($id_column, (int) $id)->limit(1)->get($table);
        return $query !== FALSE && $query->num_rows() ? $query->row_array() : NULL;
    }
}
