-- Business availability flag, independent from realtime presence.
ALTER TABLE `sek_account`
    ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `is_online`;
