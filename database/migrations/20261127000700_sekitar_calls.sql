-- One-to-one and group Jitsi calls.
-- Direct calls ring the callee; group calls only show an in-chat marker.
CREATE TABLE IF NOT EXISTS `sek_call` (
    `call_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `scope` enum('direct','group') NOT NULL DEFAULT 'direct',
    `caller_user_id` bigint unsigned NOT NULL,
    `caller_account_id` bigint unsigned DEFAULT NULL,
    `callee_user_id` bigint unsigned DEFAULT NULL,
    `callee_account_id` bigint unsigned DEFAULT NULL,
    `conversation_id` bigint unsigned DEFAULT NULL,
    `room` varchar(80) NOT NULL,
    `kind` enum('voice','video') NOT NULL DEFAULT 'voice',
    `status` enum('ringing','answered','declined','missed','canceled','ended','active') NOT NULL DEFAULT 'ringing',
    `created_at` datetime NOT NULL,
    `answered_at` datetime DEFAULT NULL,
    `ended_at` datetime DEFAULT NULL,
    `duration_seconds` int unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`call_id`),
    UNIQUE KEY `uq_sek_call_room` (`room`),
    KEY `idx_sek_call_caller` (`caller_user_id`, `call_id`),
    KEY `idx_sek_call_callee` (`callee_user_id`, `call_id`),
    KEY `idx_sek_call_conversation` (`conversation_id`, `status`),
    KEY `idx_sek_call_status` (`status`, `created_at`),
    CONSTRAINT `fk_sek_call_caller` FOREIGN KEY (`caller_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_call_callee` FOREIGN KEY (`callee_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_call_conversation` FOREIGN KEY (`conversation_id`)
        REFERENCES `sek_conversation` (`conversation_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `sek_call_participant` (
    `call_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `account_id` bigint unsigned DEFAULT NULL,
    `joined_at` datetime NOT NULL,
    `left_at` datetime DEFAULT NULL,
    PRIMARY KEY (`call_id`, `user_id`),
    KEY `idx_sek_call_participant_user` (`user_id`, `left_at`),
    CONSTRAINT `fk_sek_call_participant_call` FOREIGN KEY (`call_id`)
        REFERENCES `sek_call` (`call_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_call_participant_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
