ALTER TABLE `app_approval_request`
    ADD COLUMN `request_action` varchar(16) NOT NULL DEFAULT 'submit' AFTER `source_id`;

CREATE TABLE IF NOT EXISTS `fin_cancellation` (
    `cancellation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint NOT NULL,
    `source_type` varchar(32) NOT NULL,
    `source_id` bigint unsigned NOT NULL,
    `menu_code` varchar(32) NOT NULL,
    `approval_request_id` bigint unsigned DEFAULT NULL,
    `previous_status` varchar(16) NOT NULL,
    `status` varchar(16) NOT NULL DEFAULT 'pending',
    `reason` varchar(1000) NOT NULL,
    `requested_by` bigint NOT NULL,
    `requested_on` datetime NOT NULL,
    `decided_on` datetime DEFAULT NULL,
    `reversal_journal_id` bigint unsigned DEFAULT NULL,
    PRIMARY KEY (`cancellation_id`),
    UNIQUE KEY `uk_fin_cancellation_source` (`tenant_id`, `source_type`, `source_id`),
    KEY `idx_fin_cancellation_approval` (`approval_request_id`),
    KEY `idx_fin_cancellation_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_fin_cancellation_approval` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_fin_cancellation_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
