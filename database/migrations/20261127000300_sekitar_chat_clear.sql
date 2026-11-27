-- Per-user cleared history marker so clearing a chat only affects that user.
ALTER TABLE `sek_conversation_member`
    ADD COLUMN `cleared_before_message_id` bigint unsigned DEFAULT NULL AFTER `last_read_message_id`;
