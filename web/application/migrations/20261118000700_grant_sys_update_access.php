<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Grant the new update menu to existing administrators without widening unrelated roles. */
class Migration_Grant_sys_update_access extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('sys_tenant_menu') || !$this->db->table_exists('sys_tenant_menu_access')) {
            return;
        }

        // Tenants that can administer Properties also see System -> Update.
        $this->db->query(
            "INSERT IGNORE INTO sys_tenant_menu (tenant_id, menu_code)"
            . " SELECT tenant_id, 'sys_update' FROM sys_tenant_menu WHERE menu_code='sys_property'"
        );
        $this->db->query(
            "INSERT IGNORE INTO sys_tenant_menu_access (tenant_menu_id, access_code)"
            . " SELECT m.tenant_menu_id, 'sys_update' FROM sys_tenant_menu m"
            . " WHERE m.menu_code='sys_update'"
        );
        $this->db->query(
            "INSERT IGNORE INTO sys_tenant_menu_access (tenant_menu_id, access_code)"
            . " SELECT m.tenant_menu_id, 'sys_update_run' FROM sys_tenant_menu m"
            . " INNER JOIN sys_tenant_menu p ON p.tenant_id=m.tenant_id AND p.menu_code='sys_property'"
            . " INNER JOIN sys_tenant_menu_access pa ON pa.tenant_menu_id=p.tenant_menu_id"
            . "   AND pa.access_code='sys_property_update'"
            . " WHERE m.menu_code='sys_update'"
        );

        if (!$this->db->table_exists('app_role_menu') || !$this->db->table_exists('app_role_menu_access')) {
            return;
        }

        // Roles that can administer Properties inherit view access; only roles
        // that can already change Properties may run updates.
        $this->db->query(
            "INSERT IGNORE INTO app_role_menu (role_id, menu_code)"
            . " SELECT role_id, 'sys_update' FROM app_role_menu WHERE menu_code='sys_property'"
        );
        $this->db->query(
            "INSERT IGNORE INTO app_role_menu_access (role_menu_id, access_code)"
            . " SELECT m.role_menu_id, 'sys_update' FROM app_role_menu m"
            . " WHERE m.menu_code='sys_update'"
        );
        $this->db->query(
            "INSERT IGNORE INTO app_role_menu_access (role_menu_id, access_code)"
            . " SELECT m.role_menu_id, 'sys_update_run' FROM app_role_menu m"
            . " INNER JOIN app_role_menu p ON p.role_id=m.role_id AND p.menu_code='sys_property'"
            . " INNER JOIN app_role_menu_access pa ON pa.role_menu_id=p.role_menu_id"
            . "   AND pa.access_code='sys_property_update'"
            . " WHERE m.menu_code='sys_update'"
        );
    }

    public function down()
    {
        // Granted menu access is intentionally retained.
    }
}
