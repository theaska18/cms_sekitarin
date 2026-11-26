-- Sekitar accounts, profiles, media, and location foundation.
-- One user owns a personal account plus any number of business accounts.

CREATE TABLE `sek_account_type` (
    `type_code` varchar(32) NOT NULL,
    `label_id` varchar(80) NOT NULL,
    `label_en` varchar(80) NOT NULL,
    `icon` varchar(40) NOT NULL DEFAULT 'store',
    `allows_presence` tinyint(1) NOT NULL DEFAULT 0,
    `allows_services` tinyint(1) NOT NULL DEFAULT 0,
    `sort_order` smallint NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`type_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `sek_account_type`
    (`type_code`, `label_id`, `label_en`, `icon`, `allows_presence`, `allows_services`, `sort_order`)
VALUES
    ('personal', 'Personal', 'Personal', 'person', 0, 0, 0),
    ('warung', 'Warung / Toko', 'Shop', 'store', 0, 0, 10),
    ('restoran', 'Restoran', 'Restaurant', 'restaurant', 0, 0, 20),
    ('tambal_ban', 'Tambal Ban / Bengkel', 'Tire Repair / Workshop', 'build', 0, 1, 30),
    ('driver_motor', 'Driver Motor', 'Motorcycle Driver', 'two_wheeler', 1, 1, 40),
    ('driver_mobil', 'Driver Mobil', 'Car Driver', 'car', 1, 1, 50),
    ('kurir_makanan', 'Kurir Makanan', 'Food Courier', 'delivery', 1, 1, 60),
    ('terapis', 'Terapis / Jasa Panggilan', 'Therapist / On-call Service', 'spa', 1, 1, 70),
    ('jasa_umum', 'Jasa Umum', 'General Service', 'handyman', 0, 1, 80);

CREATE TABLE `sek_media` (
    `media_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `owner_user_id` bigint unsigned NOT NULL,
    `purpose` enum('avatar','cover','post','chat','document') NOT NULL,
    `path` varchar(255) NOT NULL,
    `mime` varchar(100) NOT NULL,
    `size_bytes` int unsigned NOT NULL DEFAULT 0,
    `width` int unsigned DEFAULT NULL,
    `height` int unsigned DEFAULT NULL,
    `checksum_sha256` char(64) DEFAULT NULL,
    `status` enum('pending','ready','rejected') NOT NULL DEFAULT 'pending',
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`media_id`),
    KEY `idx_sek_media_owner` (`owner_user_id`, `created_at`),
    CONSTRAINT `fk_sek_media_owner` FOREIGN KEY (`owner_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_account` (
    `account_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `type_code` varchar(32) NOT NULL,
    `handle` varchar(40) NOT NULL,
    `display_name` varchar(120) NOT NULL,
    `bio` varchar(500) DEFAULT NULL,
    `avatar_media_id` bigint unsigned DEFAULT NULL,
    `cover_media_id` bigint unsigned DEFAULT NULL,
    `phone` varchar(32) DEFAULT NULL,
    `address` varchar(255) DEFAULT NULL,
    `service_radius_m` int unsigned NOT NULL DEFAULT 5000,
    `is_online` tinyint(1) NOT NULL DEFAULT 0,
    `verified_at` datetime DEFAULT NULL,
    `status` enum('active','suspended','archived') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`account_id`),
    UNIQUE KEY `uk_sek_account_handle` (`handle`),
    KEY `idx_sek_account_user` (`user_id`),
    KEY `idx_sek_account_type` (`type_code`, `status`),
    KEY `idx_sek_account_name` (`display_name`),
    KEY `idx_sek_account_avatar` (`avatar_media_id`),
    KEY `idx_sek_account_cover` (`cover_media_id`),
    CONSTRAINT `fk_sek_account_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_account_type` FOREIGN KEY (`type_code`)
        REFERENCES `sek_account_type` (`type_code`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_account_avatar` FOREIGN KEY (`avatar_media_id`)
        REFERENCES `sek_media` (`media_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_account_cover` FOREIGN KEY (`cover_media_id`)
        REFERENCES `sek_media` (`media_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_account_member` (
    `account_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `role` enum('owner','manager','staff') NOT NULL DEFAULT 'owner',
    `status` enum('active','removed') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`account_id`, `user_id`),
    KEY `idx_sek_account_member_user` (`user_id`, `status`),
    CONSTRAINT `fk_sek_account_member_account` FOREIGN KEY (`account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_account_member_user` FOREIGN KEY (`user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_account_hours` (
    `hours_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `account_id` bigint unsigned NOT NULL,
    `weekday` tinyint unsigned NOT NULL,
    `open_time` time NOT NULL,
    `close_time` time NOT NULL,
    `is_closed` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`hours_id`),
    UNIQUE KEY `uk_sek_account_hours_day` (`account_id`, `weekday`),
    CONSTRAINT `fk_sek_account_hours_account` FOREIGN KEY (`account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_account_location` (
    `account_id` bigint unsigned NOT NULL,
    `location` point NOT NULL SRID 4326,
    `address` varchar(255) DEFAULT NULL,
    `source` enum('manual','gps','geocode') NOT NULL DEFAULT 'manual',
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`account_id`),
    SPATIAL KEY `sp_sek_account_location` (`location`),
    CONSTRAINT `fk_sek_account_location_account` FOREIGN KEY (`account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_account_presence` (
    `account_id` bigint unsigned NOT NULL,
    `location` point NOT NULL SRID 4326,
    `accuracy_m` smallint unsigned DEFAULT NULL,
    `heading` smallint unsigned DEFAULT NULL,
    `speed_mps` decimal(5,2) DEFAULT NULL,
    `is_online` tinyint(1) NOT NULL DEFAULT 1,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`account_id`),
    SPATIAL KEY `sp_sek_account_presence` (`location`),
    CONSTRAINT `fk_sek_account_presence_account` FOREIGN KEY (`account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
