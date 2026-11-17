<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Keep the draft owner as requester while recording who actually submitted. */
class Migration_Add_approval_submitted_by extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('app_approval_request')) {
            return;
        }
        if (!$this->db->field_exists('submitted_by', 'app_approval_request')) {
            $this->dbforge->add_column('app_approval_request', array(
                'submitted_by' => array('type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE, 'null' => TRUE),
            ));
        }
    }

    public function down()
    {
        // Submission history is retained for auditability.
    }
}
