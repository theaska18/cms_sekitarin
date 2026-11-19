-- Keep integration inbox rows in sync with cancelled Finance draft documents.
UPDATE `fin_sales_invoice_inbox` i
INNER JOIN `fin_sales_invoice` d
    ON d.`sales_invoice_id` = i.`sales_invoice_id`
    AND d.`tenant_id` = i.`tenant_id`
SET i.`status` = 'cancelled',
    i.`cancelled_at` = COALESCE(i.`cancelled_at`, NOW())
WHERE d.`status` = 'cancelled'
  AND i.`status` <> 'cancelled';

UPDATE `fin_purchase_bill_inbox` i
INNER JOIN `fin_purchase_bill` d
    ON d.`purchase_bill_id` = i.`purchase_bill_id`
    AND d.`tenant_id` = i.`tenant_id`
SET i.`status` = 'cancelled',
    i.`cancelled_at` = COALESCE(i.`cancelled_at`, NOW())
WHERE d.`status` = 'cancelled'
  AND i.`status` <> 'cancelled';
