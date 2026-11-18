<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Replace read-only Finance access codes with export/import permissions. */
class Migration_Refine_finance_access_codes extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('sys_tenant_menu') || !$this->db->table_exists('sys_tenant_menu_access')
            || !$this->db->table_exists('app_role_menu') || !$this->db->table_exists('app_role_menu_access')) {
            return;
        }

        $report_codes = array(
            'fin_profit_loss', 'fin_balance_sheet', 'fin_cash_flow', 'fin_trial_balance',
            'fin_ar_report', 'fin_ap_report', 'fin_general_ledger',
        );
        $master_codes = array('fin_chart_of_accounts', 'fin_categories', 'fin_tax_configuration');
        $placeholder_codes = array('fin_accounts_receivable', 'fin_accounts_payable');
        $orphan_codes = array('fin_dashboard', 'fin_all_transactions');

        // Remove placeholder menus; access rows cascade with their menu row.
        $this->db->where_in('menu_code', $placeholder_codes)->delete('sys_tenant_menu');
        $this->db->where_in('menu_code', $placeholder_codes)->delete('app_role_menu');

        // Remove access codes that no longer exist in the Finance manifest.
        $unused_app = array();
        foreach (array_merge($report_codes, $orphan_codes) as $menu_code) {
            $unused_app[] = $menu_code . '_add';
            $unused_app[] = $menu_code . '_update';
        }
        $this->delete_access('sys_tenant_menu_access', 'sys_tenant_menu', 'tenant_menu_id', array_merge($report_codes, $orphan_codes), $unused_app);
        $this->delete_access('app_role_menu_access', 'app_role_menu', 'role_menu_id', array_merge($report_codes, $orphan_codes), $unused_app);

        // Every existing report viewer keeps the ability to export the report.
        $this->add_access('sys_tenant_menu_access', 'sys_tenant_menu', 'tenant_menu_id', $report_codes, '_export');
        $this->add_access('app_role_menu_access', 'app_role_menu', 'role_menu_id', $report_codes, '_export');

        // Master data export keeps the previous menu-level visibility; import
        // is granted only where update access already existed.
        $this->add_access('sys_tenant_menu_access', 'sys_tenant_menu', 'tenant_menu_id', $master_codes, '_export');
        $this->add_access('app_role_menu_access', 'app_role_menu', 'role_menu_id', $master_codes, '_export');
        $this->add_import_access('sys_tenant_menu_access', 'sys_tenant_menu', 'tenant_menu_id', $master_codes);
        $this->add_import_access('app_role_menu_access', 'app_role_menu', 'role_menu_id', $master_codes);
    }

    public function down()
    {
        // Permission cleanup is intentionally retained.
    }

    private function delete_access($access_table, $menu_table, $menu_key, $menu_codes, $access_codes)
    {
        if (!$menu_codes || !$access_codes) {
            return;
        }
        $menus = implode(',', array_map(array($this->db, 'escape'), $menu_codes));
        $accesses = implode(',', array_map(array($this->db, 'escape'), $access_codes));
        $this->db->query('DELETE a FROM ' . $access_table . ' a INNER JOIN ' . $menu_table . ' m ON m.' . $menu_key . '=a.' . $menu_key
            . ' WHERE m.menu_code IN (' . $menus . ') AND a.access_code IN (' . $accesses . ')');
    }

    private function add_access($access_table, $menu_table, $menu_key, $menu_codes, $suffix)
    {
        foreach ($menu_codes as $menu_code) {
            $this->db->query('INSERT IGNORE INTO ' . $access_table . ' (' . $menu_key . ', access_code)'
                . ' SELECT m.' . $menu_key . ', ' . $this->db->escape($menu_code . $suffix)
                . ' FROM ' . $menu_table . ' m WHERE m.menu_code=' . $this->db->escape($menu_code));
        }
    }

    private function add_import_access($access_table, $menu_table, $menu_key, $menu_codes)
    {
        foreach ($menu_codes as $menu_code) {
            $this->db->query('INSERT IGNORE INTO ' . $access_table . ' (' . $menu_key . ', access_code)'
                . ' SELECT m.' . $menu_key . ', ' . $this->db->escape($menu_code . '_import')
                . ' FROM ' . $menu_table . ' m INNER JOIN ' . $access_table . ' a ON a.' . $menu_key . '=m.' . $menu_key
                . ' AND a.access_code=' . $this->db->escape($menu_code . '_update')
                . ' WHERE m.menu_code=' . $this->db->escape($menu_code));
        }
    }
}
