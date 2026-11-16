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
        } elseif ($report === 'cash_flow') {
            $required['fin_cash_account'] = array('tenant_id', 'cash_account_id', 'account_id', 'account_code', 'account_name');
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
        $recent = $this->db->select('journal_date,journal_number,description,debit_total,credit_total')
            ->where('tenant_id', $tenant_id)->where('status', 'posted')
            ->order_by('journal_date', 'DESC')->order_by('journal_entry_id', 'DESC')->limit(5)
            ->get('fin_journal_entry');
        $count = $this->db->where('tenant_id', $tenant_id)->where('status', 'posted')->count_all_results('fin_journal_entry');
        if ($settings === FALSE || $summary === FALSE || $cash === FALSE || $recent === FALSE || $count === FALSE) {
            return FALSE;
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
            'recent_journals' => $recent->result_array(),
        );
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

    private function query($report, $tenant_id, $start, $end, $search)
    {
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
