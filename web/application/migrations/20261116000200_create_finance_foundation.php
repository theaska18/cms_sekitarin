<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Initial tenant-scoped Finance data foundation.
 *
 * This migration creates schema only. It does not seed accounts, currencies,
 * tax rates, numbering sequences, or transaction data.
 */
class Migration_Create_finance_foundation extends CI_Migration
{
    public function up()
    {
        $tables = array(
            'fin_settings' => array(
                'id' => 'settings_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'base_currency' => $this->char_field(3),
                    'tax_inclusive_default' => $this->tinyint(0),
                    'create_on' => $this->datetime(),
                    'update_on' => $this->nullable_datetime(),
                ),
                'indexes' => array(array('tenant_id', TRUE)),
            ),
            'fin_exchange_rate' => array(
                'id' => 'exchange_rate_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'from_currency' => $this->char_field(3),
                    'to_currency' => $this->char_field(3),
                    'effective_on' => array('type' => 'DATE'),
                    'rate' => $this->decimal(20, 10),
                    'rate_source' => $this->varchar_nullable(40),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(array('tenant_id', 'from_currency', 'to_currency', 'effective_on', TRUE)),
            ),
            'fin_account' => array(
                'id' => 'account_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'parent_account_id' => $this->nullable_bigint(),
                    'account_code' => $this->varchar(32),
                    'account_name' => $this->varchar(120),
                    'account_type' => $this->varchar(24),
                    'normal_balance' => $this->varchar(6),
                    'description' => $this->text_nullable(),
                    'active_flag' => $this->tinyint(1),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->nullable_bigint(),
                    'update_on' => $this->nullable_datetime(),
                    'updated_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'account_code', TRUE),
                    array('tenant_id', 'parent_account_id'),
                    array('tenant_id', 'account_type', 'active_flag'),
                ),
            ),
            'fin_fiscal_year' => array(
                'id' => 'fiscal_year_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'year_code' => $this->varchar(32),
                    'year_name' => $this->varchar(80),
                    'start_on' => array('type' => 'DATE'),
                    'end_on' => array('type' => 'DATE'),
                    'status' => $this->varchar_default(16, 'open'),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->nullable_bigint(),
                    'update_on' => $this->nullable_datetime(),
                ),
                'indexes' => array(
                    array('tenant_id', 'year_code', TRUE),
                    array('tenant_id', 'start_on', 'end_on'),
                ),
            ),
            'fin_accounting_period' => array(
                'id' => 'period_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'fiscal_year_id' => $this->bigint(),
                    'period_number' => $this->int_field(),
                    'period_name' => $this->varchar(80),
                    'start_on' => array('type' => 'DATE'),
                    'end_on' => array('type' => 'DATE'),
                    'status' => $this->varchar_default(16, 'open'),
                    'create_on' => $this->datetime(),
                    'update_on' => $this->nullable_datetime(),
                ),
                'indexes' => array(
                    array('tenant_id', 'fiscal_year_id', 'period_number', TRUE),
                    array('tenant_id', 'start_on', 'end_on'),
                ),
            ),
            'fin_department' => array(
                'id' => 'department_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'department_code' => $this->varchar(32),
                    'department_name' => $this->varchar(100),
                    'active_flag' => $this->tinyint(1),
                    'create_on' => $this->datetime(),
                    'update_on' => $this->nullable_datetime(),
                ),
                'indexes' => array(array('tenant_id', 'department_code', TRUE)),
            ),
            'fin_category' => array(
                'id' => 'category_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'category_type' => $this->varchar(16),
                    'category_code' => $this->varchar(32),
                    'category_name' => $this->varchar(100),
                    'active_flag' => $this->tinyint(1),
                    'create_on' => $this->datetime(),
                    'update_on' => $this->nullable_datetime(),
                ),
                'indexes' => array(
                    array('tenant_id', 'category_type', 'category_code', TRUE),
                    array('tenant_id', 'category_type', 'active_flag'),
                ),
            ),
            'fin_tax' => array(
                'id' => 'tax_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'account_id' => $this->nullable_bigint(),
                    'tax_code' => $this->varchar(32),
                    'tax_name' => $this->varchar(100),
                    'rate_percent' => $this->decimal(9, 6),
                    'active_flag' => $this->tinyint(1),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->nullable_bigint(),
                    'update_on' => $this->nullable_datetime(),
                ),
                'indexes' => array(
                    array('tenant_id', 'tax_code', TRUE),
                    array('tenant_id', 'active_flag'),
                ),
            ),
            'fin_customer' => array(
                'id' => 'customer_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'customer_code' => $this->varchar(32),
                    'customer_name' => $this->varchar(160),
                    'email_address' => $this->varchar_nullable(160),
                    'phone_number' => $this->varchar_nullable(32),
                    'tax_number' => $this->varchar_nullable(64),
                    'billing_address' => $this->text_nullable(),
                    'active_flag' => $this->tinyint(1),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->nullable_bigint(),
                    'update_on' => $this->nullable_datetime(),
                    'updated_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'customer_code', TRUE),
                    array('tenant_id', 'customer_name'),
                ),
            ),
            'fin_supplier' => array(
                'id' => 'supplier_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'supplier_code' => $this->varchar(32),
                    'supplier_name' => $this->varchar(160),
                    'email_address' => $this->varchar_nullable(160),
                    'phone_number' => $this->varchar_nullable(32),
                    'tax_number' => $this->varchar_nullable(64),
                    'billing_address' => $this->text_nullable(),
                    'active_flag' => $this->tinyint(1),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->nullable_bigint(),
                    'update_on' => $this->nullable_datetime(),
                    'updated_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'supplier_code', TRUE),
                    array('tenant_id', 'supplier_name'),
                ),
            ),
            'fin_cash_account' => array(
                'id' => 'cash_account_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'account_id' => $this->bigint(),
                    'account_code' => $this->varchar(32),
                    'account_name' => $this->varchar(120),
                    'account_kind' => $this->varchar(8),
                    'currency_code' => $this->char_field(3),
                    'institution_name' => $this->varchar_nullable(120),
                    'active_flag' => $this->tinyint(1),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->nullable_bigint(),
                    'update_on' => $this->nullable_datetime(),
                ),
                'indexes' => array(
                    array('tenant_id', 'account_code', TRUE),
                    array('tenant_id', 'account_id'),
                ),
            ),
            'fin_transaction' => array(
                'id' => 'transaction_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'cash_account_id' => $this->bigint(),
                    'transaction_number' => $this->varchar(48),
                    'transaction_type' => $this->varchar(8),
                    'transaction_on' => array('type' => 'DATE'),
                    'currency_code' => $this->char_field(3),
                    'exchange_rate' => $this->decimal(20, 10),
                    'subtotal' => $this->decimal(20, 6),
                    'tax_total' => $this->decimal_default(20, 6, '0'),
                    'total_amount' => $this->decimal(20, 6),
                    'base_total_amount' => $this->decimal(20, 6),
                    'status' => $this->varchar_default(16, 'draft'),
                    'description' => $this->text_nullable(),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->bigint(),
                    'posted_on' => $this->nullable_datetime(),
                    'posted_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'transaction_number', TRUE),
                    array('tenant_id', 'transaction_on', 'status'),
                    array('tenant_id', 'cash_account_id', 'transaction_on'),
                ),
            ),
            'fin_transaction_line' => array(
                'id' => 'transaction_line_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'transaction_id' => $this->bigint(),
                    'category_id' => $this->nullable_bigint(),
                    'account_id' => $this->bigint(),
                    'department_id' => $this->nullable_bigint(),
                    'tax_id' => $this->nullable_bigint(),
                    'description' => $this->varchar(240),
                    'quantity' => $this->decimal_default(20, 6, '1'),
                    'unit_price' => $this->decimal(20, 6),
                    'line_subtotal' => $this->decimal(20, 6),
                    'tax_rate_snapshot' => $this->decimal_default(9, 6, '0'),
                    'tax_amount' => $this->decimal_default(20, 6, '0'),
                    'line_total' => $this->decimal(20, 6),
                    'base_line_total' => $this->decimal(20, 6),
                    'base_tax_amount' => $this->decimal_default(20, 6, '0'),
                ),
                'indexes' => array(
                    array('tenant_id', 'transaction_id'),
                    array('tenant_id', 'account_id'),
                    array('tenant_id', 'category_id'),
                ),
            ),
            'fin_sales_invoice' => array(
                'id' => 'sales_invoice_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'customer_id' => $this->bigint(),
                    'invoice_number' => $this->varchar(48),
                    'invoice_date' => array('type' => 'DATE'),
                    'due_date' => array('type' => 'DATE', 'null' => TRUE),
                    'currency_code' => $this->char_field(3),
                    'exchange_rate' => $this->decimal(20, 10),
                    'tax_inclusive' => $this->tinyint(0),
                    'subtotal' => $this->decimal(20, 6),
                    'tax_total' => $this->decimal_default(20, 6, '0'),
                    'total_amount' => $this->decimal(20, 6),
                    'base_total_amount' => $this->decimal(20, 6),
                    'status' => $this->varchar_default(16, 'draft'),
                    'memo' => $this->text_nullable(),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->bigint(),
                    'posted_on' => $this->nullable_datetime(),
                    'posted_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'invoice_number', TRUE),
                    array('tenant_id', 'customer_id', 'invoice_date'),
                    array('tenant_id', 'status', 'due_date'),
                ),
            ),
            'fin_sales_invoice_line' => array(
                'id' => 'sales_invoice_line_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'sales_invoice_id' => $this->bigint(),
                    'account_id' => $this->bigint(),
                    'department_id' => $this->nullable_bigint(),
                    'tax_id' => $this->nullable_bigint(),
                    'description' => $this->varchar(240),
                    'quantity' => $this->decimal_default(20, 6, '1'),
                    'unit_price' => $this->decimal(20, 6),
                    'tax_rate_snapshot' => $this->decimal_default(9, 6, '0'),
                    'line_subtotal' => $this->decimal(20, 6),
                    'tax_amount' => $this->decimal_default(20, 6, '0'),
                    'line_total' => $this->decimal(20, 6),
                    'base_line_total' => $this->decimal(20, 6),
                ),
                'indexes' => array(
                    array('tenant_id', 'sales_invoice_id'),
                    array('tenant_id', 'account_id'),
                ),
            ),
            'fin_customer_payment' => array(
                'id' => 'customer_payment_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'customer_id' => $this->bigint(),
                    'cash_account_id' => $this->bigint(),
                    'payment_number' => $this->varchar(48),
                    'payment_date' => array('type' => 'DATE'),
                    'currency_code' => $this->char_field(3),
                    'exchange_rate' => $this->decimal(20, 10),
                    'amount' => $this->decimal(20, 6),
                    'base_amount' => $this->decimal(20, 6),
                    'status' => $this->varchar_default(16, 'draft'),
                    'reference' => $this->varchar_nullable(80),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->bigint(),
                    'posted_on' => $this->nullable_datetime(),
                    'posted_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'payment_number', TRUE),
                    array('tenant_id', 'customer_id', 'payment_date'),
                    array('tenant_id', 'cash_account_id', 'payment_date'),
                ),
            ),
            'fin_customer_payment_allocation' => array(
                'id' => 'customer_payment_allocation_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'customer_payment_id' => $this->bigint(),
                    'sales_invoice_id' => $this->bigint(),
                    'amount' => $this->decimal(20, 6),
                    'base_amount' => $this->decimal(20, 6),
                ),
                'indexes' => array(
                    array('tenant_id', 'customer_payment_id', 'sales_invoice_id', TRUE),
                    array('tenant_id', 'sales_invoice_id'),
                ),
            ),
            'fin_purchase_bill' => array(
                'id' => 'purchase_bill_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'supplier_id' => $this->bigint(),
                    'bill_number' => $this->varchar(48),
                    'supplier_reference' => $this->varchar_nullable(80),
                    'bill_date' => array('type' => 'DATE'),
                    'due_date' => array('type' => 'DATE', 'null' => TRUE),
                    'currency_code' => $this->char_field(3),
                    'exchange_rate' => $this->decimal(20, 10),
                    'tax_inclusive' => $this->tinyint(0),
                    'subtotal' => $this->decimal(20, 6),
                    'tax_total' => $this->decimal_default(20, 6, '0'),
                    'total_amount' => $this->decimal(20, 6),
                    'base_total_amount' => $this->decimal(20, 6),
                    'status' => $this->varchar_default(16, 'draft'),
                    'memo' => $this->text_nullable(),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->bigint(),
                    'posted_on' => $this->nullable_datetime(),
                    'posted_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'bill_number', TRUE),
                    array('tenant_id', 'supplier_id', 'bill_date'),
                    array('tenant_id', 'status', 'due_date'),
                ),
            ),
            'fin_purchase_bill_line' => array(
                'id' => 'purchase_bill_line_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'purchase_bill_id' => $this->bigint(),
                    'account_id' => $this->bigint(),
                    'department_id' => $this->nullable_bigint(),
                    'tax_id' => $this->nullable_bigint(),
                    'description' => $this->varchar(240),
                    'quantity' => $this->decimal_default(20, 6, '1'),
                    'unit_price' => $this->decimal(20, 6),
                    'tax_rate_snapshot' => $this->decimal_default(9, 6, '0'),
                    'line_subtotal' => $this->decimal(20, 6),
                    'tax_amount' => $this->decimal_default(20, 6, '0'),
                    'line_total' => $this->decimal(20, 6),
                    'base_line_total' => $this->decimal(20, 6),
                ),
                'indexes' => array(
                    array('tenant_id', 'purchase_bill_id'),
                    array('tenant_id', 'account_id'),
                ),
            ),
            'fin_supplier_payment' => array(
                'id' => 'supplier_payment_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'supplier_id' => $this->bigint(),
                    'cash_account_id' => $this->bigint(),
                    'payment_number' => $this->varchar(48),
                    'payment_date' => array('type' => 'DATE'),
                    'currency_code' => $this->char_field(3),
                    'exchange_rate' => $this->decimal(20, 10),
                    'amount' => $this->decimal(20, 6),
                    'base_amount' => $this->decimal(20, 6),
                    'status' => $this->varchar_default(16, 'draft'),
                    'reference' => $this->varchar_nullable(80),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->bigint(),
                    'posted_on' => $this->nullable_datetime(),
                    'posted_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'payment_number', TRUE),
                    array('tenant_id', 'supplier_id', 'payment_date'),
                    array('tenant_id', 'cash_account_id', 'payment_date'),
                ),
            ),
            'fin_supplier_payment_allocation' => array(
                'id' => 'supplier_payment_allocation_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'supplier_payment_id' => $this->bigint(),
                    'purchase_bill_id' => $this->bigint(),
                    'amount' => $this->decimal(20, 6),
                    'base_amount' => $this->decimal(20, 6),
                ),
                'indexes' => array(
                    array('tenant_id', 'supplier_payment_id', 'purchase_bill_id', TRUE),
                    array('tenant_id', 'purchase_bill_id'),
                ),
            ),
            'fin_cash_transfer' => array(
                'id' => 'cash_transfer_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'from_cash_account_id' => $this->bigint(),
                    'to_cash_account_id' => $this->bigint(),
                    'transfer_number' => $this->varchar(48),
                    'transfer_date' => array('type' => 'DATE'),
                    'from_currency' => $this->char_field(3),
                    'to_currency' => $this->char_field(3),
                    'from_exchange_rate' => $this->decimal(20, 10),
                    'to_exchange_rate' => $this->decimal(20, 10),
                    'from_amount' => $this->decimal(20, 6),
                    'to_amount' => $this->decimal(20, 6),
                    'fee_amount' => $this->decimal_default(20, 6, '0'),
                    'base_from_amount' => $this->decimal(20, 6),
                    'base_to_amount' => $this->decimal(20, 6),
                    'status' => $this->varchar_default(16, 'draft'),
                    'memo' => $this->text_nullable(),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->bigint(),
                    'posted_on' => $this->nullable_datetime(),
                    'posted_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'transfer_number', TRUE),
                    array('tenant_id', 'transfer_date', 'status'),
                    array('tenant_id', 'from_cash_account_id', 'transfer_date'),
                    array('tenant_id', 'to_cash_account_id', 'transfer_date'),
                ),
            ),
            'fin_journal_entry' => array(
                'id' => 'journal_entry_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'period_id' => $this->nullable_bigint(),
                    'journal_number' => $this->varchar(48),
                    'journal_date' => array('type' => 'DATE'),
                    'source_type' => $this->varchar(32),
                    'source_id' => $this->nullable_bigint(),
                    'description' => $this->varchar(240),
                    'status' => $this->varchar_default(16, 'draft'),
                    'debit_total' => $this->decimal_default(20, 6, '0'),
                    'credit_total' => $this->decimal_default(20, 6, '0'),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->bigint(),
                    'posted_on' => $this->nullable_datetime(),
                    'posted_by' => $this->nullable_bigint(),
                    'reversed_entry_id' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'journal_number', TRUE),
                    array('tenant_id', 'journal_date', 'status'),
                    array('tenant_id', 'source_type', 'source_id', TRUE),
                    array('tenant_id', 'period_id', 'journal_date'),
                ),
            ),
            'fin_journal_line' => array(
                'id' => 'journal_line_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'journal_entry_id' => $this->bigint(),
                    'account_id' => $this->bigint(),
                    'department_id' => $this->nullable_bigint(),
                    'line_number' => $this->int_field(),
                    'description' => $this->varchar_nullable(240),
                    'currency_code' => $this->char_field(3),
                    'exchange_rate' => $this->decimal(20, 10),
                    'debit_amount' => $this->decimal_default(20, 6, '0'),
                    'credit_amount' => $this->decimal_default(20, 6, '0'),
                    'debit_base_amount' => $this->decimal_default(20, 6, '0'),
                    'credit_base_amount' => $this->decimal_default(20, 6, '0'),
                ),
                'indexes' => array(
                    array('tenant_id', 'journal_entry_id', 'line_number', TRUE),
                    array('tenant_id', 'account_id'),
                    array('tenant_id', 'department_id'),
                ),
            ),
            'fin_bank_reconciliation' => array(
                'id' => 'reconciliation_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'cash_account_id' => $this->bigint(),
                    'statement_date' => array('type' => 'DATE'),
                    'statement_reference' => $this->varchar_nullable(80),
                    'opening_balance' => $this->decimal(20, 6),
                    'closing_balance' => $this->decimal(20, 6),
                    'status' => $this->varchar_default(16, 'draft'),
                    'create_on' => $this->datetime(),
                    'created_by' => $this->bigint(),
                    'completed_on' => $this->nullable_datetime(),
                    'completed_by' => $this->nullable_bigint(),
                ),
                'indexes' => array(
                    array('tenant_id', 'cash_account_id', 'statement_date', TRUE),
                    array('tenant_id', 'status', 'statement_date'),
                ),
            ),
            'fin_bank_reconciliation_line' => array(
                'id' => 'reconciliation_line_id',
                'fields' => array(
                    'tenant_id' => $this->bigint(),
                    'reconciliation_id' => $this->bigint(),
                    'journal_line_id' => $this->nullable_bigint(),
                    'statement_on' => array('type' => 'DATE'),
                    'description' => $this->varchar_nullable(240),
                    'statement_amount' => $this->decimal(20, 6),
                    'matched_flag' => $this->tinyint(0),
                ),
                'indexes' => array(
                    array('tenant_id', 'reconciliation_id'),
                    array('tenant_id', 'journal_line_id'),
                ),
            ),
        );

        foreach ($tables as $table => $definition) {
            $this->create_if_missing($table, $definition['id'], $definition['fields'], $definition['indexes']);
        }
    }

    public function down()
    {
        // Finance records are retained on rollback; destructive drops require a separate approved data migration.
    }

    private function create_if_missing($table, $id_name, $fields, $indexes)
    {
        if ($this->db->table_exists($table)) {
            return;
        }

        $this->dbforge->add_field(array_merge(array($id_name => $this->id_field()), $fields));
        $this->dbforge->add_key($id_name, TRUE);
        foreach ($indexes as $index) {
            if (end($index) === TRUE) {
                array_pop($index);
            }
            // CodeIgniter 3 DBForge supports ordinary indexes only; uniqueness
            // is applied explicitly by the following migration.
            $this->dbforge->add_key($index);
        }
        $this->dbforge->create_table($table, TRUE);
    }

    private function id_field()
    {
        return array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'auto_increment' => TRUE);
    }

    private function bigint()
    {
        return array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE);
    }

    private function nullable_bigint()
    {
        return array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE);
    }

    private function int_field()
    {
        return array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE);
    }

    private function int_default($default)
    {
        return array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => $default);
    }

    private function tinyint($default)
    {
        return array('type' => 'TINYINT', 'constraint' => 1, 'unsigned' => TRUE, 'default' => $default);
    }

    private function bigint_default($default)
    {
        return array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'default' => $default);
    }

    private function varchar($length)
    {
        return array('type' => 'VARCHAR', 'constraint' => $length);
    }

    private function varchar_nullable($length)
    {
        return array('type' => 'VARCHAR', 'constraint' => $length, 'null' => TRUE);
    }

    private function varchar_default($length, $default)
    {
        return array('type' => 'VARCHAR', 'constraint' => $length, 'default' => $default);
    }

    private function char_field($length)
    {
        return array('type' => 'CHAR', 'constraint' => $length);
    }

    private function text_nullable()
    {
        return array('type' => 'TEXT', 'null' => TRUE);
    }

    private function decimal($precision, $scale)
    {
        return array('type' => 'DECIMAL', 'constraint' => $precision . ',' . $scale);
    }

    private function decimal_default($precision, $scale, $default)
    {
        return array('type' => 'DECIMAL', 'constraint' => $precision . ',' . $scale, 'default' => $default);
    }

    private function datetime()
    {
        return array('type' => 'DATETIME');
    }

    private function nullable_datetime()
    {
        return array('type' => 'DATETIME', 'null' => TRUE);
    }
}
