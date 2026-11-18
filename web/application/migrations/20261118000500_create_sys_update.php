<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** One-click update history and applied update migrations. */
class Migration_Create_sys_update extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('sys_update')) {
            $this->dbforge->add_field(array(
                'update_id' => array(
                    'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'auto_increment' => TRUE,
                ),
                'version' => array('type' => 'VARCHAR', 'constraint' => 32),
                'previous_version' => array('type' => 'VARCHAR', 'constraint' => 32),
                'status' => array('type' => 'VARCHAR', 'constraint' => 24, 'default' => 'pending'),
                'package_url' => array('type' => 'VARCHAR', 'constraint' => 512),
                'checksum' => array('type' => 'VARCHAR', 'constraint' => 64),
                'changelog' => array('type' => 'TEXT', 'null' => TRUE),
                'log' => array('type' => 'LONGTEXT', 'null' => TRUE),
                'started_on' => array('type' => 'DATETIME', 'null' => TRUE),
                'finished_on' => array('type' => 'DATETIME', 'null' => TRUE),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 0),
            ));
            $this->dbforge->add_key('update_id', TRUE);
            $this->dbforge->add_key('status');
            $this->dbforge->add_key('version');
            $this->dbforge->create_table('sys_update', TRUE);
        }

        if (!$this->db->table_exists('sys_update_migration')) {
            $this->dbforge->add_field(array(
                'update_migration_id' => array(
                    'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'auto_increment' => TRUE,
                ),
                'update_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE),
                'migration_name' => array('type' => 'VARCHAR', 'constraint' => 191),
                'checksum' => array('type' => 'VARCHAR', 'constraint' => 64),
                'applied_on' => array('type' => 'DATETIME'),
            ));
            $this->dbforge->add_key('update_migration_id', TRUE);
            $this->dbforge->add_key(array('update_id', 'migration_name'), FALSE, TRUE);
            $this->dbforge->create_table('sys_update_migration', TRUE);
        }

        if ($this->db->table_exists('sys_update') && $this->db->table_exists('sys_update_migration')) {
            $exists = $this->db->query(
                'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
                   AND CONSTRAINT_TYPE = \'FOREIGN KEY\' LIMIT 1',
                array($this->db->database, 'sys_update_migration', 'fk_sys_update_migration_update_id')
            );
            if ($exists !== FALSE && $exists->num_rows() === 0) {
                $this->db->query(
                    'ALTER TABLE `sys_update_migration` ADD CONSTRAINT `fk_sys_update_migration_update_id`'
                    . ' FOREIGN KEY (`update_id`) REFERENCES `sys_update` (`update_id`)'
                    . ' ON UPDATE CASCADE ON DELETE CASCADE'
                );
            }
        }
    }

    public function down()
    {
        // Update history is retained so administrators can audit applied releases.
    }
}
