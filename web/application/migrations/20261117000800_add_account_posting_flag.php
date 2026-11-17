<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Distinguish posting accounts from hierarchy-only group accounts. */
class Migration_Add_account_posting_flag extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('fin_account')) {
            return;
        }
        if (!$this->db->field_exists('posting_flag', 'fin_account')) {
            $this->dbforge->add_column('fin_account', array(
                'posting_flag' => array('type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 1),
            ));
        }
        $this->db->where('tenant_id', 1)
            ->where_in('account_code', array('100', '110', '150', '200', '210', '220', '300', '400', '500', '600', '700', '800'))
            ->update('fin_account', array('posting_flag' => 0));
    }

    public function down()
    {
        // Posting classification is retained so historical reports remain stable.
    }
}
