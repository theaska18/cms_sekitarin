<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Add tenant-selected control and FX accounts used by Finance document posting. */
class Migration_Add_finance_control_accounts extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('fin_settings')) {
            return;
        }
        foreach (array('receivable_account_id', 'payable_account_id', 'fx_gain_account_id', 'fx_loss_account_id') as $column) {
            if (!$this->db->field_exists($column, 'fin_settings')) {
                $this->dbforge->add_column('fin_settings', array(
                    $column => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                ));
            }
        }
    }

    public function down()
    {
        // Tenant configuration is retained; removing account mappings requires an explicit data migration.
    }
}
