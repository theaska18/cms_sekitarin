-- Preserve the external document reference on drafts created from the inbox.
ALTER TABLE `fin_sales_invoice`
  ADD COLUMN `integration_number` VARCHAR(48) DEFAULT NULL AFTER `invoice_number`,
  ADD COLUMN `integration_party_name` VARCHAR(160) DEFAULT NULL AFTER `integration_number`,
  ADD COLUMN `integration_party_code` VARCHAR(32) DEFAULT NULL AFTER `integration_party_name`;

ALTER TABLE `fin_purchase_bill`
  ADD COLUMN `integration_number` VARCHAR(48) DEFAULT NULL AFTER `bill_number`,
  ADD COLUMN `integration_party_name` VARCHAR(160) DEFAULT NULL AFTER `integration_number`,
  ADD COLUMN `integration_party_code` VARCHAR(32) DEFAULT NULL AFTER `integration_party_name`;
