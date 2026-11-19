-- Default tax codes used to prefill new income, expense, sales, and purchase forms.
ALTER TABLE `fin_settings`
    ADD COLUMN `default_output_tax_id` bigint unsigned DEFAULT NULL AFTER `rounding_loss_account_id`,
    ADD COLUMN `default_input_tax_id` bigint unsigned DEFAULT NULL AFTER `default_output_tax_id`,
    ADD KEY `idx_fin_settings_default_output_tax_id` (`default_output_tax_id`),
    ADD KEY `idx_fin_settings_default_input_tax_id` (`default_input_tax_id`),
    ADD CONSTRAINT `fk_fin_settings_default_output_tax_id`
        FOREIGN KEY (`default_output_tax_id`) REFERENCES `fin_tax` (`tax_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_fin_settings_default_input_tax_id`
        FOREIGN KEY (`default_input_tax_id`) REFERENCES `fin_tax` (`tax_id`)
        ON DELETE SET NULL ON UPDATE CASCADE;
