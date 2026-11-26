-- Sekitar chat: conversations, members, messages, receipts, and live location.

CREATE TABLE `sek_conversation` (
    `conversation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `type` enum('direct','group') NOT NULL DEFAULT 'direct',
    `direct_key` varchar(64) DEFAULT NULL,
    `title` varchar(120) DEFAULT NULL,
    `avatar_media_id` bigint unsigned DEFAULT NULL,
    `created_by_user_id` bigint unsigned NOT NULL,
    `last_message_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`conversation_id`),
    UNIQUE KEY `uk_sek_conversation_direct` (`direct_key`),
    KEY `idx_sek_conversation_last` (`last_message_at`),
    KEY `idx_sek_conversation_avatar` (`avatar_media_id`),
    CONSTRAINT `fk_sek_conversation_creator` FOREIGN KEY (`created_by_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_conversation_avatar` FOREIGN KEY (`avatar_media_id`)
        REFERENCES `sek_media` (`media_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_conversation_member` (
    `conversation_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `role` enum('owner','member') NOT NULL DEFAULT 'member',
    `last_read_message_id` bigint unsigned DEFAULT NULL,
    `joined_at` datetime NOT NULL,
    `left_at` datetime DEFAULT NULL,
    `muted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`conversation_id`, `user_id`),
    KEY `idx_sek_conversation_member_user` (`user_id`, `left_at`),
    CONSTRAINT `fk_sek_conversation_member_conversation` FOREIGN KEY (`conversation_id`)
        REFERENCES `sek_conversation` (`conversation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_conversation_member_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_message` (
    `message_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `conversation_id` bigint unsigned NOT NULL,
    `sender_user_id` bigint unsigned NOT NULL,
    `sender_account_id` bigint unsigned DEFAULT NULL,
    `type` enum('text','image','file','audio','location','system') NOT NULL DEFAULT 'text',
    `body` text DEFAULT NULL,
    `media_id` bigint unsigned DEFAULT NULL,
    `latitude` decimal(10,7) DEFAULT NULL,
    `longitude` decimal(10,7) DEFAULT NULL,
    `location_label` varchar(255) DEFAULT NULL,
    `reply_to_message_id` bigint unsigned DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `edited_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`message_id`),
    KEY `idx_sek_message_conversation` (`conversation_id`, `message_id`),
    KEY `idx_sek_message_sender` (`sender_user_id`, `created_at`),
    KEY `idx_sek_message_media` (`media_id`),
    KEY `idx_sek_message_reply` (`reply_to_message_id`),
    CONSTRAINT `fk_sek_message_conversation` FOREIGN KEY (`conversation_id`)
        REFERENCES `sek_conversation` (`conversation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_message_sender` FOREIGN KEY (`sender_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_message_sender_account` FOREIGN KEY (`sender_account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_message_media` FOREIGN KEY (`media_id`)
        REFERENCES `sek_media` (`media_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_message_reply` FOREIGN KEY (`reply_to_message_id`)
        REFERENCES `sek_message` (`message_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_message_receipt` (
    `message_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `delivered_at` datetime DEFAULT NULL,
    `read_at` datetime DEFAULT NULL,
    PRIMARY KEY (`message_id`, `user_id`),
    KEY `idx_sek_message_receipt_user` (`user_id`, `read_at`),
    CONSTRAINT `fk_sek_message_receipt_message` FOREIGN KEY (`message_id`)
        REFERENCES `sek_message` (`message_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_message_receipt_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_live_location` (
    `conversation_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `location` point NOT NULL SRID 4326,
    `accuracy_m` smallint unsigned DEFAULT NULL,
    `expires_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`conversation_id`, `user_id`),
    SPATIAL KEY `sp_sek_live_location` (`location`),
    CONSTRAINT `fk_sek_live_location_conversation` FOREIGN KEY (`conversation_id`)
        REFERENCES `sek_conversation` (`conversation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_live_location_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
