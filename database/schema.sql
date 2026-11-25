-- CMSNivo baseline database schema.
-- Snapshot schema only; does not contain application data.
-- Regenerate after intentional schema changes:
--   mysqldump --no-data ... cmsdb > database/schema.sql
-- Release database changes belong in database/migrations/*.sql.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `app_approval_action`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_approval_action` (
  `action_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `request_id` bigint unsigned NOT NULL,
  `step_number` int unsigned NOT NULL,
  `approver_user_id` bigint NOT NULL,
  `decision` varchar(16) NOT NULL,
  `note` text,
  `create_on` datetime NOT NULL,
  PRIMARY KEY (`action_id`),
  KEY `tenant_id_request_id` (`tenant_id`,`request_id`),
  KEY `tenant_id_approver_user_id` (`tenant_id`,`approver_user_id`),
  KEY `idx_app_approval_action_request_id` (`request_id`),
  KEY `idx_app_approval_action_approver_user_id` (`approver_user_id`),
  CONSTRAINT `fk_app_approval_action_approver_user_id` FOREIGN KEY (`approver_user_id`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_action_request_id` FOREIGN KEY (`request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_action_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_approval_error`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_approval_error` (
  `error_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `request_id` bigint unsigned NOT NULL,
  `flow_id` bigint unsigned DEFAULT NULL,
  `step_number` int unsigned NOT NULL DEFAULT '1',
  `org_id` bigint DEFAULT NULL,
  `error_code` varchar(64) NOT NULL,
  `error_message` text,
  `status` varchar(16) NOT NULL DEFAULT 'open',
  `assigned_employee_id` bigint DEFAULT NULL,
  `assigned_approver_user_id` bigint DEFAULT NULL,
  `handled_by` bigint DEFAULT NULL,
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  `resolved_on` datetime DEFAULT NULL,
  PRIMARY KEY (`error_id`),
  KEY `tenant_id_status_create_on` (`tenant_id`,`status`,`create_on`),
  KEY `tenant_id_request_id_status` (`tenant_id`,`request_id`,`status`),
  KEY `idx_app_approval_error_request_id` (`request_id`),
  KEY `idx_app_approval_error_flow_id` (`flow_id`),
  KEY `idx_app_approval_error_org_id` (`org_id`),
  KEY `idx_app_approval_error_assigned_employee_id` (`assigned_employee_id`),
  KEY `idx_app_approval_error_assigned_approver_user_id` (`assigned_approver_user_id`),
  KEY `idx_app_approval_error_handled_by` (`handled_by`),
  CONSTRAINT `fk_app_approval_error_assigned_approver_user_id` FOREIGN KEY (`assigned_approver_user_id`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_error_assigned_employee_id` FOREIGN KEY (`assigned_employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_error_flow_id` FOREIGN KEY (`flow_id`) REFERENCES `app_approval_flow` (`flow_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_error_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_error_org_id` FOREIGN KEY (`org_id`) REFERENCES `hr_org` (`org_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_error_request_id` FOREIGN KEY (`request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_error_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_approval_flow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_approval_flow` (
  `flow_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `tenant_id` bigint NOT NULL,
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`flow_id`),
  UNIQUE KEY `uk_app_approval_flow_tenant_code` (`tenant_id`,`flow_code`),
  KEY `tenant_id_request_type` (`tenant_id`),
  KEY `idx_app_approval_flow_flow_code` (`flow_code`),
  CONSTRAINT `fk_app_approval_flow_flow_code` FOREIGN KEY (`flow_code`) REFERENCES `sys_approval_flow` (`flow_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_flow_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_approval_flow_step`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_approval_flow_step` (
  `flow_step_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `flow_id` bigint unsigned NOT NULL,
  `step_number` int unsigned NOT NULL,
  `approver_user_id` bigint DEFAULT NULL,
  `create_on` datetime NOT NULL,
  `approver_type` varchar(16) DEFAULT 'user',
  `approver_employee_id` bigint DEFAULT NULL,
  `org_id` bigint DEFAULT NULL,
  PRIMARY KEY (`flow_step_id`),
  KEY `tenant_id_flow_id_step_number` (`tenant_id`,`flow_id`,`step_number`),
  KEY `tenant_id_approver_user_id` (`tenant_id`,`approver_user_id`),
  KEY `idx_app_approval_flow_step_flow_id` (`flow_id`),
  KEY `idx_app_approval_flow_step_approver_user_id` (`approver_user_id`),
  KEY `idx_app_approval_flow_step_approver_employee_id` (`approver_employee_id`),
  KEY `idx_app_approval_flow_step_org_id` (`org_id`),
  CONSTRAINT `fk_app_approval_flow_step_approver_employee_id` FOREIGN KEY (`approver_employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_flow_step_approver_user_id` FOREIGN KEY (`approver_user_id`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_flow_step_flow_id` FOREIGN KEY (`flow_id`) REFERENCES `app_approval_flow` (`flow_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_flow_step_org_id` FOREIGN KEY (`org_id`) REFERENCES `hr_org` (`org_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_flow_step_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_approval_link_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_approval_link_token` (
  `token_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `request_id` bigint unsigned NOT NULL,
  `approver_user_id` bigint NOT NULL,
  `decision` varchar(8) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `create_on` datetime NOT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `tenant_id_request_id` (`tenant_id`,`request_id`),
  KEY `idx_app_approval_link_token_request_id` (`request_id`),
  KEY `idx_app_approval_link_token_approver_user_id` (`approver_user_id`),
  CONSTRAINT `fk_app_approval_link_token_approver_user_id` FOREIGN KEY (`approver_user_id`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_link_token_request_id` FOREIGN KEY (`request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_link_token_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_approval_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_approval_request` (
  `request_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `flow_id` bigint unsigned DEFAULT NULL,
  `flow_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `source_type` varchar(32) DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `request_action` varchar(16) NOT NULL DEFAULT 'submit',
  `requester_user_id` bigint NOT NULL,
  `subject` varchar(180) NOT NULL,
  `details` text,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `current_step` int unsigned NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  `requester_employee_id` bigint DEFAULT NULL,
  `submitted_by` bigint DEFAULT NULL,
  `current_approver_user_id` bigint DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `tenant_id_status_current_step` (`tenant_id`,`status`,`current_step`),
  KEY `tenant_id_flow_id` (`tenant_id`,`flow_id`),
  KEY `idx_app_approval_request_source` (`tenant_id`,`source_type`,`source_id`),
  KEY `idx_app_approval_request_flow_id` (`flow_id`),
  KEY `idx_app_approval_request_flow_code` (`flow_code`),
  KEY `idx_app_approval_request_requester_user_id` (`requester_user_id`),
  KEY `idx_app_approval_request_requester_employee_id` (`requester_employee_id`),
  KEY `idx_app_approval_request_submitted_by` (`submitted_by`),
  KEY `idx_app_approval_request_current_approver_user_id` (`current_approver_user_id`),
  CONSTRAINT `fk_app_approval_request_current_approver_user_id` FOREIGN KEY (`current_approver_user_id`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_request_flow_code` FOREIGN KEY (`flow_code`) REFERENCES `sys_approval_flow` (`flow_code`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_request_flow_id` FOREIGN KEY (`flow_id`) REFERENCES `app_approval_flow` (`flow_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_request_requester_employee_id` FOREIGN KEY (`requester_employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_request_requester_user_id` FOREIGN KEY (`requester_user_id`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_request_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_approval_request_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_chat` (
  `chat_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `chat_type` varchar(32) NOT NULL,
  `message` text,
  `file_path` varchar(128) DEFAULT NULL,
  `create_on` datetime NOT NULL,
  `remove_flag` tinyint NOT NULL DEFAULT '0',
  `from_group_id` bigint DEFAULT NULL,
  `from_user_id` bigint NOT NULL,
  `to_user_id` bigint DEFAULT NULL,
  `to_group_id` bigint DEFAULT NULL,
  PRIMARY KEY (`chat_id`),
  KEY `fk_app_chat_tenant_id_idx` (`tenant_id`),
  KEY `fk_app_chat_from_group_id_idx` (`from_group_id`),
  KEY `fk_app_chat_from_user_id_idx` (`from_user_id`),
  KEY `fk_app_chat_to_user_id_idx` (`to_user_id`),
  KEY `fk_app_chat_to_group_id_idx` (`to_group_id`),
  CONSTRAINT `fk_app_chat_from_group_id` FOREIGN KEY (`from_group_id`) REFERENCES `app_chat_group` (`chat_group_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_app_chat_from_user_id` FOREIGN KEY (`from_user_id`) REFERENCES `app_user` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_app_chat_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_chat_to_group_id` FOREIGN KEY (`to_group_id`) REFERENCES `app_chat_group` (`chat_group_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_app_chat_to_user_id` FOREIGN KEY (`to_user_id`) REFERENCES `app_user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_chat_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_chat_group` (
  `chat_group_id` bigint NOT NULL COMMENT 'Id chat group',
  `tenant_id` bigint NOT NULL,
  `group_name` varchar(64) NOT NULL COMMENT 'Nama chat group',
  `create_on` datetime NOT NULL COMMENT 'Waktu di buat chat group',
  `initiator_user_id` bigint NOT NULL COMMENT 'User yang menginiasai',
  `remove_flag` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`chat_group_id`),
  KEY `fk_app_chat_group_tenant_id_idx` (`tenant_id`),
  KEY `fk_app_chat_group_initiator_user_id_idx` (`initiator_user_id`),
  CONSTRAINT `fk_app_chat_group_initiator_user_id` FOREIGN KEY (`initiator_user_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_chat_group_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_chat_group_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_chat_group_user` (
  `chat_group_user_id` bigint NOT NULL AUTO_INCREMENT,
  `chat_group_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `join_on` datetime NOT NULL,
  `admin_flag` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`chat_group_user_id`),
  UNIQUE KEY `uk_app_chat_group_user` (`chat_group_id`,`user_id`),
  KEY `fk_app_chat_group_user_user_id_idx` (`user_id`),
  CONSTRAINT `fk_app_chat_group_user_chat_group_user_id` FOREIGN KEY (`chat_group_id`) REFERENCES `app_chat_group` (`chat_group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_chat_group_user_user_id` FOREIGN KEY (`user_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_heartbeat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_heartbeat` (
  `heartbeat_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `user_id` bigint DEFAULT NULL,
  `heartbeat_type` varchar(32) NOT NULL,
  `heartbeat_on` datetime NOT NULL,
  PRIMARY KEY (`heartbeat_id`),
  KEY `fk_app_heartbeat_tenant_id_idx` (`tenant_id`),
  KEY `fk_app_heartbeat_user_id_idx` (`user_id`),
  CONSTRAINT `fk_app_heartbeat_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_heartbeat_user_id` FOREIGN KEY (`user_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_menu_visit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_menu_visit` (
  `menu_visit_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `menu_code` varchar(64) NOT NULL,
  `visited_at` datetime NOT NULL,
  PRIMARY KEY (`menu_visit_id`),
  KEY `tenant_id_visited_at_menu_code` (`tenant_id`,`visited_at`,`menu_code`),
  KEY `tenant_id_user_id_visited_at` (`tenant_id`,`user_id`,`visited_at`),
  KEY `fk_app_menu_visit_user_id_idx` (`user_id`),
  CONSTRAINT `fk_app_menu_visit_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_menu_visit_user_id` FOREIGN KEY (`user_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2063 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_notification` (
  `notification_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL COMMENT 'User yang dikirim notifikasi',
  `notification_type` varchar(32) NOT NULL COMMENT 'Jenis Notifikasi',
  `title` varchar(128) NOT NULL,
  `message` text NOT NULL COMMENT 'Pesan Notifikasi',
  `view_flag` tinyint NOT NULL DEFAULT '0' COMMENT 'Flag jika di klik icon notifikasi',
  `read_flag` tinyint NOT NULL DEFAULT '0' COMMENT 'Flag jika bear bear di baca notifikasinya',
  `create_on` datetime DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `fk_sys_notification_user_id_idx` (`user_id`),
  KEY `idx_app_notification_user_id_notification_id` (`user_id`,`notification_id`),
  KEY `idx_app_notification_user_id_view_flag` (`user_id`,`view_flag`),
  CONSTRAINT `fk_sys_notification_user_id` FOREIGN KEY (`user_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_role` (
  `role_id` bigint NOT NULL AUTO_INCREMENT,
  `role_code` varchar(32) NOT NULL,
  `tenant_id` bigint NOT NULL,
  `role_name` varchar(64) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `active_flag` tinyint NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uk_app_role` (`role_code`,`tenant_id`),
  KEY `fk_app_role_tenant_id_idx` (`tenant_id`),
  CONSTRAINT `fk_app_role_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_role_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_role_menu` (
  `role_menu_id` bigint NOT NULL AUTO_INCREMENT,
  `role_id` bigint NOT NULL,
  `menu_code` varchar(32) NOT NULL,
  PRIMARY KEY (`role_menu_id`),
  UNIQUE KEY `uk_app_role_menu` (`role_id`,`menu_code`),
  CONSTRAINT `fk_app_role_menu_role_id` FOREIGN KEY (`role_id`) REFERENCES `app_role` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=492 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_role_menu_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_role_menu_access` (
  `role_menu_access_id` bigint NOT NULL AUTO_INCREMENT,
  `role_menu_id` bigint NOT NULL,
  `access_code` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`role_menu_access_id`),
  UNIQUE KEY `uk_app_role_menu_access` (`role_menu_id`,`access_code`),
  CONSTRAINT `fk_app_role_menu_access_role_menu_id` FOREIGN KEY (`role_menu_id`) REFERENCES `app_role_menu` (`role_menu_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1542 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_sequence` (
  `sequence_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `sequence_code` varchar(32) NOT NULL,
  `repeat_type` varchar(32) NOT NULL,
  `format` varchar(45) NOT NULL,
  `last_value` int NOT NULL,
  `last_repeat` date DEFAULT NULL,
  `use_flag` varchar(45) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sequence_id`),
  UNIQUE KEY `uk_app_sequence` (`tenant_id`,`sequence_code`),
  KEY `fk_app_sequence_repeat_type_idx` (`repeat_type`),
  KEY `fk_app_sequence_sequence_code_idx` (`sequence_code`),
  CONSTRAINT `fk_app_sequence_repeat_type` FOREIGN KEY (`repeat_type`) REFERENCES `sys_parameter_option` (`option_code`) ON UPDATE CASCADE,
  CONSTRAINT `fk_app_sequence_sequence_code` FOREIGN KEY (`sequence_code`) REFERENCES `sys_sequence` (`sequence_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_sequence_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_user` (
  `user_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `user_code` varchar(32) NOT NULL,
  `username` varchar(32) NOT NULL COMMENT 'Di gunakan untuk login',
  `role_id` bigint NOT NULL COMMENT 'Peran untuk menentukan hak akses menu dan access',
  `emloyee_flag` tinyint NOT NULL DEFAULT '0' COMMENT 'Untuk menentukan apakah user ini terkait dengan sys_employee',
  `employee_id` bigint DEFAULT NULL COMMENT 'Employee id untuk menentukan apakah user ini terikat dengan sys_employee, dan wajib jika employee_flag=true',
  `password` varchar(128) NOT NULL COMMENT 'Password untuk user di Encrypt menggunakan password_hash',
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  `full_name` varchar(64) DEFAULT NULL COMMENT 'Nama User',
  `block_flag` tinyint NOT NULL DEFAULT '0' COMMENT 'Untuk Penanda User Di Block',
  `access_api_flag` tinyint NOT NULL DEFAULT '0' COMMENT 'Petanda jika ingin mengaktifkan user bisa mengakses dengan API',
  `api_secret` varchar(128) DEFAULT NULL COMMENT 'Api secret jika access_api_flag true maka ini sahib disini',
  `theme` varchar(16) DEFAULT NULL,
  `language` varchar(32) DEFAULT NULL,
  `chat_flag` tinyint DEFAULT '1',
  `last_online` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uk_app_user1` (`tenant_id`,`user_code`),
  UNIQUE KEY `uk_app_user2` (`username`),
  UNIQUE KEY `uk_app_user_api_secret` (`api_secret`),
  KEY `fk_app_user_employee_id_idx` (`employee_id`),
  KEY `fk_app_user_role_id_idx` (`role_id`),
  CONSTRAINT `fk_app_user_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_app_user_role_id` FOREIGN KEY (`role_id`) REFERENCES `app_role` (`role_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_app_user_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_account` (
  `account_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `parent_account_id` bigint unsigned DEFAULT NULL,
  `account_code` varchar(32) NOT NULL,
  `account_name` varchar(120) NOT NULL,
  `account_type` varchar(24) NOT NULL,
  `normal_balance` varchar(6) NOT NULL,
  `posting_flag` tinyint(1) NOT NULL DEFAULT '1',
  `description` text,
  `active_flag` tinyint unsigned NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `update_on` datetime DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uq_account_1` (`tenant_id`,`account_code`),
  KEY `tenant_id_parent_account_id` (`tenant_id`,`parent_account_id`),
  KEY `tenant_id_account_type_active_flag` (`tenant_id`,`account_type`,`active_flag`),
  KEY `idx_fin_account_parent_account_id` (`parent_account_id`),
  KEY `idx_fin_account_created_by` (`created_by`),
  KEY `idx_fin_account_updated_by` (`updated_by`),
  CONSTRAINT `fk_fin_account_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_account_parent_account_id` FOREIGN KEY (`parent_account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_account_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_account_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_accounting_period`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_accounting_period` (
  `period_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `fiscal_year_id` bigint unsigned NOT NULL,
  `period_number` int unsigned NOT NULL,
  `period_name` varchar(80) NOT NULL,
  `start_on` date NOT NULL,
  `end_on` date NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'open',
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`period_id`),
  UNIQUE KEY `uq_accounting_period_1` (`tenant_id`,`fiscal_year_id`,`period_number`),
  KEY `tenant_id_start_on_end_on` (`tenant_id`,`start_on`,`end_on`),
  KEY `idx_fin_accounting_period_fiscal_year_id` (`fiscal_year_id`),
  CONSTRAINT `fk_fin_accounting_period_fiscal_year_id` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fin_fiscal_year` (`fiscal_year_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_accounting_period_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_bank_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_bank_reconciliation` (
  `reconciliation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `reconciliation_number` varchar(48) DEFAULT NULL,
  `cash_account_id` bigint unsigned NOT NULL,
  `statement_date` date NOT NULL,
  `statement_reference` varchar(80) DEFAULT NULL,
  `opening_balance` decimal(20,6) NOT NULL,
  `closing_balance` decimal(20,6) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `create_on` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `completed_on` datetime DEFAULT NULL,
  `completed_by` bigint DEFAULT NULL,
  `approval_request_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`reconciliation_id`),
  UNIQUE KEY `uq_bank_reconciliation_1` (`tenant_id`,`cash_account_id`,`statement_date`),
  KEY `tenant_id_status_statement_date` (`tenant_id`,`status`,`statement_date`),
  KEY `idx_fin_bank_reconciliation_approval` (`tenant_id`,`approval_request_id`),
  KEY `idx_fin_bank_reconciliation_cash_account_id` (`cash_account_id`),
  KEY `idx_fin_bank_reconciliation_approval_request_id` (`approval_request_id`),
  KEY `idx_fin_bank_reconciliation_created_by` (`created_by`),
  KEY `idx_fin_bank_reconciliation_completed_by` (`completed_by`),
  CONSTRAINT `fk_fin_bank_reconciliation_approval_request_id` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_bank_reconciliation_cash_account_id` FOREIGN KEY (`cash_account_id`) REFERENCES `fin_cash_account` (`cash_account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_bank_reconciliation_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_bank_reconciliation_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_bank_reconciliation_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_bank_reconciliation_line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_bank_reconciliation_line` (
  `reconciliation_line_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `reconciliation_id` bigint unsigned NOT NULL,
  `journal_line_id` bigint unsigned DEFAULT NULL,
  `statement_on` date NOT NULL,
  `description` varchar(240) DEFAULT NULL,
  `statement_amount` decimal(20,6) NOT NULL,
  `matched_flag` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`reconciliation_line_id`),
  KEY `tenant_id_reconciliation_id` (`tenant_id`,`reconciliation_id`),
  UNIQUE KEY `uk_bank_reconciliation_journal_line` (`tenant_id`,`journal_line_id`),
  KEY `tenant_id_journal_line_id` (`tenant_id`,`journal_line_id`),
  KEY `idx_fin_bank_reconciliation_line_reconciliation_id` (`reconciliation_id`),
  KEY `idx_fin_bank_reconciliation_line_journal_line_id` (`journal_line_id`),
  CONSTRAINT `fk_fin_bank_reconciliation_line_journal_line_id` FOREIGN KEY (`journal_line_id`) REFERENCES `fin_journal_line` (`journal_line_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_bank_reconciliation_line_reconciliation_id` FOREIGN KEY (`reconciliation_id`) REFERENCES `fin_bank_reconciliation` (`reconciliation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_bank_reconciliation_line_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_cash_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_cash_account` (
  `cash_account_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `account_code` varchar(32) NOT NULL,
  `account_name` varchar(120) NOT NULL,
  `account_kind` varchar(8) NOT NULL,
  `currency_code` char(3) NOT NULL,
  `institution_name` varchar(120) DEFAULT NULL,
  `active_flag` tinyint unsigned NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`cash_account_id`),
  UNIQUE KEY `uq_cash_account_1` (`tenant_id`,`account_code`),
  KEY `tenant_id_account_id` (`tenant_id`,`account_id`),
  KEY `idx_fin_cash_account_account_id` (`account_id`),
  KEY `idx_fin_cash_account_created_by` (`created_by`),
  CONSTRAINT `fk_fin_cash_account_account_id` FOREIGN KEY (`account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_cash_account_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_cash_account_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_cash_transfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_cash_transfer` (
  `cash_transfer_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `from_cash_account_id` bigint unsigned NOT NULL,
  `to_cash_account_id` bigint unsigned NOT NULL,
  `transfer_number` varchar(48) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_currency` char(3) NOT NULL,
  `to_currency` char(3) NOT NULL,
  `from_exchange_rate` decimal(20,10) NOT NULL,
  `to_exchange_rate` decimal(20,10) NOT NULL,
  `from_amount` decimal(20,6) NOT NULL,
  `to_amount` decimal(20,6) NOT NULL,
  `fee_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `base_from_amount` decimal(20,6) NOT NULL,
  `base_to_amount` decimal(20,6) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `approval_request_id` bigint unsigned DEFAULT NULL,
  `memo` text,
  `create_on` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `posted_on` datetime DEFAULT NULL,
  `posted_by` bigint DEFAULT NULL,
  PRIMARY KEY (`cash_transfer_id`),
  UNIQUE KEY `uq_cash_transfer_1` (`tenant_id`,`transfer_number`),
  KEY `tenant_id_transfer_date_status` (`tenant_id`,`transfer_date`,`status`),
  KEY `tenant_id_from_cash_account_id_transfer_date` (`tenant_id`,`from_cash_account_id`,`transfer_date`),
  KEY `tenant_id_to_cash_account_id_transfer_date` (`tenant_id`,`to_cash_account_id`,`transfer_date`),
  KEY `idx_fin_cash_transfer_approval` (`tenant_id`,`approval_request_id`),
  KEY `idx_fin_cash_transfer_from_cash_account_id` (`from_cash_account_id`),
  KEY `idx_fin_cash_transfer_to_cash_account_id` (`to_cash_account_id`),
  KEY `idx_fin_cash_transfer_approval_request_id` (`approval_request_id`),
  KEY `idx_fin_cash_transfer_created_by` (`created_by`),
  KEY `idx_fin_cash_transfer_posted_by` (`posted_by`),
  CONSTRAINT `fk_fin_cash_transfer_approval_request_id` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_cash_transfer_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_cash_transfer_from_cash_account_id` FOREIGN KEY (`from_cash_account_id`) REFERENCES `fin_cash_account` (`cash_account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_cash_transfer_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_cash_transfer_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_cash_transfer_to_cash_account_id` FOREIGN KEY (`to_cash_account_id`) REFERENCES `fin_cash_account` (`cash_account_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_category` (
  `category_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `category_type` varchar(16) NOT NULL,
  `category_code` varchar(32) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `active_flag` tinyint unsigned NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_category_1` (`tenant_id`,`category_type`,`category_code`),
  KEY `tenant_id_category_type_active_flag` (`tenant_id`,`category_type`,`active_flag`),
  CONSTRAINT `fk_fin_category_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_customer` (
  `customer_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `customer_code` varchar(32) NOT NULL,
  `customer_name` varchar(160) NOT NULL,
  `email_address` varchar(160) DEFAULT NULL,
  `phone_number` varchar(32) DEFAULT NULL,
  `tax_number` varchar(64) DEFAULT NULL,
  `billing_address` text,
  `active_flag` tinyint unsigned NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `update_on` datetime DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `uq_customer_1` (`tenant_id`,`customer_code`),
  KEY `tenant_id_customer_name` (`tenant_id`,`customer_name`),
  KEY `idx_fin_customer_created_by` (`created_by`),
  KEY `idx_fin_customer_updated_by` (`updated_by`),
  CONSTRAINT `fk_fin_customer_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_customer_payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_customer_payment` (
  `customer_payment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `cash_account_id` bigint unsigned NOT NULL,
  `payment_number` varchar(48) NOT NULL,
  `payment_date` date NOT NULL,
  `currency_code` char(3) NOT NULL,
  `exchange_rate` decimal(20,10) NOT NULL,
  `amount` decimal(20,6) NOT NULL,
  `base_amount` decimal(20,6) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `reference` varchar(80) DEFAULT NULL,
  `create_on` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `posted_on` datetime DEFAULT NULL,
  `posted_by` bigint DEFAULT NULL,
  `approval_request_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`customer_payment_id`),
  UNIQUE KEY `uq_customer_payment_1` (`tenant_id`,`payment_number`),
  KEY `tenant_id_customer_id_payment_date` (`tenant_id`,`customer_id`,`payment_date`),
  KEY `tenant_id_cash_account_id_payment_date` (`tenant_id`,`cash_account_id`,`payment_date`),
  KEY `idx_fin_customer_payment_approval` (`tenant_id`,`approval_request_id`),
  KEY `idx_fin_customer_payment_customer_id` (`customer_id`),
  KEY `idx_fin_customer_payment_cash_account_id` (`cash_account_id`),
  KEY `idx_fin_customer_payment_approval_request_id` (`approval_request_id`),
  KEY `idx_fin_customer_payment_created_by` (`created_by`),
  KEY `idx_fin_customer_payment_posted_by` (`posted_by`),
  CONSTRAINT `fk_fin_customer_payment_approval_request_id` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_payment_cash_account_id` FOREIGN KEY (`cash_account_id`) REFERENCES `fin_cash_account` (`cash_account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_payment_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_payment_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `fin_customer` (`customer_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_payment_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_payment_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_customer_payment_allocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_customer_payment_allocation` (
  `customer_payment_allocation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `customer_payment_id` bigint unsigned NOT NULL,
  `sales_invoice_id` bigint unsigned NOT NULL,
  `amount` decimal(20,6) NOT NULL,
  `base_amount` decimal(20,6) NOT NULL,
  PRIMARY KEY (`customer_payment_allocation_id`),
  UNIQUE KEY `uq_customer_payment_allocation_1` (`tenant_id`,`customer_payment_id`,`sales_invoice_id`),
  KEY `tenant_id_sales_invoice_id` (`tenant_id`,`sales_invoice_id`),
  KEY `idx_fin_customer_payment_allocation_customer_payment_id` (`customer_payment_id`),
  KEY `idx_fin_customer_payment_allocation_sales_invoice_id` (`sales_invoice_id`),
  CONSTRAINT `fk_fin_customer_payment_allocation_customer_payment_id` FOREIGN KEY (`customer_payment_id`) REFERENCES `fin_customer_payment` (`customer_payment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_payment_allocation_sales_invoice_id` FOREIGN KEY (`sales_invoice_id`) REFERENCES `fin_sales_invoice` (`sales_invoice_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_customer_payment_allocation_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_department` (
  `department_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `department_code` varchar(32) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `active_flag` tinyint unsigned NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `uq_department_1` (`tenant_id`,`department_code`),
  CONSTRAINT `fk_fin_department_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_exchange_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_exchange_rate` (
  `exchange_rate_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `from_currency` char(3) NOT NULL,
  `to_currency` char(3) NOT NULL,
  `effective_on` date NOT NULL,
  `rate` decimal(20,10) NOT NULL,
  `rate_source` varchar(40) DEFAULT NULL,
  `create_on` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  PRIMARY KEY (`exchange_rate_id`),
  UNIQUE KEY `uq_exchange_rate_1` (`tenant_id`,`from_currency`,`to_currency`,`effective_on`),
  KEY `idx_fin_exchange_rate_created_by` (`created_by`),
  CONSTRAINT `fk_fin_exchange_rate_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_exchange_rate_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_fiscal_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_fiscal_year` (
  `fiscal_year_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `year_code` varchar(32) NOT NULL,
  `year_name` varchar(80) NOT NULL,
  `start_on` date NOT NULL,
  `end_on` date NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'open',
  `create_on` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`fiscal_year_id`),
  UNIQUE KEY `uq_fiscal_year_1` (`tenant_id`,`year_code`),
  KEY `tenant_id_start_on_end_on` (`tenant_id`,`start_on`,`end_on`),
  KEY `idx_fin_fiscal_year_created_by` (`created_by`),
  CONSTRAINT `fk_fin_fiscal_year_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_fiscal_year_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_journal_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_journal_entry` (
  `journal_entry_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `period_id` bigint unsigned DEFAULT NULL,
  `journal_number` varchar(48) NOT NULL,
  `journal_date` date NOT NULL,
  `source_type` varchar(32) NOT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `description` varchar(240) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `debit_total` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `credit_total` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `create_on` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `posted_on` datetime DEFAULT NULL,
  `posted_by` bigint DEFAULT NULL,
  `reversed_entry_id` bigint unsigned DEFAULT NULL,
  `approval_request_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`journal_entry_id`),
  UNIQUE KEY `uq_journal_entry_1` (`tenant_id`,`journal_number`),
  UNIQUE KEY `uq_journal_entry_2` (`tenant_id`,`source_type`,`source_id`),
  KEY `tenant_id_journal_date_status` (`tenant_id`,`journal_date`,`status`),
  KEY `tenant_id_period_id_journal_date` (`tenant_id`,`period_id`,`journal_date`),
  KEY `idx_fin_journal_entry_approval` (`tenant_id`,`approval_request_id`),
  KEY `idx_fin_journal_entry_period_id` (`period_id`),
  KEY `idx_fin_journal_entry_reversed_entry_id` (`reversed_entry_id`),
  KEY `idx_fin_journal_entry_approval_request_id` (`approval_request_id`),
  KEY `idx_fin_journal_entry_created_by` (`created_by`),
  KEY `idx_fin_journal_entry_posted_by` (`posted_by`),
  CONSTRAINT `fk_fin_journal_entry_approval_request_id` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_journal_entry_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_journal_entry_period_id` FOREIGN KEY (`period_id`) REFERENCES `fin_accounting_period` (`period_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_journal_entry_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_journal_entry_reversed_entry_id` FOREIGN KEY (`reversed_entry_id`) REFERENCES `fin_journal_entry` (`journal_entry_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_journal_entry_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_journal_line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_journal_line` (
  `journal_line_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `journal_entry_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `line_number` int unsigned NOT NULL,
  `description` varchar(240) DEFAULT NULL,
  `currency_code` char(3) NOT NULL,
  `exchange_rate` decimal(20,10) NOT NULL,
  `debit_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `credit_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `debit_base_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `credit_base_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  PRIMARY KEY (`journal_line_id`),
  UNIQUE KEY `uq_journal_line_1` (`tenant_id`,`journal_entry_id`,`line_number`),
  KEY `tenant_id_account_id` (`tenant_id`,`account_id`),
  KEY `idx_fin_journal_line_account_currency` (`tenant_id`,`account_id`,`currency_code`),
  KEY `tenant_id_department_id` (`tenant_id`,`department_id`),
  KEY `idx_fin_journal_line_journal_entry_id` (`journal_entry_id`),
  KEY `idx_fin_journal_line_account_id` (`account_id`),
  KEY `idx_fin_journal_line_department_id` (`department_id`),
  CONSTRAINT `fk_fin_journal_line_account_id` FOREIGN KEY (`account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_journal_line_department_id` FOREIGN KEY (`department_id`) REFERENCES `fin_department` (`department_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_journal_line_journal_entry_id` FOREIGN KEY (`journal_entry_id`) REFERENCES `fin_journal_entry` (`journal_entry_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_journal_line_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_purchase_bill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_purchase_bill` (
  `purchase_bill_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `bill_number` varchar(48) NOT NULL,
  `integration_number` varchar(48) DEFAULT NULL,
  `integration_party_name` varchar(160) DEFAULT NULL,
  `integration_party_code` varchar(32) DEFAULT NULL,
  `supplier_reference` varchar(80) DEFAULT NULL,
  `bill_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `currency_code` char(3) NOT NULL,
  `exchange_rate` decimal(20,10) NOT NULL,
  `tax_inclusive` tinyint unsigned NOT NULL DEFAULT '0',
  `subtotal` decimal(20,6) NOT NULL,
  `tax_total` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_amount` decimal(20,6) NOT NULL,
  `base_total_amount` decimal(20,6) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `memo` text,
  `create_on` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `posted_on` datetime DEFAULT NULL,
  `posted_by` bigint DEFAULT NULL,
  `approval_request_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`purchase_bill_id`),
  UNIQUE KEY `uq_purchase_bill_1` (`tenant_id`,`bill_number`),
  KEY `tenant_id_supplier_id_bill_date` (`tenant_id`,`supplier_id`,`bill_date`),
  KEY `tenant_id_status_due_date` (`tenant_id`,`status`,`due_date`),
  KEY `idx_fin_purchase_bill_approval` (`tenant_id`,`approval_request_id`),
  KEY `idx_fin_purchase_bill_supplier_id` (`supplier_id`),
  KEY `idx_fin_purchase_bill_approval_request_id` (`approval_request_id`),
  KEY `idx_fin_purchase_bill_created_by` (`created_by`),
  KEY `idx_fin_purchase_bill_posted_by` (`posted_by`),
  CONSTRAINT `fk_fin_purchase_bill_approval_request_id` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_purchase_bill_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_purchase_bill_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_purchase_bill_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `fin_supplier` (`supplier_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_purchase_bill_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_purchase_bill_inbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_purchase_bill_inbox` (
  `purchase_bill_inbox_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `created_by` bigint NOT NULL,
  `bill_number` varchar(48) NOT NULL,
  `active_bill_number` varchar(48) GENERATED ALWAYS AS ((case when (`status` <> _utf8mb4'cancelled') then `bill_number` else NULL end)) STORED,
  `bill_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `supplier_name` varchar(160) NOT NULL,
  `supplier_code` varchar(32) DEFAULT NULL,
  `amount_before_tax` decimal(20,6) NOT NULL,
  `tax_percent` decimal(9,6) NOT NULL DEFAULT '0.000000',
  `tax_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_amount` decimal(20,6) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'received',
  `error_message` text,
  `purchase_bill_id` bigint unsigned DEFAULT NULL,
  `payload_json` longtext,
  `received_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`purchase_bill_inbox_id`),
  UNIQUE KEY `uk_purchase_bill_inbox_active_number` (`tenant_id`,`active_bill_number`),
  KEY `idx_purchase_bill_inbox_number` (`tenant_id`,`bill_number`,`received_at`),
  KEY `idx_purchase_bill_inbox_status` (`tenant_id`,`status`,`received_at`),
  KEY `idx_purchase_bill_inbox_party` (`tenant_id`,`supplier_name`),
  KEY `fk_purchase_bill_inbox_created_by` (`created_by`),
  KEY `fk_purchase_bill_inbox_bill_id` (`purchase_bill_id`),
  CONSTRAINT `fk_purchase_bill_inbox_bill_id` FOREIGN KEY (`purchase_bill_id`) REFERENCES `fin_purchase_bill` (`purchase_bill_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_bill_inbox_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_bill_inbox_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_purchase_bill_line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_purchase_bill_line` (
  `purchase_bill_line_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `purchase_bill_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `tax_id` bigint unsigned DEFAULT NULL,
  `description` varchar(240) NOT NULL,
  `quantity` decimal(20,6) NOT NULL DEFAULT '1.000000',
  `unit_price` decimal(20,6) NOT NULL,
  `tax_rate_snapshot` decimal(9,6) NOT NULL DEFAULT '0.000000',
  `line_subtotal` decimal(20,6) NOT NULL,
  `tax_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `line_total` decimal(20,6) NOT NULL,
  `base_line_total` decimal(20,6) NOT NULL,
  PRIMARY KEY (`purchase_bill_line_id`),
  KEY `tenant_id_purchase_bill_id` (`tenant_id`,`purchase_bill_id`),
  KEY `tenant_id_account_id` (`tenant_id`,`account_id`),
  KEY `idx_fin_purchase_bill_line_purchase_bill_id` (`purchase_bill_id`),
  KEY `idx_fin_purchase_bill_line_account_id` (`account_id`),
  KEY `idx_fin_purchase_bill_line_department_id` (`department_id`),
  KEY `idx_fin_purchase_bill_line_tax_id` (`tax_id`),
  CONSTRAINT `fk_fin_purchase_bill_line_account_id` FOREIGN KEY (`account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_purchase_bill_line_department_id` FOREIGN KEY (`department_id`) REFERENCES `fin_department` (`department_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_purchase_bill_line_purchase_bill_id` FOREIGN KEY (`purchase_bill_id`) REFERENCES `fin_purchase_bill` (`purchase_bill_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_purchase_bill_line_tax_id` FOREIGN KEY (`tax_id`) REFERENCES `fin_tax` (`tax_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_purchase_bill_line_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_sales_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_sales_invoice` (
  `sales_invoice_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `invoice_number` varchar(48) NOT NULL,
  `integration_number` varchar(48) DEFAULT NULL,
  `integration_party_name` varchar(160) DEFAULT NULL,
  `integration_party_code` varchar(32) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `currency_code` char(3) NOT NULL,
  `exchange_rate` decimal(20,10) NOT NULL,
  `tax_inclusive` tinyint unsigned NOT NULL DEFAULT '0',
  `subtotal` decimal(20,6) NOT NULL,
  `tax_total` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_amount` decimal(20,6) NOT NULL,
  `base_total_amount` decimal(20,6) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `memo` text,
  `create_on` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `posted_on` datetime DEFAULT NULL,
  `posted_by` bigint DEFAULT NULL,
  `approval_request_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`sales_invoice_id`),
  UNIQUE KEY `uq_sales_invoice_1` (`tenant_id`,`invoice_number`),
  KEY `tenant_id_customer_id_invoice_date` (`tenant_id`,`customer_id`,`invoice_date`),
  KEY `tenant_id_status_due_date` (`tenant_id`,`status`,`due_date`),
  KEY `idx_fin_sales_invoice_approval` (`tenant_id`,`approval_request_id`),
  KEY `idx_fin_sales_invoice_customer_id` (`customer_id`),
  KEY `idx_fin_sales_invoice_approval_request_id` (`approval_request_id`),
  KEY `idx_fin_sales_invoice_created_by` (`created_by`),
  KEY `idx_fin_sales_invoice_posted_by` (`posted_by`),
  CONSTRAINT `fk_fin_sales_invoice_approval_request_id` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_sales_invoice_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_sales_invoice_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `fin_customer` (`customer_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_sales_invoice_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_sales_invoice_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_sales_invoice_inbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_sales_invoice_inbox` (
  `sales_invoice_inbox_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `created_by` bigint NOT NULL,
  `invoice_number` varchar(48) NOT NULL,
  `active_invoice_number` varchar(48) GENERATED ALWAYS AS ((case when (`status` <> _utf8mb4'cancelled') then `invoice_number` else NULL end)) STORED,
  `invoice_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `customer_name` varchar(160) NOT NULL,
  `customer_code` varchar(32) DEFAULT NULL,
  `amount_before_tax` decimal(20,6) NOT NULL,
  `tax_percent` decimal(9,6) NOT NULL DEFAULT '0.000000',
  `tax_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_amount` decimal(20,6) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'received',
  `error_message` text,
  `sales_invoice_id` bigint unsigned DEFAULT NULL,
  `payload_json` longtext,
  `received_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sales_invoice_inbox_id`),
  UNIQUE KEY `uk_sales_invoice_inbox_active_number` (`tenant_id`,`active_invoice_number`),
  KEY `idx_sales_invoice_inbox_number` (`tenant_id`,`invoice_number`,`received_at`),
  KEY `idx_sales_invoice_inbox_status` (`tenant_id`,`status`,`received_at`),
  KEY `idx_sales_invoice_inbox_party` (`tenant_id`,`customer_name`),
  KEY `fk_sales_invoice_inbox_created_by` (`created_by`),
  KEY `fk_sales_invoice_inbox_invoice_id` (`sales_invoice_id`),
  CONSTRAINT `fk_sales_invoice_inbox_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_invoice_inbox_invoice_id` FOREIGN KEY (`sales_invoice_id`) REFERENCES `fin_sales_invoice` (`sales_invoice_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_invoice_inbox_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_sales_invoice_line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_sales_invoice_line` (
  `sales_invoice_line_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `sales_invoice_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `tax_id` bigint unsigned DEFAULT NULL,
  `description` varchar(240) NOT NULL,
  `quantity` decimal(20,6) NOT NULL DEFAULT '1.000000',
  `unit_price` decimal(20,6) NOT NULL,
  `tax_rate_snapshot` decimal(9,6) NOT NULL DEFAULT '0.000000',
  `line_subtotal` decimal(20,6) NOT NULL,
  `tax_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `line_total` decimal(20,6) NOT NULL,
  `base_line_total` decimal(20,6) NOT NULL,
  PRIMARY KEY (`sales_invoice_line_id`),
  KEY `tenant_id_sales_invoice_id` (`tenant_id`,`sales_invoice_id`),
  KEY `tenant_id_account_id` (`tenant_id`,`account_id`),
  KEY `idx_fin_sales_invoice_line_sales_invoice_id` (`sales_invoice_id`),
  KEY `idx_fin_sales_invoice_line_account_id` (`account_id`),
  KEY `idx_fin_sales_invoice_line_department_id` (`department_id`),
  KEY `idx_fin_sales_invoice_line_tax_id` (`tax_id`),
  CONSTRAINT `fk_fin_sales_invoice_line_account_id` FOREIGN KEY (`account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_sales_invoice_line_department_id` FOREIGN KEY (`department_id`) REFERENCES `fin_department` (`department_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_sales_invoice_line_sales_invoice_id` FOREIGN KEY (`sales_invoice_id`) REFERENCES `fin_sales_invoice` (`sales_invoice_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_sales_invoice_line_tax_id` FOREIGN KEY (`tax_id`) REFERENCES `fin_tax` (`tax_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_sales_invoice_line_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_settings` (
  `settings_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `base_currency` char(3) NOT NULL,
  `tax_inclusive_default` tinyint unsigned NOT NULL DEFAULT '0',
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  `receivable_account_id` bigint unsigned DEFAULT NULL,
  `payable_account_id` bigint unsigned DEFAULT NULL,
  `fx_gain_account_id` bigint unsigned DEFAULT NULL,
  `fx_loss_account_id` bigint unsigned DEFAULT NULL,
  `rounding_tolerance` decimal(20,6) NOT NULL DEFAULT '1.000000',
  `rounding_gain_account_id` bigint unsigned DEFAULT NULL,
  `rounding_loss_account_id` bigint unsigned DEFAULT NULL,
  `default_output_tax_id` bigint unsigned DEFAULT NULL,
  `default_input_tax_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`settings_id`),
  UNIQUE KEY `uq_settings_1` (`tenant_id`),
  KEY `idx_fin_settings_receivable_account_id` (`receivable_account_id`),
  KEY `idx_fin_settings_payable_account_id` (`payable_account_id`),
  KEY `idx_fin_settings_fx_gain_account_id` (`fx_gain_account_id`),
  KEY `idx_fin_settings_fx_loss_account_id` (`fx_loss_account_id`),
  KEY `idx_fin_settings_rounding_gain_account_id` (`rounding_gain_account_id`),
  KEY `idx_fin_settings_rounding_loss_account_id` (`rounding_loss_account_id`),
  KEY `idx_fin_settings_default_output_tax_id` (`default_output_tax_id`),
  KEY `idx_fin_settings_default_input_tax_id` (`default_input_tax_id`),
  CONSTRAINT `fk_fin_settings_fx_gain_account_id` FOREIGN KEY (`fx_gain_account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_settings_fx_loss_account_id` FOREIGN KEY (`fx_loss_account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_settings_payable_account_id` FOREIGN KEY (`payable_account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_settings_receivable_account_id` FOREIGN KEY (`receivable_account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_settings_rounding_gain_account_id` FOREIGN KEY (`rounding_gain_account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_settings_rounding_loss_account_id` FOREIGN KEY (`rounding_loss_account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_settings_default_output_tax_id` FOREIGN KEY (`default_output_tax_id`) REFERENCES `fin_tax` (`tax_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_settings_default_input_tax_id` FOREIGN KEY (`default_input_tax_id`) REFERENCES `fin_tax` (`tax_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_settings_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_supplier` (
  `supplier_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `supplier_code` varchar(32) NOT NULL,
  `supplier_name` varchar(160) NOT NULL,
  `email_address` varchar(160) DEFAULT NULL,
  `phone_number` varchar(32) DEFAULT NULL,
  `tax_number` varchar(64) DEFAULT NULL,
  `billing_address` text,
  `active_flag` tinyint unsigned NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `update_on` datetime DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `uq_supplier_1` (`tenant_id`,`supplier_code`),
  KEY `tenant_id_supplier_name` (`tenant_id`,`supplier_name`),
  KEY `idx_fin_supplier_created_by` (`created_by`),
  KEY `idx_fin_supplier_updated_by` (`updated_by`),
  CONSTRAINT `fk_fin_supplier_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_supplier_payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_supplier_payment` (
  `supplier_payment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `cash_account_id` bigint unsigned NOT NULL,
  `payment_number` varchar(48) NOT NULL,
  `payment_date` date NOT NULL,
  `currency_code` char(3) NOT NULL,
  `exchange_rate` decimal(20,10) NOT NULL,
  `amount` decimal(20,6) NOT NULL,
  `base_amount` decimal(20,6) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `reference` varchar(80) DEFAULT NULL,
  `create_on` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `posted_on` datetime DEFAULT NULL,
  `posted_by` bigint DEFAULT NULL,
  `approval_request_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`supplier_payment_id`),
  UNIQUE KEY `uq_supplier_payment_1` (`tenant_id`,`payment_number`),
  KEY `tenant_id_supplier_id_payment_date` (`tenant_id`,`supplier_id`,`payment_date`),
  KEY `tenant_id_cash_account_id_payment_date` (`tenant_id`,`cash_account_id`,`payment_date`),
  KEY `idx_fin_supplier_payment_approval` (`tenant_id`,`approval_request_id`),
  KEY `idx_fin_supplier_payment_supplier_id` (`supplier_id`),
  KEY `idx_fin_supplier_payment_cash_account_id` (`cash_account_id`),
  KEY `idx_fin_supplier_payment_approval_request_id` (`approval_request_id`),
  KEY `idx_fin_supplier_payment_created_by` (`created_by`),
  KEY `idx_fin_supplier_payment_posted_by` (`posted_by`),
  CONSTRAINT `fk_fin_supplier_payment_approval_request_id` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_payment_cash_account_id` FOREIGN KEY (`cash_account_id`) REFERENCES `fin_cash_account` (`cash_account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_payment_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_payment_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_payment_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `fin_supplier` (`supplier_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_payment_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_supplier_payment_allocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_supplier_payment_allocation` (
  `supplier_payment_allocation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `supplier_payment_id` bigint unsigned NOT NULL,
  `purchase_bill_id` bigint unsigned NOT NULL,
  `amount` decimal(20,6) NOT NULL,
  `base_amount` decimal(20,6) NOT NULL,
  PRIMARY KEY (`supplier_payment_allocation_id`),
  UNIQUE KEY `uq_supplier_payment_allocation_1` (`tenant_id`,`supplier_payment_id`,`purchase_bill_id`),
  KEY `tenant_id_purchase_bill_id` (`tenant_id`,`purchase_bill_id`),
  KEY `idx_fin_supplier_payment_allocation_supplier_payment_id` (`supplier_payment_id`),
  KEY `idx_fin_supplier_payment_allocation_purchase_bill_id` (`purchase_bill_id`),
  CONSTRAINT `fk_fin_supplier_payment_allocation_purchase_bill_id` FOREIGN KEY (`purchase_bill_id`) REFERENCES `fin_purchase_bill` (`purchase_bill_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_payment_allocation_supplier_payment_id` FOREIGN KEY (`supplier_payment_id`) REFERENCES `fin_supplier_payment` (`supplier_payment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_supplier_payment_allocation_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_tax`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_tax` (
  `tax_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `tax_code` varchar(32) NOT NULL,
  `tax_name` varchar(100) NOT NULL,
  `rate_percent` decimal(9,6) NOT NULL,
  `active_flag` tinyint unsigned NOT NULL DEFAULT '1',
  `create_on` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`tax_id`),
  UNIQUE KEY `uq_tax_1` (`tenant_id`,`tax_code`),
  KEY `tenant_id_active_flag` (`tenant_id`,`active_flag`),
  KEY `idx_fin_tax_account_id` (`account_id`),
  KEY `idx_fin_tax_created_by` (`created_by`),
  CONSTRAINT `fk_fin_tax_account_id` FOREIGN KEY (`account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_tax_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_tax_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_transaction` (
  `transaction_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `cash_account_id` bigint unsigned NOT NULL,
  `transaction_number` varchar(48) NOT NULL,
  `transaction_type` varchar(8) NOT NULL,
  `transaction_on` date NOT NULL,
  `currency_code` char(3) NOT NULL,
  `exchange_rate` decimal(20,10) NOT NULL,
  `subtotal` decimal(20,6) NOT NULL,
  `tax_total` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `total_amount` decimal(20,6) NOT NULL,
  `base_total_amount` decimal(20,6) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `approval_request_id` bigint unsigned DEFAULT NULL,
  `description` text,
  `create_on` datetime NOT NULL,
  `created_by` bigint NOT NULL,
  `posted_on` datetime DEFAULT NULL,
  `posted_by` bigint DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `uq_transaction_1` (`tenant_id`,`transaction_number`),
  KEY `tenant_id_transaction_on_status` (`tenant_id`,`transaction_on`,`status`),
  KEY `tenant_id_cash_account_id_transaction_on` (`tenant_id`,`cash_account_id`,`transaction_on`),
  KEY `idx_fin_transaction_approval` (`tenant_id`,`approval_request_id`),
  KEY `idx_fin_transaction_cash_account_id` (`cash_account_id`),
  KEY `idx_fin_transaction_approval_request_id` (`approval_request_id`),
  KEY `idx_fin_transaction_created_by` (`created_by`),
  KEY `idx_fin_transaction_posted_by` (`posted_by`),
  CONSTRAINT `fk_fin_transaction_approval_request_id` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_cash_account_id` FOREIGN KEY (`cash_account_id`) REFERENCES `fin_cash_account` (`cash_account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fin_transaction_line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fin_transaction_line` (
  `transaction_line_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `transaction_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `tax_id` bigint unsigned DEFAULT NULL,
  `description` varchar(240) NOT NULL,
  `quantity` decimal(20,6) NOT NULL DEFAULT '1.000000',
  `unit_price` decimal(20,6) NOT NULL,
  `line_subtotal` decimal(20,6) NOT NULL,
  `tax_rate_snapshot` decimal(9,6) NOT NULL DEFAULT '0.000000',
  `tax_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `line_total` decimal(20,6) NOT NULL,
  `base_line_total` decimal(20,6) NOT NULL,
  `base_tax_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
  PRIMARY KEY (`transaction_line_id`),
  KEY `tenant_id_transaction_id` (`tenant_id`,`transaction_id`),
  KEY `tenant_id_account_id` (`tenant_id`,`account_id`),
  KEY `tenant_id_category_id` (`tenant_id`,`category_id`),
  KEY `idx_fin_transaction_line_transaction_id` (`transaction_id`),
  KEY `idx_fin_transaction_line_category_id` (`category_id`),
  KEY `idx_fin_transaction_line_account_id` (`account_id`),
  KEY `idx_fin_transaction_line_department_id` (`department_id`),
  KEY `idx_fin_transaction_line_tax_id` (`tax_id`),
  CONSTRAINT `fk_fin_transaction_line_account_id` FOREIGN KEY (`account_id`) REFERENCES `fin_account` (`account_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_line_category_id` FOREIGN KEY (`category_id`) REFERENCES `fin_category` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_line_department_id` FOREIGN KEY (`department_id`) REFERENCES `fin_department` (`department_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_line_tax_id` FOREIGN KEY (`tax_id`) REFERENCES `fin_tax` (`tax_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_line_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fin_transaction_line_transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `fin_transaction` (`transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_employee` (
  `employee_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `employee_code` varchar(32) NOT NULL,
  `identity_id` varchar(32) NOT NULL,
  `employee_name` varchar(64) NOT NULL,
  `address` varchar(256) DEFAULT NULL,
  `phone_number` varchar(16) DEFAULT NULL,
  `email_address` varchar(64) NOT NULL,
  `gender` varchar(32) NOT NULL,
  `image` varchar(128) DEFAULT NULL COMMENT 'Foto Profil',
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  `birth_date` date NOT NULL COMMENT 'Tanggal Lahir Karyawan',
  `birth_place` varchar(64) NOT NULL COMMENT 'Tempat Lahir Karyawan',
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `uk_hr_employee` (`tenant_id`,`employee_code`),
  KEY `fk_hr_employee_gender_idx` (`gender`),
  CONSTRAINT `fk_hr_employee_gender` FOREIGN KEY (`gender`) REFERENCES `sys_parameter_option` (`option_code`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hr_employee_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_employee_hierarchy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_employee_hierarchy` (
  `hierarchy_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `employee_id` bigint NOT NULL,
  `manager_employee_id` bigint NOT NULL,
  `active_flag` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint NOT NULL,
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`hierarchy_id`),
  KEY `tenant_id_employee_id` (`tenant_id`,`employee_id`),
  KEY `tenant_id_manager_employee_id` (`tenant_id`,`manager_employee_id`),
  KEY `idx_hr_employee_hierarchy_employee_id` (`employee_id`),
  KEY `idx_hr_employee_hierarchy_manager_employee_id` (`manager_employee_id`),
  KEY `idx_hr_employee_hierarchy_created_by` (`created_by`),
  CONSTRAINT `fk_hr_employee_hierarchy_created_by` FOREIGN KEY (`created_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_hr_employee_hierarchy_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hr_employee_hierarchy_manager_employee_id` FOREIGN KEY (`manager_employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_hr_employee_hierarchy_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_org`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_org` (
  `org_id` bigint NOT NULL AUTO_INCREMENT,
  `parent_org` bigint DEFAULT NULL,
  `tenant_id` bigint NOT NULL,
  `org_code` varchar(32) NOT NULL,
  `org_name` varchar(64) NOT NULL,
  `head_employee_id` bigint DEFAULT NULL,
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`org_id`),
  UNIQUE KEY `uk_hr_org` (`tenant_id`,`org_code`),
  KEY `fk_hr_org_head_employee_id_idx` (`head_employee_id`),
  KEY `fk_hr_org_parent_org_idx` (`parent_org`),
  CONSTRAINT `fk_hr_org_head_employee_id` FOREIGN KEY (`head_employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hr_org_parent_org` FOREIGN KEY (`parent_org`) REFERENCES `hr_org` (`org_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hr_org_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_org_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_org_member` (
  `org_employee_id` bigint NOT NULL AUTO_INCREMENT,
  `org_id` bigint NOT NULL,
  `employee_id` bigint NOT NULL,
  `job_title` varchar(64) DEFAULT NULL,
  `line` int NOT NULL,
  PRIMARY KEY (`org_employee_id`),
  UNIQUE KEY `uk_hr_org_employee` (`org_id`,`employee_id`),
  KEY `fk_hr_org_employee_employee_id_idx` (`employee_id`),
  CONSTRAINT `fk_hr_org_employee_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hr_org_employee_org_id` FOREIGN KEY (`org_id`) REFERENCES `hr_org` (`org_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `version` bigint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_approval_flow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_approval_flow` (
  `flow_code` varchar(32) NOT NULL,
  `flow_name` varchar(120) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `active_flag` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned NOT NULL,
  `create_on` datetime NOT NULL,
  `update_on` datetime DEFAULT NULL,
  PRIMARY KEY (`flow_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_approval_flow_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_approval_flow_menu` (
  `flow_menu_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_code` varchar(32) NOT NULL,
  `menu_code` varchar(32) NOT NULL,
  PRIMARY KEY (`flow_menu_id`),
  UNIQUE KEY `uk_sys_approval_flow_menu` (`flow_code`,`menu_code`),
  KEY `menu_code` (`menu_code`),
  CONSTRAINT `fk_sys_approval_flow_menu_code` FOREIGN KEY (`flow_code`) REFERENCES `sys_approval_flow` (`flow_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_approval_flow_step`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_approval_flow_step` (
  `flow_step_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_code` varchar(32) NOT NULL,
  `step_number` int unsigned NOT NULL,
  `approver_user_id` bigint DEFAULT NULL,
  `create_on` datetime NOT NULL,
  `approver_type` varchar(16) DEFAULT 'user',
  `approver_employee_id` bigint DEFAULT NULL,
  `org_id` bigint DEFAULT NULL,
  PRIMARY KEY (`flow_step_id`),
  UNIQUE KEY `uk_sys_approval_flow_step` (`flow_code`,`step_number`),
  KEY `approver_user_id` (`approver_user_id`),
  KEY `idx_sys_approval_flow_step_approver_employee_id` (`approver_employee_id`),
  KEY `idx_sys_approval_flow_step_org_id` (`org_id`),
  CONSTRAINT `fk_sys_approval_flow_step_approver_employee_id` FOREIGN KEY (`approver_employee_id`) REFERENCES `hr_employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sys_approval_flow_step_approver_user_id` FOREIGN KEY (`approver_user_id`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sys_approval_flow_step_code` FOREIGN KEY (`flow_code`) REFERENCES `sys_approval_flow` (`flow_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sys_approval_flow_step_org_id` FOREIGN KEY (`org_id`) REFERENCES `hr_org` (`org_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_parameter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_parameter` (
  `parameter_code` varchar(32) NOT NULL,
  `parameter_name` varchar(64) NOT NULL,
  `description` varchar(512) DEFAULT NULL,
  `summary` text NOT NULL,
  PRIMARY KEY (`parameter_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_parameter_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_parameter_option` (
  `option_code` varchar(32) NOT NULL,
  `parameter_code` varchar(32) NOT NULL,
  `option_name` varchar(64) NOT NULL,
  `active_flag` tinyint NOT NULL DEFAULT '1',
  `line` int NOT NULL,
  PRIMARY KEY (`option_code`),
  KEY `fk_sys_parameter_option_parameter_code_idx` (`parameter_code`),
  CONSTRAINT `fk_sys_parameter_option_parameter_code` FOREIGN KEY (`parameter_code`) REFERENCES `sys_parameter` (`parameter_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_property`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_property` (
  `property_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `property_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`property_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_sequence` (
  `sequence_code` varchar(32) NOT NULL,
  `sequence_name` varchar(64) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `repeat_type` varchar(32) NOT NULL,
  `format` varchar(128) NOT NULL,
  PRIMARY KEY (`sequence_code`),
  KEY `fk_sys_sequence_repeat_type_idx` (`repeat_type`),
  CONSTRAINT `fk_sys_sequence_repeat_type` FOREIGN KEY (`repeat_type`) REFERENCES `sys_parameter_option` (`option_code`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_sequence_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_sequence_menu` (
  `sequence_menu_id` bigint NOT NULL AUTO_INCREMENT,
  `sequence_ccode` varchar(32) NOT NULL,
  `menu_code` varchar(32) NOT NULL,
  PRIMARY KEY (`sequence_menu_id`),
  UNIQUE KEY `uk_sys_sequence_menu` (`sequence_ccode`,`menu_code`),
  CONSTRAINT `fk_sys_sequence_menu_sequence_code` FOREIGN KEY (`sequence_ccode`) REFERENCES `sys_sequence` (`sequence_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_tenant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_tenant` (
  `tenant_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_code` varchar(32) NOT NULL,
  `tenant_name` varchar(64) NOT NULL,
  `address` varchar(256) DEFAULT NULL,
  `email_address` varchar(64) NOT NULL,
  `phone_number` varchar(16) NOT NULL,
  `due_flag` tinyint NOT NULL DEFAULT '0',
  `due_on` date DEFAULT NULL,
  PRIMARY KEY (`tenant_id`),
  UNIQUE KEY `tenant_code_UNIQUE` (`tenant_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_tenant_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_tenant_menu` (
  `tenant_menu_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint NOT NULL,
  `menu_code` varchar(32) NOT NULL,
  PRIMARY KEY (`tenant_menu_id`),
  UNIQUE KEY `uk_sys_tenant_menu` (`tenant_id`,`menu_code`),
  CONSTRAINT `fk_sys_tenant_menu_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `sys_tenant` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_tenant_menu_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_tenant_menu_access` (
  `tenant_menu_access_id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_menu_id` bigint NOT NULL,
  `access_code` varchar(64) NOT NULL,
  PRIMARY KEY (`tenant_menu_access_id`),
  UNIQUE KEY `uk_sys_tenant_menu_access` (`tenant_menu_id`,`access_code`),
  CONSTRAINT `fk_sys_tenant_menu_access_tenant_menu_id` FOREIGN KEY (`tenant_menu_id`) REFERENCES `sys_tenant_menu` (`tenant_menu_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=480 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_update`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_update` (
  `update_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(32) NOT NULL,
  `previous_version` varchar(32) NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `package_url` varchar(512) NOT NULL,
  `checksum` varchar(64) NOT NULL,
  `changelog` text,
  `log` longtext,
  `started_on` datetime DEFAULT NULL,
  `finished_on` datetime DEFAULT NULL,
  `created_by` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`update_id`),
  KEY `status` (`status`),
  KEY `version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sys_update_migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_update_migration` (
  `update_migration_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `update_id` bigint unsigned NOT NULL,
  `migration_name` varchar(191) NOT NULL,
  `checksum` varchar(64) NOT NULL,
  `applied_on` datetime NOT NULL,
  PRIMARY KEY (`update_migration_id`),
  UNIQUE KEY `uk_sys_update_migration` (`update_id`,`migration_name`),
  CONSTRAINT `fk_sys_update_migration_update_id` FOREIGN KEY (`update_id`) REFERENCES `sys_update` (`update_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

CREATE TABLE IF NOT EXISTS `fin_cancellation` (
    `cancellation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint NOT NULL,
    `source_type` varchar(32) NOT NULL,
    `source_id` bigint unsigned NOT NULL,
    `menu_code` varchar(32) NOT NULL,
    `approval_request_id` bigint unsigned DEFAULT NULL,
    `previous_status` varchar(16) NOT NULL,
    `status` varchar(16) NOT NULL DEFAULT 'pending',
    `reason` varchar(1000) NOT NULL,
    `requested_by` bigint NOT NULL,
    `requested_on` datetime NOT NULL,
    `decided_on` datetime DEFAULT NULL,
    `reversal_journal_id` bigint unsigned DEFAULT NULL,
    PRIMARY KEY (`cancellation_id`),
    KEY `idx_fin_cancellation_source` (`tenant_id`, `source_type`, `source_id`, `status`),
    KEY `idx_fin_cancellation_approval` (`approval_request_id`),
    KEY `idx_fin_cancellation_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_fin_cancellation_approval` FOREIGN KEY (`approval_request_id`) REFERENCES `app_approval_request` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_fin_cancellation_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `app_user` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
