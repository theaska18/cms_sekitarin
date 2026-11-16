<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Add typed approval steps and a recoverable queue for unresolved approvals. */
class Migration_Add_dynamic_approval_routing extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('app_approval_flow_step')) {
            $this->add_column_if_missing('app_approval_flow_step', 'approver_type', array(
                'type' => 'VARCHAR', 'constraint' => 16, 'default' => 'user',
            ));
            $this->add_column_if_missing('app_approval_flow_step', 'approver_employee_id', array(
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE,
            ));
            $this->add_column_if_missing('app_approval_flow_step', 'org_id', array(
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE,
            ));
        }

        if ($this->db->table_exists('app_approval_request')) {
            $this->add_column_if_missing('app_approval_request', 'request_type', array(
                'type' => 'VARCHAR', 'constraint' => 64, 'null' => TRUE,
            ));
            $this->add_column_if_missing('app_approval_request', 'requester_employee_id', array(
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE,
            ));
            $this->add_column_if_missing('app_approval_request', 'current_approver_user_id', array(
                'type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE,
            ));
            if ($this->db->field_exists('flow_id', 'app_approval_request')) {
                $this->dbforge->modify_column('app_approval_request', array(
                    'flow_id' => array('type'=>'INT','constraint'=>11,'unsigned'=>TRUE,'null'=>TRUE),
                ));
            }
        }

        if (!$this->db->table_exists('app_approval_error')) {
            $this->dbforge->add_field(array(
                'error_id' => $this->id_field(),
                'tenant_id' => $this->int_field(),
                'request_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE),
                'flow_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                'step_number' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 1),
                'org_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                'error_code' => array('type' => 'VARCHAR', 'constraint' => 64),
                'error_message' => array('type' => 'TEXT', 'null' => TRUE),
                'status' => array('type' => 'VARCHAR', 'constraint' => 16, 'default' => 'open'),
                'assigned_employee_id' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE),
                'assigned_approver_user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
                'handled_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
                'create_on' => array('type' => 'DATETIME'),
                'update_on' => array('type' => 'DATETIME', 'null' => TRUE),
                'resolved_on' => array('type' => 'DATETIME', 'null' => TRUE),
            ));
            $this->dbforge->add_key('error_id', TRUE);
            $this->dbforge->add_key(array('tenant_id', 'status', 'create_on'));
            $this->dbforge->add_key(array('tenant_id', 'request_id', 'status'));
            $this->dbforge->create_table('app_approval_error', TRUE);
        }
    }

    public function down()
    {
        // Keep approval recovery/audit data and additive columns on rollback.
    }

    private function add_column_if_missing($table, $column, $definition)
    {
        if (!$this->db->field_exists($column, $table)) {
            $this->dbforge->add_column($table, array($column => $definition));
        }
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
