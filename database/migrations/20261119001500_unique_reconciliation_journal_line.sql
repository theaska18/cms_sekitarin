-- A posted journal line may belong to at most one bank reconciliation.
ALTER TABLE `fin_bank_reconciliation_line`
    ADD UNIQUE KEY `uk_bank_reconciliation_journal_line` (`tenant_id`, `journal_line_id`);
