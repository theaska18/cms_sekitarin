<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_202611110001_add_app_user_theme extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('theme', 'app_user')) {
            $this->dbforge->add_column('app_user', array(
                'theme' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 16,
                    'null' => TRUE,
                    'default' => 'light',
                ),
            ));
        }
        $this->db->where('(theme IS NULL OR theme = \'\')', NULL, FALSE)
            ->update('app_user', array('theme' => 'light'));
    }

    public function down()
    {
        // Keep user preferences when rolling back application code.
    }
}
