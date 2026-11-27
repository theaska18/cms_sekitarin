-- Username uniqueness per account scope (personal/business) and comment account.
ALTER TABLE `sek_account`
    ADD COLUMN `handle_scope` varchar(8) NOT NULL DEFAULT 'personal' AFTER `handle`,
    DROP INDEX `uk_sek_account_handle`,
    ADD UNIQUE KEY `uk_sek_account_handle_scope` (`handle_scope`, `handle`);

UPDATE `sek_account`
SET `handle_scope` = IF(`type_code` = 'personal', 'personal', 'business');

ALTER TABLE `sek_post_comment`
    ADD COLUMN `account_id` bigint unsigned DEFAULT NULL AFTER `user_id`,
    ADD KEY `idx_sek_post_comment_account` (`account_id`),
    ADD CONSTRAINT `fk_sek_post_comment_account` FOREIGN KEY (`account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;
