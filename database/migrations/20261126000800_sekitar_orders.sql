-- Sekitar service catalog, food/service/ride orders, and order history.

CREATE TABLE `sek_service` (
    `service_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `account_id` bigint unsigned NOT NULL,
    `name` varchar(120) NOT NULL,
    `description` varchar(500) DEFAULT NULL,
    `price` decimal(14,2) NOT NULL DEFAULT 0.00,
    `currency` char(3) NOT NULL DEFAULT 'IDR',
    `unit` varchar(20) NOT NULL DEFAULT 'item',
    `kind` enum('product','service','ride') NOT NULL DEFAULT 'service',
    `duration_minutes` smallint unsigned DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`service_id`),
    KEY `idx_sek_service_account` (`account_id`, `is_active`),
    CONSTRAINT `fk_sek_service_account` FOREIGN KEY (`account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_order` (
    `order_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(24) NOT NULL,
    `type` enum('food','service','ride') NOT NULL,
    `customer_user_id` bigint unsigned NOT NULL,
    `customer_account_id` bigint unsigned DEFAULT NULL,
    `provider_account_id` bigint unsigned NOT NULL,
    `status` enum('pending','accepted','in_progress','delivering','completed','cancelled') NOT NULL DEFAULT 'pending',
    `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
    `total` decimal(14,2) NOT NULL DEFAULT 0.00,
    `currency` char(3) NOT NULL DEFAULT 'IDR',
    `payment_method` enum('cash','transfer') NOT NULL DEFAULT 'cash',
    `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
    `notes` varchar(500) DEFAULT NULL,
    `delivery_address` varchar(255) DEFAULT NULL,
    `delivery_lat` decimal(10,7) DEFAULT NULL,
    `delivery_lng` decimal(10,7) DEFAULT NULL,
    `pickup_address` varchar(255) DEFAULT NULL,
    `pickup_lat` decimal(10,7) DEFAULT NULL,
    `pickup_lng` decimal(10,7) DEFAULT NULL,
    `dropoff_address` varchar(255) DEFAULT NULL,
    `dropoff_lat` decimal(10,7) DEFAULT NULL,
    `dropoff_lng` decimal(10,7) DEFAULT NULL,
    `scheduled_at` datetime DEFAULT NULL,
    `cancel_reason` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    `accepted_at` datetime DEFAULT NULL,
    `started_at` datetime DEFAULT NULL,
    `completed_at` datetime DEFAULT NULL,
    `cancelled_at` datetime DEFAULT NULL,
    PRIMARY KEY (`order_id`),
    UNIQUE KEY `uk_sek_order_code` (`code`),
    KEY `idx_sek_order_customer` (`customer_user_id`, `created_at`),
    KEY `idx_sek_order_provider` (`provider_account_id`, `status`, `created_at`),
    CONSTRAINT `fk_sek_order_customer` FOREIGN KEY (`customer_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_order_customer_account` FOREIGN KEY (`customer_account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_order_provider` FOREIGN KEY (`provider_account_id`)
        REFERENCES `sek_account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_order_item` (
    `item_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `order_id` bigint unsigned NOT NULL,
    `service_id` bigint unsigned DEFAULT NULL,
    `name` varchar(120) NOT NULL,
    `price` decimal(14,2) NOT NULL,
    `quantity` smallint unsigned NOT NULL DEFAULT 1,
    `subtotal` decimal(14,2) NOT NULL,
    PRIMARY KEY (`item_id`),
    KEY `idx_sek_order_item_order` (`order_id`),
    CONSTRAINT `fk_sek_order_item_order` FOREIGN KEY (`order_id`)
        REFERENCES `sek_order` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_order_item_service` FOREIGN KEY (`service_id`)
        REFERENCES `sek_service` (`service_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sek_order_event` (
    `event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `order_id` bigint unsigned NOT NULL,
    `status` varchar(20) NOT NULL,
    `actor_user_id` bigint unsigned DEFAULT NULL,
    `note` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`event_id`),
    KEY `idx_sek_order_event_order` (`order_id`, `event_id`),
    CONSTRAINT `fk_sek_order_event_order` FOREIGN KEY (`order_id`)
        REFERENCES `sek_order` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sek_order_event_actor` FOREIGN KEY (`actor_user_id`)
        REFERENCES `sek_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
