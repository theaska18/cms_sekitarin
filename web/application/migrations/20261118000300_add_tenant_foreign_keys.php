<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ensure every tenant-owned table cascades with its sys_tenant row. */
class Migration_Add_tenant_foreign_keys extends CI_Migration
{
    private $tables = array(
        'app_approval_action', 'app_approval_error', 'app_approval_flow',
        'app_approval_flow_step', 'app_approval_link_token', 'app_approval_request',
        'fin_account', 'fin_accounting_period', 'fin_bank_reconciliation',
        'fin_bank_reconciliation_line', 'fin_cash_account', 'fin_cash_transfer',
        'fin_category', 'fin_customer', 'fin_customer_payment',
        'fin_customer_payment_allocation', 'fin_department', 'fin_exchange_rate',
        'fin_fiscal_year', 'fin_journal_entry', 'fin_journal_line',
        'fin_purchase_bill', 'fin_purchase_bill_line', 'fin_sales_invoice',
        'fin_sales_invoice_line', 'fin_settings', 'fin_supplier',
        'fin_supplier_payment', 'fin_supplier_payment_allocation', 'fin_tax',
        'fin_transaction', 'fin_transaction_line', 'hr_employee_hierarchy',
    );

    public function up()
    {
        if (!$this->db->table_exists('sys_tenant')) {
            return;
        }

        foreach ($this->tables as $table) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            $column = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape('tenant_id'))->row_array();
            if (!$column) {
                continue;
            }
            if (strtolower((string) $column['Type']) !== 'bigint' || strtoupper((string) $column['Null']) !== 'NO') {
                $this->db->query('ALTER TABLE `' . $table . '` MODIFY `tenant_id` BIGINT NOT NULL');
            }
            if (!$this->has_tenant_foreign_key($table)) {
                $this->db->query('ALTER TABLE `' . $table . '` ADD CONSTRAINT `fk_' . $table . '_tenant_id`'
                    . ' FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`)'
                    . ' ON UPDATE CASCADE ON DELETE CASCADE');
            }
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            if ($this->has_tenant_foreign_key($table)) {
                $this->db->query('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `fk_' . $table . '_tenant_id`');
            }
        }
    }

    private function has_tenant_foreign_key($table)
    {
        $query = $this->db->select('CONSTRAINT_NAME')->from('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $this->db->database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', 'tenant_id')
            ->where('REFERENCED_TABLE_NAME', 'sys_tenant')
            ->limit(1)->get();
        return $query !== FALSE && $query->num_rows() > 0;
    }
}
