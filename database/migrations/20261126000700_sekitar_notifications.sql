-- Sekitar in-app notifications and push delivery queue.

CREATE TABLE `sek_notification` (
    `notification_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `type` varchar(40) NOT NULL,
    `title` varchar(150) NOT NULL,
    `body` varchar(500) NOT NULL,
    `data_json` json DEFAULT NULL,
    `read_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`notification_id`),
    KEY `idx_sek_notification_user` (`user_id`, `notification_id`),
    KEY `idx_sek_notification_unread` (`user_id`, `read_at`),
    CONSTRAINT `fk_sek_notification_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_push_queue` (
    `queue_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `notification_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `device_id` bigint unsigned DEFAULT NULL,
    `push_token` varchar(255) NOT NULL,
    `title` varchar(150) NOT NULL,
    `body` varchar(500) NOT NULL,
    `data_json` json DEFAULT NULL,
    `status` enum('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
    `attempts` tinyint unsigned NOT NULL DEFAULT 0,
    `last_error` varchar(255) DEFAULT NULL,
    `next_attempt_at` datetime NOT NULL,
    `created_at` datetime NOT NULL,
    `sent_at` datetime DEFAULT NULL,
    PRIMARY KEY (`queue_id`),
    KEY `idx_sek_push_due` (`status`, `next_attempt_at`),
    KEY `idx_sek_push_user` (`user_id`, `created_at`),
    CONSTRAINT `fk_sek_push_notification` FOREIGN KEY (`notification_id`)
        REFERENCES `sek_notification` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_push_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_push_device` FOREIGN KEY (`device_id`)
        REFERENCES `sek_device` (`device_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
