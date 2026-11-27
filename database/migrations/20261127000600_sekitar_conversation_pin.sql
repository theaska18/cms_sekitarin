-- Per-user pinned conversations.
ALTER TABLE `sek_conversation_member`
    ADD COLUMN `pinned_at` datetime DEFAULT NULL AFTER `muted_at`,
    ADD KEY `idx_sek_conversation_member_pinned` (`user_id`, `pinned_at`);
