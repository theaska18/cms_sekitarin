<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Read-only tenant-scoped reports derived from posted Finance journals/documents. */
class Finance_report_store
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

    public function available($report)
    {
        $required = array(
            'fin_journal_entry' => array('journal_entry_id', 'tenant_id', 'journal_date', 'status'),
            'fin_journal_line' => array('tenant_id', 'journal_entry_id', 'account_id', 'debit_base_amount', 'credit_base_amount'),
            'fin_account' => array('tenant_id', 'account_id', 'account_code', 'account_name', 'account_type'),
        );
        if (in_array($report, array('ar_report', 'ap_report'), TRUE)) {
            $required[$report === 'ar_report' ? 'fin_sales_invoice' : 'fin_purchase_bill'] = $report === 'ar_report'
                ? array('tenant_id', 'sales_invoice_id', 'customer_id', 'invoice_number', 'invoice_date', 'due_date', 'currency_code', 'base_total_amount', 'status')
                : array('tenant_id', 'purchase_bill_id', 'supplier_id', 'bill_number', 'bill_date', 'due_date', 'currency_code', 'base_total_amount', 'status');
        }
        if ($report === 'ar_report') {
            $required['fin_customer'] = array('tenant_id', 'customer_id', 'customer_name');
            $required['fin_customer_payment_allocation'] = array('tenant_id', 'sales_invoice_id', 'base_amount');
            $required['fin_customer_payment'] = array('tenant_id', 'customer_payment_id', 'status');
        } elseif ($report === 'ap_report') {
            $required['fin_supplier'] = array('tenant_id', 'supplier_id', 'supplier_name');
            $required['fin_supplier_payment_allocation'] = array('tenant_id', 'purchase_bill_id', 'base_amount');
            $required['fin_supplier_payment'] = array('tenant_id', 'supplier_payment_id', 'status');
        } elseif (in_array($report, array('cash_flow', 'cash_flow_detail', 'bank_balance'), TRUE)) {
            $required['fin_cash_account'] = array('tenant_id', 'cash_account_id', 'account_id', 'account_code', 'account_name', 'currency_code', 'active_flag');
        } elseif ($report === 'cancellation_history') {
            $required['fin_cancellation'] = array('tenant_id', 'source_type', 'source_id', 'previous_status', 'status', 'reason', 'requested_by', 'requested_on', 'decided_on', 'reversal_journal_id');
            $required['app_user'] = array('tenant_id', 'user_id', 'full_name', 'username');
        }
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

    public function dashboard($tenant_id)
    {
        if (!$this->available('trial_balance') || !$this->db->table_exists('fin_settings')
            || !$this->db->table_exists('fin_cash_account')) {
            return FALSE;
        }
        $tenant_id = (int) $tenant_id;
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)
            ->limit(1)->get('fin_settings');
        $summary = $this->db->select('a.account_type, SUM(jl.debit_base_amount) AS debit_total, SUM(jl.credit_base_amount) AS credit_total')
            ->from('fin_journal_entry je')
            ->join('fin_journal_line jl', 'jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id')
            ->join('fin_account a', 'a.account_id=jl.account_id AND a.tenant_id=jl.tenant_id')
            ->where('je.tenant_id', $tenant_id)->where('je.status', 'posted')
            ->where('je.journal_date >=', date('Y-01-01'))->where('je.journal_date <=', date('Y-m-d'))
            ->group_by('a.account_type')->get();
        $cash = $this->db->select('COALESCE(SUM(jl.debit_base_amount-jl.credit_base_amount),0) AS balance', FALSE)
            ->from('fin_journal_entry je')
            ->join('fin_journal_line jl', 'jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id')
            ->join('fin_cash_account ca', 'ca.account_id=jl.account_id AND ca.tenant_id=jl.tenant_id')
            ->where('je.tenant_id', $tenant_id)->where('je.status', 'posted')->get();
        $recent = $this->db->select('journal_entry_id,journal_date,journal_number,description,debit_total,credit_total')
            ->where('tenant_id', $tenant_id)->where('status', 'posted')
            ->order_by('journal_date', 'DESC')->order_by('journal_entry_id', 'DESC')->limit(5)
            ->get('fin_journal_entry');
        $count = $this->db->where('tenant_id', $tenant_id)->where('status', 'posted')->count_all_results('fin_journal_entry');
        if ($settings === FALSE || $summary === FALSE || $cash === FALSE || $recent === FALSE || $count === FALSE) {
            return FALSE;
        }
        $recent_rows = $recent->result_array();
        $recent_lines = array();
        $journal_ids = array_map('intval', array_column($recent_rows, 'journal_entry_id'));
        if ($journal_ids) {
            $line_query = $this->db->select('je.journal_entry_id,je.journal_date,je.journal_number,je.description AS journal_description,'
                . 'jl.line_number,jl.description AS line_description,jl.debit_base_amount,jl.credit_base_amount,'
                . 'a.account_code,a.account_name', FALSE)
                ->from('fin_journal_entry je')
                ->join('fin_journal_line jl', 'jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id')
                ->join('fin_account a', 'a.account_id=jl.account_id AND a.tenant_id=jl.tenant_id')
                ->where('je.tenant_id', $tenant_id)->where('je.status', 'posted')
                ->where_in('je.journal_entry_id', $journal_ids)
                ->order_by('je.journal_date', 'DESC')->order_by('je.journal_entry_id', 'DESC')
                ->order_by('jl.line_number', 'ASC')->get();
            if ($line_query === FALSE) {
                return FALSE;
            }
            $recent_lines = $line_query->result_array();
        }
        $totals = array('revenue' => 0.0, 'expense' => 0.0);
        foreach ($summary->result_array() as $row) {
            if ($row['account_type'] === 'revenue') {
                $totals['revenue'] += (float) $row['credit_total'] - (float) $row['debit_total'];
            } elseif ($row['account_type'] === 'expense') {
                $totals['expense'] += (float) $row['debit_total'] - (float) $row['credit_total'];
            }
        }
        return array(
            'base_currency' => $settings->num_rows() ? $settings->row()->base_currency : '',
            'posted_journal_count' => (int) $count,
            'cash_balance' => (float) $cash->row()->balance,
            'revenue_ytd' => $totals['revenue'],
            'expense_ytd' => $totals['expense'],
            'net_ytd' => $totals['revenue'] - $totals['expense'],
            'recent_journals' => $recent_rows,
            'recent_journal_lines' => $recent_lines,
        );
    }

    public function rows($report, $tenant_id, $start, $end, $search = '')
    {
        if (!$this->available($report)) {
            return FALSE;
        }
        list($sql, $binds, $order_columns) = $this->query($report, $tenant_id, $start, $end, $search);
        $order = reset($order_columns);
        $sql .= ' ORDER BY ' . $order . ' ASC';
        $result = $this->db->query($sql, $binds);
        return $result === FALSE ? FALSE : $result->result_array();
    }

    public function summary($report, $tenant_id, $start, $end)
    {
        if (!$this->available($report)) {
            return FALSE;
        }
        $base = $report;
        if ($base === 'profit_loss_detail') {
            $base = 'profit_loss';
        } elseif ($base === 'cash_flow_detail') {
            $base = 'cash_flow';
        }
        $fields_by_report = array(
            'general_ledger' => array('debit_base_amount', 'credit_base_amount'),
            'cash_flow' => array('cash_in', 'cash_out'),
            'ar_report' => array('billed', 'paid', 'outstanding'),
            'ap_report' => array('billed', 'paid', 'outstanding'),
            'bank_balance' => array('opening_balance', 'cash_in', 'cash_out', 'ending_balance'),
            'profit_loss' => array('debit_total', 'credit_total', 'net_debit'),
            'balance_sheet' => array('debit_total', 'credit_total', 'net_debit'),
            'trial_balance' => array('debit_total', 'credit_total', 'net_debit'),
        );
        $numeric = array('debit_base_amount','credit_base_amount','debit_total','credit_total','net_debit','cash_in','cash_out','billed','paid','outstanding','opening_balance','ending_balance');
        $fields = isset($fields_by_report[$base]) ? $fields_by_report[$base] : array();
        list($sql, $binds) = $this->query($report, $tenant_id, $start, $end, '');
        $select = array('COUNT(*) AS row_count');
        foreach ($fields as $field) {
            $select[] = 'COALESCE(SUM(`' . $field . '`),0) AS `' . $field . '`';
        }
        if ($base === 'cancellation_history') {
            foreach (array('pending', 'approved', 'rejected', 'revise', 'failed') as $status) {
                $select[] = "COALESCE(SUM(CASE WHEN `status`='" . $status . "' THEN 1 ELSE 0 END),0) AS `" . $status . "_count`";
            }
        }
        $result = $this->db->query(
            'SELECT ' . implode(',', $select) . ' FROM (' . $sql . ') finance_summary',
            $binds
        );
        if ($result === FALSE) {
            return FALSE;
        }
        $row = $result->row_array();
        $totals = array_fill_keys($numeric, 0.0);
        foreach ($fields as $field) {
            $totals[$field] = (float) $row[$field];
        }
        if ($base === 'cancellation_history') {
            foreach (array('pending', 'approved', 'rejected', 'revise', 'failed') as $status) {
                $totals[$status . '_count'] = (int) $row[$status . '_count'];
            }
        }
        $totals['row_count'] = (int) $row['row_count'];
        return $totals;
    }

    public function count($report, $tenant_id, $start, $end, $search = '')
    {
        if (!$this->available($report)) {
            return FALSE;
        }
        list($sql, $binds) = $this->query($report, $tenant_id, $start, $end, $search);
        $result = $this->db->query('SELECT COUNT(*) AS row_count FROM (' . $sql . ') finance_report', $binds);
        return $result === FALSE ? FALSE : (int) $result->row()->row_count;
    }

    public function page($report, $tenant_id, $start, $end, $search, $limit, $offset, $order, $direction)
    {
        if (!$this->available($report)) {
            return FALSE;
        }
        list($sql, $binds, $order_columns) = $this->query($report, $tenant_id, $start, $end, $search);
        if (!isset($order_columns[$order])) {
            $order = reset($order_columns);
        } else {
            $order = $order_columns[$order];
        }
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= ' ORDER BY ' . $order . ' ' . $direction . ' LIMIT ' . max(1, min(50, (int) $limit))
            . ' OFFSET ' . max(0, (int) $offset);
        $result = $this->db->query($sql, $binds);
        return $result === FALSE ? FALSE : $result->result_array();
    }

    /**
     * Two-sided (skontro) balance sheet grouped by the account hierarchy.
     * Assets sit on the left; liabilities and equity sit on the right.
     * Unclosed revenue/expense is presented as current year earnings so the
     * sheet still balances before closing entries are posted.
     */
    public function balance_sheet_skontro($tenant_id, $end)
    {
        if (!$this->available('balance_sheet') || $end === '') {
            return FALSE;
        }
        $tenant_id = (int) $tenant_id;
        $account_query = $this->db->select('account_id,parent_account_id,account_code,account_name,account_type,normal_balance,posting_flag')
            ->from('fin_account')->where('tenant_id', $tenant_id)->get();
        if ($account_query === FALSE) {
            return FALSE;
        }
        $accounts = array();
        foreach ($account_query->result_array() as $account) {
            $accounts[(int) $account['account_id']] = $account;
        }
        $balance_query = $this->db->select('a.account_id,SUM(jl.debit_base_amount) AS debit_total,SUM(jl.credit_base_amount) AS credit_total', FALSE)
            ->from('fin_journal_entry je')
            ->join('fin_journal_line jl', 'jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id')
            ->join('fin_account a', 'a.account_id=jl.account_id AND a.tenant_id=jl.tenant_id')
            ->where('je.tenant_id', $tenant_id)->where('je.status', 'posted')
            ->where('je.journal_date <=', $end)
            ->group_by('a.account_id')->get();
        if ($balance_query === FALSE) {
            return FALSE;
        }

        $sections = array(
            'asset' => array('groups' => array(), 'total' => 0.0),
            'liability' => array('groups' => array(), 'total' => 0.0),
            'equity' => array('groups' => array(), 'total' => 0.0),
        );
        $current_earnings = 0.0;
        foreach ($balance_query->result_array() as $line) {
            $account_id = (int) $line['account_id'];
            if (!isset($accounts[$account_id])) {
                continue;
            }
            $account = $accounts[$account_id];
            $raw = (float) $line['debit_total'] - (float) $line['credit_total'];
            $type = (string) $account['account_type'];
            if ($type === 'revenue') {
                $current_earnings -= $raw;
                continue;
            }
            if ($type === 'expense') {
                $current_earnings -= $raw;
                continue;
            }
            if (!isset($sections[$type])) {
                continue;
            }
            $amount = $type === 'asset' ? $raw : -$raw;
            if (abs($amount) < 0.005) {
                continue;
            }
            list($group_id, $group_account) = $this->balance_sheet_group($account, $accounts);
            if (!isset($sections[$type]['groups'][$group_id])) {
                $sections[$type]['groups'][$group_id] = array(
                    'code' => (string) $group_account['account_code'],
                    'name' => (string) $group_account['account_name'],
                    'accounts' => array(),
                    'total' => 0.0,
                );
            }
            $sections[$type]['groups'][$group_id]['accounts'][] = array(
                'code' => (string) $account['account_code'],
                'name' => (string) $account['account_name'],
                'amount' => $amount,
            );
            $sections[$type]['groups'][$group_id]['total'] += $amount;
            $sections[$type]['total'] += $amount;
        }

        $sections['equity']['groups']['current_earnings'] = array(
            'code' => '',
            'name' => '',
            'name_key' => 'finance_skontro_current_earnings',
            'accounts' => array(),
            'total' => $current_earnings,
        );
        $sections['equity']['total'] += $current_earnings;

        foreach ($sections as $type => &$section) {
            uasort($section['groups'], function ($left, $right) {
                return strcmp((string) $left['code'], (string) $right['code']);
            });
            foreach ($section['groups'] as &$group) {
                usort($group['accounts'], function ($left, $right) {
                    return strcmp((string) $left['code'], (string) $right['code']);
                });
            }
            unset($group);
        }
        unset($section);

        $total_assets = (float) $sections['asset']['total'];
        $total_liabilities_equity = (float) $sections['liability']['total'] + (float) $sections['equity']['total'];
        return array(
            'as_of' => $end,
            'sections' => $sections,
            'current_earnings' => $current_earnings,
            'total_assets' => $total_assets,
            'total_liabilities' => (float) $sections['liability']['total'],
            'total_equity' => (float) $sections['equity']['total'],
            'total_liabilities_equity' => $total_liabilities_equity,
            'balanced' => abs($total_assets - $total_liabilities_equity) < 0.01,
            'difference' => $total_assets - $total_liabilities_equity,
        );
    }

    /**
     * Formal profit & loss statement grouped like a printed report:
     * revenue, cost of goods sold, gross profit, operating expenses and
     * net profit. Amounts are derived from posted journals in the period.
     */
    public function profit_loss_statement($tenant_id, $start, $end)
    {
        if (!$this->available('profit_loss') || $start === '' || $end === '') {
            return FALSE;
        }
        $tenant_id = (int) $tenant_id;
        $account_query = $this->db->select('account_id,parent_account_id,account_code,account_name,account_type,normal_balance,posting_flag')
            ->from('fin_account')->where('tenant_id', $tenant_id)->get();
        if ($account_query === FALSE) {
            return FALSE;
        }
        $accounts = array();
        foreach ($account_query->result_array() as $account) {
            $accounts[(int) $account['account_id']] = $account;
        }
        $balance_query = $this->db->select('a.account_id,SUM(jl.debit_base_amount) AS debit_total,SUM(jl.credit_base_amount) AS credit_total', FALSE)
            ->from('fin_journal_entry je')
            ->join('fin_journal_line jl', 'jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id')
            ->join('fin_account a', 'a.account_id=jl.account_id AND a.tenant_id=jl.tenant_id')
            ->where('je.tenant_id', $tenant_id)->where('je.status', 'posted')
            ->where('je.journal_date >=', $start)->where('je.journal_date <=', $end)
            ->group_by('a.account_id')->get();
        if ($balance_query === FALSE) {
            return FALSE;
        }

        $revenue = array('accounts' => array(), 'total' => 0.0);
        $cogs = array('accounts' => array(), 'total' => 0.0);
        $operating = array('groups' => array(), 'total' => 0.0);
        foreach ($balance_query->result_array() as $line) {
            $account_id = (int) $line['account_id'];
            if (!isset($accounts[$account_id])) {
                continue;
            }
            $account = $accounts[$account_id];
            $raw = (float) $line['debit_total'] - (float) $line['credit_total'];
            if ($account['account_type'] === 'revenue') {
                $amount = -$raw;
                if (abs($amount) < 0.005) {
                    continue;
                }
                $revenue['accounts'][] = array(
                    'code' => (string) $account['account_code'],
                    'name' => (string) $account['account_name'],
                    'amount' => $amount,
                );
                $revenue['total'] += $amount;
                continue;
            }
            if ($account['account_type'] !== 'expense') {
                continue;
            }
            $amount = $raw;
            if (abs($amount) < 0.005) {
                continue;
            }
            list($top_id, $top) = $this->account_top($account, $accounts);
            $is_cogs = strpos((string) $top['account_code'], '5') === 0
                || stripos((string) $top['account_name'], 'harga pokok') !== FALSE;
            if ($is_cogs) {
                $cogs['accounts'][] = array(
                    'code' => (string) $account['account_code'],
                    'name' => (string) $account['account_name'],
                    'amount' => $amount,
                );
                $cogs['total'] += $amount;
                continue;
            }
            list($group_id, $group_account) = $this->account_group($account, $accounts);
            if (!isset($operating['groups'][$group_id])) {
                $operating['groups'][$group_id] = array(
                    'code' => (string) $group_account['account_code'],
                    'name' => (string) $group_account['account_name'],
                    'accounts' => array(),
                    'total' => 0.0,
                );
            }
            $operating['groups'][$group_id]['accounts'][] = array(
                'code' => (string) $account['account_code'],
                'name' => (string) $account['account_name'],
                'amount' => $amount,
            );
            $operating['groups'][$group_id]['total'] += $amount;
            $operating['total'] += $amount;
        }

        usort($revenue['accounts'], function ($left, $right) {
            return strcmp((string) $left['code'], (string) $right['code']);
        });
        usort($cogs['accounts'], function ($left, $right) {
            return strcmp((string) $left['code'], (string) $right['code']);
        });
        uasort($operating['groups'], function ($left, $right) {
            return strcmp((string) $left['code'], (string) $right['code']);
        });
        foreach ($operating['groups'] as &$group) {
            usort($group['accounts'], function ($left, $right) {
                return strcmp((string) $left['code'], (string) $right['code']);
            });
        }
        unset($group);

        $gross_profit = $revenue['total'] - $cogs['total'];
        return array(
            'period_start' => $start,
            'period_end' => $end,
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $gross_profit,
            'operating' => $operating,
            'total_expenses' => $cogs['total'] + $operating['total'],
            'net_profit' => $gross_profit - $operating['total'],
        );
    }

    /**
     * Cash flow statement in direct or indirect presentation. Cash movements
     * are read from journals that touch a cash/bank account. Opening balance
     * journals are treated as beginning cash instead of period cash flow.
     */
    public function cash_flow_statement($tenant_id, $start, $end, $method = 'direct')
    {
        if (!$this->available('cash_flow') || $start === '' || $end === '') {
            return FALSE;
        }
        $method = $method === 'indirect' ? 'indirect' : 'direct';
        $tenant_id = (int) $tenant_id;
        $account_query = $this->db->select('account_id,parent_account_id,account_code,account_name,account_type,normal_balance,posting_flag')
            ->from('fin_account')->where('tenant_id', $tenant_id)->get();
        $cash_query = $this->db->select('account_id')->from('fin_cash_account')->where('tenant_id', $tenant_id)->get();
        if ($account_query === FALSE || $cash_query === FALSE) {
            return FALSE;
        }
        $accounts = array();
        foreach ($account_query->result_array() as $account) {
            $accounts[(int) $account['account_id']] = $account;
        }
        $cash_accounts = array();
        foreach ($cash_query->result_array() as $cash_account) {
            $cash_accounts[(int) $cash_account['account_id']] = TRUE;
        }

        $line_query = $this->db->select('je.journal_entry_id,je.source_type,jl.account_id,jl.debit_base_amount,jl.credit_base_amount', FALSE)
            ->from('fin_journal_entry je')
            ->join('fin_journal_line jl', 'jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id')
            ->where('je.tenant_id', $tenant_id)->where('je.status', 'posted')
            ->where('je.journal_date >=', $start)->where('je.journal_date <=', $end)
            ->order_by('je.journal_entry_id', 'ASC')->order_by('jl.line_number', 'ASC')->get();
        if ($line_query === FALSE) {
            return FALSE;
        }
        $journals = array();
        $period_movements = array();
        foreach ($line_query->result_array() as $line) {
            $journal_id = (int) $line['journal_entry_id'];
            if (!isset($journals[$journal_id])) {
                $journals[$journal_id] = array('source_type' => (string) $line['source_type'], 'lines' => array());
            }
            $journals[$journal_id]['lines'][] = array(
                'account_id' => (int) $line['account_id'],
                'debit' => (float) $line['debit_base_amount'],
                'credit' => (float) $line['credit_base_amount'],
            );
            if ($line['source_type'] !== 'opening_balance') {
                $account_id = (int) $line['account_id'];
                if (!isset($period_movements[$account_id])) {
                    $period_movements[$account_id] = 0.0;
                }
                $period_movements[$account_id] += (float) $line['debit_base_amount'] - (float) $line['credit_base_amount'];
            }
        }

        $sections = array(
            'operating' => array('items' => array(), 'total' => 0.0),
            'investing' => array('items' => array(), 'total' => 0.0),
            'financing' => array('items' => array(), 'total' => 0.0),
        );
        $actual_net_change = 0.0;
        foreach ($journals as $journal) {
            if ($journal['source_type'] === 'opening_balance') {
                continue;
            }
            $cash_net = 0.0;
            $counterparts = array();
            foreach ($journal['lines'] as $line) {
                if (isset($cash_accounts[$line['account_id']])) {
                    $cash_net += $line['debit'] - $line['credit'];
                } else {
                    $counterparts[] = $line;
                }
            }
            if (abs($cash_net) < 0.005) {
                continue;
            }
            $actual_net_change += $cash_net;
            foreach ($counterparts as $line) {
                $account_id = (int) $line['account_id'];
                if (!isset($accounts[$account_id])) {
                    continue;
                }
                $effect = -($line['debit'] - $line['credit']);
                if (abs($effect) < 0.005) {
                    continue;
                }
                list($section, $kind) = $this->cash_flow_classify($accounts[$account_id], $accounts);
                if (!isset($sections[$section]['items'][$account_id])) {
                    $sections[$section]['items'][$account_id] = array(
                        'code' => (string) $accounts[$account_id]['account_code'],
                        'name' => (string) $accounts[$account_id]['account_name'],
                        'account_type' => (string) $accounts[$account_id]['account_type'],
                        'kind' => $kind,
                        'amount' => 0.0,
                    );
                }
                $sections[$section]['items'][$account_id]['amount'] += $effect;
            }
        }
        foreach ($sections as $section_key => &$section) {
            uasort($section['items'], function ($left, $right) {
                return strcmp((string) $left['code'], (string) $right['code']);
            });
            $section['total'] = 0.0;
            foreach ($section['items'] as $account_id => $item) {
                if (abs($item['amount']) < 0.005) {
                    unset($section['items'][$account_id]);
                    continue;
                }
                $section['total'] += $item['amount'];
            }
        }
        unset($section);

        $presented_total = $sections['operating']['total'] + $sections['investing']['total'] + $sections['financing']['total'];
        $difference = $actual_net_change - $presented_total;
        if (abs($difference) >= 0.005) {
            $sections['operating']['items']['other'] = array(
                'code' => '',
                'name' => '',
                'name_key' => 'finance_cf_other_adjustment',
                'account_type' => 'other',
                'kind' => 'other',
                'amount' => $difference,
            );
            $sections['operating']['total'] += $difference;
        }

        $net_profit = 0.0;
        $non_cash = array();
        $working_capital = array();
        foreach ($period_movements as $account_id => $movement) {
            if (!isset($accounts[$account_id]) || isset($cash_accounts[$account_id])) {
                continue;
            }
            $account = $accounts[$account_id];
            if ($account['account_type'] === 'revenue') {
                $net_profit -= $movement;
                continue;
            }
            if ($account['account_type'] === 'expense') {
                $net_profit -= $movement;
                if ($this->cash_flow_is_non_cash_expense((string) $account['account_name'])) {
                    if (abs($movement) >= 0.005) {
                        $non_cash[] = array(
                            'code' => (string) $account['account_code'],
                            'name' => (string) $account['account_name'],
                            'amount' => $movement,
                        );
                    }
                }
                continue;
            }
            list($section, $kind) = $this->cash_flow_classify($account, $accounts);
            if ($section !== 'operating') {
                continue;
            }
            if ($account['account_type'] === 'asset') {
                $effect = -$movement;
                $direction = $movement >= 0 ? 'increase' : 'decrease';
            } elseif ($account['account_type'] === 'liability') {
                $balance_change = -$movement;
                $effect = $balance_change;
                $direction = $balance_change >= 0 ? 'increase' : 'decrease';
            } else {
                continue;
            }
            if (abs($effect) < 0.005) {
                continue;
            }
            $working_capital[] = array(
                'code' => (string) $account['account_code'],
                'name' => (string) $account['account_name'],
                'amount' => $effect,
                'direction' => $direction,
            );
        }
        usort($non_cash, function ($left, $right) {
            return strcmp((string) $left['code'], (string) $right['code']);
        });
        usort($working_capital, function ($left, $right) {
            return strcmp((string) $left['code'], (string) $right['code']);
        });

        $adjustment_total = 0.0;
        foreach ($non_cash as $item) {
            $adjustment_total += $item['amount'];
        }
        foreach ($working_capital as $item) {
            $adjustment_total += $item['amount'];
        }
        $indirect_operating = $net_profit + $adjustment_total;
        $indirect_difference = $sections['operating']['total'] - $indirect_operating;
        if (abs($indirect_difference) >= 0.005) {
            $working_capital[] = array(
                'code' => '',
                'name' => '',
                'name_key' => 'finance_cf_other_adjustment',
                'amount' => $indirect_difference,
                'direction' => 'other',
            );
        }

        $begin_before = $this->cash_balance_before($tenant_id, $start);
        $begin_opening = $this->cash_balance_opening_in($tenant_id, $start, $end);
        if ($begin_before === FALSE || $begin_opening === FALSE) {
            return FALSE;
        }
        $beginning_cash = $begin_before + $begin_opening;
        $net_change = $sections['operating']['total'] + $sections['investing']['total'] + $sections['financing']['total'];

        return array(
            'method' => $method,
            'period_start' => $start,
            'period_end' => $end,
            'sections' => $sections,
            'net_profit' => $net_profit,
            'non_cash' => $non_cash,
            'working_capital' => $working_capital,
            'adjustment_total' => $adjustment_total,
            'indirect_total' => $sections['operating']['total'],
            'net_change' => $net_change,
            'beginning_cash' => $beginning_cash,
            'ending_cash' => $beginning_cash + $net_change,
        );
    }

    private function cash_flow_classify($account, $accounts)
    {
        $type = (string) $account['account_type'];
        list($group_id, $group) = $this->account_group($account, $accounts);
        $group_code = (string) $group['account_code'];
        $group_name = strtolower((string) $group['account_name']);
        if ($type === 'asset') {
            if (strpos($group_code, '15') === 0
                || strpos($group_name, 'aset tetap') !== FALSE
                || strpos($group_name, 'investasi') !== FALSE
                || strpos($group_name, 'peralatan') !== FALSE
                || strpos($group_name, 'kendaraan') !== FALSE
                || strpos($group_name, 'bangunan') !== FALSE
                || strpos($group_name, 'tanah') !== FALSE) {
                return array('investing', 'investing');
            }
            return array('operating', 'working_capital');
        }
        if ($type === 'liability') {
            if (strpos($group_code, '22') === 0
                || strpos($group_name, 'jangka panjang') !== FALSE
                || strpos($group_name, 'obligasi') !== FALSE) {
                return array('financing', 'financing');
            }
            return array('operating', 'working_capital');
        }
        if ($type === 'equity') {
            return array('financing', 'financing');
        }
        return array('operating', 'operating');
    }

    private function cash_flow_is_non_cash_expense($account_name)
    {
        $name = strtolower($account_name);
        return strpos($name, 'penyusutan') !== FALSE
            || strpos($name, 'depresiasi') !== FALSE
            || strpos($name, 'amortisasi') !== FALSE;
    }

    private function cash_balance_before($tenant_id, $date)
    {
        $query = $this->db->select('COALESCE(SUM(jl.debit_base_amount-jl.credit_base_amount),0) AS balance', FALSE)
            ->from('fin_journal_entry je')
            ->join('fin_journal_line jl', 'jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id')
            ->join('fin_cash_account ca', 'ca.account_id=jl.account_id AND ca.tenant_id=jl.tenant_id')
            ->where('je.tenant_id', (int) $tenant_id)->where('je.status', 'posted')
            ->where('je.journal_date <', $date)->get();
        return $query === FALSE ? FALSE : (float) $query->row()->balance;
    }

    private function cash_balance_opening_in($tenant_id, $start, $end)
    {
        $query = $this->db->select('COALESCE(SUM(jl.debit_base_amount-jl.credit_base_amount),0) AS balance', FALSE)
            ->from('fin_journal_entry je')
            ->join('fin_journal_line jl', 'jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id')
            ->join('fin_cash_account ca', 'ca.account_id=jl.account_id AND ca.tenant_id=jl.tenant_id')
            ->where('je.tenant_id', (int) $tenant_id)->where('je.status', 'posted')
            ->where('je.journal_date >=', $start)->where('je.journal_date <=', $end)
            ->where('je.source_type', 'opening_balance')->get();
        return $query === FALSE ? FALSE : (float) $query->row()->balance;
    }

    /** Return the top-level ancestor of an account. */
    private function account_top($account, $accounts)
    {
        $current = $account;
        $guard = 0;
        while ((int) $current['parent_account_id'] > 0 && isset($accounts[(int) $current['parent_account_id']]) && $guard < 32) {
            $current = $accounts[(int) $current['parent_account_id']];
            $guard++;
        }
        return array((int) $current['account_id'], $current);
    }

    /** Return the grouping parent used by the formatted reports. */
    private function account_group($account, $accounts)
    {
        $ancestors = array();
        $parent_id = (int) $account['parent_account_id'];
        $guard = 0;
        while ($parent_id > 0 && isset($accounts[$parent_id]) && $guard < 32) {
            $ancestors[] = $parent_id;
            $parent_id = (int) $accounts[$parent_id]['parent_account_id'];
            $guard++;
        }
        if (!$ancestors) {
            return array((int) $account['account_id'], $account);
        }
        $top_id = (int) end($ancestors);
        $group_id = (int) $ancestors[0];
        for ($index = 0; $index < count($ancestors); $index++) {
            $candidate = (int) $ancestors[$index];
            $candidate_parent = (int) $accounts[$candidate]['parent_account_id'];
            if ($candidate_parent === $top_id || $candidate_parent === 0 || !isset($accounts[$candidate_parent])) {
                $group_id = $candidate;
                break;
            }
        }
        return array($group_id, $accounts[$group_id]);
    }

    /** Return the grouping parent used by the skontro balance sheet. */
    private function balance_sheet_group($account, $accounts)
    {
        return $this->account_group($account, $accounts);
    }

    private function query($report, $tenant_id, $start, $end, $search)
    {
        if ($report === 'profit_loss_detail') {
            $report = 'profit_loss';
        } elseif ($report === 'cash_flow_detail') {
            $report = 'cash_flow';
        }
        $binds = array((int) $tenant_id);
        $search = is_string($search) ? trim(substr($search, 0, 120)) : '';
        $like = '%' . $search . '%';

        if ($report === 'general_ledger') {
            $sql = 'SELECT je.journal_date,je.journal_number,a.account_code,a.account_name,jl.description,'
                . 'jl.currency_code,jl.debit_base_amount,jl.credit_base_amount '
                . 'FROM fin_journal_entry je JOIN fin_journal_line jl ON jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id '
                . 'JOIN fin_account a ON a.account_id=jl.account_id AND a.tenant_id=jl.tenant_id '
                . 'WHERE je.tenant_id=? AND je.status="posted"';
            $sql .= $this->date_filter('je.journal_date', $start, $end, $binds);
            if ($search !== '') {
                $sql .= ' AND (je.journal_number LIKE ? OR a.account_code LIKE ? OR a.account_name LIKE ? OR jl.description LIKE ?)';
                array_push($binds, $like, $like, $like, $like);
            }
            return array($sql, $binds, array(
                'journal_date' => 'journal_date', 'journal_number' => 'journal_number',
                'account_code' => 'account_code', 'account_name' => 'account_name',
                'debit_base_amount' => 'debit_base_amount', 'credit_base_amount' => 'credit_base_amount',
            ));
        }

        if ($report === 'cash_flow') {
            $sql = 'SELECT DATE_FORMAT(je.journal_date,"%Y-%m") AS period,ca.account_code,ca.account_name,'
                . 'SUM(jl.debit_base_amount) AS cash_in,SUM(jl.credit_base_amount) AS cash_out '
                . 'FROM fin_journal_entry je JOIN fin_journal_line jl ON jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id '
                . 'JOIN fin_cash_account ca ON ca.account_id=jl.account_id AND ca.tenant_id=jl.tenant_id '
                . 'WHERE je.tenant_id=? AND je.status="posted"';
            $sql .= $this->date_filter('je.journal_date', $start, $end, $binds);
            if ($search !== '') {
                $sql .= ' AND (ca.account_code LIKE ? OR ca.account_name LIKE ?)';
                array_push($binds, $like, $like);
            }
            $sql .= ' GROUP BY DATE_FORMAT(je.journal_date,"%Y-%m"),ca.cash_account_id,ca.account_code,ca.account_name';
            return array($sql, $binds, array('period' => 'period', 'account_code' => 'account_code', 'cash_in' => 'cash_in', 'cash_out' => 'cash_out'));
        }

        if ($report === 'cancellation_history') {
            $sql = 'SELECT c.requested_on,c.source_type,c.source_id,c.previous_status,c.status,c.reason,c.decided_on,'
                . "COALESCE(NULLIF(u.full_name,''),u.username) AS requested_by_name,"
                . 'je.journal_number AS reversal_number '
                . 'FROM fin_cancellation c '
                . 'LEFT JOIN app_user u ON u.user_id=c.requested_by AND u.tenant_id=c.tenant_id '
                . 'LEFT JOIN fin_journal_entry je ON je.journal_entry_id=c.reversal_journal_id AND je.tenant_id=c.tenant_id '
                . 'WHERE c.tenant_id=?';
            $sql .= $this->date_filter('c.requested_on', $start, $end, $binds);
            if ($search !== '') {
                $sql .= ' AND (c.source_type LIKE ? OR c.reason LIKE ? OR u.full_name LIKE ? OR u.username LIKE ? OR je.journal_number LIKE ?)';
                array_push($binds, $like, $like, $like, $like, $like);
            }
            return array($sql, $binds, array(
                'requested_on' => 'requested_on', 'source_type' => 'source_type',
                'source_id' => 'source_id', 'status' => 'status',
                'requested_by_name' => 'requested_by_name', 'decided_on' => 'decided_on',
            ));
        }

        if ($report === 'bank_balance') {
            $sql = 'SELECT ca.account_code,ca.account_name,ca.currency_code,'
                . 'COALESCE(SUM(CASE WHEN je.journal_date < ? THEN jl.debit_base_amount-jl.credit_base_amount ELSE 0 END),0) AS opening_balance,'
                . 'COALESCE(SUM(CASE WHEN je.journal_date >= ? AND je.journal_date <= ? THEN jl.debit_base_amount ELSE 0 END),0) AS cash_in,'
                . 'COALESCE(SUM(CASE WHEN je.journal_date >= ? AND je.journal_date <= ? THEN jl.credit_base_amount ELSE 0 END),0) AS cash_out,'
                . 'COALESCE(SUM(CASE WHEN je.journal_date <= ? THEN jl.debit_base_amount-jl.credit_base_amount ELSE 0 END),0) AS ending_balance '
                . 'FROM fin_cash_account ca '
                . 'INNER JOIN fin_account a ON a.account_id=ca.account_id AND a.tenant_id=ca.tenant_id '
                . 'LEFT JOIN fin_journal_line jl ON jl.account_id=a.account_id AND jl.tenant_id=ca.tenant_id '
                . 'LEFT JOIN fin_journal_entry je ON je.journal_entry_id=jl.journal_entry_id AND je.tenant_id=jl.tenant_id AND je.status="posted" '
                . 'WHERE ca.tenant_id=?';
            $binds = array($start, $start, $end, $start, $end, $end, (int) $tenant_id);
            if ($search !== '') {
                $sql .= ' AND (ca.account_code LIKE ? OR ca.account_name LIKE ? OR ca.currency_code LIKE ?)';
                array_push($binds, $like, $like, $like);
            }
            $sql .= ' GROUP BY ca.cash_account_id,ca.account_code,ca.account_name,ca.currency_code';
            return array($sql, $binds, array(
                'account_code' => 'account_code', 'account_name' => 'account_name',
                'opening_balance' => 'opening_balance', 'cash_in' => 'cash_in',
                'cash_out' => 'cash_out', 'ending_balance' => 'ending_balance',
            ));
        }

        if ($report === 'ar_report') {
            $sql = 'SELECT i.invoice_number,c.customer_name,i.invoice_date,i.due_date,i.currency_code,'
                . 'i.base_total_amount AS billed,COALESCE(p.paid,0) AS paid,'
                . '(i.base_total_amount-COALESCE(p.paid,0)) AS outstanding '
                . 'FROM fin_sales_invoice i JOIN fin_customer c ON c.customer_id=i.customer_id AND c.tenant_id=i.tenant_id '
                . 'LEFT JOIN (SELECT a.tenant_id,a.sales_invoice_id,SUM(a.base_amount) AS paid FROM fin_customer_payment_allocation a '
                . 'JOIN fin_customer_payment cp ON cp.tenant_id=a.tenant_id AND cp.customer_payment_id=a.customer_payment_id '
                . 'WHERE cp.status="posted" GROUP BY a.tenant_id,a.sales_invoice_id) p '
                . 'ON p.tenant_id=i.tenant_id AND p.sales_invoice_id=i.sales_invoice_id '
                . 'WHERE i.tenant_id=? AND i.status="posted" AND (i.base_total_amount-COALESCE(p.paid,0)) <> 0';
            if ($end !== '') {
                $sql .= ' AND i.invoice_date <= ?';
                $binds[] = $end;
            }
            if ($search !== '') {
                $sql .= ' AND (i.invoice_number LIKE ? OR c.customer_name LIKE ?)';
                array_push($binds, $like, $like);
            }
            return array($sql, $binds, array('invoice_number' => 'invoice_number', 'customer_name' => 'customer_name', 'due_date' => 'due_date', 'outstanding' => 'outstanding'));
        }

        if ($report === 'ap_report') {
            $sql = 'SELECT b.bill_number,s.supplier_name,b.bill_date,b.due_date,b.currency_code,'
                . 'b.base_total_amount AS billed,COALESCE(p.paid,0) AS paid,'
                . '(b.base_total_amount-COALESCE(p.paid,0)) AS outstanding '
                . 'FROM fin_purchase_bill b JOIN fin_supplier s ON s.supplier_id=b.supplier_id AND s.tenant_id=b.tenant_id '
                . 'LEFT JOIN (SELECT a.tenant_id,a.purchase_bill_id,SUM(a.base_amount) AS paid FROM fin_supplier_payment_allocation a '
                . 'JOIN fin_supplier_payment sp ON sp.tenant_id=a.tenant_id AND sp.supplier_payment_id=a.supplier_payment_id '
                . 'WHERE sp.status="posted" GROUP BY a.tenant_id,a.purchase_bill_id) p '
                . 'ON p.tenant_id=b.tenant_id AND p.purchase_bill_id=b.purchase_bill_id '
                . 'WHERE b.tenant_id=? AND b.status="posted" AND (b.base_total_amount-COALESCE(p.paid,0)) <> 0';
            if ($end !== '') {
                $sql .= ' AND b.bill_date <= ?';
                $binds[] = $end;
            }
            if ($search !== '') {
                $sql .= ' AND (b.bill_number LIKE ? OR s.supplier_name LIKE ?)';
                array_push($binds, $like, $like);
            }
            return array($sql, $binds, array('bill_number' => 'bill_number', 'supplier_name' => 'supplier_name', 'due_date' => 'due_date', 'outstanding' => 'outstanding'));
        }

        $types = array('profit_loss' => array('revenue', 'expense'), 'balance_sheet' => array('asset', 'liability', 'equity'), 'trial_balance' => array());
        $type_filter = isset($types[$report]) ? $types[$report] : array();
        $net_expression = 'SUM(jl.debit_base_amount-jl.credit_base_amount)';
        if ($report === 'profit_loss') {
            $net_expression = 'SUM(CASE WHEN a.account_type="revenue" THEN jl.credit_base_amount-jl.debit_base_amount ELSE jl.debit_base_amount-jl.credit_base_amount END)';
        } elseif ($report === 'balance_sheet') {
            $net_expression = 'SUM(CASE WHEN a.account_type="asset" THEN jl.debit_base_amount-jl.credit_base_amount ELSE jl.credit_base_amount-jl.debit_base_amount END)';
        }
        $sql = 'SELECT a.account_id,a.account_code,a.account_name,a.account_type,'
            . 'SUM(jl.debit_base_amount) AS debit_total,SUM(jl.credit_base_amount) AS credit_total,'
            . $net_expression . ' AS net_debit '
            . 'FROM fin_journal_entry je JOIN fin_journal_line jl ON jl.journal_entry_id=je.journal_entry_id AND jl.tenant_id=je.tenant_id '
            . 'JOIN fin_account a ON a.account_id=jl.account_id AND a.tenant_id=jl.tenant_id '
            . 'WHERE je.tenant_id=? AND je.status="posted"';
        if ($report === 'balance_sheet') {
            if ($end !== '') {
                $sql .= ' AND je.journal_date <= ?';
                $binds[] = $end;
            }
        } else {
            $sql .= $this->date_filter('je.journal_date', $start, $end, $binds);
        }
        if ($type_filter) {
            $placeholders = implode(',', array_fill(0, count($type_filter), '?'));
            $sql .= ' AND a.account_type IN (' . $placeholders . ')';
            foreach ($type_filter as $type) {
                $binds[] = $type;
            }
        }
        if ($search !== '') {
            $sql .= ' AND (a.account_code LIKE ? OR a.account_name LIKE ?)';
            array_push($binds, $like, $like);
        }
        $sql .= ' GROUP BY a.account_id,a.account_code,a.account_name,a.account_type '
            . 'HAVING SUM(jl.debit_base_amount) <> 0 OR SUM(jl.credit_base_amount) <> 0';
        return array($sql, $binds, array(
            'account_code' => 'account_code', 'account_name' => 'account_name',
            'account_type' => 'account_type', 'debit_total' => 'debit_total',
            'credit_total' => 'credit_total', 'net_debit' => 'net_debit',
        ));
    }

    private function date_filter($column, $start, $end, &$binds)
    {
        $sql = '';
        if ($start !== '') {
            $sql .= ' AND ' . $column . ' >= ?';
            $binds[] = $start;
        }
        if ($end !== '') {
            $sql .= ' AND ' . $column . ' <= ?';
            $binds[] = $end;
        }
        return $sql;
    }
}
