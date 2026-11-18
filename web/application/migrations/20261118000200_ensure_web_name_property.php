<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ensure the editable application brand property exists for new installs. */
class Migration_Ensure_web_name_property extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('sys_property')) {
            return;
        }
        $this->db->query(
            "INSERT INTO sys_property (property_code, property_name, description, value)"
            . " SELECT 'web_name', 'Nama Web', '', 'CMSNivo' FROM DUAL"
            . " WHERE NOT EXISTS (SELECT 1 FROM sys_property WHERE property_code='web_name')"
        );
    }

    public function down()
    {
        // Branding configuration is intentionally retained.
    }
}
