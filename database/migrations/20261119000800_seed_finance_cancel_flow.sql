INSERT INTO `sys_approval_flow` (`flow_code`, `flow_name`, `description`, `active_flag`, `created_by`, `create_on`)
SELECT 'finance_cancel', 'Finance - Cancellation', 'Approval for cancelling approved or posted Finance records.', 1, MIN(`user_id`), NOW()
FROM `app_user`
ON DUPLICATE KEY UPDATE `flow_name` = VALUES(`flow_name`), `description` = VALUES(`description`), `active_flag` = 1;

INSERT IGNORE INTO `sys_approval_flow_menu` (`flow_code`, `menu_code`)
SELECT 'finance_cancel', `menu_code` FROM (
    SELECT 'fin_income' AS `menu_code`
    UNION ALL SELECT 'fin_expenses'
    UNION ALL SELECT 'fin_sales_invoices'
    UNION ALL SELECT 'fin_purchase_bills'
    UNION ALL SELECT 'fin_customer_payments'
    UNION ALL SELECT 'fin_supplier_payments'
    UNION ALL SELECT 'fin_cash_bank_transfer'
    UNION ALL SELECT 'fin_bank_reconciliation'
    UNION ALL SELECT 'fin_journal_entries'
    UNION ALL SELECT 'fin_opening_balances'
) AS `cancel_menus`
WHERE EXISTS (SELECT 1 FROM `sys_approval_flow` WHERE `flow_code` = 'finance_cancel');

INSERT INTO `sys_approval_flow_step` (`flow_code`, `step_number`, `approver_type`, `create_on`)
SELECT 'finance_cancel', 1, 'superior', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `sys_approval_flow_step` WHERE `flow_code` = 'finance_cancel'
);
