/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.1.2-MariaDB, for osx10.19 (x86_64)
--
-- Host: localhost    Database: healthnexus
-- ------------------------------------------------------
-- Server version	12.1.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_branch_id_foreign` (`branch_id`),
  KEY `activity_logs_company_id_action_index` (`company_id`,`action`),
  KEY `activity_logs_created_at_index` (`created_at`),
  KEY `activity_logs_request_id_index` (`request_id`),
  CONSTRAINT `activity_logs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activity_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `activity_logs` VALUES
(1,1,1,1,'PATIENT_LIST_VIEWED',NULL,NULL,'Viewed patient list','[]','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','20260924-004910--g7ctVrKj','2026-09-23 18:49:10','2026-09-23 18:49:10'),
(2,1,1,1,'PATIENT_VIEWED','App\\Models\\Patient',1,'Viewed patient record','[]','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','20260924-004915--SZAVWJUb','2026-09-23 18:49:15','2026-09-23 18:49:15'),
(3,1,1,2,'PATIENT_LIST_VIEWED',NULL,NULL,'Viewed patient list','[]','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','20260924-042731--X1U9IDDd','2026-09-23 22:27:31','2026-09-23 22:27:31'),
(4,1,1,2,'PATIENT_VIEWED','App\\Models\\Patient',1,'Viewed patient record','[]','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','20260924-042734--S5FZgbjL','2026-09-23 22:27:34','2026-09-23 22:27:34'),
(5,1,1,2,'DASHBOARD_VIEWED',NULL,NULL,'Viewed dashboard','[]','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','20260924-042759--y71mhCqf','2026-09-23 22:27:59','2026-09-23 22:27:59'),
(6,1,1,2,'DASHBOARD_VIEWED',NULL,NULL,'Viewed dashboard','[]','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','20260924-042845--lhrSZvPh','2026-09-23 22:28:45','2026-09-23 22:28:45'),
(7,1,1,2,'DASHBOARD_VIEWED',NULL,NULL,'Viewed dashboard','[]','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','20260924-042951--6LKJ6DZJ','2026-09-23 22:29:51','2026-09-23 22:29:51');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_documents`
--

DROP TABLE IF EXISTS `appointment_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned NOT NULL,
  `file_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(255) NOT NULL DEFAULT 'supporting_document',
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_documents_appointment_id_foreign` (`appointment_id`),
  KEY `appointment_documents_file_id_foreign` (`file_id`),
  KEY `appointment_documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `appointment_document_appt_idx` (`company_id`,`appointment_id`),
  CONSTRAINT `appointment_documents_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_documents_file_id_foreign` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_documents`
--

LOCK TABLES `appointment_documents` WRITE;
/*!40000 ALTER TABLE `appointment_documents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `appointment_documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_notes`
--

DROP TABLE IF EXISTS `appointment_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `note` text NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_notes_appointment_id_foreign` (`appointment_id`),
  KEY `appointment_notes_created_by_foreign` (`created_by`),
  KEY `appointment_notes_company_id_appointment_id_index` (`company_id`,`appointment_id`),
  CONSTRAINT `appointment_notes_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_notes`
--

LOCK TABLES `appointment_notes` WRITE;
/*!40000 ALTER TABLE `appointment_notes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `appointment_notes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_number_counters`
--

DROP TABLE IF EXISTS `appointment_number_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_number_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `counter_type` enum('appointment','token') NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_number_counter_unique` (`company_id`,`branch_id`,`counter_type`),
  KEY `appointment_number_counters_branch_id_foreign` (`branch_id`),
  CONSTRAINT `appointment_number_counters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_number_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_number_counters`
--

LOCK TABLES `appointment_number_counters` WRITE;
/*!40000 ALTER TABLE `appointment_number_counters` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `appointment_number_counters` VALUES
(1,1,NULL,'appointment',1,'2026-09-23 22:20:29','2026-09-23 22:20:29'),
(2,1,2,'token',1,'2026-09-23 22:20:29','2026-09-23 22:20:29');
/*!40000 ALTER TABLE `appointment_number_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_reminder_rules`
--

DROP TABLE IF EXISTS `appointment_reminder_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_reminder_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `provider_id` bigint(20) unsigned DEFAULT NULL,
  `appointment_type_id` bigint(20) unsigned DEFAULT NULL,
  `channel` enum('sms','email','whatsapp','push') NOT NULL,
  `offset_minutes` int(10) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_reminder_rules_branch_id_foreign` (`branch_id`),
  KEY `appointment_reminder_rules_department_id_foreign` (`department_id`),
  KEY `appointment_reminder_rules_provider_id_foreign` (`provider_id`),
  KEY `appointment_reminder_rules_appointment_type_id_foreign` (`appointment_type_id`),
  KEY `appointment_reminder_rule_active_idx` (`company_id`,`is_active`),
  CONSTRAINT `appointment_reminder_rules_appointment_type_id_foreign` FOREIGN KEY (`appointment_type_id`) REFERENCES `appointment_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointment_reminder_rules_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_reminder_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_reminder_rules_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointment_reminder_rules_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_reminder_rules`
--

LOCK TABLES `appointment_reminder_rules` WRITE;
/*!40000 ALTER TABLE `appointment_reminder_rules` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `appointment_reminder_rules` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_reminders`
--

DROP TABLE IF EXISTS `appointment_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned NOT NULL,
  `rule_id` bigint(20) unsigned DEFAULT NULL,
  `channel` enum('sms','email','whatsapp','push') NOT NULL,
  `scheduled_for` timestamp NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `failure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_reminder_unique` (`appointment_id`,`rule_id`),
  KEY `appointment_reminders_company_id_foreign` (`company_id`),
  KEY `appointment_reminders_rule_id_foreign` (`rule_id`),
  KEY `appointment_reminder_due_idx` (`status`,`scheduled_for`),
  CONSTRAINT `appointment_reminders_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_reminders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_reminders_rule_id_foreign` FOREIGN KEY (`rule_id`) REFERENCES `appointment_reminder_rules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_reminders`
--

LOCK TABLES `appointment_reminders` WRITE;
/*!40000 ALTER TABLE `appointment_reminders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `appointment_reminders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_rooms`
--

DROP TABLE IF EXISTS `appointment_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) NOT NULL DEFAULT 'consultation',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_rooms_branch_id_foreign` (`branch_id`),
  KEY `appointment_rooms_department_id_foreign` (`department_id`),
  KEY `appointment_room_branch_active_idx` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `appointment_rooms_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_rooms_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_rooms_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_rooms`
--

LOCK TABLES `appointment_rooms` WRITE;
/*!40000 ALTER TABLE `appointment_rooms` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `appointment_rooms` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_slots`
--

DROP TABLE IF EXISTS `appointment_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `schedule_id` bigint(20) unsigned NOT NULL,
  `slot_datetime` datetime NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 15,
  `max_capacity` int(11) NOT NULL DEFAULT 1,
  `booked_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('available','booked','blocked','cancelled') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slot_datetime_unique` (`company_id`,`doctor_id`,`slot_datetime`),
  KEY `appointment_slots_branch_id_foreign` (`branch_id`),
  KEY `appointment_slots_doctor_id_foreign` (`doctor_id`),
  KEY `appointment_slots_schedule_id_foreign` (`schedule_id`),
  CONSTRAINT `appointment_slots_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_slots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_slots_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_slots_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `doctor_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_slots`
--

LOCK TABLES `appointment_slots` WRITE;
/*!40000 ALTER TABLE `appointment_slots` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `appointment_slots` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_status_histories`
--

DROP TABLE IF EXISTS `appointment_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint(20) unsigned NOT NULL,
  `old_status` varchar(255) DEFAULT NULL,
  `new_status` varchar(255) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_status_histories_changed_by_foreign` (`changed_by`),
  KEY `appointment_status_histories_appointment_id_index` (`appointment_id`),
  CONSTRAINT `appointment_status_histories_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_status_histories`
--

LOCK TABLES `appointment_status_histories` WRITE;
/*!40000 ALTER TABLE `appointment_status_histories` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `appointment_status_histories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_tokens`
--

DROP TABLE IF EXISTS `appointment_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned NOT NULL,
  `token_number` varchar(255) NOT NULL,
  `counter` varchar(255) DEFAULT NULL,
  `status` enum('waiting','called','checked_in','in_progress','completed','skipped','cancelled') NOT NULL DEFAULT 'waiting',
  `priority` enum('emergency','priority','vip','regular','follow_up') NOT NULL DEFAULT 'regular',
  `generated_at` timestamp NULL DEFAULT NULL,
  `called_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `skipped_at` timestamp NULL DEFAULT NULL,
  `transferred_to_provider_id` bigint(20) unsigned DEFAULT NULL,
  `called_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_tokens_token_number_unique` (`token_number`),
  KEY `appointment_tokens_branch_id_foreign` (`branch_id`),
  KEY `appointment_tokens_appointment_id_foreign` (`appointment_id`),
  KEY `appointment_tokens_called_by_foreign` (`called_by`),
  KEY `appointment_tokens_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `appointment_tokens_company_id_token_number_index` (`company_id`,`token_number`),
  KEY `appointment_tokens_transferred_to_provider_id_foreign` (`transferred_to_provider_id`),
  CONSTRAINT `appointment_tokens_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_tokens_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_tokens_called_by_foreign` FOREIGN KEY (`called_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointment_tokens_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_tokens_transferred_to_provider_id_foreign` FOREIGN KEY (`transferred_to_provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_tokens`
--

LOCK TABLES `appointment_tokens` WRITE;
/*!40000 ALTER TABLE `appointment_tokens` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `appointment_tokens` VALUES
(1,1,2,1,'HN-CI-00000001',NULL,'waiting','regular','2026-09-23 22:20:29',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-23 22:20:29','2026-09-23 22:20:29',NULL);
/*!40000 ALTER TABLE `appointment_tokens` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointment_types`
--

DROP TABLE IF EXISTS `appointment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_follow_up_type` tinyint(1) NOT NULL DEFAULT 0,
  `is_telemedicine_type` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_type_company_code_unique` (`company_id`,`code`),
  CONSTRAINT `appointment_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_types`
--

LOCK TABLES `appointment_types` WRITE;
/*!40000 ALTER TABLE `appointment_types` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `appointment_types` VALUES
(1,NULL,'new_consultation','New Consultation',NULL,0,0,1,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,NULL,'follow_up','Follow-up',NULL,1,0,1,2,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,NULL,'review','Review',NULL,0,0,1,3,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,NULL,'second_opinion','Second Opinion',NULL,0,0,1,4,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,NULL,'procedure','Procedure',NULL,0,0,1,5,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(6,NULL,'health_checkup','Health Checkup',NULL,0,0,1,6,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(7,NULL,'referral','Referral',NULL,0,0,1,7,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(8,NULL,'telemedicine','Telemedicine',NULL,0,1,1,8,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(9,NULL,'vaccination','Vaccination',NULL,0,0,1,9,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(10,NULL,'diagnostic','Diagnostic',NULL,0,0,1,10,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(11,NULL,'pre_operative','Pre-operative',NULL,0,0,1,11,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(12,NULL,'post_operative','Post-operative',NULL,0,0,1,12,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(13,NULL,'corporate','Corporate',NULL,0,0,1,13,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(14,NULL,'package','Package',NULL,0,0,1,14,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(15,NULL,'other','Other',NULL,0,0,1,15,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `appointment_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `provider_id` bigint(20) unsigned DEFAULT NULL,
  `slot_id` bigint(20) unsigned DEFAULT NULL,
  `appointment_no` varchar(255) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `actual_datetime` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'scheduled',
  `appointment_type_id` bigint(20) unsigned DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'online',
  `reason` text DEFAULT NULL,
  `referral_source` varchar(255) DEFAULT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `is_walk_in` tinyint(1) NOT NULL DEFAULT 0,
  `is_follow_up` tinyint(1) NOT NULL DEFAULT 0,
  `is_telemedicine` tinyint(1) NOT NULL DEFAULT 0,
  `previous_appointment_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('scheduled','confirmed','checked_in','in_progress','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  `priority` enum('routine','urgent','stat') NOT NULL DEFAULT 'routine',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `booked_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `no_show_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `referral_organization` varchar(255) DEFAULT NULL,
  `referral_reference` varchar(255) DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned DEFAULT NULL,
  `checked_in_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointments_appointment_no_unique` (`appointment_no`),
  KEY `appointments_branch_id_foreign` (`branch_id`),
  KEY `appointments_patient_id_foreign` (`patient_id`),
  KEY `appointments_doctor_id_foreign` (`doctor_id`),
  KEY `appointments_slot_id_foreign` (`slot_id`),
  KEY `appointments_created_by_foreign` (`created_by`),
  KEY `appointments_company_id_branch_id_appointment_date_index` (`company_id`,`branch_id`,`appointment_date`),
  KEY `appointments_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `appointments_company_id_doctor_id_appointment_date_index` (`company_id`,`doctor_id`,`appointment_date`),
  KEY `appointments_provider_id_foreign` (`provider_id`),
  KEY `appointments_appointment_type_id_foreign` (`appointment_type_id`),
  KEY `appointments_room_id_foreign` (`room_id`),
  KEY `appointment_provider_datetime_idx` (`company_id`,`provider_id`,`appointment_date`,`appointment_time`),
  KEY `appointments_previous_appointment_id_foreign` (`previous_appointment_id`),
  KEY `appointments_confirmed_by_foreign` (`confirmed_by`),
  KEY `appointments_checked_in_by_foreign` (`checked_in_by`),
  KEY `appointments_cancelled_by_foreign` (`cancelled_by`),
  KEY `appointments_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `appointments_appointment_type_id_foreign` FOREIGN KEY (`appointment_type_id`) REFERENCES `appointment_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_checked_in_by_foreign` FOREIGN KEY (`checked_in_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_previous_appointment_id_foreign` FOREIGN KEY (`previous_appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `appointment_rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `appointment_slots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `appointments` VALUES
(1,1,2,1,2,NULL,NULL,'HN--APT-00000001','2026-09-24','18:00:00',NULL,NULL,NULL,'walk_in',NULL,NULL,NULL,'online',NULL,NULL,NULL,0,0,0,NULL,'scheduled','routine',1,'2026-09-23 22:20:29','2026-09-23 22:20:29',NULL,NULL,NULL,NULL,NULL,'2026-09-23 22:20:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(255) DEFAULT NULL,
  `model_type` varchar(255) DEFAULT NULL,
  `model_id` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `method` varchar(255) DEFAULT NULL,
  `request_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_branch_id_foreign` (`branch_id`),
  KEY `audit_logs_company_id_action_index` (`company_id`,`action`),
  KEY `audit_logs_user_id_action_index` (`user_id`,`action`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_request_id_index` (`request_id`),
  CONSTRAINT `audit_logs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `audit_logs` VALUES
(1,1,1,1,'CREATE','core','App\\Models\\Patient','1',NULL,'{\"company_id\":1,\"patient_type_id\":\"1\",\"gender_id\":\"1\",\"marital_status_id\":\"1\",\"national_identifier\":\"98789789896\",\"first_name\":\"Saidur\",\"middle_name\":null,\"last_name\":\"Rahman\",\"preferred_name\":null,\"date_of_birth\":\"1982-01-01T00:00:00.000000Z\",\"sex\":null,\"blood_group\":\"B+\",\"phone\":null,\"email\":null,\"address\":null,\"status\":\"active\",\"enterprise_patient_no\":\"HN--00000001\",\"registered_at\":\"2026-09-24T00:49:10.000000Z\",\"registered_by\":1,\"updated_at\":\"2026-09-24T00:49:10.000000Z\",\"created_at\":\"2026-09-24T00:49:10.000000Z\",\"id\":1}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','http://healthnexus.test/admin/patients','POST','20260924-004910--R7ecTIXD','2026-09-23 18:49:10','2026-09-23 18:49:10'),
(2,1,1,2,'CREATE','core','App\\Models\\Appointment','1',NULL,'{\"id\":1,\"company_id\":1,\"branch_id\":2,\"patient_id\":1,\"doctor_id\":2,\"provider_id\":null,\"slot_id\":null,\"appointment_no\":\"HN--APT-00000001\",\"appointment_date\":\"2026-09-24T00:00:00.000000Z\",\"appointment_time\":\"18:00\",\"actual_datetime\":null,\"started_at\":null,\"ended_at\":null,\"type\":\"walk_in\",\"appointment_type_id\":null,\"specialty_id\":null,\"room_id\":null,\"source\":\"online\",\"reason\":null,\"referral_source\":null,\"referred_by\":null,\"is_walk_in\":false,\"is_follow_up\":false,\"is_telemedicine\":false,\"previous_appointment_id\":null,\"status\":\"scheduled\",\"priority\":\"routine\",\"created_by\":1,\"created_at\":\"2026-09-24T04:20:29.000000Z\",\"booked_at\":\"2026-09-24T04:20:29.000000Z\",\"confirmed_at\":null,\"checked_in_at\":null,\"completed_at\":null,\"cancelled_at\":null,\"no_show_at\":null,\"updated_at\":\"2026-09-24T04:20:29.000000Z\",\"deleted_at\":null,\"referral_organization\":null,\"referral_reference\":null,\"confirmed_by\":null,\"checked_in_by\":null,\"cancelled_by\":null,\"cancellation_reason\":null,\"token\":{\"id\":1,\"company_id\":1,\"branch_id\":2,\"appointment_id\":1,\"token_number\":\"HN-CI-00000001\",\"counter\":null,\"status\":\"waiting\",\"priority\":\"regular\",\"generated_at\":\"2026-09-24T04:20:29.000000Z\",\"called_at\":null,\"started_at\":null,\"completed_at\":null,\"skipped_at\":null,\"transferred_to_provider_id\":null,\"called_by\":null,\"created_at\":\"2026-09-24T04:20:29.000000Z\",\"updated_at\":\"2026-09-24T04:20:29.000000Z\",\"deleted_at\":null},\"slot\":null,\"provider\":null,\"patient\":{\"id\":1,\"company_id\":1,\"patient_type_id\":1,\"country_id\":null,\"state_id\":null,\"enterprise_patient_no\":\"HN--00000001\",\"national_identifier\":\"98789789896\",\"first_name\":\"Saidur\",\"middle_name\":null,\"last_name\":\"Rahman\",\"preferred_name\":null,\"display_name\":\"Saidur  Rahman\",\"date_of_birth\":\"1982-01-01T00:00:00.000000Z\",\"dob_unknown\":false,\"estimated_age\":null,\"estimated_age_unit\":null,\"sex\":null,\"gender_id\":1,\"marital_status_id\":1,\"nationality_id\":null,\"blood_group\":\"B+\",\"rh_factor\":null,\"deceased_at\":null,\"is_temporary\":false,\"is_unknown\":false,\"registered_at\":\"2026-09-24T00:49:10.000000Z\",\"registered_by\":1,\"photo_file_id\":null,\"portal_enabled\":false,\"portal_user_id\":null,\"phone\":null,\"email\":null,\"address\":null,\"city\":null,\"state\":null,\"country\":null,\"postal_code\":null,\"emergency_contact\":null,\"notes\":null,\"status\":\"active\",\"merged_into_patient_id\":null,\"created_at\":\"2026-09-24T00:49:10.000000Z\",\"updated_at\":\"2026-09-24T00:49:10.000000Z\",\"deleted_at\":null}}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','http://healthnexus.test/admin/appointments','POST','20260924-042029--3YRxWiAs','2026-09-23 22:20:29','2026-09-23 22:20:29'),
(3,1,1,2,'CREATE','core','App\\Models\\VitalSign','1',NULL,'{\"appointment_id\":1,\"temperature\":\"36.00\",\"systolic\":null,\"diastolic\":null,\"pulse_rate\":89,\"respiratory_rate\":null,\"height\":\"172.00\",\"weight\":\"87.00\",\"oxygen_saturation\":\"96\",\"notes\":null,\"company_id\":1,\"branch_id\":2,\"encounter_id\":null,\"patient_id\":1,\"recorded_by\":1,\"recorded_at\":\"2026-09-24T04:21:21.000000Z\",\"updated_at\":\"2026-09-24T04:21:21.000000Z\",\"created_at\":\"2026-09-24T04:21:21.000000Z\",\"id\":1}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','http://healthnexus.test/admin/opd/vital-signs','POST','20260924-042121--iNXjqPC8','2026-09-23 22:21:21','2026-09-23 22:21:21'),
(4,1,1,2,'CREATE','core','App\\Models\\Prescription','1',NULL,'{\"company_id\":1,\"branch_id\":2,\"appointment_id\":1,\"encounter_id\":null,\"patient_id\":1,\"doctor_id\":2,\"prescription_no\":\"HN--PRX-00000001\",\"status\":\"draft\",\"clinical_notes\":\"This is clinical advice\",\"advice\":\"This general advice\",\"created_by\":1,\"prescribed_at\":\"2026-09-24T04:22:44.000000Z\",\"updated_at\":\"2026-09-24T04:22:44.000000Z\",\"created_at\":\"2026-09-24T04:22:44.000000Z\",\"id\":1}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','http://healthnexus.test/admin/opd/prescriptions','POST','20260924-042244--txXUYdER','2026-09-23 22:22:44','2026-09-23 22:22:44'),
(5,1,1,2,'CREATE','core','App\\Models\\InvestigationOrder','1',NULL,'{\"appointment_id\":1,\"patient_id\":1,\"test_name\":\"CBC\",\"category\":null,\"clinical_notes\":null,\"priority\":\"routine\",\"order_no\":\"LAB-CAUIOSXE\",\"company_id\":1,\"branch_id\":2,\"encounter_id\":null,\"doctor_id\":2,\"ordered_by\":1,\"ordered_at\":\"2026-09-24T04:23:20.000000Z\",\"updated_at\":\"2026-09-24T04:23:20.000000Z\",\"created_at\":\"2026-09-24T04:23:20.000000Z\",\"id\":1}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','http://healthnexus.test/admin/opd/investigation-orders','POST','20260924-042320--lQ6g2PZZ','2026-09-23 22:23:20','2026-09-23 22:23:20'),
(6,1,1,2,'CREATE','core','App\\Models\\VitalSign','2',NULL,'{\"appointment_id\":1,\"temperature\":null,\"systolic\":null,\"diastolic\":null,\"pulse_rate\":null,\"respiratory_rate\":null,\"height\":null,\"weight\":null,\"oxygen_saturation\":null,\"notes\":null,\"company_id\":1,\"branch_id\":2,\"encounter_id\":null,\"patient_id\":1,\"recorded_by\":1,\"recorded_at\":\"2026-09-24T04:24:03.000000Z\",\"updated_at\":\"2026-09-24T04:24:03.000000Z\",\"created_at\":\"2026-09-24T04:24:03.000000Z\",\"id\":2}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','http://healthnexus.test/admin/opd/vital-signs','POST','20260924-042403--H1Xz30Bm','2026-09-23 22:24:03','2026-09-23 22:24:03'),
(7,1,1,2,'CREATE','core','App\\Models\\VitalSign','3',NULL,'{\"appointment_id\":1,\"temperature\":null,\"systolic\":null,\"diastolic\":null,\"pulse_rate\":null,\"respiratory_rate\":null,\"height\":null,\"weight\":null,\"oxygen_saturation\":null,\"notes\":null,\"company_id\":1,\"branch_id\":2,\"encounter_id\":null,\"patient_id\":1,\"recorded_by\":1,\"recorded_at\":\"2026-09-24T04:24:10.000000Z\",\"updated_at\":\"2026-09-24T04:24:10.000000Z\",\"created_at\":\"2026-09-24T04:24:10.000000Z\",\"id\":3}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','http://healthnexus.test/admin/opd/vital-signs','POST','20260924-042410--sZ7XSMLh','2026-09-23 22:24:10','2026-09-23 22:24:10');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_adjustments`
--

DROP TABLE IF EXISTS `billing_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `adjustable_type` varchar(255) DEFAULT NULL,
  `adjustable_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'BDT',
  `original_value` decimal(18,2) NOT NULL,
  `new_value` decimal(18,2) NOT NULL,
  `difference` decimal(18,2) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `requested_by` bigint(20) unsigned NOT NULL,
  `requested_at` timestamp NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_adjustments_branch_id_foreign` (`branch_id`),
  KEY `billing_adjustments_invoice_id_foreign` (`invoice_id`),
  KEY `billing_adjustments_requested_by_foreign` (`requested_by`),
  KEY `billing_adjustments_approved_by_foreign` (`approved_by`),
  KEY `billing_adjustments_adjustable_type_adjustable_id_index` (`adjustable_type`,`adjustable_id`),
  KEY `billing_adjustments_company_id_branch_id_invoice_id_status_index` (`company_id`,`branch_id`,`invoice_id`,`status`),
  KEY `billing_adjustments_payment_id_status_index` (`payment_id`,`status`),
  KEY `billing_adjustments_approved_at_status_index` (`approved_at`,`status`),
  CONSTRAINT `billing_adjustments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_adjustments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_adjustments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_adjustments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_adjustments_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `billing_payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_adjustments_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_adjustments`
--

LOCK TABLES `billing_adjustments` WRITE;
/*!40000 ALTER TABLE `billing_adjustments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_adjustments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_advance_accounts`
--

DROP TABLE IF EXISTS `billing_advance_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_advance_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'BDT',
  `balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_advance_accounts_company_id_patient_id_unique` (`company_id`,`patient_id`),
  KEY `billing_advance_accounts_branch_id_foreign` (`branch_id`),
  KEY `billing_advance_accounts_patient_id_foreign` (`patient_id`),
  KEY `billing_advance_accounts_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  CONSTRAINT `billing_advance_accounts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_advance_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_advance_accounts_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_advance_accounts`
--

LOCK TABLES `billing_advance_accounts` WRITE;
/*!40000 ALTER TABLE `billing_advance_accounts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_advance_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_advance_transactions`
--

DROP TABLE IF EXISTS `billing_advance_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_advance_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `advance_account_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `balance_after` decimal(18,2) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `performed_by` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_advance_transactions_idempotency_unique` (`advance_account_id`,`idempotency_key`),
  KEY `billing_advance_transactions_payment_id_foreign` (`payment_id`),
  KEY `billing_advance_transactions_invoice_id_foreign` (`invoice_id`),
  KEY `billing_advance_transactions_performed_by_foreign` (`performed_by`),
  KEY `billing_advance_transactions_advance_account_id_created_at_index` (`advance_account_id`,`created_at`),
  KEY `billing_advance_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `billing_advance_transactions_advance_account_id_foreign` FOREIGN KEY (`advance_account_id`) REFERENCES `billing_advance_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_advance_transactions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_advance_transactions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `billing_payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_advance_transactions_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_advance_transactions`
--

LOCK TABLES `billing_advance_transactions` WRITE;
/*!40000 ALTER TABLE `billing_advance_transactions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_advance_transactions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_cashier_sessions`
--

DROP TABLE IF EXISTS `billing_cashier_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_cashier_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `counter_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `currency` varchar(3) NOT NULL DEFAULT 'BDT',
  `opening_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `expected_collections` decimal(18,2) NOT NULL DEFAULT 0.00,
  `expected_refunds` decimal(18,2) NOT NULL DEFAULT 0.00,
  `expected_closing` decimal(18,2) NOT NULL DEFAULT 0.00,
  `actual_closing` decimal(18,2) NOT NULL DEFAULT 0.00,
  `variance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `opened_at` timestamp NOT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closing_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_cashier_sessions_branch_id_foreign` (`branch_id`),
  KEY `billing_cashier_sessions_counter_id_foreign` (`counter_id`),
  KEY `billing_cashier_sessions_user_id_foreign` (`user_id`),
  KEY `billing_cashier_sessions_closed_by_foreign` (`closed_by`),
  KEY `billing_cashier_sessions_scope_idx` (`company_id`,`branch_id`,`user_id`,`status`),
  KEY `billing_cashier_sessions_opened_at_closed_at_index` (`opened_at`,`closed_at`),
  CONSTRAINT `billing_cashier_sessions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_cashier_sessions_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_cashier_sessions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_cashier_sessions_counter_id_foreign` FOREIGN KEY (`counter_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_cashier_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_cashier_sessions`
--

LOCK TABLES `billing_cashier_sessions` WRITE;
/*!40000 ALTER TABLE `billing_cashier_sessions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_cashier_sessions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_categories`
--

DROP TABLE IF EXISTS `billing_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_categories_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `billing_categories_branch_id_foreign` (`branch_id`),
  KEY `billing_categories_created_by_foreign` (`created_by`),
  KEY `billing_categories_updated_by_foreign` (`updated_by`),
  KEY `billing_categories_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `billing_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_categories_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_categories`
--

LOCK TABLES `billing_categories` WRITE;
/*!40000 ALTER TABLE `billing_categories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `billing_categories` VALUES
(1,1,NULL,'Consultation','CONSULT','Consultation',1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'Procedures','PROC','Procedures',1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'Diagnostics','DIAG','Diagnostics',1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,1,NULL,'Room & Nursing','ROOM','Room & Nursing',1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,1,NULL,'Pharmacy','PHARM','Dispensed medications',1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(6,1,NULL,'Inpatient','IPD','Admission and bed occupancy charges',1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `billing_categories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_charges`
--

DROP TABLE IF EXISTS `billing_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_charges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `billing_item_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `provider_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `currency` varchar(3) NOT NULL DEFAULT 'BDT',
  `unit_price` decimal(18,2) NOT NULL,
  `gross_amount` decimal(18,2) NOT NULL,
  `discount_type` varchar(255) NOT NULL DEFAULT 'none',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(18,2) NOT NULL,
  `charged_at` timestamp NULL DEFAULT NULL,
  `billed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_charges_company_id_idempotency_key_unique` (`company_id`,`idempotency_key`),
  KEY `billing_charges_branch_id_foreign` (`branch_id`),
  KEY `billing_charges_patient_id_foreign` (`patient_id`),
  KEY `billing_charges_encounter_id_foreign` (`encounter_id`),
  KEY `billing_charges_department_id_foreign` (`department_id`),
  KEY `billing_charges_provider_id_foreign` (`provider_id`),
  KEY `billing_charges_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `billing_charges_cancelled_by_foreign` (`cancelled_by`),
  KEY `billing_charges_created_by_foreign` (`created_by`),
  KEY `billing_charges_scope_idx` (`company_id`,`branch_id`,`patient_id`,`status`),
  KEY `billing_charges_billing_item_id_charged_at_index` (`billing_item_id`,`charged_at`),
  CONSTRAINT `billing_charges_billing_item_id_foreign` FOREIGN KEY (`billing_item_id`) REFERENCES `billing_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_charges_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_charges_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_charges_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_charges_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_charges_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_charges_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_charges_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_charges_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_charges`
--

LOCK TABLES `billing_charges` WRITE;
/*!40000 ALTER TABLE `billing_charges` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_charges` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_corporate_contracts`
--

DROP TABLE IF EXISTS `billing_corporate_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_corporate_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `corporate_id` bigint(20) unsigned NOT NULL,
  `price_list_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `discount_type` varchar(255) NOT NULL DEFAULT 'none',
  `discount_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credit_limit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `payment_terms_days` smallint(5) unsigned NOT NULL DEFAULT 30,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_corporate_contracts_branch_id_foreign` (`branch_id`),
  KEY `billing_corporate_contracts_corporate_id_foreign` (`corporate_id`),
  KEY `billing_corporate_contracts_price_list_id_foreign` (`price_list_id`),
  KEY `billing_corporate_contracts_scope_idx` (`company_id`,`branch_id`,`corporate_id`,`status`),
  KEY `billing_corporate_contracts_effective_from_effective_to_index` (`effective_from`,`effective_to`),
  CONSTRAINT `billing_corporate_contracts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_corporate_contracts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_corporate_contracts_corporate_id_foreign` FOREIGN KEY (`corporate_id`) REFERENCES `billing_corporates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_corporate_contracts_price_list_id_foreign` FOREIGN KEY (`price_list_id`) REFERENCES `billing_price_lists` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_corporate_contracts`
--

LOCK TABLES `billing_corporate_contracts` WRITE;
/*!40000 ALTER TABLE `billing_corporate_contracts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_corporate_contracts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_corporate_members`
--

DROP TABLE IF EXISTS `billing_corporate_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_corporate_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `corporate_id` bigint(20) unsigned NOT NULL,
  `corporate_contract_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `member_number` varchar(255) DEFAULT NULL,
  `employee_id` varchar(255) DEFAULT NULL,
  `relationship` varchar(255) NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_corporate_members_corporate_id_member_number_unique` (`corporate_id`,`member_number`),
  KEY `billing_corporate_members_branch_id_foreign` (`branch_id`),
  KEY `billing_corporate_members_corporate_contract_id_foreign` (`corporate_contract_id`),
  KEY `billing_corporate_members_patient_id_foreign` (`patient_id`),
  KEY `billing_corporate_members_scope_idx` (`company_id`,`branch_id`,`patient_id`,`is_active`),
  CONSTRAINT `billing_corporate_members_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_corporate_members_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_corporate_members_corporate_contract_id_foreign` FOREIGN KEY (`corporate_contract_id`) REFERENCES `billing_corporate_contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_corporate_members_corporate_id_foreign` FOREIGN KEY (`corporate_id`) REFERENCES `billing_corporates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_corporate_members_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_corporate_members`
--

LOCK TABLES `billing_corporate_members` WRITE;
/*!40000 ALTER TABLE `billing_corporate_members` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_corporate_members` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_corporates`
--

DROP TABLE IF EXISTS `billing_corporates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_corporates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `credit_limit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `payment_terms_days` smallint(5) unsigned NOT NULL DEFAULT 30,
  `billing_cycle` varchar(255) NOT NULL DEFAULT 'monthly',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_corporates_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `billing_corporates_branch_id_foreign` (`branch_id`),
  KEY `billing_corporates_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  CONSTRAINT `billing_corporates_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_corporates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_corporates`
--

LOCK TABLES `billing_corporates` WRITE;
/*!40000 ALTER TABLE `billing_corporates` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_corporates` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_counters`
--

DROP TABLE IF EXISTS `billing_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(3) NOT NULL,
  `prefix` varchar(16) NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_counters_scope_unique` (`company_id`,`branch_id`,`document_type`,`prefix`),
  KEY `billing_counters_branch_id_foreign` (`branch_id`),
  KEY `billing_counters_company_id_branch_id_document_type_index` (`company_id`,`branch_id`,`document_type`),
  CONSTRAINT `billing_counters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_counters`
--

LOCK TABLES `billing_counters` WRITE;
/*!40000 ALTER TABLE `billing_counters` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_insurance_policies`
--

DROP TABLE IF EXISTS `billing_insurance_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_insurance_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `insurance_provider_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `policy_number` varchar(255) NOT NULL,
  `member_number` varchar(255) DEFAULT NULL,
  `group_number` varchar(255) DEFAULT NULL,
  `authorization_reference` varchar(255) DEFAULT NULL,
  `coverage_limit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `copay_percentage` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_insurance_policies_provider_number_unique` (`insurance_provider_id`,`policy_number`),
  KEY `billing_insurance_policies_branch_id_foreign` (`branch_id`),
  KEY `billing_insurance_policies_patient_id_foreign` (`patient_id`),
  KEY `billing_insurance_policies_scope_idx` (`company_id`,`branch_id`,`patient_id`,`status`),
  KEY `billing_insurance_policies_effective_from_effective_to_index` (`effective_from`,`effective_to`),
  CONSTRAINT `billing_insurance_policies_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_insurance_policies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_insurance_policies_insurance_provider_id_foreign` FOREIGN KEY (`insurance_provider_id`) REFERENCES `billing_insurance_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_insurance_policies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_insurance_policies`
--

LOCK TABLES `billing_insurance_policies` WRITE;
/*!40000 ALTER TABLE `billing_insurance_policies` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_insurance_policies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_insurance_providers`
--

DROP TABLE IF EXISTS `billing_insurance_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_insurance_providers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_insurance_providers_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `billing_insurance_providers_branch_id_foreign` (`branch_id`),
  KEY `billing_insurance_providers_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  CONSTRAINT `billing_insurance_providers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_insurance_providers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_insurance_providers`
--

LOCK TABLES `billing_insurance_providers` WRITE;
/*!40000 ALTER TABLE `billing_insurance_providers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_insurance_providers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_invoice_items`
--

DROP TABLE IF EXISTS `billing_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `charge_id` bigint(20) unsigned DEFAULT NULL,
  `billing_item_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(18,2) NOT NULL,
  `gross_amount` decimal(18,2) NOT NULL,
  `discount_type` varchar(255) NOT NULL DEFAULT 'none',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(18,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_invoice_items_charge_id_unique` (`charge_id`),
  KEY `billing_invoice_items_billing_item_id_foreign` (`billing_item_id`),
  KEY `billing_invoice_items_invoice_id_billing_item_id_index` (`invoice_id`,`billing_item_id`),
  CONSTRAINT `billing_invoice_items_billing_item_id_foreign` FOREIGN KEY (`billing_item_id`) REFERENCES `billing_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_invoice_items_charge_id_foreign` FOREIGN KEY (`charge_id`) REFERENCES `billing_charges` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_invoice_items`
--

LOCK TABLES `billing_invoice_items` WRITE;
/*!40000 ALTER TABLE `billing_invoice_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_invoices`
--

DROP TABLE IF EXISTS `billing_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `corporate_id` bigint(20) unsigned DEFAULT NULL,
  `corporate_contract_id` bigint(20) unsigned DEFAULT NULL,
  `insurance_policy_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `invoice_type` varchar(255) NOT NULL DEFAULT 'opd',
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `currency` varchar(3) NOT NULL DEFAULT 'BDT',
  `patient_category` varchar(255) DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(255) NOT NULL DEFAULT 'none',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `rounding_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `billing_party_type` varchar(255) DEFAULT NULL,
  `billing_party_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `finalized_by` bigint(20) unsigned DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `refunded_by` bigint(20) unsigned DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `written_off_by` bigint(20) unsigned DEFAULT NULL,
  `written_off_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_invoices_company_id_invoice_number_unique` (`company_id`,`invoice_number`),
  KEY `billing_invoices_branch_id_foreign` (`branch_id`),
  KEY `billing_invoices_encounter_id_foreign` (`encounter_id`),
  KEY `billing_invoices_corporate_contract_id_foreign` (`corporate_contract_id`),
  KEY `billing_invoices_created_by_foreign` (`created_by`),
  KEY `billing_invoices_finalized_by_foreign` (`finalized_by`),
  KEY `billing_invoices_cancelled_by_foreign` (`cancelled_by`),
  KEY `billing_invoices_refunded_by_foreign` (`refunded_by`),
  KEY `billing_invoices_written_off_by_foreign` (`written_off_by`),
  KEY `billing_invoices_company_id_branch_id_status_invoice_date_index` (`company_id`,`branch_id`,`status`,`invoice_date`),
  KEY `billing_invoices_patient_id_status_index` (`patient_id`,`status`),
  KEY `billing_invoices_corporate_id_status_index` (`corporate_id`,`status`),
  KEY `billing_invoices_insurance_policy_id_status_index` (`insurance_policy_id`,`status`),
  CONSTRAINT `billing_invoices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_invoices_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_invoices_corporate_contract_id_foreign` FOREIGN KEY (`corporate_contract_id`) REFERENCES `billing_corporate_contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoices_corporate_id_foreign` FOREIGN KEY (`corporate_id`) REFERENCES `billing_corporates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoices_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoices_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoices_insurance_policy_id_foreign` FOREIGN KEY (`insurance_policy_id`) REFERENCES `billing_insurance_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoices_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_invoices_refunded_by_foreign` FOREIGN KEY (`refunded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_invoices_written_off_by_foreign` FOREIGN KEY (`written_off_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_invoices`
--

LOCK TABLES `billing_invoices` WRITE;
/*!40000 ALTER TABLE `billing_invoices` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_invoices` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_items`
--

DROP TABLE IF EXISTS `billing_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `tax_category_id` bigint(20) unsigned DEFAULT NULL,
  `item_code` varchar(255) NOT NULL,
  `item_type` varchar(255) NOT NULL DEFAULT 'service',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'each',
  `base_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 1,
  `is_clinically_chargeable` tinyint(1) NOT NULL DEFAULT 0,
  `clinical_event_type` varchar(255) DEFAULT NULL,
  `clinical_event_key` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_items_company_id_branch_id_item_code_unique` (`company_id`,`branch_id`,`item_code`),
  KEY `billing_items_branch_id_foreign` (`branch_id`),
  KEY `billing_items_category_id_foreign` (`category_id`),
  KEY `billing_items_tax_category_id_foreign` (`tax_category_id`),
  KEY `billing_items_created_by_foreign` (`created_by`),
  KEY `billing_items_updated_by_foreign` (`updated_by`),
  KEY `billing_items_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  KEY `billing_items_clinical_event_type_clinical_event_key_index` (`clinical_event_type`,`clinical_event_key`),
  CONSTRAINT `billing_items_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `billing_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_items_tax_category_id_foreign` FOREIGN KEY (`tax_category_id`) REFERENCES `billing_tax_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_items`
--

LOCK TABLES `billing_items` WRITE;
/*!40000 ALTER TABLE `billing_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `billing_items` VALUES
(1,1,NULL,1,1,'OPD-CONSULT','consultation','OPD Consultation Fee','OPD Consultation Fee','visit',500.00,0,1,'encounter','completed',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,1,1,'FOLLOWUP-CONSULT','consultation','Follow-up Consultation','Follow-up Consultation','visit',300.00,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,1,1,'REGISTRATION','service','Registration Fee','Registration Fee','visit',100.00,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,1,NULL,2,1,'PROC-GENERAL','procedure','General Procedure','General Procedure','visit',1000.00,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,1,NULL,4,1,'ROOM-GENERAL','room','General Room Charge (per day)','General Room Charge (per day)','visit',1500.00,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(6,1,NULL,3,NULL,'LAB-HGB','diagnostic','Hemoglobin','Hemoglobin','test',300.00,0,1,'lab_test','HGB',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(7,1,NULL,3,NULL,'LAB-RBC','diagnostic','RBC Count','RBC Count','test',250.00,0,1,'lab_test','RBC',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(8,1,NULL,3,NULL,'LAB-WBC','diagnostic','WBC Count','WBC Count','test',250.00,0,1,'lab_test','WBC',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(9,1,NULL,3,NULL,'LAB-PLT','diagnostic','Platelet Count','Platelet Count','test',250.00,0,1,'lab_test','PLT',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(10,1,NULL,3,NULL,'LAB-HCT','diagnostic','Hematocrit','Hematocrit','test',250.00,0,1,'lab_test','HCT',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(11,1,NULL,3,NULL,'LAB-MCV','diagnostic','MCV','MCV','test',250.00,0,1,'lab_test','MCV',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(12,1,NULL,3,NULL,'LAB-FBS','diagnostic','Fasting Blood Glucose','Fasting Blood Glucose','test',200.00,0,1,'lab_test','FBS',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(13,1,NULL,3,NULL,'RAD-XR-CHEST-PA','diagnostic','Chest X-Ray PA','Chest X-Ray PA','procedure',400.00,0,1,'radiology_procedure','XR-CHEST-PA',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(14,1,NULL,3,NULL,'RAD-CT-BRAIN','diagnostic','CT Brain','CT Brain','procedure',4500.00,0,1,'radiology_procedure','CT-BRAIN',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(15,1,NULL,3,NULL,'RAD-US-ABDOMEN','diagnostic','Ultrasound Abdomen','Ultrasound Abdomen','procedure',1200.00,0,1,'radiology_procedure','US-ABDOMEN',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(16,1,NULL,5,NULL,'PH-NAPA-500-TAB','medication','Napa 500mg Tablet','Napa 500mg Tablet','tablet',5.00,0,1,'pharmacy_medication','NAPA-500-TAB',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(17,1,NULL,5,NULL,'PH-AMOX-500-CAP','medication','Amoxicillin 500mg Capsule','Amoxicillin 500mg Capsule','tablet',8.00,0,1,'pharmacy_medication','AMOX-500-CAP',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(18,1,NULL,6,NULL,'IPD-ADM-ELECTIVE','admission_fee','Admission Fee (ELECTIVE)','One-time admission fee','admission',500.00,0,1,'ipd_admission_fee','ELECTIVE',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(19,1,NULL,6,NULL,'IPD-ADM-EMERGENCY','admission_fee','Admission Fee (EMERGENCY)','One-time admission fee','admission',500.00,0,1,'ipd_admission_fee','EMERGENCY',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(20,1,NULL,6,NULL,'IPD-ADM-DAY_CARE','admission_fee','Admission Fee (DAY_CARE)','One-time admission fee','admission',500.00,0,1,'ipd_admission_fee','DAY_CARE',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(21,1,NULL,6,NULL,'IPD-ADM-general','admission_fee','Admission Fee (general)','One-time admission fee','admission',500.00,0,1,'ipd_admission_fee','general',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(22,1,NULL,6,NULL,'IPD-BED-GENERAL','bed_day','General Bed — Daily Charge','Per-day bed occupancy charge','day',1200.00,0,1,'ipd_bed_day','GENERAL',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(23,1,NULL,6,NULL,'IPD-BED-PRIVATE','bed_day','Private Bed — Daily Charge','Per-day bed occupancy charge','day',2500.00,0,1,'ipd_bed_day','PRIVATE',1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL);
/*!40000 ALTER TABLE `billing_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_payment_methods`
--

DROP TABLE IF EXISTS `billing_payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'cash',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_payment_methods_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `billing_payment_methods_branch_id_foreign` (`branch_id`),
  KEY `billing_payment_methods_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `billing_payment_methods_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_payment_methods_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_payment_methods`
--

LOCK TABLES `billing_payment_methods` WRITE;
/*!40000 ALTER TABLE `billing_payment_methods` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `billing_payment_methods` VALUES
(1,1,NULL,'Cash','CASH','cash',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,1,NULL,'Card','CARD','card',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(3,1,NULL,'Mobile Financial Service','MFS','mobile_financial_service',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(4,1,NULL,'Bank Transfer','BANK','bank_transfer',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `billing_payment_methods` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_payments`
--

DROP TABLE IF EXISTS `billing_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `corporate_id` bigint(20) unsigned DEFAULT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `cashier_session_id` bigint(20) unsigned DEFAULT NULL,
  `payment_number` varchar(255) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'BDT',
  `amount` decimal(18,2) NOT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `received_by` bigint(20) unsigned NOT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_payments_company_id_payment_number_unique` (`company_id`,`payment_number`),
  KEY `billing_payments_branch_id_foreign` (`branch_id`),
  KEY `billing_payments_patient_id_foreign` (`patient_id`),
  KEY `billing_payments_corporate_id_foreign` (`corporate_id`),
  KEY `billing_payments_payment_method_id_foreign` (`payment_method_id`),
  KEY `billing_payments_received_by_foreign` (`received_by`),
  KEY `billing_payments_cancelled_by_foreign` (`cancelled_by`),
  KEY `billing_payments_scope_idx` (`company_id`,`branch_id`,`patient_id`,`status`,`payment_date`),
  KEY `billing_payments_invoice_id_status_index` (`invoice_id`,`status`),
  KEY `billing_payments_cashier_session_id_status_index` (`cashier_session_id`,`status`),
  CONSTRAINT `billing_payments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_payments_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_payments_cashier_session_id_foreign` FOREIGN KEY (`cashier_session_id`) REFERENCES `billing_cashier_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_payments_corporate_id_foreign` FOREIGN KEY (`corporate_id`) REFERENCES `billing_corporates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_payments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `billing_payment_methods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_payments`
--

LOCK TABLES `billing_payments` WRITE;
/*!40000 ALTER TABLE `billing_payments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_payments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_price_list_items`
--

DROP TABLE IF EXISTS `billing_price_list_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_price_list_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `price_list_id` bigint(20) unsigned NOT NULL,
  `billing_item_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `provider_id` bigint(20) unsigned DEFAULT NULL,
  `corporate_id` bigint(20) unsigned DEFAULT NULL,
  `insurance_policy_id` bigint(20) unsigned DEFAULT NULL,
  `patient_category` varchar(255) DEFAULT NULL,
  `scope_hash` varchar(40) NOT NULL,
  `unit_price` decimal(18,2) NOT NULL,
  `minimum_price` decimal(18,2) DEFAULT NULL,
  `maximum_price` decimal(18,2) DEFAULT NULL,
  `discount_type` varchar(255) NOT NULL DEFAULT 'none',
  `discount_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_included` tinyint(1) NOT NULL DEFAULT 0,
  `priority` smallint(5) unsigned NOT NULL DEFAULT 100,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_price_list_items_scope_hash_unique` (`price_list_id`,`billing_item_id`,`scope_hash`),
  KEY `billing_price_list_items_billing_item_id_foreign` (`billing_item_id`),
  KEY `billing_price_list_items_department_id_foreign` (`department_id`),
  KEY `billing_price_list_items_provider_id_foreign` (`provider_id`),
  KEY `billing_price_list_items_corporate_id_foreign` (`corporate_id`),
  KEY `billing_price_list_items_insurance_policy_id_foreign` (`insurance_policy_id`),
  KEY `billing_price_list_items_lookup_idx` (`price_list_id`,`billing_item_id`,`is_active`,`priority`),
  CONSTRAINT `billing_price_list_items_billing_item_id_foreign` FOREIGN KEY (`billing_item_id`) REFERENCES `billing_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_price_list_items_corporate_id_foreign` FOREIGN KEY (`corporate_id`) REFERENCES `billing_corporates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_price_list_items_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_price_list_items_insurance_policy_id_foreign` FOREIGN KEY (`insurance_policy_id`) REFERENCES `billing_insurance_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_price_list_items_price_list_id_foreign` FOREIGN KEY (`price_list_id`) REFERENCES `billing_price_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_price_list_items_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_price_list_items`
--

LOCK TABLES `billing_price_list_items` WRITE;
/*!40000 ALTER TABLE `billing_price_list_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `billing_price_list_items` VALUES
(1,1,1,NULL,NULL,NULL,NULL,NULL,'5ed18d7e2a27c629a074f180807442d121bbe8e7',500.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,1,2,NULL,NULL,NULL,NULL,NULL,'34159bdd8ce21e258d3ffd11cf15cf431f44f1fe',300.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(3,1,3,NULL,NULL,NULL,NULL,NULL,'500f842f41f18f8bd4bc97347806c12da159b545',100.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(4,1,4,NULL,NULL,NULL,NULL,NULL,'201d825c8587de3c2bb954bddf4c6f6282000de5',1000.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(5,1,5,NULL,NULL,NULL,NULL,NULL,'2c0391dbe200b876409a62cf3277afac25ec93ac',1500.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `billing_price_list_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_price_lists`
--

DROP TABLE IF EXISTS `billing_price_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_price_lists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'BDT',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `priority` smallint(5) unsigned NOT NULL DEFAULT 100,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_price_lists_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `billing_price_lists_branch_id_foreign` (`branch_id`),
  KEY `billing_price_lists_created_by_foreign` (`created_by`),
  KEY `billing_price_lists_updated_by_foreign` (`updated_by`),
  KEY `billing_price_lists_scope_idx` (`company_id`,`branch_id`,`status`,`priority`,`effective_from`),
  CONSTRAINT `billing_price_lists_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_price_lists_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_price_lists_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_price_lists_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_price_lists`
--

LOCK TABLES `billing_price_lists` WRITE;
/*!40000 ALTER TABLE `billing_price_lists` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `billing_price_lists` VALUES
(1,1,NULL,'Default Price List','DEFAULT','BDT','active',100,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `billing_price_lists` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_receipts`
--

DROP TABLE IF EXISTS `billing_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `receipt_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'issued',
  `issued_at` timestamp NOT NULL,
  `issued_by` bigint(20) unsigned NOT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `voided_by` bigint(20) unsigned DEFAULT NULL,
  `voided_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_receipts_company_id_receipt_number_unique` (`company_id`,`receipt_number`),
  UNIQUE KEY `billing_receipts_payment_id_unique` (`payment_id`),
  KEY `billing_receipts_branch_id_foreign` (`branch_id`),
  KEY `billing_receipts_invoice_id_foreign` (`invoice_id`),
  KEY `billing_receipts_issued_by_foreign` (`issued_by`),
  KEY `billing_receipts_company_id_branch_id_issued_at_index` (`company_id`,`branch_id`,`issued_at`),
  KEY `billing_receipts_voided_by_foreign` (`voided_by`),
  CONSTRAINT `billing_receipts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_receipts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_receipts_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_receipts_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_receipts_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `billing_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_receipts_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_receipts`
--

LOCK TABLES `billing_receipts` WRITE;
/*!40000 ALTER TABLE `billing_receipts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_receipts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_refunds`
--

DROP TABLE IF EXISTS `billing_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `refund_number` varchar(255) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `requested_by` bigint(20) unsigned NOT NULL,
  `requested_at` timestamp NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `processor_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_refunds_company_id_refund_number_unique` (`company_id`,`refund_number`),
  KEY `billing_refunds_branch_id_foreign` (`branch_id`),
  KEY `billing_refunds_payment_id_foreign` (`payment_id`),
  KEY `billing_refunds_patient_id_foreign` (`patient_id`),
  KEY `billing_refunds_requested_by_foreign` (`requested_by`),
  KEY `billing_refunds_approved_by_foreign` (`approved_by`),
  KEY `billing_refunds_processed_by_foreign` (`processed_by`),
  KEY `billing_refunds_company_id_branch_id_payment_id_status_index` (`company_id`,`branch_id`,`payment_id`,`status`),
  KEY `billing_refunds_invoice_id_status_index` (`invoice_id`,`status`),
  KEY `billing_refunds_processed_at_status_index` (`processed_at`,`status`),
  CONSTRAINT `billing_refunds_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_refunds_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_refunds_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_refunds_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_refunds_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_refunds_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `billing_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_refunds_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_refunds_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_refunds`
--

LOCK TABLES `billing_refunds` WRITE;
/*!40000 ALTER TABLE `billing_refunds` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `billing_refunds` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `billing_tax_categories`
--

DROP TABLE IF EXISTS `billing_tax_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_tax_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `is_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_tax_categories_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `billing_tax_categories_branch_id_foreign` (`branch_id`),
  KEY `billing_tax_categories_scope_idx` (`company_id`,`branch_id`,`is_active`,`effective_from`),
  CONSTRAINT `billing_tax_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_tax_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_tax_categories`
--

LOCK TABLES `billing_tax_categories` WRITE;
/*!40000 ALTER TABLE `billing_tax_categories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `billing_tax_categories` VALUES
(1,1,NULL,'VAT','VAT',0.0000,0,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `billing_tax_categories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_company_id_code_unique` (`company_id`,`code`),
  UNIQUE KEY `branches_company_id_slug_unique` (`company_id`,`slug`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `branches` VALUES
(1,1,'Main Hospital','MAIN','main-hospital','main@healthnexus.test','+1-555-0101','123 Healthcare Blvd, Medical District',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,'City Center Clinic','CITY','city-center-clinic','city@healthnexus.test','+1-555-0102','456 Downtown Ave, City Center',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `break_glass_accesses`
--

DROP TABLE IF EXISTS `break_glass_accesses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `break_glass_accesses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned DEFAULT NULL,
  `reason` text NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `scope` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revoked_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `break_glass_accesses_encounter_id_foreign` (`encounter_id`),
  KEY `break_glass_accesses_patient_id_foreign` (`patient_id`),
  KEY `break_glass_accesses_revoked_by_foreign` (`revoked_by`),
  KEY `break_glass_accesses_user_id_encounter_id_index` (`user_id`,`encounter_id`),
  KEY `break_glass_accesses_expires_at_index` (`expires_at`),
  CONSTRAINT `break_glass_accesses_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `break_glass_accesses_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `break_glass_accesses_revoked_by_foreign` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `break_glass_accesses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `break_glass_accesses`
--

LOCK TABLES `break_glass_accesses` WRITE;
/*!40000 ALTER TABLE `break_glass_accesses` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `break_glass_accesses` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cache` VALUES
('hms_cachehms_settings_map_v2','a:80:{i:0;a:11:{s:2:\"id\";i:1;s:5:\"group\";s:6:\"system\";s:3:\"key\";s:15:\"system.app_name\";s:5:\"value\";s:11:\"HealthNexus\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:16:\"Application name\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:1;a:11:{s:2:\"id\";i:2;s:5:\"group\";s:6:\"system\";s:3:\"key\";s:13:\"system.locale\";s:5:\"value\";s:2:\"en\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:26:\"Default application locale\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:2;a:11:{s:2:\"id\";i:3;s:5:\"group\";s:6:\"system\";s:3:\"key\";s:22:\"system.fallback_locale\";s:5:\"value\";s:2:\"en\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:3;a:11:{s:2:\"id\";i:4;s:5:\"group\";s:6:\"system\";s:3:\"key\";s:15:\"system.timezone\";s:5:\"value\";s:3:\"UTC\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:28:\"Default application timezone\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:4;a:11:{s:2:\"id\";i:5;s:5:\"group\";s:6:\"system\";s:3:\"key\";s:15:\"system.currency\";s:5:\"value\";s:3:\"USD\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:21:\"Default currency code\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:5;a:11:{s:2:\"id\";i:6;s:5:\"group\";s:6:\"system\";s:3:\"key\";s:18:\"system.date_format\";s:5:\"value\";s:5:\"Y-m-d\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:6;a:11:{s:2:\"id\";i:7;s:5:\"group\";s:6:\"system\";s:3:\"key\";s:18:\"system.time_format\";s:5:\"value\";s:3:\"H:i\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:7;a:11:{s:2:\"id\";i:8;s:5:\"group\";s:8:\"hospital\";s:3:\"key\";s:13:\"hospital.name\";s:5:\"value\";s:11:\"HealthNexus\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:8;a:11:{s:2:\"id\";i:9;s:5:\"group\";s:8:\"hospital\";s:3:\"key\";s:14:\"hospital.phone\";s:5:\"value\";N;s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:9;a:11:{s:2:\"id\";i:10;s:5:\"group\";s:8:\"hospital\";s:3:\"key\";s:14:\"hospital.email\";s:5:\"value\";N;s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:10;a:11:{s:2:\"id\";i:11;s:5:\"group\";s:8:\"hospital\";s:3:\"key\";s:16:\"hospital.address\";s:5:\"value\";N;s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:11;a:11:{s:2:\"id\";i:12;s:5:\"group\";s:12:\"localization\";s:3:\"key\";s:30:\"localization.supported_locales\";s:5:\"value\";s:44:\"{\"en\":\"English\",\"bn\":\"Bangla\",\"ar\":\"Arabic\"}\";s:4:\"type\";s:4:\"json\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:12;a:11:{s:2:\"id\";i:13;s:5:\"group\";s:8:\"security\";s:3:\"key\";s:24:\"security.session_timeout\";s:5:\"value\";s:3:\"120\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:13;a:11:{s:2:\"id\";i:14;s:5:\"group\";s:8:\"security\";s:3:\"key\";s:27:\"security.max_login_attempts\";s:5:\"value\";s:1:\"5\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:14;a:11:{s:2:\"id\";i:15;s:5:\"group\";s:8:\"security\";s:3:\"key\";s:29:\"security.password_expiry_days\";s:5:\"value\";s:2:\"90\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:15;a:11:{s:2:\"id\";i:16;s:5:\"group\";s:8:\"security\";s:3:\"key\";s:28:\"security.password_min_length\";s:5:\"value\";s:1:\"8\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";s:23:\"Minimum password length\";s:12:\"is_sensitive\";i:1;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:16;a:11:{s:2:\"id\";i:17;s:5:\"group\";s:13:\"notifications\";s:3:\"key\";s:27:\"notifications.email_enabled\";s:5:\"value\";s:1:\"1\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:17;a:11:{s:2:\"id\";i:18;s:5:\"group\";s:13:\"notifications\";s:3:\"key\";s:25:\"notifications.sms_enabled\";s:5:\"value\";s:1:\"0\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:18;a:11:{s:2:\"id\";i:19;s:5:\"group\";s:13:\"notifications\";s:3:\"key\";s:30:\"notifications.whatsapp_enabled\";s:5:\"value\";s:1:\"0\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:19;a:11:{s:2:\"id\";i:20;s:5:\"group\";s:5:\"files\";s:3:\"key\";s:17:\"files.max_size_kb\";s:5:\"value\";s:4:\"2048\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:20;a:11:{s:2:\"id\";i:21;s:5:\"group\";s:5:\"files\";s:3:\"key\";s:24:\"files.allowed_extensions\";s:5:\"value\";s:26:\"[\"pdf\",\"jpg\",\"png\",\"docx\"]\";s:4:\"type\";s:4:\"json\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:21;a:11:{s:2:\"id\";i:22;s:5:\"group\";s:5:\"audit\";s:3:\"key\";s:20:\"audit.retention_days\";s:5:\"value\";s:3:\"365\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:22;a:11:{s:2:\"id\";i:23;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:16:\"billing.currency\";s:5:\"value\";s:3:\"BDT\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:24:\"Default billing currency\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:23;a:11:{s:2:\"id\";i:24;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:22:\"billing.invoice_prefix\";s:5:\"value\";s:3:\"INV\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:24;a:11:{s:2:\"id\";i:25;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:22:\"billing.payment_prefix\";s:5:\"value\";s:3:\"PMT\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:25;a:11:{s:2:\"id\";i:26;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:22:\"billing.receipt_prefix\";s:5:\"value\";s:3:\"RCT\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:26;a:11:{s:2:\"id\";i:27;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:21:\"billing.refund_prefix\";s:5:\"value\";s:3:\"RFD\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:27;a:11:{s:2:\"id\";i:28;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:25:\"billing.adjustment_prefix\";s:5:\"value\";s:3:\"ADJ\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:28;a:11:{s:2:\"id\";i:29;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:29:\"billing.invoice_number_format\";s:5:\"value\";s:23:\"{PREFIX}-{YEAR}-{SEQ:8}\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:25:\"Document numbering format\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:29;a:11:{s:2:\"id\";i:30;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:26:\"billing.rounding_precision\";s:5:\"value\";s:1:\"2\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:30;a:11:{s:2:\"id\";i:31;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:21:\"billing.rounding_mode\";s:5:\"value\";s:7:\"nearest\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:15:\"nearest|up|down\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:31;a:11:{s:2:\"id\";i:32;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:43:\"billing.discount_approval_threshold_percent\";s:5:\"value\";s:2:\"10\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";s:43:\"Discount % above which approval is required\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:32;a:11:{s:2:\"id\";i:33;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:42:\"billing.discount_approval_threshold_amount\";s:5:\"value\";s:4:\"5000\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";s:48:\"Discount amount above which approval is required\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:33;a:11:{s:2:\"id\";i:34;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:30:\"billing.discount_approver_role\";s:5:\"value\";s:14:\"hospital_admin\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:34;a:11:{s:2:\"id\";i:35;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:32:\"billing.refund_approval_required\";s:5:\"value\";s:1:\"1\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:35;a:11:{s:2:\"id\";i:36;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:35:\"billing.default_price_list_priority\";s:5:\"value\";s:3:\"100\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:36;a:11:{s:2:\"id\";i:37;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:29:\"billing.tax_inclusive_default\";s:5:\"value\";s:1:\"0\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:37;a:11:{s:2:\"id\";i:38;s:5:\"group\";s:7:\"billing\";s:3:\"key\";s:27:\"billing.advance_min_balance\";s:5:\"value\";s:1:\"0\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:38;a:11:{s:2:\"id\";i:39;s:5:\"group\";s:10:\"laboratory\";s:3:\"key\";s:23:\"laboratory.order_prefix\";s:5:\"value\";s:3:\"LAB\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:39;a:11:{s:2:\"id\";i:40;s:5:\"group\";s:10:\"laboratory\";s:3:\"key\";s:27:\"laboratory.accession_prefix\";s:5:\"value\";s:3:\"ACC\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:40;a:11:{s:2:\"id\";i:41;s:5:\"group\";s:10:\"laboratory\";s:3:\"key\";s:24:\"laboratory.report_prefix\";s:5:\"value\";s:3:\"RPT\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:41;a:11:{s:2:\"id\";i:42;s:5:\"group\";s:10:\"laboratory\";s:3:\"key\";s:30:\"laboratory.order_number_format\";s:5:\"value\";s:23:\"{PREFIX}-{YEAR}-{SEQ:8}\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:25:\"Document numbering format\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:42;a:11:{s:2:\"id\";i:43;s:5:\"group\";s:10:\"laboratory\";s:3:\"key\";s:34:\"laboratory.accession_number_format\";s:5:\"value\";s:23:\"{PREFIX}-{YEAR}-{SEQ:8}\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:43;a:11:{s:2:\"id\";i:44;s:5:\"group\";s:10:\"laboratory\";s:3:\"key\";s:37:\"laboratory.default_turnaround_minutes\";s:5:\"value\";s:2:\"60\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:44;a:11:{s:2:\"id\";i:45;s:5:\"group\";s:10:\"laboratory\";s:3:\"key\";s:41:\"laboratory.critical_notification_channels\";s:5:\"value\";s:19:\"[\"database\",\"mail\"]\";s:4:\"type\";s:4:\"json\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:45;a:11:{s:2:\"id\";i:46;s:5:\"group\";s:10:\"laboratory\";s:3:\"key\";s:47:\"laboratory.require_pathologist_approval_default\";s:5:\"value\";s:1:\"0\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:46;a:11:{s:2:\"id\";i:47;s:5:\"group\";s:9:\"radiology\";s:3:\"key\";s:22:\"radiology.order_prefix\";s:5:\"value\";s:3:\"RAD\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:47;a:11:{s:2:\"id\";i:48;s:5:\"group\";s:9:\"radiology\";s:3:\"key\";s:26:\"radiology.accession_prefix\";s:5:\"value\";s:3:\"RAD\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:48;a:11:{s:2:\"id\";i:49;s:5:\"group\";s:9:\"radiology\";s:3:\"key\";s:29:\"radiology.order_number_format\";s:5:\"value\";s:23:\"{PREFIX}-{YEAR}-{SEQ:8}\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:49;a:11:{s:2:\"id\";i:50;s:5:\"group\";s:9:\"radiology\";s:3:\"key\";s:33:\"radiology.accession_number_format\";s:5:\"value\";s:23:\"{PREFIX}-{YEAR}-{SEQ:8}\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:50;a:11:{s:2:\"id\";i:51;s:5:\"group\";s:9:\"radiology\";s:3:\"key\";s:36:\"radiology.default_turnaround_minutes\";s:5:\"value\";s:3:\"120\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:51;a:11:{s:2:\"id\";i:52;s:5:\"group\";s:9:\"radiology\";s:3:\"key\";s:48:\"radiology.critical_finding_notification_channels\";s:5:\"value\";s:19:\"[\"database\",\"mail\"]\";s:4:\"type\";s:4:\"json\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:52;a:11:{s:2:\"id\";i:53;s:5:\"group\";s:9:\"radiology\";s:3:\"key\";s:24:\"radiology.dicom_uid_root\";s:5:\"value\";s:26:\"1.2.826.0.1.3680043.10.001\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:71:\"Org root OID used only if the app ever pre-assigns a Study Instance UID\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:53;a:11:{s:2:\"id\";i:54;s:5:\"group\";s:9:\"radiology\";s:3:\"key\";s:32:\"radiology.default_pacs_server_id\";s:5:\"value\";N;s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:54;a:11:{s:2:\"id\";i:55;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:21:\"pharmacy.order_prefix\";s:5:\"value\";s:2:\"RX\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:55;a:11:{s:2:\"id\";i:56;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:26:\"pharmacy.dispensing_prefix\";s:5:\"value\";s:3:\"DSP\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:56;a:11:{s:2:\"id\";i:57;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:24:\"pharmacy.transfer_prefix\";s:5:\"value\";s:3:\"TRF\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:57;a:11:{s:2:\"id\";i:58;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:22:\"pharmacy.return_prefix\";s:5:\"value\";s:3:\"RTN\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:58;a:11:{s:2:\"id\";i:59;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:28:\"pharmacy.order_number_format\";s:5:\"value\";s:23:\"{PREFIX}-{YEAR}-{SEQ:8}\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:59;a:11:{s:2:\"id\";i:60;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:35:\"pharmacy.near_expiry_threshold_days\";s:5:\"value\";s:2:\"90\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";s:62:\"Batches expiring within this many days are flagged near-expiry\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:60;a:11:{s:2:\"id\";i:61;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:29:\"pharmacy.allow_negative_stock\";s:5:\"value\";s:1:\"0\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";s:95:\"Reserved — no override path is wired in Phase 7; dispensing always refuses insufficient stock\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:61;a:11:{s:2:\"id\";i:62;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:41:\"pharmacy.controlled_drug_witness_required\";s:5:\"value\";s:1:\"0\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:62;a:11:{s:2:\"id\";i:63;s:5:\"group\";s:8:\"pharmacy\";s:3:\"key\";s:39:\"pharmacy.substitution_requires_approval\";s:5:\"value\";s:1:\"1\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:63;a:11:{s:2:\"id\";i:64;s:5:\"group\";s:3:\"ipd\";s:3:\"key\";s:20:\"ipd.admission_prefix\";s:5:\"value\";s:3:\"ADM\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:64;a:11:{s:2:\"id\";i:65;s:5:\"group\";s:3:\"ipd\";s:3:\"key\";s:27:\"ipd.admission_number_format\";s:5:\"value\";s:23:\"{PREFIX}-{YEAR}-{SEQ:8}\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:65;a:11:{s:2:\"id\";i:66;s:5:\"group\";s:3:\"ipd\";s:3:\"key\";s:34:\"ipd.bed_reservation_expiry_minutes\";s:5:\"value\";s:3:\"120\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";s:91:\"Minutes after which an unconverted bed reservation expires and the bed returns to Available\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:66;a:11:{s:2:\"id\";i:67;s:5:\"group\";s:3:\"ipd\";s:3:\"key\";s:31:\"ipd.discharge_requires_cleaning\";s:5:\"value\";s:1:\"1\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";s:76:\"When true, a released bed goes to Cleaning rather than directly to Available\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:67;a:11:{s:2:\"id\";i:68;s:5:\"group\";s:3:\"ipd\";s:3:\"key\";s:30:\"ipd.leave_default_bed_handling\";s:5:\"value\";s:6:\"retain\";s:4:\"type\";s:6:\"string\";s:11:\"description\";s:82:\"retain keeps the bed allocation active during patient leave; release frees the bed\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:68;a:11:{s:2:\"id\";i:69;s:5:\"group\";s:3:\"ipd\";s:3:\"key\";s:31:\"ipd.admission_requires_approval\";s:5:\"value\";s:1:\"1\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:69;a:11:{s:2:\"id\";i:70;s:5:\"group\";s:3:\"ipd\";s:3:\"key\";s:38:\"ipd.delayed_discharge_escalation_hours\";s:5:\"value\";s:2:\"24\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:70;a:11:{s:2:\"id\";i:71;s:5:\"group\";s:8:\"patients\";s:3:\"key\";s:26:\"patients.duplicate_weights\";s:5:\"value\";s:76:\"{\"name\":30,\"date_of_birth\":25,\"phone\":20,\"national_identifier\":20,\"email\":5}\";s:4:\"type\";s:4:\"json\";s:11:\"description\";s:54:\"Weighted duplicate-detection scoring per matched field\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:71;a:11:{s:2:\"id\";i:72;s:5:\"group\";s:8:\"patients\";s:3:\"key\";s:35:\"patients.amendment_sensitive_fields\";s:5:\"value\";s:45:\"[\"date_of_birth\",\"sex\",\"national_identifier\"]\";s:4:\"type\";s:4:\"json\";s:11:\"description\";s:76:\"Fields that require the amendment approval workflow instead of a direct edit\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-23 23:34:58\";s:10:\"updated_at\";s:19:\"2026-09-23 23:34:58\";}i:72;a:11:{s:2:\"id\";i:73;s:5:\"group\";s:7:\"nursing\";s:3:\"key\";s:31:\"nursing.mar_frequency_intervals\";s:5:\"value\";s:68:\"{\"OD\":24,\"BID\":12,\"TID\":8,\"QID\":6,\"Q4H\":4,\"Q6H\":6,\"Q8H\":8,\"Q12H\":12}\";s:4:\"type\";s:4:\"json\";s:11:\"description\";s:210:\"Hospital-configurable map of recognized frequency codes to hour intervals, used only to auto-generate the next scheduled MAR dose — unrecognized codes and all PRN items fall back to nurse-initiated scheduling\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-24 04:26:30\";s:10:\"updated_at\";s:19:\"2026-09-24 04:26:30\";}i:73;a:11:{s:2:\"id\";i:74;s:5:\"group\";s:7:\"nursing\";s:3:\"key\";s:40:\"nursing.vitals_default_frequency_minutes\";s:5:\"value\";s:3:\"240\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";s:100:\"Default interval between routine vital-sign observations when no care plan/order specifies otherwise\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-24 04:26:30\";s:10:\"updated_at\";s:19:\"2026-09-24 04:26:30\";}i:74;a:11:{s:2:\"id\";i:75;s:5:\"group\";s:7:\"nursing\";s:3:\"key\";s:41:\"nursing.handover_requires_acknowledgement\";s:5:\"value\";s:1:\"1\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-24 04:26:30\";s:10:\"updated_at\";s:19:\"2026-09-24 04:26:30\";}i:75;a:11:{s:2:\"id\";i:76;s:5:\"group\";s:7:\"nursing\";s:3:\"key\";s:35:\"nursing.high_alert_requires_witness\";s:5:\"value\";s:1:\"1\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";s:98:\"Requires a second-nurse witness for administration of medications flagged is_high_alert in Phase 7\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-24 04:26:30\";s:10:\"updated_at\";s:19:\"2026-09-24 04:26:30\";}i:76;a:11:{s:2:\"id\";i:77;s:5:\"group\";s:7:\"nursing\";s:3:\"key\";s:35:\"nursing.controlled_requires_witness\";s:5:\"value\";s:1:\"1\";s:4:\"type\";s:7:\"boolean\";s:11:\"description\";s:98:\"Requires a second-nurse witness for administration of medications flagged is_controlled in Phase 7\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-24 04:26:30\";s:10:\"updated_at\";s:19:\"2026-09-24 04:26:30\";}i:77;a:11:{s:2:\"id\";i:78;s:5:\"group\";s:7:\"nursing\";s:3:\"key\";s:32:\"nursing.prn_reassessment_minutes\";s:5:\"value\";s:2:\"60\";s:4:\"type\";s:7:\"integer\";s:11:\"description\";s:88:\"Minutes after a PRN administration by which a reassessment/response should be documented\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-24 04:26:30\";s:10:\"updated_at\";s:19:\"2026-09-24 04:26:30\";}i:78;a:11:{s:2:\"id\";i:79;s:5:\"group\";s:7:\"nursing\";s:3:\"key\";s:41:\"nursing.escalation_default_recipient_role\";s:5:\"value\";s:12:\"charge_nurse\";s:4:\"type\";s:6:\"string\";s:11:\"description\";N;s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-24 04:26:30\";s:10:\"updated_at\";s:19:\"2026-09-24 04:26:30\";}i:79;a:11:{s:2:\"id\";i:80;s:5:\"group\";s:7:\"nursing\";s:3:\"key\";s:30:\"nursing.observation_thresholds\";s:5:\"value\";s:68:\"{\"blood_glucose\":{\"low\":70,\"high\":200},\"pain\":{\"low\":null,\"high\":7}}\";s:4:\"type\";s:4:\"json\";s:11:\"description\";s:143:\"Hospital-configurable low/high alert thresholds per nursing_observations.observation_type — a breach raises a NursingAlert, never a diagnosis\";s:12:\"is_sensitive\";i:0;s:9:\"is_locked\";i:0;s:10:\"sort_order\";i:0;s:10:\"created_at\";s:19:\"2026-09-24 04:26:30\";s:10:\"updated_at\";s:19:\"2026-09-24 04:26:30\";}}',1790227590),
('hms_cachespatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:420:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:16:\"manage companies\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:15:\"manage branches\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:18:\"manage departments\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"manage users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:12:\"manage roles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:18:\"manage permissions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:15:\"manage patients\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:13:\"patients.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:14:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:9;i:6;i:12;i:7;i:27;i:8;i:28;i:9;i:29;i:10;i:30;i:11;i:31;i:12;i:32;i:13;i:33;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:15:\"patients.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:27;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:15:\"patients.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:27;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:15:\"patients.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:14:\"patients.merge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:15:\"patients.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:14:\"patients.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:19:\"patients.alert.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;i:7;i:33;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:21:\"patients.alert.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:23:\"patients.documents.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:10:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:27;i:6;i:30;i:7;i:31;i:8;i:32;i:9;i:33;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:25:\"patients.documents.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:22:\"patients.consents.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:24:\"patients.consents.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:22:\"patients.amend.request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:22:\"patients.amend.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:22:\"patients.portal.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:17:\"appointments.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:9;i:5;i:12;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:19:\"appointments.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:19:\"appointments.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:19:\"appointments.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:20:\"appointments.confirm\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:20:\"appointments.checkin\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:19:\"appointments.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:23:\"appointments.reschedule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:20:\"appointments.no_show\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:18:\"appointments.queue\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:18:\"appointments.token\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:21:\"appointments.override\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:19:\"appointments.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:18:\"appointments.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:28:\"appointments.manage_schedule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:28:\"appointments.manage_provider\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:27:\"appointments.manage_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:25:\"appointments.manage_block\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:31:\"appointments.manage_overbooking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:14:\"schedules.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:16:\"schedules.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:16:\"schedules.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:16:\"schedules.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:10:\"queue.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:12:\"queue.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:8:\"opd.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:10:\"opd.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:10:\"opd.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:11:\"opd.consult\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:14:\"emergency.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:16:\"emergency.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:16:\"emergency.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:16:\"emergency.triage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:8:\"ipd.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:10:\"ipd.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:10:\"ipd.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:9:\"ipd.admit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:13:\"ipd.discharge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:8:\"bed.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:12:\"bed.allocate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:12:\"bed.transfer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:9:\"bed.block\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:12:\"nursing.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:14:\"nursing.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:14:\"nursing.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:18:\"nursing.administer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:11:\"doctor.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:13:\"doctor.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:13:\"doctor.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:14:\"doctor.consult\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:8:\"emr.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:10:\"emr.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:10:\"emr.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:9:\"emr.amend\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:11:\"emr.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:15:\"encounters.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:17:\"encounters.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:17:\"encounters.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:17:\"encounters.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:15:\"encounter.start\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:18:\"encounter.complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:16:\"encounter.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:85;a:4:{s:1:\"a\";i:86;s:1:\"b\";s:15:\"encounter.amend\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:86;a:4:{s:1:\"a\";i:87;s:1:\"b\";s:14:\"encounter.lock\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:87;a:4:{s:1:\"a\";i:88;s:1:\"b\";s:16:\"encounter.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:88;a:4:{s:1:\"a\";i:89;s:1:\"b\";s:15:\"encounter.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:89;a:4:{s:1:\"a\";i:90;s:1:\"b\";s:18:\"clinical.note.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;}}i:90;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:20:\"clinical.note.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;}}i:91;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:20:\"clinical.note.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:92;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:20:\"clinical.vitals.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;}}i:93;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:22:\"clinical.vitals.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;}}i:94;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:23:\"clinical.diagnosis.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:30;i:5;i:31;i:6;i:32;}}i:95;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:25:\"clinical.diagnosis.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:96;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:25:\"clinical.diagnosis.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:97;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:19:\"clinical.order.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:98;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:21:\"clinical.order.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:99;a:4:{s:1:\"a\";i:100;s:1:\"b\";s:21:\"clinical.order.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:100;a:4:{s:1:\"a\";i:101;s:1:\"b\";s:22:\"clinical.referral.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:101;a:4:{s:1:\"a\";i:102;s:1:\"b\";s:24:\"clinical.referral.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:102;a:4:{s:1:\"a\";i:103;s:1:\"b\";s:20:\"clinical.break_glass\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:103;a:4:{s:1:\"a\";i:104;s:1:\"b\";s:17:\"prescription.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:10:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:20;i:5;i:21;i:6;i:22;i:7;i:30;i:8;i:31;i:9;i:32;}}i:104;a:4:{s:1:\"a\";i:105;s:1:\"b\";s:19:\"prescription.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:105;a:4:{s:1:\"a\";i:106;s:1:\"b\";s:19:\"prescription.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:106;a:4:{s:1:\"a\";i:107;s:1:\"b\";s:18:\"prescription.issue\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:107;a:4:{s:1:\"a\";i:108;s:1:\"b\";s:19:\"prescription.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:108;a:4:{s:1:\"a\";i:109;s:1:\"b\";s:18:\"prescription.amend\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:109;a:4:{s:1:\"a\";i:110;s:1:\"b\";s:13:\"pharmacy.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:20;}}i:110;a:4:{s:1:\"a\";i:111;s:1:\"b\";s:15:\"pharmacy.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:20;}}i:111;a:4:{s:1:\"a\";i:112;s:1:\"b\";s:15:\"pharmacy.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:20;}}i:112;a:4:{s:1:\"a\";i:113;s:1:\"b\";s:17:\"pharmacy.dispense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:20;}}i:113;a:4:{s:1:\"a\";i:114;s:1:\"b\";s:15:\"pharmacy.adjust\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:20;}}i:114;a:4:{s:1:\"a\";i:115;s:1:\"b\";s:15:\"laboratory.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:3;i:2;i:6;i:3;i:7;i:4;i:8;i:5;i:11;}}i:115;a:4:{s:1:\"a\";i:116;s:1:\"b\";s:17:\"laboratory.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:6;i:3;i:7;i:4;i:11;}}i:116;a:4:{s:1:\"a\";i:117;s:1:\"b\";s:17:\"laboratory.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:6;i:3;i:7;i:4;i:11;}}i:117;a:4:{s:1:\"a\";i:118;s:1:\"b\";s:17:\"laboratory.verify\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:8;i:4;i:11;}}i:118;a:4:{s:1:\"a\";i:119;s:1:\"b\";s:18:\"laboratory.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:8;i:2;i:11;}}i:119;a:4:{s:1:\"a\";i:120;s:1:\"b\";s:18:\"laboratory.release\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:8;i:2;i:11;}}i:120;a:4:{s:1:\"a\";i:121;s:1:\"b\";s:14:\"radiology.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:14;i:3;i:15;i:4;i:16;}}i:121;a:4:{s:1:\"a\";i:122;s:1:\"b\";s:16:\"radiology.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:16;}}i:122;a:4:{s:1:\"a\";i:123;s:1:\"b\";s:16:\"radiology.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:16;}}i:123;a:4:{s:1:\"a\";i:124;s:1:\"b\";s:16:\"radiology.report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:14;i:3;i:15;i:4;i:16;}}i:124;a:4:{s:1:\"a\";i:125;s:1:\"b\";s:7:\"ot.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:125;a:4:{s:1:\"a\";i:126;s:1:\"b\";s:9:\"ot.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:126;a:4:{s:1:\"a\";i:127;s:1:\"b\";s:9:\"ot.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:127;a:4:{s:1:\"a\";i:128;s:1:\"b\";s:11:\"ot.schedule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:128;a:4:{s:1:\"a\";i:129;s:1:\"b\";s:8:\"icu.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:129;a:4:{s:1:\"a\";i:130;s:1:\"b\";s:10:\"icu.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:130;a:4:{s:1:\"a\";i:131;s:1:\"b\";s:10:\"icu.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:131;a:4:{s:1:\"a\";i:132;s:1:\"b\";s:12:\"billing.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:5;i:3;i:9;i:4;i:12;i:5;i:25;i:6;i:26;}}i:132;a:4:{s:1:\"a\";i:133;s:1:\"b\";s:14:\"billing.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:5;i:3;i:25;}}i:133;a:4:{s:1:\"a\";i:134;s:1:\"b\";s:14:\"billing.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:5;i:3;i:25;i:4;i:26;}}i:134;a:4:{s:1:\"a\";i:135;s:1:\"b\";s:16:\"billing.discount\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:135;a:4:{s:1:\"a\";i:136;s:1:\"b\";s:14:\"billing.refund\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:136;a:4:{s:1:\"a\";i:137;s:1:\"b\";s:15:\"billing.payment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:5;i:3;i:25;}}i:137;a:4:{s:1:\"a\";i:138;s:1:\"b\";s:22:\"billing.dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:25;i:3;i:26;}}i:138;a:4:{s:1:\"a\";i:139;s:1:\"b\";s:19:\"billing.charge.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:9;i:2;i:12;i:3;i:25;}}i:139;a:4:{s:1:\"a\";i:140;s:1:\"b\";s:21:\"billing.charge.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:140;a:4:{s:1:\"a\";i:141;s:1:\"b\";s:21:\"billing.charge.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:141;a:4:{s:1:\"a\";i:142;s:1:\"b\";s:20:\"billing.invoice.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:9;i:3;i:12;i:4;i:25;i:5;i:26;}}i:142;a:4:{s:1:\"a\";i:143;s:1:\"b\";s:22:\"billing.invoice.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:143;a:4:{s:1:\"a\";i:144;s:1:\"b\";s:22:\"billing.invoice.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:144;a:4:{s:1:\"a\";i:145;s:1:\"b\";s:24:\"billing.invoice.finalize\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:145;a:4:{s:1:\"a\";i:146;s:1:\"b\";s:22:\"billing.invoice.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:146;a:4:{s:1:\"a\";i:147;s:1:\"b\";s:24:\"billing.invoice.writeoff\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:147;a:4:{s:1:\"a\";i:148;s:1:\"b\";s:20:\"billing.payment.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:25;i:2;i:26;}}i:148;a:4:{s:1:\"a\";i:149;s:1:\"b\";s:22:\"billing.payment.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:25;}}i:149;a:4:{s:1:\"a\";i:150;s:1:\"b\";s:22:\"billing.payment.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:150;a:4:{s:1:\"a\";i:151;s:1:\"b\";s:20:\"billing.receipt.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:25;i:2;i:26;}}i:151;a:4:{s:1:\"a\";i:152;s:1:\"b\";s:20:\"billing.receipt.void\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:152;a:4:{s:1:\"a\";i:153;s:1:\"b\";s:22:\"billing.refund.request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:25;}}i:153;a:4:{s:1:\"a\";i:154;s:1:\"b\";s:22:\"billing.refund.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:26;}}i:154;a:4:{s:1:\"a\";i:155;s:1:\"b\";s:22:\"billing.refund.process\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:155;a:4:{s:1:\"a\";i:156;s:1:\"b\";s:26:\"billing.adjustment.request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:156;a:4:{s:1:\"a\";i:157;s:1:\"b\";s:26:\"billing.adjustment.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:26;}}i:157;a:4:{s:1:\"a\";i:158;s:1:\"b\";s:20:\"billing.cashier.open\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:25;}}i:158;a:4:{s:1:\"a\";i:159;s:1:\"b\";s:21:\"billing.cashier.close\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:25;}}i:159;a:4:{s:1:\"a\";i:160;s:1:\"b\";s:20:\"billing.cashier.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:25;i:2;i:26;}}i:160;a:4:{s:1:\"a\";i:161;s:1:\"b\";s:25:\"billing.cashier.reconcile\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:26;}}i:161;a:4:{s:1:\"a\";i:162;s:1:\"b\";s:20:\"billing.pricing.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:162;a:4:{s:1:\"a\";i:163;s:1:\"b\";s:22:\"billing.pricing.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:163;a:4:{s:1:\"a\";i:164;s:1:\"b\";s:23:\"billing.category.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:164;a:4:{s:1:\"a\";i:165;s:1:\"b\";s:19:\"billing.item.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:165;a:4:{s:1:\"a\";i:166;s:1:\"b\";s:22:\"billing.corporate.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:26;}}i:166;a:4:{s:1:\"a\";i:167;s:1:\"b\";s:24:\"billing.corporate.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:167;a:4:{s:1:\"a\";i:168;s:1:\"b\";s:29:\"billing.insurance.policy.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:26;}}i:168;a:4:{s:1:\"a\";i:169;s:1:\"b\";s:31:\"billing.insurance.policy.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:169;a:4:{s:1:\"a\";i:170;s:1:\"b\";s:19:\"billing.report.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:26;}}i:170;a:4:{s:1:\"a\";i:171;s:1:\"b\";s:23:\"billing.settings.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:171;a:4:{s:1:\"a\";i:172;s:1:\"b\";s:18:\"lab.dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;i:5;i:8;i:6;i:9;i:7;i:10;i:8;i:11;}}i:172;a:4:{s:1:\"a\";i:173;s:1:\"b\";s:13:\"lab.test.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:6;i:3;i:7;i:4;i:8;i:5;i:11;}}i:173;a:4:{s:1:\"a\";i:174;s:1:\"b\";s:15:\"lab.test.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:11;}}i:174;a:4:{s:1:\"a\";i:175;s:1:\"b\";s:15:\"lab.test.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:11;}}i:175;a:4:{s:1:\"a\";i:176;s:1:\"b\";s:14:\"lab.panel.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:6;i:3;i:7;i:4;i:8;i:5;i:11;}}i:176;a:4:{s:1:\"a\";i:177;s:1:\"b\";s:16:\"lab.panel.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:11;}}i:177;a:4:{s:1:\"a\";i:178;s:1:\"b\";s:16:\"lab.panel.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:11;}}i:178;a:4:{s:1:\"a\";i:179;s:1:\"b\";s:17:\"lab.specimen.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:8;i:4;i:9;i:5;i:10;i:6;i:11;}}i:179;a:4:{s:1:\"a\";i:180;s:1:\"b\";s:20:\"lab.specimen.collect\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:10;i:4;i:11;}}i:180;a:4:{s:1:\"a\";i:181;s:1:\"b\";s:20:\"lab.specimen.receive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:11;}}i:181;a:4:{s:1:\"a\";i:182;s:1:\"b\";s:19:\"lab.specimen.reject\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:11;}}i:182;a:4:{s:1:\"a\";i:183;s:1:\"b\";s:20:\"lab.specimen.process\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:11;}}i:183;a:4:{s:1:\"a\";i:184;s:1:\"b\";s:14:\"lab.order.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;i:5;i:8;i:6;i:9;i:7;i:10;i:8;i:11;}}i:184;a:4:{s:1:\"a\";i:185;s:1:\"b\";s:16:\"lab.order.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:9;i:3;i:11;}}i:185;a:4:{s:1:\"a\";i:186;s:1:\"b\";s:16:\"lab.order.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:11;}}i:186;a:4:{s:1:\"a\";i:187;s:1:\"b\";s:15:\"lab.result.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;i:5;i:8;i:6;i:11;}}i:187;a:4:{s:1:\"a\";i:188;s:1:\"b\";s:17:\"lab.result.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:11;}}i:188;a:4:{s:1:\"a\";i:189;s:1:\"b\";s:17:\"lab.result.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:11;}}i:189;a:4:{s:1:\"a\";i:190;s:1:\"b\";s:19:\"lab.result.validate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:7;i:2;i:8;i:3;i:11;}}i:190;a:4:{s:1:\"a\";i:191;s:1:\"b\";s:18:\"lab.result.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:8;i:3;i:11;}}i:191;a:4:{s:1:\"a\";i:192;s:1:\"b\";s:16:\"lab.result.amend\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:8;i:3;i:11;}}i:192;a:4:{s:1:\"a\";i:193;s:1:\"b\";s:15:\"lab.report.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;i:5;i:8;i:6;i:11;}}i:193;a:4:{s:1:\"a\";i:194;s:1:\"b\";s:19:\"lab.report.generate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:8;i:2;i:11;}}i:194;a:4:{s:1:\"a\";i:195;s:1:\"b\";s:16:\"lab.report.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:3;i:2;i:6;i:3;i:7;i:4;i:8;i:5;i:11;}}i:195;a:4:{s:1:\"a\";i:196;s:1:\"b\";s:17:\"lab.report.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:8;i:3;i:11;}}i:196;a:4:{s:1:\"a\";i:197;s:1:\"b\";s:24:\"lab.critical_result.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;i:5;i:8;i:6;i:11;}}i:197;a:4:{s:1:\"a\";i:198;s:1:\"b\";s:26:\"lab.critical_result.notify\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:8;i:4;i:11;}}i:198;a:4:{s:1:\"a\";i:199;s:1:\"b\";s:31:\"lab.critical_result.acknowledge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:7;i:3;i:8;i:4;i:11;}}i:199;a:4:{s:1:\"a\";i:200;s:1:\"b\";s:11:\"lab.qc.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:6;i:3;i:7;i:4;i:8;i:5;i:11;}}i:200;a:4:{s:1:\"a\";i:201;s:1:\"b\";s:13:\"lab.qc.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:6;i:2;i:7;i:3;i:11;}}i:201;a:4:{s:1:\"a\";i:202;s:1:\"b\";s:17:\"lab.analyzer.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:11;}}i:202;a:4:{s:1:\"a\";i:203;s:1:\"b\";s:22:\"lab.analyzer.configure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:11;}}i:203;a:4:{s:1:\"a\";i:204;s:1:\"b\";s:19:\"lab.settings.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:11;}}i:204;a:4:{s:1:\"a\";i:205;s:1:\"b\";s:24:\"radiology.dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:12;i:4;i:13;i:5;i:14;i:6;i:15;i:7;i:16;i:8;i:17;}}i:205;a:4:{s:1:\"a\";i:206;s:1:\"b\";s:24:\"radiology.procedure.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:16;}}i:206;a:4:{s:1:\"a\";i:207;s:1:\"b\";s:26:\"radiology.procedure.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:16;}}i:207;a:4:{s:1:\"a\";i:208;s:1:\"b\";s:26:\"radiology.procedure.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:16;}}i:208;a:4:{s:1:\"a\";i:209;s:1:\"b\";s:23:\"radiology.modality.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:16;}}i:209;a:4:{s:1:\"a\";i:210;s:1:\"b\";s:25:\"radiology.modality.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:16;}}i:210;a:4:{s:1:\"a\";i:211;s:1:\"b\";s:25:\"radiology.modality.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:16;}}i:211;a:4:{s:1:\"a\";i:212;s:1:\"b\";s:20:\"radiology.order.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:12;i:4;i:13;i:5;i:14;i:6;i:15;i:7;i:16;}}i:212;a:4:{s:1:\"a\";i:213;s:1:\"b\";s:22:\"radiology.order.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:12;i:3;i:16;}}i:213;a:4:{s:1:\"a\";i:214;s:1:\"b\";s:22:\"radiology.order.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:16;}}i:214;a:4:{s:1:\"a\";i:215;s:1:\"b\";s:23:\"radiology.schedule.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:12;i:2;i:13;i:3;i:16;}}i:215;a:4:{s:1:\"a\";i:216;s:1:\"b\";s:25:\"radiology.schedule.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:12;i:2;i:16;}}i:216;a:4:{s:1:\"a\";i:217;s:1:\"b\";s:25:\"radiology.schedule.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:12;i:2;i:16;}}i:217;a:4:{s:1:\"a\";i:218;s:1:\"b\";s:25:\"radiology.schedule.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:12;i:2;i:16;}}i:218;a:4:{s:1:\"a\";i:219;s:1:\"b\";s:26:\"radiology.examination.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:13;i:2;i:14;i:3;i:15;i:4;i:16;}}i:219;a:4:{s:1:\"a\";i:220;s:1:\"b\";s:27:\"radiology.examination.start\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:13;i:2;i:16;}}i:220;a:4:{s:1:\"a\";i:221;s:1:\"b\";s:30:\"radiology.examination.complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:13;i:2;i:16;}}i:221;a:4:{s:1:\"a\";i:222;s:1:\"b\";s:20:\"radiology.study.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:13;i:2;i:14;i:3;i:15;i:4;i:16;i:5;i:17;}}i:222;a:4:{s:1:\"a\";i:223;s:1:\"b\";s:22:\"radiology.study.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:16;}}i:223;a:4:{s:1:\"a\";i:224;s:1:\"b\";s:23:\"radiology.worklist.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:13;i:2;i:14;i:3;i:15;i:4;i:16;}}i:224;a:4:{s:1:\"a\";i:225;s:1:\"b\";s:25:\"radiology.worklist.assign\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:14;i:2;i:15;i:3;i:16;}}i:225;a:4:{s:1:\"a\";i:226;s:1:\"b\";s:21:\"radiology.report.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:13;i:4;i:14;i:5;i:15;i:6;i:16;}}i:226;a:4:{s:1:\"a\";i:227;s:1:\"b\";s:23:\"radiology.report.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:14;i:2;i:15;i:3;i:16;}}i:227;a:4:{s:1:\"a\";i:228;s:1:\"b\";s:23:\"radiology.report.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:14;i:2;i:15;i:3;i:16;}}i:228;a:4:{s:1:\"a\";i:229;s:1:\"b\";s:23:\"radiology.report.submit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:14;i:2;i:15;i:3;i:16;}}i:229;a:4:{s:1:\"a\";i:230;s:1:\"b\";s:24:\"radiology.report.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:15;i:3;i:16;}}i:230;a:4:{s:1:\"a\";i:231;s:1:\"b\";s:22:\"radiology.report.amend\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:15;i:3;i:16;}}i:231;a:4:{s:1:\"a\";i:232;s:1:\"b\";s:31:\"radiology.critical_finding.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:14;i:4;i:15;i:5;i:16;}}i:232;a:4:{s:1:\"a\";i:233;s:1:\"b\";s:33:\"radiology.critical_finding.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:14;i:2;i:15;i:3;i:16;}}i:233;a:4:{s:1:\"a\";i:234;s:1:\"b\";s:33:\"radiology.critical_finding.notify\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:14;i:2;i:15;i:3;i:16;}}i:234;a:4:{s:1:\"a\";i:235;s:1:\"b\";s:38:\"radiology.critical_finding.acknowledge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:14;i:3;i:15;i:4;i:16;}}i:235;a:4:{s:1:\"a\";i:236;s:1:\"b\";s:19:\"radiology.pacs.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:16;i:3;i:17;}}i:236;a:4:{s:1:\"a\";i:237;s:1:\"b\";s:21:\"radiology.pacs.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:16;i:3;i:17;}}i:237;a:4:{s:1:\"a\";i:238;s:1:\"b\";s:20:\"radiology.dicom.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:16;i:3;i:17;}}i:238;a:4:{s:1:\"a\";i:239;s:1:\"b\";s:22:\"radiology.dicom.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:16;i:3;i:17;}}i:239;a:4:{s:1:\"a\";i:240;s:1:\"b\";s:25:\"radiology.settings.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:16;}}i:240;a:4:{s:1:\"a\";i:241;s:1:\"b\";s:23:\"pharmacy.dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:10:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:18;i:4;i:19;i:5;i:20;i:6;i:21;i:7;i:22;i:8;i:23;i:9;i:24;}}i:241;a:4:{s:1:\"a\";i:242;s:1:\"b\";s:24:\"pharmacy.medication.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:2;i:2;i:18;i:3;i:19;i:4;i:20;i:5;i:21;i:6;i:22;i:7;i:23;i:8;i:24;}}i:242;a:4:{s:1:\"a\";i:243;s:1:\"b\";s:26:\"pharmacy.medication.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:22;i:3;i:24;}}i:243;a:4:{s:1:\"a\";i:244;s:1:\"b\";s:26:\"pharmacy.medication.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:22;i:3;i:24;}}i:244;a:4:{s:1:\"a\";i:245;s:1:\"b\";s:21:\"pharmacy.generic.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:2;i:2;i:18;i:3;i:19;i:4;i:20;i:5;i:21;i:6;i:22;i:7;i:23;i:8;i:24;}}i:245;a:4:{s:1:\"a\";i:246;s:1:\"b\";s:23:\"pharmacy.generic.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:22;i:3;i:24;}}i:246;a:4:{s:1:\"a\";i:247;s:1:\"b\";s:23:\"pharmacy.generic.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:22;i:3;i:24;}}i:247;a:4:{s:1:\"a\";i:248;s:1:\"b\";s:19:\"pharmacy.brand.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:2;i:2;i:18;i:3;i:19;i:4;i:20;i:5;i:21;i:6;i:22;i:7;i:23;i:8;i:24;}}i:248;a:4:{s:1:\"a\";i:249;s:1:\"b\";s:21:\"pharmacy.brand.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:22;i:3;i:24;}}i:249;a:4:{s:1:\"a\";i:250;s:1:\"b\";s:21:\"pharmacy.brand.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:22;i:3;i:24;}}i:250;a:4:{s:1:\"a\";i:251;s:1:\"b\";s:26:\"pharmacy.prescription.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:3;i:2;i:18;i:3;i:19;i:4;i:20;i:5;i:21;i:6;i:22;}}i:251;a:4:{s:1:\"a\";i:252;s:1:\"b\";s:28:\"pharmacy.prescription.review\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:20;i:2;i:21;i:3;i:22;}}i:252;a:4:{s:1:\"a\";i:253;s:1:\"b\";s:24:\"pharmacy.dispensing.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:19;i:4;i:20;i:5;i:21;i:6;i:22;}}i:253;a:4:{s:1:\"a\";i:254;s:1:\"b\";s:26:\"pharmacy.dispensing.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:19;i:2;i:20;i:3;i:21;i:4;i:22;}}i:254;a:4:{s:1:\"a\";i:255;s:1:\"b\";s:26:\"pharmacy.dispensing.verify\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:20;i:2;i:21;i:3;i:22;}}i:255;a:4:{s:1:\"a\";i:256;s:1:\"b\";s:26:\"pharmacy.dispensing.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:20;i:2;i:21;i:3;i:22;}}i:256;a:4:{s:1:\"a\";i:257;s:1:\"b\";s:26:\"pharmacy.dispensing.return\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:20;i:2;i:21;i:3;i:22;}}i:257;a:4:{s:1:\"a\";i:258;s:1:\"b\";s:19:\"pharmacy.stock.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:19;i:3;i:20;i:4;i:21;i:5;i:22;i:6;i:23;i:7;i:24;}}i:258;a:4:{s:1:\"a\";i:259;s:1:\"b\";s:22:\"pharmacy.stock.receive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:22;i:2;i:23;}}i:259;a:4:{s:1:\"a\";i:260;s:1:\"b\";s:23:\"pharmacy.stock.transfer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:22;i:2;i:23;}}i:260;a:4:{s:1:\"a\";i:261;s:1:\"b\";s:21:\"pharmacy.stock.adjust\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:22;i:2;i:23;}}i:261;a:4:{s:1:\"a\";i:262;s:1:\"b\";s:20:\"pharmacy.stock.count\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:22;i:2;i:23;}}i:262;a:4:{s:1:\"a\";i:263;s:1:\"b\";s:19:\"pharmacy.batch.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:19;i:3;i:20;i:4;i:21;i:5;i:22;i:6;i:23;i:7;i:24;}}i:263;a:4:{s:1:\"a\";i:264;s:1:\"b\";s:21:\"pharmacy.batch.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:22;i:2;i:23;}}i:264;a:4:{s:1:\"a\";i:265;s:1:\"b\";s:21:\"pharmacy.batch.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:22;i:2;i:23;}}i:265;a:4:{s:1:\"a\";i:266;s:1:\"b\";s:20:\"pharmacy.expiry.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:19;i:3;i:20;i:4;i:21;i:5;i:22;i:6;i:23;i:7;i:24;}}i:266;a:4:{s:1:\"a\";i:267;s:1:\"b\";s:26:\"pharmacy.quarantine.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:20;i:3;i:21;i:4;i:22;i:5;i:23;i:6;i:24;}}i:267;a:4:{s:1:\"a\";i:268;s:1:\"b\";s:26:\"pharmacy.substitution.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:20;i:3;i:21;i:4;i:22;}}i:268;a:4:{s:1:\"a\";i:269;s:1:\"b\";s:29:\"pharmacy.substitution.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:21;i:3;i:22;}}i:269;a:4:{s:1:\"a\";i:270;s:1:\"b\";s:26:\"pharmacy.safety_alert.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:19;i:4;i:20;i:5;i:21;i:6;i:22;i:7;i:24;}}i:270;a:4:{s:1:\"a\";i:271;s:1:\"b\";s:30:\"pharmacy.safety_alert.override\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:20;i:3;i:21;i:4;i:22;}}i:271;a:4:{s:1:\"a\";i:272;s:1:\"b\";s:29:\"pharmacy.controlled_drug.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:20;i:3;i:21;i:4;i:22;i:5;i:24;}}i:272;a:4:{s:1:\"a\";i:273;s:1:\"b\";s:31:\"pharmacy.controlled_drug.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:21;i:3;i:22;i:4;i:24;}}i:273;a:4:{s:1:\"a\";i:274;s:1:\"b\";s:20:\"pharmacy.recall.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:21;i:3;i:22;i:4;i:24;}}i:274;a:4:{s:1:\"a\";i:275;s:1:\"b\";s:22:\"pharmacy.recall.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:22;i:3;i:24;}}i:275;a:4:{s:1:\"a\";i:276;s:1:\"b\";s:21:\"pharmacy.reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:20;i:3;i:21;i:4;i:22;i:5;i:24;}}i:276;a:4:{s:1:\"a\";i:277;s:1:\"b\";s:23:\"pharmacy.reports.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:21;i:2;i:22;i:3;i:24;}}i:277;a:4:{s:1:\"a\";i:278;s:1:\"b\";s:24:\"pharmacy.settings.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:22;i:3;i:24;}}i:278;a:4:{s:1:\"a\";i:279;s:1:\"b\";s:18:\"ipd.dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:11:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:27;i:5;i:28;i:6;i:29;i:7;i:30;i:8;i:31;i:9;i:32;i:10;i:33;}}i:279;a:4:{s:1:\"a\";i:280;s:1:\"b\";s:18:\"ipd.admission.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:11:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:27;i:5;i:28;i:6;i:29;i:7;i:30;i:8;i:31;i:9;i:32;i:10;i:33;}}i:280;a:4:{s:1:\"a\";i:281;s:1:\"b\";s:20:\"ipd.admission.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:27;i:4;i:28;}}i:281;a:4:{s:1:\"a\";i:282;s:1:\"b\";s:20:\"ipd.admission.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:27;i:3;i:28;}}i:282;a:4:{s:1:\"a\";i:283;s:1:\"b\";s:21:\"ipd.admission.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:28;}}i:283;a:4:{s:1:\"a\";i:284;s:1:\"b\";s:20:\"ipd.admission.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:27;i:3;i:28;}}i:284;a:4:{s:1:\"a\";i:285;s:1:\"b\";s:13:\"ipd.ward.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:28;i:3;i:29;}}i:285;a:4:{s:1:\"a\";i:286;s:1:\"b\";s:15:\"ipd.ward.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:29;}}i:286;a:4:{s:1:\"a\";i:287;s:1:\"b\";s:15:\"ipd.ward.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:29;}}i:287;a:4:{s:1:\"a\";i:288;s:1:\"b\";s:13:\"ipd.room.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:28;i:3;i:29;}}i:288;a:4:{s:1:\"a\";i:289;s:1:\"b\";s:15:\"ipd.room.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:29;}}i:289;a:4:{s:1:\"a\";i:290;s:1:\"b\";s:15:\"ipd.room.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:29;}}i:290;a:4:{s:1:\"a\";i:291;s:1:\"b\";s:12:\"ipd.bed.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:11:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:27;i:5;i:28;i:6;i:29;i:7;i:30;i:8;i:31;i:9;i:32;i:10;i:33;}}i:291;a:4:{s:1:\"a\";i:292;s:1:\"b\";s:14:\"ipd.bed.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:29;}}i:292;a:4:{s:1:\"a\";i:293;s:1:\"b\";s:14:\"ipd.bed.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:29;}}i:293;a:4:{s:1:\"a\";i:294;s:1:\"b\";s:13:\"ipd.bed.block\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:29;}}i:294;a:4:{s:1:\"a\";i:295;s:1:\"b\";s:15:\"ipd.bed.unblock\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:29;}}i:295;a:4:{s:1:\"a\";i:296;s:1:\"b\";s:15:\"ipd.bed.reserve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:27;i:3;i:28;i:4;i:29;}}i:296;a:4:{s:1:\"a\";i:297;s:1:\"b\";s:16:\"ipd.bed.allocate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:28;i:3;i:29;}}i:297;a:4:{s:1:\"a\";i:298;s:1:\"b\";s:15:\"ipd.bed.release\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:28;i:3;i:29;}}i:298;a:4:{s:1:\"a\";i:299;s:1:\"b\";s:17:\"ipd.transfer.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:28;i:5;i:29;i:6;i:30;i:7;i:31;i:8;i:32;}}i:299;a:4:{s:1:\"a\";i:300;s:1:\"b\";s:19:\"ipd.transfer.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:28;i:5;i:29;}}i:300;a:4:{s:1:\"a\";i:301;s:1:\"b\";s:20:\"ipd.transfer.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:28;i:3;i:29;}}i:301;a:4:{s:1:\"a\";i:302;s:1:\"b\";s:21:\"ipd.transfer.complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:28;i:3;i:29;}}i:302;a:4:{s:1:\"a\";i:303;s:1:\"b\";s:19:\"ipd.transfer.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:28;}}i:303;a:4:{s:1:\"a\";i:304;s:1:\"b\";s:18:\"ipd.discharge.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:28;i:5;i:30;i:6;i:31;i:7;i:32;}}i:304;a:4:{s:1:\"a\";i:305;s:1:\"b\";s:20:\"ipd.discharge.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:28;}}i:305;a:4:{s:1:\"a\";i:306;s:1:\"b\";s:21:\"ipd.discharge.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:28;}}i:306;a:4:{s:1:\"a\";i:307;s:1:\"b\";s:22:\"ipd.discharge.complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:28;}}i:307;a:4:{s:1:\"a\";i:308;s:1:\"b\";s:14:\"ipd.leave.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:28;i:5;i:30;i:6;i:31;i:7;i:32;}}i:308;a:4:{s:1:\"a\";i:309;s:1:\"b\";s:16:\"ipd.leave.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:28;}}i:309;a:4:{s:1:\"a\";i:310;s:1:\"b\";s:17:\"ipd.leave.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:28;}}i:310;a:4:{s:1:\"a\";i:311;s:1:\"b\";s:18:\"ipd.leave.complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:28;}}i:311;a:4:{s:1:\"a\";i:312;s:1:\"b\";s:16:\"ipd.reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:27;i:3;i:28;i:4;i:29;i:5;i:33;}}i:312;a:4:{s:1:\"a\";i:313;s:1:\"b\";s:18:\"ipd.reports.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:28;}}i:313;a:4:{s:1:\"a\";i:314;s:1:\"b\";s:19:\"ipd.settings.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:314;a:4:{s:1:\"a\";i:315;s:1:\"b\";s:14:\"ipd.audit.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:315;a:4:{s:1:\"a\";i:316;s:1:\"b\";s:14:\"insurance.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:316;a:4:{s:1:\"a\";i:317;s:1:\"b\";s:16:\"insurance.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:317;a:4:{s:1:\"a\";i:318;s:1:\"b\";s:16:\"insurance.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:318;a:4:{s:1:\"a\";i:319;s:1:\"b\";s:16:\"insurance.submit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:319;a:4:{s:1:\"a\";i:320;s:1:\"b\";s:17:\"insurance.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:320;a:4:{s:1:\"a\";i:321;s:1:\"b\";s:12:\"finance.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:26;}}i:321;a:4:{s:1:\"a\";i:322;s:1:\"b\";s:14:\"finance.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:26;}}i:322;a:4:{s:1:\"a\";i:323;s:1:\"b\";s:14:\"finance.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:26;}}i:323;a:4:{s:1:\"a\";i:324;s:1:\"b\";s:12:\"finance.post\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:26;}}i:324;a:4:{s:1:\"a\";i:325;s:1:\"b\";s:13:\"finance.close\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:26;}}i:325;a:4:{s:1:\"a\";i:326;s:1:\"b\";s:14:\"inventory.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:326;a:4:{s:1:\"a\";i:327;s:1:\"b\";s:16:\"inventory.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:327;a:4:{s:1:\"a\";i:328;s:1:\"b\";s:16:\"inventory.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:328;a:4:{s:1:\"a\";i:329;s:1:\"b\";s:16:\"inventory.adjust\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:329;a:4:{s:1:\"a\";i:330;s:1:\"b\";s:18:\"inventory.transfer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:330;a:4:{s:1:\"a\";i:331;s:1:\"b\";s:16:\"procurement.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:331;a:4:{s:1:\"a\";i:332;s:1:\"b\";s:18:\"procurement.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:332;a:4:{s:1:\"a\";i:333;s:1:\"b\";s:18:\"procurement.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:333;a:4:{s:1:\"a\";i:334;s:1:\"b\";s:19:\"procurement.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:334;a:4:{s:1:\"a\";i:335;s:1:\"b\";s:7:\"hr.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:335;a:4:{s:1:\"a\";i:336;s:1:\"b\";s:9:\"hr.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:336;a:4:{s:1:\"a\";i:337;s:1:\"b\";s:9:\"hr.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:337;a:4:{s:1:\"a\";i:338;s:1:\"b\";s:13:\"hr.attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:338;a:4:{s:1:\"a\";i:339;s:1:\"b\";s:10:\"hr.payroll\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:339;a:4:{s:1:\"a\";i:340;s:1:\"b\";s:14:\"reporting.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:34:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;i:7;i:8;i:8;i:9;i:9;i:10;i:10;i:11;i:11;i:12;i:12;i:13;i:13;i:14;i:14;i:15;i:15;i:16;i:16;i:17;i:17;i:18;i:18;i:19;i:19;i:20;i:20;i:21;i:21;i:22;i:22;i:23;i:23;i:24;i:24;i:25;i:25;i:26;i:26;i:27;i:27;i:28;i:28;i:29;i:29;i:30;i:30;i:31;i:31;i:32;i:32;i:33;i:33;i:34;}}i:340;a:4:{s:1:\"a\";i:341;s:1:\"b\";s:16:\"reporting.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:14:{i:0;i:1;i:1;i:2;i:2;i:11;i:3;i:15;i:4;i:16;i:5;i:21;i:6;i:22;i:7;i:24;i:8;i:26;i:9;i:28;i:10;i:31;i:11;i:32;i:12;i:33;i:13;i:34;}}i:341;a:4:{s:1:\"a\";i:342;s:1:\"b\";s:15:\"reporting.print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:2;i:2;i:11;i:3;i:16;i:4;i:22;i:5;i:24;i:6;i:26;i:7;i:33;i:8;i:34;}}i:342;a:4:{s:1:\"a\";i:343;s:1:\"b\";s:10:\"audit.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:343;a:4:{s:1:\"a\";i:344;s:1:\"b\";s:13:\"activity.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:344;a:4:{s:1:\"a\";i:345;s:1:\"b\";s:15:\"activity.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:345;a:4:{s:1:\"a\";i:346;s:1:\"b\";s:19:\"security.event.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:346;a:4:{s:1:\"a\";i:347;s:1:\"b\";s:22:\"security.event.resolve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:347;a:4:{s:1:\"a\";i:348;s:1:\"b\";s:18:\"login.history.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:348;a:4:{s:1:\"a\";i:349;s:1:\"b\";s:18:\"system.health.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:349;a:4:{s:1:\"a\";i:350;s:1:\"b\";s:17:\"system.queue.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:350;a:4:{s:1:\"a\";i:351;s:1:\"b\";s:21:\"system.scheduler.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:351;a:4:{s:1:\"a\";i:352;s:1:\"b\";s:13:\"workflow.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:26;}}i:352;a:4:{s:1:\"a\";i:353;s:1:\"b\";s:12:\"workflow.act\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:26;}}i:353;a:4:{s:1:\"a\";i:354;s:1:\"b\";s:15:\"workflow.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:354;a:4:{s:1:\"a\";i:355;s:1:\"b\";s:13:\"settings.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:355;a:4:{s:1:\"a\";i:356;s:1:\"b\";s:15:\"settings.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:356;a:4:{s:1:\"a\";i:357;s:1:\"b\";s:17:\"notification.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:357;a:4:{s:1:\"a\";i:358;s:1:\"b\";s:19:\"notification.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:358;a:4:{s:1:\"a\";i:359;s:1:\"b\";s:9:\"file.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:359;a:4:{s:1:\"a\";i:360;s:1:\"b\";s:11:\"file.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:360;a:4:{s:1:\"a\";i:361;s:1:\"b\";s:22:\"nursing.dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:361;a:4:{s:1:\"a\";i:362;s:1:\"b\";s:23:\"nursing.assignment.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;i:5;i:33;i:6;i:34;}}i:362;a:4:{s:1:\"a\";i:363;s:1:\"b\";s:25:\"nursing.assignment.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:31;i:2;i:32;i:3;i:33;}}i:363;a:4:{s:1:\"a\";i:364;s:1:\"b\";s:25:\"nursing.assignment.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:31;i:2;i:32;i:3;i:33;}}i:364;a:4:{s:1:\"a\";i:365;s:1:\"b\";s:18:\"nursing.shift.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:32;i:2;i:33;i:3;i:34;}}i:365;a:4:{s:1:\"a\";i:366;s:1:\"b\";s:20:\"nursing.shift.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:32;i:2;i:33;i:3;i:34;}}i:366;a:4:{s:1:\"a\";i:367;s:1:\"b\";s:23:\"nursing.assessment.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:367;a:4:{s:1:\"a\";i:368;s:1:\"b\";s:25:\"nursing.assessment.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:368;a:4:{s:1:\"a\";i:369;s:1:\"b\";s:25:\"nursing.assessment.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:369;a:4:{s:1:\"a\";i:370;s:1:\"b\";s:27:\"nursing.assessment.finalize\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;i:5;i:33;}}i:370;a:4:{s:1:\"a\";i:371;s:1:\"b\";s:24:\"nursing.observation.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:371;a:4:{s:1:\"a\";i:372;s:1:\"b\";s:26:\"nursing.observation.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:372;a:4:{s:1:\"a\";i:373;s:1:\"b\";s:19:\"nursing.vitals.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:373;a:4:{s:1:\"a\";i:374;s:1:\"b\";s:21:\"nursing.vitals.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:374;a:4:{s:1:\"a\";i:375;s:1:\"b\";s:21:\"nursing.vitals.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:375;a:4:{s:1:\"a\";i:376;s:1:\"b\";s:22:\"nursing.care_plan.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:376;a:4:{s:1:\"a\";i:377;s:1:\"b\";s:24:\"nursing.care_plan.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:31;i:2;i:32;}}i:377;a:4:{s:1:\"a\";i:378;s:1:\"b\";s:24:\"nursing.care_plan.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:31;i:2;i:32;}}i:378;a:4:{s:1:\"a\";i:379;s:1:\"b\";s:26:\"nursing.care_plan.complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:32;i:2;i:33;}}i:379;a:4:{s:1:\"a\";i:380;s:1:\"b\";s:22:\"nursing.diagnosis.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:380;a:4:{s:1:\"a\";i:381;s:1:\"b\";s:24:\"nursing.diagnosis.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:31;i:2;i:32;}}i:381;a:4:{s:1:\"a\";i:382;s:1:\"b\";s:24:\"nursing.diagnosis.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:31;i:2;i:32;}}i:382;a:4:{s:1:\"a\";i:383;s:1:\"b\";s:17:\"nursing.task.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:383;a:4:{s:1:\"a\";i:384;s:1:\"b\";s:19:\"nursing.task.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:384;a:4:{s:1:\"a\";i:385;s:1:\"b\";s:21:\"nursing.task.complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:385;a:4:{s:1:\"a\";i:386;s:1:\"b\";s:16:\"nursing.mar.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:20;i:4;i:30;i:5;i:31;i:6;i:32;i:7;i:33;i:8;i:34;}}i:386;a:4:{s:1:\"a\";i:387;s:1:\"b\";s:22:\"nursing.mar.administer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:387;a:4:{s:1:\"a\";i:388;s:1:\"b\";s:41:\"nursing.mar.administer_without_dispensing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:32;}}i:388;a:4:{s:1:\"a\";i:389;s:1:\"b\";s:16:\"nursing.mar.hold\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:389;a:4:{s:1:\"a\";i:390;s:1:\"b\";s:18:\"nursing.mar.refuse\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:390;a:4:{s:1:\"a\";i:391;s:1:\"b\";s:16:\"nursing.mar.omit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:391;a:4:{s:1:\"a\";i:392;s:1:\"b\";s:19:\"nursing.mar.correct\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:31;i:2;i:32;i:3;i:33;}}i:392;a:4:{s:1:\"a\";i:393;s:1:\"b\";s:15:\"nursing.iv.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:393;a:4:{s:1:\"a\";i:394;s:1:\"b\";s:17:\"nursing.iv.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:394;a:4:{s:1:\"a\";i:395;s:1:\"b\";s:17:\"nursing.iv.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:395;a:4:{s:1:\"a\";i:396;s:1:\"b\";s:19:\"nursing.device.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:396;a:4:{s:1:\"a\";i:397;s:1:\"b\";s:21:\"nursing.device.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:397;a:4:{s:1:\"a\";i:398;s:1:\"b\";s:21:\"nursing.device.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:398;a:4:{s:1:\"a\";i:399;s:1:\"b\";s:18:\"nursing.wound.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:399;a:4:{s:1:\"a\";i:400;s:1:\"b\";s:20:\"nursing.wound.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:400;a:4:{s:1:\"a\";i:401;s:1:\"b\";s:20:\"nursing.wound.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:401;a:4:{s:1:\"a\";i:402;s:1:\"b\";s:22:\"nursing.education.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;i:5;i:33;i:6;i:34;}}i:402;a:4:{s:1:\"a\";i:403;s:1:\"b\";s:24:\"nursing.education.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:403;a:4:{s:1:\"a\";i:404;s:1:\"b\";s:18:\"nursing.notes.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:404;a:4:{s:1:\"a\";i:405;s:1:\"b\";s:20:\"nursing.notes.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:405;a:4:{s:1:\"a\";i:406;s:1:\"b\";s:22:\"nursing.notes.finalize\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:406;a:4:{s:1:\"a\";i:407;s:1:\"b\";s:19:\"nursing.notes.amend\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:31;i:2;i:32;i:3;i:33;}}i:407;a:4:{s:1:\"a\";i:408;s:1:\"b\";s:21:\"nursing.handover.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:408;a:4:{s:1:\"a\";i:409;s:1:\"b\";s:23:\"nursing.handover.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:409;a:4:{s:1:\"a\";i:410;s:1:\"b\";s:28:\"nursing.handover.acknowledge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:410;a:4:{s:1:\"a\";i:411;s:1:\"b\";s:23:\"nursing.escalation.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:411;a:4:{s:1:\"a\";i:412;s:1:\"b\";s:25:\"nursing.escalation.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:4;i:2;i:30;i:3;i:31;i:4;i:32;}}i:412;a:4:{s:1:\"a\";i:413;s:1:\"b\";s:30:\"nursing.escalation.acknowledge\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:31;i:3;i:32;i:4;i:33;}}i:413;a:4:{s:1:\"a\";i:414;s:1:\"b\";s:26:\"nursing.escalation.resolve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:32;i:2;i:33;}}i:414;a:4:{s:1:\"a\";i:415;s:1:\"b\";s:22:\"nursing.discharge.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:30;i:4;i:31;i:5;i:32;i:6;i:33;i:7;i:34;}}i:415;a:4:{s:1:\"a\";i:416;s:1:\"b\";s:26:\"nursing.discharge.complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:31;i:2;i:32;}}i:416;a:4:{s:1:\"a\";i:417;s:1:\"b\";s:20:\"nursing.reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:32;i:2;i:33;i:3;i:34;}}i:417;a:4:{s:1:\"a\";i:418;s:1:\"b\";s:22:\"nursing.reports.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:33;i:2;i:34;}}i:418;a:4:{s:1:\"a\";i:419;s:1:\"b\";s:18:\"nursing.audit.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:32;i:2;i:33;i:3;i:34;}}i:419;a:4:{s:1:\"a\";i:420;s:1:\"b\";s:23:\"nursing.settings.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:34;}}}s:5:\"roles\";a:34:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:14:\"hospital_admin\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:6:\"doctor\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:5:\"nurse\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:12:\"receptionist\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:9;s:1:\"b\";s:16:\"lab_receptionist\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:12;s:1:\"b\";s:22:\"radiology_receptionist\";s:1:\"c\";s:3:\"web\";}i:7;a:3:{s:1:\"a\";i:27;s:1:\"b\";s:17:\"admission_officer\";s:1:\"c\";s:3:\"web\";}i:8;a:3:{s:1:\"a\";i:28;s:1:\"b\";s:15:\"ipd_coordinator\";s:1:\"c\";s:3:\"web\";}i:9;a:3:{s:1:\"a\";i:29;s:1:\"b\";s:12:\"ward_manager\";s:1:\"c\";s:3:\"web\";}i:10;a:3:{s:1:\"a\";i:30;s:1:\"b\";s:11:\"staff_nurse\";s:1:\"c\";s:3:\"web\";}i:11;a:3:{s:1:\"a\";i:31;s:1:\"b\";s:18:\"senior_staff_nurse\";s:1:\"c\";s:3:\"web\";}i:12;a:3:{s:1:\"a\";i:32;s:1:\"b\";s:12:\"charge_nurse\";s:1:\"c\";s:3:\"web\";}i:13;a:3:{s:1:\"a\";i:33;s:1:\"b\";s:18:\"nursing_supervisor\";s:1:\"c\";s:3:\"web\";}i:14;a:3:{s:1:\"a\";i:20;s:1:\"b\";s:10:\"pharmacist\";s:1:\"c\";s:3:\"web\";}i:15;a:3:{s:1:\"a\";i:21;s:1:\"b\";s:17:\"senior_pharmacist\";s:1:\"c\";s:3:\"web\";}i:16;a:3:{s:1:\"a\";i:22;s:1:\"b\";s:16:\"pharmacy_manager\";s:1:\"c\";s:3:\"web\";}i:17;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:14:\"lab_technician\";s:1:\"c\";s:3:\"web\";}i:18;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:21:\"senior_lab_technician\";s:1:\"c\";s:3:\"web\";}i:19;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:11:\"pathologist\";s:1:\"c\";s:3:\"web\";}i:20;a:3:{s:1:\"a\";i:11;s:1:\"b\";s:11:\"lab_manager\";s:1:\"c\";s:3:\"web\";}i:21;a:3:{s:1:\"a\";i:14;s:1:\"b\";s:11:\"radiologist\";s:1:\"c\";s:3:\"web\";}i:22;a:3:{s:1:\"a\";i:15;s:1:\"b\";s:18:\"senior_radiologist\";s:1:\"c\";s:3:\"web\";}i:23;a:3:{s:1:\"a\";i:16;s:1:\"b\";s:17:\"radiology_manager\";s:1:\"c\";s:3:\"web\";}i:24;a:3:{s:1:\"a\";i:25;s:1:\"b\";s:7:\"cashier\";s:1:\"c\";s:3:\"web\";}i:25;a:3:{s:1:\"a\";i:26;s:1:\"b\";s:10:\"accountant\";s:1:\"c\";s:3:\"web\";}i:26;a:3:{s:1:\"a\";i:10;s:1:\"b\";s:12:\"phlebotomist\";s:1:\"c\";s:3:\"web\";}i:27;a:3:{s:1:\"a\";i:13;s:1:\"b\";s:20:\"radiology_technician\";s:1:\"c\";s:3:\"web\";}i:28;a:3:{s:1:\"a\";i:17;s:1:\"b\";s:18:\"pacs_administrator\";s:1:\"c\";s:3:\"web\";}i:29;a:3:{s:1:\"a\";i:18;s:1:\"b\";s:21:\"pharmacy_receptionist\";s:1:\"c\";s:3:\"web\";}i:30;a:3:{s:1:\"a\";i:19;s:1:\"b\";s:19:\"pharmacy_technician\";s:1:\"c\";s:3:\"web\";}i:31;a:3:{s:1:\"a\";i:23;s:1:\"b\";s:11:\"storekeeper\";s:1:\"c\";s:3:\"web\";}i:32;a:3:{s:1:\"a\";i:24;s:1:\"b\";s:22:\"pharmacy_administrator\";s:1:\"c\";s:3:\"web\";}i:33;a:3:{s:1:\"a\";i:34;s:1:\"b\";s:21:\"nursing_administrator\";s:1:\"c\";s:3:\"web\";}}}',1790309668);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `clinical_number_counters`
--

DROP TABLE IF EXISTS `clinical_number_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_number_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `counter_type` enum('clinical_order','prescription') NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinical_number_counter_unique` (`company_id`,`branch_id`,`counter_type`),
  KEY `clinical_number_counters_branch_id_foreign` (`branch_id`),
  CONSTRAINT `clinical_number_counters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_number_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_number_counters`
--

LOCK TABLES `clinical_number_counters` WRITE;
/*!40000 ALTER TABLE `clinical_number_counters` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `clinical_number_counters` VALUES
(4,1,2,'prescription',1,'2026-09-23 22:22:44','2026-09-23 22:22:44');
/*!40000 ALTER TABLE `clinical_number_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `clinical_order_items`
--

DROP TABLE IF EXISTS `clinical_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clinical_order_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_code` varchar(255) DEFAULT NULL,
  `quantity` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinical_order_items_clinical_order_id_foreign` (`clinical_order_id`),
  KEY `clinical_order_items_company_id_foreign` (`company_id`),
  CONSTRAINT `clinical_order_items_clinical_order_id_foreign` FOREIGN KEY (`clinical_order_id`) REFERENCES `clinical_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_order_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_order_items`
--

LOCK TABLES `clinical_order_items` WRITE;
/*!40000 ALTER TABLE `clinical_order_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `clinical_order_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `clinical_orders`
--

DROP TABLE IF EXISTS `clinical_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `provider_id` bigint(20) unsigned DEFAULT NULL,
  `order_type` varchar(255) NOT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'routine',
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `ordered_at` timestamp NULL DEFAULT NULL,
  `ordered_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinical_orders_order_number_unique` (`order_number`),
  KEY `clinical_orders_company_id_foreign` (`company_id`),
  KEY `clinical_orders_branch_id_foreign` (`branch_id`),
  KEY `clinical_orders_patient_id_foreign` (`patient_id`),
  KEY `clinical_orders_provider_id_foreign` (`provider_id`),
  KEY `clinical_orders_ordered_by_foreign` (`ordered_by`),
  KEY `clinical_orders_cancelled_by_foreign` (`cancelled_by`),
  KEY `clinical_orders_encounter_id_index` (`encounter_id`),
  CONSTRAINT `clinical_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_orders_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_orders_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_orders_ordered_by_foreign` FOREIGN KEY (`ordered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_orders_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_orders_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_orders`
--

LOCK TABLES `clinical_orders` WRITE;
/*!40000 ALTER TABLE `clinical_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `clinical_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `logo` text DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `subscription_ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_code_unique` (`code`),
  UNIQUE KEY `companies_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `companies` VALUES
(1,'HealthNexus Hospital Group','HN-HG','healthnexus-hospital-group','info@healthnexus.test','+1-555-0100','123 Healthcare Blvd, Medical District',NULL,NULL,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(2) NOT NULL,
  `currency_code` varchar(3) DEFAULT NULL,
  `currency_symbol` varchar(255) DEFAULT NULL,
  `phone_code` varchar(10) DEFAULT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `date_format` varchar(255) DEFAULT NULL,
  `time_format` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `countries` VALUES
(1,'Bangladesh','BD','BDT','৳','+880','Asia/Dhaka','d/m/Y','H:i',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,'United States','US','USD','$','+1','America/New_York','m/d/Y','h:i A',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,'United Kingdom','GB','GBP','£','+44','Europe/London','d/m/Y','H:i',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,'India','IN','INR','₹','+91','Asia/Kolkata','d/m/Y','H:i',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,'Canada','CA','CAD','C$','+1','America/Toronto','Y-m-d','H:i',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(3) NOT NULL,
  `symbol` varchar(255) NOT NULL,
  `decimal_places` varchar(1) NOT NULL DEFAULT '2',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currencies_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `currencies` VALUES
(1,'Bangladeshi Taka','BDT','৳','2',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,'US Dollar','USD','$','2',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,'British Pound','GBP','£','2',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,'Indian Rupee','INR','₹','2',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,'Canadian Dollar','CAD','C$','2',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(6,'Euro','EUR','€','2',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `head_of_department` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `departments_branch_id_foreign` (`branch_id`),
  CONSTRAINT `departments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `diagnoses`
--

DROP TABLE IF EXISTS `diagnoses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `diagnoses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `code_type` varchar(255) DEFAULT NULL,
  `coding_system` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `status` enum('provisional','confirmed','rule_out','resolved') NOT NULL DEFAULT 'confirmed',
  `diagnosis_type` enum('primary','secondary','differential','historical') NOT NULL DEFAULT 'primary',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diagnoses_branch_id_foreign` (`branch_id`),
  KEY `diagnoses_appointment_id_foreign` (`appointment_id`),
  KEY `diagnoses_patient_id_foreign` (`patient_id`),
  KEY `diagnoses_doctor_id_foreign` (`doctor_id`),
  KEY `diagnoses_recorded_by_foreign` (`recorded_by`),
  KEY `diagnoses_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `diagnoses_company_id_appointment_id_index` (`company_id`,`appointment_id`),
  KEY `diagnoses_encounter_id_index` (`encounter_id`),
  CONSTRAINT `diagnoses_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `diagnoses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diagnoses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diagnoses_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diagnoses_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `diagnoses_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diagnoses_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diagnoses`
--

LOCK TABLES `diagnoses` WRITE;
/*!40000 ALTER TABLE `diagnoses` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `diagnoses` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `doctor_schedules`
--

DROP TABLE IF EXISTS `doctor_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `provider_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `slot_duration_minutes` int(11) NOT NULL DEFAULT 15,
  `buffer_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `break_start_time` time DEFAULT NULL,
  `break_end_time` time DEFAULT NULL,
  `default_capacity_per_slot` int(10) unsigned NOT NULL DEFAULT 1,
  `overbooking_limit` int(10) unsigned NOT NULL DEFAULT 0,
  `appointment_type_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`appointment_type_ids`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_schedule_session_unique` (`company_id`,`doctor_id`,`day_of_week`,`start_time`),
  KEY `doctor_schedules_branch_id_foreign` (`branch_id`),
  KEY `doctor_schedules_doctor_id_foreign` (`doctor_id`),
  KEY `doctor_schedules_department_id_foreign` (`department_id`),
  KEY `doctor_schedules_provider_id_foreign` (`provider_id`),
  KEY `doctor_schedules_room_id_foreign` (`room_id`),
  KEY `doctor_schedules_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `doctor_schedules_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_schedules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_schedules_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctor_schedules_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_schedules_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctor_schedules_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `appointment_rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctor_schedules_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_schedules`
--

LOCK TABLES `doctor_schedules` WRITE;
/*!40000 ALTER TABLE `doctor_schedules` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `doctor_schedules` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_allergies`
--

DROP TABLE IF EXISTS `encounter_allergies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_allergies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `allergen_type` varchar(255) DEFAULT NULL,
  `allergen_name` varchar(255) NOT NULL,
  `reaction` varchar(255) DEFAULT NULL,
  `severity` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_allergies_patient_id_foreign` (`patient_id`),
  KEY `encounter_allergies_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_allergies_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_allergies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_allergies`
--

LOCK TABLES `encounter_allergies` WRITE;
/*!40000 ALTER TABLE `encounter_allergies` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_allergies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_amendments`
--

DROP TABLE IF EXISTS `encounter_amendments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_amendments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `amendment_type` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `content` text NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_amendments_patient_id_foreign` (`patient_id`),
  KEY `encounter_amendments_company_id_foreign` (`company_id`),
  KEY `encounter_amendments_created_by_foreign` (`created_by`),
  KEY `encounter_amendments_approved_by_foreign` (`approved_by`),
  KEY `encounter_amendments_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_amendments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_amendments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_amendments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_amendments_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_amendments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_amendments`
--

LOCK TABLES `encounter_amendments` WRITE;
/*!40000 ALTER TABLE `encounter_amendments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_amendments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_assessments`
--

DROP TABLE IF EXISTS `encounter_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `assessment_type` varchar(255) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_assessments_patient_id_foreign` (`patient_id`),
  KEY `encounter_assessments_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_assessments_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_assessments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_assessments`
--

LOCK TABLES `encounter_assessments` WRITE;
/*!40000 ALTER TABLE `encounter_assessments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_assessments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_complaints`
--

DROP TABLE IF EXISTS `encounter_complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_complaints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `complaint` varchar(255) NOT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `duration_unit` varchar(255) DEFAULT NULL,
  `onset` varchar(255) DEFAULT NULL,
  `severity` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_complaints_patient_id_foreign` (`patient_id`),
  KEY `encounter_complaints_company_id_foreign` (`company_id`),
  KEY `encounter_complaints_encounter_id_sort_order_index` (`encounter_id`,`sort_order`),
  CONSTRAINT `encounter_complaints_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_complaints_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_complaints_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_complaints`
--

LOCK TABLES `encounter_complaints` WRITE;
/*!40000 ALTER TABLE `encounter_complaints` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_complaints` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_counters`
--

DROP TABLE IF EXISTS `encounter_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `counter` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `encounter_counters_company_id_unique` (`company_id`),
  CONSTRAINT `encounter_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_counters`
--

LOCK TABLES `encounter_counters` WRITE;
/*!40000 ALTER TABLE `encounter_counters` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_documents`
--

DROP TABLE IF EXISTS `encounter_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_documents_patient_id_foreign` (`patient_id`),
  KEY `encounter_documents_company_id_foreign` (`company_id`),
  KEY `encounter_documents_created_by_foreign` (`created_by`),
  KEY `encounter_documents_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_documents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_documents_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_documents_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_documents`
--

LOCK TABLES `encounter_documents` WRITE;
/*!40000 ALTER TABLE `encounter_documents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_examinations`
--

DROP TABLE IF EXISTS `encounter_examinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_examinations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `findings` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_examinations_patient_id_foreign` (`patient_id`),
  KEY `encounter_examinations_company_id_foreign` (`company_id`),
  KEY `encounter_examinations_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_examinations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_examinations_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_examinations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_examinations`
--

LOCK TABLES `encounter_examinations` WRITE;
/*!40000 ALTER TABLE `encounter_examinations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_examinations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_follow_ups`
--

DROP TABLE IF EXISTS `encounter_follow_ups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_follow_ups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `follow_up_date` date NOT NULL,
  `follow_up_type` varchar(255) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_follow_ups_patient_id_foreign` (`patient_id`),
  KEY `encounter_follow_ups_created_by_foreign` (`created_by`),
  KEY `encounter_follow_ups_encounter_id_follow_up_date_index` (`encounter_id`,`follow_up_date`),
  CONSTRAINT `encounter_follow_ups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_follow_ups_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_follow_ups_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_follow_ups`
--

LOCK TABLES `encounter_follow_ups` WRITE;
/*!40000 ALTER TABLE `encounter_follow_ups` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_follow_ups` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_histories`
--

DROP TABLE IF EXISTS `encounter_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `history_type` varchar(255) NOT NULL,
  `onset` varchar(255) DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `severity` varchar(255) DEFAULT NULL,
  `associated_symptoms` text DEFAULT NULL,
  `aggravating_factors` text DEFAULT NULL,
  `relieving_factors` text DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_histories_patient_id_foreign` (`patient_id`),
  KEY `encounter_histories_company_id_foreign` (`company_id`),
  KEY `encounter_histories_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_histories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_histories_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_histories_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_histories`
--

LOCK TABLES `encounter_histories` WRITE;
/*!40000 ALTER TABLE `encounter_histories` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_histories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_instructions`
--

DROP TABLE IF EXISTS `encounter_instructions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_instructions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `instruction_type` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_instructions_patient_id_foreign` (`patient_id`),
  KEY `encounter_instructions_company_id_foreign` (`company_id`),
  KEY `encounter_instructions_created_by_foreign` (`created_by`),
  KEY `encounter_instructions_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_instructions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_instructions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_instructions_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_instructions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_instructions`
--

LOCK TABLES `encounter_instructions` WRITE;
/*!40000 ALTER TABLE `encounter_instructions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_instructions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_medication_histories`
--

DROP TABLE IF EXISTS `encounter_medication_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_medication_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(255) DEFAULT NULL,
  `frequency` varchar(255) DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_medication_histories_patient_id_foreign` (`patient_id`),
  KEY `encounter_medication_histories_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_medication_histories_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_medication_histories_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_medication_histories`
--

LOCK TABLES `encounter_medication_histories` WRITE;
/*!40000 ALTER TABLE `encounter_medication_histories` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_medication_histories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_notes`
--

DROP TABLE IF EXISTS `encounter_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `section` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_notes_patient_id_foreign` (`patient_id`),
  KEY `encounter_notes_company_id_foreign` (`company_id`),
  KEY `encounter_notes_created_by_foreign` (`created_by`),
  KEY `encounter_notes_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_notes_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_notes_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_notes`
--

LOCK TABLES `encounter_notes` WRITE;
/*!40000 ALTER TABLE `encounter_notes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_notes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_procedures`
--

DROP TABLE IF EXISTS `encounter_procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_procedures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `procedure_code` varchar(255) DEFAULT NULL,
  `procedure_name` varchar(255) NOT NULL,
  `procedure_date` date DEFAULT NULL,
  `provider_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_procedures_patient_id_foreign` (`patient_id`),
  KEY `encounter_procedures_company_id_foreign` (`company_id`),
  KEY `encounter_procedures_provider_id_foreign` (`provider_id`),
  KEY `encounter_procedures_recorded_by_foreign` (`recorded_by`),
  KEY `encounter_procedures_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_procedures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_procedures_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_procedures_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_procedures_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_procedures_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_procedures`
--

LOCK TABLES `encounter_procedures` WRITE;
/*!40000 ALTER TABLE `encounter_procedures` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_procedures` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_referrals`
--

DROP TABLE IF EXISTS `encounter_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_referrals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `referral_type` varchar(255) NOT NULL,
  `referred_to` varchar(255) DEFAULT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_referrals_patient_id_foreign` (`patient_id`),
  KEY `encounter_referrals_company_id_foreign` (`company_id`),
  KEY `encounter_referrals_created_by_foreign` (`created_by`),
  KEY `encounter_referrals_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_referrals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_referrals_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_referrals_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_referrals_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_referrals`
--

LOCK TABLES `encounter_referrals` WRITE;
/*!40000 ALTER TABLE `encounter_referrals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_referrals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_review_of_systems`
--

DROP TABLE IF EXISTS `encounter_review_of_systems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_review_of_systems` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `system_name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_review_of_systems_patient_id_foreign` (`patient_id`),
  KEY `encounter_review_of_systems_company_id_foreign` (`company_id`),
  KEY `encounter_review_of_systems_encounter_id_index` (`encounter_id`),
  CONSTRAINT `encounter_review_of_systems_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_review_of_systems_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_review_of_systems_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_review_of_systems`
--

LOCK TABLES `encounter_review_of_systems` WRITE;
/*!40000 ALTER TABLE `encounter_review_of_systems` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_review_of_systems` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_status_histories`
--

DROP TABLE IF EXISTS `encounter_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(255) DEFAULT NULL,
  `to_status` varchar(255) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_status_history_changed_by_foreign` (`changed_by`),
  KEY `encounter_status_history_encounter_id_created_at_index` (`encounter_id`,`created_at`),
  CONSTRAINT `encounter_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounter_status_history_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_status_histories`
--

LOCK TABLES `encounter_status_histories` WRITE;
/*!40000 ALTER TABLE `encounter_status_histories` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_status_histories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_template_sections`
--

DROP TABLE IF EXISTS `encounter_template_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_template_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_template_id` bigint(20) unsigned NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_template_sections_encounter_template_id_foreign` (`encounter_template_id`),
  CONSTRAINT `encounter_template_sections_encounter_template_id_foreign` FOREIGN KEY (`encounter_template_id`) REFERENCES `encounter_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_template_sections`
--

LOCK TABLES `encounter_template_sections` WRITE;
/*!40000 ALTER TABLE `encounter_template_sections` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_template_sections` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_templates`
--

DROP TABLE IF EXISTS `encounter_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_templates_created_by_foreign` (`created_by`),
  KEY `encounter_templates_company_id_specialty_index` (`company_id`,`specialty`),
  CONSTRAINT `encounter_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_templates`
--

LOCK TABLES `encounter_templates` WRITE;
/*!40000 ALTER TABLE `encounter_templates` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_templates` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_types`
--

DROP TABLE IF EXISTS `encounter_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `encounter_types_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `encounter_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_types`
--

LOCK TABLES `encounter_types` WRITE;
/*!40000 ALTER TABLE `encounter_types` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `encounter_types` VALUES
(1,1,'New Consultation','new_consultation',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(2,1,'Follow-up','follow_up',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(3,1,'Review','review',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(4,1,'Second Opinion','second_opinion',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(5,1,'Procedure','procedure',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(6,1,'Health Checkup','health_checkup',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(7,1,'Referral','referral',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(8,1,'Telemedicine','telemedicine',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(9,1,'Walk-in','walk_in',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(10,1,'Other','other',NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59');
/*!40000 ALTER TABLE `encounter_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounter_vitals`
--

DROP TABLE IF EXISTS `encounter_vitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_vitals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `recorded_at` timestamp NOT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `temperature_unit` varchar(255) DEFAULT NULL,
  `systolic` int(11) DEFAULT NULL,
  `diastolic` int(11) DEFAULT NULL,
  `bp_unit` varchar(255) DEFAULT NULL,
  `pulse_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `height` decimal(6,2) DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `oxygen_saturation` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_vitals_patient_id_foreign` (`patient_id`),
  KEY `encounter_vitals_recorded_by_foreign` (`recorded_by`),
  KEY `encounter_vitals_encounter_id_recorded_at_index` (`encounter_id`,`recorded_at`),
  CONSTRAINT `encounter_vitals_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_vitals_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_vitals_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounter_vitals`
--

LOCK TABLES `encounter_vitals` WRITE;
/*!40000 ALTER TABLE `encounter_vitals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounter_vitals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `encounters`
--

DROP TABLE IF EXISTS `encounters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_no` varchar(255) NOT NULL,
  `encounter_date` date DEFAULT NULL,
  `encounter_type` varchar(255) NOT NULL,
  `encounter_type_id` bigint(20) unsigned DEFAULT NULL,
  `attending_doctor_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `provider_id` bigint(20) unsigned DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `priority` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `chief_complaint_summary` text DEFAULT NULL,
  `reason_for_visit` text DEFAULT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `encounters_encounter_no_unique` (`encounter_no`),
  KEY `encounters_branch_id_foreign` (`branch_id`),
  KEY `encounters_attending_doctor_id_foreign` (`attending_doctor_id`),
  KEY `encounters_department_id_foreign` (`department_id`),
  KEY `encounters_company_id_branch_id_patient_id_index` (`company_id`,`branch_id`,`patient_id`),
  KEY `encounters_company_id_encounter_no_index` (`company_id`,`encounter_no`),
  KEY `encounters_appointment_id_foreign` (`appointment_id`),
  KEY `encounters_created_by_foreign` (`created_by`),
  KEY `encounters_completed_by_foreign` (`completed_by`),
  KEY `encounters_locked_by_foreign` (`locked_by`),
  KEY `encounters_company_id_encounter_date_index` (`company_id`,`encounter_date`),
  KEY `encounters_patient_id_encounter_date_index` (`patient_id`,`encounter_date`),
  KEY `encounters_provider_id_encounter_date_index` (`provider_id`,`encounter_date`),
  KEY `encounters_encounter_type_id_foreign` (`encounter_type_id`),
  KEY `encounters_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `encounters_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_attending_doctor_id_foreign` FOREIGN KEY (`attending_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounters_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_encounter_type_id_foreign` FOREIGN KEY (`encounter_type_id`) REFERENCES `encounter_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounters_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encounters`
--

LOCK TABLES `encounters` WRITE;
/*!40000 ALTER TABLE `encounters` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `encounters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `file_versions`
--

DROP TABLE IF EXISTS `file_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `file_id` bigint(20) unsigned NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `checksum` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_versions_file_id_index` (`file_id`),
  CONSTRAINT `file_versions_file_id_foreign` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_versions`
--

LOCK TABLES `file_versions` WRITE;
/*!40000 ALTER TABLE `file_versions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `file_versions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `extension` varchar(32) NOT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `checksum` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `files_disk_path_unique` (`disk`,`path`),
  KEY `files_uploaded_by_index` (`uploaded_by`),
  KEY `files_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `files_created_at_index` (`created_at`),
  CONSTRAINT `files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `genders`
--

DROP TABLE IF EXISTS `genders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `genders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `genders_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genders`
--

LOCK TABLES `genders` WRITE;
/*!40000 ALTER TABLE `genders` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `genders` VALUES
(1,'male','Male',1,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,'female','Female',1,2,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,'other','Other',1,3,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,'unknown','Unknown',1,4,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `genders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `hospital_holidays`
--

DROP TABLE IF EXISTS `hospital_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospital_holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `is_recurring_annually` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hospital_holidays_branch_id_foreign` (`branch_id`),
  KEY `hospital_holidays_department_id_foreign` (`department_id`),
  KEY `hospital_holiday_branch_date_idx` (`company_id`,`branch_id`,`date`),
  CONSTRAINT `hospital_holidays_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hospital_holidays_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hospital_holidays_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hospital_holidays`
--

LOCK TABLES `hospital_holidays` WRITE;
/*!40000 ALTER TABLE `hospital_holidays` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `hospital_holidays` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `identification_types`
--

DROP TABLE IF EXISTS `identification_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `identification_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `issuing_authority` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identification_types_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `identification_types`
--

LOCK TABLES `identification_types` WRITE;
/*!40000 ALTER TABLE `identification_types` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `identification_types` VALUES
(1,'National ID','NID','National Identity Card','Government',1,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,'Passport','PASSPORT','International Passport','Government',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,'Birth Certificate','BIRTH_CERT','Birth Certificate','Local Government',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,'Driving License','DL','Driver License','Transport Authority',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,'Voter ID','VOTER_ID','Voter Identity Card','Election Commission',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `identification_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `investigation_orders`
--

DROP TABLE IF EXISTS `investigation_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `investigation_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `order_no` varchar(255) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `priority` enum('routine','urgent','stat') NOT NULL DEFAULT 'routine',
  `status` enum('ordered','sample_collected','in_progress','result_entered','verified','approved','cancelled') NOT NULL DEFAULT 'ordered',
  `ordered_by` bigint(20) unsigned DEFAULT NULL,
  `ordered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `investigation_orders_order_no_unique` (`order_no`),
  KEY `investigation_orders_branch_id_foreign` (`branch_id`),
  KEY `investigation_orders_appointment_id_foreign` (`appointment_id`),
  KEY `investigation_orders_patient_id_foreign` (`patient_id`),
  KEY `investigation_orders_doctor_id_foreign` (`doctor_id`),
  KEY `investigation_orders_ordered_by_foreign` (`ordered_by`),
  KEY `investigation_orders_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `investigation_orders_company_id_appointment_id_index` (`company_id`,`appointment_id`),
  KEY `investigation_orders_company_id_status_index` (`company_id`,`status`),
  KEY `investigation_orders_encounter_id_index` (`encounter_id`),
  CONSTRAINT `investigation_orders_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investigation_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investigation_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investigation_orders_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investigation_orders_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investigation_orders_ordered_by_foreign` FOREIGN KEY (`ordered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investigation_orders_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investigation_orders`
--

LOCK TABLES `investigation_orders` WRITE;
/*!40000 ALTER TABLE `investigation_orders` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `investigation_orders` VALUES
(1,1,2,1,NULL,1,2,'LAB-CAUIOSXE','CBC',NULL,NULL,'routine','ordered',1,'2026-09-23 22:23:20','2026-09-23 22:23:20','2026-09-23 22:23:20',NULL);
/*!40000 ALTER TABLE `investigation_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_admission_requests`
--

DROP TABLE IF EXISTS `ipd_admission_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_admission_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `source_encounter_id` bigint(20) unsigned DEFAULT NULL,
  `requesting_provider_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `admission_type_id` bigint(20) unsigned DEFAULT NULL,
  `admission_source_id` bigint(20) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `provisional_diagnosis` text DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'routine',
  `expected_length_of_stay_days` int(10) unsigned DEFAULT NULL,
  `expected_admission_date` date DEFAULT NULL,
  `expected_discharge_date` date DEFAULT NULL,
  `required_bed_type_id` bigint(20) unsigned DEFAULT NULL,
  `isolation_requirement` varchar(255) DEFAULT NULL,
  `special_requirements` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `workflow_instance_id` bigint(20) unsigned DEFAULT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_admission_requests_branch_id_foreign` (`branch_id`),
  KEY `ipd_admission_requests_source_encounter_id_foreign` (`source_encounter_id`),
  KEY `ipd_admission_requests_requesting_provider_id_foreign` (`requesting_provider_id`),
  KEY `ipd_admission_requests_department_id_foreign` (`department_id`),
  KEY `ipd_admission_requests_specialty_id_foreign` (`specialty_id`),
  KEY `ipd_admission_requests_admission_type_id_foreign` (`admission_type_id`),
  KEY `ipd_admission_requests_admission_source_id_foreign` (`admission_source_id`),
  KEY `ipd_admission_requests_required_bed_type_id_foreign` (`required_bed_type_id`),
  KEY `ipd_admission_requests_workflow_instance_id_foreign` (`workflow_instance_id`),
  KEY `ipd_admission_requests_requested_by_foreign` (`requested_by`),
  KEY `ipd_admission_requests_approved_by_foreign` (`approved_by`),
  KEY `ipd_admission_requests_cancelled_by_foreign` (`cancelled_by`),
  KEY `ipd_admission_requests_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `ipd_admission_requests_patient_id_index` (`patient_id`),
  CONSTRAINT `ipd_admission_requests_admission_source_id_foreign` FOREIGN KEY (`admission_source_id`) REFERENCES `ipd_admission_sources` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_admission_type_id_foreign` FOREIGN KEY (`admission_type_id`) REFERENCES `ipd_admission_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_admission_requests_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_admission_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_requesting_provider_id_foreign` FOREIGN KEY (`requesting_provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_required_bed_type_id_foreign` FOREIGN KEY (`required_bed_type_id`) REFERENCES `ipd_bed_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_source_encounter_id_foreign` FOREIGN KEY (`source_encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_requests_workflow_instance_id_foreign` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_admission_requests`
--

LOCK TABLES `ipd_admission_requests` WRITE;
/*!40000 ALTER TABLE `ipd_admission_requests` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_admission_requests` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_admission_sources`
--

DROP TABLE IF EXISTS `ipd_admission_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_admission_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_admission_sources_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `ipd_admission_sources_branch_id_foreign` (`branch_id`),
  KEY `ipd_admission_sources_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `ipd_admission_sources_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_sources_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_admission_sources`
--

LOCK TABLES `ipd_admission_sources` WRITE;
/*!40000 ALTER TABLE `ipd_admission_sources` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_admission_sources` VALUES
(1,1,NULL,'OPD','OPD',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'EMERGENCY','Emergency',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'REFERRAL','Referral',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,1,NULL,'DIRECT','Direct',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `ipd_admission_sources` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_admission_types`
--

DROP TABLE IF EXISTS `ipd_admission_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_admission_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_admission_types_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `ipd_admission_types_branch_id_foreign` (`branch_id`),
  KEY `ipd_admission_types_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `ipd_admission_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admission_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_admission_types`
--

LOCK TABLES `ipd_admission_types` WRITE;
/*!40000 ALTER TABLE `ipd_admission_types` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_admission_types` VALUES
(1,1,NULL,'ELECTIVE','Elective',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'EMERGENCY','Emergency',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'DAY_CARE','Day Care',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `ipd_admission_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_admissions`
--

DROP TABLE IF EXISTS `ipd_admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_admissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `admission_number` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `admission_request_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `admission_type_id` bigint(20) unsigned DEFAULT NULL,
  `admission_source_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `admitting_provider_id` bigint(20) unsigned DEFAULT NULL,
  `attending_provider_id` bigint(20) unsigned DEFAULT NULL,
  `admitted_at` timestamp NOT NULL,
  `expected_discharge_date` date DEFAULT NULL,
  `actual_discharge_date` timestamp NULL DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'routine',
  `status` varchar(255) NOT NULL DEFAULT 'admitted',
  `discharge_disposition_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `admitted_by` bigint(20) unsigned DEFAULT NULL,
  `discharged_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_admissions_company_id_admission_number_unique` (`company_id`,`admission_number`),
  KEY `ipd_admissions_branch_id_foreign` (`branch_id`),
  KEY `ipd_admissions_admission_request_id_foreign` (`admission_request_id`),
  KEY `ipd_admissions_encounter_id_foreign` (`encounter_id`),
  KEY `ipd_admissions_admission_type_id_foreign` (`admission_type_id`),
  KEY `ipd_admissions_admission_source_id_foreign` (`admission_source_id`),
  KEY `ipd_admissions_department_id_foreign` (`department_id`),
  KEY `ipd_admissions_specialty_id_foreign` (`specialty_id`),
  KEY `ipd_admissions_admitting_provider_id_foreign` (`admitting_provider_id`),
  KEY `ipd_admissions_attending_provider_id_foreign` (`attending_provider_id`),
  KEY `ipd_admissions_discharge_disposition_id_foreign` (`discharge_disposition_id`),
  KEY `ipd_admissions_created_by_foreign` (`created_by`),
  KEY `ipd_admissions_approved_by_foreign` (`approved_by`),
  KEY `ipd_admissions_admitted_by_foreign` (`admitted_by`),
  KEY `ipd_admissions_discharged_by_foreign` (`discharged_by`),
  KEY `ipd_admissions_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `ipd_admissions_patient_id_index` (`patient_id`),
  KEY `ipd_admissions_expected_discharge_date_index` (`expected_discharge_date`),
  CONSTRAINT `ipd_admissions_admission_request_id_foreign` FOREIGN KEY (`admission_request_id`) REFERENCES `ipd_admission_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_admission_source_id_foreign` FOREIGN KEY (`admission_source_id`) REFERENCES `ipd_admission_sources` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_admission_type_id_foreign` FOREIGN KEY (`admission_type_id`) REFERENCES `ipd_admission_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_admitted_by_foreign` FOREIGN KEY (`admitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_admitting_provider_id_foreign` FOREIGN KEY (`admitting_provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_attending_provider_id_foreign` FOREIGN KEY (`attending_provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_admissions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_discharge_disposition_id_foreign` FOREIGN KEY (`discharge_disposition_id`) REFERENCES `ipd_discharge_dispositions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_discharged_by_foreign` FOREIGN KEY (`discharged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_admissions_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_admissions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_admissions_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_admissions`
--

LOCK TABLES `ipd_admissions` WRITE;
/*!40000 ALTER TABLE `ipd_admissions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_admissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_bed_allocations`
--

DROP TABLE IF EXISTS `ipd_bed_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_bed_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `bed_id` bigint(20) unsigned NOT NULL,
  `allocated_at` timestamp NOT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `allocation_type` varchar(255) NOT NULL DEFAULT 'admission',
  `reason` text DEFAULT NULL,
  `allocated_by` bigint(20) unsigned DEFAULT NULL,
  `released_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_bed_allocations_branch_id_foreign` (`branch_id`),
  KEY `ipd_bed_allocations_patient_id_foreign` (`patient_id`),
  KEY `ipd_bed_allocations_allocated_by_foreign` (`allocated_by`),
  KEY `ipd_bed_allocations_released_by_foreign` (`released_by`),
  KEY `ipd_bed_allocations_admission_id_released_at_index` (`admission_id`,`released_at`),
  KEY `ipd_bed_allocations_bed_id_released_at_index` (`bed_id`,`released_at`),
  KEY `ipd_bed_allocations_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  CONSTRAINT `ipd_bed_allocations_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_allocations_allocated_by_foreign` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_allocations_bed_id_foreign` FOREIGN KEY (`bed_id`) REFERENCES `ipd_beds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_allocations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_allocations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_allocations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_allocations_released_by_foreign` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_bed_allocations`
--

LOCK TABLES `ipd_bed_allocations` WRITE;
/*!40000 ALTER TABLE `ipd_bed_allocations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_bed_allocations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_bed_blocks`
--

DROP TABLE IF EXISTS `ipd_bed_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_bed_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `bed_id` bigint(20) unsigned NOT NULL,
  `reason_type` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `start_at` timestamp NOT NULL,
  `expected_end_at` timestamp NULL DEFAULT NULL,
  `actual_end_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_bed_blocks_branch_id_foreign` (`branch_id`),
  KEY `ipd_bed_blocks_requested_by_foreign` (`requested_by`),
  KEY `ipd_bed_blocks_approved_by_foreign` (`approved_by`),
  KEY `ipd_bed_blocks_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `ipd_bed_blocks_bed_id_status_index` (`bed_id`,`status`),
  CONSTRAINT `ipd_bed_blocks_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_blocks_bed_id_foreign` FOREIGN KEY (`bed_id`) REFERENCES `ipd_beds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_blocks_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_blocks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_blocks_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_bed_blocks`
--

LOCK TABLES `ipd_bed_blocks` WRITE;
/*!40000 ALTER TABLE `ipd_bed_blocks` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_bed_blocks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_bed_charge_events`
--

DROP TABLE IF EXISTS `ipd_bed_charge_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_bed_charge_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `bed_id` bigint(20) unsigned NOT NULL,
  `charge_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_bed_charge_events_admission_id_charge_date_unique` (`admission_id`,`charge_date`),
  KEY `ipd_bed_charge_events_bed_id_foreign` (`bed_id`),
  CONSTRAINT `ipd_bed_charge_events_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_charge_events_bed_id_foreign` FOREIGN KEY (`bed_id`) REFERENCES `ipd_beds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_bed_charge_events`
--

LOCK TABLES `ipd_bed_charge_events` WRITE;
/*!40000 ALTER TABLE `ipd_bed_charge_events` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_bed_charge_events` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_bed_movements`
--

DROP TABLE IF EXISTS `ipd_bed_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_bed_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `from_bed_id` bigint(20) unsigned DEFAULT NULL,
  `to_bed_id` bigint(20) unsigned DEFAULT NULL,
  `movement_type` varchar(255) NOT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `moved_at` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_bed_movements_branch_id_foreign` (`branch_id`),
  KEY `ipd_bed_movements_patient_id_foreign` (`patient_id`),
  KEY `ipd_bed_movements_requested_by_foreign` (`requested_by`),
  KEY `ipd_bed_movements_approved_by_foreign` (`approved_by`),
  KEY `ipd_bed_movements_completed_by_foreign` (`completed_by`),
  KEY `ipd_bed_movements_admission_id_movement_type_index` (`admission_id`,`movement_type`),
  KEY `ipd_bed_movements_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `ipd_bed_movements_from_bed_id_index` (`from_bed_id`),
  KEY `ipd_bed_movements_to_bed_id_index` (`to_bed_id`),
  KEY `ipd_bed_movements_moved_at_index` (`moved_at`),
  CONSTRAINT `ipd_bed_movements_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_movements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_movements_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_movements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_movements_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_movements_from_bed_id_foreign` FOREIGN KEY (`from_bed_id`) REFERENCES `ipd_beds` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_movements_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_movements_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_movements_to_bed_id_foreign` FOREIGN KEY (`to_bed_id`) REFERENCES `ipd_beds` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_bed_movements`
--

LOCK TABLES `ipd_bed_movements` WRITE;
/*!40000 ALTER TABLE `ipd_bed_movements` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_bed_movements` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_bed_reservations`
--

DROP TABLE IF EXISTS `ipd_bed_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_bed_reservations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `admission_request_id` bigint(20) unsigned DEFAULT NULL,
  `bed_id` bigint(20) unsigned NOT NULL,
  `reserved_at` timestamp NOT NULL,
  `expires_at` timestamp NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'reserved',
  `reason` text DEFAULT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_bed_reservations_branch_id_foreign` (`branch_id`),
  KEY `ipd_bed_reservations_patient_id_foreign` (`patient_id`),
  KEY `ipd_bed_reservations_admission_request_id_foreign` (`admission_request_id`),
  KEY `ipd_bed_reservations_requested_by_foreign` (`requested_by`),
  KEY `ipd_bed_reservations_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `ipd_bed_reservations_bed_id_status_index` (`bed_id`,`status`),
  KEY `ipd_bed_reservations_expires_at_index` (`expires_at`),
  CONSTRAINT `ipd_bed_reservations_admission_request_id_foreign` FOREIGN KEY (`admission_request_id`) REFERENCES `ipd_admission_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_reservations_bed_id_foreign` FOREIGN KEY (`bed_id`) REFERENCES `ipd_beds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_reservations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_reservations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_reservations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_bed_reservations_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_bed_reservations`
--

LOCK TABLES `ipd_bed_reservations` WRITE;
/*!40000 ALTER TABLE `ipd_bed_reservations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_bed_reservations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_bed_types`
--

DROP TABLE IF EXISTS `ipd_bed_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_bed_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_bed_types_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `ipd_bed_types_branch_id_foreign` (`branch_id`),
  KEY `ipd_bed_types_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `ipd_bed_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_bed_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_bed_types`
--

LOCK TABLES `ipd_bed_types` WRITE;
/*!40000 ALTER TABLE `ipd_bed_types` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_bed_types` VALUES
(1,1,NULL,'GENERAL','General',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'PRIVATE','Private',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'ICU','ICU',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `ipd_bed_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_beds`
--

DROP TABLE IF EXISTS `ipd_beds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_beds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `room_id` bigint(20) unsigned NOT NULL,
  `bed_type_id` bigint(20) unsigned DEFAULT NULL,
  `bed_code` varchar(255) NOT NULL,
  `bed_name` varchar(255) DEFAULT NULL,
  `gender_type` varchar(255) NOT NULL DEFAULT 'any',
  `status` varchar(255) NOT NULL DEFAULT 'available',
  `isolation_capable` tinyint(1) NOT NULL DEFAULT 0,
  `icu_capable` tinyint(1) NOT NULL DEFAULT 0,
  `ventilator_capable` tinyint(1) NOT NULL DEFAULT 0,
  `oxygen_available` tinyint(1) NOT NULL DEFAULT 0,
  `monitor_available` tinyint(1) NOT NULL DEFAULT 0,
  `is_vip` tinyint(1) NOT NULL DEFAULT 0,
  `is_pediatric` tinyint(1) NOT NULL DEFAULT 0,
  `is_maternity` tinyint(1) NOT NULL DEFAULT 0,
  `is_bariatric` tinyint(1) NOT NULL DEFAULT 0,
  `is_accessible` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_beds_company_id_bed_code_unique` (`company_id`,`bed_code`),
  KEY `ipd_beds_branch_id_foreign` (`branch_id`),
  KEY `ipd_beds_bed_type_id_foreign` (`bed_type_id`),
  KEY `ipd_beds_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `ipd_beds_room_id_index` (`room_id`),
  CONSTRAINT `ipd_beds_bed_type_id_foreign` FOREIGN KEY (`bed_type_id`) REFERENCES `ipd_bed_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_beds_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_beds_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_beds_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `ipd_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_beds`
--

LOCK TABLES `ipd_beds` WRITE;
/*!40000 ALTER TABLE `ipd_beds` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_beds` VALUES
(1,1,NULL,1,1,'MED-100-A','Bed A','any','available',0,0,0,0,0,0,0,0,0,0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,1,1,'MED-100-B','Bed B','any','available',0,0,0,0,0,0,0,0,0,0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,2,1,'MED-200-A','Bed A','any','available',0,0,0,0,0,0,0,0,0,0,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL),
(4,1,NULL,2,1,'MED-200-B','Bed B','any','available',0,0,0,0,0,0,0,0,0,0,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL),
(5,1,NULL,3,2,'SURG-100-A','Bed A','any','available',0,0,0,0,0,0,0,0,0,0,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL),
(6,1,NULL,3,2,'SURG-100-B','Bed B','any','available',0,0,0,0,0,0,0,0,0,0,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL),
(7,1,NULL,4,2,'SURG-200-A','Bed A','any','available',0,0,0,0,0,0,0,0,0,0,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL),
(8,1,NULL,4,2,'SURG-200-B','Bed B','any','available',0,0,0,0,0,0,0,0,0,0,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL);
/*!40000 ALTER TABLE `ipd_beds` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_buildings`
--

DROP TABLE IF EXISTS `ipd_buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_buildings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_buildings_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `ipd_buildings_branch_id_foreign` (`branch_id`),
  KEY `ipd_buildings_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `ipd_buildings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_buildings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_buildings`
--

LOCK TABLES `ipd_buildings` WRITE;
/*!40000 ALTER TABLE `ipd_buildings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_buildings` VALUES
(1,1,NULL,'MAIN','Main Building',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `ipd_buildings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_counters`
--

DROP TABLE IF EXISTS `ipd_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(3) NOT NULL,
  `prefix` varchar(16) NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_counters_scope_unique` (`company_id`,`branch_id`,`document_type`,`prefix`),
  KEY `ipd_counters_branch_id_foreign` (`branch_id`),
  KEY `ipd_counters_company_id_branch_id_document_type_index` (`company_id`,`branch_id`,`document_type`),
  CONSTRAINT `ipd_counters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_counters`
--

LOCK TABLES `ipd_counters` WRITE;
/*!40000 ALTER TABLE `ipd_counters` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_discharge_dispositions`
--

DROP TABLE IF EXISTS `ipd_discharge_dispositions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_discharge_dispositions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_discharge_dispositions_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `ipd_discharge_dispositions_branch_id_foreign` (`branch_id`),
  KEY `ipd_discharge_dispositions_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `ipd_discharge_dispositions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_discharge_dispositions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_discharge_dispositions`
--

LOCK TABLES `ipd_discharge_dispositions` WRITE;
/*!40000 ALTER TABLE `ipd_discharge_dispositions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_discharge_dispositions` VALUES
(1,1,NULL,'HOME','Home',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'ANOTHER_HOSPITAL','Another Hospital',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'DECEASED','Deceased',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `ipd_discharge_dispositions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_discharge_requests`
--

DROP TABLE IF EXISTS `ipd_discharge_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_discharge_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `discharge_type` varchar(255) NOT NULL DEFAULT 'routine',
  `planned_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `discharge_diagnosis` text DEFAULT NULL,
  `disposition_id` bigint(20) unsigned DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `follow_up_required` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_provider_id` bigint(20) unsigned DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `clinical_cleared_at` timestamp NULL DEFAULT NULL,
  `clinical_cleared_by` bigint(20) unsigned DEFAULT NULL,
  `billing_cleared_at` timestamp NULL DEFAULT NULL,
  `billing_cleared_by` bigint(20) unsigned DEFAULT NULL,
  `pharmacy_cleared_at` timestamp NULL DEFAULT NULL,
  `pharmacy_cleared_by` bigint(20) unsigned DEFAULT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_discharge_requests_branch_id_foreign` (`branch_id`),
  KEY `ipd_discharge_requests_patient_id_foreign` (`patient_id`),
  KEY `ipd_discharge_requests_disposition_id_foreign` (`disposition_id`),
  KEY `ipd_discharge_requests_follow_up_provider_id_foreign` (`follow_up_provider_id`),
  KEY `ipd_discharge_requests_clinical_cleared_by_foreign` (`clinical_cleared_by`),
  KEY `ipd_discharge_requests_billing_cleared_by_foreign` (`billing_cleared_by`),
  KEY `ipd_discharge_requests_pharmacy_cleared_by_foreign` (`pharmacy_cleared_by`),
  KEY `ipd_discharge_requests_requested_by_foreign` (`requested_by`),
  KEY `ipd_discharge_requests_approved_by_foreign` (`approved_by`),
  KEY `ipd_discharge_requests_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `ipd_discharge_requests_admission_id_index` (`admission_id`),
  CONSTRAINT `ipd_discharge_requests_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_discharge_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_discharge_requests_billing_cleared_by_foreign` FOREIGN KEY (`billing_cleared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_discharge_requests_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_discharge_requests_clinical_cleared_by_foreign` FOREIGN KEY (`clinical_cleared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_discharge_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_discharge_requests_disposition_id_foreign` FOREIGN KEY (`disposition_id`) REFERENCES `ipd_discharge_dispositions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_discharge_requests_follow_up_provider_id_foreign` FOREIGN KEY (`follow_up_provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_discharge_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_discharge_requests_pharmacy_cleared_by_foreign` FOREIGN KEY (`pharmacy_cleared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_discharge_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_discharge_requests`
--

LOCK TABLES `ipd_discharge_requests` WRITE;
/*!40000 ALTER TABLE `ipd_discharge_requests` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_discharge_requests` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_floors`
--

DROP TABLE IF EXISTS `ipd_floors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_floors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `building_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_floors_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `ipd_floors_branch_id_foreign` (`branch_id`),
  KEY `ipd_floors_building_id_foreign` (`building_id`),
  KEY `ipd_floors_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `ipd_floors_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_floors_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `ipd_buildings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_floors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_floors`
--

LOCK TABLES `ipd_floors` WRITE;
/*!40000 ALTER TABLE `ipd_floors` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_floors` VALUES
(1,1,NULL,1,'F3','3rd Floor',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `ipd_floors` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_patient_leaves`
--

DROP TABLE IF EXISTS `ipd_patient_leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_patient_leaves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `leave_type` varchar(255) NOT NULL DEFAULT 'temporary_pass',
  `requested_at` timestamp NOT NULL,
  `expected_return_at` timestamp NOT NULL,
  `actual_return_at` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `bed_handling` varchar(255) NOT NULL DEFAULT 'retain',
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_patient_leaves_branch_id_foreign` (`branch_id`),
  KEY `ipd_patient_leaves_patient_id_foreign` (`patient_id`),
  KEY `ipd_patient_leaves_requested_by_foreign` (`requested_by`),
  KEY `ipd_patient_leaves_approved_by_foreign` (`approved_by`),
  KEY `ipd_patient_leaves_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `ipd_patient_leaves_admission_id_index` (`admission_id`),
  CONSTRAINT `ipd_patient_leaves_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_patient_leaves_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_patient_leaves_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_patient_leaves_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_patient_leaves_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_patient_leaves_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_patient_leaves`
--

LOCK TABLES `ipd_patient_leaves` WRITE;
/*!40000 ALTER TABLE `ipd_patient_leaves` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_patient_leaves` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_provider_assignments`
--

DROP TABLE IF EXISTS `ipd_provider_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_provider_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `provider_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL,
  `assigned_at` timestamp NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_provider_assignments_provider_id_foreign` (`provider_id`),
  KEY `ipd_provider_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `ipd_provider_assignments_admission_id_role_index` (`admission_id`,`role`),
  KEY `ipd_provider_assignments_admission_id_ended_at_index` (`admission_id`,`ended_at`),
  CONSTRAINT `ipd_provider_assignments_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_provider_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_provider_assignments_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_provider_assignments`
--

LOCK TABLES `ipd_provider_assignments` WRITE;
/*!40000 ALTER TABLE `ipd_provider_assignments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `ipd_provider_assignments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_rooms`
--

DROP TABLE IF EXISTS `ipd_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `ward_id` bigint(20) unsigned NOT NULL,
  `room_number` varchar(255) NOT NULL,
  `room_type` varchar(255) NOT NULL DEFAULT 'general',
  `capacity` int(10) unsigned NOT NULL DEFAULT 1,
  `gender_policy` varchar(255) NOT NULL DEFAULT 'any',
  `isolation_capable` tinyint(1) NOT NULL DEFAULT 0,
  `is_vip` tinyint(1) NOT NULL DEFAULT 0,
  `rate_category` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_rooms_ward_id_room_number_unique` (`ward_id`,`room_number`),
  KEY `ipd_rooms_branch_id_foreign` (`branch_id`),
  KEY `ipd_rooms_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `ipd_rooms_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_rooms_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_rooms_ward_id_foreign` FOREIGN KEY (`ward_id`) REFERENCES `ipd_wards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_rooms`
--

LOCK TABLES `ipd_rooms` WRITE;
/*!40000 ALTER TABLE `ipd_rooms` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_rooms` VALUES
(1,1,NULL,1,'100','general',2,'any',0,0,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,1,'200','general',2,'any',0,0,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,2,'100','private',2,'any',0,0,NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL),
(4,1,NULL,2,'200','private',2,'any',0,0,NULL,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL);
/*!40000 ALTER TABLE `ipd_rooms` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ipd_wards`
--

DROP TABLE IF EXISTS `ipd_wards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_wards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `building_id` bigint(20) unsigned DEFAULT NULL,
  `floor_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gender_policy` varchar(255) NOT NULL DEFAULT 'any',
  `capacity` int(10) unsigned NOT NULL DEFAULT 0,
  `isolation_capable` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipd_wards_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `ipd_wards_branch_id_foreign` (`branch_id`),
  KEY `ipd_wards_building_id_foreign` (`building_id`),
  KEY `ipd_wards_floor_id_foreign` (`floor_id`),
  KEY `ipd_wards_department_id_foreign` (`department_id`),
  KEY `ipd_wards_specialty_id_foreign` (`specialty_id`),
  KEY `ipd_wards_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `ipd_wards_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_wards_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `ipd_buildings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_wards_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_wards_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_wards_floor_id_foreign` FOREIGN KEY (`floor_id`) REFERENCES `ipd_floors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_wards_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipd_wards`
--

LOCK TABLES `ipd_wards` WRITE;
/*!40000 ALTER TABLE `ipd_wards` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ipd_wards` VALUES
(1,1,NULL,1,1,NULL,NULL,'MED','Medical Ward','any',4,0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,1,1,NULL,NULL,'SURG','Surgical Ward','any',4,0,1,'2026-09-23 17:34:59','2026-09-23 17:34:59',NULL);
/*!40000 ALTER TABLE `ipd_wards` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_analyzer_tests`
--

DROP TABLE IF EXISTS `lab_analyzer_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_analyzer_tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `analyzer_id` bigint(20) unsigned NOT NULL,
  `test_id` bigint(20) unsigned NOT NULL,
  `analyzer_test_code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_analyzer_tests_analyzer_id_test_id_unique` (`analyzer_id`,`test_id`),
  KEY `lab_analyzer_tests_test_id_foreign` (`test_id`),
  CONSTRAINT `lab_analyzer_tests_analyzer_id_foreign` FOREIGN KEY (`analyzer_id`) REFERENCES `lab_analyzers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_analyzer_tests_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_analyzer_tests`
--

LOCK TABLES `lab_analyzer_tests` WRITE;
/*!40000 ALTER TABLE `lab_analyzer_tests` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_analyzer_tests` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_analyzers`
--

DROP TABLE IF EXISTS `lab_analyzers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_analyzers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `vendor` varchar(255) DEFAULT NULL,
  `connection_type` varchar(255) NOT NULL DEFAULT 'manual',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_analyzers_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `lab_analyzers_branch_id_foreign` (`branch_id`),
  KEY `lab_analyzers_section_id_foreign` (`section_id`),
  CONSTRAINT `lab_analyzers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_analyzers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_analyzers_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `lab_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_analyzers`
--

LOCK TABLES `lab_analyzers` WRITE;
/*!40000 ALTER TABLE `lab_analyzers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_analyzers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_container_types`
--

DROP TABLE IF EXISTS `lab_container_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_container_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_container_types_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `lab_container_types_branch_id_foreign` (`branch_id`),
  KEY `lab_container_types_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `lab_container_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_container_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_container_types`
--

LOCK TABLES `lab_container_types` WRITE;
/*!40000 ALTER TABLE `lab_container_types` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_container_types` VALUES
(1,1,NULL,'EDTA','EDTA Tube',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'PLAIN','Plain Tube',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'FLUORIDE','Fluoride Tube',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `lab_container_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_counters`
--

DROP TABLE IF EXISTS `lab_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(3) NOT NULL,
  `prefix` varchar(16) NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_counters_scope_unique` (`company_id`,`branch_id`,`document_type`,`prefix`),
  KEY `lab_counters_branch_id_foreign` (`branch_id`),
  KEY `lab_counters_company_id_branch_id_document_type_index` (`company_id`,`branch_id`,`document_type`),
  CONSTRAINT `lab_counters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_counters`
--

LOCK TABLES `lab_counters` WRITE;
/*!40000 ALTER TABLE `lab_counters` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_critical_result_alerts`
--

DROP TABLE IF EXISTS `lab_critical_result_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_critical_result_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `result_id` bigint(20) unsigned NOT NULL,
  `detected_at` timestamp NOT NULL,
  `notified_to` bigint(20) unsigned DEFAULT NULL,
  `notification_method` varchar(255) DEFAULT NULL,
  `acknowledged_by` bigint(20) unsigned DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_critical_result_alerts_branch_id_foreign` (`branch_id`),
  KEY `lab_critical_result_alerts_notified_to_foreign` (`notified_to`),
  KEY `lab_critical_result_alerts_acknowledged_by_foreign` (`acknowledged_by`),
  KEY `lab_crit_alerts_scope_ack_idx` (`company_id`,`branch_id`,`acknowledged_at`),
  KEY `lab_critical_result_alerts_result_id_index` (`result_id`),
  CONSTRAINT `lab_critical_result_alerts_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_critical_result_alerts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_critical_result_alerts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_critical_result_alerts_notified_to_foreign` FOREIGN KEY (`notified_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_critical_result_alerts_result_id_foreign` FOREIGN KEY (`result_id`) REFERENCES `lab_results` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_critical_result_alerts`
--

LOCK TABLES `lab_critical_result_alerts` WRITE;
/*!40000 ALTER TABLE `lab_critical_result_alerts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_critical_result_alerts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_critical_values`
--

DROP TABLE IF EXISTS `lab_critical_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_critical_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `low_threshold` decimal(18,4) DEFAULT NULL,
  `high_threshold` decimal(18,4) DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_critical_values_test_id_is_active_index` (`test_id`,`is_active`),
  CONSTRAINT `lab_critical_values_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_critical_values`
--

LOCK TABLES `lab_critical_values` WRITE;
/*!40000 ALTER TABLE `lab_critical_values` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_critical_values` VALUES
(1,7,40.0000,400.0000,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `lab_critical_values` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_order_items`
--

DROP TABLE IF EXISTS `lab_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_order_id` bigint(20) unsigned NOT NULL,
  `test_id` bigint(20) unsigned DEFAULT NULL,
  `panel_id` bigint(20) unsigned DEFAULT NULL,
  `specimen_id` bigint(20) unsigned DEFAULT NULL,
  `requested_test_name` varchar(255) NOT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'routine',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `result_status` varchar(255) NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT NULL,
  `collected_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_order_items_panel_id_foreign` (`panel_id`),
  KEY `lab_order_items_lab_order_id_status_index` (`lab_order_id`,`status`),
  KEY `lab_order_items_test_id_index` (`test_id`),
  KEY `lab_order_items_specimen_id_foreign` (`specimen_id`),
  CONSTRAINT `lab_order_items_lab_order_id_foreign` FOREIGN KEY (`lab_order_id`) REFERENCES `lab_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_order_items_panel_id_foreign` FOREIGN KEY (`panel_id`) REFERENCES `lab_panels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_order_items_specimen_id_foreign` FOREIGN KEY (`specimen_id`) REFERENCES `lab_specimens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_order_items_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `lab_tests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_order_items`
--

LOCK TABLES `lab_order_items` WRITE;
/*!40000 ALTER TABLE `lab_order_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_order_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_orders`
--

DROP TABLE IF EXISTS `lab_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `clinical_order_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'routine',
  `status` varchar(255) NOT NULL DEFAULT 'ordered',
  `ordered_by` bigint(20) unsigned DEFAULT NULL,
  `ordered_at` timestamp NULL DEFAULT NULL,
  `requested_collection_at` timestamp NULL DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_orders_company_id_order_number_unique` (`company_id`,`order_number`),
  KEY `lab_orders_branch_id_foreign` (`branch_id`),
  KEY `lab_orders_department_id_foreign` (`department_id`),
  KEY `lab_orders_ordered_by_foreign` (`ordered_by`),
  KEY `lab_orders_cancelled_by_foreign` (`cancelled_by`),
  KEY `lab_orders_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `lab_orders_patient_id_index` (`patient_id`),
  KEY `lab_orders_encounter_id_index` (`encounter_id`),
  KEY `lab_orders_clinical_order_id_index` (`clinical_order_id`),
  CONSTRAINT `lab_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_orders_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_orders_clinical_order_id_foreign` FOREIGN KEY (`clinical_order_id`) REFERENCES `clinical_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_orders_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_orders_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_orders_ordered_by_foreign` FOREIGN KEY (`ordered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_orders_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_orders`
--

LOCK TABLES `lab_orders` WRITE;
/*!40000 ALTER TABLE `lab_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_panel_items`
--

DROP TABLE IF EXISTS `lab_panel_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_panel_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `panel_id` bigint(20) unsigned NOT NULL,
  `test_id` bigint(20) unsigned NOT NULL,
  `sequence` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_panel_items_panel_id_test_id_unique` (`panel_id`,`test_id`),
  KEY `lab_panel_items_test_id_foreign` (`test_id`),
  CONSTRAINT `lab_panel_items_panel_id_foreign` FOREIGN KEY (`panel_id`) REFERENCES `lab_panels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_panel_items_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_panel_items`
--

LOCK TABLES `lab_panel_items` WRITE;
/*!40000 ALTER TABLE `lab_panel_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_panel_items` VALUES
(1,1,1,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,1,2,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(3,1,3,2,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(4,1,4,3,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(5,1,5,4,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(6,1,6,5,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `lab_panel_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_panels`
--

DROP TABLE IF EXISTS `lab_panels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_panels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_panels_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `lab_panels_branch_id_foreign` (`branch_id`),
  KEY `lab_panels_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `lab_panels_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_panels_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_panels`
--

LOCK TABLES `lab_panels` WRITE;
/*!40000 ALTER TABLE `lab_panels` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_panels` VALUES
(1,1,NULL,'CBC','Complete Blood Count',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `lab_panels` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_qc_materials`
--

DROP TABLE IF EXISTS `lab_qc_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_qc_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `level` varchar(255) NOT NULL DEFAULT 'normal',
  `lot_number` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_qc_materials_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `lab_qc_materials_branch_id_foreign` (`branch_id`),
  KEY `lab_qc_materials_section_id_foreign` (`section_id`),
  CONSTRAINT `lab_qc_materials_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_qc_materials_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_qc_materials_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `lab_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_qc_materials`
--

LOCK TABLES `lab_qc_materials` WRITE;
/*!40000 ALTER TABLE `lab_qc_materials` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_qc_materials` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_qc_runs`
--

DROP TABLE IF EXISTS `lab_qc_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_qc_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `qc_material_id` bigint(20) unsigned NOT NULL,
  `test_id` bigint(20) unsigned NOT NULL,
  `analyzer_id` bigint(20) unsigned DEFAULT NULL,
  `run_at` timestamp NOT NULL,
  `expected_low` decimal(18,4) DEFAULT NULL,
  `expected_high` decimal(18,4) DEFAULT NULL,
  `observed_value` decimal(18,4) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `performed_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_qc_runs_branch_id_foreign` (`branch_id`),
  KEY `lab_qc_runs_test_id_foreign` (`test_id`),
  KEY `lab_qc_runs_analyzer_id_foreign` (`analyzer_id`),
  KEY `lab_qc_runs_performed_by_foreign` (`performed_by`),
  KEY `lab_qc_runs_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `lab_qc_runs_qc_material_id_index` (`qc_material_id`),
  CONSTRAINT `lab_qc_runs_analyzer_id_foreign` FOREIGN KEY (`analyzer_id`) REFERENCES `lab_analyzers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_qc_runs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_qc_runs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_qc_runs_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_qc_runs_qc_material_id_foreign` FOREIGN KEY (`qc_material_id`) REFERENCES `lab_qc_materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_qc_runs_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_qc_runs`
--

LOCK TABLES `lab_qc_runs` WRITE;
/*!40000 ALTER TABLE `lab_qc_runs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_qc_runs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_reference_ranges`
--

DROP TABLE IF EXISTS `lab_reference_ranges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_reference_ranges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `specimen_type_id` bigint(20) unsigned DEFAULT NULL,
  `gender` varchar(255) NOT NULL DEFAULT 'any',
  `age_min_years` int(10) unsigned DEFAULT NULL,
  `age_max_years` int(10) unsigned DEFAULT NULL,
  `pregnancy_status` varchar(255) NOT NULL DEFAULT 'any',
  `unit` varchar(255) DEFAULT NULL,
  `low` decimal(18,4) DEFAULT NULL,
  `high` decimal(18,4) DEFAULT NULL,
  `text_range` varchar(255) DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_reference_ranges_specimen_type_id_foreign` (`specimen_type_id`),
  KEY `lab_reference_ranges_test_id_is_active_index` (`test_id`,`is_active`),
  CONSTRAINT `lab_reference_ranges_specimen_type_id_foreign` FOREIGN KEY (`specimen_type_id`) REFERENCES `lab_specimen_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_reference_ranges_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_reference_ranges`
--

LOCK TABLES `lab_reference_ranges` WRITE;
/*!40000 ALTER TABLE `lab_reference_ranges` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_reference_ranges` VALUES
(1,1,NULL,'male',18,NULL,'any','g/dL',13.0000,17.0000,NULL,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,1,NULL,'female',18,NULL,'any','g/dL',12.0000,15.0000,NULL,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(3,7,NULL,'any',NULL,NULL,'any','mg/dL',70.0000,100.0000,NULL,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `lab_reference_ranges` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_reports`
--

DROP TABLE IF EXISTS `lab_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `lab_order_id` bigint(20) unsigned NOT NULL,
  `report_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'preliminary',
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `amended_by` bigint(20) unsigned DEFAULT NULL,
  `amended_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_reports_company_id_report_number_unique` (`company_id`,`report_number`),
  KEY `lab_reports_branch_id_foreign` (`branch_id`),
  KEY `lab_reports_generated_by_foreign` (`generated_by`),
  KEY `lab_reports_amended_by_foreign` (`amended_by`),
  KEY `lab_reports_cancelled_by_foreign` (`cancelled_by`),
  KEY `lab_reports_lab_order_id_index` (`lab_order_id`),
  KEY `lab_reports_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  CONSTRAINT `lab_reports_amended_by_foreign` FOREIGN KEY (`amended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_reports_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_reports_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_reports_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_reports_lab_order_id_foreign` FOREIGN KEY (`lab_order_id`) REFERENCES `lab_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_reports`
--

LOCK TABLES `lab_reports` WRITE;
/*!40000 ALTER TABLE `lab_reports` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_reports` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_results`
--

DROP TABLE IF EXISTS `lab_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `lab_order_item_id` bigint(20) unsigned NOT NULL,
  `test_id` bigint(20) unsigned NOT NULL,
  `specimen_id` bigint(20) unsigned DEFAULT NULL,
  `result_type` varchar(255) NOT NULL,
  `numeric_value` decimal(18,4) DEFAULT NULL,
  `text_value` text DEFAULT NULL,
  `qualitative_value` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `reference_range_low` decimal(18,4) DEFAULT NULL,
  `reference_range_high` decimal(18,4) DEFAULT NULL,
  `reference_range_text` varchar(255) DEFAULT NULL,
  `abnormal_flag` varchar(255) DEFAULT NULL,
  `critical_flag` tinyint(1) NOT NULL DEFAULT 0,
  `result_status` varchar(255) NOT NULL DEFAULT 'pending',
  `entered_by` bigint(20) unsigned DEFAULT NULL,
  `entered_at` timestamp NULL DEFAULT NULL,
  `technical_validated_by` bigint(20) unsigned DEFAULT NULL,
  `technical_validated_at` timestamp NULL DEFAULT NULL,
  `pathologist_approved_by` bigint(20) unsigned DEFAULT NULL,
  `pathologist_approved_at` timestamp NULL DEFAULT NULL,
  `reported_at` timestamp NULL DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `amended_from_id` bigint(20) unsigned DEFAULT NULL,
  `amendment_reason` text DEFAULT NULL,
  `amended_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_results_branch_id_foreign` (`branch_id`),
  KEY `lab_results_test_id_foreign` (`test_id`),
  KEY `lab_results_specimen_id_foreign` (`specimen_id`),
  KEY `lab_results_entered_by_foreign` (`entered_by`),
  KEY `lab_results_technical_validated_by_foreign` (`technical_validated_by`),
  KEY `lab_results_pathologist_approved_by_foreign` (`pathologist_approved_by`),
  KEY `lab_results_amended_from_id_foreign` (`amended_from_id`),
  KEY `lab_results_amended_by_foreign` (`amended_by`),
  KEY `lab_results_lab_order_item_id_is_current_index` (`lab_order_item_id`,`is_current`),
  KEY `lab_results_company_id_branch_id_result_status_index` (`company_id`,`branch_id`,`result_status`),
  KEY `lab_results_critical_flag_index` (`critical_flag`),
  CONSTRAINT `lab_results_amended_by_foreign` FOREIGN KEY (`amended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_results_amended_from_id_foreign` FOREIGN KEY (`amended_from_id`) REFERENCES `lab_results` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_results_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_results_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_results_entered_by_foreign` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_results_lab_order_item_id_foreign` FOREIGN KEY (`lab_order_item_id`) REFERENCES `lab_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_results_pathologist_approved_by_foreign` FOREIGN KEY (`pathologist_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_results_specimen_id_foreign` FOREIGN KEY (`specimen_id`) REFERENCES `lab_specimens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_results_technical_validated_by_foreign` FOREIGN KEY (`technical_validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_results_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_results`
--

LOCK TABLES `lab_results` WRITE;
/*!40000 ALTER TABLE `lab_results` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_results` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_sections`
--

DROP TABLE IF EXISTS `lab_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_sections_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `lab_sections_branch_id_foreign` (`branch_id`),
  KEY `lab_sections_department_id_foreign` (`department_id`),
  KEY `lab_sections_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `lab_sections_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_sections_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_sections_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_sections`
--

LOCK TABLES `lab_sections` WRITE;
/*!40000 ALTER TABLE `lab_sections` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_sections` VALUES
(1,1,NULL,NULL,'HEMA','Hematology',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,NULL,'BIOCHEM','Biochemistry',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,NULL,'MICRO','Microbiology',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `lab_sections` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_specimen_types`
--

DROP TABLE IF EXISTS `lab_specimen_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_specimen_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `collection_requirements` text DEFAULT NULL,
  `storage_temperature` varchar(255) DEFAULT NULL,
  `stability_hours` int(10) unsigned DEFAULT NULL,
  `max_processing_minutes` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_specimen_types_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `lab_specimen_types_branch_id_foreign` (`branch_id`),
  KEY `lab_specimen_types_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `lab_specimen_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_specimen_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_specimen_types`
--

LOCK TABLES `lab_specimen_types` WRITE;
/*!40000 ALTER TABLE `lab_specimen_types` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_specimen_types` VALUES
(1,1,NULL,'BLOOD','Blood',NULL,NULL,NULL,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'SERUM','Serum',NULL,NULL,NULL,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'URINE','Urine',NULL,NULL,NULL,NULL,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `lab_specimen_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_specimens`
--

DROP TABLE IF EXISTS `lab_specimens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_specimens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `lab_order_id` bigint(20) unsigned NOT NULL,
  `specimen_type_id` bigint(20) unsigned DEFAULT NULL,
  `container_type_id` bigint(20) unsigned DEFAULT NULL,
  `accession_number` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `collected_by` bigint(20) unsigned DEFAULT NULL,
  `collected_at` timestamp NULL DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `storage_location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_specimens_company_id_accession_number_unique` (`company_id`,`accession_number`),
  UNIQUE KEY `lab_specimens_company_id_barcode_unique` (`company_id`,`barcode`),
  KEY `lab_specimens_branch_id_foreign` (`branch_id`),
  KEY `lab_specimens_specimen_type_id_foreign` (`specimen_type_id`),
  KEY `lab_specimens_container_type_id_foreign` (`container_type_id`),
  KEY `lab_specimens_collected_by_foreign` (`collected_by`),
  KEY `lab_specimens_received_by_foreign` (`received_by`),
  KEY `lab_specimens_rejected_by_foreign` (`rejected_by`),
  KEY `lab_specimens_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `lab_specimens_lab_order_id_index` (`lab_order_id`),
  CONSTRAINT `lab_specimens_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_specimens_collected_by_foreign` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_specimens_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_specimens_container_type_id_foreign` FOREIGN KEY (`container_type_id`) REFERENCES `lab_container_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_specimens_lab_order_id_foreign` FOREIGN KEY (`lab_order_id`) REFERENCES `lab_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_specimens_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_specimens_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_specimens_specimen_type_id_foreign` FOREIGN KEY (`specimen_type_id`) REFERENCES `lab_specimen_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_specimens`
--

LOCK TABLES `lab_specimens` WRITE;
/*!40000 ALTER TABLE `lab_specimens` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `lab_specimens` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_test_categories`
--

DROP TABLE IF EXISTS `lab_test_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_test_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_test_categories_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `lab_test_categories_branch_id_foreign` (`branch_id`),
  KEY `lab_test_categories_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `lab_test_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_test_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_test_categories`
--

LOCK TABLES `lab_test_categories` WRITE;
/*!40000 ALTER TABLE `lab_test_categories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_test_categories` VALUES
(1,1,NULL,'HEMA','Hematology',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'CHEM','Clinical Chemistry',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `lab_test_categories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `lab_tests`
--

DROP TABLE IF EXISTS `lab_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `specimen_type_id` bigint(20) unsigned DEFAULT NULL,
  `container_type_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `test_type` varchar(255) NOT NULL DEFAULT 'quantitative',
  `method` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `fasting_required` tinyint(1) NOT NULL DEFAULT 0,
  `turnaround_time_minutes` int(10) unsigned DEFAULT NULL,
  `is_panel` tinyint(1) NOT NULL DEFAULT 0,
  `requires_pathologist_approval` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_tests_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `lab_tests_branch_id_foreign` (`branch_id`),
  KEY `lab_tests_category_id_foreign` (`category_id`),
  KEY `lab_tests_section_id_foreign` (`section_id`),
  KEY `lab_tests_specimen_type_id_foreign` (`specimen_type_id`),
  KEY `lab_tests_container_type_id_foreign` (`container_type_id`),
  KEY `lab_tests_created_by_foreign` (`created_by`),
  KEY `lab_tests_updated_by_foreign` (`updated_by`),
  KEY `lab_tests_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `lab_tests_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_tests_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `lab_test_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_tests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_tests_container_type_id_foreign` FOREIGN KEY (`container_type_id`) REFERENCES `lab_container_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_tests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_tests_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `lab_sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_tests_specimen_type_id_foreign` FOREIGN KEY (`specimen_type_id`) REFERENCES `lab_specimen_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_tests_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_tests`
--

LOCK TABLES `lab_tests` WRITE;
/*!40000 ALTER TABLE `lab_tests` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `lab_tests` VALUES
(1,1,NULL,1,1,1,1,'HGB','Hemoglobin',NULL,NULL,'quantitative',NULL,'g/dL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,1,1,1,1,'RBC','RBC Count',NULL,NULL,'quantitative',NULL,'x10^6/uL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,1,1,1,1,'WBC','WBC Count',NULL,NULL,'quantitative',NULL,'x10^3/uL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,1,NULL,1,1,1,1,'PLT','Platelet Count',NULL,NULL,'quantitative',NULL,'x10^3/uL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,1,NULL,1,1,1,1,'HCT','Hematocrit',NULL,NULL,'quantitative',NULL,'%',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(6,1,NULL,1,1,1,1,'MCV','MCV',NULL,NULL,'quantitative',NULL,'fL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(7,1,NULL,2,2,2,3,'FBS','Fasting Blood Glucose',NULL,NULL,'quantitative',NULL,'mg/dL',1,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `lab_tests` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `login_histories`
--

DROP TABLE IF EXISTS `login_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'success',
  `failure_reason` varchar(255) DEFAULT NULL,
  `logged_in_at` timestamp NULL DEFAULT NULL,
  `logged_out_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login_histories_user_id_status_index` (`user_id`,`status`),
  KEY `login_histories_ip_address_status_index` (`ip_address`,`status`),
  KEY `login_histories_logged_in_at_index` (`logged_in_at`),
  CONSTRAINT `login_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_histories`
--

LOCK TABLES `login_histories` WRITE;
/*!40000 ALTER TABLE `login_histories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `login_histories` VALUES
(1,1,'admin@healthnexus.test','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','success',NULL,'2026-09-23 18:00:53',NULL,'2026-09-23 18:00:53','2026-09-23 18:00:53'),
(2,1,'admin@healthnexus.test','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','success',NULL,'2026-09-23 22:02:17',NULL,'2026-09-23 22:02:17','2026-09-23 22:02:17');
/*!40000 ALTER TABLE `login_histories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `marital_statuses`
--

DROP TABLE IF EXISTS `marital_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marital_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marital_statuses_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marital_statuses`
--

LOCK TABLES `marital_statuses` WRITE;
/*!40000 ALTER TABLE `marital_statuses` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `marital_statuses` VALUES
(1,'single','Single',1,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,'married','Married',1,2,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,'divorced','Divorced',1,3,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,'widowed','Widowed',1,4,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,'separated','Separated',1,5,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(6,'unknown','Unknown',1,6,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `marital_statuses` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_09_10_150357_create_permission_tables',1),
(5,'2026_09_10_150358_create_personal_access_tokens_table',1),
(6,'2026_09_10_160000_create_companies_table',1),
(7,'2026_09_10_160001_create_branches_table',1),
(8,'2026_09_10_160002_create_departments_table',1),
(9,'2026_09_10_160003_create_user_companies_table',1),
(10,'2026_09_10_160004_create_user_branches_table',1),
(11,'2026_09_10_160005_create_user_departments_table',1),
(12,'2026_09_10_160006_create_audit_logs_table',1),
(13,'2026_09_10_160007_add_additional_fields_to_users_table',1),
(14,'2026_09_10_190001_create_patients_table',1),
(15,'2026_09_10_190002_create_patient_branch_registrations_table',1),
(16,'2026_09_10_190003_create_patient_identifiers_table',1),
(17,'2026_09_10_190004_create_patient_contacts_table',1),
(18,'2026_09_11_010001_create_encounters_table',1),
(19,'2026_09_11_010002_update_encounters_status_default',1),
(20,'2026_09_21_010001_create_countries_table',1),
(21,'2026_09_21_010002_create_states_table',1),
(22,'2026_09_21_010003_create_currencies_table',1),
(23,'2026_09_21_010004_create_identification_types_table',1),
(24,'2026_09_21_010005_add_country_state_to_patients_table',1),
(25,'2026_09_21_010006_add_identification_type_to_patient_identifiers_table',1),
(26,'2026_09_21_010007_add_mfa_fields_to_users_table',1),
(27,'2026_09_21_010008_create_login_histories_table',1),
(28,'2026_09_21_010009_add_soft_deletes_to_countries_table',1),
(29,'2026_09_21_010010_add_soft_deletes_to_states_table',1),
(30,'2026_09_21_010011_add_soft_deletes_to_currencies_table',1),
(31,'2026_09_21_010012_add_soft_deletes_to_identification_types_table',1),
(32,'2026_09_21_010013_add_profile_picture_to_users_table',1),
(33,'2026_09_21_020001_create_patient_allergies_table',1),
(34,'2026_09_21_020002_create_patient_histories_table',1),
(35,'2026_09_21_020003_create_patient_documents_table',1),
(36,'2026_09_21_030001_create_doctor_schedules_table',1),
(37,'2026_09_21_030002_create_appointment_slots_table',1),
(38,'2026_09_21_030003_create_appointments_table',1),
(39,'2026_09_21_030004_create_appointment_tokens_table',1),
(40,'2026_09_21_030005_create_vital_signs_table',1),
(41,'2026_09_21_030006_create_diagnoses_table',1),
(42,'2026_09_21_030007_create_prescriptions_table',1),
(43,'2026_09_21_030008_create_investigation_orders_table',1),
(44,'2026_09_21_040000_add_timestamps_to_appointments_table',1),
(45,'2026_09_22_170311_add_lockout_fields_to_users_table',1),
(46,'2026_09_22_170312_add_request_id_and_module_to_audit_logs_table',1),
(47,'2026_09_22_230435_create_workflows_table',1),
(48,'2026_09_22_230436_create_workflow_steps_table',1),
(49,'2026_09_22_230437_create_workflow_approvers_table',1),
(50,'2026_09_22_230438_create_workflow_instances_table',1),
(51,'2026_09_22_230439_create_workflow_actions_table',1),
(52,'2026_09_22_234843_create_patient_number_counters_table',1),
(53,'2026_09_22_234844_add_merge_support_to_patients_table',1),
(54,'2026_09_22_234845_add_verification_fields_to_patient_identifiers_table',1),
(55,'2026_09_22_234903_create_genders_table',1),
(56,'2026_09_22_234904_create_marital_statuses_table',1),
(57,'2026_09_22_234905_create_patient_types_table',1),
(58,'2026_09_22_234907_create_patient_addresses_table',1),
(59,'2026_09_22_234908_create_patient_guardians_table',1),
(60,'2026_09_22_234909_create_patient_preferences_table',1),
(61,'2026_09_22_234911_create_patient_duplicate_candidates_table',1),
(62,'2026_09_22_234912_create_patient_amendments_table',1),
(63,'2026_09_22_234913_create_patient_timeline_events_table',1),
(64,'2026_09_22_234914_create_patient_portal_accounts_table',1),
(65,'2026_09_23_003800_create_appointment_number_counters_table',1),
(66,'2026_09_23_003801_create_appointment_types_table',1),
(67,'2026_09_23_003802_create_providers_table',1),
(68,'2026_09_23_003803_create_appointment_rooms_table',1),
(69,'2026_09_23_003804_create_hospital_holidays_table',1),
(70,'2026_09_23_003805_create_provider_unavailability_table',1),
(71,'2026_09_23_003806_add_scheduling_fields_to_doctor_schedules_table',1),
(72,'2026_09_23_003807_add_scheduling_fields_to_appointments_table',1),
(73,'2026_09_23_003808_add_priority_fields_to_appointment_tokens_table',1),
(74,'2026_09_23_003810_create_appointment_reminder_rules_table',1),
(75,'2026_09_23_003811_create_appointment_reminders_table',1),
(76,'2026_09_23_003812_backfill_providers_from_doctor_users',1),
(77,'2026_09_23_004230_add_provider_time_index_to_appointments_table',1),
(78,'2026_09_23_010000_create_settings_table',1),
(79,'2026_09_23_020000_create_activity_logs_table',1),
(80,'2026_09_23_030000_create_notifications_table',1),
(81,'2026_09_23_040000_create_files_table',1),
(82,'2026_09_23_040100_add_expanded_demographics_to_patients_table',1),
(83,'2026_09_23_040101_create_patient_consents_table',1),
(84,'2026_09_23_040200_create_appointment_documents_table',1),
(85,'2026_09_23_044945_add_coding_fields_to_diagnoses_table',1),
(86,'2026_09_23_044946_create_clinical_number_counters_table',1),
(87,'2026_09_23_044947_make_appointment_id_nullable_on_vital_signs_table',1),
(88,'2026_09_23_050000_create_patient_alerts_table',1),
(89,'2026_09_23_060000_create_appointment_history_and_notes_tables',1),
(90,'2026_09_23_070000_add_lifecycle_fields_to_appointments_table',1),
(91,'2026_09_23_080000_create_encounter_counters_table',1),
(92,'2026_09_23_080001_add_phase3_fields_to_encounters_table',1),
(93,'2026_09_23_080002_create_encounter_types_and_specialties_tables',1),
(94,'2026_09_23_080003_create_encounter_status_history_table',1),
(95,'2026_09_23_080004_create_clinical_data_tables',1),
(96,'2026_09_23_080005_rename_encounter_status_history_table',1),
(97,'2026_09_23_080006_add_encounter_id_to_clinical_tables',1),
(98,'2026_09_23_080007_add_encounter_id_to_patient_problems_table',1),
(99,'2026_09_23_080008_update_encounter_status_history_fields',1),
(100,'2026_09_23_080009_create_break_glass_accesses_table',1),
(101,'2026_09_23_080010_add_prescription_issuance_fields',1),
(102,'2026_09_23_080011_create_encounter_vitals_table',1),
(103,'2026_09_23_080012_create_encounter_assessments_table',1),
(104,'2026_09_23_080013_create_encounter_allergies_table',1),
(105,'2026_09_23_080014_create_encounter_medication_histories_table',1),
(106,'2026_09_23_080015_create_encounter_follow_ups_table',1),
(107,'2026_09_23_080016_add_encounter_foreign_keys',1),
(108,'2026_09_23_090000_add_specialty_foreign_keys_to_appointment_tables',1),
(109,'2026_09_24_000001_create_billing_categories_table',1),
(110,'2026_09_24_000002_create_billing_tax_categories_table',1),
(111,'2026_09_24_000003_create_billing_payment_methods_table',1),
(112,'2026_09_24_000004_create_billing_corporates_table',1),
(113,'2026_09_24_000005_create_billing_insurance_providers_table',1),
(114,'2026_09_24_000006_create_billing_items_table',1),
(115,'2026_09_24_000007_create_billing_price_lists_table',1),
(116,'2026_09_24_000008_create_billing_insurance_policies_table',1),
(117,'2026_09_24_000009_create_billing_corporate_contracts_table',1),
(118,'2026_09_24_000010_create_billing_corporate_members_table',1),
(119,'2026_09_24_000011_create_billing_price_list_items_table',1),
(120,'2026_09_24_000012_create_billing_counters_table',1),
(121,'2026_09_24_000013_create_billing_charges_table',1),
(122,'2026_09_24_000014_create_billing_invoices_table',1),
(123,'2026_09_24_000015_create_billing_invoice_items_table',1),
(124,'2026_09_24_000016_create_billing_payments_table',1),
(125,'2026_09_24_000017_create_billing_receipts_table',1),
(126,'2026_09_24_000018_create_billing_refunds_table',1),
(127,'2026_09_24_000019_create_billing_adjustments_table',1),
(128,'2026_09_24_000020_create_billing_advance_accounts_table',1),
(129,'2026_09_24_000021_create_billing_advance_transactions_table',1),
(130,'2026_09_24_000022_create_billing_cashier_sessions_table',1),
(131,'2026_09_24_000023_add_billing_payment_cashier_foreign_key',1),
(132,'2026_09_24_000024_add_billing_financial_reporting_indexes',1),
(133,'2026_09_24_000025_add_void_fields_to_billing_receipts_table',1),
(134,'2026_09_25_000001_create_lab_sections_table',1),
(135,'2026_09_25_000002_create_lab_test_categories_table',1),
(136,'2026_09_25_000003_create_lab_specimen_types_table',1),
(137,'2026_09_25_000004_create_lab_container_types_table',1),
(138,'2026_09_25_000005_create_lab_tests_table',1),
(139,'2026_09_25_000006_create_lab_panels_table',1),
(140,'2026_09_25_000007_create_lab_reference_ranges_table',1),
(141,'2026_09_25_000008_create_lab_critical_values_table',1),
(142,'2026_09_25_000009_create_lab_counters_table',1),
(143,'2026_09_25_000010_create_lab_orders_table',1),
(144,'2026_09_25_000011_create_lab_specimens_table',1),
(145,'2026_09_25_000012_create_lab_results_table',1),
(146,'2026_09_25_000013_create_lab_critical_result_alerts_table',1),
(147,'2026_09_25_000014_create_lab_reports_table',1),
(148,'2026_09_25_000015_create_lab_analyzer_tables',1),
(149,'2026_09_25_000016_create_lab_qc_tables',1),
(150,'2026_09_26_000001_create_radiology_sections_table',1),
(151,'2026_09_26_000002_create_radiology_body_parts_table',1),
(152,'2026_09_26_000003_create_radiology_contrast_agents_table',1),
(153,'2026_09_26_000004_create_radiology_modalities_table',1),
(154,'2026_09_26_000005_create_radiology_procedures_table',1),
(155,'2026_09_26_000006_create_radiology_protocols_table',1),
(156,'2026_09_26_000007_create_radiology_counters_table',1),
(157,'2026_09_26_000008_create_radiology_orders_table',1),
(158,'2026_09_26_000009_create_radiology_examinations_table',1),
(159,'2026_09_26_000010_create_radiology_pacs_servers_table',1),
(160,'2026_09_26_000011_create_radiology_studies_table',1),
(161,'2026_09_26_000012_create_radiology_series_and_instances_tables',1),
(162,'2026_09_26_000013_create_radiology_dicom_events_table',1),
(163,'2026_09_26_000014_create_radiology_report_templates_table',1),
(164,'2026_09_26_000015_create_radiology_reports_table',1),
(165,'2026_09_26_000016_create_radiology_report_findings_table',1),
(166,'2026_09_26_000017_create_radiology_critical_findings_table',1),
(167,'2026_09_27_000001_create_pharmacy_dosage_forms_table',1),
(168,'2026_09_27_000002_create_pharmacy_routes_table',1),
(169,'2026_09_27_000003_create_pharmacy_generics_table',1),
(170,'2026_09_27_000004_create_pharmacy_brands_table',1),
(171,'2026_09_27_000005_create_pharmacy_medications_table',1),
(172,'2026_09_27_000006_create_pharmacy_medication_ingredients_table',1),
(173,'2026_09_27_000007_create_pharmacy_stores_table',1),
(174,'2026_09_27_000008_create_pharmacy_medication_store_levels_table',1),
(175,'2026_09_27_000009_create_pharmacy_counters_table',1),
(176,'2026_09_27_000010_create_pharmacy_batches_table',1),
(177,'2026_09_27_000011_create_pharmacy_stock_table',1),
(178,'2026_09_27_000012_create_pharmacy_stock_transactions_table',1),
(179,'2026_09_27_000013_create_pharmacy_orders_table',1),
(180,'2026_09_27_000014_create_pharmacy_order_items_table',1),
(181,'2026_09_27_000015_create_pharmacy_dispensings_table',1),
(182,'2026_09_27_000016_create_pharmacy_dispensing_items_table',1),
(183,'2026_09_27_000017_create_pharmacy_safety_alerts_table',1),
(184,'2026_09_27_000018_create_pharmacy_controlled_drug_transactions_table',1),
(185,'2026_09_27_000019_create_pharmacy_returns_table',1),
(186,'2026_09_27_000020_create_pharmacy_return_items_table',1),
(187,'2026_09_27_000021_create_pharmacy_transfers_table',1),
(188,'2026_09_27_000022_create_pharmacy_transfer_items_table',1),
(189,'2026_09_27_000023_create_pharmacy_stock_counts_table',1),
(190,'2026_09_27_000024_create_pharmacy_stock_count_items_table',1),
(191,'2026_09_27_000025_create_pharmacy_quarantine_table',1),
(192,'2026_09_27_000026_create_pharmacy_recalls_table',1),
(193,'2026_09_28_000001_create_ipd_buildings_table',1),
(194,'2026_09_28_000002_create_ipd_floors_table',1),
(195,'2026_09_28_000003_create_ipd_bed_types_table',1),
(196,'2026_09_28_000004_create_ipd_wards_table',1),
(197,'2026_09_28_000005_create_ipd_rooms_table',1),
(198,'2026_09_28_000006_create_ipd_beds_table',1),
(199,'2026_09_28_000007_create_ipd_counters_table',1),
(200,'2026_09_28_000008_create_ipd_admission_types_table',1),
(201,'2026_09_28_000009_create_ipd_admission_sources_table',1),
(202,'2026_09_28_000010_create_ipd_admission_requests_table',1),
(203,'2026_09_28_000011_create_ipd_bed_reservations_table',1),
(204,'2026_09_28_000012_create_ipd_discharge_dispositions_table',1),
(205,'2026_09_28_000013_create_ipd_admissions_table',1),
(206,'2026_09_28_000014_create_ipd_provider_assignments_table',1),
(207,'2026_09_28_000015_create_ipd_bed_allocations_table',1),
(208,'2026_09_28_000016_create_ipd_bed_movements_table',1),
(209,'2026_09_28_000017_create_ipd_discharge_requests_table',1),
(210,'2026_09_28_000018_create_ipd_patient_leaves_table',1),
(211,'2026_09_28_000019_create_ipd_bed_blocks_table',1),
(212,'2026_09_28_000020_create_ipd_bed_charge_events_table',1),
(213,'2026_09_29_000001_create_nursing_shifts_table',2),
(214,'2026_09_29_000002_create_nursing_episodes_table',2),
(215,'2026_09_29_000003_create_nursing_assignments_table',2),
(216,'2026_09_29_000004_create_nursing_assessments_table',2),
(217,'2026_09_29_000005_create_nursing_observations_table',3),
(218,'2026_09_29_000006_create_nursing_pain_assessments_table',3),
(219,'2026_09_29_000007_create_nursing_risk_assessments_table',3),
(220,'2026_09_29_000008_create_nursing_intake_output_records_table',3),
(221,'2026_09_29_000009_create_nursing_care_plans_table',3),
(222,'2026_09_29_000010_create_nursing_diagnoses_table',3),
(223,'2026_09_29_000011_create_nursing_care_plan_goals_table',3),
(224,'2026_09_29_000012_create_nursing_care_plan_interventions_table',3),
(225,'2026_09_29_000013_create_nursing_tasks_table',3),
(226,'2026_09_29_000014_create_nursing_medication_administrations_table',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `model_has_permissions` VALUES
(1,'App\\Models\\User',1),
(2,'App\\Models\\User',1),
(3,'App\\Models\\User',1),
(4,'App\\Models\\User',1),
(5,'App\\Models\\User',1),
(6,'App\\Models\\User',1),
(7,'App\\Models\\User',1),
(8,'App\\Models\\User',1),
(9,'App\\Models\\User',1),
(10,'App\\Models\\User',1),
(11,'App\\Models\\User',1),
(12,'App\\Models\\User',1),
(13,'App\\Models\\User',1),
(14,'App\\Models\\User',1),
(15,'App\\Models\\User',1),
(16,'App\\Models\\User',1),
(17,'App\\Models\\User',1),
(18,'App\\Models\\User',1),
(19,'App\\Models\\User',1),
(20,'App\\Models\\User',1),
(21,'App\\Models\\User',1),
(22,'App\\Models\\User',1),
(23,'App\\Models\\User',1),
(24,'App\\Models\\User',1),
(25,'App\\Models\\User',1),
(26,'App\\Models\\User',1),
(27,'App\\Models\\User',1),
(28,'App\\Models\\User',1),
(29,'App\\Models\\User',1),
(30,'App\\Models\\User',1),
(31,'App\\Models\\User',1),
(32,'App\\Models\\User',1),
(33,'App\\Models\\User',1),
(34,'App\\Models\\User',1),
(35,'App\\Models\\User',1),
(36,'App\\Models\\User',1),
(37,'App\\Models\\User',1),
(38,'App\\Models\\User',1),
(39,'App\\Models\\User',1),
(40,'App\\Models\\User',1),
(41,'App\\Models\\User',1),
(42,'App\\Models\\User',1),
(43,'App\\Models\\User',1),
(44,'App\\Models\\User',1),
(45,'App\\Models\\User',1),
(46,'App\\Models\\User',1),
(47,'App\\Models\\User',1),
(48,'App\\Models\\User',1),
(49,'App\\Models\\User',1),
(50,'App\\Models\\User',1),
(51,'App\\Models\\User',1),
(52,'App\\Models\\User',1),
(53,'App\\Models\\User',1),
(54,'App\\Models\\User',1),
(55,'App\\Models\\User',1),
(56,'App\\Models\\User',1),
(57,'App\\Models\\User',1),
(58,'App\\Models\\User',1),
(59,'App\\Models\\User',1),
(60,'App\\Models\\User',1),
(61,'App\\Models\\User',1),
(62,'App\\Models\\User',1),
(63,'App\\Models\\User',1),
(64,'App\\Models\\User',1),
(65,'App\\Models\\User',1),
(66,'App\\Models\\User',1),
(67,'App\\Models\\User',1),
(68,'App\\Models\\User',1),
(69,'App\\Models\\User',1),
(70,'App\\Models\\User',1),
(71,'App\\Models\\User',1),
(72,'App\\Models\\User',1),
(73,'App\\Models\\User',1),
(74,'App\\Models\\User',1),
(75,'App\\Models\\User',1),
(76,'App\\Models\\User',1),
(77,'App\\Models\\User',1),
(78,'App\\Models\\User',1),
(79,'App\\Models\\User',1),
(80,'App\\Models\\User',1),
(81,'App\\Models\\User',1),
(82,'App\\Models\\User',1),
(83,'App\\Models\\User',1),
(84,'App\\Models\\User',1),
(85,'App\\Models\\User',1),
(86,'App\\Models\\User',1),
(87,'App\\Models\\User',1),
(88,'App\\Models\\User',1),
(89,'App\\Models\\User',1),
(90,'App\\Models\\User',1),
(91,'App\\Models\\User',1),
(92,'App\\Models\\User',1),
(93,'App\\Models\\User',1),
(94,'App\\Models\\User',1),
(95,'App\\Models\\User',1),
(96,'App\\Models\\User',1),
(97,'App\\Models\\User',1),
(98,'App\\Models\\User',1),
(99,'App\\Models\\User',1),
(100,'App\\Models\\User',1),
(101,'App\\Models\\User',1),
(102,'App\\Models\\User',1),
(103,'App\\Models\\User',1),
(104,'App\\Models\\User',1),
(105,'App\\Models\\User',1),
(106,'App\\Models\\User',1),
(107,'App\\Models\\User',1),
(108,'App\\Models\\User',1),
(109,'App\\Models\\User',1),
(110,'App\\Models\\User',1),
(111,'App\\Models\\User',1),
(112,'App\\Models\\User',1),
(113,'App\\Models\\User',1),
(114,'App\\Models\\User',1),
(115,'App\\Models\\User',1),
(116,'App\\Models\\User',1),
(117,'App\\Models\\User',1),
(118,'App\\Models\\User',1),
(119,'App\\Models\\User',1),
(120,'App\\Models\\User',1),
(121,'App\\Models\\User',1),
(122,'App\\Models\\User',1),
(123,'App\\Models\\User',1),
(124,'App\\Models\\User',1),
(125,'App\\Models\\User',1),
(126,'App\\Models\\User',1),
(127,'App\\Models\\User',1),
(128,'App\\Models\\User',1),
(129,'App\\Models\\User',1),
(130,'App\\Models\\User',1),
(131,'App\\Models\\User',1),
(132,'App\\Models\\User',1),
(133,'App\\Models\\User',1),
(134,'App\\Models\\User',1),
(135,'App\\Models\\User',1),
(136,'App\\Models\\User',1),
(137,'App\\Models\\User',1),
(138,'App\\Models\\User',1),
(139,'App\\Models\\User',1),
(140,'App\\Models\\User',1),
(141,'App\\Models\\User',1),
(142,'App\\Models\\User',1),
(143,'App\\Models\\User',1),
(144,'App\\Models\\User',1),
(145,'App\\Models\\User',1),
(146,'App\\Models\\User',1),
(147,'App\\Models\\User',1),
(148,'App\\Models\\User',1),
(149,'App\\Models\\User',1),
(150,'App\\Models\\User',1),
(151,'App\\Models\\User',1),
(152,'App\\Models\\User',1),
(153,'App\\Models\\User',1),
(154,'App\\Models\\User',1),
(155,'App\\Models\\User',1),
(156,'App\\Models\\User',1),
(157,'App\\Models\\User',1),
(158,'App\\Models\\User',1),
(159,'App\\Models\\User',1),
(160,'App\\Models\\User',1),
(161,'App\\Models\\User',1),
(162,'App\\Models\\User',1),
(163,'App\\Models\\User',1),
(164,'App\\Models\\User',1),
(165,'App\\Models\\User',1),
(166,'App\\Models\\User',1),
(167,'App\\Models\\User',1),
(168,'App\\Models\\User',1),
(169,'App\\Models\\User',1),
(170,'App\\Models\\User',1),
(171,'App\\Models\\User',1),
(172,'App\\Models\\User',1),
(173,'App\\Models\\User',1),
(174,'App\\Models\\User',1),
(175,'App\\Models\\User',1),
(176,'App\\Models\\User',1),
(177,'App\\Models\\User',1),
(178,'App\\Models\\User',1),
(179,'App\\Models\\User',1),
(180,'App\\Models\\User',1),
(181,'App\\Models\\User',1),
(182,'App\\Models\\User',1),
(183,'App\\Models\\User',1),
(184,'App\\Models\\User',1),
(185,'App\\Models\\User',1),
(186,'App\\Models\\User',1),
(187,'App\\Models\\User',1),
(188,'App\\Models\\User',1),
(189,'App\\Models\\User',1),
(190,'App\\Models\\User',1),
(191,'App\\Models\\User',1),
(192,'App\\Models\\User',1),
(193,'App\\Models\\User',1),
(194,'App\\Models\\User',1),
(195,'App\\Models\\User',1),
(196,'App\\Models\\User',1),
(197,'App\\Models\\User',1),
(198,'App\\Models\\User',1),
(199,'App\\Models\\User',1),
(200,'App\\Models\\User',1),
(201,'App\\Models\\User',1),
(202,'App\\Models\\User',1),
(203,'App\\Models\\User',1),
(204,'App\\Models\\User',1),
(205,'App\\Models\\User',1),
(206,'App\\Models\\User',1),
(207,'App\\Models\\User',1),
(208,'App\\Models\\User',1),
(209,'App\\Models\\User',1),
(210,'App\\Models\\User',1),
(211,'App\\Models\\User',1),
(212,'App\\Models\\User',1),
(213,'App\\Models\\User',1),
(214,'App\\Models\\User',1),
(215,'App\\Models\\User',1),
(216,'App\\Models\\User',1),
(217,'App\\Models\\User',1),
(218,'App\\Models\\User',1),
(219,'App\\Models\\User',1),
(220,'App\\Models\\User',1),
(221,'App\\Models\\User',1),
(222,'App\\Models\\User',1),
(223,'App\\Models\\User',1),
(224,'App\\Models\\User',1),
(225,'App\\Models\\User',1),
(226,'App\\Models\\User',1),
(227,'App\\Models\\User',1),
(228,'App\\Models\\User',1),
(229,'App\\Models\\User',1),
(230,'App\\Models\\User',1),
(231,'App\\Models\\User',1),
(232,'App\\Models\\User',1),
(233,'App\\Models\\User',1),
(234,'App\\Models\\User',1),
(235,'App\\Models\\User',1),
(236,'App\\Models\\User',1),
(237,'App\\Models\\User',1),
(238,'App\\Models\\User',1),
(239,'App\\Models\\User',1),
(240,'App\\Models\\User',1),
(241,'App\\Models\\User',1),
(242,'App\\Models\\User',1),
(243,'App\\Models\\User',1),
(244,'App\\Models\\User',1),
(245,'App\\Models\\User',1),
(246,'App\\Models\\User',1),
(247,'App\\Models\\User',1),
(248,'App\\Models\\User',1),
(249,'App\\Models\\User',1),
(250,'App\\Models\\User',1),
(251,'App\\Models\\User',1),
(252,'App\\Models\\User',1),
(253,'App\\Models\\User',1),
(254,'App\\Models\\User',1),
(255,'App\\Models\\User',1),
(256,'App\\Models\\User',1),
(257,'App\\Models\\User',1),
(258,'App\\Models\\User',1),
(259,'App\\Models\\User',1),
(260,'App\\Models\\User',1),
(261,'App\\Models\\User',1),
(262,'App\\Models\\User',1),
(263,'App\\Models\\User',1),
(264,'App\\Models\\User',1),
(265,'App\\Models\\User',1),
(266,'App\\Models\\User',1),
(267,'App\\Models\\User',1),
(268,'App\\Models\\User',1),
(269,'App\\Models\\User',1),
(270,'App\\Models\\User',1),
(271,'App\\Models\\User',1),
(272,'App\\Models\\User',1),
(273,'App\\Models\\User',1),
(274,'App\\Models\\User',1),
(275,'App\\Models\\User',1),
(276,'App\\Models\\User',1),
(277,'App\\Models\\User',1),
(278,'App\\Models\\User',1),
(279,'App\\Models\\User',1),
(280,'App\\Models\\User',1),
(281,'App\\Models\\User',1),
(282,'App\\Models\\User',1),
(283,'App\\Models\\User',1),
(284,'App\\Models\\User',1),
(285,'App\\Models\\User',1),
(286,'App\\Models\\User',1),
(287,'App\\Models\\User',1),
(288,'App\\Models\\User',1),
(289,'App\\Models\\User',1),
(290,'App\\Models\\User',1),
(291,'App\\Models\\User',1),
(292,'App\\Models\\User',1),
(293,'App\\Models\\User',1),
(294,'App\\Models\\User',1),
(295,'App\\Models\\User',1),
(296,'App\\Models\\User',1),
(297,'App\\Models\\User',1),
(298,'App\\Models\\User',1),
(299,'App\\Models\\User',1),
(300,'App\\Models\\User',1),
(301,'App\\Models\\User',1),
(302,'App\\Models\\User',1),
(303,'App\\Models\\User',1),
(304,'App\\Models\\User',1),
(305,'App\\Models\\User',1),
(306,'App\\Models\\User',1),
(307,'App\\Models\\User',1),
(308,'App\\Models\\User',1),
(309,'App\\Models\\User',1),
(310,'App\\Models\\User',1),
(311,'App\\Models\\User',1),
(312,'App\\Models\\User',1),
(313,'App\\Models\\User',1),
(314,'App\\Models\\User',1),
(315,'App\\Models\\User',1),
(316,'App\\Models\\User',1),
(317,'App\\Models\\User',1),
(318,'App\\Models\\User',1),
(319,'App\\Models\\User',1),
(320,'App\\Models\\User',1),
(321,'App\\Models\\User',1),
(322,'App\\Models\\User',1),
(323,'App\\Models\\User',1),
(324,'App\\Models\\User',1),
(325,'App\\Models\\User',1),
(326,'App\\Models\\User',1),
(327,'App\\Models\\User',1),
(328,'App\\Models\\User',1),
(329,'App\\Models\\User',1),
(330,'App\\Models\\User',1),
(331,'App\\Models\\User',1),
(332,'App\\Models\\User',1),
(333,'App\\Models\\User',1),
(334,'App\\Models\\User',1),
(335,'App\\Models\\User',1),
(336,'App\\Models\\User',1),
(337,'App\\Models\\User',1),
(338,'App\\Models\\User',1),
(339,'App\\Models\\User',1),
(340,'App\\Models\\User',1),
(341,'App\\Models\\User',1),
(342,'App\\Models\\User',1),
(343,'App\\Models\\User',1),
(344,'App\\Models\\User',1),
(345,'App\\Models\\User',1),
(346,'App\\Models\\User',1),
(347,'App\\Models\\User',1),
(348,'App\\Models\\User',1),
(349,'App\\Models\\User',1),
(350,'App\\Models\\User',1),
(351,'App\\Models\\User',1),
(352,'App\\Models\\User',1),
(353,'App\\Models\\User',1),
(354,'App\\Models\\User',1),
(355,'App\\Models\\User',1),
(356,'App\\Models\\User',1),
(357,'App\\Models\\User',1),
(358,'App\\Models\\User',1),
(359,'App\\Models\\User',1),
(360,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `model_has_roles` VALUES
(1,'App\\Models\\User',1),
(3,'App\\Models\\User',2);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'database',
  `channel` varchar(255) NOT NULL DEFAULT 'database',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  KEY `notifications_created_at_index` (`created_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_assessments`
--

DROP TABLE IF EXISTS `nursing_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `assessment_type` varchar(255) NOT NULL DEFAULT 'initial',
  `template_key` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sections`)),
  `finalized_at` timestamp NULL DEFAULT NULL,
  `finalized_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_assessments_company_id_foreign` (`company_id`),
  KEY `nursing_assessments_branch_id_foreign` (`branch_id`),
  KEY `nursing_assessments_patient_id_foreign` (`patient_id`),
  KEY `nursing_assessments_encounter_id_foreign` (`encounter_id`),
  KEY `nursing_assessments_finalized_by_foreign` (`finalized_by`),
  KEY `nursing_assessments_created_by_foreign` (`created_by`),
  KEY `nursing_assessments_episode_id_assessment_type_index` (`episode_id`,`assessment_type`),
  KEY `nursing_assessments_admission_id_status_index` (`admission_id`,`status`),
  CONSTRAINT `nursing_assessments_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_assessments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_assessments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_assessments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_assessments_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_assessments_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_assessments_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_assessments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_assessments`
--

LOCK TABLES `nursing_assessments` WRITE;
/*!40000 ALTER TABLE `nursing_assessments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_assessments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_assignments`
--

DROP TABLE IF EXISTS `nursing_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `ward_id` bigint(20) unsigned DEFAULT NULL,
  `bed_id` bigint(20) unsigned DEFAULT NULL,
  `nurse_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `assignment_type` varchar(255) NOT NULL DEFAULT 'patient',
  `started_at` timestamp NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_assignments_patient_id_foreign` (`patient_id`),
  KEY `nursing_assignments_ward_id_foreign` (`ward_id`),
  KEY `nursing_assignments_bed_id_foreign` (`bed_id`),
  KEY `nursing_assignments_shift_id_foreign` (`shift_id`),
  KEY `nursing_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `nursing_assignments_episode_id_ended_at_index` (`episode_id`,`ended_at`),
  KEY `nursing_assignments_nurse_id_ended_at_index` (`nurse_id`,`ended_at`),
  KEY `nursing_assignments_admission_id_ended_at_index` (`admission_id`,`ended_at`),
  CONSTRAINT `nursing_assignments_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_assignments_bed_id_foreign` FOREIGN KEY (`bed_id`) REFERENCES `ipd_beds` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_assignments_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_assignments_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_assignments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_assignments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `nursing_shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_assignments_ward_id_foreign` FOREIGN KEY (`ward_id`) REFERENCES `ipd_wards` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_assignments`
--

LOCK TABLES `nursing_assignments` WRITE;
/*!40000 ALTER TABLE `nursing_assignments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_assignments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_care_plan_goals`
--

DROP TABLE IF EXISTS `nursing_care_plan_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_care_plan_goals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `care_plan_id` bigint(20) unsigned NOT NULL,
  `nursing_diagnosis_id` bigint(20) unsigned DEFAULT NULL,
  `goal_text` text NOT NULL,
  `target_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_care_plan_goals_nursing_diagnosis_id_foreign` (`nursing_diagnosis_id`),
  KEY `nursing_care_plan_goals_care_plan_id_status_index` (`care_plan_id`,`status`),
  CONSTRAINT `nursing_care_plan_goals_care_plan_id_foreign` FOREIGN KEY (`care_plan_id`) REFERENCES `nursing_care_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_care_plan_goals_nursing_diagnosis_id_foreign` FOREIGN KEY (`nursing_diagnosis_id`) REFERENCES `nursing_diagnoses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_care_plan_goals`
--

LOCK TABLES `nursing_care_plan_goals` WRITE;
/*!40000 ALTER TABLE `nursing_care_plan_goals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_care_plan_goals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_care_plan_interventions`
--

DROP TABLE IF EXISTS `nursing_care_plan_interventions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_care_plan_interventions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `care_plan_id` bigint(20) unsigned NOT NULL,
  `goal_id` bigint(20) unsigned DEFAULT NULL,
  `intervention_type` varchar(255) NOT NULL,
  `frequency` varchar(255) DEFAULT NULL,
  `responsible_nurse_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `evaluation_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_care_plan_interventions_goal_id_foreign` (`goal_id`),
  KEY `nursing_care_plan_interventions_responsible_nurse_id_foreign` (`responsible_nurse_id`),
  KEY `nursing_care_plan_interventions_care_plan_id_status_index` (`care_plan_id`,`status`),
  CONSTRAINT `nursing_care_plan_interventions_care_plan_id_foreign` FOREIGN KEY (`care_plan_id`) REFERENCES `nursing_care_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_care_plan_interventions_goal_id_foreign` FOREIGN KEY (`goal_id`) REFERENCES `nursing_care_plan_goals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_care_plan_interventions_responsible_nurse_id_foreign` FOREIGN KEY (`responsible_nurse_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_care_plan_interventions`
--

LOCK TABLES `nursing_care_plan_interventions` WRITE;
/*!40000 ALTER TABLE `nursing_care_plan_interventions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_care_plan_interventions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_care_plans`
--

DROP TABLE IF EXISTS `nursing_care_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_care_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_care_plans_company_id_foreign` (`company_id`),
  KEY `nursing_care_plans_branch_id_foreign` (`branch_id`),
  KEY `nursing_care_plans_admission_id_foreign` (`admission_id`),
  KEY `nursing_care_plans_patient_id_foreign` (`patient_id`),
  KEY `nursing_care_plans_created_by_foreign` (`created_by`),
  KEY `nursing_care_plans_episode_id_status_index` (`episode_id`,`status`),
  CONSTRAINT `nursing_care_plans_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_care_plans_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_care_plans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_care_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_care_plans_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_care_plans_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_care_plans`
--

LOCK TABLES `nursing_care_plans` WRITE;
/*!40000 ALTER TABLE `nursing_care_plans` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_care_plans` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_diagnoses`
--

DROP TABLE IF EXISTS `nursing_diagnoses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_diagnoses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `care_plan_id` bigint(20) unsigned NOT NULL,
  `diagnosis_text` text NOT NULL,
  `related_factors` text DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `coding_system` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_diagnoses_created_by_foreign` (`created_by`),
  KEY `nursing_diagnoses_care_plan_id_status_index` (`care_plan_id`,`status`),
  CONSTRAINT `nursing_diagnoses_care_plan_id_foreign` FOREIGN KEY (`care_plan_id`) REFERENCES `nursing_care_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_diagnoses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_diagnoses`
--

LOCK TABLES `nursing_diagnoses` WRITE;
/*!40000 ALTER TABLE `nursing_diagnoses` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_diagnoses` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_episodes`
--

DROP TABLE IF EXISTS `nursing_episodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_episodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `primary_nurse_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'planned',
  `start_at` timestamp NOT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nursing_episodes_admission_id_unique` (`admission_id`),
  KEY `nursing_episodes_branch_id_foreign` (`branch_id`),
  KEY `nursing_episodes_encounter_id_foreign` (`encounter_id`),
  KEY `nursing_episodes_primary_nurse_id_foreign` (`primary_nurse_id`),
  KEY `nursing_episodes_created_by_foreign` (`created_by`),
  KEY `nursing_episodes_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `nursing_episodes_patient_id_index` (`patient_id`),
  CONSTRAINT `nursing_episodes_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_episodes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_episodes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_episodes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_episodes_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_episodes_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_episodes_primary_nurse_id_foreign` FOREIGN KEY (`primary_nurse_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_episodes`
--

LOCK TABLES `nursing_episodes` WRITE;
/*!40000 ALTER TABLE `nursing_episodes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_episodes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_intake_output_records`
--

DROP TABLE IF EXISTS `nursing_intake_output_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_intake_output_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'ml',
  `route` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_intake_output_records_company_id_foreign` (`company_id`),
  KEY `nursing_intake_output_records_branch_id_foreign` (`branch_id`),
  KEY `nursing_intake_output_records_patient_id_foreign` (`patient_id`),
  KEY `nursing_intake_output_records_recorded_by_foreign` (`recorded_by`),
  KEY `nursing_intake_output_records_episode_id_type_recorded_at_index` (`episode_id`,`type`,`recorded_at`),
  KEY `nursing_intake_output_records_admission_id_recorded_at_index` (`admission_id`,`recorded_at`),
  CONSTRAINT `nursing_intake_output_records_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_intake_output_records_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_intake_output_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_intake_output_records_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_intake_output_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_intake_output_records_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_intake_output_records`
--

LOCK TABLES `nursing_intake_output_records` WRITE;
/*!40000 ALTER TABLE `nursing_intake_output_records` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_intake_output_records` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_medication_administration_corrections`
--

DROP TABLE IF EXISTS `nursing_medication_administration_corrections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_medication_administration_corrections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `administration_id` bigint(20) unsigned NOT NULL,
  `correction_type` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `previous_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`previous_values`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_mar_corrections_admin_fk` (`administration_id`),
  KEY `nursing_medication_administration_corrections_created_by_foreign` (`created_by`),
  CONSTRAINT `nursing_mar_corrections_admin_fk` FOREIGN KEY (`administration_id`) REFERENCES `nursing_medication_administrations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_medication_administration_corrections_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_medication_administration_corrections`
--

LOCK TABLES `nursing_medication_administration_corrections` WRITE;
/*!40000 ALTER TABLE `nursing_medication_administration_corrections` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_medication_administration_corrections` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_medication_administrations`
--

DROP TABLE IF EXISTS `nursing_medication_administrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_medication_administrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `prescription_id` bigint(20) unsigned DEFAULT NULL,
  `prescription_item_id` bigint(20) unsigned DEFAULT NULL,
  `dispensing_item_id` bigint(20) unsigned DEFAULT NULL,
  `medication_id` bigint(20) unsigned DEFAULT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` timestamp NOT NULL,
  `administered_at` timestamp NULL DEFAULT NULL,
  `dose` varchar(255) DEFAULT NULL,
  `dose_unit` varchar(255) DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `reason_if_not_administered` varchar(255) DEFAULT NULL,
  `is_prn` tinyint(1) NOT NULL DEFAULT 0,
  `prn_reason` varchar(255) DEFAULT NULL,
  `administered_by` bigint(20) unsigned DEFAULT NULL,
  `witnessed_by` bigint(20) unsigned DEFAULT NULL,
  `safety_checks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`safety_checks`)),
  `notes` text DEFAULT NULL,
  `superseded_by_correction_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_medication_administrations_company_id_foreign` (`company_id`),
  KEY `nursing_medication_administrations_branch_id_foreign` (`branch_id`),
  KEY `nursing_medication_administrations_encounter_id_foreign` (`encounter_id`),
  KEY `nursing_medication_administrations_patient_id_foreign` (`patient_id`),
  KEY `nursing_medication_administrations_prescription_id_foreign` (`prescription_id`),
  KEY `nursing_medication_administrations_prescription_item_id_foreign` (`prescription_item_id`),
  KEY `nursing_medication_administrations_medication_id_foreign` (`medication_id`),
  KEY `nursing_medication_administrations_batch_id_foreign` (`batch_id`),
  KEY `nursing_medication_administrations_administered_by_foreign` (`administered_by`),
  KEY `nursing_medication_administrations_witnessed_by_foreign` (`witnessed_by`),
  KEY `nursing_medication_administrations_created_by_foreign` (`created_by`),
  KEY `nursing_mar_episode_status_sched_idx` (`episode_id`,`status`,`scheduled_at`),
  KEY `nursing_medication_administrations_admission_id_status_index` (`admission_id`,`status`),
  KEY `nursing_mar_dispensing_sched_idx` (`dispensing_item_id`,`scheduled_at`),
  CONSTRAINT `nursing_medication_administrations_administered_by_foreign` FOREIGN KEY (`administered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_medication_administrations_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_medication_administrations_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_medication_administrations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_medication_administrations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_medication_administrations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_medication_administrations_dispensing_item_id_foreign` FOREIGN KEY (`dispensing_item_id`) REFERENCES `pharmacy_dispensing_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_medication_administrations_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_medication_administrations_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_medication_administrations_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_medication_administrations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_medication_administrations_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_medication_administrations_prescription_item_id_foreign` FOREIGN KEY (`prescription_item_id`) REFERENCES `prescription_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_medication_administrations_witnessed_by_foreign` FOREIGN KEY (`witnessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_medication_administrations`
--

LOCK TABLES `nursing_medication_administrations` WRITE;
/*!40000 ALTER TABLE `nursing_medication_administrations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_medication_administrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_observations`
--

DROP TABLE IF EXISTS `nursing_observations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_observations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `observation_type` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `reference_range_low` decimal(10,2) DEFAULT NULL,
  `reference_range_high` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'final',
  `observed_at` timestamp NOT NULL,
  `observed_by` bigint(20) unsigned DEFAULT NULL,
  `device_source` varchar(255) DEFAULT NULL,
  `corrects_observation_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_observations_company_id_foreign` (`company_id`),
  KEY `nursing_observations_branch_id_foreign` (`branch_id`),
  KEY `nursing_observations_admission_id_foreign` (`admission_id`),
  KEY `nursing_observations_encounter_id_foreign` (`encounter_id`),
  KEY `nursing_observations_observed_by_foreign` (`observed_by`),
  KEY `nursing_observations_corrects_observation_id_foreign` (`corrects_observation_id`),
  KEY `nursing_obs_episode_type_observed_idx` (`episode_id`,`observation_type`,`observed_at`),
  KEY `nursing_observations_patient_id_observed_at_index` (`patient_id`,`observed_at`),
  CONSTRAINT `nursing_observations_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_observations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_observations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_observations_corrects_observation_id_foreign` FOREIGN KEY (`corrects_observation_id`) REFERENCES `nursing_observations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_observations_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_observations_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_observations_observed_by_foreign` FOREIGN KEY (`observed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_observations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_observations`
--

LOCK TABLES `nursing_observations` WRITE;
/*!40000 ALTER TABLE `nursing_observations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_observations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_pain_assessments`
--

DROP TABLE IF EXISTS `nursing_pain_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_pain_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `observation_id` bigint(20) unsigned DEFAULT NULL,
  `scale_type` varchar(255) NOT NULL DEFAULT 'numeric',
  `score` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `character` varchar(255) DEFAULT NULL,
  `onset` varchar(255) DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `aggravating_factors` text DEFAULT NULL,
  `relieving_factors` text DEFAULT NULL,
  `intervention` text DEFAULT NULL,
  `reassessment_due_at` timestamp NULL DEFAULT NULL,
  `assessed_by` bigint(20) unsigned DEFAULT NULL,
  `assessed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_pain_assessments_company_id_foreign` (`company_id`),
  KEY `nursing_pain_assessments_branch_id_foreign` (`branch_id`),
  KEY `nursing_pain_assessments_admission_id_foreign` (`admission_id`),
  KEY `nursing_pain_assessments_patient_id_foreign` (`patient_id`),
  KEY `nursing_pain_assessments_observation_id_foreign` (`observation_id`),
  KEY `nursing_pain_assessments_assessed_by_foreign` (`assessed_by`),
  KEY `nursing_pain_assessments_episode_id_assessed_at_index` (`episode_id`,`assessed_at`),
  CONSTRAINT `nursing_pain_assessments_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_pain_assessments_assessed_by_foreign` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_pain_assessments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_pain_assessments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_pain_assessments_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_pain_assessments_observation_id_foreign` FOREIGN KEY (`observation_id`) REFERENCES `nursing_observations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_pain_assessments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_pain_assessments`
--

LOCK TABLES `nursing_pain_assessments` WRITE;
/*!40000 ALTER TABLE `nursing_pain_assessments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_pain_assessments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_risk_assessments`
--

DROP TABLE IF EXISTS `nursing_risk_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_risk_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `risk_type` varchar(255) NOT NULL,
  `tool_name` varchar(255) DEFAULT NULL,
  `score` varchar(255) DEFAULT NULL,
  `risk_level` varchar(255) DEFAULT NULL,
  `contributing_factors` text DEFAULT NULL,
  `interventions` text DEFAULT NULL,
  `assessed_by` bigint(20) unsigned DEFAULT NULL,
  `assessed_at` timestamp NOT NULL,
  `reassessment_due_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_risk_assessments_company_id_foreign` (`company_id`),
  KEY `nursing_risk_assessments_branch_id_foreign` (`branch_id`),
  KEY `nursing_risk_assessments_patient_id_foreign` (`patient_id`),
  KEY `nursing_risk_assessments_assessed_by_foreign` (`assessed_by`),
  KEY `nursing_risk_assessments_episode_id_risk_type_assessed_at_index` (`episode_id`,`risk_type`,`assessed_at`),
  KEY `nursing_risk_assessments_admission_id_risk_type_index` (`admission_id`,`risk_type`),
  CONSTRAINT `nursing_risk_assessments_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_risk_assessments_assessed_by_foreign` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_risk_assessments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_risk_assessments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_risk_assessments_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_risk_assessments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_risk_assessments`
--

LOCK TABLES `nursing_risk_assessments` WRITE;
/*!40000 ALTER TABLE `nursing_risk_assessments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_risk_assessments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_shifts`
--

DROP TABLE IF EXISTS `nursing_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `grace_period_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nursing_shifts_company_id_branch_id_name_unique` (`company_id`,`branch_id`,`name`),
  KEY `nursing_shifts_branch_id_foreign` (`branch_id`),
  CONSTRAINT `nursing_shifts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_shifts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_shifts`
--

LOCK TABLES `nursing_shifts` WRITE;
/*!40000 ALTER TABLE `nursing_shifts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `nursing_shifts` VALUES
(1,1,NULL,'Morning','07:00:00','15:00:00',15,1,'2026-09-23 22:11:25','2026-09-23 22:11:25'),
(2,1,NULL,'Evening','15:00:00','23:00:00',15,1,'2026-09-23 22:11:25','2026-09-23 22:11:25'),
(3,1,NULL,'Night','23:00:00','07:00:00',15,1,'2026-09-23 22:11:25','2026-09-23 22:11:25');
/*!40000 ALTER TABLE `nursing_shifts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `nursing_tasks`
--

DROP TABLE IF EXISTS `nursing_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `episode_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `care_plan_id` bigint(20) unsigned DEFAULT NULL,
  `intervention_id` bigint(20) unsigned DEFAULT NULL,
  `task_type` varchar(255) NOT NULL,
  `due_at` timestamp NOT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'routine',
  `assigned_nurse_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_tasks_company_id_foreign` (`company_id`),
  KEY `nursing_tasks_branch_id_foreign` (`branch_id`),
  KEY `nursing_tasks_patient_id_foreign` (`patient_id`),
  KEY `nursing_tasks_care_plan_id_foreign` (`care_plan_id`),
  KEY `nursing_tasks_intervention_id_foreign` (`intervention_id`),
  KEY `nursing_tasks_completed_by_foreign` (`completed_by`),
  KEY `nursing_tasks_episode_id_status_due_at_index` (`episode_id`,`status`,`due_at`),
  KEY `nursing_tasks_assigned_nurse_id_status_index` (`assigned_nurse_id`,`status`),
  KEY `nursing_tasks_admission_id_status_index` (`admission_id`,`status`),
  CONSTRAINT `nursing_tasks_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `ipd_admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_tasks_assigned_nurse_id_foreign` FOREIGN KEY (`assigned_nurse_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_tasks_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_tasks_care_plan_id_foreign` FOREIGN KEY (`care_plan_id`) REFERENCES `nursing_care_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_tasks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_tasks_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_tasks_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `nursing_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_tasks_intervention_id_foreign` FOREIGN KEY (`intervention_id`) REFERENCES `nursing_care_plan_interventions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nursing_tasks_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_tasks`
--

LOCK TABLES `nursing_tasks` WRITE;
/*!40000 ALTER TABLE `nursing_tasks` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `nursing_tasks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_addresses`
--

DROP TABLE IF EXISTS `patient_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `address_type` enum('permanent','present','work','mailing') NOT NULL,
  `line1` varchar(255) DEFAULT NULL,
  `line2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_addresses_patient_id_foreign` (`patient_id`),
  KEY `patient_addresses_country_id_foreign` (`country_id`),
  KEY `patient_addresses_state_id_foreign` (`state_id`),
  KEY `patient_address_type_idx` (`company_id`,`patient_id`,`address_type`),
  CONSTRAINT `patient_addresses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_addresses_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_addresses_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_addresses_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_addresses`
--

LOCK TABLES `patient_addresses` WRITE;
/*!40000 ALTER TABLE `patient_addresses` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_addresses` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_alerts`
--

DROP TABLE IF EXISTS `patient_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `alert_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'warning',
  `status` enum('active','inactive','resolved','expired') NOT NULL DEFAULT 'active',
  `start_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_alerts_patient_id_foreign` (`patient_id`),
  KEY `patient_alerts_created_by_foreign` (`created_by`),
  KEY `patient_alerts_resolved_by_foreign` (`resolved_by`),
  KEY `patient_alerts_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `patient_alerts_company_id_status_index` (`company_id`,`status`),
  KEY `patient_alerts_expires_at_index` (`expires_at`),
  CONSTRAINT `patient_alerts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_alerts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_alerts_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_alerts_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_alerts`
--

LOCK TABLES `patient_alerts` WRITE;
/*!40000 ALTER TABLE `patient_alerts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_alerts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_allergies`
--

DROP TABLE IF EXISTS `patient_allergies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_allergies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `substance` varchar(255) NOT NULL,
  `severity` enum('mild','moderate','severe') DEFAULT NULL,
  `reaction` enum('rash','hives','itching','swelling','anaphylaxis','other') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_allergies_patient_id_foreign` (`patient_id`),
  KEY `patient_allergies_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `patient_allergies_company_id_substance_index` (`company_id`,`substance`),
  CONSTRAINT `patient_allergies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_allergies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_allergies`
--

LOCK TABLES `patient_allergies` WRITE;
/*!40000 ALTER TABLE `patient_allergies` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_allergies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_amendments`
--

DROP TABLE IF EXISTS `patient_amendments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_amendments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `workflow_instance_id` bigint(20) unsigned DEFAULT NULL,
  `proposed_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`proposed_changes`)),
  `original_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`original_values`)),
  `reason` text NOT NULL,
  `status` enum('draft','submitted','pending_approval','approved','applied','rejected') NOT NULL DEFAULT 'draft',
  `requested_by` bigint(20) unsigned NOT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_amendments_patient_id_foreign` (`patient_id`),
  KEY `patient_amendments_workflow_instance_id_foreign` (`workflow_instance_id`),
  KEY `patient_amendments_requested_by_foreign` (`requested_by`),
  KEY `patient_amendment_status_idx` (`company_id`,`patient_id`,`status`),
  CONSTRAINT `patient_amendments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_amendments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_amendments_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_amendments_workflow_instance_id_foreign` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_amendments`
--

LOCK TABLES `patient_amendments` WRITE;
/*!40000 ALTER TABLE `patient_amendments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_amendments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_branch_registrations`
--

DROP TABLE IF EXISTS `patient_branch_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_branch_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `local_patient_no` varchar(255) DEFAULT NULL,
  `registered_at` timestamp NOT NULL,
  `status` enum('active','inactive','transferred','discharged') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_branch_unique` (`company_id`,`patient_id`,`branch_id`),
  KEY `patient_branch_registrations_patient_id_foreign` (`patient_id`),
  KEY `patient_branch_registrations_branch_id_foreign` (`branch_id`),
  KEY `patient_branch_local_idx` (`company_id`,`branch_id`,`local_patient_no`),
  CONSTRAINT `patient_branch_registrations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_branch_registrations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_branch_registrations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_branch_registrations`
--

LOCK TABLES `patient_branch_registrations` WRITE;
/*!40000 ALTER TABLE `patient_branch_registrations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `patient_branch_registrations` VALUES
(1,1,1,1,'MAI-00000001','2026-09-23 18:49:10','active','2026-09-23 18:49:10','2026-09-23 18:49:10',NULL);
/*!40000 ALTER TABLE `patient_branch_registrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_consents`
--

DROP TABLE IF EXISTS `patient_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `consent_type` varchar(255) NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `status` enum('active','withdrawn') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `document_file_id` bigint(20) unsigned DEFAULT NULL,
  `granted_by` bigint(20) unsigned DEFAULT NULL,
  `granted_at` timestamp NOT NULL,
  `withdrawn_by` bigint(20) unsigned DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_consents_patient_id_foreign` (`patient_id`),
  KEY `patient_consents_document_file_id_foreign` (`document_file_id`),
  KEY `patient_consents_granted_by_foreign` (`granted_by`),
  KEY `patient_consents_withdrawn_by_foreign` (`withdrawn_by`),
  KEY `patient_consent_type_idx` (`company_id`,`patient_id`,`consent_type`),
  CONSTRAINT `patient_consents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_consents_document_file_id_foreign` FOREIGN KEY (`document_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_consents_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_consents_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_consents_withdrawn_by_foreign` FOREIGN KEY (`withdrawn_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_consents`
--

LOCK TABLES `patient_consents` WRITE;
/*!40000 ALTER TABLE `patient_consents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_consents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_contacts`
--

DROP TABLE IF EXISTS `patient_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `relationship` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_emergency` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_contacts_patient_id_foreign` (`patient_id`),
  KEY `patient_contacts_company_id_patient_id_index` (`company_id`,`patient_id`),
  CONSTRAINT `patient_contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_contacts_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_contacts`
--

LOCK TABLES `patient_contacts` WRITE;
/*!40000 ALTER TABLE `patient_contacts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `patient_contacts` VALUES
(1,1,1,'Hasan',NULL,'0198687687976',NULL,NULL,1,'2026-09-23 18:49:10','2026-09-23 18:49:10',NULL),
(2,1,1,'Rahim',NULL,'0187676575',NULL,NULL,0,'2026-09-23 18:49:10','2026-09-23 18:49:10',NULL);
/*!40000 ALTER TABLE `patient_contacts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_documents`
--

DROP TABLE IF EXISTS `patient_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_documents_patient_id_foreign` (`patient_id`),
  KEY `patient_documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `patient_documents_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `patient_documents_company_id_document_type_index` (`company_id`,`document_type`),
  CONSTRAINT `patient_documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_documents_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_documents`
--

LOCK TABLES `patient_documents` WRITE;
/*!40000 ALTER TABLE `patient_documents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_duplicate_candidates`
--

DROP TABLE IF EXISTS `patient_duplicate_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_duplicate_candidates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id_a` bigint(20) unsigned NOT NULL,
  `patient_id_b` bigint(20) unsigned NOT NULL,
  `score` tinyint(3) unsigned NOT NULL,
  `classification` enum('possible_match','strong_match') NOT NULL,
  `status` enum('pending','confirmed_duplicate','not_duplicate','needs_investigation','merged','rejected') NOT NULL DEFAULT 'pending',
  `match_reasons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`match_reasons`)),
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_duplicate_pair_unique` (`patient_id_a`,`patient_id_b`),
  KEY `patient_duplicate_candidates_patient_id_b_foreign` (`patient_id_b`),
  KEY `patient_duplicate_candidates_reviewed_by_foreign` (`reviewed_by`),
  KEY `patient_duplicate_status_idx` (`company_id`,`status`),
  CONSTRAINT `patient_duplicate_candidates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_duplicate_candidates_patient_id_a_foreign` FOREIGN KEY (`patient_id_a`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_duplicate_candidates_patient_id_b_foreign` FOREIGN KEY (`patient_id_b`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_duplicate_candidates_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_duplicate_candidates`
--

LOCK TABLES `patient_duplicate_candidates` WRITE;
/*!40000 ALTER TABLE `patient_duplicate_candidates` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_duplicate_candidates` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_guardians`
--

DROP TABLE IF EXISTS `patient_guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `guardian_patient_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `relationship` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `national_identifier` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_guardians_patient_id_foreign` (`patient_id`),
  KEY `patient_guardians_guardian_patient_id_foreign` (`guardian_patient_id`),
  KEY `patient_guardian_patient_idx` (`company_id`,`patient_id`),
  CONSTRAINT `patient_guardians_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_guardians_guardian_patient_id_foreign` FOREIGN KEY (`guardian_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_guardians_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_guardians`
--

LOCK TABLES `patient_guardians` WRITE;
/*!40000 ALTER TABLE `patient_guardians` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_guardians` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_histories`
--

DROP TABLE IF EXISTS `patient_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `condition` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `diagnosed_at` date DEFAULT NULL,
  `resolved_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_histories_patient_id_foreign` (`patient_id`),
  KEY `patient_histories_recorded_by_foreign` (`recorded_by`),
  KEY `patient_histories_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `patient_histories_company_id_condition_index` (`company_id`,`condition`),
  CONSTRAINT `patient_histories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_histories_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_histories_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_histories`
--

LOCK TABLES `patient_histories` WRITE;
/*!40000 ALTER TABLE `patient_histories` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_histories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_identifiers`
--

DROP TABLE IF EXISTS `patient_identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_identifiers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `identification_type_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `identifier_type` varchar(255) NOT NULL,
  `identifier_value` varchar(255) NOT NULL,
  `issuing_authority` varchar(255) DEFAULT NULL,
  `issued_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_identifier_unique` (`company_id`,`patient_id`,`identifier_type`,`identifier_value`),
  KEY `patient_identifiers_patient_id_foreign` (`patient_id`),
  KEY `patient_identifier_type_idx` (`company_id`,`patient_id`,`identifier_type`),
  KEY `patient_identifiers_identification_type_id_foreign` (`identification_type_id`),
  KEY `patient_identifiers_verified_by_foreign` (`verified_by`),
  CONSTRAINT `patient_identifiers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_identifiers_identification_type_id_foreign` FOREIGN KEY (`identification_type_id`) REFERENCES `identification_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_identifiers_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_identifiers_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_identifiers`
--

LOCK TABLES `patient_identifiers` WRITE;
/*!40000 ALTER TABLE `patient_identifiers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_identifiers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_number_counters`
--

DROP TABLE IF EXISTS `patient_number_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_number_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `counter_type` enum('enterprise','local') NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_number_counter_unique` (`company_id`,`branch_id`,`counter_type`),
  KEY `patient_number_counters_branch_id_foreign` (`branch_id`),
  CONSTRAINT `patient_number_counters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_number_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_number_counters`
--

LOCK TABLES `patient_number_counters` WRITE;
/*!40000 ALTER TABLE `patient_number_counters` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `patient_number_counters` VALUES
(1,1,NULL,'enterprise',1,'2026-09-23 18:49:10','2026-09-23 18:49:10'),
(2,1,1,'local',1,'2026-09-23 18:49:10','2026-09-23 18:49:10');
/*!40000 ALTER TABLE `patient_number_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_portal_accounts`
--

DROP TABLE IF EXISTS `patient_portal_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_portal_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `invited_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_portal_accounts_patient_id_unique` (`patient_id`),
  KEY `patient_portal_accounts_company_id_foreign` (`company_id`),
  KEY `patient_portal_accounts_user_id_foreign` (`user_id`),
  CONSTRAINT `patient_portal_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_portal_accounts_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_portal_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_portal_accounts`
--

LOCK TABLES `patient_portal_accounts` WRITE;
/*!40000 ALTER TABLE `patient_portal_accounts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_portal_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_preferences`
--

DROP TABLE IF EXISTS `patient_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `preferred_language` varchar(255) DEFAULT NULL,
  `preferred_contact_method` enum('phone','email','sms') DEFAULT NULL,
  `preferred_notification_channel` enum('sms','email','push','none') NOT NULL DEFAULT 'none',
  `accessibility_requirements` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_preferences_patient_id_unique` (`patient_id`),
  KEY `patient_preferences_company_id_foreign` (`company_id`),
  CONSTRAINT `patient_preferences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_preferences_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_preferences`
--

LOCK TABLES `patient_preferences` WRITE;
/*!40000 ALTER TABLE `patient_preferences` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_preferences` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_problems`
--

DROP TABLE IF EXISTS `patient_problems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_problems` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `problem_code` varchar(255) DEFAULT NULL,
  `problem_name` varchar(255) NOT NULL,
  `coding_system` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `onset_date` date DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `source_encounter_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_problems_company_id_foreign` (`company_id`),
  KEY `patient_problems_source_encounter_id_foreign` (`source_encounter_id`),
  KEY `patient_problems_created_by_foreign` (`created_by`),
  KEY `patient_problems_updated_by_foreign` (`updated_by`),
  KEY `patient_problems_patient_id_status_index` (`patient_id`,`status`),
  KEY `patient_problems_encounter_id_index` (`encounter_id`),
  CONSTRAINT `patient_problems_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_problems_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_problems_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_problems_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_problems_source_encounter_id_foreign` FOREIGN KEY (`source_encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_problems_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_problems`
--

LOCK TABLES `patient_problems` WRITE;
/*!40000 ALTER TABLE `patient_problems` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_problems` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_timeline_events`
--

DROP TABLE IF EXISTS `patient_timeline_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_timeline_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `event_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_timeline_events_patient_id_foreign` (`patient_id`),
  KEY `patient_timeline_events_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `patient_timeline_events_actor_id_foreign` (`actor_id`),
  KEY `patient_timeline_patient_idx` (`company_id`,`patient_id`,`event_at`),
  CONSTRAINT `patient_timeline_events_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_timeline_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_timeline_events_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_timeline_events`
--

LOCK TABLES `patient_timeline_events` WRITE;
/*!40000 ALTER TABLE `patient_timeline_events` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `patient_timeline_events` VALUES
(1,1,1,'PATIENT_REGISTERED','Patient registered','App\\Models\\Patient',1,1,'[]','2026-09-23 18:49:10',NULL,NULL);
/*!40000 ALTER TABLE `patient_timeline_events` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_types`
--

DROP TABLE IF EXISTS `patient_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_types_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_types`
--

LOCK TABLES `patient_types` WRITE;
/*!40000 ALTER TABLE `patient_types` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `patient_types` VALUES
(1,'general','General',1,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,'vip','VIP',1,2,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,'corporate','Corporate',1,3,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,'staff','Staff',1,4,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,'insurance','Insurance',1,5,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `patient_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `patient_type_id` bigint(20) unsigned DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `enterprise_patient_no` varchar(255) DEFAULT NULL,
  `national_identifier` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `preferred_name` varchar(255) DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `dob_unknown` tinyint(1) NOT NULL DEFAULT 0,
  `estimated_age` smallint(5) unsigned DEFAULT NULL,
  `estimated_age_unit` enum('years','months','days') DEFAULT NULL,
  `sex` enum('M','F','O') DEFAULT NULL,
  `gender_id` bigint(20) unsigned DEFAULT NULL,
  `marital_status_id` bigint(20) unsigned DEFAULT NULL,
  `nationality_id` bigint(20) unsigned DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `rh_factor` enum('positive','negative','unknown') DEFAULT NULL,
  `deceased_at` timestamp NULL DEFAULT NULL,
  `is_temporary` tinyint(1) NOT NULL DEFAULT 0,
  `is_unknown` tinyint(1) NOT NULL DEFAULT 0,
  `registered_at` timestamp NULL DEFAULT NULL,
  `registered_by` bigint(20) unsigned DEFAULT NULL,
  `photo_file_id` bigint(20) unsigned DEFAULT NULL,
  `portal_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `portal_user_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `emergency_contact` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emergency_contact`)),
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','deceased','merged') NOT NULL DEFAULT 'active',
  `merged_into_patient_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patients_company_id_enterprise_patient_no_unique` (`company_id`,`enterprise_patient_no`),
  KEY `patients_company_id_last_name_first_name_index` (`company_id`,`last_name`,`first_name`),
  KEY `patients_company_id_phone_index` (`company_id`,`phone`),
  KEY `patients_company_id_national_identifier_index` (`company_id`,`national_identifier`),
  KEY `patients_country_id_foreign` (`country_id`),
  KEY `patients_state_id_foreign` (`state_id`),
  KEY `patients_merged_into_patient_id_foreign` (`merged_into_patient_id`),
  KEY `patients_patient_type_id_foreign` (`patient_type_id`),
  KEY `patients_gender_id_foreign` (`gender_id`),
  KEY `patients_marital_status_id_foreign` (`marital_status_id`),
  KEY `patients_nationality_id_foreign` (`nationality_id`),
  KEY `patients_registered_by_foreign` (`registered_by`),
  KEY `patients_photo_file_id_foreign` (`photo_file_id`),
  KEY `patients_portal_user_id_foreign` (`portal_user_id`),
  KEY `patients_company_type_idx` (`company_id`,`patient_type_id`),
  CONSTRAINT `patients_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patients_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_gender_id_foreign` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_marital_status_id_foreign` FOREIGN KEY (`marital_status_id`) REFERENCES `marital_statuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_merged_into_patient_id_foreign` FOREIGN KEY (`merged_into_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_nationality_id_foreign` FOREIGN KEY (`nationality_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_patient_type_id_foreign` FOREIGN KEY (`patient_type_id`) REFERENCES `patient_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_photo_file_id_foreign` FOREIGN KEY (`photo_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_portal_user_id_foreign` FOREIGN KEY (`portal_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patients_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `patients` VALUES
(1,1,1,NULL,NULL,'HN--00000001','98789789896','Saidur',NULL,'Rahman',NULL,NULL,'1982-01-01',0,NULL,NULL,NULL,1,1,NULL,'B+',NULL,NULL,0,0,'2026-09-23 18:49:10',1,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-09-23 18:49:10','2026-09-23 18:49:10',NULL);
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=421 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `permissions` VALUES
(1,'manage companies','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(2,'manage branches','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(3,'manage departments','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(4,'manage users','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(5,'manage roles','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(6,'manage permissions','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(7,'manage patients','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(8,'patients.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(9,'patients.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(10,'patients.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(11,'patients.delete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(12,'patients.merge','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(13,'patients.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(14,'patients.print','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(15,'patients.alert.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(16,'patients.alert.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(17,'patients.documents.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(18,'patients.documents.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(19,'patients.consents.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(20,'patients.consents.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(21,'patients.amend.request','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(22,'patients.amend.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(23,'patients.portal.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(24,'appointments.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(25,'appointments.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(26,'appointments.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(27,'appointments.delete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(28,'appointments.confirm','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(29,'appointments.checkin','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(30,'appointments.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(31,'appointments.reschedule','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(32,'appointments.no_show','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(33,'appointments.queue','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(34,'appointments.token','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(35,'appointments.override','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(36,'appointments.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(37,'appointments.print','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(38,'appointments.manage_schedule','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(39,'appointments.manage_provider','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(40,'appointments.manage_holiday','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(41,'appointments.manage_block','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(42,'appointments.manage_overbooking','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(43,'schedules.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(44,'schedules.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(45,'schedules.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(46,'schedules.delete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(47,'queue.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(48,'queue.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(49,'opd.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(50,'opd.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(51,'opd.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(52,'opd.consult','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(53,'emergency.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(54,'emergency.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(55,'emergency.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(56,'emergency.triage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(57,'ipd.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(58,'ipd.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(59,'ipd.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(60,'ipd.admit','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(61,'ipd.discharge','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(62,'bed.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(63,'bed.allocate','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(64,'bed.transfer','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(65,'bed.block','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(66,'nursing.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(67,'nursing.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(68,'nursing.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(69,'nursing.administer','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(70,'doctor.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(71,'doctor.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(72,'doctor.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(73,'doctor.consult','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(74,'emr.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(75,'emr.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(76,'emr.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(77,'emr.amend','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(78,'emr.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(79,'encounters.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(80,'encounters.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(81,'encounters.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(82,'encounters.delete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(83,'encounter.start','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(84,'encounter.complete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(85,'encounter.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(86,'encounter.amend','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(87,'encounter.lock','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(88,'encounter.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(89,'encounter.print','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(90,'clinical.note.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(91,'clinical.note.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(92,'clinical.note.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(93,'clinical.vitals.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(94,'clinical.vitals.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(95,'clinical.diagnosis.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(96,'clinical.diagnosis.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(97,'clinical.diagnosis.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(98,'clinical.order.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(99,'clinical.order.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(100,'clinical.order.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(101,'clinical.referral.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(102,'clinical.referral.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(103,'clinical.break_glass','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(104,'prescription.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(105,'prescription.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(106,'prescription.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(107,'prescription.issue','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(108,'prescription.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(109,'prescription.amend','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(110,'pharmacy.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(111,'pharmacy.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(112,'pharmacy.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(113,'pharmacy.dispense','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(114,'pharmacy.adjust','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(115,'laboratory.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(116,'laboratory.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(117,'laboratory.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(118,'laboratory.verify','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(119,'laboratory.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(120,'laboratory.release','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(121,'radiology.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(122,'radiology.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(123,'radiology.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(124,'radiology.report','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(125,'ot.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(126,'ot.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(127,'ot.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(128,'ot.schedule','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(129,'icu.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(130,'icu.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(131,'icu.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(132,'billing.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(133,'billing.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(134,'billing.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(135,'billing.discount','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(136,'billing.refund','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(137,'billing.payment','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(138,'billing.dashboard.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(139,'billing.charge.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(140,'billing.charge.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(141,'billing.charge.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(142,'billing.invoice.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(143,'billing.invoice.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(144,'billing.invoice.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(145,'billing.invoice.finalize','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(146,'billing.invoice.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(147,'billing.invoice.writeoff','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(148,'billing.payment.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(149,'billing.payment.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(150,'billing.payment.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(151,'billing.receipt.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(152,'billing.receipt.void','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(153,'billing.refund.request','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(154,'billing.refund.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(155,'billing.refund.process','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(156,'billing.adjustment.request','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(157,'billing.adjustment.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(158,'billing.cashier.open','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(159,'billing.cashier.close','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(160,'billing.cashier.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(161,'billing.cashier.reconcile','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(162,'billing.pricing.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(163,'billing.pricing.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(164,'billing.category.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(165,'billing.item.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(166,'billing.corporate.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(167,'billing.corporate.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(168,'billing.insurance.policy.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(169,'billing.insurance.policy.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(170,'billing.report.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(171,'billing.settings.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(172,'lab.dashboard.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(173,'lab.test.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(174,'lab.test.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(175,'lab.test.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(176,'lab.panel.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(177,'lab.panel.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(178,'lab.panel.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(179,'lab.specimen.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(180,'lab.specimen.collect','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(181,'lab.specimen.receive','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(182,'lab.specimen.reject','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(183,'lab.specimen.process','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(184,'lab.order.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(185,'lab.order.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(186,'lab.order.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(187,'lab.result.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(188,'lab.result.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(189,'lab.result.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(190,'lab.result.validate','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(191,'lab.result.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(192,'lab.result.amend','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(193,'lab.report.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(194,'lab.report.generate','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(195,'lab.report.print','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(196,'lab.report.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(197,'lab.critical_result.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(198,'lab.critical_result.notify','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(199,'lab.critical_result.acknowledge','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(200,'lab.qc.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(201,'lab.qc.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(202,'lab.analyzer.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(203,'lab.analyzer.configure','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(204,'lab.settings.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(205,'radiology.dashboard.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(206,'radiology.procedure.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(207,'radiology.procedure.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(208,'radiology.procedure.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(209,'radiology.modality.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(210,'radiology.modality.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(211,'radiology.modality.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(212,'radiology.order.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(213,'radiology.order.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(214,'radiology.order.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(215,'radiology.schedule.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(216,'radiology.schedule.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(217,'radiology.schedule.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(218,'radiology.schedule.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(219,'radiology.examination.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(220,'radiology.examination.start','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(221,'radiology.examination.complete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(222,'radiology.study.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(223,'radiology.study.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(224,'radiology.worklist.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(225,'radiology.worklist.assign','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(226,'radiology.report.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(227,'radiology.report.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(228,'radiology.report.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(229,'radiology.report.submit','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(230,'radiology.report.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(231,'radiology.report.amend','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(232,'radiology.critical_finding.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(233,'radiology.critical_finding.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(234,'radiology.critical_finding.notify','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(235,'radiology.critical_finding.acknowledge','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(236,'radiology.pacs.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(237,'radiology.pacs.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(238,'radiology.dicom.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(239,'radiology.dicom.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(240,'radiology.settings.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(241,'pharmacy.dashboard.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(242,'pharmacy.medication.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(243,'pharmacy.medication.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(244,'pharmacy.medication.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(245,'pharmacy.generic.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(246,'pharmacy.generic.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(247,'pharmacy.generic.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(248,'pharmacy.brand.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(249,'pharmacy.brand.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(250,'pharmacy.brand.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(251,'pharmacy.prescription.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(252,'pharmacy.prescription.review','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(253,'pharmacy.dispensing.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(254,'pharmacy.dispensing.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(255,'pharmacy.dispensing.verify','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(256,'pharmacy.dispensing.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(257,'pharmacy.dispensing.return','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(258,'pharmacy.stock.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(259,'pharmacy.stock.receive','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(260,'pharmacy.stock.transfer','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(261,'pharmacy.stock.adjust','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(262,'pharmacy.stock.count','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(263,'pharmacy.batch.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(264,'pharmacy.batch.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(265,'pharmacy.batch.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(266,'pharmacy.expiry.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(267,'pharmacy.quarantine.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(268,'pharmacy.substitution.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(269,'pharmacy.substitution.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(270,'pharmacy.safety_alert.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(271,'pharmacy.safety_alert.override','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(272,'pharmacy.controlled_drug.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(273,'pharmacy.controlled_drug.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(274,'pharmacy.recall.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(275,'pharmacy.recall.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(276,'pharmacy.reports.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(277,'pharmacy.reports.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(278,'pharmacy.settings.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(279,'ipd.dashboard.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(280,'ipd.admission.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(281,'ipd.admission.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(282,'ipd.admission.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(283,'ipd.admission.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(284,'ipd.admission.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(285,'ipd.ward.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(286,'ipd.ward.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(287,'ipd.ward.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(288,'ipd.room.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(289,'ipd.room.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(290,'ipd.room.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(291,'ipd.bed.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(292,'ipd.bed.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(293,'ipd.bed.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(294,'ipd.bed.block','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(295,'ipd.bed.unblock','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(296,'ipd.bed.reserve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(297,'ipd.bed.allocate','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(298,'ipd.bed.release','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(299,'ipd.transfer.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(300,'ipd.transfer.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(301,'ipd.transfer.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(302,'ipd.transfer.complete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(303,'ipd.transfer.cancel','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(304,'ipd.discharge.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(305,'ipd.discharge.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(306,'ipd.discharge.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(307,'ipd.discharge.complete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(308,'ipd.leave.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(309,'ipd.leave.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(310,'ipd.leave.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(311,'ipd.leave.complete','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(312,'ipd.reports.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(313,'ipd.reports.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(314,'ipd.settings.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(315,'ipd.audit.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(316,'insurance.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(317,'insurance.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(318,'insurance.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(319,'insurance.submit','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(320,'insurance.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(321,'finance.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(322,'finance.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(323,'finance.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(324,'finance.post','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(325,'finance.close','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(326,'inventory.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(327,'inventory.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(328,'inventory.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(329,'inventory.adjust','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(330,'inventory.transfer','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(331,'procurement.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(332,'procurement.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(333,'procurement.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(334,'procurement.approve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(335,'hr.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(336,'hr.create','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(337,'hr.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(338,'hr.attendance','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(339,'hr.payroll','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(340,'reporting.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(341,'reporting.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(342,'reporting.print','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(343,'audit.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(344,'activity.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(345,'activity.export','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(346,'security.event.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(347,'security.event.resolve','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(348,'login.history.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(349,'system.health.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(350,'system.queue.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(351,'system.scheduler.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(352,'workflow.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(353,'workflow.act','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(354,'workflow.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(355,'settings.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(356,'settings.update','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(357,'notification.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(358,'notification.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(359,'file.view','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(360,'file.manage','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(361,'nursing.dashboard.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(362,'nursing.assignment.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(363,'nursing.assignment.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(364,'nursing.assignment.update','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(365,'nursing.shift.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(366,'nursing.shift.manage','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(367,'nursing.assessment.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(368,'nursing.assessment.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(369,'nursing.assessment.update','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(370,'nursing.assessment.finalize','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(371,'nursing.observation.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(372,'nursing.observation.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(373,'nursing.vitals.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(374,'nursing.vitals.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(375,'nursing.vitals.update','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(376,'nursing.care_plan.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(377,'nursing.care_plan.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(378,'nursing.care_plan.update','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(379,'nursing.care_plan.complete','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(380,'nursing.diagnosis.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(381,'nursing.diagnosis.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(382,'nursing.diagnosis.update','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(383,'nursing.task.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(384,'nursing.task.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(385,'nursing.task.complete','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(386,'nursing.mar.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(387,'nursing.mar.administer','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(388,'nursing.mar.administer_without_dispensing','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(389,'nursing.mar.hold','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(390,'nursing.mar.refuse','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(391,'nursing.mar.omit','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(392,'nursing.mar.correct','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(393,'nursing.iv.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(394,'nursing.iv.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(395,'nursing.iv.update','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(396,'nursing.device.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(397,'nursing.device.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(398,'nursing.device.update','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(399,'nursing.wound.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(400,'nursing.wound.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(401,'nursing.wound.update','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(402,'nursing.education.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(403,'nursing.education.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(404,'nursing.notes.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(405,'nursing.notes.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(406,'nursing.notes.finalize','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(407,'nursing.notes.amend','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(408,'nursing.handover.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(409,'nursing.handover.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(410,'nursing.handover.acknowledge','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(411,'nursing.escalation.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(412,'nursing.escalation.create','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(413,'nursing.escalation.acknowledge','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(414,'nursing.escalation.resolve','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(415,'nursing.discharge.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(416,'nursing.discharge.complete','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(417,'nursing.reports.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(418,'nursing.reports.export','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(419,'nursing.audit.view','web','2026-09-23 22:11:19','2026-09-23 22:11:19'),
(420,'nursing.settings.manage','web','2026-09-23 22:11:19','2026-09-23 22:11:19');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_batches`
--

DROP TABLE IF EXISTS `pharmacy_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `supplier_reference` varchar(255) DEFAULT NULL,
  `is_quarantined` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_batches_company_id_medication_id_batch_number_unique` (`company_id`,`medication_id`,`batch_number`),
  KEY `pharmacy_batches_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_batches_medication_id_foreign` (`medication_id`),
  KEY `pharmacy_batches_created_by_foreign` (`created_by`),
  KEY `pharmacy_batches_company_id_branch_id_medication_id_index` (`company_id`,`branch_id`,`medication_id`),
  KEY `pharmacy_batches_expiry_date_index` (`expiry_date`),
  KEY `pharmacy_batches_is_quarantined_index` (`is_quarantined`),
  CONSTRAINT `pharmacy_batches_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_batches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_batches_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_batches`
--

LOCK TABLES `pharmacy_batches` WRITE;
/*!40000 ALTER TABLE `pharmacy_batches` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_batches` VALUES
(1,1,NULL,1,'SEED-NAPA-500-TAB',NULL,'2026-06-23','2027-09-23',2.00,5.00,NULL,0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,1,NULL,2,'SEED-AMOX-500-CAP',NULL,'2026-06-23','2027-09-23',2.00,5.00,NULL,0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `pharmacy_batches` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_brands`
--

DROP TABLE IF EXISTS `pharmacy_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `generic_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_brands_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `pharmacy_brands_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_brands_generic_id_foreign` (`generic_id`),
  KEY `pharmacy_brands_created_by_foreign` (`created_by`),
  KEY `pharmacy_brands_updated_by_foreign` (`updated_by`),
  KEY `pharmacy_brands_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  KEY `pharmacy_brands_name_index` (`name`),
  CONSTRAINT `pharmacy_brands_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_brands_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_brands_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_brands_generic_id_foreign` FOREIGN KEY (`generic_id`) REFERENCES `pharmacy_generics` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_brands_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_brands`
--

LOCK TABLES `pharmacy_brands` WRITE;
/*!40000 ALTER TABLE `pharmacy_brands` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_brands` VALUES
(1,1,NULL,1,'NAPA','Napa','Beximco Pharmaceuticals',1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `pharmacy_brands` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_controlled_drug_transactions`
--

DROP TABLE IF EXISTS `pharmacy_controlled_drug_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_controlled_drug_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `direction` enum('in','out') NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `balance_after` int(10) unsigned NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `performed_by` bigint(20) unsigned NOT NULL,
  `witnessed_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_controlled_drug_transactions_company_id_foreign` (`company_id`),
  KEY `pharmacy_controlled_drug_transactions_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_controlled_drug_transactions_medication_id_foreign` (`medication_id`),
  KEY `pharmacy_controlled_drug_transactions_batch_id_foreign` (`batch_id`),
  KEY `pharmacy_controlled_drug_transactions_performed_by_foreign` (`performed_by`),
  KEY `pharmacy_controlled_drug_transactions_witnessed_by_foreign` (`witnessed_by`),
  KEY `pharmacy_cd_txn_scope_time_idx` (`store_id`,`medication_id`,`created_at`),
  KEY `pharmacy_cd_txn_reference_idx` (`reference_type`,`reference_id`),
  CONSTRAINT `pharmacy_controlled_drug_transactions_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_controlled_drug_transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_controlled_drug_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_controlled_drug_transactions_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_controlled_drug_transactions_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_controlled_drug_transactions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_controlled_drug_transactions_witnessed_by_foreign` FOREIGN KEY (`witnessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_controlled_drug_transactions`
--

LOCK TABLES `pharmacy_controlled_drug_transactions` WRITE;
/*!40000 ALTER TABLE `pharmacy_controlled_drug_transactions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_controlled_drug_transactions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_counters`
--

DROP TABLE IF EXISTS `pharmacy_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(3) NOT NULL,
  `prefix` varchar(16) NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_counters_scope_unique` (`company_id`,`branch_id`,`document_type`,`prefix`),
  KEY `pharmacy_counters_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_counters_company_id_branch_id_document_type_index` (`company_id`,`branch_id`,`document_type`),
  CONSTRAINT `pharmacy_counters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_counters`
--

LOCK TABLES `pharmacy_counters` WRITE;
/*!40000 ALTER TABLE `pharmacy_counters` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_dispensing_items`
--

DROP TABLE IF EXISTS `pharmacy_dispensing_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_dispensing_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dispensing_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `quantity_prescribed` int(10) unsigned DEFAULT NULL,
  `quantity_dispensed` int(10) unsigned NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `substitution_flag` tinyint(1) NOT NULL DEFAULT 0,
  `substitution_reason` text DEFAULT NULL,
  `substituted_from_medication_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_dispensing_items_medication_id_foreign` (`medication_id`),
  KEY `pharmacy_dispensing_items_substituted_from_medication_id_foreign` (`substituted_from_medication_id`),
  KEY `pharmacy_dispensing_items_dispensing_id_index` (`dispensing_id`),
  KEY `pharmacy_dispensing_items_order_item_id_index` (`order_item_id`),
  KEY `pharmacy_dispensing_items_batch_id_index` (`batch_id`),
  CONSTRAINT `pharmacy_dispensing_items_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_dispensing_items_dispensing_id_foreign` FOREIGN KEY (`dispensing_id`) REFERENCES `pharmacy_dispensings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispensing_items_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispensing_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `pharmacy_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispensing_items_substituted_from_medication_id_foreign` FOREIGN KEY (`substituted_from_medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_dispensing_items`
--

LOCK TABLES `pharmacy_dispensing_items` WRITE;
/*!40000 ALTER TABLE `pharmacy_dispensing_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_dispensing_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_dispensings`
--

DROP TABLE IF EXISTS `pharmacy_dispensings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_dispensings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `dispensing_number` varchar(255) NOT NULL,
  `dispensed_by` bigint(20) unsigned DEFAULT NULL,
  `dispensed_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_dispensings_company_id_dispensing_number_unique` (`company_id`,`dispensing_number`),
  KEY `pharmacy_dispensings_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_dispensings_prescription_id_foreign` (`prescription_id`),
  KEY `pharmacy_dispensings_store_id_foreign` (`store_id`),
  KEY `pharmacy_dispensings_dispensed_by_foreign` (`dispensed_by`),
  KEY `pharmacy_dispensings_verified_by_foreign` (`verified_by`),
  KEY `pharmacy_dispensings_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `pharmacy_dispensings_order_id_index` (`order_id`),
  KEY `pharmacy_dispensings_patient_id_index` (`patient_id`),
  CONSTRAINT `pharmacy_dispensings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_dispensings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispensings_dispensed_by_foreign` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_dispensings_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `pharmacy_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispensings_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispensings_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispensings_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispensings_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_dispensings`
--

LOCK TABLES `pharmacy_dispensings` WRITE;
/*!40000 ALTER TABLE `pharmacy_dispensings` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_dispensings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_dosage_forms`
--

DROP TABLE IF EXISTS `pharmacy_dosage_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_dosage_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_dosage_forms_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `pharmacy_dosage_forms_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_dosage_forms_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `pharmacy_dosage_forms_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_dosage_forms_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_dosage_forms`
--

LOCK TABLES `pharmacy_dosage_forms` WRITE;
/*!40000 ALTER TABLE `pharmacy_dosage_forms` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_dosage_forms` VALUES
(1,1,NULL,'TAB','Tablet',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'CAP','Capsule',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'SYR','Syrup',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,1,NULL,'INJ','Injection',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `pharmacy_dosage_forms` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_generics`
--

DROP TABLE IF EXISTS `pharmacy_generics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_generics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `generic_name` varchar(255) NOT NULL,
  `chemical_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `therapeutic_class` varchar(255) DEFAULT NULL,
  `pharmacological_class` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_generics_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `pharmacy_generics_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_generics_created_by_foreign` (`created_by`),
  KEY `pharmacy_generics_updated_by_foreign` (`updated_by`),
  KEY `pharmacy_generics_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  KEY `pharmacy_generics_generic_name_index` (`generic_name`),
  CONSTRAINT `pharmacy_generics_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_generics_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_generics_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_generics_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_generics`
--

LOCK TABLES `pharmacy_generics` WRITE;
/*!40000 ALTER TABLE `pharmacy_generics` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_generics` VALUES
(1,1,NULL,'PARA','Paracetamol',NULL,NULL,'Analgesic/Antipyretic',NULL,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'AMOX','Amoxicillin',NULL,NULL,'Penicillin antibiotic',NULL,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `pharmacy_generics` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_medication_ingredients`
--

DROP TABLE IF EXISTS `pharmacy_medication_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_medication_ingredients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medication_id` bigint(20) unsigned NOT NULL,
  `generic_id` bigint(20) unsigned NOT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_medication_ingredients_medication_id_generic_id_unique` (`medication_id`,`generic_id`),
  KEY `pharmacy_medication_ingredients_generic_id_foreign` (`generic_id`),
  CONSTRAINT `pharmacy_medication_ingredients_generic_id_foreign` FOREIGN KEY (`generic_id`) REFERENCES `pharmacy_generics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_medication_ingredients_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_medication_ingredients`
--

LOCK TABLES `pharmacy_medication_ingredients` WRITE;
/*!40000 ALTER TABLE `pharmacy_medication_ingredients` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_medication_ingredients` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_medication_store_levels`
--

DROP TABLE IF EXISTS `pharmacy_medication_store_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_medication_store_levels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medication_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `minimum_stock` int(10) unsigned NOT NULL DEFAULT 0,
  `reorder_level` int(10) unsigned NOT NULL DEFAULT 0,
  `maximum_stock` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_medication_store_levels_medication_id_store_id_unique` (`medication_id`,`store_id`),
  KEY `pharmacy_medication_store_levels_store_id_foreign` (`store_id`),
  CONSTRAINT `pharmacy_medication_store_levels_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_medication_store_levels_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_medication_store_levels`
--

LOCK TABLES `pharmacy_medication_store_levels` WRITE;
/*!40000 ALTER TABLE `pharmacy_medication_store_levels` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_medication_store_levels` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_medications`
--

DROP TABLE IF EXISTS `pharmacy_medications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_medications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `generic_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `dosage_form_id` bigint(20) unsigned DEFAULT NULL,
  `route_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `strength_unit` varchar(255) DEFAULT NULL,
  `pack_size` int(10) unsigned DEFAULT NULL,
  `dispensing_unit` varchar(255) DEFAULT NULL,
  `prescription_unit` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `is_prescription_required` tinyint(1) NOT NULL DEFAULT 1,
  `is_controlled` tinyint(1) NOT NULL DEFAULT 0,
  `is_high_alert` tinyint(1) NOT NULL DEFAULT 0,
  `storage_temperature_min` decimal(5,2) DEFAULT NULL,
  `storage_temperature_max` decimal(5,2) DEFAULT NULL,
  `temperature_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_medications_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `pharmacy_medications_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_medications_generic_id_foreign` (`generic_id`),
  KEY `pharmacy_medications_brand_id_foreign` (`brand_id`),
  KEY `pharmacy_medications_dosage_form_id_foreign` (`dosage_form_id`),
  KEY `pharmacy_medications_route_id_foreign` (`route_id`),
  KEY `pharmacy_medications_created_by_foreign` (`created_by`),
  KEY `pharmacy_medications_updated_by_foreign` (`updated_by`),
  KEY `pharmacy_medications_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  KEY `pharmacy_medications_name_index` (`name`),
  KEY `pharmacy_medications_is_controlled_index` (`is_controlled`),
  CONSTRAINT `pharmacy_medications_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_medications_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `pharmacy_brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_medications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_medications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_medications_dosage_form_id_foreign` FOREIGN KEY (`dosage_form_id`) REFERENCES `pharmacy_dosage_forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_medications_generic_id_foreign` FOREIGN KEY (`generic_id`) REFERENCES `pharmacy_generics` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_medications_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `pharmacy_routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_medications_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_medications`
--

LOCK TABLES `pharmacy_medications` WRITE;
/*!40000 ALTER TABLE `pharmacy_medications` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_medications` VALUES
(1,1,NULL,1,1,1,1,'NAPA-500-TAB','Napa 500mg Tablet','500','mg',NULL,'tablet','tablet',NULL,1,0,0,NULL,NULL,0,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,2,NULL,2,1,'AMOX-500-CAP','Amoxicillin 500mg Capsule','500','mg',NULL,'tablet','tablet',NULL,1,0,0,NULL,NULL,0,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `pharmacy_medications` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_order_items`
--

DROP TABLE IF EXISTS `pharmacy_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `prescription_item_id` bigint(20) unsigned NOT NULL,
  `medication_id` bigint(20) unsigned DEFAULT NULL,
  `requested_medicine_name` varchar(255) NOT NULL,
  `quantity_prescribed` int(10) unsigned DEFAULT NULL,
  `quantity_dispensed` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_order_items_prescription_item_id_foreign` (`prescription_item_id`),
  KEY `pharmacy_order_items_order_id_status_index` (`order_id`,`status`),
  KEY `pharmacy_order_items_medication_id_index` (`medication_id`),
  CONSTRAINT `pharmacy_order_items_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `pharmacy_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_order_items_prescription_item_id_foreign` FOREIGN KEY (`prescription_item_id`) REFERENCES `prescription_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_order_items`
--

LOCK TABLES `pharmacy_order_items` WRITE;
/*!40000 ALTER TABLE `pharmacy_order_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_order_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_orders`
--

DROP TABLE IF EXISTS `pharmacy_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `ordered_by` bigint(20) unsigned DEFAULT NULL,
  `ordered_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_orders_company_id_order_number_unique` (`company_id`,`order_number`),
  KEY `pharmacy_orders_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_orders_department_id_foreign` (`department_id`),
  KEY `pharmacy_orders_encounter_id_foreign` (`encounter_id`),
  KEY `pharmacy_orders_ordered_by_foreign` (`ordered_by`),
  KEY `pharmacy_orders_cancelled_by_foreign` (`cancelled_by`),
  KEY `pharmacy_orders_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `pharmacy_orders_patient_id_index` (`patient_id`),
  KEY `pharmacy_orders_prescription_id_index` (`prescription_id`),
  CONSTRAINT `pharmacy_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_orders_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_orders_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_orders_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_orders_ordered_by_foreign` FOREIGN KEY (`ordered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_orders_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_orders_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_orders`
--

LOCK TABLES `pharmacy_orders` WRITE;
/*!40000 ALTER TABLE `pharmacy_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_quarantine`
--

DROP TABLE IF EXISTS `pharmacy_quarantine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_quarantine` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `reason_type` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'quarantined',
  `quarantined_by` bigint(20) unsigned DEFAULT NULL,
  `quarantined_at` timestamp NULL DEFAULT NULL,
  `released_by` bigint(20) unsigned DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `disposed_by` bigint(20) unsigned DEFAULT NULL,
  `disposed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_quarantine_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_quarantine_medication_id_foreign` (`medication_id`),
  KEY `pharmacy_quarantine_batch_id_foreign` (`batch_id`),
  KEY `pharmacy_quarantine_quarantined_by_foreign` (`quarantined_by`),
  KEY `pharmacy_quarantine_released_by_foreign` (`released_by`),
  KEY `pharmacy_quarantine_disposed_by_foreign` (`disposed_by`),
  KEY `pharmacy_quarantine_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `pharmacy_quarantine_store_id_medication_id_batch_id_index` (`store_id`,`medication_id`,`batch_id`),
  CONSTRAINT `pharmacy_quarantine_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_quarantine_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_quarantine_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_quarantine_disposed_by_foreign` FOREIGN KEY (`disposed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_quarantine_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_quarantine_quarantined_by_foreign` FOREIGN KEY (`quarantined_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_quarantine_released_by_foreign` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_quarantine_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_quarantine`
--

LOCK TABLES `pharmacy_quarantine` WRITE;
/*!40000 ALTER TABLE `pharmacy_quarantine` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_quarantine` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_recalls`
--

DROP TABLE IF EXISTS `pharmacy_recalls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_recalls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `recall_number` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'initiated',
  `initiated_by` bigint(20) unsigned DEFAULT NULL,
  `initiated_at` timestamp NULL DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_recalls_company_id_recall_number_unique` (`company_id`,`recall_number`),
  KEY `pharmacy_recalls_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_recalls_batch_id_foreign` (`batch_id`),
  KEY `pharmacy_recalls_initiated_by_foreign` (`initiated_by`),
  KEY `pharmacy_recalls_closed_by_foreign` (`closed_by`),
  KEY `pharmacy_recalls_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `pharmacy_recalls_medication_id_batch_id_index` (`medication_id`,`batch_id`),
  CONSTRAINT `pharmacy_recalls_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_recalls_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_recalls_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_recalls_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_recalls_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_recalls_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_recalls`
--

LOCK TABLES `pharmacy_recalls` WRITE;
/*!40000 ALTER TABLE `pharmacy_recalls` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_recalls` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_return_items`
--

DROP TABLE IF EXISTS `pharmacy_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint(20) unsigned NOT NULL,
  `dispensing_item_id` bigint(20) unsigned NOT NULL,
  `quantity_returned` int(10) unsigned NOT NULL,
  `returnable_to_stock` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_return_items_return_id_index` (`return_id`),
  KEY `pharmacy_return_items_dispensing_item_id_index` (`dispensing_item_id`),
  CONSTRAINT `pharmacy_return_items_dispensing_item_id_foreign` FOREIGN KEY (`dispensing_item_id`) REFERENCES `pharmacy_dispensing_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_return_items_return_id_foreign` FOREIGN KEY (`return_id`) REFERENCES `pharmacy_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_return_items`
--

LOCK TABLES `pharmacy_return_items` WRITE;
/*!40000 ALTER TABLE `pharmacy_return_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_return_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_returns`
--

DROP TABLE IF EXISTS `pharmacy_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `dispensing_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `return_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `reason` text NOT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_returns_company_id_return_number_unique` (`company_id`,`return_number`),
  KEY `pharmacy_returns_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_returns_store_id_foreign` (`store_id`),
  KEY `pharmacy_returns_requested_by_foreign` (`requested_by`),
  KEY `pharmacy_returns_verified_by_foreign` (`verified_by`),
  KEY `pharmacy_returns_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `pharmacy_returns_dispensing_id_index` (`dispensing_id`),
  CONSTRAINT `pharmacy_returns_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_returns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_returns_dispensing_id_foreign` FOREIGN KEY (`dispensing_id`) REFERENCES `pharmacy_dispensings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_returns_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_returns_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_returns_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_returns`
--

LOCK TABLES `pharmacy_returns` WRITE;
/*!40000 ALTER TABLE `pharmacy_returns` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_returns` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_routes`
--

DROP TABLE IF EXISTS `pharmacy_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_routes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_routes_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `pharmacy_routes_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_routes_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `pharmacy_routes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_routes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_routes`
--

LOCK TABLES `pharmacy_routes` WRITE;
/*!40000 ALTER TABLE `pharmacy_routes` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_routes` VALUES
(1,1,NULL,'ORAL','Oral',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'IV','Intravenous',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'IM','Intramuscular',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,1,NULL,'TOP','Topical',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `pharmacy_routes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_safety_alerts`
--

DROP TABLE IF EXISTS `pharmacy_safety_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_safety_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `dispensing_item_id` bigint(20) unsigned DEFAULT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `alert_type` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL DEFAULT 'moderate',
  `interacting_reference` varchar(255) DEFAULT NULL,
  `explanation` text NOT NULL,
  `recommended_action` text DEFAULT NULL,
  `is_overridden` tinyint(1) NOT NULL DEFAULT 0,
  `override_reason` text DEFAULT NULL,
  `overridden_by` bigint(20) unsigned DEFAULT NULL,
  `overridden_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_safety_alerts_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_safety_alerts_overridden_by_foreign` (`overridden_by`),
  KEY `pharmacy_safety_alerts_company_id_branch_id_is_overridden_index` (`company_id`,`branch_id`,`is_overridden`),
  KEY `pharmacy_safety_alerts_order_item_id_index` (`order_item_id`),
  KEY `pharmacy_safety_alerts_dispensing_item_id_index` (`dispensing_item_id`),
  KEY `pharmacy_safety_alerts_medication_id_index` (`medication_id`),
  CONSTRAINT `pharmacy_safety_alerts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_safety_alerts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_safety_alerts_dispensing_item_id_foreign` FOREIGN KEY (`dispensing_item_id`) REFERENCES `pharmacy_dispensing_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_safety_alerts_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_safety_alerts_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `pharmacy_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_safety_alerts_overridden_by_foreign` FOREIGN KEY (`overridden_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_safety_alerts`
--

LOCK TABLES `pharmacy_safety_alerts` WRITE;
/*!40000 ALTER TABLE `pharmacy_safety_alerts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_safety_alerts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_stock`
--

DROP TABLE IF EXISTS `pharmacy_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `quantity_available` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_stock_unique_join` (`store_id`,`medication_id`,`batch_id`),
  KEY `pharmacy_stock_medication_id_foreign` (`medication_id`),
  KEY `pharmacy_stock_batch_id_foreign` (`batch_id`),
  KEY `pharmacy_stock_store_id_medication_id_index` (`store_id`,`medication_id`),
  CONSTRAINT `pharmacy_stock_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stock_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stock_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_stock`
--

LOCK TABLES `pharmacy_stock` WRITE;
/*!40000 ALTER TABLE `pharmacy_stock` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_stock` VALUES
(1,1,1,1,500,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,1,2,2,500,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `pharmacy_stock` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_stock_count_items`
--

DROP TABLE IF EXISTS `pharmacy_stock_count_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stock_count_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_count_id` bigint(20) unsigned NOT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `expected_quantity` int(10) unsigned NOT NULL,
  `counted_quantity` int(10) unsigned DEFAULT NULL,
  `variance` int(11) DEFAULT NULL,
  `is_adjusted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_stock_count_item_unique` (`stock_count_id`,`medication_id`,`batch_id`),
  KEY `pharmacy_stock_count_items_medication_id_foreign` (`medication_id`),
  KEY `pharmacy_stock_count_items_batch_id_foreign` (`batch_id`),
  KEY `pharmacy_stock_count_items_stock_count_id_index` (`stock_count_id`),
  CONSTRAINT `pharmacy_stock_count_items_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stock_count_items_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stock_count_items_stock_count_id_foreign` FOREIGN KEY (`stock_count_id`) REFERENCES `pharmacy_stock_counts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_stock_count_items`
--

LOCK TABLES `pharmacy_stock_count_items` WRITE;
/*!40000 ALTER TABLE `pharmacy_stock_count_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_stock_count_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_stock_counts`
--

DROP TABLE IF EXISTS `pharmacy_stock_counts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stock_counts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `count_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'in_progress',
  `started_by` bigint(20) unsigned DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_stock_counts_company_id_count_number_unique` (`company_id`,`count_number`),
  KEY `pharmacy_stock_counts_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_stock_counts_started_by_foreign` (`started_by`),
  KEY `pharmacy_stock_counts_completed_by_foreign` (`completed_by`),
  KEY `pharmacy_stock_counts_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `pharmacy_stock_counts_store_id_index` (`store_id`),
  CONSTRAINT `pharmacy_stock_counts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_counts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stock_counts_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_counts_started_by_foreign` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stock_counts_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_stock_counts`
--

LOCK TABLES `pharmacy_stock_counts` WRITE;
/*!40000 ALTER TABLE `pharmacy_stock_counts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_stock_counts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_stock_transactions`
--

DROP TABLE IF EXISTS `pharmacy_stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stock_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `direction` enum('in','out') NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `balance_after` int(10) unsigned NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `performed_by` bigint(20) unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_stock_transactions_medication_id_foreign` (`medication_id`),
  KEY `pharmacy_stock_transactions_batch_id_foreign` (`batch_id`),
  KEY `pharmacy_stock_transactions_performed_by_foreign` (`performed_by`),
  KEY `pharmacy_stock_txn_scope_time_idx` (`store_id`,`medication_id`,`batch_id`,`created_at`),
  KEY `pharmacy_stock_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `pharmacy_stock_transactions_type_index` (`type`),
  CONSTRAINT `pharmacy_stock_transactions_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stock_transactions_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stock_transactions_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stock_transactions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_stock_transactions`
--

LOCK TABLES `pharmacy_stock_transactions` WRITE;
/*!40000 ALTER TABLE `pharmacy_stock_transactions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_stock_transactions` VALUES
(1,1,1,1,'opening','in',500,500,NULL,NULL,1,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,1,2,2,'opening','in',500,500,NULL,NULL,1,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `pharmacy_stock_transactions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_stores`
--

DROP TABLE IF EXISTS `pharmacy_stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `store_type` varchar(255) NOT NULL DEFAULT 'main',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_stores_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `pharmacy_stores_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_stores_department_id_foreign` (`department_id`),
  KEY `pharmacy_stores_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `pharmacy_stores_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_stores_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_stores_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_stores`
--

LOCK TABLES `pharmacy_stores` WRITE;
/*!40000 ALTER TABLE `pharmacy_stores` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_stores` VALUES
(1,1,NULL,NULL,'PH-MAIN','Main Pharmacy Store','main',1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `pharmacy_stores` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_transfer_items`
--

DROP TABLE IF EXISTS `pharmacy_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint(20) unsigned NOT NULL,
  `medication_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `quantity_requested` int(10) unsigned NOT NULL,
  `quantity_dispatched` int(10) unsigned DEFAULT NULL,
  `quantity_received` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_transfer_items_batch_id_foreign` (`batch_id`),
  KEY `pharmacy_transfer_items_transfer_id_index` (`transfer_id`),
  KEY `pharmacy_transfer_items_medication_id_batch_id_index` (`medication_id`,`batch_id`),
  CONSTRAINT `pharmacy_transfer_items_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `pharmacy_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_transfer_items_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `pharmacy_medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_transfer_items_transfer_id_foreign` FOREIGN KEY (`transfer_id`) REFERENCES `pharmacy_transfers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_transfer_items`
--

LOCK TABLES `pharmacy_transfer_items` WRITE;
/*!40000 ALTER TABLE `pharmacy_transfer_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_transfer_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pharmacy_transfers`
--

DROP TABLE IF EXISTS `pharmacy_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `source_store_id` bigint(20) unsigned NOT NULL,
  `destination_store_id` bigint(20) unsigned NOT NULL,
  `transfer_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `dispatched_by` bigint(20) unsigned DEFAULT NULL,
  `dispatched_at` timestamp NULL DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pharmacy_transfers_company_id_transfer_number_unique` (`company_id`,`transfer_number`),
  KEY `pharmacy_transfers_branch_id_foreign` (`branch_id`),
  KEY `pharmacy_transfers_source_store_id_foreign` (`source_store_id`),
  KEY `pharmacy_transfers_destination_store_id_foreign` (`destination_store_id`),
  KEY `pharmacy_transfers_requested_by_foreign` (`requested_by`),
  KEY `pharmacy_transfers_approved_by_foreign` (`approved_by`),
  KEY `pharmacy_transfers_dispatched_by_foreign` (`dispatched_by`),
  KEY `pharmacy_transfers_received_by_foreign` (`received_by`),
  KEY `pharmacy_transfers_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  CONSTRAINT `pharmacy_transfers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_transfers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_transfers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_transfers_destination_store_id_foreign` FOREIGN KEY (`destination_store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_transfers_dispatched_by_foreign` FOREIGN KEY (`dispatched_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_transfers_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_transfers_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pharmacy_transfers_source_store_id_foreign` FOREIGN KEY (`source_store_id`) REFERENCES `pharmacy_stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_transfers`
--

LOCK TABLES `pharmacy_transfers` WRITE;
/*!40000 ALTER TABLE `pharmacy_transfers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pharmacy_transfers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `prescription_items`
--

DROP TABLE IF EXISTS `prescription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescription_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `dosage_form` varchar(255) DEFAULT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `frequency` varchar(255) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescription_items_prescription_id_foreign` (`prescription_id`),
  KEY `prescription_items_company_id_prescription_id_index` (`company_id`,`prescription_id`),
  CONSTRAINT `prescription_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescription_items_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescription_items`
--

LOCK TABLES `prescription_items` WRITE;
/*!40000 ALTER TABLE `prescription_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `prescription_items` VALUES
(1,1,1,'Napa','today','500mg','7','7',NULL,NULL,NULL,1,'2026-09-23 22:22:44','2026-09-23 22:22:44',NULL);
/*!40000 ALTER TABLE `prescription_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `prescription_no` varchar(255) NOT NULL,
  `clinical_notes` text DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `issued_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `prescribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescriptions_prescription_no_unique` (`prescription_no`),
  KEY `prescriptions_branch_id_foreign` (`branch_id`),
  KEY `prescriptions_appointment_id_foreign` (`appointment_id`),
  KEY `prescriptions_patient_id_foreign` (`patient_id`),
  KEY `prescriptions_doctor_id_foreign` (`doctor_id`),
  KEY `prescriptions_created_by_foreign` (`created_by`),
  KEY `prescriptions_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `prescriptions_company_id_appointment_id_index` (`company_id`,`appointment_id`),
  KEY `prescriptions_encounter_id_index` (`encounter_id`),
  KEY `prescriptions_issued_by_foreign` (`issued_by`),
  CONSTRAINT `prescriptions_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prescriptions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prescriptions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prescriptions_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `prescriptions` VALUES
(1,1,2,1,NULL,1,2,'HN--PRX-00000001','This is clinical advice','This general advice','draft',NULL,1,'2026-09-23 22:22:44','2026-09-23 22:22:44','2026-09-23 22:22:44',NULL,NULL);
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `provider_unavailability`
--

DROP TABLE IF EXISTS `provider_unavailability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_unavailability` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `provider_id` bigint(20) unsigned NOT NULL,
  `reason_type` enum('leave','training','meeting','conference','personal','emergency','other') NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `provider_unavailability_branch_id_foreign` (`branch_id`),
  KEY `provider_unavailability_provider_id_foreign` (`provider_id`),
  KEY `provider_unavailability_created_by_foreign` (`created_by`),
  KEY `provider_unavailability_window_idx` (`company_id`,`provider_id`,`start_at`,`end_at`),
  CONSTRAINT `provider_unavailability_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_unavailability_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_unavailability_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `provider_unavailability_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provider_unavailability`
--

LOCK TABLES `provider_unavailability` WRITE;
/*!40000 ALTER TABLE `provider_unavailability` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `provider_unavailability` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `providers`
--

DROP TABLE IF EXISTS `providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `providers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `provider_code` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `provider_type` varchar(255) NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_company_code_unique` (`company_id`,`provider_code`),
  KEY `providers_branch_id_foreign` (`branch_id`),
  KEY `providers_user_id_foreign` (`user_id`),
  KEY `providers_department_id_foreign` (`department_id`),
  KEY `provider_company_branch_status_idx` (`company_id`,`branch_id`,`status`),
  KEY `providers_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `providers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `providers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `providers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `providers_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `providers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `providers`
--

LOCK TABLES `providers` WRITE;
/*!40000 ALTER TABLE `providers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `providers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_body_parts`
--

DROP TABLE IF EXISTS `radiology_body_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_body_parts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `laterality_applicable` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_body_parts_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `radiology_body_parts_branch_id_foreign` (`branch_id`),
  KEY `radiology_body_parts_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `radiology_body_parts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_body_parts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_body_parts`
--

LOCK TABLES `radiology_body_parts` WRITE;
/*!40000 ALTER TABLE `radiology_body_parts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `radiology_body_parts` VALUES
(1,1,NULL,'HEAD','Head',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,'BRAIN','Brain',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,'CHEST','Chest',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,1,NULL,'ABDOMEN','Abdomen',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(5,1,NULL,'SPINE','Spine',0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(6,1,NULL,'SHOULDER','Shoulder',1,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(7,1,NULL,'KNEE','Knee',1,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(8,1,NULL,'HAND','Hand',1,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `radiology_body_parts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_contrast_agents`
--

DROP TABLE IF EXISTS `radiology_contrast_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_contrast_agents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `concentration` varchar(255) DEFAULT NULL,
  `default_route` varchar(255) DEFAULT NULL,
  `default_dose` varchar(255) DEFAULT NULL,
  `max_dose` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `safety_information` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_contrast_agents_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `radiology_contrast_agents_branch_id_foreign` (`branch_id`),
  KEY `radiology_contrast_agents_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `radiology_contrast_agents_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_contrast_agents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_contrast_agents`
--

LOCK TABLES `radiology_contrast_agents` WRITE;
/*!40000 ALTER TABLE `radiology_contrast_agents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_contrast_agents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_counters`
--

DROP TABLE IF EXISTS `radiology_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(3) NOT NULL,
  `prefix` varchar(16) NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_counters_scope_unique` (`company_id`,`branch_id`,`document_type`,`prefix`),
  KEY `radiology_counters_branch_id_foreign` (`branch_id`),
  KEY `radiology_counters_company_id_branch_id_document_type_index` (`company_id`,`branch_id`,`document_type`),
  CONSTRAINT `radiology_counters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_counters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_counters`
--

LOCK TABLES `radiology_counters` WRITE;
/*!40000 ALTER TABLE `radiology_counters` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_counters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_critical_findings`
--

DROP TABLE IF EXISTS `radiology_critical_findings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_critical_findings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `report_id` bigint(20) unsigned NOT NULL,
  `finding_text` text NOT NULL,
  `detected_by` bigint(20) unsigned DEFAULT NULL,
  `detected_at` timestamp NOT NULL,
  `notified_to` bigint(20) unsigned DEFAULT NULL,
  `notification_method` varchar(255) DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint(20) unsigned DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'detected',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radiology_critical_findings_branch_id_foreign` (`branch_id`),
  KEY `radiology_critical_findings_detected_by_foreign` (`detected_by`),
  KEY `radiology_critical_findings_notified_to_foreign` (`notified_to`),
  KEY `radiology_critical_findings_acknowledged_by_foreign` (`acknowledged_by`),
  KEY `radiology_critical_findings_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `radiology_critical_findings_report_id_index` (`report_id`),
  CONSTRAINT `radiology_critical_findings_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_critical_findings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_critical_findings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_critical_findings_detected_by_foreign` FOREIGN KEY (`detected_by`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_critical_findings_notified_to_foreign` FOREIGN KEY (`notified_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_critical_findings_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `radiology_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_critical_findings`
--

LOCK TABLES `radiology_critical_findings` WRITE;
/*!40000 ALTER TABLE `radiology_critical_findings` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_critical_findings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_dicom_events`
--

DROP TABLE IF EXISTS `radiology_dicom_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_dicom_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `radiology_examination_id` bigint(20) unsigned DEFAULT NULL,
  `pacs_server_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radiology_dicom_events_branch_id_foreign` (`branch_id`),
  KEY `radiology_dicom_events_pacs_server_id_foreign` (`pacs_server_id`),
  KEY `radiology_dicom_events_scope_idx` (`company_id`,`branch_id`,`event_type`,`status`),
  KEY `radiology_dicom_events_radiology_examination_id_index` (`radiology_examination_id`),
  CONSTRAINT `radiology_dicom_events_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_dicom_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_dicom_events_pacs_server_id_foreign` FOREIGN KEY (`pacs_server_id`) REFERENCES `radiology_pacs_servers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_dicom_events_radiology_examination_id_foreign` FOREIGN KEY (`radiology_examination_id`) REFERENCES `radiology_examinations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_dicom_events`
--

LOCK TABLES `radiology_dicom_events` WRITE;
/*!40000 ALTER TABLE `radiology_dicom_events` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_dicom_events` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_examinations`
--

DROP TABLE IF EXISTS `radiology_examinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_examinations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `modality_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `check_in_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `technologist_id` bigint(20) unsigned DEFAULT NULL,
  `performing_provider_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_radiologist_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `technical_notes` text DEFAULT NULL,
  `mri_safety_screening` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mri_safety_screening`)),
  `mri_screened_by` bigint(20) unsigned DEFAULT NULL,
  `mri_screened_at` timestamp NULL DEFAULT NULL,
  `contrast_agent_id` bigint(20) unsigned DEFAULT NULL,
  `contrast_route` varchar(255) DEFAULT NULL,
  `contrast_dose` varchar(255) DEFAULT NULL,
  `contrast_administered_at` timestamp NULL DEFAULT NULL,
  `contrast_administered_by` bigint(20) unsigned DEFAULT NULL,
  `contrast_reaction` varchar(255) DEFAULT NULL,
  `contrast_reaction_severity` varchar(255) DEFAULT NULL,
  `contrast_action_taken` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radiology_examinations_branch_id_foreign` (`branch_id`),
  KEY `radiology_examinations_order_item_id_foreign` (`order_item_id`),
  KEY `radiology_examinations_encounter_id_foreign` (`encounter_id`),
  KEY `radiology_examinations_appointment_id_foreign` (`appointment_id`),
  KEY `radiology_examinations_technologist_id_foreign` (`technologist_id`),
  KEY `radiology_examinations_performing_provider_id_foreign` (`performing_provider_id`),
  KEY `radiology_examinations_assigned_by_foreign` (`assigned_by`),
  KEY `radiology_examinations_mri_screened_by_foreign` (`mri_screened_by`),
  KEY `radiology_examinations_contrast_agent_id_foreign` (`contrast_agent_id`),
  KEY `radiology_examinations_contrast_administered_by_foreign` (`contrast_administered_by`),
  KEY `radiology_examinations_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `radiology_examinations_patient_id_index` (`patient_id`),
  KEY `radiology_examinations_modality_id_index` (`modality_id`),
  KEY `radiology_examinations_assigned_radiologist_id_index` (`assigned_radiologist_id`),
  CONSTRAINT `radiology_examinations_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_examinations_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_examinations_assigned_radiologist_id_foreign` FOREIGN KEY (`assigned_radiologist_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_examinations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_examinations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_examinations_contrast_administered_by_foreign` FOREIGN KEY (`contrast_administered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_examinations_contrast_agent_id_foreign` FOREIGN KEY (`contrast_agent_id`) REFERENCES `radiology_contrast_agents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_examinations_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_examinations_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `radiology_modalities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_examinations_mri_screened_by_foreign` FOREIGN KEY (`mri_screened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_examinations_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `radiology_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_examinations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_examinations_performing_provider_id_foreign` FOREIGN KEY (`performing_provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_examinations_technologist_id_foreign` FOREIGN KEY (`technologist_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_examinations`
--

LOCK TABLES `radiology_examinations` WRITE;
/*!40000 ALTER TABLE `radiology_examinations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_examinations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_instances`
--

DROP TABLE IF EXISTS `radiology_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_instances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `series_id` bigint(20) unsigned NOT NULL,
  `sop_instance_uid` varchar(255) NOT NULL,
  `sop_class_uid` varchar(255) DEFAULT NULL,
  `instance_number` varchar(255) DEFAULT NULL,
  `file_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_instances_sop_instance_uid_unique` (`sop_instance_uid`),
  KEY `radiology_instances_series_id_index` (`series_id`),
  CONSTRAINT `radiology_instances_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `radiology_series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_instances`
--

LOCK TABLES `radiology_instances` WRITE;
/*!40000 ALTER TABLE `radiology_instances` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_instances` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_modalities`
--

DROP TABLE IF EXISTS `radiology_modalities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_modalities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `modality_type` varchar(255) NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `ae_title` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `port` int(10) unsigned DEFAULT NULL,
  `pacs_endpoint` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'offline',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_modalities_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `radiology_modalities_branch_id_foreign` (`branch_id`),
  KEY `radiology_modalities_section_id_foreign` (`section_id`),
  KEY `radiology_modalities_department_id_foreign` (`department_id`),
  KEY `radiology_modalities_room_id_foreign` (`room_id`),
  KEY `radiology_modalities_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  KEY `radiology_modalities_modality_type_index` (`modality_type`),
  CONSTRAINT `radiology_modalities_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_modalities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_modalities_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_modalities_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `appointment_rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_modalities_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `radiology_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_modalities`
--

LOCK TABLES `radiology_modalities` WRITE;
/*!40000 ALTER TABLE `radiology_modalities` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_modalities` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_order_items`
--

DROP TABLE IF EXISTS `radiology_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `radiology_order_id` bigint(20) unsigned NOT NULL,
  `procedure_id` bigint(20) unsigned DEFAULT NULL,
  `protocol_id` bigint(20) unsigned DEFAULT NULL,
  `body_part_id` bigint(20) unsigned DEFAULT NULL,
  `laterality` varchar(255) DEFAULT NULL,
  `requested_procedure_name` varchar(255) NOT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'routine',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `result_status` varchar(255) NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radiology_order_items_protocol_id_foreign` (`protocol_id`),
  KEY `radiology_order_items_body_part_id_foreign` (`body_part_id`),
  KEY `radiology_order_items_radiology_order_id_status_index` (`radiology_order_id`,`status`),
  KEY `radiology_order_items_procedure_id_index` (`procedure_id`),
  CONSTRAINT `radiology_order_items_body_part_id_foreign` FOREIGN KEY (`body_part_id`) REFERENCES `radiology_body_parts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_order_items_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `radiology_procedures` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_order_items_protocol_id_foreign` FOREIGN KEY (`protocol_id`) REFERENCES `radiology_protocols` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_order_items_radiology_order_id_foreign` FOREIGN KEY (`radiology_order_id`) REFERENCES `radiology_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_order_items`
--

LOCK TABLES `radiology_order_items` WRITE;
/*!40000 ALTER TABLE `radiology_order_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_order_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_orders`
--

DROP TABLE IF EXISTS `radiology_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `clinical_order_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `accession_number` varchar(255) DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'routine',
  `status` varchar(255) NOT NULL DEFAULT 'ordered',
  `ordered_by` bigint(20) unsigned DEFAULT NULL,
  `ordered_at` timestamp NULL DEFAULT NULL,
  `registered_by` bigint(20) unsigned DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL,
  `clinical_indication` text DEFAULT NULL,
  `provisional_diagnosis` text DEFAULT NULL,
  `relevant_history` text DEFAULT NULL,
  `requested_date` date DEFAULT NULL,
  `contrast_required` tinyint(1) NOT NULL DEFAULT 0,
  `special_instructions` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_orders_company_id_order_number_unique` (`company_id`,`order_number`),
  UNIQUE KEY `radiology_orders_company_id_accession_number_unique` (`company_id`,`accession_number`),
  KEY `radiology_orders_branch_id_foreign` (`branch_id`),
  KEY `radiology_orders_department_id_foreign` (`department_id`),
  KEY `radiology_orders_ordered_by_foreign` (`ordered_by`),
  KEY `radiology_orders_registered_by_foreign` (`registered_by`),
  KEY `radiology_orders_cancelled_by_foreign` (`cancelled_by`),
  KEY `radiology_orders_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  KEY `radiology_orders_patient_id_index` (`patient_id`),
  KEY `radiology_orders_encounter_id_index` (`encounter_id`),
  KEY `radiology_orders_clinical_order_id_index` (`clinical_order_id`),
  CONSTRAINT `radiology_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_orders_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_orders_clinical_order_id_foreign` FOREIGN KEY (`clinical_order_id`) REFERENCES `clinical_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_orders_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_orders_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_orders_ordered_by_foreign` FOREIGN KEY (`ordered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_orders_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_orders_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_orders`
--

LOCK TABLES `radiology_orders` WRITE;
/*!40000 ALTER TABLE `radiology_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_pacs_servers`
--

DROP TABLE IF EXISTS `radiology_pacs_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_pacs_servers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `adapter_type` varchar(255) NOT NULL DEFAULT 'null',
  `base_url` varchar(255) DEFAULT NULL,
  `ae_title` varchar(255) DEFAULT NULL,
  `port` int(10) unsigned DEFAULT NULL,
  `username` text DEFAULT NULL,
  `password` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_pacs_servers_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `radiology_pacs_servers_branch_id_foreign` (`branch_id`),
  CONSTRAINT `radiology_pacs_servers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_pacs_servers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_pacs_servers`
--

LOCK TABLES `radiology_pacs_servers` WRITE;
/*!40000 ALTER TABLE `radiology_pacs_servers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `radiology_pacs_servers` VALUES
(1,1,NULL,'DEFAULT','Default PACS (not connected)','null',NULL,NULL,NULL,NULL,NULL,0,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `radiology_pacs_servers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_procedures`
--

DROP TABLE IF EXISTS `radiology_procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_procedures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `body_part_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `modality_type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(10) unsigned DEFAULT NULL,
  `turnaround_time_minutes` int(10) unsigned DEFAULT NULL,
  `contrast_required` tinyint(1) NOT NULL DEFAULT 0,
  `preparation_required` tinyint(1) NOT NULL DEFAULT 0,
  `sedation_required` tinyint(1) NOT NULL DEFAULT 0,
  `preparation_instructions` text DEFAULT NULL,
  `requires_senior_approval` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_procedures_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `radiology_procedures_branch_id_foreign` (`branch_id`),
  KEY `radiology_procedures_section_id_foreign` (`section_id`),
  KEY `radiology_procedures_body_part_id_foreign` (`body_part_id`),
  KEY `radiology_procedures_created_by_foreign` (`created_by`),
  KEY `radiology_procedures_updated_by_foreign` (`updated_by`),
  KEY `radiology_procedures_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `radiology_procedures_body_part_id_foreign` FOREIGN KEY (`body_part_id`) REFERENCES `radiology_body_parts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_procedures_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_procedures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_procedures_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_procedures_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `radiology_sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_procedures_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_procedures`
--

LOCK TABLES `radiology_procedures` WRITE;
/*!40000 ALTER TABLE `radiology_procedures` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `radiology_procedures` VALUES
(1,1,NULL,1,3,'XR-CHEST-PA','Chest X-Ray PA','XR',NULL,10,60,0,0,0,NULL,0,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,2,2,'CT-BRAIN','CT Brain','CT',NULL,20,120,0,0,0,NULL,0,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,4,4,'US-ABDOMEN','Ultrasound Abdomen','US',NULL,20,60,0,1,0,'Fasting for 6 hours prior to the scan.',0,1,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `radiology_procedures` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_protocols`
--

DROP TABLE IF EXISTS `radiology_protocols`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_protocols` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_id` bigint(20) unsigned NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `contrast_required` tinyint(1) DEFAULT NULL,
  `preparation_instructions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_protocols_procedure_id_code_unique` (`procedure_id`,`code`),
  CONSTRAINT `radiology_protocols_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `radiology_procedures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_protocols`
--

LOCK TABLES `radiology_protocols` WRITE;
/*!40000 ALTER TABLE `radiology_protocols` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `radiology_protocols` VALUES
(1,2,'NON-CONTRAST','Non-Contrast',NULL,0,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,2,'CONTRAST','Contrast',NULL,1,NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58');
/*!40000 ALTER TABLE `radiology_protocols` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_report_findings`
--

DROP TABLE IF EXISTS `radiology_report_findings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_report_findings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) unsigned NOT NULL,
  `organ` varchar(255) DEFAULT NULL,
  `finding_text` text NOT NULL,
  `measurement_value` decimal(10,3) DEFAULT NULL,
  `measurement_unit` varchar(255) DEFAULT NULL,
  `laterality` varchar(255) DEFAULT NULL,
  `is_critical` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radiology_report_findings_report_id_index` (`report_id`),
  CONSTRAINT `radiology_report_findings_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `radiology_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_report_findings`
--

LOCK TABLES `radiology_report_findings` WRITE;
/*!40000 ALTER TABLE `radiology_report_findings` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_report_findings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_report_templates`
--

DROP TABLE IF EXISTS `radiology_report_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_report_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `procedure_id` bigint(20) unsigned DEFAULT NULL,
  `modality_type` varchar(255) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sections`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_report_templates_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `radiology_report_templates_branch_id_foreign` (`branch_id`),
  KEY `radiology_report_templates_procedure_id_foreign` (`procedure_id`),
  CONSTRAINT `radiology_report_templates_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_report_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_report_templates_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `radiology_procedures` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_report_templates`
--

LOCK TABLES `radiology_report_templates` WRITE;
/*!40000 ALTER TABLE `radiology_report_templates` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_report_templates` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_reports`
--

DROP TABLE IF EXISTS `radiology_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `examination_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `report_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `radiologist_id` bigint(20) unsigned DEFAULT NULL,
  `clinical_indication` text DEFAULT NULL,
  `technique` text DEFAULT NULL,
  `findings` longtext DEFAULT NULL,
  `impression` longtext DEFAULT NULL,
  `recommendation` longtext DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `amended_from_id` bigint(20) unsigned DEFAULT NULL,
  `amendment_reason` text DEFAULT NULL,
  `amended_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_reports_company_id_report_number_version_unique` (`company_id`,`report_number`,`version`),
  KEY `radiology_reports_branch_id_foreign` (`branch_id`),
  KEY `radiology_reports_template_id_foreign` (`template_id`),
  KEY `radiology_reports_radiologist_id_foreign` (`radiologist_id`),
  KEY `radiology_reports_reviewed_by_foreign` (`reviewed_by`),
  KEY `radiology_reports_approved_by_foreign` (`approved_by`),
  KEY `radiology_reports_amended_from_id_foreign` (`amended_from_id`),
  KEY `radiology_reports_amended_by_foreign` (`amended_by`),
  KEY `radiology_reports_examination_id_is_current_index` (`examination_id`,`is_current`),
  KEY `radiology_reports_company_id_branch_id_status_index` (`company_id`,`branch_id`,`status`),
  CONSTRAINT `radiology_reports_amended_by_foreign` FOREIGN KEY (`amended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_reports_amended_from_id_foreign` FOREIGN KEY (`amended_from_id`) REFERENCES `radiology_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_reports_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_reports_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_reports_examination_id_foreign` FOREIGN KEY (`examination_id`) REFERENCES `radiology_examinations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_reports_radiologist_id_foreign` FOREIGN KEY (`radiologist_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_reports_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `radiology_report_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_reports`
--

LOCK TABLES `radiology_reports` WRITE;
/*!40000 ALTER TABLE `radiology_reports` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_reports` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_sections`
--

DROP TABLE IF EXISTS `radiology_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_sections_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `radiology_sections_branch_id_foreign` (`branch_id`),
  KEY `radiology_sections_department_id_foreign` (`department_id`),
  KEY `radiology_sections_company_id_branch_id_is_active_index` (`company_id`,`branch_id`,`is_active`),
  CONSTRAINT `radiology_sections_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_sections_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_sections_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_sections`
--

LOCK TABLES `radiology_sections` WRITE;
/*!40000 ALTER TABLE `radiology_sections` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `radiology_sections` VALUES
(1,1,NULL,NULL,'XR','X-Ray',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(2,1,NULL,NULL,'CT','CT',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(3,1,NULL,NULL,'MRI','MRI',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL),
(4,1,NULL,NULL,'US','Ultrasound',NULL,1,'2026-09-23 17:34:58','2026-09-23 17:34:58',NULL);
/*!40000 ALTER TABLE `radiology_sections` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_series`
--

DROP TABLE IF EXISTS `radiology_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_series` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `study_id` bigint(20) unsigned NOT NULL,
  `series_instance_uid` varchar(255) NOT NULL,
  `series_number` varchar(255) DEFAULT NULL,
  `series_description` varchar(255) DEFAULT NULL,
  `modality` varchar(255) DEFAULT NULL,
  `body_part` varchar(255) DEFAULT NULL,
  `number_of_instances` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_series_series_instance_uid_unique` (`series_instance_uid`),
  KEY `radiology_series_study_id_index` (`study_id`),
  CONSTRAINT `radiology_series_study_id_foreign` FOREIGN KEY (`study_id`) REFERENCES `radiology_studies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_series`
--

LOCK TABLES `radiology_series` WRITE;
/*!40000 ALTER TABLE `radiology_series` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_series` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `radiology_studies`
--

DROP TABLE IF EXISTS `radiology_studies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_studies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `radiology_examination_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `pacs_server_id` bigint(20) unsigned DEFAULT NULL,
  `accession_number` varchar(255) NOT NULL,
  `study_instance_uid` varchar(255) DEFAULT NULL,
  `study_id` varchar(255) DEFAULT NULL,
  `study_date` date DEFAULT NULL,
  `study_time` time DEFAULT NULL,
  `modality` varchar(255) DEFAULT NULL,
  `study_description` varchar(255) DEFAULT NULL,
  `body_part` varchar(255) DEFAULT NULL,
  `referring_physician` varchar(255) DEFAULT NULL,
  `institution_name` varchar(255) DEFAULT NULL,
  `pacs_status` varchar(255) NOT NULL DEFAULT 'pending',
  `study_status` varchar(255) NOT NULL DEFAULT 'unmatched',
  `number_of_series` int(10) unsigned NOT NULL DEFAULT 0,
  `number_of_instances` int(10) unsigned NOT NULL DEFAULT 0,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_studies_study_instance_uid_unique` (`study_instance_uid`),
  KEY `radiology_studies_branch_id_foreign` (`branch_id`),
  KEY `radiology_studies_patient_id_foreign` (`patient_id`),
  KEY `radiology_studies_pacs_server_id_foreign` (`pacs_server_id`),
  KEY `radiology_studies_company_id_branch_id_study_status_index` (`company_id`,`branch_id`,`study_status`),
  KEY `radiology_studies_radiology_examination_id_index` (`radiology_examination_id`),
  KEY `radiology_studies_accession_number_index` (`accession_number`),
  CONSTRAINT `radiology_studies_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_studies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_studies_pacs_server_id_foreign` FOREIGN KEY (`pacs_server_id`) REFERENCES `radiology_pacs_servers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_studies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `radiology_studies_radiology_examination_id_foreign` FOREIGN KEY (`radiology_examination_id`) REFERENCES `radiology_examinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radiology_studies`
--

LOCK TABLES `radiology_studies` WRITE;
/*!40000 ALTER TABLE `radiology_studies` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `radiology_studies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `role_has_permissions` VALUES
(1,1),
(2,1),
(3,1),
(4,1),
(5,1),
(6,1),
(7,1),
(8,1),
(9,1),
(10,1),
(11,1),
(12,1),
(13,1),
(14,1),
(15,1),
(16,1),
(17,1),
(18,1),
(19,1),
(20,1),
(21,1),
(22,1),
(23,1),
(24,1),
(25,1),
(26,1),
(27,1),
(28,1),
(29,1),
(30,1),
(31,1),
(32,1),
(33,1),
(34,1),
(35,1),
(36,1),
(37,1),
(38,1),
(39,1),
(40,1),
(41,1),
(42,1),
(43,1),
(44,1),
(45,1),
(46,1),
(47,1),
(48,1),
(49,1),
(50,1),
(51,1),
(52,1),
(53,1),
(54,1),
(55,1),
(56,1),
(57,1),
(58,1),
(59,1),
(60,1),
(61,1),
(62,1),
(63,1),
(64,1),
(65,1),
(66,1),
(67,1),
(68,1),
(69,1),
(70,1),
(71,1),
(72,1),
(73,1),
(74,1),
(75,1),
(76,1),
(77,1),
(78,1),
(79,1),
(80,1),
(81,1),
(82,1),
(83,1),
(84,1),
(85,1),
(86,1),
(87,1),
(88,1),
(89,1),
(90,1),
(91,1),
(92,1),
(93,1),
(94,1),
(95,1),
(96,1),
(97,1),
(98,1),
(99,1),
(100,1),
(101,1),
(102,1),
(103,1),
(104,1),
(105,1),
(106,1),
(107,1),
(108,1),
(109,1),
(110,1),
(111,1),
(112,1),
(113,1),
(114,1),
(115,1),
(116,1),
(117,1),
(118,1),
(119,1),
(120,1),
(121,1),
(122,1),
(123,1),
(124,1),
(125,1),
(126,1),
(127,1),
(128,1),
(129,1),
(130,1),
(131,1),
(132,1),
(133,1),
(134,1),
(135,1),
(136,1),
(137,1),
(138,1),
(139,1),
(140,1),
(141,1),
(142,1),
(143,1),
(144,1),
(145,1),
(146,1),
(147,1),
(148,1),
(149,1),
(150,1),
(151,1),
(152,1),
(153,1),
(154,1),
(155,1),
(156,1),
(157,1),
(158,1),
(159,1),
(160,1),
(161,1),
(162,1),
(163,1),
(164,1),
(165,1),
(166,1),
(167,1),
(168,1),
(169,1),
(170,1),
(171,1),
(172,1),
(173,1),
(174,1),
(175,1),
(176,1),
(177,1),
(178,1),
(179,1),
(180,1),
(181,1),
(182,1),
(183,1),
(184,1),
(185,1),
(186,1),
(187,1),
(188,1),
(189,1),
(190,1),
(191,1),
(192,1),
(193,1),
(194,1),
(195,1),
(196,1),
(197,1),
(198,1),
(199,1),
(200,1),
(201,1),
(202,1),
(203,1),
(204,1),
(205,1),
(206,1),
(207,1),
(208,1),
(209,1),
(210,1),
(211,1),
(212,1),
(213,1),
(214,1),
(215,1),
(216,1),
(217,1),
(218,1),
(219,1),
(220,1),
(221,1),
(222,1),
(223,1),
(224,1),
(225,1),
(226,1),
(227,1),
(228,1),
(229,1),
(230,1),
(231,1),
(232,1),
(233,1),
(234,1),
(235,1),
(236,1),
(237,1),
(238,1),
(239,1),
(240,1),
(241,1),
(242,1),
(243,1),
(244,1),
(245,1),
(246,1),
(247,1),
(248,1),
(249,1),
(250,1),
(251,1),
(252,1),
(253,1),
(254,1),
(255,1),
(256,1),
(257,1),
(258,1),
(259,1),
(260,1),
(261,1),
(262,1),
(263,1),
(264,1),
(265,1),
(266,1),
(267,1),
(268,1),
(269,1),
(270,1),
(271,1),
(272,1),
(273,1),
(274,1),
(275,1),
(276,1),
(277,1),
(278,1),
(279,1),
(280,1),
(281,1),
(282,1),
(283,1),
(284,1),
(285,1),
(286,1),
(287,1),
(288,1),
(289,1),
(290,1),
(291,1),
(292,1),
(293,1),
(294,1),
(295,1),
(296,1),
(297,1),
(298,1),
(299,1),
(300,1),
(301,1),
(302,1),
(303,1),
(304,1),
(305,1),
(306,1),
(307,1),
(308,1),
(309,1),
(310,1),
(311,1),
(312,1),
(313,1),
(314,1),
(315,1),
(316,1),
(317,1),
(318,1),
(319,1),
(320,1),
(321,1),
(322,1),
(323,1),
(324,1),
(325,1),
(326,1),
(327,1),
(328,1),
(329,1),
(330,1),
(331,1),
(332,1),
(333,1),
(334,1),
(335,1),
(336,1),
(337,1),
(338,1),
(339,1),
(340,1),
(341,1),
(342,1),
(343,1),
(344,1),
(345,1),
(346,1),
(347,1),
(348,1),
(349,1),
(350,1),
(351,1),
(352,1),
(353,1),
(354,1),
(355,1),
(356,1),
(357,1),
(358,1),
(359,1),
(360,1),
(361,1),
(362,1),
(363,1),
(364,1),
(365,1),
(366,1),
(367,1),
(368,1),
(369,1),
(370,1),
(371,1),
(372,1),
(373,1),
(374,1),
(375,1),
(376,1),
(377,1),
(378,1),
(379,1),
(380,1),
(381,1),
(382,1),
(383,1),
(384,1),
(385,1),
(386,1),
(387,1),
(388,1),
(389,1),
(390,1),
(391,1),
(392,1),
(393,1),
(394,1),
(395,1),
(396,1),
(397,1),
(398,1),
(399,1),
(400,1),
(401,1),
(402,1),
(403,1),
(404,1),
(405,1),
(406,1),
(407,1),
(408,1),
(409,1),
(410,1),
(411,1),
(412,1),
(413,1),
(414,1),
(415,1),
(416,1),
(417,1),
(418,1),
(419,1),
(420,1),
(1,2),
(2,2),
(3,2),
(4,2),
(7,2),
(8,2),
(9,2),
(10,2),
(11,2),
(12,2),
(13,2),
(14,2),
(15,2),
(16,2),
(17,2),
(18,2),
(19,2),
(20,2),
(21,2),
(22,2),
(23,2),
(24,2),
(25,2),
(26,2),
(27,2),
(28,2),
(29,2),
(30,2),
(31,2),
(32,2),
(33,2),
(34,2),
(35,2),
(36,2),
(37,2),
(38,2),
(39,2),
(40,2),
(41,2),
(42,2),
(43,2),
(44,2),
(45,2),
(46,2),
(47,2),
(48,2),
(49,2),
(50,2),
(51,2),
(52,2),
(79,2),
(80,2),
(81,2),
(82,2),
(83,2),
(84,2),
(85,2),
(86,2),
(87,2),
(88,2),
(89,2),
(90,2),
(91,2),
(92,2),
(93,2),
(94,2),
(95,2),
(96,2),
(97,2),
(98,2),
(99,2),
(100,2),
(101,2),
(102,2),
(103,2),
(104,2),
(105,2),
(106,2),
(107,2),
(108,2),
(109,2),
(132,2),
(133,2),
(134,2),
(137,2),
(138,2),
(142,2),
(145,2),
(146,2),
(147,2),
(154,2),
(157,2),
(162,2),
(163,2),
(164,2),
(165,2),
(166,2),
(167,2),
(168,2),
(169,2),
(170,2),
(171,2),
(172,2),
(173,2),
(174,2),
(175,2),
(176,2),
(177,2),
(178,2),
(184,2),
(186,2),
(187,2),
(191,2),
(192,2),
(193,2),
(196,2),
(197,2),
(200,2),
(202,2),
(203,2),
(204,2),
(205,2),
(206,2),
(207,2),
(208,2),
(209,2),
(210,2),
(211,2),
(212,2),
(214,2),
(226,2),
(230,2),
(231,2),
(232,2),
(236,2),
(237,2),
(238,2),
(239,2),
(240,2),
(241,2),
(242,2),
(243,2),
(244,2),
(245,2),
(246,2),
(247,2),
(248,2),
(249,2),
(250,2),
(253,2),
(258,2),
(263,2),
(266,2),
(267,2),
(268,2),
(269,2),
(270,2),
(271,2),
(272,2),
(273,2),
(274,2),
(275,2),
(276,2),
(278,2),
(279,2),
(280,2),
(281,2),
(282,2),
(283,2),
(284,2),
(285,2),
(286,2),
(287,2),
(288,2),
(289,2),
(290,2),
(291,2),
(292,2),
(293,2),
(294,2),
(295,2),
(296,2),
(297,2),
(298,2),
(299,2),
(300,2),
(301,2),
(302,2),
(303,2),
(304,2),
(305,2),
(306,2),
(307,2),
(308,2),
(309,2),
(310,2),
(311,2),
(312,2),
(313,2),
(314,2),
(315,2),
(340,2),
(341,2),
(342,2),
(352,2),
(353,2),
(354,2),
(355,2),
(8,3),
(9,3),
(10,3),
(15,3),
(16,3),
(17,3),
(18,3),
(19,3),
(21,3),
(24,3),
(25,3),
(26,3),
(28,3),
(29,3),
(30,3),
(31,3),
(32,3),
(33,3),
(49,3),
(50,3),
(51,3),
(52,3),
(74,3),
(75,3),
(76,3),
(77,3),
(78,3),
(79,3),
(80,3),
(81,3),
(83,3),
(84,3),
(85,3),
(86,3),
(87,3),
(88,3),
(89,3),
(90,3),
(91,3),
(92,3),
(93,3),
(94,3),
(95,3),
(96,3),
(97,3),
(98,3),
(99,3),
(100,3),
(101,3),
(102,3),
(104,3),
(105,3),
(106,3),
(107,3),
(108,3),
(109,3),
(115,3),
(116,3),
(117,3),
(121,3),
(122,3),
(123,3),
(124,3),
(172,3),
(184,3),
(185,3),
(186,3),
(187,3),
(193,3),
(195,3),
(197,3),
(199,3),
(205,3),
(212,3),
(213,3),
(214,3),
(226,3),
(232,3),
(235,3),
(241,3),
(251,3),
(253,3),
(270,3),
(279,3),
(280,3),
(281,3),
(291,3),
(299,3),
(300,3),
(304,3),
(305,3),
(306,3),
(308,3),
(310,3),
(340,3),
(361,3),
(367,3),
(371,3),
(373,3),
(376,3),
(380,3),
(383,3),
(386,3),
(393,3),
(396,3),
(399,3),
(404,3),
(408,3),
(411,3),
(413,3),
(415,3),
(8,4),
(15,4),
(17,4),
(66,4),
(67,4),
(68,4),
(69,4),
(74,4),
(75,4),
(76,4),
(79,4),
(81,4),
(83,4),
(90,4),
(91,4),
(93,4),
(94,4),
(95,4),
(104,4),
(279,4),
(280,4),
(291,4),
(299,4),
(300,4),
(304,4),
(308,4),
(309,4),
(311,4),
(340,4),
(361,4),
(362,4),
(367,4),
(368,4),
(369,4),
(370,4),
(371,4),
(372,4),
(373,4),
(374,4),
(375,4),
(376,4),
(380,4),
(383,4),
(384,4),
(385,4),
(386,4),
(387,4),
(389,4),
(390,4),
(391,4),
(393,4),
(394,4),
(395,4),
(396,4),
(397,4),
(398,4),
(399,4),
(400,4),
(401,4),
(402,4),
(403,4),
(404,4),
(405,4),
(406,4),
(408,4),
(409,4),
(410,4),
(411,4),
(412,4),
(415,4),
(8,5),
(9,5),
(10,5),
(14,5),
(17,5),
(18,5),
(19,5),
(20,5),
(24,5),
(25,5),
(26,5),
(28,5),
(29,5),
(30,5),
(31,5),
(32,5),
(33,5),
(34,5),
(37,5),
(43,5),
(47,5),
(48,5),
(132,5),
(133,5),
(134,5),
(137,5),
(340,5),
(115,6),
(116,6),
(117,6),
(118,6),
(172,6),
(173,6),
(176,6),
(179,6),
(180,6),
(181,6),
(182,6),
(183,6),
(184,6),
(187,6),
(188,6),
(189,6),
(193,6),
(195,6),
(197,6),
(198,6),
(200,6),
(201,6),
(340,6),
(115,7),
(116,7),
(117,7),
(118,7),
(172,7),
(173,7),
(176,7),
(179,7),
(180,7),
(181,7),
(182,7),
(183,7),
(184,7),
(187,7),
(188,7),
(189,7),
(190,7),
(193,7),
(195,7),
(197,7),
(198,7),
(199,7),
(200,7),
(201,7),
(340,7),
(115,8),
(118,8),
(119,8),
(120,8),
(172,8),
(173,8),
(176,8),
(179,8),
(184,8),
(187,8),
(190,8),
(191,8),
(192,8),
(193,8),
(194,8),
(195,8),
(196,8),
(197,8),
(198,8),
(199,8),
(200,8),
(340,8),
(8,9),
(24,9),
(132,9),
(139,9),
(142,9),
(172,9),
(179,9),
(184,9),
(185,9),
(340,9),
(172,10),
(179,10),
(180,10),
(184,10),
(340,10),
(115,11),
(116,11),
(117,11),
(118,11),
(119,11),
(120,11),
(172,11),
(173,11),
(174,11),
(175,11),
(176,11),
(177,11),
(178,11),
(179,11),
(180,11),
(181,11),
(182,11),
(183,11),
(184,11),
(185,11),
(186,11),
(187,11),
(188,11),
(189,11),
(190,11),
(191,11),
(192,11),
(193,11),
(194,11),
(195,11),
(196,11),
(197,11),
(198,11),
(199,11),
(200,11),
(201,11),
(202,11),
(203,11),
(204,11),
(340,11),
(341,11),
(342,11),
(8,12),
(24,12),
(132,12),
(139,12),
(142,12),
(205,12),
(212,12),
(213,12),
(215,12),
(216,12),
(217,12),
(218,12),
(340,12),
(205,13),
(212,13),
(215,13),
(219,13),
(220,13),
(221,13),
(222,13),
(224,13),
(226,13),
(340,13),
(121,14),
(124,14),
(205,14),
(212,14),
(219,14),
(222,14),
(224,14),
(225,14),
(226,14),
(227,14),
(228,14),
(229,14),
(232,14),
(233,14),
(234,14),
(235,14),
(340,14),
(121,15),
(124,15),
(205,15),
(212,15),
(219,15),
(222,15),
(224,15),
(225,15),
(226,15),
(227,15),
(228,15),
(229,15),
(230,15),
(231,15),
(232,15),
(233,15),
(234,15),
(235,15),
(340,15),
(341,15),
(121,16),
(122,16),
(123,16),
(124,16),
(205,16),
(206,16),
(207,16),
(208,16),
(209,16),
(210,16),
(211,16),
(212,16),
(213,16),
(214,16),
(215,16),
(216,16),
(217,16),
(218,16),
(219,16),
(220,16),
(221,16),
(222,16),
(223,16),
(224,16),
(225,16),
(226,16),
(227,16),
(228,16),
(229,16),
(230,16),
(231,16),
(232,16),
(233,16),
(234,16),
(235,16),
(236,16),
(237,16),
(238,16),
(239,16),
(240,16),
(340,16),
(341,16),
(342,16),
(205,17),
(222,17),
(236,17),
(237,17),
(238,17),
(239,17),
(340,17),
(241,18),
(242,18),
(245,18),
(248,18),
(251,18),
(340,18),
(241,19),
(242,19),
(245,19),
(248,19),
(251,19),
(253,19),
(254,19),
(258,19),
(263,19),
(266,19),
(270,19),
(340,19),
(104,20),
(110,20),
(111,20),
(112,20),
(113,20),
(114,20),
(241,20),
(242,20),
(245,20),
(248,20),
(251,20),
(252,20),
(253,20),
(254,20),
(255,20),
(256,20),
(257,20),
(258,20),
(263,20),
(266,20),
(267,20),
(268,20),
(270,20),
(271,20),
(272,20),
(276,20),
(340,20),
(386,20),
(104,21),
(241,21),
(242,21),
(245,21),
(248,21),
(251,21),
(252,21),
(253,21),
(254,21),
(255,21),
(256,21),
(257,21),
(258,21),
(263,21),
(266,21),
(267,21),
(268,21),
(269,21),
(270,21),
(271,21),
(272,21),
(273,21),
(274,21),
(276,21),
(277,21),
(340,21),
(341,21),
(104,22),
(241,22),
(242,22),
(243,22),
(244,22),
(245,22),
(246,22),
(247,22),
(248,22),
(249,22),
(250,22),
(251,22),
(252,22),
(253,22),
(254,22),
(255,22),
(256,22),
(257,22),
(258,22),
(259,22),
(260,22),
(261,22),
(262,22),
(263,22),
(264,22),
(265,22),
(266,22),
(267,22),
(268,22),
(269,22),
(270,22),
(271,22),
(272,22),
(273,22),
(274,22),
(275,22),
(276,22),
(277,22),
(278,22),
(340,22),
(341,22),
(342,22),
(241,23),
(242,23),
(245,23),
(248,23),
(258,23),
(259,23),
(260,23),
(261,23),
(262,23),
(263,23),
(264,23),
(265,23),
(266,23),
(267,23),
(340,23),
(241,24),
(242,24),
(243,24),
(244,24),
(245,24),
(246,24),
(247,24),
(248,24),
(249,24),
(250,24),
(258,24),
(263,24),
(266,24),
(267,24),
(270,24),
(272,24),
(273,24),
(274,24),
(275,24),
(276,24),
(277,24),
(278,24),
(340,24),
(341,24),
(342,24),
(132,25),
(133,25),
(134,25),
(137,25),
(138,25),
(139,25),
(142,25),
(148,25),
(149,25),
(151,25),
(153,25),
(158,25),
(159,25),
(160,25),
(340,25),
(132,26),
(134,26),
(138,26),
(142,26),
(148,26),
(151,26),
(154,26),
(157,26),
(160,26),
(161,26),
(166,26),
(168,26),
(170,26),
(321,26),
(322,26),
(323,26),
(324,26),
(325,26),
(340,26),
(341,26),
(342,26),
(352,26),
(353,26),
(8,27),
(9,27),
(10,27),
(17,27),
(279,27),
(280,27),
(281,27),
(282,27),
(284,27),
(291,27),
(296,27),
(312,27),
(340,27),
(8,28),
(279,28),
(280,28),
(281,28),
(282,28),
(283,28),
(284,28),
(285,28),
(288,28),
(291,28),
(296,28),
(297,28),
(298,28),
(299,28),
(300,28),
(301,28),
(302,28),
(303,28),
(304,28),
(305,28),
(306,28),
(307,28),
(308,28),
(309,28),
(310,28),
(311,28),
(312,28),
(313,28),
(340,28),
(341,28),
(8,29),
(279,29),
(280,29),
(285,29),
(286,29),
(287,29),
(288,29),
(289,29),
(290,29),
(291,29),
(292,29),
(293,29),
(294,29),
(295,29),
(296,29),
(297,29),
(298,29),
(299,29),
(300,29),
(301,29),
(302,29),
(312,29),
(340,29),
(8,30),
(15,30),
(17,30),
(74,30),
(75,30),
(76,30),
(79,30),
(81,30),
(83,30),
(90,30),
(91,30),
(93,30),
(94,30),
(95,30),
(104,30),
(279,30),
(280,30),
(291,30),
(299,30),
(304,30),
(308,30),
(340,30),
(361,30),
(362,30),
(367,30),
(368,30),
(369,30),
(370,30),
(371,30),
(372,30),
(373,30),
(374,30),
(375,30),
(376,30),
(380,30),
(383,30),
(384,30),
(385,30),
(386,30),
(387,30),
(389,30),
(390,30),
(391,30),
(393,30),
(394,30),
(395,30),
(396,30),
(397,30),
(398,30),
(399,30),
(400,30),
(401,30),
(402,30),
(403,30),
(404,30),
(405,30),
(406,30),
(408,30),
(409,30),
(410,30),
(411,30),
(412,30),
(415,30),
(8,31),
(15,31),
(17,31),
(74,31),
(75,31),
(76,31),
(79,31),
(81,31),
(83,31),
(90,31),
(91,31),
(93,31),
(94,31),
(95,31),
(104,31),
(279,31),
(280,31),
(291,31),
(299,31),
(304,31),
(308,31),
(340,31),
(341,31),
(361,31),
(362,31),
(363,31),
(364,31),
(367,31),
(368,31),
(369,31),
(370,31),
(371,31),
(372,31),
(373,31),
(374,31),
(375,31),
(376,31),
(377,31),
(378,31),
(380,31),
(381,31),
(382,31),
(383,31),
(384,31),
(385,31),
(386,31),
(387,31),
(389,31),
(390,31),
(391,31),
(392,31),
(393,31),
(394,31),
(395,31),
(396,31),
(397,31),
(398,31),
(399,31),
(400,31),
(401,31),
(402,31),
(403,31),
(404,31),
(405,31),
(406,31),
(407,31),
(408,31),
(409,31),
(410,31),
(411,31),
(412,31),
(413,31),
(415,31),
(416,31),
(8,32),
(15,32),
(17,32),
(74,32),
(75,32),
(76,32),
(79,32),
(81,32),
(83,32),
(90,32),
(91,32),
(93,32),
(94,32),
(95,32),
(104,32),
(279,32),
(280,32),
(291,32),
(299,32),
(304,32),
(308,32),
(340,32),
(341,32),
(361,32),
(362,32),
(363,32),
(364,32),
(365,32),
(366,32),
(367,32),
(368,32),
(369,32),
(370,32),
(371,32),
(372,32),
(373,32),
(374,32),
(375,32),
(376,32),
(377,32),
(378,32),
(379,32),
(380,32),
(381,32),
(382,32),
(383,32),
(384,32),
(385,32),
(386,32),
(387,32),
(388,32),
(389,32),
(390,32),
(391,32),
(392,32),
(393,32),
(394,32),
(395,32),
(396,32),
(397,32),
(398,32),
(399,32),
(400,32),
(401,32),
(402,32),
(403,32),
(404,32),
(405,32),
(406,32),
(407,32),
(408,32),
(409,32),
(410,32),
(411,32),
(412,32),
(413,32),
(414,32),
(415,32),
(416,32),
(417,32),
(419,32),
(8,33),
(15,33),
(17,33),
(279,33),
(280,33),
(291,33),
(312,33),
(340,33),
(341,33),
(342,33),
(361,33),
(362,33),
(363,33),
(364,33),
(365,33),
(366,33),
(367,33),
(370,33),
(371,33),
(373,33),
(376,33),
(379,33),
(380,33),
(383,33),
(386,33),
(392,33),
(393,33),
(396,33),
(399,33),
(402,33),
(404,33),
(407,33),
(408,33),
(411,33),
(413,33),
(414,33),
(415,33),
(417,33),
(418,33),
(419,33),
(340,34),
(341,34),
(342,34),
(361,34),
(362,34),
(365,34),
(366,34),
(367,34),
(371,34),
(373,34),
(376,34),
(380,34),
(383,34),
(386,34),
(393,34),
(396,34),
(399,34),
(402,34),
(404,34),
(408,34),
(411,34),
(415,34),
(417,34),
(418,34),
(419,34),
(420,34);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `roles` VALUES
(1,'super_admin','web','2026-09-23 17:34:54','2026-09-23 17:34:54'),
(2,'hospital_admin','web','2026-09-23 17:34:55','2026-09-23 17:34:55'),
(3,'doctor','web','2026-09-23 17:34:55','2026-09-23 17:34:55'),
(4,'nurse','web','2026-09-23 17:34:55','2026-09-23 17:34:55'),
(5,'receptionist','web','2026-09-23 17:34:55','2026-09-23 17:34:55'),
(6,'lab_technician','web','2026-09-23 17:34:55','2026-09-23 17:34:55'),
(7,'senior_lab_technician','web','2026-09-23 17:34:55','2026-09-23 17:34:55'),
(8,'pathologist','web','2026-09-23 17:34:55','2026-09-23 17:34:55'),
(9,'lab_receptionist','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(10,'phlebotomist','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(11,'lab_manager','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(12,'radiology_receptionist','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(13,'radiology_technician','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(14,'radiologist','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(15,'senior_radiologist','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(16,'radiology_manager','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(17,'pacs_administrator','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(18,'pharmacy_receptionist','web','2026-09-23 17:34:56','2026-09-23 17:34:56'),
(19,'pharmacy_technician','web','2026-09-23 17:34:57','2026-09-23 17:34:57'),
(20,'pharmacist','web','2026-09-23 17:34:57','2026-09-23 17:34:57'),
(21,'senior_pharmacist','web','2026-09-23 17:34:57','2026-09-23 17:34:57'),
(22,'pharmacy_manager','web','2026-09-23 17:34:57','2026-09-23 17:34:57'),
(23,'storekeeper','web','2026-09-23 17:34:57','2026-09-23 17:34:57'),
(24,'pharmacy_administrator','web','2026-09-23 17:34:57','2026-09-23 17:34:57'),
(25,'cashier','web','2026-09-23 17:34:57','2026-09-23 17:34:57'),
(26,'accountant','web','2026-09-23 17:34:57','2026-09-23 17:34:57'),
(27,'admission_officer','web','2026-09-23 17:34:58','2026-09-23 17:34:58'),
(28,'ipd_coordinator','web','2026-09-23 17:34:58','2026-09-23 17:34:58'),
(29,'ward_manager','web','2026-09-23 17:34:58','2026-09-23 17:34:58'),
(30,'staff_nurse','web','2026-09-23 22:11:23','2026-09-23 22:11:23'),
(31,'senior_staff_nurse','web','2026-09-23 22:11:24','2026-09-23 22:11:24'),
(32,'charge_nurse','web','2026-09-23 22:11:24','2026-09-23 22:11:24'),
(33,'nursing_supervisor','web','2026-09-23 22:11:24','2026-09-23 22:11:24'),
(34,'nursing_administrator','web','2026-09-23 22:11:24','2026-09-23 22:11:24');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `security_events`
--

DROP TABLE IF EXISTS `security_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL DEFAULT 'info',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_id` varchar(255) DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_events_user_id_foreign` (`user_id`),
  KEY `security_events_company_id_foreign` (`company_id`),
  KEY `security_events_branch_id_foreign` (`branch_id`),
  KEY `security_events_event_created_at_index` (`event`,`created_at`),
  KEY `security_events_severity_index` (`severity`),
  KEY `security_events_request_id_index` (`request_id`),
  CONSTRAINT `security_events_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `security_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `security_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_events`
--

LOCK TABLES `security_events` WRITE;
/*!40000 ALTER TABLE `security_events` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `security_events` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `sessions` VALUES
('oB9iXxhWLMmevwgEERH6f1AKubPFTyAsDVgMqXdc',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:155.0) Gecko/20100101 Firefox/155.0','eyJfdG9rZW4iOiJhMVZUaEdRUnJqMFJWSTRlVnRXVmxySjR1ODVZMWxBdjM2RVY3aFE4IiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2hlYWx0aG5leHVzLnRlc3RcL2Rhc2hib2FyZCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJsb2dpbl9oaXN0b3J5X2lkIjoyLCJ0ZW5hbnRfY29tcGFueV9pZCI6MSwidGVuYW50X2JyYW5jaF9pZCI6Mn0=',1790224191);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_group_is_sensitive_index` (`group`,`is_sensitive`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `settings` VALUES
(1,'system','system.app_name','HealthNexus','string','Application name',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,'system','system.locale','en','string','Default application locale',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(3,'system','system.fallback_locale','en','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(4,'system','system.timezone','UTC','string','Default application timezone',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(5,'system','system.currency','USD','string','Default currency code',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(6,'system','system.date_format','Y-m-d','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(7,'system','system.time_format','H:i','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(8,'hospital','hospital.name','HealthNexus','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(9,'hospital','hospital.phone',NULL,'string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(10,'hospital','hospital.email',NULL,'string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(11,'hospital','hospital.address',NULL,'string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(12,'localization','localization.supported_locales','{\"en\":\"English\",\"bn\":\"Bangla\",\"ar\":\"Arabic\"}','json',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(13,'security','security.session_timeout','120','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(14,'security','security.max_login_attempts','5','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(15,'security','security.password_expiry_days','90','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(16,'security','security.password_min_length','8','integer','Minimum password length',1,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(17,'notifications','notifications.email_enabled','1','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(18,'notifications','notifications.sms_enabled','0','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(19,'notifications','notifications.whatsapp_enabled','0','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(20,'files','files.max_size_kb','2048','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(21,'files','files.allowed_extensions','[\"pdf\",\"jpg\",\"png\",\"docx\"]','json',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(22,'audit','audit.retention_days','365','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(23,'billing','billing.currency','BDT','string','Default billing currency',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(24,'billing','billing.invoice_prefix','INV','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(25,'billing','billing.payment_prefix','PMT','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(26,'billing','billing.receipt_prefix','RCT','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(27,'billing','billing.refund_prefix','RFD','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(28,'billing','billing.adjustment_prefix','ADJ','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(29,'billing','billing.invoice_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string','Document numbering format',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(30,'billing','billing.rounding_precision','2','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(31,'billing','billing.rounding_mode','nearest','string','nearest|up|down',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(32,'billing','billing.discount_approval_threshold_percent','10','integer','Discount % above which approval is required',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(33,'billing','billing.discount_approval_threshold_amount','5000','integer','Discount amount above which approval is required',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(34,'billing','billing.discount_approver_role','hospital_admin','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(35,'billing','billing.refund_approval_required','1','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(36,'billing','billing.default_price_list_priority','100','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(37,'billing','billing.tax_inclusive_default','0','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(38,'billing','billing.advance_min_balance','0','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(39,'laboratory','laboratory.order_prefix','LAB','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(40,'laboratory','laboratory.accession_prefix','ACC','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(41,'laboratory','laboratory.report_prefix','RPT','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(42,'laboratory','laboratory.order_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string','Document numbering format',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(43,'laboratory','laboratory.accession_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(44,'laboratory','laboratory.default_turnaround_minutes','60','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(45,'laboratory','laboratory.critical_notification_channels','[\"database\",\"mail\"]','json',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(46,'laboratory','laboratory.require_pathologist_approval_default','0','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(47,'radiology','radiology.order_prefix','RAD','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(48,'radiology','radiology.accession_prefix','RAD','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(49,'radiology','radiology.order_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(50,'radiology','radiology.accession_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(51,'radiology','radiology.default_turnaround_minutes','120','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(52,'radiology','radiology.critical_finding_notification_channels','[\"database\",\"mail\"]','json',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(53,'radiology','radiology.dicom_uid_root','1.2.826.0.1.3680043.10.001','string','Org root OID used only if the app ever pre-assigns a Study Instance UID',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(54,'radiology','radiology.default_pacs_server_id',NULL,'integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(55,'pharmacy','pharmacy.order_prefix','RX','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(56,'pharmacy','pharmacy.dispensing_prefix','DSP','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(57,'pharmacy','pharmacy.transfer_prefix','TRF','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(58,'pharmacy','pharmacy.return_prefix','RTN','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(59,'pharmacy','pharmacy.order_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(60,'pharmacy','pharmacy.near_expiry_threshold_days','90','integer','Batches expiring within this many days are flagged near-expiry',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(61,'pharmacy','pharmacy.allow_negative_stock','0','boolean','Reserved — no override path is wired in Phase 7; dispensing always refuses insufficient stock',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(62,'pharmacy','pharmacy.controlled_drug_witness_required','0','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(63,'pharmacy','pharmacy.substitution_requires_approval','1','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(64,'ipd','ipd.admission_prefix','ADM','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(65,'ipd','ipd.admission_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(66,'ipd','ipd.bed_reservation_expiry_minutes','120','integer','Minutes after which an unconverted bed reservation expires and the bed returns to Available',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(67,'ipd','ipd.discharge_requires_cleaning','1','boolean','When true, a released bed goes to Cleaning rather than directly to Available',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(68,'ipd','ipd.leave_default_bed_handling','retain','string','retain keeps the bed allocation active during patient leave; release frees the bed',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(69,'ipd','ipd.admission_requires_approval','1','boolean',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(70,'ipd','ipd.delayed_discharge_escalation_hours','24','integer',NULL,0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(71,'patients','patients.duplicate_weights','{\"name\":30,\"date_of_birth\":25,\"phone\":20,\"national_identifier\":20,\"email\":5}','json','Weighted duplicate-detection scoring per matched field',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(72,'patients','patients.amendment_sensitive_fields','[\"date_of_birth\",\"sex\",\"national_identifier\"]','json','Fields that require the amendment approval workflow instead of a direct edit',0,0,0,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(73,'nursing','nursing.mar_frequency_intervals','{\"OD\":24,\"BID\":12,\"TID\":8,\"QID\":6,\"Q4H\":4,\"Q6H\":6,\"Q8H\":8,\"Q12H\":12}','json','Hospital-configurable map of recognized frequency codes to hour intervals, used only to auto-generate the next scheduled MAR dose — unrecognized codes and all PRN items fall back to nurse-initiated scheduling',0,0,0,'2026-09-23 22:26:30','2026-09-23 22:26:30'),
(74,'nursing','nursing.vitals_default_frequency_minutes','240','integer','Default interval between routine vital-sign observations when no care plan/order specifies otherwise',0,0,0,'2026-09-23 22:26:30','2026-09-23 22:26:30'),
(75,'nursing','nursing.handover_requires_acknowledgement','1','boolean',NULL,0,0,0,'2026-09-23 22:26:30','2026-09-23 22:26:30'),
(76,'nursing','nursing.high_alert_requires_witness','1','boolean','Requires a second-nurse witness for administration of medications flagged is_high_alert in Phase 7',0,0,0,'2026-09-23 22:26:30','2026-09-23 22:26:30'),
(77,'nursing','nursing.controlled_requires_witness','1','boolean','Requires a second-nurse witness for administration of medications flagged is_controlled in Phase 7',0,0,0,'2026-09-23 22:26:30','2026-09-23 22:26:30'),
(78,'nursing','nursing.prn_reassessment_minutes','60','integer','Minutes after a PRN administration by which a reassessment/response should be documented',0,0,0,'2026-09-23 22:26:30','2026-09-23 22:26:30'),
(79,'nursing','nursing.escalation_default_recipient_role','charge_nurse','string',NULL,0,0,0,'2026-09-23 22:26:30','2026-09-23 22:26:30'),
(80,'nursing','nursing.observation_thresholds','{\"blood_glucose\":{\"low\":70,\"high\":200},\"pain\":{\"low\":null,\"high\":7}}','json','Hospital-configurable low/high alert thresholds per nursing_observations.observation_type — a breach raises a NursingAlert, never a diagnosis',0,0,0,'2026-09-23 22:26:30','2026-09-23 22:26:30');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `specialties`
--

DROP TABLE IF EXISTS `specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `specialties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `specialties_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `specialties_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `specialties`
--

LOCK TABLES `specialties` WRITE;
/*!40000 ALTER TABLE `specialties` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `specialties` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `states_country_id_code_unique` (`country_id`,`code`),
  KEY `states_country_id_name_index` (`country_id`,`name`),
  CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `states`
--

LOCK TABLES `states` WRITE;
/*!40000 ALTER TABLE `states` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `states` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_branches`
--

DROP TABLE IF EXISTS `user_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `access_level` enum('manager','staff') NOT NULL DEFAULT 'staff',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_branches_user_id_branch_id_unique` (`user_id`,`branch_id`),
  KEY `user_branches_company_id_foreign` (`company_id`),
  KEY `user_branches_user_id_company_id_index` (`user_id`,`company_id`),
  KEY `user_branches_branch_id_access_level_index` (`branch_id`,`access_level`),
  CONSTRAINT `user_branches_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_branches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_branches`
--

LOCK TABLES `user_branches` WRITE;
/*!40000 ALTER TABLE `user_branches` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_branches` VALUES
(1,1,1,1,'manager',1,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,1,1,2,'manager',0,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(3,2,1,1,'staff',0,NULL,'2026-09-23 18:54:29','2026-09-23 18:54:29');
/*!40000 ALTER TABLE `user_branches` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_companies`
--

DROP TABLE IF EXISTS `user_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `access_level` enum('owner','admin','staff') NOT NULL DEFAULT 'staff',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_companies_user_id_company_id_unique` (`user_id`,`company_id`),
  KEY `user_companies_company_id_access_level_index` (`company_id`,`access_level`),
  CONSTRAINT `user_companies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_companies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_companies`
--

LOCK TABLES `user_companies` WRITE;
/*!40000 ALTER TABLE `user_companies` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_companies` VALUES
(1,1,1,'admin',1,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,2,1,'staff',0,NULL,'2026-09-23 18:54:29','2026-09-23 18:54:29');
/*!40000 ALTER TABLE `user_companies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_departments`
--

DROP TABLE IF EXISTS `user_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `access_level` enum('head','staff') NOT NULL DEFAULT 'staff',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_departments_user_id_department_id_unique` (`user_id`,`department_id`),
  KEY `user_departments_company_id_foreign` (`company_id`),
  KEY `user_departments_branch_id_foreign` (`branch_id`),
  KEY `user_departments_user_id_company_id_index` (`user_id`,`company_id`),
  KEY `user_departments_department_id_access_level_index` (`department_id`,`access_level`),
  CONSTRAINT `user_departments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_departments_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_departments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_departments`
--

LOCK TABLES `user_departments` WRITE;
/*!40000 ALTER TABLE `user_departments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `user_departments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'UTC',
  `locale` varchar(255) NOT NULL DEFAULT 'en',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `failed_login_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `google2fa_secret` varchar(255) DEFAULT NULL,
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mfa_confirmed_at` timestamp NULL DEFAULT NULL,
  `mfa_recovery_codes` text DEFAULT NULL,
  `mfa_last_used_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'Admin User','admin@healthnexus.test',NULL,NULL,NULL,'UTC','en',1,NULL,NULL,NULL,'$2y$12$Xd8gB.vFl2YHQZzx4npz0OO3sYw6XR.u1JvVVCvw4R9RTM76ejArC',0,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-09-23 17:34:58','2026-09-23 17:34:58'),
(2,'Dr. Mohammad Rahman','doctor@hospital.com','01812222222',NULL,NULL,'+6','en',1,NULL,NULL,NULL,'$2y$12$puLW9NKmMSkV74VvFnGvg.YqOxSFXxOJJncJtDigpq2wn/P6IcDkS',0,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-09-23 18:54:29','2026-09-23 18:54:29');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `vital_signs`
--

DROP TABLE IF EXISTS `vital_signs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vital_signs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `recorded_at` datetime DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `temperature_unit` varchar(10) NOT NULL DEFAULT 'celsius',
  `systolic` int(11) DEFAULT NULL,
  `diastolic` int(11) DEFAULT NULL,
  `bp_unit` varchar(10) NOT NULL DEFAULT 'mmhg',
  `pulse_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `oxygen_saturation` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vital_signs_branch_id_foreign` (`branch_id`),
  KEY `vital_signs_appointment_id_foreign` (`appointment_id`),
  KEY `vital_signs_patient_id_foreign` (`patient_id`),
  KEY `vital_signs_recorded_by_foreign` (`recorded_by`),
  KEY `vital_signs_company_id_patient_id_index` (`company_id`,`patient_id`),
  KEY `vital_signs_company_id_appointment_id_index` (`company_id`,`appointment_id`),
  KEY `vital_signs_encounter_id_index` (`encounter_id`),
  CONSTRAINT `vital_signs_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vital_signs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vital_signs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vital_signs_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vital_signs_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vital_signs_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vital_signs`
--

LOCK TABLES `vital_signs` WRITE;
/*!40000 ALTER TABLE `vital_signs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `vital_signs` VALUES
(1,1,2,1,NULL,1,1,'2026-09-24 04:21:21',36.00,'celsius',NULL,NULL,'mmhg',89,NULL,172.00,87.00,NULL,'96',NULL,'2026-09-23 22:21:21','2026-09-23 22:21:21',NULL),
(2,1,2,1,NULL,1,1,'2026-09-24 04:24:03',NULL,'celsius',NULL,NULL,'mmhg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-23 22:24:03','2026-09-23 22:24:03',NULL),
(3,1,2,1,NULL,1,1,'2026-09-24 04:24:10',NULL,'celsius',NULL,NULL,'mmhg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-23 22:24:10','2026-09-23 22:24:10',NULL);
/*!40000 ALTER TABLE `vital_signs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflow_actions`
--

DROP TABLE IF EXISTS `workflow_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_instance_id` bigint(20) unsigned NOT NULL,
  `workflow_step_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `performed_by` bigint(20) unsigned NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_actions_workflow_step_id_foreign` (`workflow_step_id`),
  KEY `workflow_actions_performed_by_foreign` (`performed_by`),
  KEY `workflow_actions_workflow_instance_id_created_at_index` (`workflow_instance_id`,`created_at`),
  CONSTRAINT `workflow_actions_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_actions_workflow_instance_id_foreign` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_actions_workflow_step_id_foreign` FOREIGN KEY (`workflow_step_id`) REFERENCES `workflow_steps` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_actions`
--

LOCK TABLES `workflow_actions` WRITE;
/*!40000 ALTER TABLE `workflow_actions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `workflow_actions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflow_approvers`
--

DROP TABLE IF EXISTS `workflow_approvers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_approvers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_step_id` bigint(20) unsigned NOT NULL,
  `approver_type` varchar(255) NOT NULL,
  `role_name` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_approvers_user_id_foreign` (`user_id`),
  KEY `workflow_approvers_department_id_foreign` (`department_id`),
  KEY `workflow_approvers_workflow_step_id_approver_type_index` (`workflow_step_id`,`approver_type`),
  CONSTRAINT `workflow_approvers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_approvers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_approvers_workflow_step_id_foreign` FOREIGN KEY (`workflow_step_id`) REFERENCES `workflow_steps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_approvers`
--

LOCK TABLES `workflow_approvers` WRITE;
/*!40000 ALTER TABLE `workflow_approvers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `workflow_approvers` VALUES
(1,1,'role','hospital_admin',NULL,NULL,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(2,2,'role','super_admin',NULL,NULL,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(3,3,'role','hospital_admin',NULL,NULL,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(4,4,'role','ipd_coordinator',NULL,NULL,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(5,4,'role','hospital_admin',NULL,NULL,'2026-09-23 17:34:59','2026-09-23 17:34:59');
/*!40000 ALTER TABLE `workflow_approvers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflow_instances`
--

DROP TABLE IF EXISTS `workflow_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_instances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `subject_type` varchar(255) NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `current_step_id` bigint(20) unsigned DEFAULT NULL,
  `initiated_by` bigint(20) unsigned NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_instances_workflow_id_foreign` (`workflow_id`),
  KEY `workflow_instances_branch_id_foreign` (`branch_id`),
  KEY `workflow_instances_current_step_id_foreign` (`current_step_id`),
  KEY `workflow_instances_initiated_by_foreign` (`initiated_by`),
  KEY `workflow_instances_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `workflow_instances_company_id_status_index` (`company_id`,`status`),
  KEY `workflow_instances_status_index` (`status`),
  CONSTRAINT `workflow_instances_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_instances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_instances_current_step_id_foreign` FOREIGN KEY (`current_step_id`) REFERENCES `workflow_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_instances_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_instances_workflow_id_foreign` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_instances`
--

LOCK TABLES `workflow_instances` WRITE;
/*!40000 ALTER TABLE `workflow_instances` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `workflow_instances` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflow_steps`
--

DROP TABLE IF EXISTS `workflow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` bigint(20) unsigned NOT NULL,
  `step_order` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_steps_workflow_id_step_order_unique` (`workflow_id`,`step_order`),
  CONSTRAINT `workflow_steps_workflow_id_foreign` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_steps`
--

LOCK TABLES `workflow_steps` WRITE;
/*!40000 ALTER TABLE `workflow_steps` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `workflow_steps` VALUES
(1,1,1,'Supervisor Review',0,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(2,1,2,'Final Approval',1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(3,2,1,'Admin Approval',1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(4,3,1,'Admission Approval',1,'2026-09-23 17:34:59','2026-09-23 17:34:59');
/*!40000 ALTER TABLE `workflow_steps` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `workflows`
--

DROP TABLE IF EXISTS `workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflows_company_id_code_unique` (`company_id`,`code`),
  CONSTRAINT `workflows_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflows`
--

LOCK TABLES `workflows` WRITE;
/*!40000 ALTER TABLE `workflows` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `workflows` VALUES
(1,1,'generic_approval','Generic Approval','Two-step reference approval workflow: supervisor review, then final sign-off.',1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(2,1,'patient_amendment','Patient Record Amendment','Approval required to correct sensitive patient identity fields.',1,'2026-09-23 17:34:59','2026-09-23 17:34:59'),
(3,1,'ipd_admission','IPD Admission Approval','Approval required before an admission request becomes an active admission.',1,'2026-09-23 17:34:59','2026-09-23 17:34:59');
/*!40000 ALTER TABLE `workflows` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-24 10:41:24
