<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Link finance income/expense drafts to their approval request lifecycle. */
class Migration_Link_finance_transactions_to_approval extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('app_approval_request')) {
            if (!$this->db->field_exists('source_type', 'app_approval_request')) {
                $this->dbforge->add_column('app_approval_request', array(
                    'source_type' => array('type' => 'VARCHAR', 'constraint' => 32, 'null' => TRUE),
                ));
            }
            if (!$this->db->field_exists('source_id', 'app_approval_request')) {
                $this->dbforge->add_column('app_approval_request', array(
                    'source_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                ));
            }
            $this->add_index('app_approval_request', 'idx_app_approval_request_source', array('tenant_id', 'source_type', 'source_id'));
        }

        if ($this->db->table_exists('fin_transaction')) {
            if (!$this->db->field_exists('approval_request_id', 'fin_transaction')) {
                $this->dbforge->add_column('fin_transaction', array(
                    'approval_request_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                ));
            }
            $this->add_index('fin_transaction', 'idx_fin_transaction_approval', array('tenant_id', 'approval_request_id'));
        }
    }

    public function down()
    {
        // Approval links are retained so finance history stays traceable on rollback.
    }

    private function add_index($table, $name, $columns)
    {
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            array($this->db->database, $table, $name)
        );
        if ($exists !== FALSE && $exists->num_rows() > 0) {
            return;
        }
        $this->db->query(
            'ALTER TABLE `' . $table . '` ADD KEY `' . $name . '` (`' . implode('`, `', $columns) . '`)'
        );
    }
}
