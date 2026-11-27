-- Per-user hidden messages (delete for me).
CREATE TABLE IF NOT EXISTS `sek_message_hidden` (
    `message_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `hidden_at` datetime NOT NULL,
    PRIMARY KEY (`message_id`, `user_id`),
    KEY `idx_sek_message_hidden_user` (`user_id`, `hidden_at`),
    CONSTRAINT `fk_sek_message_hidden_message` FOREIGN KEY (`message_id`)
        REFERENCES `sek_message` (`message_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_message_hidden_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
