-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: kpi_laravel
-- ------------------------------------------------------
-- Server version	8.0.30

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

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `module` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_user_idx` (`user_id`,`created_at`),
  KEY `activity_module_idx` (`module`,`created_at`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=362 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_name` varchar(220) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conversion_quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `conversion_kpi` decimal(10,2) NOT NULL DEFAULT '1.00',
  `conversion_mode` enum('proportional','full_group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proportional',
  `default_excess_rate` decimal(18,2) NOT NULL DEFAULT '0.00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_normalized_unique` (`normalized_name`),
  UNIQUE KEY `courses_code_unique` (`code`),
  KEY `courses_active_idx` (`active`,`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=591 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `excess_payments`
--

DROP TABLE IF EXISTS `excess_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `excess_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_kind` enum('excess_kpi','collaborator') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `personnel_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `year` smallint unsigned NOT NULL,
  `period_type` enum('month','quarter','year') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_value` tinyint unsigned NOT NULL DEFAULT '0',
  `target_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `actual_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `excess_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `revenue_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `payment_rate` decimal(18,2) NOT NULL DEFAULT '0.00',
  `payment_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','approved','paid','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `paid_by` bigint unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `calculated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_period_unique` (`payment_key`,`year`,`period_type`,`period_value`),
  KEY `payment_status_idx` (`status`,`year`),
  KEY `fk_excess_payments_personnel` (`personnel_id`),
  KEY `fk_excess_payments_course` (`course_id`),
  KEY `fk_excess_payments_approved_by` (`approved_by`),
  KEY `fk_excess_payments_paid_by` (`paid_by`),
  KEY `fk_excess_payments_calculated_by` (`calculated_by`),
  CONSTRAINT `fk_excess_payments_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_excess_payments_calculated_by` FOREIGN KEY (`calculated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_excess_payments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_excess_payments_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_excess_payments_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `import_batches`
--

DROP TABLE IF EXISTS `import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_type` enum('target','result') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'result',
  `period_type` enum('month','quarter','year') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` smallint unsigned NOT NULL,
  `quarter` tinyint unsigned NOT NULL DEFAULT '0',
  `month` tinyint unsigned NOT NULL DEFAULT '0',
  `original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('processing','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processing',
  `total_rows` int unsigned NOT NULL DEFAULT '0',
  `success_rows` int unsigned NOT NULL DEFAULT '0',
  `error_rows` int unsigned NOT NULL DEFAULT '0',
  `total_revenue` decimal(18,2) NOT NULL DEFAULT '0.00',
  `error_details` json DEFAULT NULL,
  `imported_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_hash_idx` (`file_hash`),
  KEY `import_period_idx` (`year`,`period_type`,`quarter`,`month`),
  KEY `fk_import_batches_user` (`imported_by`),
  CONSTRAINT `fk_import_batches_user` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kpi_plans`
--

DROP TABLE IF EXISTS `kpi_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpi_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','active','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `settlement_scope` enum('month','quarter','year') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'quarter',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_plans_year_unique` (`year`),
  KEY `fk_kpi_plans_creator` (`created_by`),
  CONSTRAINT `fk_kpi_plans_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kpi_records`
--

DROP TABLE IF EXISTS `kpi_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpi_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_batch_id` bigint unsigned DEFAULT NULL,
  `source_row_no` int unsigned DEFAULT NULL,
  `personnel_id` bigint unsigned NOT NULL,
  `collaborator_id` bigint unsigned DEFAULT NULL,
  `course_id` bigint unsigned NOT NULL,
  `student_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_quantity` decimal(12,2) NOT NULL DEFAULT '1.00',
  `revenue` decimal(18,2) NOT NULL DEFAULT '0.00',
  `receipt_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_date` date NOT NULL,
  `record_year` smallint unsigned NOT NULL,
  `record_quarter` tinyint unsigned NOT NULL,
  `record_month` tinyint unsigned NOT NULL,
  `conversion_quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `conversion_kpi` decimal(10,2) NOT NULL DEFAULT '1.00',
  `conversion_mode` enum('proportional','full_group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proportional',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kpi_records_period_idx` (`record_year`,`record_quarter`,`record_month`),
  KEY `kpi_records_person_period_idx` (`personnel_id`,`record_year`,`record_month`),
  KEY `kpi_records_collab_period_idx` (`collaborator_id`,`record_year`,`record_month`),
  KEY `kpi_records_course_idx` (`course_id`),
  KEY `fk_kpi_records_batch` (`import_batch_id`),
  KEY `fk_kpi_records_creator` (`created_by`),
  CONSTRAINT `fk_kpi_records_batch` FOREIGN KEY (`import_batch_id`) REFERENCES `import_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_records_collaborator` FOREIGN KEY (`collaborator_id`) REFERENCES `personnels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_records_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_kpi_records_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_records_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kpi_targets`
--

DROP TABLE IF EXISTS `kpi_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpi_targets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint unsigned NOT NULL,
  `personnel_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `period_type` enum('month','quarter','year') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quarter` tinyint unsigned NOT NULL DEFAULT '0',
  `month` tinyint unsigned NOT NULL DEFAULT '0',
  `target_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `target_revenue` decimal(18,2) NOT NULL DEFAULT '0.00',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '1',
  `excess_payment_per_kpi` decimal(18,2) NOT NULL DEFAULT '0.00',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_target_period_unique` (`plan_id`,`personnel_id`,`course_id`,`period_type`,`quarter`,`month`,`deleted_at`),
  KEY `kpi_target_period_idx` (`plan_id`,`period_type`,`quarter`,`month`),
  KEY `kpi_target_person_idx` (`personnel_id`),
  KEY `fk_kpi_targets_creator` (`created_by`),
  KEY `fk_kpi_targets_course` (`course_id`),
  CONSTRAINT `fk_kpi_targets_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_targets_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_targets_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kpi_targets_plan` FOREIGN KEY (`plan_id`) REFERENCES `kpi_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_classes`
--

DROP TABLE IF EXISTS `language_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `language_course_id` bigint unsigned DEFAULT NULL,
  `language_program_id` bigint unsigned NOT NULL,
  `language_level_id` bigint unsigned DEFAULT NULL,
  `teacher_user_id` bigint unsigned DEFAULT NULL,
  `room` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expected_end_date` date DEFAULT NULL,
  `expected_sessions` smallint unsigned NOT NULL DEFAULT '0',
  `completed_sessions` smallint unsigned NOT NULL DEFAULT '0',
  `completion_requested_at` timestamp NULL DEFAULT NULL,
  `completion_requested_by` bigint unsigned DEFAULT NULL,
  `completion_note` text COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint unsigned DEFAULT NULL,
  `max_students` smallint unsigned NOT NULL DEFAULT '20',
  `default_tuition` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recruiting',
  `schedule_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_classes_code_unique` (`code`),
  KEY `language_classes_language_program_id_foreign` (`language_program_id`),
  KEY `language_classes_language_level_id_foreign` (`language_level_id`),
  KEY `language_classes_teacher_user_id_foreign` (`teacher_user_id`),
  KEY `language_classes_completion_requested_by_foreign` (`completion_requested_by`),
  KEY `language_classes_completed_by_foreign` (`completed_by`),
  KEY `language_classes_language_course_id_foreign` (`language_course_id`),
  CONSTRAINT `language_classes_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_classes_completion_requested_by_foreign` FOREIGN KEY (`completion_requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_classes_language_course_id_foreign` FOREIGN KEY (`language_course_id`) REFERENCES `language_courses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `language_classes_language_level_id_foreign` FOREIGN KEY (`language_level_id`) REFERENCES `language_levels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_classes_language_program_id_foreign` FOREIGN KEY (`language_program_id`) REFERENCES `language_programs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `language_classes_teacher_user_id_foreign` FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_collaborators`
--

DROP TABLE IF EXISTS `language_collaborators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_collaborators` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `personnel_id` bigint unsigned DEFAULT NULL,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_collaborators_code_unique` (`code`),
  UNIQUE KEY `language_collaborators_personnel_id_unique` (`personnel_id`),
  CONSTRAINT `language_collaborators_personnel_id_foreign` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_courses`
--

DROP TABLE IF EXISTS `language_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `language_program_id` bigint unsigned DEFAULT NULL,
  `language_level_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `textbook` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tuition` decimal(14,2) NOT NULL DEFAULT '0.00',
  `duration_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `sessions` smallint unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_courses_code_unique` (`code`),
  KEY `language_courses_language_program_id_foreign` (`language_program_id`),
  KEY `language_courses_language_level_id_foreign` (`language_level_id`),
  CONSTRAINT `language_courses_language_level_id_foreign` FOREIGN KEY (`language_level_id`) REFERENCES `language_levels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `language_courses_language_program_id_foreign` FOREIGN KEY (`language_program_id`) REFERENCES `language_programs` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_discount_policies`
--

DROP TABLE IF EXISTS `language_discount_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_discount_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `eligible_subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `starts_at` date DEFAULT NULL,
  `ends_at` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_discount_policies_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_enrollments`
--

DROP TABLE IF EXISTS `language_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_class_id` bigint unsigned NOT NULL,
  `language_student_id` bigint unsigned NOT NULL,
  `enrolled_at` date NOT NULL,
  `ended_at` date DEFAULT NULL,
  `tuition` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'studying',
  `exit_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_enrollment_class_student_uq` (`language_class_id`,`language_student_id`),
  KEY `language_enrollments_language_student_id_foreign` (`language_student_id`),
  CONSTRAINT `language_enrollments_language_class_id_foreign` FOREIGN KEY (`language_class_id`) REFERENCES `language_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `language_enrollments_language_student_id_foreign` FOREIGN KEY (`language_student_id`) REFERENCES `language_students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_guardians`
--

DROP TABLE IF EXISTS `language_guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_guardians` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_student_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zalo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `language_guardians_language_student_id_foreign` (`language_student_id`),
  CONSTRAINT `language_guardians_language_student_id_foreign` FOREIGN KEY (`language_student_id`) REFERENCES `language_students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_leads`
--

DROP TABLE IF EXISTS `language_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zalo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  `language_program_id` bigint unsigned DEFAULT NULL,
  `language_course_id` bigint unsigned DEFAULT NULL,
  `converted_student_id` bigint unsigned DEFAULT NULL,
  `consultant_user_id` bigint unsigned DEFAULT NULL,
  `language_collaborator_id` bigint unsigned DEFAULT NULL,
  `appointment_at` datetime DEFAULT NULL,
  `last_consulted_at` datetime DEFAULT NULL,
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `consultation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_leads_code_unique` (`code`),
  KEY `language_leads_language_program_id_foreign` (`language_program_id`),
  KEY `language_leads_consultant_user_id_foreign` (`consultant_user_id`),
  KEY `language_leads_language_collaborator_id_foreign` (`language_collaborator_id`),
  KEY `language_leads_language_course_id_foreign` (`language_course_id`),
  KEY `language_leads_converted_student_id_foreign` (`converted_student_id`),
  KEY `language_leads_received_at_index` (`received_at`),
  CONSTRAINT `language_leads_consultant_user_id_foreign` FOREIGN KEY (`consultant_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_leads_converted_student_id_foreign` FOREIGN KEY (`converted_student_id`) REFERENCES `language_students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_leads_language_collaborator_id_foreign` FOREIGN KEY (`language_collaborator_id`) REFERENCES `language_collaborators` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_leads_language_course_id_foreign` FOREIGN KEY (`language_course_id`) REFERENCES `language_courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_leads_language_program_id_foreign` FOREIGN KEY (`language_program_id`) REFERENCES `language_programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_levels`
--

DROP TABLE IF EXISTS `language_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_program_id` bigint unsigned NOT NULL,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expected_sessions` smallint unsigned NOT NULL DEFAULT '0',
  `expected_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `default_tuition` decimal(14,2) NOT NULL DEFAULT '0.00',
  `passing_score` decimal(5,2) DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_levels_code_unique` (`code`),
  KEY `language_levels_language_program_id_foreign` (`language_program_id`),
  CONSTRAINT `language_levels_language_program_id_foreign` FOREIGN KEY (`language_program_id`) REFERENCES `language_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_monthly_target_records`
--

DROP TABLE IF EXISTS `language_monthly_target_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_monthly_target_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_year` smallint unsigned NOT NULL,
  `record_month` tinyint unsigned NOT NULL,
  `language_student_id` bigint unsigned NOT NULL,
  `language_lead_id` bigint unsigned DEFAULT NULL,
  `language_collaborator_id` bigint unsigned DEFAULT NULL,
  `language_course_id` bigint unsigned NOT NULL,
  `language_tuition_payment_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `revenue` decimal(14,2) NOT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lmtr_payment_uq` (`language_tuition_payment_id`),
  KEY `lmtr_student_fk` (`language_student_id`),
  KEY `lmtr_lead_fk` (`language_lead_id`),
  KEY `lmtr_collab_fk` (`language_collaborator_id`),
  KEY `lmtr_course_fk` (`language_course_id`),
  KEY `language_monthly_target_records_record_year_record_month_index` (`record_year`,`record_month`),
  CONSTRAINT `lmtr_collab_fk` FOREIGN KEY (`language_collaborator_id`) REFERENCES `language_collaborators` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lmtr_course_fk` FOREIGN KEY (`language_course_id`) REFERENCES `language_courses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `lmtr_lead_fk` FOREIGN KEY (`language_lead_id`) REFERENCES `language_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lmtr_payment_fk` FOREIGN KEY (`language_tuition_payment_id`) REFERENCES `language_tuition_payments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `lmtr_student_fk` FOREIGN KEY (`language_student_id`) REFERENCES `language_students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_programs`
--

DROP TABLE IF EXISTS `language_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_programs_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_student_monthly_progress`
--

DROP TABLE IF EXISTS `language_student_monthly_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_student_monthly_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_enrollment_id` bigint unsigned NOT NULL,
  `month` date NOT NULL,
  `planned_sessions` smallint unsigned NOT NULL DEFAULT '0',
  `attended_sessions` smallint unsigned NOT NULL DEFAULT '0',
  `participation_score` decimal(5,2) DEFAULT NULL,
  `homework_score` decimal(5,2) DEFAULT NULL,
  `assessment` text COLLATE utf8mb4_unicode_ci,
  `learning_note` text COLLATE utf8mb4_unicode_ci,
  `teacher_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_progress_enrollment_month_uq` (`language_enrollment_id`,`month`),
  KEY `language_student_monthly_progress_teacher_user_id_foreign` (`teacher_user_id`),
  CONSTRAINT `language_student_monthly_progress_language_enrollment_id_foreign` FOREIGN KEY (`language_enrollment_id`) REFERENCES `language_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `language_student_monthly_progress_teacher_user_id_foreign` FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_student_scores`
--

DROP TABLE IF EXISTS `language_student_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_student_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_enrollment_id` bigint unsigned NOT NULL,
  `test_date` date NOT NULL,
  `test_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `score` decimal(6,2) NOT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT '10.00',
  `note` text COLLATE utf8mb4_unicode_ci,
  `teacher_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `language_student_scores_teacher_user_id_foreign` (`teacher_user_id`),
  KEY `language_student_scores_language_enrollment_id_test_date_index` (`language_enrollment_id`,`test_date`),
  CONSTRAINT `language_student_scores_language_enrollment_id_foreign` FOREIGN KEY (`language_enrollment_id`) REFERENCES `language_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `language_student_scores_teacher_user_id_foreign` FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_students`
--

DROP TABLE IF EXISTS `language_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `school` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registered_at` date NOT NULL,
  `official_enrollment_date` date DEFAULT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language_course_id` bigint unsigned DEFAULT NULL,
  `language_discount_policy_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_students_code_unique` (`code`),
  KEY `language_students_language_course_id_foreign` (`language_course_id`),
  KEY `language_students_language_discount_policy_id_foreign` (`language_discount_policy_id`),
  CONSTRAINT `language_students_language_course_id_foreign` FOREIGN KEY (`language_course_id`) REFERENCES `language_courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_students_language_discount_policy_id_foreign` FOREIGN KEY (`language_discount_policy_id`) REFERENCES `language_discount_policies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_target_submissions`
--

DROP TABLE IF EXISTS `language_target_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_target_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_normalized` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language_course_id` bigint unsigned DEFAULT NULL,
  `other_course` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language_lead_id` bigint unsigned DEFAULT NULL,
  `submitted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `language_target_submissions_language_course_id_foreign` (`language_course_id`),
  KEY `language_target_submissions_submitted_by_created_at_index` (`submitted_by`,`created_at`),
  KEY `lts_duplicate_lookup_idx` (`phone_normalized`,`course_key`),
  KEY `language_target_submissions_language_lead_id_foreign` (`language_lead_id`),
  CONSTRAINT `language_target_submissions_language_course_id_foreign` FOREIGN KEY (`language_course_id`) REFERENCES `language_courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_target_submissions_language_lead_id_foreign` FOREIGN KEY (`language_lead_id`) REFERENCES `language_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_target_submissions_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_tuition_charges`
--

DROP TABLE IF EXISTS `language_tuition_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_tuition_charges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `language_student_id` bigint unsigned NOT NULL,
  `language_lead_id` bigint unsigned DEFAULT NULL,
  `language_course_id` bigint unsigned NOT NULL,
  `language_class_id` bigint unsigned DEFAULT NULL,
  `language_discount_policy_id` bigint unsigned DEFAULT NULL,
  `original_amount` decimal(14,2) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payable_amount` decimal(14,2) NOT NULL,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `due_date` date DEFAULT NULL,
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_tuition_charges_code_unique` (`code`),
  UNIQUE KEY `tuition_student_class_unique` (`language_student_id`,`language_class_id`),
  KEY `language_tuition_charges_language_student_id_foreign` (`language_student_id`),
  KEY `language_tuition_charges_language_lead_id_foreign` (`language_lead_id`),
  KEY `language_tuition_charges_language_course_id_foreign` (`language_course_id`),
  KEY `language_tuition_charges_language_class_id_foreign` (`language_class_id`),
  KEY `language_tuition_charges_language_discount_policy_id_foreign` (`language_discount_policy_id`),
  KEY `language_tuition_charges_created_by_foreign` (`created_by`),
  CONSTRAINT `language_tuition_charges_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_tuition_charges_language_class_id_foreign` FOREIGN KEY (`language_class_id`) REFERENCES `language_classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_tuition_charges_language_course_id_foreign` FOREIGN KEY (`language_course_id`) REFERENCES `language_courses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `language_tuition_charges_language_discount_policy_id_foreign` FOREIGN KEY (`language_discount_policy_id`) REFERENCES `language_discount_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_tuition_charges_language_lead_id_foreign` FOREIGN KEY (`language_lead_id`) REFERENCES `language_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_tuition_charges_language_student_id_foreign` FOREIGN KEY (`language_student_id`) REFERENCES `language_students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_tuition_payments`
--

DROP TABLE IF EXISTS `language_tuition_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_tuition_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `language_tuition_charge_id` bigint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `book_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `paid_at` datetime NOT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `payment_method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collected_by` bigint unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_tuition_payments_receipt_code_unique` (`receipt_code`),
  KEY `language_tuition_payments_language_tuition_charge_id_foreign` (`language_tuition_charge_id`),
  KEY `language_tuition_payments_collected_by_foreign` (`collected_by`),
  KEY `language_tuition_payments_receipt_status_index` (`receipt_status`),
  CONSTRAINT `language_tuition_payments_collected_by_foreign` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `language_tuition_payments_language_tuition_charge_id_foreign` FOREIGN KEY (`language_tuition_charge_id`) REFERENCES `language_tuition_charges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event` enum('login_success','login_failed','logout') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login_logs_email_idx` (`email`,`created_at`),
  KEY `login_logs_user_idx` (`user_id`,`created_at`),
  CONSTRAINT `fk_login_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnels`
--

DROP TABLE IF EXISTS `personnels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_name` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('teacher','employee','leader','collaborator','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_kpi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `has_kpi` tinyint(1) NOT NULL DEFAULT '1',
  `is_consultant` tinyint(1) NOT NULL DEFAULT '0',
  `payment_type` enum('none','percentage','per_student','fixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `payment_value` decimal(18,2) NOT NULL DEFAULT '0.00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personnels_code_unique` (`code`),
  KEY `personnels_normalized_idx` (`normalized_name`),
  KEY `personnels_type_active_idx` (`type`,`active`),
  KEY `personnels_deleted_idx` (`deleted_at`),
  KEY `personnels_is_consultant_index` (`is_consultant`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `module_id` bigint unsigned NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_create` tinyint(1) NOT NULL DEFAULT '0',
  `can_update` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `can_export` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_module_unique` (`role_id`,`module_id`),
  KEY `fk_role_permissions_module` (`module_id`),
  CONSTRAINT `fk_role_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `upcoming_plans`
--

DROP TABLE IF EXISTS `upcoming_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `upcoming_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `assigned_by_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `scheduled_for` datetime NOT NULL,
  `reminder_days` tinyint unsigned NOT NULL DEFAULT '1',
  `priority` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `kind` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'personal',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `upcoming_plans_user_id_scheduled_for_index` (`user_id`,`scheduled_for`),
  KEY `upcoming_plans_assigned_by_id_foreign` (`assigned_by_id`),
  KEY `upcoming_plans_kind_index` (`kind`),
  CONSTRAINT `upcoming_plans_assigned_by_id_foreign` FOREIGN KEY (`assigned_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `upcoming_plans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `module_id` bigint unsigned NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_create` tinyint(1) NOT NULL DEFAULT '0',
  `can_update` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `can_export` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module_unique` (`user_id`,`module_id`),
  KEY `fk_user_permissions_module` (`module_id`),
  CONSTRAINT `fk_user_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_preferences_user_id_key_unique` (`user_id`,`key`),
  CONSTRAINT `user_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `personnel_id` bigint unsigned DEFAULT NULL,
  `language_collaborator_id` bigint unsigned DEFAULT NULL,
  `role_id` bigint unsigned NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `zalo_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zalo_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zalo_linked_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `theme_color` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_personnel_unique` (`personnel_id`),
  UNIQUE KEY `users_language_collaborator_id_unique` (`language_collaborator_id`),
  UNIQUE KEY `users_zalo_id_unique` (`zalo_id`),
  KEY `users_role_idx` (`role_id`),
  KEY `users_active_idx` (`active`,`deleted_at`),
  CONSTRAINT `fk_users_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `users_language_collaborator_id_foreign` FOREIGN KEY (`language_collaborator_id`) REFERENCES `language_collaborators` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `work_task_activities`
--

DROP TABLE IF EXISTS `work_task_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_task_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_task_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_task_activities_work_task_id_foreign` (`work_task_id`),
  KEY `work_task_activities_user_id_foreign` (`user_id`),
  CONSTRAINT `work_task_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_task_activities_work_task_id_foreign` FOREIGN KEY (`work_task_id`) REFERENCES `work_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `work_task_assignees`
--

DROP TABLE IF EXISTS `work_task_assignees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_task_assignees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_task_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `is_lead` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_task_assignees_work_task_id_user_id_unique` (`work_task_id`,`user_id`),
  KEY `work_task_assignees_user_id_foreign` (`user_id`),
  CONSTRAINT `work_task_assignees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_task_assignees_work_task_id_foreign` FOREIGN KEY (`work_task_id`) REFERENCES `work_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `work_task_comments`
--

DROP TABLE IF EXISTS `work_task_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_task_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_task_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_task_comments_work_task_id_foreign` (`work_task_id`),
  KEY `work_task_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `work_task_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_task_comments_work_task_id_foreign` FOREIGN KEY (`work_task_id`) REFERENCES `work_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `work_tasks`
--

DROP TABLE IF EXISTS `work_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by_id` bigint unsigned NOT NULL,
  `closed_by_id` bigint unsigned DEFAULT NULL,
  `title` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `due_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  `priority` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_tasks_created_by_id_due_at_index` (`created_by_id`,`due_at`),
  KEY `work_tasks_closed_by_id_foreign` (`closed_by_id`),
  KEY `work_tasks_closed_at_index` (`closed_at`),
  CONSTRAINT `work_tasks_closed_by_id_foreign` FOREIGN KEY (`closed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_tasks_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'kpi_laravel'
--

--
-- Dumping routines for database 'kpi_laravel'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-29  9:11:56
