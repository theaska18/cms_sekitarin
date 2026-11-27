-- Track call liveness so abandoned/answered calls can be closed automatically.
ALTER TABLE `sek_call`
    ADD COLUMN `last_activity_at` datetime DEFAULT NULL AFTER `answered_at`,
    ADD KEY `idx_sek_call_liveness` (`status`, `last_activity_at`);
