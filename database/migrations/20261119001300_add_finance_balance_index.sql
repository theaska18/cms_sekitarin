-- Speed up cash/bank balance checks that filter by account and currency.
ALTER TABLE `fin_journal_line`
    ADD KEY `idx_fin_journal_line_account_currency` (`tenant_id`, `account_id`, `currency_code`);
