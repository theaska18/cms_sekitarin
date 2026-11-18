<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Configure the update endpoint used by System -> Update. */
class Migration_Add_link_update_property extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('sys_property')) {
            return;
        }
        $this->db->query(
            "INSERT INTO sys_property (property_code, property_name, description, value)"
            . " SELECT 'link_update', 'Link Update', 'Alamat API pemeriksaan update aplikasi.',"
            . " 'https://cms.ckamal.com/api/update/check' FROM DUAL"
            . " WHERE NOT EXISTS (SELECT 1 FROM sys_property WHERE property_code='link_update')"
        );
    }

    public function down()
    {
        // Update endpoint configuration is intentionally retained.
    }
}
