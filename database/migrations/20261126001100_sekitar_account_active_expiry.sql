-- Business availability expires automatically; re-enable from settings.
ALTER TABLE `sek_account`
    ADD COLUMN `active_until` datetime DEFAULT NULL AFTER `is_active`;

-- Existing active business accounts expire 24 hours after this migration.
UPDATE `sek_account`
SET `active_until` = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR)
WHERE `type_code` <> 'personal' AND `is_active` = 1 AND `active_until` IS NULL;
