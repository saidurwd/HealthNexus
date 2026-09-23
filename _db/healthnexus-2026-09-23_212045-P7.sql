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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_number_counters`
--

LOCK TABLES `appointment_number_counters` WRITE;
/*!40000 ALTER TABLE `appointment_number_counters` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_tokens`
--

LOCK TABLES `appointment_tokens` WRITE;
/*!40000 ALTER TABLE `appointment_tokens` DISABLE KEYS */;
set autocommit=0;
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
(1,NULL,'new_consultation','New Consultation',NULL,0,0,1,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(2,NULL,'follow_up','Follow-up',NULL,1,0,1,2,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(3,NULL,'review','Review',NULL,0,0,1,3,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(4,NULL,'second_opinion','Second Opinion',NULL,0,0,1,4,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(5,NULL,'procedure','Procedure',NULL,0,0,1,5,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(6,NULL,'health_checkup','Health Checkup',NULL,0,0,1,6,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(7,NULL,'referral','Referral',NULL,0,0,1,7,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(8,NULL,'telemedicine','Telemedicine',NULL,0,1,1,8,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(9,NULL,'vaccination','Vaccination',NULL,0,0,1,9,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(10,NULL,'diagnostic','Diagnostic',NULL,0,0,1,10,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(11,NULL,'pre_operative','Pre-operative',NULL,0,0,1,11,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(12,NULL,'post_operative','Post-operative',NULL,0,0,1,12,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(13,NULL,'corporate','Corporate',NULL,0,0,1,13,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(14,NULL,'package','Package',NULL,0,0,1,14,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(15,NULL,'other','Other',NULL,0,0,1,15,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_categories`
--

LOCK TABLES `billing_categories` WRITE;
/*!40000 ALTER TABLE `billing_categories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `billing_categories` VALUES
(1,1,NULL,'Consultation','CONSULT','Consultation',1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,'Procedures','PROC','Procedures',1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,'Diagnostics','DIAG','Diagnostics',1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(4,1,NULL,'Room & Nursing','ROOM','Room & Nursing',1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(5,1,NULL,'Pharmacy','PHARM','Dispensed medications',1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_items`
--

LOCK TABLES `billing_items` WRITE;
/*!40000 ALTER TABLE `billing_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `billing_items` VALUES
(1,1,NULL,1,1,'OPD-CONSULT','consultation','OPD Consultation Fee','OPD Consultation Fee','visit',500.00,0,1,'encounter','completed',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,1,1,'FOLLOWUP-CONSULT','consultation','Follow-up Consultation','Follow-up Consultation','visit',300.00,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,1,1,'REGISTRATION','service','Registration Fee','Registration Fee','visit',100.00,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(4,1,NULL,2,1,'PROC-GENERAL','procedure','General Procedure','General Procedure','visit',1000.00,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(5,1,NULL,4,1,'ROOM-GENERAL','room','General Room Charge (per day)','General Room Charge (per day)','visit',1500.00,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(6,1,NULL,3,NULL,'LAB-HGB','diagnostic','Hemoglobin','Hemoglobin','test',300.00,0,1,'lab_test','HGB',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(7,1,NULL,3,NULL,'LAB-RBC','diagnostic','RBC Count','RBC Count','test',250.00,0,1,'lab_test','RBC',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(8,1,NULL,3,NULL,'LAB-WBC','diagnostic','WBC Count','WBC Count','test',250.00,0,1,'lab_test','WBC',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(9,1,NULL,3,NULL,'LAB-PLT','diagnostic','Platelet Count','Platelet Count','test',250.00,0,1,'lab_test','PLT',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(10,1,NULL,3,NULL,'LAB-HCT','diagnostic','Hematocrit','Hematocrit','test',250.00,0,1,'lab_test','HCT',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(11,1,NULL,3,NULL,'LAB-MCV','diagnostic','MCV','MCV','test',250.00,0,1,'lab_test','MCV',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(12,1,NULL,3,NULL,'LAB-FBS','diagnostic','Fasting Blood Glucose','Fasting Blood Glucose','test',200.00,0,1,'lab_test','FBS',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(13,1,NULL,3,NULL,'RAD-XR-CHEST-PA','diagnostic','Chest X-Ray PA','Chest X-Ray PA','procedure',400.00,0,1,'radiology_procedure','XR-CHEST-PA',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(14,1,NULL,3,NULL,'RAD-CT-BRAIN','diagnostic','CT Brain','CT Brain','procedure',4500.00,0,1,'radiology_procedure','CT-BRAIN',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(15,1,NULL,3,NULL,'RAD-US-ABDOMEN','diagnostic','Ultrasound Abdomen','Ultrasound Abdomen','procedure',1200.00,0,1,'radiology_procedure','US-ABDOMEN',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(16,1,NULL,5,NULL,'PH-NAPA-500-TAB','medication','Napa 500mg Tablet','Napa 500mg Tablet','tablet',5.00,0,1,'pharmacy_medication','NAPA-500-TAB',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(17,1,NULL,5,NULL,'PH-AMOX-500-CAP','medication','Amoxicillin 500mg Capsule','Amoxicillin 500mg Capsule','tablet',8.00,0,1,'pharmacy_medication','AMOX-500-CAP',1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(1,1,NULL,'Cash','CASH','cash',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,NULL,'Card','CARD','card',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(3,1,NULL,'Mobile Financial Service','MFS','mobile_financial_service',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(4,1,NULL,'Bank Transfer','BANK','bank_transfer',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,1,NULL,NULL,NULL,NULL,NULL,'5ed18d7e2a27c629a074f180807442d121bbe8e7',500.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,2,NULL,NULL,NULL,NULL,NULL,'34159bdd8ce21e258d3ffd11cf15cf431f44f1fe',300.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(3,1,3,NULL,NULL,NULL,NULL,NULL,'500f842f41f18f8bd4bc97347806c12da159b545',100.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(4,1,4,NULL,NULL,NULL,NULL,NULL,'201d825c8587de3c2bb954bddf4c6f6282000de5',1000.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(5,1,5,NULL,NULL,NULL,NULL,NULL,'2c0391dbe200b876409a62cf3277afac25ec93ac',1500.00,NULL,NULL,'none',0.00,0,100,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,NULL,'Default Price List','DEFAULT','BDT','active',100,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,NULL,'VAT','VAT',0.0000,0,1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,'Main Hospital','MAIN','main-hospital','main@healthnexus.test','+1-555-0101','123 Healthcare Blvd, Medical District',NULL,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(2,1,'City Center Clinic','CITY','city-center-clinic','city@healthnexus.test','+1-555-0102','456 Downtown Ave, City Center',NULL,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_number_counters`
--

LOCK TABLES `clinical_number_counters` WRITE;
/*!40000 ALTER TABLE `clinical_number_counters` DISABLE KEYS */;
set autocommit=0;
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
(1,'HealthNexus Hospital Group','HN-HG','healthnexus-hospital-group','info@healthnexus.test','+1-555-0100','123 Healthcare Blvd, Medical District',NULL,NULL,1,NULL,NULL,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
(1,'Bangladesh','BD','BDT','৳','+880','Asia/Dhaka','d/m/Y','H:i',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(2,'United States','US','USD','$','+1','America/New_York','m/d/Y','h:i A',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(3,'United Kingdom','GB','GBP','£','+44','Europe/London','d/m/Y','H:i',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(4,'India','IN','INR','₹','+91','Asia/Kolkata','d/m/Y','H:i',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(5,'Canada','CA','CAD','C$','+1','America/Toronto','Y-m-d','H:i',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
(1,'Bangladeshi Taka','BDT','৳','2',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(2,'US Dollar','USD','$','2',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(3,'British Pound','GBP','£','2',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(4,'Indian Rupee','INR','₹','2',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(5,'Canadian Dollar','CAD','C$','2',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(6,'Euro','EUR','€','2',1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(1,1,'New Consultation','new_consultation',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,'Follow-up','follow_up',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(3,1,'Review','review',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(4,1,'Second Opinion','second_opinion',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(5,1,'Procedure','procedure',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(6,1,'Health Checkup','health_checkup',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(7,1,'Referral','referral',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(8,1,'Telemedicine','telemedicine',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(9,1,'Walk-in','walk_in',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(10,1,'Other','other',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,'male','Male',1,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(2,'female','Female',1,2,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(3,'other','Other',1,3,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(4,'unknown','Unknown',1,4,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
(1,'National ID','NID','National Identity Card','Government',1,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(2,'Passport','PASSPORT','International Passport','Government',0,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(3,'Birth Certificate','BIRTH_CERT','Birth Certificate','Local Government',0,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(4,'Driving License','DL','Driver License','Transport Authority',0,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(5,'Voter ID','VOTER_ID','Voter Identity Card','Election Commission',0,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investigation_orders`
--

LOCK TABLES `investigation_orders` WRITE;
/*!40000 ALTER TABLE `investigation_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `investigation_orders` ENABLE KEYS */;
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
(1,1,NULL,'EDTA','EDTA Tube',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,'PLAIN','Plain Tube',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,'FLUORIDE','Fluoride Tube',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(1,7,40.0000,400.0000,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,1,0,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,2,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(3,1,3,2,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(4,1,4,3,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(5,1,5,4,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(6,1,6,5,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,NULL,'CBC','Complete Blood Count',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(1,1,NULL,'male',18,NULL,'any','g/dL',13.0000,17.0000,NULL,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,NULL,'female',18,NULL,'any','g/dL',12.0000,15.0000,NULL,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(3,7,NULL,'any',NULL,NULL,'any','mg/dL',70.0000,100.0000,NULL,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,NULL,NULL,'HEMA','Hematology',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,NULL,'BIOCHEM','Biochemistry',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,NULL,'MICRO','Microbiology',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(1,1,NULL,'BLOOD','Blood',NULL,NULL,NULL,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,'SERUM','Serum',NULL,NULL,NULL,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,'URINE','Urine',NULL,NULL,NULL,NULL,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(1,1,NULL,'HEMA','Hematology',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,'CHEM','Clinical Chemistry',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(1,1,NULL,1,1,1,1,'HGB','Hemoglobin',NULL,NULL,'quantitative',NULL,'g/dL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,1,1,1,1,'RBC','RBC Count',NULL,NULL,'quantitative',NULL,'x10^6/uL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,1,1,1,1,'WBC','WBC Count',NULL,NULL,'quantitative',NULL,'x10^3/uL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(4,1,NULL,1,1,1,1,'PLT','Platelet Count',NULL,NULL,'quantitative',NULL,'x10^3/uL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(5,1,NULL,1,1,1,1,'HCT','Hematocrit',NULL,NULL,'quantitative',NULL,'%',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(6,1,NULL,1,1,1,1,'MCV','MCV',NULL,NULL,'quantitative',NULL,'fL',0,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(7,1,NULL,2,2,2,3,'FBS','Fasting Blood Glucose',NULL,NULL,'quantitative',NULL,'mg/dL',1,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_histories`
--

LOCK TABLES `login_histories` WRITE;
/*!40000 ALTER TABLE `login_histories` DISABLE KEYS */;
set autocommit=0;
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
(1,'single','Single',1,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(2,'married','Married',1,2,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(3,'divorced','Divorced',1,3,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(4,'widowed','Widowed',1,4,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(5,'separated','Separated',1,5,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(6,'unknown','Unknown',1,6,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(192,'2026_09_27_000026_create_pharmacy_recalls_table',1);
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
(323,'App\\Models\\User',1);
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
(1,'App\\Models\\User',1);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_branch_registrations`
--

LOCK TABLES `patient_branch_registrations` WRITE;
/*!40000 ALTER TABLE `patient_branch_registrations` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_contacts`
--

LOCK TABLES `patient_contacts` WRITE;
/*!40000 ALTER TABLE `patient_contacts` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_number_counters`
--

LOCK TABLES `patient_number_counters` WRITE;
/*!40000 ALTER TABLE `patient_number_counters` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_timeline_events`
--

LOCK TABLES `patient_timeline_events` WRITE;
/*!40000 ALTER TABLE `patient_timeline_events` DISABLE KEYS */;
set autocommit=0;
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
(1,'general','General',1,1,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(2,'vip','VIP',1,2,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(3,'corporate','Corporate',1,3,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(4,'staff','Staff',1,4,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL),
(5,'insurance','Insurance',1,5,'2026-09-23 09:09:43','2026-09-23 09:09:43',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB AUTO_INCREMENT=324 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `permissions` VALUES
(1,'manage companies','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(2,'manage branches','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(3,'manage departments','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(4,'manage users','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(5,'manage roles','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(6,'manage permissions','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(7,'manage patients','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(8,'patients.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(9,'patients.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(10,'patients.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(11,'patients.delete','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(12,'patients.merge','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(13,'patients.export','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(14,'patients.print','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(15,'patients.alert.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(16,'patients.alert.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(17,'patients.documents.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(18,'patients.documents.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(19,'patients.consents.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(20,'patients.consents.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(21,'patients.amend.request','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(22,'patients.amend.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(23,'patients.portal.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(24,'appointments.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(25,'appointments.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(26,'appointments.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(27,'appointments.delete','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(28,'appointments.confirm','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(29,'appointments.checkin','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(30,'appointments.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(31,'appointments.reschedule','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(32,'appointments.no_show','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(33,'appointments.queue','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(34,'appointments.token','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(35,'appointments.override','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(36,'appointments.export','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(37,'appointments.print','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(38,'appointments.manage_schedule','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(39,'appointments.manage_provider','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(40,'appointments.manage_holiday','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(41,'appointments.manage_block','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(42,'appointments.manage_overbooking','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(43,'schedules.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(44,'schedules.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(45,'schedules.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(46,'schedules.delete','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(47,'queue.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(48,'queue.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(49,'opd.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(50,'opd.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(51,'opd.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(52,'opd.consult','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(53,'emergency.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(54,'emergency.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(55,'emergency.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(56,'emergency.triage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(57,'ipd.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(58,'ipd.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(59,'ipd.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(60,'ipd.admit','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(61,'ipd.discharge','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(62,'bed.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(63,'bed.allocate','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(64,'bed.transfer','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(65,'bed.block','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(66,'nursing.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(67,'nursing.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(68,'nursing.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(69,'nursing.administer','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(70,'doctor.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(71,'doctor.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(72,'doctor.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(73,'doctor.consult','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(74,'emr.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(75,'emr.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(76,'emr.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(77,'emr.amend','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(78,'emr.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(79,'encounters.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(80,'encounters.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(81,'encounters.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(82,'encounters.delete','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(83,'encounter.start','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(84,'encounter.complete','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(85,'encounter.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(86,'encounter.amend','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(87,'encounter.lock','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(88,'encounter.export','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(89,'encounter.print','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(90,'clinical.note.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(91,'clinical.note.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(92,'clinical.note.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(93,'clinical.vitals.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(94,'clinical.vitals.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(95,'clinical.diagnosis.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(96,'clinical.diagnosis.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(97,'clinical.diagnosis.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(98,'clinical.order.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(99,'clinical.order.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(100,'clinical.order.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(101,'clinical.referral.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(102,'clinical.referral.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(103,'clinical.break_glass','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(104,'prescription.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(105,'prescription.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(106,'prescription.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(107,'prescription.issue','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(108,'prescription.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(109,'prescription.amend','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(110,'pharmacy.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(111,'pharmacy.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(112,'pharmacy.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(113,'pharmacy.dispense','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(114,'pharmacy.adjust','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(115,'laboratory.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(116,'laboratory.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(117,'laboratory.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(118,'laboratory.verify','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(119,'laboratory.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(120,'laboratory.release','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(121,'radiology.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(122,'radiology.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(123,'radiology.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(124,'radiology.report','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(125,'ot.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(126,'ot.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(127,'ot.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(128,'ot.schedule','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(129,'icu.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(130,'icu.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(131,'icu.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(132,'billing.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(133,'billing.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(134,'billing.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(135,'billing.discount','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(136,'billing.refund','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(137,'billing.payment','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(138,'billing.dashboard.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(139,'billing.charge.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(140,'billing.charge.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(141,'billing.charge.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(142,'billing.invoice.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(143,'billing.invoice.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(144,'billing.invoice.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(145,'billing.invoice.finalize','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(146,'billing.invoice.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(147,'billing.invoice.writeoff','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(148,'billing.payment.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(149,'billing.payment.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(150,'billing.payment.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(151,'billing.receipt.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(152,'billing.receipt.void','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(153,'billing.refund.request','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(154,'billing.refund.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(155,'billing.refund.process','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(156,'billing.adjustment.request','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(157,'billing.adjustment.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(158,'billing.cashier.open','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(159,'billing.cashier.close','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(160,'billing.cashier.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(161,'billing.cashier.reconcile','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(162,'billing.pricing.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(163,'billing.pricing.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(164,'billing.category.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(165,'billing.item.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(166,'billing.corporate.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(167,'billing.corporate.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(168,'billing.insurance.policy.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(169,'billing.insurance.policy.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(170,'billing.report.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(171,'billing.settings.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(172,'lab.dashboard.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(173,'lab.test.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(174,'lab.test.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(175,'lab.test.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(176,'lab.panel.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(177,'lab.panel.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(178,'lab.panel.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(179,'lab.specimen.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(180,'lab.specimen.collect','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(181,'lab.specimen.receive','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(182,'lab.specimen.reject','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(183,'lab.specimen.process','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(184,'lab.order.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(185,'lab.order.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(186,'lab.order.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(187,'lab.result.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(188,'lab.result.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(189,'lab.result.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(190,'lab.result.validate','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(191,'lab.result.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(192,'lab.result.amend','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(193,'lab.report.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(194,'lab.report.generate','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(195,'lab.report.print','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(196,'lab.report.export','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(197,'lab.critical_result.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(198,'lab.critical_result.notify','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(199,'lab.critical_result.acknowledge','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(200,'lab.qc.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(201,'lab.qc.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(202,'lab.analyzer.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(203,'lab.analyzer.configure','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(204,'lab.settings.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(205,'radiology.dashboard.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(206,'radiology.procedure.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(207,'radiology.procedure.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(208,'radiology.procedure.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(209,'radiology.modality.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(210,'radiology.modality.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(211,'radiology.modality.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(212,'radiology.order.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(213,'radiology.order.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(214,'radiology.order.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(215,'radiology.schedule.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(216,'radiology.schedule.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(217,'radiology.schedule.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(218,'radiology.schedule.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(219,'radiology.examination.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(220,'radiology.examination.start','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(221,'radiology.examination.complete','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(222,'radiology.study.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(223,'radiology.study.export','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(224,'radiology.worklist.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(225,'radiology.worklist.assign','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(226,'radiology.report.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(227,'radiology.report.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(228,'radiology.report.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(229,'radiology.report.submit','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(230,'radiology.report.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(231,'radiology.report.amend','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(232,'radiology.critical_finding.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(233,'radiology.critical_finding.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(234,'radiology.critical_finding.notify','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(235,'radiology.critical_finding.acknowledge','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(236,'radiology.pacs.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(237,'radiology.pacs.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(238,'radiology.dicom.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(239,'radiology.dicom.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(240,'radiology.settings.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(241,'pharmacy.dashboard.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(242,'pharmacy.medication.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(243,'pharmacy.medication.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(244,'pharmacy.medication.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(245,'pharmacy.generic.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(246,'pharmacy.generic.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(247,'pharmacy.generic.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(248,'pharmacy.brand.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(249,'pharmacy.brand.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(250,'pharmacy.brand.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(251,'pharmacy.prescription.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(252,'pharmacy.prescription.review','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(253,'pharmacy.dispensing.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(254,'pharmacy.dispensing.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(255,'pharmacy.dispensing.verify','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(256,'pharmacy.dispensing.cancel','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(257,'pharmacy.dispensing.return','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(258,'pharmacy.stock.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(259,'pharmacy.stock.receive','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(260,'pharmacy.stock.transfer','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(261,'pharmacy.stock.adjust','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(262,'pharmacy.stock.count','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(263,'pharmacy.batch.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(264,'pharmacy.batch.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(265,'pharmacy.batch.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(266,'pharmacy.expiry.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(267,'pharmacy.quarantine.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(268,'pharmacy.substitution.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(269,'pharmacy.substitution.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(270,'pharmacy.safety_alert.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(271,'pharmacy.safety_alert.override','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(272,'pharmacy.controlled_drug.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(273,'pharmacy.controlled_drug.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(274,'pharmacy.recall.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(275,'pharmacy.recall.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(276,'pharmacy.reports.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(277,'pharmacy.reports.export','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(278,'pharmacy.settings.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(279,'insurance.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(280,'insurance.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(281,'insurance.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(282,'insurance.submit','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(283,'insurance.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(284,'finance.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(285,'finance.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(286,'finance.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(287,'finance.post','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(288,'finance.close','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(289,'inventory.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(290,'inventory.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(291,'inventory.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(292,'inventory.adjust','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(293,'inventory.transfer','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(294,'procurement.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(295,'procurement.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(296,'procurement.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(297,'procurement.approve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(298,'hr.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(299,'hr.create','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(300,'hr.update','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(301,'hr.attendance','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(302,'hr.payroll','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(303,'reporting.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(304,'reporting.export','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(305,'reporting.print','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(306,'audit.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(307,'activity.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(308,'activity.export','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(309,'security.event.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(310,'security.event.resolve','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(311,'login.history.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(312,'system.health.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(313,'system.queue.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(314,'system.scheduler.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(315,'workflow.view','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(316,'workflow.act','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(317,'workflow.manage','web','2026-09-23 09:09:38','2026-09-23 09:09:38'),
(318,'settings.view','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(319,'settings.update','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(320,'notification.view','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(321,'notification.manage','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(322,'file.view','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(323,'file.manage','web','2026-09-23 09:09:39','2026-09-23 09:09:39');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_batches`
--

LOCK TABLES `pharmacy_batches` WRITE;
/*!40000 ALTER TABLE `pharmacy_batches` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_batches` VALUES
(1,1,NULL,1,'SEED-NAPA-500-TAB',NULL,'2026-06-23','2027-09-23',2.00,5.00,NULL,0,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,NULL,2,'SEED-AMOX-500-CAP',NULL,'2026-06-23','2027-09-23',2.00,5.00,NULL,0,1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,NULL,1,'NAPA','Napa','Beximco Pharmaceuticals',1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(1,1,NULL,'TAB','Tablet',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,'CAP','Capsule',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,'SYR','Syrup',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(4,1,NULL,'INJ','Injection',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_generics`
--

LOCK TABLES `pharmacy_generics` WRITE;
/*!40000 ALTER TABLE `pharmacy_generics` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_generics` VALUES
(1,1,NULL,'PARA','Paracetamol',NULL,NULL,'Analgesic/Antipyretic',NULL,1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,'AMOX','Amoxicillin',NULL,NULL,'Penicillin antibiotic',NULL,1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_medications`
--

LOCK TABLES `pharmacy_medications` WRITE;
/*!40000 ALTER TABLE `pharmacy_medications` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_medications` VALUES
(1,1,NULL,1,1,1,1,'NAPA-500-TAB','Napa 500mg Tablet','500','mg',NULL,'tablet','tablet',NULL,1,0,0,NULL,NULL,0,1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,2,NULL,2,1,'AMOX-500-CAP','Amoxicillin 500mg Capsule','500','mg',NULL,'tablet','tablet',NULL,1,0,0,NULL,NULL,0,1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(1,1,NULL,'ORAL','Oral',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,'IV','Intravenous',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,'IM','Intramuscular',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(4,1,NULL,'TOP','Topical',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_stock`
--

LOCK TABLES `pharmacy_stock` WRITE;
/*!40000 ALTER TABLE `pharmacy_stock` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_stock` VALUES
(1,1,1,1,490,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,2,2,500,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,1,1,'opening','in',500,500,NULL,NULL,1,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,2,2,'opening','in',500,500,NULL,NULL,1,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_stores`
--

LOCK TABLES `pharmacy_stores` WRITE;
/*!40000 ALTER TABLE `pharmacy_stores` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pharmacy_stores` VALUES
(1,1,NULL,NULL,'PH-MAIN','Main Pharmacy Store','main',1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescription_items`
--

LOCK TABLES `prescription_items` WRITE;
/*!40000 ALTER TABLE `prescription_items` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
set autocommit=0;
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
(1,1,NULL,'HEAD','Head',0,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,'BRAIN','Brain',0,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,'CHEST','Chest',0,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(4,1,NULL,'ABDOMEN','Abdomen',0,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(5,1,NULL,'SPINE','Spine',0,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(6,1,NULL,'SHOULDER','Shoulder',1,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(7,1,NULL,'KNEE','Knee',1,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(8,1,NULL,'HAND','Hand',1,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(1,1,NULL,'DEFAULT','Default PACS (not connected)','null',NULL,NULL,NULL,NULL,NULL,0,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(1,1,NULL,1,3,'XR-CHEST-PA','Chest X-Ray PA','XR',NULL,10,60,0,0,0,NULL,0,1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,2,2,'CT-BRAIN','CT Brain','CT',NULL,20,120,0,0,0,NULL,0,1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,4,4,'US-ABDOMEN','Ultrasound Abdomen','US',NULL,20,60,0,1,0,'Fasting for 6 hours prior to the scan.',0,1,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(1,2,'NON-CONTRAST','Non-Contrast',NULL,0,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,2,'CONTRAST','Contrast',NULL,1,NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
(1,1,NULL,NULL,'XR','X-Ray',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(2,1,NULL,NULL,'CT','CT',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(3,1,NULL,NULL,'MRI','MRI',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL),
(4,1,NULL,NULL,'US','Ultrasound',NULL,1,'2026-09-23 09:09:44','2026-09-23 09:09:44',NULL);
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
(303,2),
(304,2),
(305,2),
(315,2),
(316,2),
(317,2),
(318,2),
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
(303,3),
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
(303,4),
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
(303,5),
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
(303,6),
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
(303,7),
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
(303,8),
(8,9),
(24,9),
(132,9),
(139,9),
(142,9),
(172,9),
(179,9),
(184,9),
(185,9),
(303,9),
(172,10),
(179,10),
(180,10),
(184,10),
(303,10),
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
(303,11),
(304,11),
(305,11),
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
(303,12),
(205,13),
(212,13),
(215,13),
(219,13),
(220,13),
(221,13),
(222,13),
(224,13),
(226,13),
(303,13),
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
(303,14),
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
(303,15),
(304,15),
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
(303,16),
(304,16),
(305,16),
(205,17),
(222,17),
(236,17),
(237,17),
(238,17),
(239,17),
(303,17),
(241,18),
(242,18),
(245,18),
(248,18),
(251,18),
(303,18),
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
(303,19),
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
(303,20),
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
(303,21),
(304,21),
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
(303,22),
(304,22),
(305,22),
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
(303,23),
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
(303,24),
(304,24),
(305,24),
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
(303,25),
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
(284,26),
(285,26),
(286,26),
(287,26),
(288,26),
(303,26),
(304,26),
(305,26),
(315,26),
(316,26);
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `roles` VALUES
(1,'super_admin','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(2,'hospital_admin','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(3,'doctor','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(4,'nurse','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(5,'receptionist','web','2026-09-23 09:09:39','2026-09-23 09:09:39'),
(6,'lab_technician','web','2026-09-23 09:09:40','2026-09-23 09:09:40'),
(7,'senior_lab_technician','web','2026-09-23 09:09:40','2026-09-23 09:09:40'),
(8,'pathologist','web','2026-09-23 09:09:40','2026-09-23 09:09:40'),
(9,'lab_receptionist','web','2026-09-23 09:09:40','2026-09-23 09:09:40'),
(10,'phlebotomist','web','2026-09-23 09:09:40','2026-09-23 09:09:40'),
(11,'lab_manager','web','2026-09-23 09:09:40','2026-09-23 09:09:40'),
(12,'radiology_receptionist','web','2026-09-23 09:09:40','2026-09-23 09:09:40'),
(13,'radiology_technician','web','2026-09-23 09:09:41','2026-09-23 09:09:41'),
(14,'radiologist','web','2026-09-23 09:09:41','2026-09-23 09:09:41'),
(15,'senior_radiologist','web','2026-09-23 09:09:41','2026-09-23 09:09:41'),
(16,'radiology_manager','web','2026-09-23 09:09:41','2026-09-23 09:09:41'),
(17,'pacs_administrator','web','2026-09-23 09:09:41','2026-09-23 09:09:41'),
(18,'pharmacy_receptionist','web','2026-09-23 09:09:41','2026-09-23 09:09:41'),
(19,'pharmacy_technician','web','2026-09-23 09:09:42','2026-09-23 09:09:42'),
(20,'pharmacist','web','2026-09-23 09:09:42','2026-09-23 09:09:42'),
(21,'senior_pharmacist','web','2026-09-23 09:09:42','2026-09-23 09:09:42'),
(22,'pharmacy_manager','web','2026-09-23 09:09:42','2026-09-23 09:09:42'),
(23,'storekeeper','web','2026-09-23 09:09:42','2026-09-23 09:09:42'),
(24,'pharmacy_administrator','web','2026-09-23 09:09:42','2026-09-23 09:09:42'),
(25,'cashier','web','2026-09-23 09:09:43','2026-09-23 09:09:43'),
(26,'accountant','web','2026-09-23 09:09:43','2026-09-23 09:09:43');
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
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `settings` VALUES
(1,'system','system.app_name','HealthNexus','string','Application name',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(2,'system','system.locale','en','string','Default application locale',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(3,'system','system.fallback_locale','en','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(4,'system','system.timezone','UTC','string','Default application timezone',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(5,'system','system.currency','USD','string','Default currency code',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(6,'system','system.date_format','Y-m-d','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(7,'system','system.time_format','H:i','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(8,'hospital','hospital.name','HealthNexus','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(9,'hospital','hospital.phone',NULL,'string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(10,'hospital','hospital.email',NULL,'string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(11,'hospital','hospital.address',NULL,'string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(12,'localization','localization.supported_locales','{\"en\":\"English\",\"bn\":\"Bangla\",\"ar\":\"Arabic\"}','json',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(13,'security','security.session_timeout','120','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(14,'security','security.max_login_attempts','5','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(15,'security','security.password_expiry_days','90','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(16,'security','security.password_min_length','8','integer','Minimum password length',1,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(17,'notifications','notifications.email_enabled','1','boolean',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(18,'notifications','notifications.sms_enabled','0','boolean',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(19,'notifications','notifications.whatsapp_enabled','0','boolean',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(20,'files','files.max_size_kb','2048','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(21,'files','files.allowed_extensions','[\"pdf\",\"jpg\",\"png\",\"docx\"]','json',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(22,'audit','audit.retention_days','365','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(23,'billing','billing.currency','BDT','string','Default billing currency',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(24,'billing','billing.invoice_prefix','INV','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(25,'billing','billing.payment_prefix','PMT','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(26,'billing','billing.receipt_prefix','RCT','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(27,'billing','billing.refund_prefix','RFD','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(28,'billing','billing.adjustment_prefix','ADJ','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(29,'billing','billing.invoice_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string','Document numbering format',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(30,'billing','billing.rounding_precision','2','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(31,'billing','billing.rounding_mode','nearest','string','nearest|up|down',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(32,'billing','billing.discount_approval_threshold_percent','10','integer','Discount % above which approval is required',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(33,'billing','billing.discount_approval_threshold_amount','5000','integer','Discount amount above which approval is required',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(34,'billing','billing.discount_approver_role','hospital_admin','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(35,'billing','billing.refund_approval_required','1','boolean',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(36,'billing','billing.default_price_list_priority','100','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(37,'billing','billing.tax_inclusive_default','0','boolean',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(38,'billing','billing.advance_min_balance','0','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(39,'laboratory','laboratory.order_prefix','LAB','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(40,'laboratory','laboratory.accession_prefix','ACC','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(41,'laboratory','laboratory.report_prefix','RPT','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(42,'laboratory','laboratory.order_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string','Document numbering format',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(43,'laboratory','laboratory.accession_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(44,'laboratory','laboratory.default_turnaround_minutes','60','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(45,'laboratory','laboratory.critical_notification_channels','[\"database\",\"mail\"]','json',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(46,'laboratory','laboratory.require_pathologist_approval_default','0','boolean',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(47,'radiology','radiology.order_prefix','RAD','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(48,'radiology','radiology.accession_prefix','RAD','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(49,'radiology','radiology.order_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(50,'radiology','radiology.accession_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(51,'radiology','radiology.default_turnaround_minutes','120','integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(52,'radiology','radiology.critical_finding_notification_channels','[\"database\",\"mail\"]','json',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(53,'radiology','radiology.dicom_uid_root','1.2.826.0.1.3680043.10.001','string','Org root OID used only if the app ever pre-assigns a Study Instance UID',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(54,'radiology','radiology.default_pacs_server_id',NULL,'integer',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(55,'pharmacy','pharmacy.order_prefix','RX','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(56,'pharmacy','pharmacy.dispensing_prefix','DSP','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(57,'pharmacy','pharmacy.transfer_prefix','TRF','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(58,'pharmacy','pharmacy.return_prefix','RTN','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(59,'pharmacy','pharmacy.order_number_format','{PREFIX}-{YEAR}-{SEQ:8}','string',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(60,'pharmacy','pharmacy.near_expiry_threshold_days','90','integer','Batches expiring within this many days are flagged near-expiry',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(61,'pharmacy','pharmacy.allow_negative_stock','0','boolean','Reserved — no override path is wired in Phase 7; dispensing always refuses insufficient stock',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(62,'pharmacy','pharmacy.controlled_drug_witness_required','0','boolean',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(63,'pharmacy','pharmacy.substitution_requires_approval','1','boolean',NULL,0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(64,'patients','patients.duplicate_weights','{\"name\":30,\"date_of_birth\":25,\"phone\":20,\"national_identifier\":20,\"email\":5}','json','Weighted duplicate-detection scoring per matched field',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43'),
(65,'patients','patients.amendment_sensitive_fields','[\"date_of_birth\",\"sex\",\"national_identifier\"]','json','Fields that require the amendment approval workflow instead of a direct edit',0,0,0,'2026-09-23 09:09:43','2026-09-23 09:09:43');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_branches`
--

LOCK TABLES `user_branches` WRITE;
/*!40000 ALTER TABLE `user_branches` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_branches` VALUES
(1,1,1,1,'manager',1,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,1,2,'manager',0,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_companies`
--

LOCK TABLES `user_companies` WRITE;
/*!40000 ALTER TABLE `user_companies` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_companies` VALUES
(1,1,1,'admin',1,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'Admin User','admin@healthnexus.test',NULL,NULL,NULL,'UTC','en',1,NULL,NULL,NULL,'$2y$12$OVGGEOXqOODZkMnwso47uOkiEW5yj3HCljQdTwRT3c8Rc.GXfPCjG',0,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vital_signs`
--

LOCK TABLES `vital_signs` WRITE;
/*!40000 ALTER TABLE `vital_signs` DISABLE KEYS */;
set autocommit=0;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_approvers`
--

LOCK TABLES `workflow_approvers` WRITE;
/*!40000 ALTER TABLE `workflow_approvers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `workflow_approvers` VALUES
(1,1,'role','hospital_admin',NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,2,'role','super_admin',NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(3,3,'role','hospital_admin',NULL,NULL,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_steps`
--

LOCK TABLES `workflow_steps` WRITE;
/*!40000 ALTER TABLE `workflow_steps` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `workflow_steps` VALUES
(1,1,1,'Supervisor Review',0,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,2,'Final Approval',1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(3,2,1,'Admin Approval',1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflows`
--

LOCK TABLES `workflows` WRITE;
/*!40000 ALTER TABLE `workflows` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `workflows` VALUES
(1,1,'generic_approval','Generic Approval','Two-step reference approval workflow: supervisor review, then final sign-off.',1,'2026-09-23 09:09:44','2026-09-23 09:09:44'),
(2,1,'patient_amendment','Patient Record Amendment','Approval required to correct sensitive patient identity fields.',1,'2026-09-23 09:09:44','2026-09-23 09:09:44');
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

-- Dump completed on 2026-09-23 21:20:47
