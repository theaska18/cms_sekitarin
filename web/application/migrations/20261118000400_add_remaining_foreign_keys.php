<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Add the remaining tenant-scoped foreign keys, including audit user links. */
class Migration_Add_remaining_foreign_keys extends CI_Migration
{
    private $foreign_keys = array(
        array('table' => 'app_approval_action', 'column' => 'request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'CASCADE'),
        array('table' => 'app_approval_action', 'column' => 'approver_user_id', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'app_approval_error', 'column' => 'request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'CASCADE'),
        array('table' => 'app_approval_error', 'column' => 'flow_id', 'ref_table' => 'app_approval_flow', 'ref_column' => 'flow_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_error', 'column' => 'org_id', 'ref_table' => 'hr_org', 'ref_column' => 'org_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_error', 'column' => 'assigned_employee_id', 'ref_table' => 'hr_employee', 'ref_column' => 'employee_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_error', 'column' => 'assigned_approver_user_id', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_error', 'column' => 'handled_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_flow', 'column' => 'flow_code', 'ref_table' => 'sys_approval_flow', 'ref_column' => 'flow_code', 'on_delete' => 'CASCADE'),
        array('table' => 'app_approval_flow_step', 'column' => 'flow_id', 'ref_table' => 'app_approval_flow', 'ref_column' => 'flow_id', 'on_delete' => 'CASCADE'),
        array('table' => 'app_approval_flow_step', 'column' => 'approver_user_id', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT', 'nullable' => TRUE),
        array('table' => 'app_approval_flow_step', 'column' => 'approver_employee_id', 'ref_table' => 'hr_employee', 'ref_column' => 'employee_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_flow_step', 'column' => 'org_id', 'ref_table' => 'hr_org', 'ref_column' => 'org_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_link_token', 'column' => 'request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'CASCADE'),
        array('table' => 'app_approval_link_token', 'column' => 'approver_user_id', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'app_approval_request', 'column' => 'flow_id', 'ref_table' => 'app_approval_flow', 'ref_column' => 'flow_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_request', 'column' => 'flow_code', 'ref_table' => 'sys_approval_flow', 'ref_column' => 'flow_code', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_request', 'column' => 'requester_user_id', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'app_approval_request', 'column' => 'requester_employee_id', 'ref_table' => 'hr_employee', 'ref_column' => 'employee_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_request', 'column' => 'submitted_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'app_approval_request', 'column' => 'current_approver_user_id', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'hr_employee_hierarchy', 'column' => 'employee_id', 'ref_table' => 'hr_employee', 'ref_column' => 'employee_id', 'on_delete' => 'CASCADE'),
        array('table' => 'hr_employee_hierarchy', 'column' => 'manager_employee_id', 'ref_table' => 'hr_employee', 'ref_column' => 'employee_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'hr_employee_hierarchy', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'sys_approval_flow_step', 'column' => 'approver_user_id', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT', 'nullable' => TRUE),
        array('table' => 'sys_approval_flow_step', 'column' => 'approver_employee_id', 'ref_table' => 'hr_employee', 'ref_column' => 'employee_id', 'on_delete' => 'SET NULL'),
        array('table' => 'sys_approval_flow_step', 'column' => 'org_id', 'ref_table' => 'hr_org', 'ref_column' => 'org_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_account', 'column' => 'parent_account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_account', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_account', 'column' => 'updated_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_accounting_period', 'column' => 'fiscal_year_id', 'ref_table' => 'fin_fiscal_year', 'ref_column' => 'fiscal_year_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_bank_reconciliation', 'column' => 'cash_account_id', 'ref_table' => 'fin_cash_account', 'ref_column' => 'cash_account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_bank_reconciliation', 'column' => 'approval_request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_bank_reconciliation', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_bank_reconciliation', 'column' => 'completed_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_bank_reconciliation_line', 'column' => 'reconciliation_id', 'ref_table' => 'fin_bank_reconciliation', 'ref_column' => 'reconciliation_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_bank_reconciliation_line', 'column' => 'journal_line_id', 'ref_table' => 'fin_journal_line', 'ref_column' => 'journal_line_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_cash_account', 'column' => 'account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_cash_account', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_cash_transfer', 'column' => 'from_cash_account_id', 'ref_table' => 'fin_cash_account', 'ref_column' => 'cash_account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_cash_transfer', 'column' => 'to_cash_account_id', 'ref_table' => 'fin_cash_account', 'ref_column' => 'cash_account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_cash_transfer', 'column' => 'approval_request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_cash_transfer', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_cash_transfer', 'column' => 'posted_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_customer', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_customer', 'column' => 'updated_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_customer_payment', 'column' => 'customer_id', 'ref_table' => 'fin_customer', 'ref_column' => 'customer_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_customer_payment', 'column' => 'cash_account_id', 'ref_table' => 'fin_cash_account', 'ref_column' => 'cash_account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_customer_payment', 'column' => 'approval_request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_customer_payment', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_customer_payment', 'column' => 'posted_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_customer_payment_allocation', 'column' => 'customer_payment_id', 'ref_table' => 'fin_customer_payment', 'ref_column' => 'customer_payment_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_customer_payment_allocation', 'column' => 'sales_invoice_id', 'ref_table' => 'fin_sales_invoice', 'ref_column' => 'sales_invoice_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_exchange_rate', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_fiscal_year', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_journal_entry', 'column' => 'period_id', 'ref_table' => 'fin_accounting_period', 'ref_column' => 'period_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_journal_entry', 'column' => 'reversed_entry_id', 'ref_table' => 'fin_journal_entry', 'ref_column' => 'journal_entry_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_journal_entry', 'column' => 'approval_request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_journal_entry', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_journal_entry', 'column' => 'posted_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_journal_line', 'column' => 'journal_entry_id', 'ref_table' => 'fin_journal_entry', 'ref_column' => 'journal_entry_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_journal_line', 'column' => 'account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_journal_line', 'column' => 'department_id', 'ref_table' => 'fin_department', 'ref_column' => 'department_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_purchase_bill', 'column' => 'supplier_id', 'ref_table' => 'fin_supplier', 'ref_column' => 'supplier_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_purchase_bill', 'column' => 'approval_request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_purchase_bill', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_purchase_bill', 'column' => 'posted_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_purchase_bill_line', 'column' => 'purchase_bill_id', 'ref_table' => 'fin_purchase_bill', 'ref_column' => 'purchase_bill_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_purchase_bill_line', 'column' => 'account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_purchase_bill_line', 'column' => 'department_id', 'ref_table' => 'fin_department', 'ref_column' => 'department_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_purchase_bill_line', 'column' => 'tax_id', 'ref_table' => 'fin_tax', 'ref_column' => 'tax_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_sales_invoice', 'column' => 'customer_id', 'ref_table' => 'fin_customer', 'ref_column' => 'customer_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_sales_invoice', 'column' => 'approval_request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_sales_invoice', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_sales_invoice', 'column' => 'posted_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_sales_invoice_line', 'column' => 'sales_invoice_id', 'ref_table' => 'fin_sales_invoice', 'ref_column' => 'sales_invoice_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_sales_invoice_line', 'column' => 'account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_sales_invoice_line', 'column' => 'department_id', 'ref_table' => 'fin_department', 'ref_column' => 'department_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_sales_invoice_line', 'column' => 'tax_id', 'ref_table' => 'fin_tax', 'ref_column' => 'tax_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_settings', 'column' => 'receivable_account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_settings', 'column' => 'payable_account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_settings', 'column' => 'fx_gain_account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_settings', 'column' => 'fx_loss_account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_supplier', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_supplier', 'column' => 'updated_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_supplier_payment', 'column' => 'supplier_id', 'ref_table' => 'fin_supplier', 'ref_column' => 'supplier_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_supplier_payment', 'column' => 'cash_account_id', 'ref_table' => 'fin_cash_account', 'ref_column' => 'cash_account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_supplier_payment', 'column' => 'approval_request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_supplier_payment', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_supplier_payment', 'column' => 'posted_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_supplier_payment_allocation', 'column' => 'supplier_payment_id', 'ref_table' => 'fin_supplier_payment', 'ref_column' => 'supplier_payment_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_supplier_payment_allocation', 'column' => 'purchase_bill_id', 'ref_table' => 'fin_purchase_bill', 'ref_column' => 'purchase_bill_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_tax', 'column' => 'account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_tax', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_transaction', 'column' => 'cash_account_id', 'ref_table' => 'fin_cash_account', 'ref_column' => 'cash_account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_transaction', 'column' => 'approval_request_id', 'ref_table' => 'app_approval_request', 'ref_column' => 'request_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_transaction', 'column' => 'created_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_transaction', 'column' => 'posted_by', 'ref_table' => 'app_user', 'ref_column' => 'user_id', 'on_delete' => 'SET NULL'),
        array('table' => 'fin_transaction_line', 'column' => 'transaction_id', 'ref_table' => 'fin_transaction', 'ref_column' => 'transaction_id', 'on_delete' => 'CASCADE'),
        array('table' => 'fin_transaction_line', 'column' => 'category_id', 'ref_table' => 'fin_category', 'ref_column' => 'category_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_transaction_line', 'column' => 'account_id', 'ref_table' => 'fin_account', 'ref_column' => 'account_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_transaction_line', 'column' => 'department_id', 'ref_table' => 'fin_department', 'ref_column' => 'department_id', 'on_delete' => 'RESTRICT'),
        array('table' => 'fin_transaction_line', 'column' => 'tax_id', 'ref_table' => 'fin_tax', 'ref_column' => 'tax_id', 'on_delete' => 'RESTRICT'),
    );

    public function up()
    {
        if (!$this->db->table_exists('sys_tenant')) {
            return;
        }
        $this->align_flow_code_collations();
        // First pass: align types/nullability before cleaning sentinel values.
        foreach ($this->foreign_keys as $fk) {
            if (!$this->db->table_exists($fk['table']) || !$this->db->table_exists($fk['ref_table'])) {
                continue;
            }
            $column = $this->column($fk['table'], $fk['column']);
            $reference = $this->column($fk['ref_table'], $fk['ref_column']);
            if (!$column || !$reference) {
                continue;
            }
            $nullable = strtoupper((string) $column['Null']) === 'YES' || !empty($fk['nullable']);
            if (strtolower((string) $column['Type']) !== strtolower((string) $reference['Type'])
                || (strtoupper((string) $column['Null']) === 'YES') !== $nullable) {
                $null_sql = $nullable ? 'NULL' : 'NOT NULL';
                $this->db->query('ALTER TABLE `' . $fk['table'] . '` MODIFY `' . $fk['column'] . '` '
                    . $reference['Type'] . ' ' . $null_sql);
            }
        }
        $this->cleanup_invalid_references();
        // Second pass: indexes and foreign keys.
        foreach ($this->foreign_keys as $fk) {
            if (!$this->db->table_exists($fk['table']) || !$this->db->table_exists($fk['ref_table'])) {
                continue;
            }
            if (!$this->has_index($fk['table'], $fk['column'])) {
                $this->db->query('ALTER TABLE `' . $fk['table'] . '` ADD INDEX `idx_'
                    . $fk['table'] . '_' . $fk['column'] . '` (`' . $fk['column'] . '`)');
            }
            if (!$this->has_foreign_key($fk['table'], $fk['column'])) {
                $on_delete = $fk['on_delete'] === 'RESTRICT' ? 'RESTRICT' : $fk['on_delete'];
                $this->db->query('ALTER TABLE `' . $fk['table'] . '` ADD CONSTRAINT `fk_'
                    . $fk['table'] . '_' . $fk['column'] . '` FOREIGN KEY (`' . $fk['column'] . '`)'
                    . ' REFERENCES `' . $fk['ref_table'] . '` (`' . $fk['ref_column'] . '`)'
                    . ' ON UPDATE CASCADE ON DELETE ' . $on_delete);
            }
        }
    }

    public function down()
    {
        foreach ($this->foreign_keys as $fk) {
            if ($this->db->table_exists($fk['table']) && $this->has_foreign_key($fk['table'], $fk['column'])) {
                $this->db->query('ALTER TABLE `' . $fk['table'] . '` DROP FOREIGN KEY `fk_'
                    . $fk['table'] . '_' . $fk['column'] . '`');
            }
        }
    }

    private function cleanup_invalid_references()
    {
        if ($this->db->table_exists('app_approval_flow_step')) {
            $this->db->query('UPDATE `app_approval_flow_step` SET `approver_user_id`=NULL WHERE `approver_user_id`=0');
        }
        if ($this->db->table_exists('sys_approval_flow_step')) {
            $this->db->query('UPDATE `sys_approval_flow_step` SET `approver_user_id`=NULL WHERE `approver_user_id`=0');
        }
        if ($this->db->table_exists('app_approval_link_token') && $this->db->table_exists('app_approval_request')) {
            $this->db->query('DELETE t FROM `app_approval_link_token` t LEFT JOIN `app_approval_request` r'
                . ' ON r.request_id=t.request_id WHERE r.request_id IS NULL');
        }
    }

    private function align_flow_code_collations()
    {
        if ($this->db->table_exists('app_approval_flow')) {
            $this->db->query('ALTER TABLE `app_approval_flow` MODIFY `flow_code` varchar(32)'
                . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL');
        }
        if ($this->db->table_exists('app_approval_request')) {
            $this->db->query('ALTER TABLE `app_approval_request` MODIFY `flow_code` varchar(32)'
                . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL');
        }
    }

    private function column($table, $column)
    {
        $query = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape($column));
        return $query !== FALSE && $query->num_rows() ? $query->row_array() : NULL;
    }

    private function has_index($table, $column)
    {
        $query = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Column_name=' . $this->db->escape($column));
        if ($query === FALSE) {
            return FALSE;
        }
        foreach ($query->result_array() as $index) {
            if (isset($index['Seq_in_index']) && (int) $index['Seq_in_index'] === 1) {
                return TRUE;
            }
        }
        return FALSE;
    }

    private function has_foreign_key($table, $column)
    {
        $query = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE'
            . ' WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=' . $this->db->escape($table)
            . ' AND COLUMN_NAME=' . $this->db->escape($column)
            . ' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1'
        );
        return $query !== FALSE && $query->num_rows() > 0;
    }
}
