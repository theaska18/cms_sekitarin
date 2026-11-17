<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Add tenant-scoped business-key uniqueness for the Finance schema. */
class Migration_Add_finance_unique_constraints extends CI_Migration
{
    public function up()
    {
        $unique_keys = array(
            'fin_settings' => array(array('tenant_id')),
            'fin_exchange_rate' => array(array('tenant_id', 'from_currency', 'to_currency', 'effective_on')),
            'fin_account' => array(array('tenant_id', 'account_code')),
            'fin_fiscal_year' => array(array('tenant_id', 'year_code')),
            'fin_accounting_period' => array(array('tenant_id', 'fiscal_year_id', 'period_number')),
            'fin_department' => array(array('tenant_id', 'department_code')),
            'fin_category' => array(array('tenant_id', 'category_type', 'category_code')),
            'fin_tax' => array(array('tenant_id', 'tax_code')),
            'fin_customer' => array(array('tenant_id', 'customer_code')),
            'fin_supplier' => array(array('tenant_id', 'supplier_code')),
            'fin_cash_account' => array(array('tenant_id', 'account_code')),
            'fin_transaction' => array(array('tenant_id', 'transaction_number')),
            'fin_sales_invoice' => array(array('tenant_id', 'invoice_number')),
            'fin_customer_payment' => array(array('tenant_id', 'payment_number')),
            'fin_customer_payment_allocation' => array(array('tenant_id', 'customer_payment_id', 'sales_invoice_id')),
            'fin_purchase_bill' => array(array('tenant_id', 'bill_number')),
            'fin_supplier_payment' => array(array('tenant_id', 'payment_number')),
            'fin_supplier_payment_allocation' => array(array('tenant_id', 'supplier_payment_id', 'purchase_bill_id')),
            'fin_cash_transfer' => array(array('tenant_id', 'transfer_number')),
            'fin_journal_entry' => array(
                array('tenant_id', 'journal_number'),
                array('tenant_id', 'source_type', 'source_id'),
            ),
            'fin_journal_line' => array(array('tenant_id', 'journal_entry_id', 'line_number')),
            'fin_bank_reconciliation' => array(array('tenant_id', 'cash_account_id', 'statement_date')),
        );

        foreach ($unique_keys as $table => $keys) {
            foreach ($keys as $position => $columns) {
                $name = 'uq_' . substr($table, 4) . '_' . ($position + 1);
                $this->add_unique_if_missing($table, $name, $columns);
            }
        }
    }

    public function down()
    {
        // Retain tenant-scoped uniqueness and Finance data on rollback.
    }

    private function add_unique_if_missing($table, $name, $columns)
    {
        if (!$this->db->table_exists($table)) {
            return;
        }

        $existing = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            array($table, $name)
        );
        if ($existing !== FALSE && $existing->num_rows() > 0) {
            return;
        }

        $quoted_columns = array();
        foreach ($columns as $column) {
            $quoted_columns[] = $this->db->escape_identifiers($column);
        }
        $sql = 'ALTER TABLE ' . $this->db->escape_identifiers($table)
            . ' ADD UNIQUE KEY ' . $this->db->escape_identifiers($name)
            . ' (' . implode(', ', $quoted_columns) . ')';
        $this->db->query($sql);
    }
}
