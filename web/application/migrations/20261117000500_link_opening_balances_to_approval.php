<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Link opening-balance journals to approval and register their sequence template. */
class Migration_Link_opening_balances_to_approval extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('fin_journal_entry')) {
            if (!$this->db->field_exists('approval_request_id', 'fin_journal_entry')) {
                $this->dbforge->add_column('fin_journal_entry', array(
                    'approval_request_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                ));
            }
            $exists = $this->db->query(
                'SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                array($this->db->database, 'fin_journal_entry', 'idx_fin_journal_entry_approval')
            );
            if ($exists === FALSE || $exists->num_rows() === 0) {
                $this->db->query(
                    'ALTER TABLE `fin_journal_entry` ADD KEY `idx_fin_journal_entry_approval` (`tenant_id`, `approval_request_id`)'
                );
            }
        }

        if ($this->db->table_exists('sys_sequence')) {
            $exists = $this->db->where('sequence_code', 'journal_opening_balance')
                ->count_all_results('sys_sequence') > 0;
            if (!$exists) {
                $this->db->insert('sys_sequence', array(
                    'sequence_code' => 'journal_opening_balance',
                    'sequence_name' => 'Opening Balance Journal Number',
                    'description' => NULL,
                    'repeat_type' => 'repeat_type_daily',
                    'format' => 'OB-{Y}{M}{D}-{NNNNN}',
                ));
            }
        }
    }

    public function down()
    {
        // Approval links and journal numbers are retained so history stays traceable.
    }
}
