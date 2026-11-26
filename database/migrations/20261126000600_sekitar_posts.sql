-- Sekitar posts, media carousel, likes, comments, and post reports.

CREATE TABLE `sek_post` (
    `post_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `account_id` bigint unsigned NOT NULL,
    `caption` varchar(2200) DEFAULT NULL,
    `status` enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`post_id`),
    KEY `idx_sek_post_created` (`created_at`),
    KEY `idx_sek_post_account` (`account_id`, `status`, `post_id`),
    KEY `idx_sek_post_user` (`user_id`, `created_at`),
    CONSTRAINT `fk_sek_post_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_post_account` FOREIGN KEY (`account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_post_media` (
    `post_id` bigint unsigned NOT NULL,
    `media_id` bigint unsigned NOT NULL,
    `sort_order` tinyint unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`post_id`, `media_id`),
    KEY `idx_sek_post_media_media` (`media_id`),
    CONSTRAINT `fk_sek_post_media_post` FOREIGN KEY (`post_id`)
        REFERENCES `sek_post` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_post_media_media` FOREIGN KEY (`media_id`)
        REFERENCES `sek_media` (`media_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_post_like` (
    `post_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`post_id`, `user_id`),
    KEY `idx_sek_post_like_user` (`user_id`, `created_at`),
    CONSTRAINT `fk_sek_post_like_post` FOREIGN KEY (`post_id`)
        REFERENCES `sek_post` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_post_like_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_post_comment` (
    `comment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `post_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `body` varchar(1000) NOT NULL,
    `created_at` datetime NOT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`comment_id`),
    KEY `idx_sek_post_comment_post` (`post_id`, `created_at`),
    KEY `idx_sek_post_comment_user` (`user_id`, `created_at`),
    CONSTRAINT `fk_sek_post_comment_post` FOREIGN KEY (`post_id`)
        REFERENCES `sek_post` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_post_comment_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `sek_report`
    ADD COLUMN `post_id` bigint unsigned DEFAULT NULL AFTER `target_account_id`,
    ADD KEY `idx_sek_report_post` (`post_id`),
    ADD CONSTRAINT `fk_sek_report_post` FOREIGN KEY (`post_id`)
        REFERENCES `sek_post` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE;
