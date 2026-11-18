-- Bootstrap update history tables for installations that predate System -> Update.
-- Safe to run more than once.
CREATE TABLE IF NOT EXISTS `sys_update` (
  `update_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` VARCHAR(32) NOT NULL,
  `previous_version` VARCHAR(32) NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending',
  `package_url` VARCHAR(512) NOT NULL,
  `checksum` VARCHAR(64) NOT NULL,
  `changelog` TEXT NULL,
  `log` LONGTEXT NULL,
  `started_on` DATETIME NULL,
  `finished_on` DATETIME NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`update_id`),
  KEY `status` (`status`),
  KEY `version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sys_update_migration` (
  `update_migration_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_id` BIGINT UNSIGNED NOT NULL,
  `migration_name` VARCHAR(191) NOT NULL,
  `checksum` VARCHAR(64) NOT NULL,
  `applied_on` DATETIME NOT NULL,
  PRIMARY KEY (`update_migration_id`),
  UNIQUE KEY `uk_sys_update_migration` (`update_id`, `migration_name`),
  CONSTRAINT `fk_sys_update_migration_update_id`
    FOREIGN KEY (`update_id`) REFERENCES `sys_update` (`update_id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

