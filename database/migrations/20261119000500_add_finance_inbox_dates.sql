-- Invoice/bill dates submitted by external systems.
-- Existing rows fall back to the received timestamp date.
ALTER TABLE `fin_sales_invoice_inbox`
  ADD COLUMN `invoice_date` DATE DEFAULT NULL AFTER `invoice_number`,
  ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `invoice_date`;

UPDATE `fin_sales_invoice_inbox`
SET `invoice_date` = DATE(`received_at`)
WHERE `invoice_date` IS NULL;

ALTER TABLE `fin_purchase_bill_inbox`
  ADD COLUMN `bill_date` DATE DEFAULT NULL AFTER `bill_number`,
  ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `bill_date`;

UPDATE `fin_purchase_bill_inbox`
SET `bill_date` = DATE(`received_at`)
WHERE `bill_date` IS NULL;
