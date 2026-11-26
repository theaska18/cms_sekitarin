-- Sekitar mobile identity: users, email tokens, devices, consent, rate limits.
-- The mobile domain is deliberately separate from app_user/sys_tenant used by
-- the CMS admin. No existing table is modified by this migration.

CREATE TABLE `sek_user` (
    `user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `email` varchar(190) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `email_verified_at` datetime DEFAULT NULL,
    `display_name` varchar(80) NOT NULL,
    `status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
    `last_login_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uk_sek_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_user_token` (
    `token_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `purpose` enum('verify_email','reset_password') NOT NULL,
    `token_hash` char(64) NOT NULL,
    `expires_at` datetime NOT NULL,
    `used_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`token_id`),
    UNIQUE KEY `uk_sek_user_token_hash` (`token_hash`),
    KEY `idx_sek_user_token_user` (`user_id`, `purpose`, `expires_at`),
    CONSTRAINT `fk_sek_user_token_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_device` (
    `device_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `device_uuid` varchar(64) NOT NULL,
    `platform` enum('ios','android') NOT NULL,
    `app_version` varchar(32) DEFAULT NULL,
    `push_token` varchar(255) DEFAULT NULL,
    `access_token_hash` char(64) NOT NULL,
    `access_expires_at` datetime NOT NULL,
    `refresh_token_hash` char(64) NOT NULL,
    `refresh_expires_at` datetime NOT NULL,
    `active_account_id` bigint unsigned DEFAULT NULL,
    `last_seen_at` datetime DEFAULT NULL,
    `revoked_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`device_id`),
    UNIQUE KEY `uk_sek_device_user_uuid` (`user_id`, `device_uuid`),
    UNIQUE KEY `uk_sek_device_access` (`access_token_hash`),
    UNIQUE KEY `uk_sek_device_refresh` (`refresh_token_hash`),
    KEY `idx_sek_device_user` (`user_id`, `revoked_at`),
    CONSTRAINT `fk_sek_device_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_consent` (
    `consent_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `scope` enum('location','notifications','marketing') NOT NULL,
    `granted` tinyint(1) NOT NULL DEFAULT 0,
    `policy_version` varchar(20) NOT NULL DEFAULT '1.0',
    `device_id` bigint unsigned DEFAULT NULL,
    `granted_at` datetime DEFAULT NULL,
    `revoked_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`consent_id`),
    KEY `idx_sek_consent_user_scope` (`user_id`, `scope`, `created_at`),
    CONSTRAINT `fk_sek_consent_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_consent_device` FOREIGN KEY (`device_id`)
        REFERENCES `sek_device` (`device_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_rate_limit` (
    `rate_key` char(64) NOT NULL,
    `attempts` int unsigned NOT NULL DEFAULT 0,
    `window_started_at` datetime NOT NULL,
    `blocked_until` datetime DEFAULT NULL,
    PRIMARY KEY (`rate_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
