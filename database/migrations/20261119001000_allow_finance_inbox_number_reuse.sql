-- Allow a cancelled integration number to be submitted again.
-- Only one active row may use a document number per tenant; cancelled rows stay for audit.
ALTER TABLE `fin_sales_invoice_inbox`
    DROP INDEX `uk_sales_invoice_inbox_number`,
    ADD COLUMN `active_invoice_number` varchar(48)
        GENERATED ALWAYS AS (IF(`status` <> 'cancelled', `invoice_number`, NULL)) STORED
        AFTER `invoice_number`,
    ADD UNIQUE KEY `uk_sales_invoice_inbox_active_number` (`tenant_id`, `active_invoice_number`),
    ADD KEY `idx_sales_invoice_inbox_number` (`tenant_id`, `invoice_number`, `received_at`);

ALTER TABLE `fin_purchase_bill_inbox`
    DROP INDEX `uk_purchase_bill_inbox_number`,
    ADD COLUMN `active_bill_number` varchar(48)
        GENERATED ALWAYS AS (IF(`status` <> 'cancelled', `bill_number`, NULL)) STORED
        AFTER `bill_number`,
    ADD UNIQUE KEY `uk_purchase_bill_inbox_active_number` (`tenant_id`, `active_bill_number`),
    ADD KEY `idx_purchase_bill_inbox_number` (`tenant_id`, `bill_number`, `received_at`);
