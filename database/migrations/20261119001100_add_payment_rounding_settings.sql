-- Allow small final-payment rounding differences to be written off.
ALTER TABLE `fin_settings`
    ADD COLUMN `rounding_tolerance` decimal(20,6) NOT NULL DEFAULT '1.000000' AFTER `fx_loss_account_id`,
    ADD COLUMN `rounding_gain_account_id` bigint unsigned DEFAULT NULL AFTER `rounding_tolerance`,
    ADD COLUMN `rounding_loss_account_id` bigint unsigned DEFAULT NULL AFTER `rounding_gain_account_id`,
    ADD KEY `idx_fin_settings_rounding_gain_account_id` (`rounding_gain_account_id`),
    ADD KEY `idx_fin_settings_rounding_loss_account_id` (`rounding_loss_account_id`),
    ADD CONSTRAINT `fk_fin_settings_rounding_gain_account_id`
        FOREIGN KEY (`rounding_gain_account_id`) REFERENCES `fin_account` (`account_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_fin_settings_rounding_loss_account_id`
        FOREIGN KEY (`rounding_loss_account_id`) REFERENCES `fin_account` (`account_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE;
