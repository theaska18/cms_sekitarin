<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Global approval flow templates, their steps, and their menu mapping. */
class Migration_Create_sys_approval_flow extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('sys_approval_flow')) {
            $this->dbforge->add_field(array(
                'flow_code' => array('type' => 'VARCHAR', 'constraint' => 32),
                'flow_name' => array('type' => 'VARCHAR', 'constraint' => 120),
                'description' => array('type' => 'VARCHAR', 'constraint' => 256, 'null' => TRUE),
                'active_flag' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
                'create_on' => array('type' => 'DATETIME'),
                'update_on' => array('type' => 'DATETIME', 'null' => TRUE),
            ));
            $this->dbforge->add_key('flow_code', TRUE);
            $this->dbforge->create_table('sys_approval_flow', TRUE);
        }

        if (!$this->db->table_exists('sys_approval_flow_step')) {
            $this->dbforge->add_field(array(
                'flow_step_id' => array(
                    'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'auto_increment' => TRUE,
                ),
                'flow_code' => array('type' => 'VARCHAR', 'constraint' => 32),
                'step_number' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
                'approver_user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 0),
                'create_on' => array('type' => 'DATETIME'),
                'approver_type' => array('type' => 'VARCHAR', 'constraint' => 16, 'default' => 'user'),
                'approver_employee_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                'org_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
            ));
            $this->dbforge->add_key('flow_step_id', TRUE);
            $this->dbforge->add_key(array('flow_code', 'step_number'), FALSE, TRUE);
            $this->dbforge->add_key('approver_user_id');
            $this->dbforge->create_table('sys_approval_flow_step', TRUE);
        }

        if (!$this->db->table_exists('sys_approval_flow_menu')) {
            $this->dbforge->add_field(array(
                'flow_menu_id' => array(
                    'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'auto_increment' => TRUE,
                ),
                'flow_code' => array('type' => 'VARCHAR', 'constraint' => 32),
                'menu_code' => array('type' => 'VARCHAR', 'constraint' => 32),
            ));
            $this->dbforge->add_key('flow_menu_id', TRUE);
            $this->dbforge->add_key(array('flow_code', 'menu_code'), FALSE, TRUE);
            $this->dbforge->add_key('menu_code');
            $this->dbforge->create_table('sys_approval_flow_menu', TRUE);
        }

        $this->add_foreign_key(
            'sys_approval_flow_step',
            'fk_sys_approval_flow_step_code',
            'flow_code',
            'sys_approval_flow',
            'flow_code'
        );
        $this->add_foreign_key(
            'sys_approval_flow_menu',
            'fk_sys_approval_flow_menu_code',
            'flow_code',
            'sys_approval_flow',
            'flow_code'
        );
    }

    public function down()
    {
        // Retain configured approval templates on rollback; dropping them would lose admin data.
    }

    private function add_foreign_key($table, $name, $column, $reference_table, $reference_column)
    {
        if (!$this->db->table_exists($table) || !$this->db->table_exists($reference_table)) {
            return;
        }
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = \'FOREIGN KEY\' LIMIT 1',
            array($this->db->database, $table, $name)
        );
        if ($exists !== FALSE && $exists->num_rows() > 0) {
            return;
        }
        $this->db->query(
            'ALTER TABLE `' . $table . '` ADD CONSTRAINT `' . $name . '` '
            . 'FOREIGN KEY (`' . $column . '`) REFERENCES `' . $reference_table . '` (`' . $reference_column . '`) '
            . 'ON DELETE CASCADE ON UPDATE CASCADE'
        );
    }
}
