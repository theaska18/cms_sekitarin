<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Prevents posted cash/bank journals from taking an account below zero. */
class Finance_balance_guard
{
    private $db;

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            if (!isset($CI->db)) {
                $CI->load->database();
            }
            $db = $CI->db;
        }
        $this->db = $db;
    }

    /** Lock one cash account row for the remainder of the posting transaction. */
    public function lock_account($tenant_id, $cash_account_id)
    {
        $query = $this->db->query(
            'SELECT cash_account_id, account_id, currency_code, active_flag '
            . 'FROM fin_cash_account WHERE tenant_id = ? AND cash_account_id = ? LIMIT 1 FOR UPDATE',
            array((int) $tenant_id, (int) $cash_account_id)
        );
        if ($query === FALSE || $query->num_rows() !== 1) {
            return FALSE;
        }
        return $query->row_array();
    }

    /** Posted balance for a cash account in its own currency. */
    public function balance($tenant_id, $account_id, $currency_code)
    {
        $query = $this->db->query(
            'SELECT COALESCE(SUM(jl.debit_amount - jl.credit_amount), 0) AS balance '
            . 'FROM fin_journal_entry je '
            . 'INNER JOIN fin_journal_line jl ON jl.journal_entry_id = je.journal_entry_id AND jl.tenant_id = je.tenant_id '
            . 'WHERE je.tenant_id = ? AND je.status = \'posted\' AND jl.account_id = ? AND jl.currency_code = ?',
            array((int) $tenant_id, (int) $account_id, (string) $currency_code)
        );
        if ($query === FALSE) {
            return FALSE;
        }
        return (float) $query->row()->balance;
    }

    /**
     * Validate an outgoing cash amount. Caller must already own the posting
     * transaction, but this method locks the cash account row itself.
     */
    public function can_debit($tenant_id, $cash_account_id, $amount, $currency_code = NULL)
    {
        $account = $this->lock_account($tenant_id, $cash_account_id);
        if (!is_array($account) || (int) $account['active_flag'] !== 1) {
            return 'cash_account_invalid';
        }
        $currency = $currency_code !== NULL && $currency_code !== ''
            ? strtoupper((string) $currency_code)
            : strtoupper((string) $account['currency_code']);
        if ($currency !== strtoupper((string) $account['currency_code'])) {
            return 'currency_mismatch';
        }
        $balance = $this->balance($tenant_id, (int) $account['account_id'], $currency);
        if ($balance === FALSE) {
            return 'balance_unavailable';
        }
        $amount = round((float) $amount, 6);
        if ($balance - $amount < -0.000001) {
            return 'insufficient_balance';
        }
        return 'ok';
    }

    /**
     * Validate a manual journal's cash lines. Lines are grouped by cash GL
     * account and currency; any net credit group must be covered by its balance.
     *
     * Each line needs account_id, currency_code, debit_amount, credit_amount.
     */
    public function validate_journal_lines($tenant_id, $lines)
    {
        if (!is_array($lines) || !$lines) {
            return 'ok';
        }
        $deltas = array();
        foreach ($lines as $line) {
            if (!is_array($line) || empty($line['account_id'])) {
                continue;
            }
            $account_id = (int) $line['account_id'];
            $currency = strtoupper(isset($line['currency_code']) ? (string) $line['currency_code'] : '');
            if ($currency === '') {
                continue;
            }
            $key = $account_id . '|' . $currency;
            if (!isset($deltas[$key])) {
                $deltas[$key] = array('account_id' => $account_id, 'currency' => $currency, 'delta' => 0.0);
            }
            $deltas[$key]['delta'] += (float) $line['debit_amount'] - (float) $line['credit_amount'];
        }

        $account_ids = array_values(array_unique(array_column($deltas, 'account_id')));
        if (!$account_ids) {
            return 'ok';
        }
        $cash_rows = $this->db->select('cash_account_id, account_id, currency_code')
            ->from('fin_cash_account')->where('tenant_id', (int) $tenant_id)
            ->where_in('account_id', $account_ids)->order_by('cash_account_id', 'ASC')->get();
        if ($cash_rows === FALSE) {
            return 'balance_unavailable';
        }
        $cash_accounts = array();
        foreach ($cash_rows->result_array() as $row) {
            $cash_accounts[$row['account_id'] . '|' . strtoupper((string) $row['currency_code'])] = $row;
        }
        foreach ($deltas as $key => $delta) {
            if ($delta['delta'] >= -0.000001 || !isset($cash_accounts[$key])) {
                continue;
            }
            $cash = $cash_accounts[$key];
            $locked = $this->lock_account($tenant_id, (int) $cash['cash_account_id']);
            if (!is_array($locked)) {
                return 'cash_account_invalid';
            }
            $balance = $this->balance($tenant_id, (int) $locked['account_id'], $delta['currency']);
            if ($balance === FALSE) {
                return 'balance_unavailable';
            }
            if ($balance + $delta['delta'] < -0.000001) {
                return 'insufficient_balance';
            }
        }
        return 'ok';
    }
}
