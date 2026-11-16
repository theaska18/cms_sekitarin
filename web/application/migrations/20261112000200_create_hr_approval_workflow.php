<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped org hierarchy support and configurable approval requests. */
class Migration_Create_hr_approval_workflow extends CI_Migration
{
    public function up()
    {

        $this->create_if_missing('app_approval_flow', array(
            'flow_id' => $this->id_field(),
            'tenant_id' => $this->int_field(),
            'flow_name' => array('type' => 'VARCHAR', 'constraint' => 120),
            'request_type' => array('type' => 'VARCHAR', 'constraint' => 64),
            'active_flag' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => $this->int_field(),
            'create_on' => array('type' => 'DATETIME'),
            'update_on' => array('type' => 'DATETIME', 'null' => TRUE),
        ), array(array('tenant_id', 'request_type', 'unique')));

        $this->create_if_missing('app_approval_flow_step', array(
            'flow_step_id' => $this->id_field(),
            'tenant_id' => $this->int_field(),
            'flow_id' => $this->int_field(),
            'step_number' => $this->int_field(),
            'approver_user_id' => $this->int_field(),
            'create_on' => array('type' => 'DATETIME'),
        ), array(array('tenant_id', 'flow_id', 'step_number', 'unique'), array('tenant_id', 'approver_user_id')));

        $this->create_if_missing('app_approval_request', array(
            'request_id' => $this->id_field(),
            'tenant_id' => $this->int_field(),
            'flow_id' => $this->int_field(),
            'requester_user_id' => $this->int_field(),
            'subject' => array('type' => 'VARCHAR', 'constraint' => 180),
            'details' => array('type' => 'TEXT', 'null' => TRUE),
            'status' => array('type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'),
            'current_step' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 1),
            'create_on' => array('type' => 'DATETIME'),
            'update_on' => array('type' => 'DATETIME', 'null' => TRUE),
        ), array(array('tenant_id', 'status', 'current_step'), array('tenant_id', 'flow_id')));

        $this->create_if_missing('app_approval_action', array(
            'action_id' => $this->id_field(),
            'tenant_id' => $this->int_field(),
            'request_id' => $this->int_field(),
            'step_number' => $this->int_field(),
            'approver_user_id' => $this->int_field(),
            'decision' => array('type' => 'VARCHAR', 'constraint' => 16),
            'note' => array('type' => 'TEXT', 'null' => TRUE),
            'create_on' => array('type' => 'DATETIME'),
        ), array(array('tenant_id', 'request_id'), array('tenant_id', 'approver_user_id')));
    }

    public function down()
    {
        // Retain approval history on application rollback; destructive drops require an explicit data migration.
    }

    private function create_if_missing($table, $fields, $indexes)
    {
        if ($this->db->table_exists($table)) { return; }
        $this->dbforge->add_field($fields);
        foreach ($indexes as $index) {
            $unique = end($index) === 'unique';
            if ($unique) { array_pop($index); }
            $this->dbforge->add_key($index, FALSE, $unique);
        }
        $primary = array_keys($fields)[0];
        $this->dbforge->add_key($primary, TRUE);
        $this->dbforge->create_table($table, TRUE);
    }

    private function id_field()
    {
        return array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'auto_increment' => TRUE);
    }

    private function int_field()
    {
        return array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE);
    }
}
