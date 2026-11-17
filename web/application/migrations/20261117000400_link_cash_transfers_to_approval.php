<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Link cash/bank transfers to their approval request lifecycle. */
class Migration_Link_cash_transfers_to_approval extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('fin_cash_transfer')) {
            return;
        }
        if (!$this->db->field_exists('approval_request_id', 'fin_cash_transfer')) {
            $this->dbforge->add_column('fin_cash_transfer', array(
                'approval_request_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
            ));
        }
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            array($this->db->database, 'fin_cash_transfer', 'idx_fin_cash_transfer_approval')
        );
        if ($exists === FALSE || $exists->num_rows() === 0) {
            $this->db->query(
                'ALTER TABLE `fin_cash_transfer` ADD KEY `idx_fin_cash_transfer_approval` (`tenant_id`, `approval_request_id`)'
            );
        }
    }

    public function down()
    {
        // Approval links are retained so transfer history stays traceable.
    }
}
