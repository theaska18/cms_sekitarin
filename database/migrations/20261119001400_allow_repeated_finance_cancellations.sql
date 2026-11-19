-- Keep every cancellation attempt; rejected/revised requests may be submitted again.
ALTER TABLE `fin_cancellation`
    DROP INDEX `uk_fin_cancellation_source`,
    ADD KEY `idx_fin_cancellation_source` (`tenant_id`, `source_type`, `source_id`, `status`);
