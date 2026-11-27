-- Temporary diagnostics for the flutter_webrtc call client.
CREATE TABLE IF NOT EXISTS `sek_call_debug` (
    `debug_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `room` varchar(80) DEFAULT NULL,
    `stage` varchar(40) NOT NULL,
    `payload` longtext,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`debug_id`),
    KEY `idx_sek_call_debug_user` (`user_id`, `debug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
