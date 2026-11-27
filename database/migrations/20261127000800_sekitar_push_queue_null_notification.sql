-- Call pushes are queued without an in-app notification row.
ALTER TABLE `sek_push_queue`
    MODIFY COLUMN `notification_id` bigint unsigned DEFAULT NULL;
