-- Soft-cancel support for external finance inbox submissions.
-- Cancelled rows stay for audit and keep their document number reserved.
ALTER TABLE `fin_sales_invoice_inbox`
  ADD COLUMN `cancelled_at` DATETIME DEFAULT NULL AFTER `processed_at`;

ALTER TABLE `fin_purchase_bill_inbox`
  ADD COLUMN `cancelled_at` DATETIME DEFAULT NULL AFTER `processed_at`;
