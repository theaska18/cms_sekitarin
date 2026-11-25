ALTER TABLE `app_notification`
    ADD INDEX `idx_app_notification_user_id_notification_id` (`user_id`, `notification_id`),
    ADD INDEX `idx_app_notification_user_id_view_flag` (`user_id`, `view_flag`);
