-- Business accounts can either keep a fixed map point or update from GPS.
ALTER TABLE `sek_account`
    ADD COLUMN `location_mode` enum('fixed','realtime') NOT NULL DEFAULT 'fixed' AFTER `service_radius_m`;
