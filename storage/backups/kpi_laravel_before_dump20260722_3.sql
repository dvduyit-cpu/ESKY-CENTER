-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: kpi_laravel
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
  `module` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_user_idx` (`user_id`,`created_at`),
  KEY `activity_module_idx` (`module`,`created_at`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_name` varchar(220) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conversion_quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `conversion_kpi` decimal(10,2) NOT NULL DEFAULT '1.00',
  `conversion_mode` enum('proportional','full_group') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proportional',
  `default_excess_rate` decimal(18,2) NOT NULL DEFAULT '0.00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_normalized_unique` (`normalized_name`),
  UNIQUE KEY `courses_code_unique` (`code`),
  KEY `courses_active_idx` (`active`,`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'B1','Chứng nhận B1','chung nhan b1','Chứng chỉ ngoại ngữ',2.00,1.00,'full_group',100000.00,1,'Đủ 2 lượt mới tính 1 KPI','2026-07-22 15:36:27','2026-07-22 15:36:27',NULL),(2,'UDCNTT','Ứng dụng CNTT cơ bản','ung dung cntt co ban','Tin học',1.00,1.00,'proportional',100000.00,1,'Một lượt tính một KPI','2026-07-22 15:36:27','2026-07-22 15:36:27',NULL),(3,'AI-VP','AI trong công tác văn phòng','ai trong cong tac van phong','AI - Tin học',1.00,1.00,'proportional',100000.00,1,'Một lượt tính một KPI','2026-07-22 15:36:27','2026-07-22 15:36:27',NULL);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `excess_payments`
--

DROP TABLE IF EXISTS `excess_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `excess_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_kind` enum('excess_kpi','collaborator') COLLATE utf8mb4_unicode_ci NOT NULL,
  `personnel_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `year` smallint unsigned NOT NULL,
  `period_type` enum('month','quarter','year') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_value` tinyint unsigned NOT NULL DEFAULT '0',
  `target_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `actual_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `excess_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `revenue_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `payment_rate` decimal(18,2) NOT NULL DEFAULT '0.00',
  `payment_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','approved','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `paid_by` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
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
-- Dumping data for table `excess_payments`
--

LOCK TABLES `excess_payments` WRITE;
/*!40000 ALTER TABLE `excess_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `excess_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_batches`
--

DROP TABLE IF EXISTS `import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_type` enum('target','result') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'result',
  `period_type` enum('month','quarter','year') COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` smallint unsigned NOT NULL,
  `quarter` tinyint unsigned NOT NULL DEFAULT '0',
  `month` tinyint unsigned NOT NULL DEFAULT '0',
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processing',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_batches`
--

LOCK TABLES `import_batches` WRITE;
/*!40000 ALTER TABLE `import_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kpi_plans`
--

DROP TABLE IF EXISTS `kpi_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpi_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','active','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `settlement_scope` enum('month','quarter','year') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'quarter',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_plans_year_unique` (`year`),
  KEY `fk_kpi_plans_creator` (`created_by`),
  CONSTRAINT `fk_kpi_plans_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kpi_plans`
--

LOCK TABLES `kpi_plans` WRITE;
/*!40000 ALTER TABLE `kpi_plans` DISABLE KEYS */;
INSERT INTO `kpi_plans` VALUES (1,2026,'Kế hoạch chỉ tiêu năm 2026','active','quarter','Kế hoạch mẫu',1,'2026-07-22 15:36:27','2026-07-22 15:36:27');
/*!40000 ALTER TABLE `kpi_plans` ENABLE KEYS */;
UNLOCK TABLES;

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
  `student_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_quantity` decimal(12,2) NOT NULL DEFAULT '1.00',
  `revenue` decimal(18,2) NOT NULL DEFAULT '0.00',
  `receipt_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_date` date NOT NULL,
  `record_year` smallint unsigned NOT NULL,
  `record_quarter` tinyint unsigned NOT NULL,
  `record_month` tinyint unsigned NOT NULL,
  `conversion_quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `conversion_kpi` decimal(10,2) NOT NULL DEFAULT '1.00',
  `conversion_mode` enum('proportional','full_group') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proportional',
  `note` text COLLATE utf8mb4_unicode_ci,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kpi_records`
--

LOCK TABLES `kpi_records` WRITE;
/*!40000 ALTER TABLE `kpi_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `kpi_records` ENABLE KEYS */;
UNLOCK TABLES;

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
  `period_type` enum('month','quarter','year') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quarter` tinyint unsigned NOT NULL DEFAULT '0',
  `month` tinyint unsigned NOT NULL DEFAULT '0',
  `target_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `target_revenue` decimal(18,2) NOT NULL DEFAULT '0.00',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '1',
  `excess_payment_per_kpi` decimal(18,2) NOT NULL DEFAULT '0.00',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_target_period_unique` (`plan_id`,`personnel_id`,`course_id`,`period_type`,`quarter`,`month`,`deleted_at`),
  KEY `kpi_target_period_idx` (`plan_id`,`period_type`,`quarter`,`month`),
  KEY `kpi_target_person_idx` (`personnel_id`),
  KEY `fk_kpi_targets_course` (`course_id`),
  KEY `fk_kpi_targets_creator` (`created_by`),
  CONSTRAINT `fk_kpi_targets_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_targets_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_targets_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kpi_targets_plan` FOREIGN KEY (`plan_id`) REFERENCES `kpi_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kpi_targets`
--

LOCK TABLES `kpi_targets` WRITE;
/*!40000 ALTER TABLE `kpi_targets` DISABLE KEYS */;
INSERT INTO `kpi_targets` VALUES (1,1,2,NULL,'month',3,7,42.00,0.00,1,100000.00,'KPI tổng giáo viên tháng 7',1,'2026-07-22 15:36:27','2026-07-22 15:36:27',NULL),(2,1,3,NULL,'month',3,7,55.00,0.00,1,100000.00,'KPI tổng nhân viên tháng 7',1,'2026-07-22 15:36:27','2026-07-22 15:36:27',NULL);
/*!40000 ALTER TABLE `kpi_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event` enum('login_success','login_failed','logout') COLLATE utf8mb4_unicode_ci NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login_logs_email_idx` (`email`,`created_at`),
  KEY `login_logs_user_idx` (`user_id`,`created_at`),
  CONSTRAINT `fk_login_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
INSERT INTO `login_logs` VALUES (1,NULL,'admin@bdu.edu.vn','login_failed',0,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-22 22:42:36'),(2,NULL,'admin@bdu.edu.vn','login_failed',0,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-22 22:42:45'),(3,1,'admin@kpi.local','login_success',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-22 22:42:52');
/*!40000 ALTER TABLE `login_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,'personnel','Nhân sự & cộng tác viên','bi-people-fill',10,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(2,'users','Tài khoản','bi-person-lock',20,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(3,'roles','Vai trò & quyền','bi-shield-check',30,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(4,'kpis','Chỉ tiêu KPI','bi-bullseye',40,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(5,'courses','Khóa học','bi-journal-bookmark-fill',50,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(6,'imports','Nhập Excel','bi-file-earmark-spreadsheet',60,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(7,'reports','Báo cáo','bi-bar-chart-line-fill',70,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(8,'payments','Thanh toán vượt','bi-cash-coin',80,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(9,'logs','Nhật ký hệ thống','bi-clock-history',90,'2026-07-22 15:36:27','2026-07-22 15:36:27');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personnels`
--

DROP TABLE IF EXISTS `personnels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('teacher','employee','leader','collaborator','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_kpi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `has_kpi` tinyint(1) NOT NULL DEFAULT '1',
  `payment_type` enum('none','percentage','per_student','fixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `payment_value` decimal(18,2) NOT NULL DEFAULT '0.00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personnels_code_unique` (`code`),
  KEY `personnels_normalized_idx` (`normalized_name`),
  KEY `personnels_type_active_idx` (`type`,`active`),
  KEY `personnels_deleted_idx` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnels`
--

LOCK TABLES `personnels` WRITE;
/*!40000 ALTER TABLE `personnels` DISABLE KEYS */;
INSERT INTO `personnels` VALUES (1,'ADM001','Quản trị viên','quan tri vien','admin','Admin hệ thống','admin@kpi.local',NULL,0.00,0,'none',0.00,1,NULL,'2026-07-22 15:36:27','2026-07-22 15:36:27',NULL),(2,'GV001','Giáo viên mẫu','giao vien mau','teacher','Giáo viên','giaovien@kpi.local',NULL,42.00,1,'none',0.00,1,NULL,'2026-07-22 15:36:27','2026-07-22 15:36:27',NULL),(3,'NV001','Nhân viên mẫu','nhan vien mau','employee','Nhân viên','nhanvien@kpi.local',NULL,55.00,1,'none',0.00,1,NULL,'2026-07-22 15:36:27','2026-07-22 15:36:27',NULL),(4,'LD001','Lãnh đạo mẫu','lanh dao mau','leader','Giám đốc','lanhdao@kpi.local',NULL,0.00,0,'none',0.00,1,NULL,'2026-07-22 15:36:27','2026-07-22 15:36:27',NULL),(5,'CTV001','Cộng tác viên mẫu','cong tac vien mau','collaborator','Cộng tác viên',NULL,NULL,0.00,0,'percentage',5.00,1,NULL,'2026-07-22 15:36:27','2026-07-22 15:36:27',NULL);
/*!40000 ALTER TABLE `personnels` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,5,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(2,1,6,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(3,1,4,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(4,1,9,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(5,1,8,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(6,1,1,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(7,1,7,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(8,1,3,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(9,1,2,1,1,1,1,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(16,2,1,1,1,1,0,0,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(17,2,4,1,1,1,0,0,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(18,2,5,1,0,0,0,0,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(19,2,6,1,1,0,0,0,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(20,2,7,1,0,0,0,1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(21,2,8,1,1,1,0,0,'2026-07-22 15:36:27','2026-07-22 15:36:27');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Admin','Toàn quyền hệ thống',1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(2,'leader','Lãnh đạo','Giám đốc, Phó giám đốc và người quản lý',1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(3,'teacher','Giáo viên','Mặc định chỉ xem tổng quan cá nhân; Admin cấp thêm module nếu cần',1,'2026-07-22 15:36:27','2026-07-22 15:36:27'),(4,'staff','Nhân viên','Mặc định chỉ xem tổng quan cá nhân; Admin cấp thêm module nếu cần',1,'2026-07-22 15:36:27','2026-07-22 15:36:27');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `personnel_id` bigint unsigned DEFAULT NULL,
  `role_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_personnel_unique` (`personnel_id`),
  KEY `users_role_idx` (`role_id`),
  KEY `users_active_idx` (`active`,`deleted_at`),
  CONSTRAINT `fk_users_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,1,'Quản trị viên','admin@kpi.local',NULL,'$2y$12$TcLovTFlAEMf.tsGOe1Giel5RvCN6LI.F5U83wXO8Q2rMaRmm1mre',1,0,'2026-07-22 22:42:52','127.0.0.1',NULL,'2026-07-22 15:36:27','2026-07-22 15:42:52',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

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

-- Dump completed on 2026-07-22 22:44:01
