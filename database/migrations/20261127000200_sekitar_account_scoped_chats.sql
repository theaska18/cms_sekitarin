-- Direct conversations are scoped to the account pair, so a driver account and
-- a personal account no longer share the same chat thread.
ALTER TABLE `sek_conversation`
    ADD COLUMN `direct_account_a_id` bigint unsigned DEFAULT NULL AFTER `direct_key`,
    ADD COLUMN `direct_account_b_id` bigint unsigned DEFAULT NULL AFTER `direct_account_a_id`,
    ADD KEY `idx_sek_conversation_direct_a` (`direct_account_a_id`),
    ADD KEY `idx_sek_conversation_direct_b` (`direct_account_b_id`),
    ADD CONSTRAINT `fk_sek_conversation_account_a` FOREIGN KEY (`direct_account_a_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_sek_conversation_account_b` FOREIGN KEY (`direct_account_b_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Backfill existing direct chats with each side's personal account.
UPDATE `sek_conversation` c
JOIN `sek_conversation_member` m1
    ON m1.conversation_id = c.conversation_id AND m1.left_at IS NULL
JOIN `sek_conversation_member` m2
    ON m2.conversation_id = c.conversation_id AND m2.left_at IS NULL
    AND m2.user_id > m1.user_id
JOIN `sek_account` a1
    ON a1.user_id = m1.user_id AND a1.type_code = 'personal'
JOIN `sek_account` a2
    ON a2.user_id = m2.user_id AND a2.type_code = 'personal'
SET c.direct_account_a_id = LEAST(a1.account_id, a2.account_id),
    c.direct_account_b_id = GREATEST(a1.account_id, a2.account_id)
WHERE c.type = 'direct'
  AND c.direct_account_a_id IS NULL;

ALTER TABLE `sek_conversation`
    ADD UNIQUE KEY `uk_sek_conversation_direct_accounts`
        (`direct_account_a_id`, `direct_account_b_id`);
