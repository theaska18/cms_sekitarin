<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Turn app_approval_flow into a tenant override of sys_approval_flow.
 *
 * Legacy tenant flow definitions are intentionally discarded: the system
 * template list is now the single source of flow identity, and tenants only
 * store an override when they change the approval steps.
 */
class Migration_Refactor_app_approval_flow_to_template_overrides extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('app_approval_flow_step')) {
            $this->db->truncate('app_approval_flow_step');
        }
        if ($this->db->table_exists('app_approval_flow')) {
            $this->db->truncate('app_approval_flow');
            if (!$this->db->field_exists('flow_code', 'app_approval_flow')) {
                $this->dbforge->add_column('app_approval_flow', array(
                    'flow_code' => array('type' => 'VARCHAR', 'constraint' => 32, 'null' => FALSE),
                ));
            }
            foreach (array('flow_name', 'request_type', 'active_flag', 'created_by') as $column) {
                if ($this->db->field_exists($column, 'app_approval_flow')) {
                    $this->dbforge->drop_column('app_approval_flow', $column);
                }
            }
            $this->add_unique_key('app_approval_flow', 'uk_app_approval_flow_tenant_code', array('tenant_id', 'flow_code'));
        }

        if ($this->db->table_exists('app_approval_request')) {
            if (!$this->db->field_exists('flow_code', 'app_approval_request')) {
                $this->dbforge->add_column('app_approval_request', array(
                    'flow_code' => array('type' => 'VARCHAR', 'constraint' => 32, 'null' => TRUE),
                ));
            }
            $this->db->where('flow_code IS NULL', NULL, FALSE)->update('app_approval_request', array('flow_id' => NULL));
            if ($this->db->field_exists('request_type', 'app_approval_request')) {
                $this->dbforge->drop_column('app_approval_request', 'request_type');
            }
        }
    }

    public function down()
    {
        // Legacy tenant flow definitions were discarded by design.
    }

    private function add_unique_key($table, $name, $columns)
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
            'ALTER TABLE `' . $table . '` ADD UNIQUE KEY `' . $name . '` (`' . implode('`, `', $columns) . '`)'
        );
    }
}
