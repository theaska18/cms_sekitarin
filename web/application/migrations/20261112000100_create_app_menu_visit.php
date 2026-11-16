<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_app_menu_visit extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('app_menu_visit')) {
            $this->dbforge->add_field(array(
                'menu_visit_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'tenant_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                ),
                'user_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                ),
                'menu_code' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ),
                'visited_at' => array(
                    'type' => 'DATETIME',
                ),
            ));
            $this->dbforge->add_key('menu_visit_id', TRUE);
            $this->dbforge->add_key(array('tenant_id', 'visited_at', 'menu_code'));
            $this->dbforge->add_key(array('tenant_id', 'user_id', 'visited_at'));
            $this->dbforge->create_table('app_menu_visit', TRUE);
        }
    }

    public function down()
    {
        // Keep analytics history when rolling back application code.
    }
}
