-- Grant the Finance integration inbox menus to tenants/roles that can already
-- manage sales invoices or purchase bills.
INSERT IGNORE INTO `sys_tenant_menu` (`tenant_id`, `menu_code`)
SELECT `tenant_id`, 'fin_sales_invoice_inbox' FROM `sys_tenant_menu` WHERE `menu_code`='fin_sales_invoices';
INSERT IGNORE INTO `sys_tenant_menu` (`tenant_id`, `menu_code`)
SELECT `tenant_id`, 'fin_purchase_bill_inbox' FROM `sys_tenant_menu` WHERE `menu_code`='fin_purchase_bills';

INSERT IGNORE INTO `sys_tenant_menu_access` (`tenant_menu_id`, `access_code`)
SELECT `tenant_menu_id`, 'fin_sales_invoice_inbox_add' FROM `sys_tenant_menu` WHERE `menu_code`='fin_sales_invoice_inbox';
INSERT IGNORE INTO `sys_tenant_menu_access` (`tenant_menu_id`, `access_code`)
SELECT `tenant_menu_id`, 'fin_sales_invoice_inbox_update' FROM `sys_tenant_menu` WHERE `menu_code`='fin_sales_invoice_inbox';
INSERT IGNORE INTO `sys_tenant_menu_access` (`tenant_menu_id`, `access_code`)
SELECT `tenant_menu_id`, 'fin_purchase_bill_inbox_add' FROM `sys_tenant_menu` WHERE `menu_code`='fin_purchase_bill_inbox';
INSERT IGNORE INTO `sys_tenant_menu_access` (`tenant_menu_id`, `access_code`)
SELECT `tenant_menu_id`, 'fin_purchase_bill_inbox_update' FROM `sys_tenant_menu` WHERE `menu_code`='fin_purchase_bill_inbox';

INSERT IGNORE INTO `app_role_menu` (`role_id`, `menu_code`)
SELECT `role_id`, 'fin_sales_invoice_inbox' FROM `app_role_menu` WHERE `menu_code`='fin_sales_invoices';
INSERT IGNORE INTO `app_role_menu` (`role_id`, `menu_code`)
SELECT `role_id`, 'fin_purchase_bill_inbox' FROM `app_role_menu` WHERE `menu_code`='fin_purchase_bills';

INSERT IGNORE INTO `app_role_menu_access` (`role_menu_id`, `access_code`)
SELECT `role_menu_id`, 'fin_sales_invoice_inbox_add' FROM `app_role_menu` WHERE `menu_code`='fin_sales_invoice_inbox';
INSERT IGNORE INTO `app_role_menu_access` (`role_menu_id`, `access_code`)
SELECT `role_menu_id`, 'fin_sales_invoice_inbox_update' FROM `app_role_menu` WHERE `menu_code`='fin_sales_invoice_inbox';
INSERT IGNORE INTO `app_role_menu_access` (`role_menu_id`, `access_code`)
SELECT `role_menu_id`, 'fin_purchase_bill_inbox_add' FROM `app_role_menu` WHERE `menu_code`='fin_purchase_bill_inbox';
INSERT IGNORE INTO `app_role_menu_access` (`role_menu_id`, `access_code`)
SELECT `role_menu_id`, 'fin_purchase_bill_inbox_update' FROM `app_role_menu` WHERE `menu_code`='fin_purchase_bill_inbox';
