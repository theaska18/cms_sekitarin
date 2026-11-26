-- Sekitar social basics: saved contacts, account blocking, and reports.

CREATE TABLE `sek_contact` (
    `contact_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `owner_user_id` bigint unsigned NOT NULL,
    `account_id` bigint unsigned NOT NULL,
    `alias` varchar(80) DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`contact_id`),
    UNIQUE KEY `uk_sek_contact_owner_account` (`owner_user_id`, `account_id`),
    KEY `idx_sek_contact_account` (`account_id`),
    CONSTRAINT `fk_sek_contact_owner` FOREIGN KEY (`owner_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_contact_account` FOREIGN KEY (`account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_block` (
    `blocker_account_id` bigint unsigned NOT NULL,
    `blocked_account_id` bigint unsigned NOT NULL,
    `reason` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`blocker_account_id`, `blocked_account_id`),
    KEY `idx_sek_block_blocked` (`blocked_account_id`),
    CONSTRAINT `fk_sek_block_blocker` FOREIGN KEY (`blocker_account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_block_blocked` FOREIGN KEY (`blocked_account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_report` (
    `report_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `reporter_user_id` bigint unsigned NOT NULL,
    `target_account_id` bigint unsigned NOT NULL,
    `category` enum('spam','abuse','fraud','inappropriate','other') NOT NULL,
    `description` varchar(1000) DEFAULT NULL,
    `status` enum('open','reviewing','resolved','rejected') NOT NULL DEFAULT 'open',
    `created_at` datetime NOT NULL,
    `reviewed_at` datetime DEFAULT NULL,
    `reviewed_by` bigint unsigned DEFAULT NULL,
    PRIMARY KEY (`report_id`),
    KEY `idx_sek_report_reporter` (`reporter_user_id`, `created_at`),
    KEY `idx_sek_report_status` (`status`, `created_at`),
    KEY `idx_sek_report_target` (`target_account_id`),
    CONSTRAINT `fk_sek_report_reporter` FOREIGN KEY (`reporter_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_report_target` FOREIGN KEY (`target_account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
