<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** One-time, expiring approval links; retain rows for audit/replay prevention. */
class Migration_Create_approval_link_tokens extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('app_approval_link_token')) { return; }
        $this->dbforge->add_field(array(
            'token_id' => array('type'=>'BIGINT','constraint'=>20,'unsigned'=>TRUE,'auto_increment'=>TRUE),
            'tenant_id' => array('type'=>'INT','constraint'=>11,'unsigned'=>TRUE),
            'request_id' => array('type'=>'BIGINT','constraint'=>20,'unsigned'=>TRUE),
            'approver_user_id' => array('type'=>'INT','constraint'=>11,'unsigned'=>TRUE),
            'decision' => array('type'=>'VARCHAR','constraint'=>8),
            'token_hash' => array('type'=>'CHAR','constraint'=>64),
            'expires_at' => array('type'=>'DATETIME'),
            'used_at' => array('type'=>'DATETIME','null'=>TRUE),
            'create_on' => array('type'=>'DATETIME'),
        ));
        $this->dbforge->add_key('token_id', TRUE);
        $this->dbforge->add_key('token_hash', FALSE, TRUE);
        $this->dbforge->add_key(array('tenant_id','request_id'));
        $this->dbforge->create_table('app_approval_link_token', TRUE);
    }

    public function down()
    {
        // Do not drop one-time token audit/replay history during rollback.
    }
}
