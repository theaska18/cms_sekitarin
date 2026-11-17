<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Link finance documents, payments, and reconciliations to approval requests. */
class Migration_Link_finance_documents_to_approval extends CI_Migration
{
    public function up()
    {
        $targets = array(
            'fin_sales_invoice' => 'idx_fin_sales_invoice_approval',
            'fin_purchase_bill' => 'idx_fin_purchase_bill_approval',
            'fin_customer_payment' => 'idx_fin_customer_payment_approval',
            'fin_supplier_payment' => 'idx_fin_supplier_payment_approval',
            'fin_bank_reconciliation' => 'idx_fin_bank_reconciliation_approval',
        );
        foreach ($targets as $table => $index) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            if (!$this->db->field_exists('approval_request_id', $table)) {
                $this->dbforge->add_column($table, array(
                    'approval_request_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                ));
            }
            $exists = $this->db->query(
                'SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                array($this->db->database, $table, $index)
            );
            if ($exists === FALSE || $exists->num_rows() === 0) {
                $this->db->query('ALTER TABLE `' . $table . '` ADD KEY `' . $index . '` (`tenant_id`, `approval_request_id`)');
            }
        }
        if ($this->db->table_exists('fin_bank_reconciliation')
            && !$this->db->field_exists('reconciliation_number', 'fin_bank_reconciliation')) {
            $this->dbforge->add_column('fin_bank_reconciliation', array(
                'reconciliation_number' => array('type' => 'VARCHAR', 'constraint' => 48, 'null' => TRUE),
            ));
        }
    }

    public function down()
    {
        // Approval links and document numbers are retained so history stays traceable.
    }
}
