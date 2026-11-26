-- Approval-backed revisions for posted invoices and bills.
-- A revision reverses the original journal, marks the source as `revised`,
-- and creates a linked replacement draft with a new document number.
ALTER TABLE `fin_cancellation`
    ADD COLUMN `request_kind` varchar(16) NOT NULL DEFAULT 'cancel' AFTER `menu_code`,
    ADD COLUMN `replacement_id` bigint unsigned DEFAULT NULL AFTER `reversal_journal_id`,
    ADD KEY `idx_fin_cancellation_kind` (`tenant_id`, `request_kind`, `status`);

ALTER TABLE `fin_sales_invoice`
    ADD COLUMN `revision_of_id` bigint unsigned DEFAULT NULL AFTER `approval_request_id`,
    ADD COLUMN `revision_reason` varchar(1000) DEFAULT NULL AFTER `revision_of_id`,
    ADD COLUMN `revised_on` datetime DEFAULT NULL AFTER `revision_reason`,
    ADD KEY `idx_fin_sales_invoice_revision_of` (`tenant_id`, `revision_of_id`);

ALTER TABLE `fin_purchase_bill`
    ADD COLUMN `revision_of_id` bigint unsigned DEFAULT NULL AFTER `approval_request_id`,
    ADD COLUMN `revision_reason` varchar(1000) DEFAULT NULL AFTER `revision_of_id`,
    ADD COLUMN `revised_on` datetime DEFAULT NULL AFTER `revision_reason`,
    ADD KEY `idx_fin_purchase_bill_revision_of` (`tenant_id`, `revision_of_id`);
