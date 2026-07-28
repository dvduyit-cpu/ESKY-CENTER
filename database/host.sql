-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: esky_automated_test
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
) ENGINE=InnoDB AUTO_INCREMENT=373 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (585,'B1TVU','chứng nhận B1','chung nhan b1','Liên kết',1.00,0.50,'proportional',0.00,1,NULL,'2026-07-14 12:41:47','2026-07-14 13:05:36',NULL),(586,'UDCNTT','UDCNTT','udcntt','Tin học',1.00,1.00,'proportional',0.00,1,NULL,'2026-07-14 12:42:48','2026-07-14 12:42:48',NULL),(587,'AI','Bồi dưỡng AI','boi duong ai','Tin học',1.00,1.00,'proportional',0.00,1,NULL,'2026-07-14 12:43:07','2026-07-14 12:43:07',NULL),(588,'AVTN','AVTN','avtn','Ngoại ngữ',1.00,1.00,'proportional',0.00,1,NULL,'2026-07-14 12:43:43','2026-07-14 12:43:43',NULL),(589,'VSTEP','VSTEP','vstep','Liên kết',1.00,1.00,'proportional',0.00,1,NULL,'2026-07-14 12:43:57','2026-07-14 12:43:57',NULL),(590,NULL,'esky','esky',NULL,1.00,1.00,'proportional',0.00,1,NULL,'2026-07-14 12:53:31','2026-07-14 12:53:31',NULL);
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
-- Dumping data for table `excess_payments`
--

LOCK TABLES `excess_payments` WRITE;
/*!40000 ALTER TABLE `excess_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `excess_payments` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `import_batches`
--

LOCK TABLES `import_batches` WRITE;
/*!40000 ALTER TABLE `import_batches` DISABLE KEYS */;
INSERT INTO `import_batches` VALUES (1,'result','month',2026,3,7,'du-lieu-kiem-thu-1.xlsx','private/imports/kiem-thu-1.xlsx','ed1e1dcf971990c1b89676ae785436106f7548b1ae41d174ca9d3bfb9661a477','failed',0,0,0,0.00,NULL,NULL,'2026-07-14 08:52:12','2026-07-14 08:52:13'),(2,'result','month',2026,3,7,'du-lieu-kiem-thu-2.xlsx','private/imports/kiem-thu-2.xlsx','e063cdf36f817a24e97839b0799c023644dd1c31c668bda6481869027035a655','completed',526,0,526,0.00,NULL,1,'2026-07-14 08:57:29','2026-07-14 08:57:30'),(3,'result','quarter',2026,2,0,'du-lieu-kiem-thu-3.xlsx','private/imports/kiem-thu-3.xlsx','2a8c9f051e91be1d0f801980a9e87f8495582668d966b633bfde5d8a93d0e049','completed',526,0,526,0.00,NULL,1,'2026-07-14 08:59:27','2026-07-14 08:59:29'),(4,'result','quarter',2026,1,0,'du-lieu-kiem-thu-4.xlsx','private/imports/kiem-thu-4.xlsx','a9ebf3ad7308478fec2f0240cab4605bcd5876b3361d275d5f4cedf5770fdb12','completed',526,0,526,0.00,NULL,1,'2026-07-14 09:01:31','2026-07-14 09:01:33'),(5,'result','month',2026,3,7,'du-lieu-kiem-thu-5.xlsx','private/imports/kiem-thu-5.xlsx','5f71b27f826ff16d1d955451ad0e2fdfc00390c9c5678da32d3ffa26ed2064c8','completed',581,1,580,1500000.00,NULL,1,'2026-07-14 09:02:06','2026-07-14 09:02:08'),(6,'result','month',2026,3,7,'du-lieu-kiem-thu-6.xlsx','private/imports/kiem-thu-6.xlsx','e0b714fb3f30724489657a375fd4913a874da4f6a01810ccb6b88743142a4d73','completed',3,3,0,4500003.00,NULL,1,'2026-07-14 09:04:26','2026-07-14 09:04:26'),(7,'result','month',2026,3,7,'du-lieu-kiem-thu-7.xlsx','private/imports/kiem-thu-7.xlsx','9c71311c5fc4e8453f5dd0d4dd41773325ea5ab5e188a7341cacdb44a7c3df4a','completed',4,4,0,6000005.00,NULL,1,'2026-07-14 09:09:18','2026-07-14 09:09:18'),(8,'result','month',2026,3,7,'du-lieu-kiem-thu-8.xlsx','private/imports/kiem-thu-8.xlsx','d96164f03222019b42723ed685bb20eed3a5014f78bb4fc1e0b7be71705a251a','completed',17,17,0,25500000.00,NULL,1,'2026-07-14 09:14:59','2026-07-14 09:14:59'),(9,'result','month',2026,3,7,'du-lieu-kiem-thu-9.xlsx','private/imports/kiem-thu-9.xlsx','770990271fb786bacd994e80c59ebd09c80092256cb8620ec7fc8096b7d72595','completed',1,0,1,0.00,NULL,1,'2026-07-14 12:51:59','2026-07-14 12:51:59'),(10,'result','month',2026,3,7,'du-lieu-kiem-thu-10.xlsx','private/imports/kiem-thu-10.xlsx','f2b614d017e8ce763436178f2127018ece0886f9a1a1c85e7e649efcb061217f','completed',1,0,1,0.00,NULL,1,'2026-07-14 12:52:45','2026-07-14 12:52:45'),(11,'result','month',2026,3,7,'du-lieu-kiem-thu-11.xlsx','private/imports/kiem-thu-11.xlsx','aba8c8f7638d612014e04aafbf84c8b3952aed3864669ffdefeb5c22d6e339ec','completed',1,0,1,0.00,NULL,1,'2026-07-14 12:53:31','2026-07-14 12:53:31'),(12,'result','month',2026,3,7,'du-lieu-kiem-thu-12.xlsx','private/imports/kiem-thu-12.xlsx','dd024ed1472c827cecdd1e4491a4ea1ecae86b22c84e5720f80f13426a8c0a98','completed',1,0,1,0.00,NULL,1,'2026-07-14 12:56:50','2026-07-14 12:56:50'),(13,'result','month',2026,3,7,'du-lieu-kiem-thu-13.xlsx','private/imports/kiem-thu-13.xlsx','5de299b8beb678665914339d2814b42298c2c7e1268724ab56518d5e03d600b2','completed',1,0,1,0.00,NULL,1,'2026-07-14 12:58:06','2026-07-14 12:58:06'),(14,'result','month',2026,3,7,'du-lieu-kiem-thu-14.xlsx','private/imports/kiem-thu-14.xlsx','8e23e79decdb7b2265f178871a4af452bc9d6ff8222bb5b523d2244db62862c5','completed',1,1,0,325000.00,NULL,1,'2026-07-14 12:59:31','2026-07-14 12:59:31'),(15,'result','month',2026,3,7,'du-lieu-kiem-thu-15.xlsx','private/imports/kiem-thu-15.xlsx','829039d35f37dbaca97d70b80966c52f4ff18a3501eefe4beb6661a785065548','completed',6,6,0,1950000.00,NULL,1,'2026-07-14 13:02:13','2026-07-14 13:02:13'),(16,'result','month',2026,3,7,'du-lieu-kiem-thu-16.xlsx','private/imports/kiem-thu-16.xlsx','7edd35efe309eca1147b20ecdcd5d1a5ffc58da05f7e240da771cb8557b97b23','completed',6,6,0,1950000.00,NULL,1,'2026-07-14 13:06:33','2026-07-14 13:06:33'),(17,'result','month',2026,3,7,'du-lieu-kiem-thu-17.xlsx','private/imports/kiem-thu-17.xlsx','83e35b3c3c964814ff82340f1c732f7f4f65dcc93058c48e4bcb3f883e8a2c48','completed',6,6,0,1950000.00,NULL,1,'2026-07-14 13:07:51','2026-07-14 13:07:51');
/*!40000 ALTER TABLE `import_batches` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
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
-- Dumping data for table `kpi_plans`
--

LOCK TABLES `kpi_plans` WRITE;
/*!40000 ALTER TABLE `kpi_plans` DISABLE KEYS */;
INSERT INTO `kpi_plans` VALUES (1,2026,'Kế hoạch chỉ tiêu năm 2026','active','quarter','Kế hoạch mẫu',1,'2026-07-14 08:20:24','2026-07-14 08:20:24');
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
-- Dumping data for table `kpi_records`
--

LOCK TABLES `kpi_records` WRITE;
/*!40000 ALTER TABLE `kpi_records` DISABLE KEYS */;
INSERT INTO `kpi_records` VALUES (26,14,2,47,55,585,'Học viên kiểm thử 26','B1-TVU',1.00,325000.00,'PT-KT-000026','2026-07-14',2026,3,7,1.00,2.00,'full_group',NULL,1,'2026-07-14 12:59:31','2026-07-14 13:03:55','2026-07-14 13:03:55'),(33,16,2,47,55,585,'Học viên kiểm thử 33','B1-TVU',1.00,325000.00,'PT-KT-000033','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:06:33','2026-07-14 13:06:33',NULL),(34,16,3,47,55,585,'Học viên kiểm thử 34','B1-TVU',1.00,325000.00,'PT-KT-000034','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:06:33','2026-07-14 13:06:33',NULL),(35,16,4,47,55,585,'Học viên kiểm thử 35','B1-TVU',1.00,325000.00,'PT-KT-000035','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:06:33','2026-07-14 13:06:33',NULL),(36,16,5,47,55,585,'Học viên kiểm thử 36','B1-TVU',1.00,325000.00,'PT-KT-000036','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:06:33','2026-07-14 13:06:33',NULL),(37,16,6,47,55,585,'Học viên kiểm thử 37','B1-TVU',1.00,325000.00,'PT-KT-000037','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:06:33','2026-07-14 13:06:33',NULL),(38,16,7,47,55,585,'Học viên kiểm thử 38','B1-TVU',1.00,325000.00,'PT-KT-000038','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:06:33','2026-07-14 13:06:33',NULL),(39,17,2,55,NULL,585,'Học viên kiểm thử 39','B1-TVU',1.00,325000.00,'PT-KT-000039','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:07:51','2026-07-14 13:07:51',NULL),(40,17,3,55,NULL,585,'Học viên kiểm thử 40','B1-TVU',1.00,325000.00,'PT-KT-000040','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:07:51','2026-07-14 13:07:51',NULL),(41,17,4,55,NULL,585,'Học viên kiểm thử 41','B1-TVU',1.00,325000.00,'PT-KT-000041','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:07:51','2026-07-14 13:07:51',NULL),(42,17,5,55,NULL,585,'Học viên kiểm thử 42','B1-TVU',1.00,325000.00,'PT-KT-000042','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:07:51','2026-07-14 13:07:51',NULL),(43,17,6,55,NULL,585,'Học viên kiểm thử 43','B1-TVU',1.00,325000.00,'PT-KT-000043','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:07:51','2026-07-14 13:07:51',NULL),(44,17,7,55,NULL,585,'Học viên kiểm thử 44','B1-TVU',1.00,325000.00,'PT-KT-000044','2026-07-14',2026,3,7,1.00,0.50,'proportional',NULL,1,'2026-07-14 13:07:51','2026-07-14 13:07:51',NULL);
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
-- Dumping data for table `kpi_targets`
--

LOCK TABLES `kpi_targets` WRITE;
/*!40000 ALTER TABLE `kpi_targets` DISABLE KEYS */;
INSERT INTO `kpi_targets` VALUES (3,1,45,NULL,'year',0,0,55.00,0.00,1,0.00,NULL,1,'2026-07-14 09:13:57','2026-07-14 11:48:49','2026-07-14 11:48:49'),(4,1,45,NULL,'year',0,0,55.00,0.00,1,0.00,NULL,1,'2026-07-14 12:10:32','2026-07-14 12:38:09','2026-07-14 12:38:09'),(5,1,46,NULL,'year',0,0,55.00,0.00,1,0.00,NULL,1,'2026-07-14 12:10:48','2026-07-14 12:10:48',NULL),(6,1,53,NULL,'year',0,0,42.00,0.00,1,0.00,NULL,1,'2026-07-14 12:27:13','2026-07-14 12:27:13',NULL),(7,1,51,NULL,'year',0,0,42.00,0.00,1,0.00,NULL,1,'2026-07-14 12:27:21','2026-07-14 12:27:21',NULL),(8,1,52,NULL,'year',0,0,42.00,0.00,1,0.00,NULL,1,'2026-07-14 12:27:32','2026-07-14 12:27:32',NULL),(9,1,47,NULL,'year',0,0,55.00,0.00,1,0.00,NULL,1,'2026-07-14 12:27:48','2026-07-14 12:27:48',NULL),(10,1,48,NULL,'year',0,0,42.00,0.00,1,0.00,NULL,1,'2026-07-14 12:28:01','2026-07-14 12:28:01',NULL),(11,1,50,NULL,'year',0,0,42.00,0.00,1,0.00,NULL,1,'2026-07-14 12:28:21','2026-07-14 12:28:21',NULL),(12,1,49,NULL,'year',0,0,55.00,0.00,1,0.00,NULL,1,'2026-07-14 12:31:12','2026-07-14 12:31:12',NULL),(13,1,54,NULL,'year',0,0,55.00,0.00,1,0.00,NULL,1,'2026-07-14 12:31:21','2026-07-14 12:31:21',NULL),(14,1,45,NULL,'year',0,0,55.00,0.00,1,0.00,NULL,1,'2026-07-14 12:38:20','2026-07-14 12:38:20',NULL);
/*!40000 ALTER TABLE `kpi_targets` ENABLE KEYS */;
UNLOCK TABLES;

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
  `completion_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_classes`
--

LOCK TABLES `language_classes` WRITE;
/*!40000 ALTER TABLE `language_classes` DISABLE KEYS */;
INSERT INTO `language_classes` VALUES (1,'AL1','Mầm non 1',2,1,NULL,13,'R03','2026-07-21','2026-07-31',12,24,'2026-07-22 17:28:44',13,NULL,'2026-07-22 17:32:27',1,20,630000.00,'completed',NULL,NULL,'2026-07-21 05:17:10','2026-07-22 17:32:27',NULL),(2,'A1','Tiếng Trung giao tiếp',NULL,1,NULL,13,NULL,'2026-07-22',NULL,12,24,NULL,NULL,NULL,NULL,NULL,20,0.00,'completed',NULL,NULL,'2026-07-22 16:39:37','2026-07-22 17:17:08',NULL),(3,'THTN01','Tin học thiếu nhi',3,2,NULL,14,NULL,'2026-07-23',NULL,12,24,'2026-07-22 17:46:55',14,NULL,NULL,NULL,15,630000.00,'recruiting',NULL,NULL,'2026-07-22 17:39:20','2026-07-22 17:46:56',NULL),(4,'THTN.54','TIN HỌC HÈ',4,1,1,14,NULL,'2026-07-23',NULL,12,0,NULL,NULL,NULL,NULL,NULL,20,630000.00,'upcoming',NULL,NULL,'2026-07-22 17:59:46','2026-07-22 17:59:46',NULL);
/*!40000 ALTER TABLE `language_classes` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_collaborators`
--

LOCK TABLES `language_collaborators` WRITE;
/*!40000 ALTER TABLE `language_collaborators` DISABLE KEYS */;
INSERT INTO `language_collaborators` VALUES (2,45,'CTV0002','Cộng tác viên kiểm thử 2','0940000002','collaborator2@example.test','Địa chỉ kiểm thử',0.00,1,NULL,'2026-07-21 03:33:10','2026-07-22 16:24:22',NULL),(3,NULL,'CTV0003','Cộng tác viên kiểm thử 3','0940000003','collaborator3@example.test','Địa chỉ kiểm thử',0.00,1,NULL,'2026-07-21 06:11:47','2026-07-21 06:11:47',NULL),(4,55,'CTV0004','Cộng tác viên kiểm thử 4','0940000004','collaborator4@example.test','Địa chỉ kiểm thử',0.00,1,NULL,'2026-07-21 06:52:23','2026-07-21 06:52:23',NULL),(5,53,'CTV0005','Cộng tác viên kiểm thử 5','0940000005','collaborator5@example.test','Địa chỉ kiểm thử',0.00,1,NULL,'2026-07-21 06:54:39','2026-07-21 07:00:10',NULL);
/*!40000 ALTER TABLE `language_collaborators` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_courses`
--

LOCK TABLES `language_courses` WRITE;
/*!40000 ALTER TABLE `language_courses` DISABLE KEYS */;
INSERT INTO `language_courses` VALUES (2,'KH-2026-00001',1,NULL,'Tiếng Anh mẫu giáo (5-6 tuổi)','Fingerprints 1',630000.00,24.00,12,1,NULL,'2026-07-21 03:46:11','2026-07-21 03:46:11',NULL),(3,'KH-2026-00003',2,NULL,'Tin học thiếu nhi',NULL,630000.00,24.00,12,1,NULL,'2026-07-22 17:37:38','2026-07-22 17:37:38',NULL),(4,'KH-2026-00004',1,1,'Tin học hè','ko',630000.00,24.00,12,1,NULL,'2026-07-22 17:59:04','2026-07-22 17:59:04',NULL);
/*!40000 ALTER TABLE `language_courses` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_discount_policies`
--

LOCK TABLES `language_discount_policies` WRITE;
/*!40000 ALTER TABLE `language_discount_policies` DISABLE KEYS */;
INSERT INTO `language_discount_policies` VALUES (1,'MG-2026-00001','Học viên được nhận voucher miễn giảm',25.00,'Tất cả',NULL,NULL,1,NULL,'2026-07-21 05:20:10','2026-07-21 05:20:10',NULL),(2,'MG-2026-00002','Giảm 5%',5.00,'học phí cho học viên đăng kí mới',NULL,NULL,1,NULL,'2026-07-21 05:20:37','2026-07-21 05:20:37',NULL);
/*!40000 ALTER TABLE `language_discount_policies` ENABLE KEYS */;
UNLOCK TABLES;

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
  `exit_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_enrollment_class_student_uq` (`language_class_id`,`language_student_id`),
  KEY `language_enrollments_language_student_id_foreign` (`language_student_id`),
  CONSTRAINT `language_enrollments_language_class_id_foreign` FOREIGN KEY (`language_class_id`) REFERENCES `language_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `language_enrollments_language_student_id_foreign` FOREIGN KEY (`language_student_id`) REFERENCES `language_students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_enrollments`
--

LOCK TABLES `language_enrollments` WRITE;
/*!40000 ALTER TABLE `language_enrollments` DISABLE KEYS */;
INSERT INTO `language_enrollments` VALUES (1,1,6,'2026-07-21','2026-07-23',630000.00,0.00,'completed','Lớp đã được giáo vụ xác nhận hoàn thành',NULL,'2026-07-21 05:18:15','2026-07-22 17:32:27'),(2,1,8,'2026-07-22','2026-07-23',630000.00,0.00,'completed','Lớp đã được giáo vụ xác nhận hoàn thành',NULL,'2026-07-21 07:24:33','2026-07-22 17:32:27'),(3,1,3,'2026-07-22','2026-07-23',630000.00,0.00,'completed','Lớp đã được giáo vụ xác nhận hoàn thành',NULL,'2026-07-22 05:32:27','2026-07-22 17:32:27'),(4,1,9,'2026-07-22','2026-07-23',630000.00,0.00,'completed','Lớp đã được giáo vụ xác nhận hoàn thành',NULL,'2026-07-22 05:33:47','2026-07-22 17:32:27'),(5,1,11,'2026-07-22','2026-07-23',630000.00,0.00,'completed','Lớp đã được giáo vụ xác nhận hoàn thành',NULL,'2026-07-22 16:38:28','2026-07-22 17:32:27'),(6,2,11,'2026-07-22','2026-07-23',0.00,0.00,'completed','Lớp đã hoàn thành',NULL,'2026-07-22 16:40:06','2026-07-22 17:17:08'),(7,2,8,'2026-07-22','2026-07-23',0.00,0.00,'completed','Lớp đã hoàn thành',NULL,'2026-07-22 16:40:41','2026-07-22 17:17:08'),(9,3,8,'2026-07-23',NULL,630000.00,0.00,'studying',NULL,NULL,'2026-07-22 17:39:54','2026-07-22 17:39:54'),(10,3,6,'2026-07-23',NULL,630000.00,0.00,'studying',NULL,NULL,'2026-07-22 17:40:00','2026-07-22 17:40:45'),(11,3,4,'2026-07-23',NULL,630000.00,0.00,'studying',NULL,NULL,'2026-07-22 17:40:06','2026-07-22 17:40:06'),(12,3,5,'2026-07-23',NULL,630000.00,0.00,'studying',NULL,NULL,'2026-07-22 17:40:52','2026-07-22 17:40:52'),(13,4,11,'2026-07-23',NULL,630000.00,0.00,'studying',NULL,NULL,'2026-07-22 18:00:20','2026-07-22 18:00:20'),(14,4,8,'2026-07-23',NULL,630000.00,0.00,'studying',NULL,NULL,'2026-07-22 18:05:21','2026-07-22 18:05:21');
/*!40000 ALTER TABLE `language_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_guardians`
--

LOCK TABLES `language_guardians` WRITE;
/*!40000 ALTER TABLE `language_guardians` DISABLE KEYS */;
/*!40000 ALTER TABLE `language_guardians` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_leads`
--

LOCK TABLES `language_leads` WRITE;
/*!40000 ALTER TABLE `language_leads` DISABLE KEYS */;
INSERT INTO `language_leads` VALUES (2,'KH-2026-00001','Khách hàng kiểm thử 2',NULL,'0920000002','lead2@example.test',NULL,NULL,'2026-07-21',NULL,2,3,6,2,NULL,NULL,'registered',NULL,NULL,'2026-07-21 03:47:01','2026-07-21 03:59:46',NULL),(3,'KH-2026-00003','Khách hàng kiểm thử 3',NULL,'0920000003','lead3@example.test',NULL,NULL,'2026-07-21',NULL,2,4,13,2,NULL,NULL,'registered',NULL,NULL,'2026-07-21 04:17:25','2026-07-21 04:33:22',NULL),(4,'KH-2026-00004','Khách hàng kiểm thử 4',NULL,'0920000004','lead4@example.test',NULL,NULL,'2026-07-21',NULL,2,5,13,2,NULL,NULL,'registered',NULL,NULL,'2026-07-21 04:53:31','2026-07-21 04:55:06',NULL),(5,'KH-2026-00005','Khách hàng kiểm thử 5',NULL,'0920000005','lead5@example.test',NULL,NULL,'2026-07-21',NULL,2,6,13,2,NULL,NULL,'registered',NULL,NULL,'2026-07-21 05:17:42','2026-07-21 05:17:53',NULL),(8,'KH-2026-00006','Khách hàng kiểm thử 8',NULL,'0920000008','lead8@example.test',NULL,NULL,'2026-07-21',NULL,NULL,NULL,6,5,NULL,NULL,'new',NULL,NULL,'2026-07-21 05:58:36','2026-07-21 06:54:39',NULL),(9,'KH-2026-00009','Khách hàng kiểm thử 9',NULL,'0920000009','lead9@example.test',NULL,NULL,'2026-07-21',NULL,NULL,NULL,6,5,NULL,NULL,'new',NULL,NULL,'2026-07-21 06:48:46','2026-07-21 06:54:39',NULL),(10,'KH-2026-00010','Khách hàng kiểm thử 10',NULL,'0920000010','lead10@example.test',NULL,NULL,'2026-07-21',NULL,2,7,6,3,NULL,NULL,'registered',NULL,NULL,'2026-07-21 06:53:05','2026-07-21 07:23:45',NULL),(11,'KH-2026-00011','Khách hàng kiểm thử 11',NULL,'0920000011','lead11@example.test',NULL,NULL,'2026-07-21',NULL,2,NULL,6,5,NULL,NULL,'not_interested',NULL,NULL,'2026-07-21 06:55:08','2026-07-21 07:36:27',NULL),(12,'KH-2026-00012','Khách hàng kiểm thử 12',NULL,'0920000012','lead12@example.test',NULL,NULL,'2026-07-21',NULL,2,NULL,6,5,NULL,NULL,'new',NULL,NULL,'2026-07-21 07:00:49','2026-07-21 07:00:49',NULL),(13,'KH-2026-00013','Khách hàng kiểm thử 13',NULL,'0920000013','lead13@example.test',NULL,NULL,'2026-07-21',NULL,2,NULL,6,5,NULL,'2026-07-22 20:38:00','placement_test',NULL,NULL,'2026-07-21 07:01:48','2026-07-22 13:38:00',NULL),(14,'KH-2026-00014','Khách hàng kiểm thử 14',NULL,'0920000014','lead14@example.test',NULL,NULL,'2026-07-21',NULL,2,8,6,5,NULL,NULL,'registered',NULL,NULL,'2026-07-21 07:22:46','2026-07-22 05:34:53',NULL),(15,'KH-2026-00015','Khách hàng kiểm thử 15',NULL,'0920000015','lead15@example.test',NULL,NULL,'2026-07-21',NULL,2,9,6,5,NULL,NULL,'registered',NULL,NULL,'2026-07-21 07:36:49','2026-07-21 07:42:11',NULL);
/*!40000 ALTER TABLE `language_leads` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_levels`
--

LOCK TABLES `language_levels` WRITE;
/*!40000 ALTER TABLE `language_levels` DISABLE KEYS */;
INSERT INTO `language_levels` VALUES (1,1,'a1','Cấp độ kiểm thử 1',1,1.00,1.00,NULL,NULL,'2026-07-22 05:33:00','2026-07-22 05:33:00',NULL);
/*!40000 ALTER TABLE `language_levels` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_monthly_target_records`
--

LOCK TABLES `language_monthly_target_records` WRITE;
/*!40000 ALTER TABLE `language_monthly_target_records` DISABLE KEYS */;
INSERT INTO `language_monthly_target_records` VALUES (2,2026,7,3,2,2,2,2,1.00,630000.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00001','2026-07-21 04:01:00','2026-07-21 07:34:10'),(3,2026,7,5,4,2,2,3,1.00,630000.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00004','2026-07-21 05:09:48','2026-07-21 07:34:10'),(4,2026,7,6,5,2,2,4,1.00,598500.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00005','2026-07-21 05:30:03','2026-07-21 07:34:10'),(5,2026,7,8,14,5,2,5,1.00,598500.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00007','2026-07-21 07:26:48','2026-07-21 07:34:10'),(6,2026,7,9,15,5,2,6,1.00,472500.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00008','2026-07-22 04:18:47','2026-07-22 04:18:47'),(7,2026,7,6,5,2,2,7,1.00,630000.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00009','2026-07-22 04:19:25','2026-07-22 04:19:25'),(8,2026,7,4,3,2,2,10,1.00,630000.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00003','2026-07-22 04:19:44','2026-07-22 04:19:44'),(9,2026,7,8,14,5,2,11,1.00,472500.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00011','2026-07-22 04:20:18','2026-07-22 04:20:18'),(10,2026,7,6,14,5,2,8,1.00,598500.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00010','2026-07-22 04:36:07','2026-07-22 04:36:07'),(11,2026,7,6,14,5,2,12,1.00,598500.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00012','2026-07-22 05:28:25','2026-07-22 05:28:25'),(12,2026,7,6,5,2,2,9,1.00,630000.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00006','2026-07-22 05:30:27','2026-07-22 05:30:27'),(13,2026,7,8,14,5,2,13,1.00,598500.00,'Tự động ghi nhận khi hoàn tất học phí HP-2026-00013','2026-07-22 05:34:53','2026-07-22 05:34:53'),(14,2026,7,3,2,2,2,16,1.00,630000.00,'Thu học phí HP-2026-00016','2026-07-22 17:31:31','2026-07-22 17:31:31'),(15,2026,7,11,NULL,NULL,2,15,1.00,630000.00,'Thu học phí HP-2026-00015','2026-07-22 17:32:08','2026-07-22 17:32:08'),(16,2026,7,4,3,2,3,17,1.00,598500.00,'Thu học phí HP-2026-00020','2026-07-22 17:44:26','2026-07-22 17:44:26');
/*!40000 ALTER TABLE `language_monthly_target_records` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_programs`
--

LOCK TABLES `language_programs` WRITE;
/*!40000 ALTER TABLE `language_programs` DISABLE KEYS */;
INSERT INTO `language_programs` VALUES (1,'CC1','Tiếng anh thiếu nhi 1','5-6 tuổi',NULL,1,'2026-07-21 05:16:12','2026-07-21 05:16:12',NULL),(2,'THTN01','Tin học thiếu nhi','lớp 3-9',NULL,1,'2026-07-22 17:38:19','2026-07-22 17:38:19',NULL);
/*!40000 ALTER TABLE `language_programs` ENABLE KEYS */;
UNLOCK TABLES;

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
  `assessment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `learning_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
-- Dumping data for table `language_student_monthly_progress`
--

LOCK TABLES `language_student_monthly_progress` WRITE;
/*!40000 ALTER TABLE `language_student_monthly_progress` DISABLE KEYS */;
INSERT INTO `language_student_monthly_progress` VALUES (2,7,'2026-07-01',12,11,8.00,8.00,'oke','oke',13,'2026-07-22 16:42:03','2026-07-22 16:42:03'),(3,6,'2026-07-01',12,11,10.00,7.00,'oke','oke',13,'2026-07-22 16:42:03','2026-07-22 16:57:05');
/*!40000 ALTER TABLE `language_student_monthly_progress` ENABLE KEYS */;
UNLOCK TABLES;

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
  `test_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `score` decimal(6,2) NOT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT '10.00',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `language_student_scores_teacher_user_id_foreign` (`teacher_user_id`),
  KEY `language_student_scores_language_enrollment_id_test_date_index` (`language_enrollment_id`,`test_date`),
  CONSTRAINT `language_student_scores_language_enrollment_id_foreign` FOREIGN KEY (`language_enrollment_id`) REFERENCES `language_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `language_student_scores_teacher_user_id_foreign` FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_student_scores`
--

LOCK TABLES `language_student_scores` WRITE;
/*!40000 ALTER TABLE `language_student_scores` DISABLE KEYS */;
INSERT INTO `language_student_scores` VALUES (6,7,'2026-07-22','a','regular',2.00,10.00,NULL,1,'2026-07-22 16:52:04','2026-07-22 16:52:04'),(7,7,'2026-07-22','kiểm tra nói','midterm',8.00,10.00,NULL,1,'2026-07-22 16:55:33','2026-07-22 16:55:33'),(8,7,'2026-07-22','kiểm tra viết','regular',6.00,10.00,NULL,13,'2026-07-22 16:56:22','2026-07-22 16:56:22'),(9,6,'2026-07-22','kiểm tra nói','regular',5.00,10.00,NULL,13,'2026-07-22 16:56:39','2026-07-22 16:56:39');
/*!40000 ALTER TABLE `language_student_scores` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_students`
--

LOCK TABLES `language_students` WRITE;
/*!40000 ALTER TABLE `language_students` DISABLE KEYS */;
INSERT INTO `language_students` VALUES (3,'HV0003','Học viên kiểm thử 3',NULL,NULL,'Trường kiểm thử',NULL,'0910000003','student3@example.test','Địa chỉ kiểm thử','2026-07-21',NULL,NULL,2,NULL,'new',NULL,'2026-07-21 03:59:46','2026-07-22 17:31:11',NULL),(4,'HV0004','Học viên kiểm thử 4',NULL,NULL,'Trường kiểm thử',NULL,'0910000004','student4@example.test','Địa chỉ kiểm thử','2026-07-21',NULL,NULL,3,2,'new',NULL,'2026-07-21 04:33:22','2026-07-22 17:42:50',NULL),(5,'HV0005','Học viên kiểm thử 5',NULL,NULL,'Trường kiểm thử',NULL,'0910000005','student5@example.test','Địa chỉ kiểm thử','2026-07-21',NULL,NULL,2,NULL,'new',NULL,'2026-07-21 04:55:06','2026-07-22 05:39:24',NULL),(6,'HV0006','Học viên kiểm thử 6',NULL,NULL,'Trường kiểm thử',NULL,'0910000006','student6@example.test','Địa chỉ kiểm thử','2026-07-21',NULL,NULL,2,2,'new',NULL,'2026-07-21 05:17:53','2026-07-22 05:04:12',NULL),(7,'HV0007','Học viên kiểm thử 7',NULL,NULL,'Trường kiểm thử',NULL,'0910000007','student7@example.test','Địa chỉ kiểm thử','2026-07-21',NULL,NULL,2,NULL,'new',NULL,'2026-07-21 07:23:45','2026-07-21 07:23:45',NULL),(8,'HV0008','Học viên kiểm thử 8',NULL,NULL,'Trường kiểm thử',NULL,'0910000008','student8@example.test','Địa chỉ kiểm thử','2026-07-21','2026-07-21',NULL,4,NULL,'new',NULL,'2026-07-21 07:24:01','2026-07-22 18:05:58',NULL),(9,'HV0009','Học viên kiểm thử 9',NULL,NULL,'Trường kiểm thử',NULL,'0910000009','student9@example.test','Địa chỉ kiểm thử','2026-07-21',NULL,NULL,2,1,'studying',NULL,'2026-07-21 07:42:11','2026-07-22 05:33:29',NULL),(11,'HV0011','Học viên kiểm thử 11','male',NULL,'Trường kiểm thử',NULL,'0910000011','student11@example.test','Địa chỉ kiểm thử','2026-07-22',NULL,NULL,4,NULL,'new',NULL,'2026-07-22 16:38:28','2026-07-22 18:02:18',NULL);
/*!40000 ALTER TABLE `language_students` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_target_submissions`
--

LOCK TABLES `language_target_submissions` WRITE;
/*!40000 ALTER TABLE `language_target_submissions` DISABLE KEYS */;
INSERT INTO `language_target_submissions` VALUES (3,'Khách hàng kiểm thử 3','0950000003','0950000003',NULL,'tin học thiếu nhi','other:tin hoc thieu nhi',8,13,'2026-07-21 05:50:05','2026-07-21 05:50:05'),(5,'Khách hàng kiểm thử 5','0950000005','0950000005',NULL,'tiếng anh thiếu nhi','other:tieng anh thieu nhi',8,13,'2026-07-21 05:58:36','2026-07-21 05:58:36'),(6,'Khách hàng kiểm thử 6','0950000006','0950000006',NULL,'udcntt','other:udcntt',9,13,'2026-07-21 06:48:46','2026-07-21 06:48:46'),(7,'Khách hàng kiểm thử 7','0950000007','0950000007',2,NULL,'course:2',10,13,'2026-07-21 06:53:05','2026-07-21 06:53:05'),(8,'Khách hàng kiểm thử 8','0950000008','0950000008',2,NULL,'course:2',11,13,'2026-07-21 06:55:08','2026-07-21 06:55:08'),(9,'Khách hàng kiểm thử 9','0950000009','0950000009',2,NULL,'course:2',12,13,'2026-07-21 07:00:49','2026-07-21 07:00:49'),(10,'Khách hàng kiểm thử 10','0950000010','0950000010',2,NULL,'course:2',13,13,'2026-07-21 07:01:48','2026-07-21 07:01:48'),(11,'Khách hàng kiểm thử 11','0950000011','0950000011',2,NULL,'course:2',14,13,'2026-07-21 07:22:46','2026-07-21 07:22:46'),(12,'Khách hàng kiểm thử 12','0950000012','0950000012',2,NULL,'course:2',15,13,'2026-07-21 07:36:49','2026-07-21 07:36:49');
/*!40000 ALTER TABLE `language_target_submissions` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_tuition_charges`
--

LOCK TABLES `language_tuition_charges` WRITE;
/*!40000 ALTER TABLE `language_tuition_charges` DISABLE KEYS */;
INSERT INTO `language_tuition_charges` VALUES (2,'HP-2026-00001',3,2,2,NULL,NULL,630000.00,0.00,0.00,630000.00,630000.00,NULL,'paid',NULL,1,'2026-07-21 04:00:37','2026-07-21 07:34:10'),(3,'HP-2026-00003',4,3,2,NULL,NULL,630000.00,0.00,0.00,630000.00,630000.00,NULL,'paid',NULL,1,'2026-07-21 04:33:43','2026-07-22 04:19:44'),(4,'HP-2026-00004',5,4,2,NULL,NULL,630000.00,0.00,0.00,630000.00,630000.00,NULL,'paid',NULL,1,'2026-07-21 04:56:45','2026-07-21 05:09:48'),(5,'HP-2026-00005',6,5,2,1,2,3150000.00,5.00,94500.00,3055500.00,3055500.00,NULL,'paid',NULL,5,'2026-07-21 05:29:08','2026-07-22 17:35:30'),(7,'HP-2026-00007',8,14,2,1,2,1890000.00,5.00,220500.00,1669500.00,1669500.00,NULL,'paid',NULL,1,'2026-07-21 07:25:22','2026-07-22 17:35:30'),(8,'HP-2026-00008',9,15,2,1,1,630000.00,25.00,157500.00,472500.00,472500.00,NULL,'paid',NULL,1,'2026-07-21 07:42:20','2026-07-22 04:18:47'),(14,'HP-2026-00014',5,4,2,1,NULL,630000.00,0.00,0.00,630000.00,630000.00,NULL,'pending_receipt',NULL,1,'2026-07-22 05:39:24','2026-07-22 05:58:49'),(15,'HP-2026-00015',11,NULL,2,1,NULL,630000.00,0.00,0.00,630000.00,630000.00,NULL,'paid',NULL,1,'2026-07-22 17:04:32','2026-07-22 17:32:08'),(18,'HP-2026-00016',3,2,2,1,NULL,630000.00,0.00,0.00,630000.00,630000.00,NULL,'paid',NULL,1,'2026-07-22 17:31:11','2026-07-22 17:31:31'),(19,'HP-2026-00019',8,14,3,3,NULL,630000.00,0.00,0.00,630000.00,630000.00,NULL,'pending_receipt',NULL,1,'2026-07-22 17:42:22','2026-07-22 17:48:47'),(20,'HP-2026-00020',4,3,3,3,2,630000.00,5.00,31500.00,598500.00,598500.00,NULL,'paid',NULL,1,'2026-07-22 17:42:50','2026-07-22 17:44:26'),(21,'HP-2026-00021',11,NULL,4,4,NULL,630000.00,0.00,0.00,630000.00,0.00,NULL,'unpaid',NULL,1,'2026-07-22 18:02:18','2026-07-22 18:02:18'),(22,'HP-2026-00022',8,14,4,4,NULL,630000.00,0.00,0.00,630000.00,630000.00,NULL,'pending_receipt',NULL,1,'2026-07-22 18:05:58','2026-07-25 03:46:52');
/*!40000 ALTER TABLE `language_tuition_charges` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `language_tuition_payments`
--

LOCK TABLES `language_tuition_payments` WRITE;
/*!40000 ALTER TABLE `language_tuition_payments` DISABLE KEYS */;
INSERT INTO `language_tuition_payments` VALUES (2,'PT-KT-000002','confirmed',2,630000.00,0.00,'2026-07-21 11:00:00','2026-07-21 11:00:00','cash',NULL,1,NULL,'2026-07-21 04:01:00','2026-07-21 04:01:00'),(3,'PT-KT-000003','confirmed',4,630000.00,0.00,'2026-07-21 12:09:00','2026-07-21 12:09:48','transfer',NULL,1,NULL,'2026-07-21 05:09:24','2026-07-21 05:09:48'),(4,'PT-KT-000004','confirmed',5,598500.00,0.00,'2026-07-21 12:29:00','2026-07-21 12:30:03','transfer',NULL,5,NULL,'2026-07-21 05:29:26','2026-07-21 05:30:03'),(5,'PT-KT-000005','confirmed',7,598500.00,0.00,'2026-07-21 14:25:00','2026-07-21 14:26:48','transfer',NULL,1,NULL,'2026-07-21 07:26:27','2026-07-21 07:26:48'),(6,'PT-KT-000006','confirmed',8,472500.00,0.00,'2026-07-21 14:42:00','2026-07-22 11:18:47','transfer',NULL,1,NULL,'2026-07-21 07:42:33','2026-07-22 04:18:47'),(7,'PT-KT-000007','confirmed',5,630000.00,0.00,'2026-07-22 10:42:00','2026-07-22 11:19:25','transfer',NULL,1,NULL,'2026-07-22 03:42:21','2026-07-22 04:19:25'),(8,'PT-KT-000008','confirmed',5,598500.00,0.00,'2026-07-22 10:50:00','2026-07-22 11:36:07','transfer',NULL,1,NULL,'2026-07-22 03:50:38','2026-07-22 04:36:07'),(9,'PT-KT-000009','confirmed',5,630000.00,0.00,'2026-07-22 11:05:00','2026-07-22 12:30:27','cash',NULL,1,NULL,'2026-07-22 04:05:31','2026-07-22 05:30:27'),(10,'PT-KT-000010','confirmed',3,630000.00,0.00,'2026-07-22 11:09:00','2026-07-22 11:19:44','cash',NULL,1,NULL,'2026-07-22 04:09:55','2026-07-22 04:19:44'),(11,'PT-KT-000011','confirmed',7,472500.00,0.00,'2026-07-22 11:20:00','2026-07-22 11:20:18','cash',NULL,1,NULL,'2026-07-22 04:20:11','2026-07-22 04:20:18'),(12,'PT-KT-000012','confirmed',5,598500.00,0.00,'2026-07-22 12:04:00','2026-07-22 12:28:25','transfer',NULL,6,NULL,'2026-07-22 05:04:28','2026-07-22 05:28:25'),(13,'PT-KT-000013','confirmed',7,598500.00,0.00,'2026-07-22 12:34:00','2026-07-22 12:34:53','transfer',NULL,1,NULL,'2026-07-22 05:34:44','2026-07-22 05:34:53'),(14,NULL,'pending',14,630000.00,20000.00,'2026-07-22 12:58:00',NULL,'transfer',NULL,1,NULL,'2026-07-22 05:58:49','2026-07-22 05:58:49'),(15,'PT-KT-000015','confirmed',15,630000.00,0.00,'2026-07-23 00:04:00','2026-07-23 00:32:08','cash',NULL,1,NULL,'2026-07-22 17:04:42','2026-07-22 17:32:08'),(16,'PT-KT-000016','confirmed',18,630000.00,0.00,'2026-07-23 00:31:00','2026-07-23 00:31:31','cash',NULL,1,NULL,'2026-07-22 17:31:20','2026-07-22 17:31:31'),(17,'PT-KT-000017','confirmed',20,598500.00,0.00,'2026-07-23 00:43:00','2026-07-23 00:44:26','cash',NULL,1,NULL,'2026-07-22 17:43:26','2026-07-22 17:44:26'),(18,NULL,'pending',19,630000.00,0.00,'2026-07-23 00:48:00',NULL,'cash',NULL,1,NULL,'2026-07-22 17:48:47','2026-07-22 17:48:47'),(19,NULL,'pending',22,630000.00,0.00,'2026-07-25 10:46:00',NULL,'cash',NULL,1,NULL,'2026-07-25 03:46:52','2026-07-25 03:46:52');
/*!40000 ALTER TABLE `language_tuition_payments` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_logs` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_07_14_000001_make_kpi_targets_global',1),(2,'2026_07_21_000001_add_language_center_modules',2),(3,'2026_07_21_000002_create_system_settings_table',3),(4,'2026_07_21_000003_add_dashboard_permissions',4),(5,'2026_07_21_000004_add_center_sales_and_tuition_modules',5),(6,'2026_07_21_000005_expand_student_consulting_and_tuition',6),(7,'2026_07_21_000006_add_received_at_to_language_leads',7),(8,'2026_07_21_000007_allow_pending_tuition_receipts',8),(9,'2026_07_21_000008_create_language_target_submissions_table',9),(10,'2026_07_21_000009_add_duplicate_keys_to_target_submissions',10),(11,'2026_07_21_000010_add_consultant_flag_to_personnels',11),(12,'2026_07_21_000011_link_target_submissions_to_leads',12),(13,'2026_07_21_000012_backfill_submission_lead_links',13),(14,'2026_07_21_000013_link_collaborators_to_personnels',14),(15,'2026_07_21_000014_backfill_submission_collaborators',15),(16,'2026_07_21_000015_link_users_to_language_collaborators',16),(17,'2026_07_21_000016_backfill_tuition_target_collaborators',17),(18,'2026_07_21_000017_add_page_level_consulting_permissions',18),(19,'2026_07_21_000018_rename_leads_to_prospective_students',19),(20,'2026_07_22_120000_create_upcoming_plans_table',20),(21,'2026_07_22_130000_add_assigner_to_upcoming_plans_table',21),(22,'2026_07_22_140000_add_kind_to_upcoming_plans_table',22),(23,'2026_07_22_150000_add_notifications_enabled_to_users_table',23),(24,'2026_07_22_160000_add_book_amount_to_language_tuition_payments',24),(25,'2026_07_22_133634_create_jobs_table',25),(26,'2026_07_22_133706_create_failed_jobs_table',26),(27,'2026_07_22_133706_create_job_batches_table',26),(28,'2026_07_22_170000_create_work_task_module',27),(29,'2026_07_22_180000_add_work_tasks_permissions',28),(30,'2026_07_22_190000_add_closing_to_work_tasks',29),(31,'2026_07_22_200000_add_student_learning_history',30),(32,'2026_07_23_000000_add_completed_sessions_to_language_classes',31),(33,'2026_07_23_010000_add_class_completion_approval',32),(34,'2026_07_23_020000_one_tuition_charge_per_student_class',33),(35,'2026_07_23_030000_link_courses_programs_levels_and_classes',34),(36,'2026_07_26_000001_add_theme_color_to_users_table',35),(37,'2026_07_26_000002_add_missing_permission_modules',36),(38,'2026_07_26_000003_create_user_preferences_table',37),(39,'2026_07_26_000004_add_zalo_login_to_users_table',38);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,'personnel','Nhân sự & cộng tác viên','bi-people-fill',10,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(2,'users','Tài khoản','bi-person-lock',20,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(3,'roles','Vai trò & quyền','bi-shield-check',30,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(4,'kpis','Chỉ tiêu KPI','bi-bullseye',40,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(5,'courses','Khóa học','bi-journal-bookmark-fill',50,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(6,'imports','Nhập Excel','bi-file-earmark-spreadsheet',60,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(7,'reports','Báo cáo','bi-bar-chart-line-fill',70,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(8,'payments','Thanh toán vượt','bi-cash-coin',80,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(9,'logs','Nhật ký hệ thống','bi-clock-history',90,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(10,'language_leads','Học viên tiềm năng','bi-person-plus',20,'2026-07-21 02:12:27','2026-07-21 08:14:40'),(11,'language_students','Học viên','bi-mortarboard',21,'2026-07-21 02:12:27','2026-07-21 02:12:27'),(12,'language_programs','Chương trình & cấp độ','bi-journal-richtext',22,'2026-07-21 02:12:27','2026-07-21 02:12:27'),(13,'language_classes','Lớp học','bi-easel2',23,'2026-07-21 02:12:27','2026-07-21 02:12:27'),(14,'system_dashboard','Tổng quan toàn hệ thống','bi-grid-1x2-fill',1,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(15,'kpi_dashboard_all','Tổng quan KPI toàn hệ thống','bi-speedometer',2,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(16,'language_dashboard_all','Tổng quan trung tâm toàn hệ thống','bi-speedometer2',3,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(17,'language_collaborators','Cộng tác viên trung tâm','bi-person-vcard',24,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(18,'language_courses','Khóa học trung tâm','bi-book',25,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(19,'language_discounts','Chế độ miễn giảm','bi-percent',26,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(20,'language_tuition','Thu học phí','bi-cash-coin',27,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(21,'language_targets','Chỉ tiêu trung tâm theo tháng','bi-clipboard-data',28,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(22,'language_consulting','Công việc tư vấn','bi-headset',18,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(23,'language_target_submissions','Gửi chỉ tiêu','bi-send-fill',19,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(24,'work_tasks','Công việc','bi-list-check',4,'2026-07-22 11:44:15','2026-07-22 11:44:15'),(25,'teacher_classes','Lớp giảng dạy & điểm','bi-journal-check',31,'2026-07-26 00:58:29','2026-07-26 00:58:29'),(26,'software_settings','Cấu hình phần mềm','bi-sliders',96,'2026-07-26 00:58:29','2026-07-26 00:58:29');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `personnels`
--

LOCK TABLES `personnels` WRITE;
/*!40000 ALTER TABLE `personnels` DISABLE KEYS */;
INSERT INTO `personnels` VALUES (1,'NV0001','Nhân sự kiểm thử 1','nhan su kiem thu 1','admin','Admin hệ thống','personnel1@example.test','0900000001',0.00,0,0,'none',0.00,1,NULL,'2026-07-14 08:20:24','2026-07-14 12:18:07',NULL),(45,'NV0045','Nhân sự kiểm thử 45','nhan su kiem thu 45','employee','Nhân viên','personnel45@example.test','0900000045',55.00,1,0,'per_student',0.00,1,NULL,'2026-07-14 09:06:29','2026-07-26 01:20:06',NULL),(46,'NV0046','Nhân sự kiểm thử 46','nhan su kiem thu 46','employee','Nhân viên','personnel46@example.test','0900000046',55.00,1,0,'none',0.00,1,NULL,'2026-07-14 12:06:18','2026-07-14 12:06:18',NULL),(47,'NV0047','Nhân sự kiểm thử 47','nhan su kiem thu 47','employee','Nhân viên','personnel47@example.test','0900000047',55.00,1,1,'none',0.00,1,NULL,'2026-07-14 12:17:08','2026-07-21 05:58:27',NULL),(48,'NV0048','Nhân sự kiểm thử 48','nhan su kiem thu 48','teacher','Giáo viên','personnel48@example.test','0900000048',42.00,1,0,'none',0.00,1,NULL,'2026-07-14 12:17:45','2026-07-14 12:17:45',NULL),(49,'NV0049','Nhân sự kiểm thử 49','nhan su kiem thu 49','leader','Phó giám đốc','personnel49@example.test','0900000049',55.00,1,0,'none',0.00,1,NULL,'2026-07-14 12:18:55','2026-07-14 12:18:55',NULL),(50,'NV0050','Nhân sự kiểm thử 50','nhan su kiem thu 50','teacher','Giáo viên','personnel50@example.test','0900000050',42.00,1,0,'none',0.00,1,NULL,'2026-07-14 12:19:18','2026-07-14 12:19:18',NULL),(51,'NV0051','Nhân sự kiểm thử 51','nhan su kiem thu 51','teacher','Giáo viên','personnel51@example.test','0900000051',42.00,1,0,'none',0.00,1,NULL,'2026-07-14 12:19:40','2026-07-14 12:19:40',NULL),(52,'NV0052','Nhân sự kiểm thử 52','nhan su kiem thu 52','teacher','Giáo viên','personnel52@example.test','0900000052',42.00,1,0,'none',0.00,1,NULL,'2026-07-14 12:20:10','2026-07-14 12:20:10',NULL),(53,'NV0053','Nhân sự kiểm thử 53','nhan su kiem thu 53','teacher','Giáo viên','personnel53@example.test','0900000053',42.00,1,0,'none',0.00,1,NULL,'2026-07-14 12:20:34','2026-07-21 07:00:00',NULL),(54,'NV0054','Nhân sự kiểm thử 54','nhan su kiem thu 54','leader','Giám đốc','personnel54@example.test','0900000054',55.00,1,0,'none',0.00,1,NULL,'2026-07-14 12:21:22','2026-07-14 12:21:22',NULL),(55,'NV0055','Nhân sự kiểm thử 55','nhan su kiem thu 55','collaborator','Cộng tác viên','personnel55@example.test','0900000055',0.00,0,0,'none',0.00,1,NULL,'2026-07-14 12:51:59','2026-07-14 13:08:24',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,5,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(2,1,6,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(3,1,4,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(4,1,9,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(5,1,8,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(6,1,1,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(7,1,7,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(8,1,3,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(9,1,2,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(16,2,1,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-21 03:28:16'),(17,2,4,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-15 03:50:21'),(18,2,5,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-15 03:50:21'),(19,2,6,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-15 03:50:21'),(20,2,7,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-15 03:50:21'),(21,2,8,1,1,1,1,1,'2026-07-14 08:20:24','2026-07-15 03:50:21'),(22,4,1,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-14 08:47:55'),(23,4,2,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-14 08:47:55'),(24,4,3,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-22 06:35:16'),(25,4,4,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-22 06:35:16'),(26,4,5,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-22 06:35:16'),(27,4,6,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-22 06:35:16'),(28,4,7,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-22 06:35:16'),(29,4,8,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-14 08:47:55'),(30,4,9,0,0,0,0,0,'2026-07-14 08:47:55','2026-07-14 08:47:55'),(31,3,1,0,0,0,0,0,'2026-07-14 08:50:15','2026-07-14 08:50:15'),(32,3,2,0,0,0,0,0,'2026-07-14 08:50:15','2026-07-21 08:04:50'),(33,3,3,0,0,0,0,0,'2026-07-14 08:50:15','2026-07-14 08:50:15'),(34,3,4,0,0,0,0,0,'2026-07-14 08:50:15','2026-07-21 08:09:16'),(35,3,5,0,0,0,0,0,'2026-07-14 08:50:15','2026-07-14 08:50:15'),(36,3,6,0,0,0,0,0,'2026-07-14 08:50:15','2026-07-14 08:50:15'),(37,3,7,1,0,0,0,0,'2026-07-14 08:50:15','2026-07-21 08:08:36'),(38,3,8,0,0,0,0,0,'2026-07-14 08:50:15','2026-07-14 08:50:15'),(39,3,9,0,0,0,0,0,'2026-07-14 08:50:15','2026-07-14 08:50:15'),(40,2,2,1,1,1,1,1,'2026-07-14 08:50:18','2026-07-15 03:50:21'),(41,2,3,1,1,1,1,1,'2026-07-14 08:50:18','2026-07-15 03:50:21'),(42,2,9,1,1,1,1,1,'2026-07-14 08:50:18','2026-07-15 03:50:21'),(43,1,10,1,1,1,1,1,'2026-07-21 02:12:27','2026-07-21 02:12:27'),(44,2,10,1,1,1,1,1,'2026-07-21 02:12:27','2026-07-21 03:28:16'),(45,4,10,0,0,0,0,0,'2026-07-21 02:12:27','2026-07-22 06:35:16'),(46,3,10,0,0,0,0,0,'2026-07-21 02:12:27','2026-07-21 02:12:27'),(47,1,11,1,1,1,1,1,'2026-07-21 02:12:27','2026-07-21 02:12:27'),(48,2,11,1,1,1,1,1,'2026-07-21 02:12:27','2026-07-21 03:28:16'),(49,4,11,0,0,0,0,0,'2026-07-21 02:12:27','2026-07-22 06:35:16'),(50,3,11,1,0,0,0,0,'2026-07-21 02:12:27','2026-07-21 02:32:34'),(51,1,12,1,1,1,1,1,'2026-07-21 02:12:27','2026-07-21 02:12:27'),(52,2,12,1,1,1,1,1,'2026-07-21 02:12:27','2026-07-21 03:28:16'),(53,4,12,0,0,0,0,0,'2026-07-21 02:12:27','2026-07-22 06:35:16'),(54,3,12,1,0,0,0,0,'2026-07-21 02:12:27','2026-07-21 02:32:34'),(55,1,13,1,1,1,1,1,'2026-07-21 02:12:27','2026-07-21 02:12:27'),(56,2,13,1,1,1,1,1,'2026-07-21 02:12:27','2026-07-21 03:28:16'),(57,4,13,0,0,0,0,0,'2026-07-21 02:12:27','2026-07-22 06:35:16'),(58,3,13,1,0,0,0,0,'2026-07-21 02:12:27','2026-07-21 02:32:34'),(59,1,14,1,1,1,1,1,'2026-07-21 02:49:46','2026-07-22 05:10:16'),(60,2,14,1,1,1,1,1,'2026-07-21 02:49:46','2026-07-21 03:28:16'),(61,4,14,0,0,0,0,0,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(62,3,14,0,0,0,0,0,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(63,1,15,1,1,1,1,1,'2026-07-21 02:49:46','2026-07-22 05:10:16'),(64,2,15,1,1,1,1,1,'2026-07-21 02:49:46','2026-07-21 03:28:16'),(65,4,15,0,0,0,0,0,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(66,3,15,0,0,0,0,0,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(67,1,16,1,1,1,1,1,'2026-07-21 02:49:46','2026-07-22 05:10:16'),(68,2,16,1,1,1,1,1,'2026-07-21 02:49:46','2026-07-21 03:28:16'),(69,4,16,0,0,0,0,0,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(70,3,16,0,0,0,0,0,'2026-07-21 02:49:46','2026-07-21 02:49:46'),(71,1,17,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(72,2,17,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:28:16'),(73,4,17,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(74,3,17,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(75,1,18,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(76,2,18,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:28:16'),(77,4,18,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(78,3,18,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(79,1,19,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(80,2,19,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:28:16'),(81,4,19,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(82,3,19,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(83,1,20,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(84,2,20,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:28:16'),(85,4,20,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(86,3,20,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(87,1,21,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(88,2,21,1,1,1,1,1,'2026-07-21 03:24:45','2026-07-21 03:28:16'),(89,4,21,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 03:24:45'),(90,3,21,0,0,0,0,0,'2026-07-21 03:24:45','2026-07-21 08:08:36'),(91,1,22,1,1,1,1,1,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(92,2,22,1,1,1,1,1,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(93,4,22,0,0,0,0,0,'2026-07-21 08:03:19','2026-07-22 06:35:16'),(94,3,22,0,0,0,0,0,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(95,1,23,1,1,1,1,1,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(96,2,23,1,1,1,1,1,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(97,4,23,0,0,0,0,0,'2026-07-21 08:03:19','2026-07-22 06:35:16'),(98,3,23,1,1,1,1,1,'2026-07-21 08:03:19','2026-07-21 08:04:50'),(99,1,24,1,1,1,1,0,'2026-07-22 11:44:15','2026-07-22 11:44:15'),(100,2,24,1,1,1,1,0,'2026-07-22 11:44:15','2026-07-22 11:44:15'),(101,4,24,1,0,1,0,0,'2026-07-22 11:44:15','2026-07-22 12:40:07'),(102,3,24,1,0,1,0,0,'2026-07-22 11:44:15','2026-07-22 11:44:15'),(103,1,25,1,0,1,0,0,'2026-07-26 00:58:29','2026-07-26 00:58:29'),(104,1,26,1,0,1,0,0,'2026-07-26 00:58:29','2026-07-26 00:58:29'),(105,2,25,1,0,1,0,0,'2026-07-26 00:58:29','2026-07-26 00:58:29'),(106,2,26,0,0,0,0,0,'2026-07-26 00:58:29','2026-07-26 00:58:29'),(107,4,25,0,0,0,0,0,'2026-07-26 00:58:29','2026-07-26 00:58:29'),(108,4,26,0,0,0,0,0,'2026-07-26 00:58:29','2026-07-26 00:58:29'),(109,3,25,1,0,1,0,0,'2026-07-26 00:58:29','2026-07-26 00:58:29'),(110,3,26,0,0,0,0,0,'2026-07-26 00:58:29','2026-07-26 00:58:29');
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
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Admin','Toàn quyền hệ thống',1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(2,'leader','Lãnh đạo','Giám đốc, Phó giám đốc và người quản lý',1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(3,'teacher','Giáo viên','Mặc định chỉ xem tổng quan cá nhân; Admin cấp thêm module nếu cần',1,'2026-07-14 08:20:24','2026-07-14 08:20:24'),(4,'staff','Nhân viên','Mặc định chỉ xem tổng quan cá nhân; Admin cấp thêm module nếu cần',1,'2026-07-14 08:20:24','2026-07-14 08:20:24');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES ('app_name','E-SKY CENTER','2026-07-22 00:17:55','2026-07-22 00:17:55'),('app_tagline','HỆ THỐNG QUẢN LÝ','2026-07-22 00:17:55','2026-07-22 00:17:59'),('bank_bin','970428','2026-07-22 05:46:22','2026-07-22 05:46:22'),('bank_enabled','0','2026-07-22 05:46:22','2026-07-22 05:46:22'),('bank_name','Nam A Bank','2026-07-22 05:46:22','2026-07-22 05:46:22'),('date_format','d/m/Y','2026-07-22 00:17:55','2026-07-22 00:17:55'),('default_per_page','100','2026-07-25 16:13:02','2026-07-26 00:42:35'),('interface_density','comfortable','2026-07-22 00:17:55','2026-07-22 00:17:55'),('loading_style','top','2026-07-22 03:28:43','2026-07-26 01:56:57'),('logo_path','uploads/branding/logo-20260722101948.png','2026-07-22 00:59:59','2026-07-22 03:19:48'),('sidebar_style','gradient','2026-07-22 00:17:55','2026-07-22 00:17:55'),('software_name','E-SKY CENTER','2026-07-22 03:14:57','2026-07-22 03:19:23'),('theme_color','green','2026-07-21 02:21:30','2026-07-26 00:56:09'),('timezone','Asia/Ho_Chi_Minh','2026-07-22 00:17:55','2026-07-22 00:17:55'),('visual_effect','standard','2026-07-26 01:56:57','2026-07-26 01:56:57');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `upcoming_plans`
--

LOCK TABLES `upcoming_plans` WRITE;
/*!40000 ALTER TABLE `upcoming_plans` DISABLE KEYS */;
INSERT INTO `upcoming_plans` VALUES (2,6,NULL,'Kế hoạch kiểm thử 2','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:05:00',1,'high','personal',NULL,'2026-07-22 05:06:04','2026-07-22 05:06:04'),(4,6,1,'Kế hoạch kiểm thử 4','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:10:00',1,'normal','task','2026-07-22 12:11:38','2026-07-22 05:11:09','2026-07-22 05:11:38'),(6,5,1,'Kế hoạch kiểm thử 6','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task',NULL,'2026-07-22 05:17:12','2026-07-22 05:17:12'),(7,6,1,'Kế hoạch kiểm thử 7','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task','2026-07-22 12:17:25','2026-07-22 05:17:12','2026-07-22 05:17:25'),(8,7,1,'Kế hoạch kiểm thử 8','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task',NULL,'2026-07-22 05:17:12','2026-07-22 05:17:12'),(9,8,1,'Kế hoạch kiểm thử 9','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task',NULL,'2026-07-22 05:17:12','2026-07-22 05:17:12'),(10,9,1,'Kế hoạch kiểm thử 10','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task',NULL,'2026-07-22 05:17:12','2026-07-22 05:17:12'),(11,10,1,'Kế hoạch kiểm thử 11','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task',NULL,'2026-07-22 05:17:12','2026-07-22 05:27:36'),(12,11,1,'Kế hoạch kiểm thử 12','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task',NULL,'2026-07-22 05:17:12','2026-07-22 05:17:12'),(13,12,1,'Kế hoạch kiểm thử 13','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task',NULL,'2026-07-22 05:17:12','2026-07-22 05:17:12'),(14,13,1,'Kế hoạch kiểm thử 14','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task',NULL,'2026-07-22 05:17:12','2026-07-22 05:17:12'),(15,14,1,'Kế hoạch kiểm thử 15','Nội dung giả lập phục vụ kiểm thử','2026-07-23 12:16:00',1,'normal','task','2026-07-22 12:20:21','2026-07-22 05:17:12','2026-07-22 05:20:21'),(36,5,5,'Kế hoạch kiểm thử 36','Nội dung giả lập phục vụ kiểm thử','2026-07-26 22:44:00',1,'high','personal',NULL,'2026-07-25 15:45:09','2026-07-25 15:49:22'),(49,1,1,'Kế hoạch kiểm thử 49','Nội dung giả lập phục vụ kiểm thử','2026-07-27 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(50,1,1,'Kế hoạch kiểm thử 50','Nội dung giả lập phục vụ kiểm thử','2026-08-03 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(51,1,1,'Kế hoạch kiểm thử 51','Nội dung giả lập phục vụ kiểm thử','2026-08-10 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(52,1,1,'Kế hoạch kiểm thử 52','Nội dung giả lập phục vụ kiểm thử','2026-08-17 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(53,1,1,'Kế hoạch kiểm thử 53','Nội dung giả lập phục vụ kiểm thử','2026-08-24 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(54,1,1,'Kế hoạch kiểm thử 54','Nội dung giả lập phục vụ kiểm thử','2026-08-31 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(55,1,1,'Kế hoạch kiểm thử 55','Nội dung giả lập phục vụ kiểm thử','2026-09-07 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(56,1,1,'Kế hoạch kiểm thử 56','Nội dung giả lập phục vụ kiểm thử','2026-09-14 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(57,1,1,'Kế hoạch kiểm thử 57','Nội dung giả lập phục vụ kiểm thử','2026-09-21 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:22','2026-07-26 01:54:22'),(58,1,1,'Kế hoạch kiểm thử 58','Nội dung giả lập phục vụ kiểm thử','2026-09-28 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:23','2026-07-26 01:54:23'),(59,1,1,'Kế hoạch kiểm thử 59','Nội dung giả lập phục vụ kiểm thử','2026-10-05 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:23','2026-07-26 01:54:23'),(60,1,1,'Kế hoạch kiểm thử 60','Nội dung giả lập phục vụ kiểm thử','2026-10-12 08:54:00',1,'normal','personal',NULL,'2026-07-26 01:54:23','2026-07-26 01:54:23');
/*!40000 ALTER TABLE `upcoming_plans` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
INSERT INTO `user_permissions` VALUES (5,5,1,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(6,5,2,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(7,5,3,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(8,5,4,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(9,5,5,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(10,5,6,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(11,5,7,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(12,5,8,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(13,5,9,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(14,5,10,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(15,5,11,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(16,5,12,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(17,5,13,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(18,5,14,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(19,5,15,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(20,5,16,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(21,5,17,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(22,5,18,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(23,5,19,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(24,5,20,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(25,5,21,1,1,1,1,1,'2026-07-21 03:28:05','2026-07-21 03:28:05'),(34,5,22,1,1,1,1,1,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(37,5,23,1,1,1,1,1,'2026-07-21 08:03:19','2026-07-21 08:03:19'),(40,14,11,1,1,1,1,1,'2026-07-22 17:46:15','2026-07-22 17:46:15'),(41,14,13,1,1,1,1,1,'2026-07-22 17:46:15','2026-07-22 17:46:15');
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `key` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_preferences_user_id_key_unique` (`user_id`,`key`),
  CONSTRAINT `user_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
INSERT INTO `user_preferences` VALUES (2,14,'landing_page','welcome','2026-07-26 01:07:45','2026-07-26 01:07:45'),(3,14,'sidebar_mode','expanded','2026-07-26 01:07:45','2026-07-26 01:07:57'),(4,14,'visual_effect','glass','2026-07-26 01:16:42','2026-07-26 01:16:58'),(5,1,'landing_page','welcome','2026-07-26 01:22:21','2026-07-26 01:22:21'),(6,1,'sidebar_mode','remember','2026-07-26 01:22:21','2026-07-26 01:22:21');
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
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
  `theme_color` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,NULL,1,'Tài khoản kiểm thử 1','user1@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 08:20:24','2026-07-26 01:39:36',NULL),(5,54,NULL,2,'Tài khoản kiểm thử 5','user5@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:22:35','2026-07-25 15:43:41',NULL),(6,47,NULL,4,'Tài khoản kiểm thử 6','user6@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,0,NULL,1,NULL,NULL,NULL,'2026-07-14 12:23:02','2026-07-22 06:35:49',NULL),(7,48,NULL,3,'Tài khoản kiểm thử 7','user7@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:23:29','2026-07-14 12:36:09',NULL),(8,49,NULL,2,'Tài khoản kiểm thử 8','user8@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:23:52','2026-07-15 03:55:02',NULL),(9,50,NULL,3,'Tài khoản kiểm thử 9','user9@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:24:20','2026-07-14 12:36:03',NULL),(10,51,NULL,3,'Tài khoản kiểm thử 10','user10@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:24:41','2026-07-14 12:35:38',NULL),(11,52,NULL,3,'Tài khoản kiểm thử 11','user11@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:25:05','2026-07-14 12:35:53',NULL),(12,46,NULL,4,'Tài khoản kiểm thử 12','user12@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:25:32','2026-07-14 12:28:02',NULL),(13,53,5,3,'Tài khoản kiểm thử 13','user13@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:25:59','2026-07-22 16:37:06',NULL),(14,45,2,4,'Tài khoản kiểm thử 14','user14@example.test',NULL,NULL,NULL,NULL,'$2y$12$..bocohFTWTkzReRwCqpHuJ.0XDy.jcd09yomB/2sxiNN1B14xgV2',1,1,NULL,1,NULL,NULL,NULL,'2026-07-14 12:26:25','2026-07-26 00:54:29',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_task_activities`
--

LOCK TABLES `work_task_activities` WRITE;
/*!40000 ALTER TABLE `work_task_activities` DISABLE KEYS */;
INSERT INTO `work_task_activities` VALUES (24,3,1,'created','Đã tạo và giao công việc cho 1 người.','2026-07-22 12:50:58','2026-07-22 12:50:58'),(25,3,14,'acknowledged','Đã xác nhận nhận công việc.','2026-07-22 12:52:27','2026-07-22 12:52:27'),(26,3,1,'comment','Đã gửi một phản hồi.','2026-07-22 12:52:47','2026-07-22 12:52:47'),(27,3,14,'comment','Đã gửi một phản hồi.','2026-07-22 12:53:06','2026-07-22 12:53:06'),(28,3,14,'status','Đã hoàn thành công việc.','2026-07-22 12:53:54','2026-07-22 12:53:54'),(29,3,1,'closed','Đã đóng task.','2026-07-22 12:54:15','2026-07-22 12:54:15'),(42,7,1,'created','Đã tạo và giao công việc cho 1 người.','2026-07-26 01:41:58','2026-07-26 01:41:58'),(43,7,14,'acknowledged','Đã xác nhận nhận công việc.','2026-07-26 01:42:42','2026-07-26 01:42:42'),(44,7,14,'status','Đã hoàn thành công việc.','2026-07-26 01:43:00','2026-07-26 01:43:00'),(45,7,1,'closed','Đã đóng task.','2026-07-26 01:43:29','2026-07-26 01:43:29'),(46,8,1,'created','Đã tạo và giao công việc cho 1 người.','2026-07-26 01:55:01','2026-07-26 01:55:01'),(47,8,14,'acknowledged','Đã xác nhận nhận công việc.','2026-07-26 01:55:16','2026-07-26 01:55:16'),(48,8,14,'acknowledged','Đã bỏ xác nhận nhận công việc.','2026-07-26 01:55:18','2026-07-26 01:55:18'),(49,8,14,'status','Đã hoàn thành công việc.','2026-07-26 01:55:22','2026-07-26 01:55:22'),(50,8,14,'closed','Đã đóng task.','2026-07-26 01:55:27','2026-07-26 01:55:27'),(51,8,1,'closed','Đã mở lại task.','2026-07-26 01:55:36','2026-07-26 01:55:36'),(52,9,1,'created','Đã tạo và giao công việc cho 1 người.','2026-07-26 01:57:22','2026-07-26 01:57:22'),(53,9,14,'acknowledged','Đã xác nhận nhận công việc.','2026-07-26 01:57:36','2026-07-26 01:57:36'),(54,9,14,'status','Đã hoàn thành công việc.','2026-07-26 01:57:37','2026-07-26 01:57:37'),(55,9,1,'closed','Đã đóng task.','2026-07-26 01:58:01','2026-07-26 01:58:01');
/*!40000 ALTER TABLE `work_task_activities` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_task_assignees`
--

LOCK TABLES `work_task_assignees` WRITE;
/*!40000 ALTER TABLE `work_task_assignees` DISABLE KEYS */;
INSERT INTO `work_task_assignees` VALUES (7,3,14,1,'2026-07-22 19:52:27','2026-07-22 19:53:54',NULL,'2026-07-22 12:50:58','2026-07-22 12:53:54'),(12,7,14,1,'2026-07-26 08:42:42','2026-07-26 08:43:00',NULL,'2026-07-26 01:41:58','2026-07-26 01:43:00'),(13,8,14,1,NULL,'2026-07-26 08:55:22',NULL,'2026-07-26 01:55:01','2026-07-26 01:55:22'),(14,9,14,1,'2026-07-26 08:57:36','2026-07-26 08:57:37',NULL,'2026-07-26 01:57:22','2026-07-26 01:57:37');
/*!40000 ALTER TABLE `work_task_assignees` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_task_comments`
--

LOCK TABLES `work_task_comments` WRITE;
/*!40000 ALTER TABLE `work_task_comments` DISABLE KEYS */;
INSERT INTO `work_task_comments` VALUES (4,3,1,'Phản hồi giả lập phục vụ kiểm thử','2026-07-22 12:52:47','2026-07-22 12:52:47'),(5,3,14,'Phản hồi giả lập phục vụ kiểm thử','2026-07-22 12:53:06','2026-07-22 12:53:06');
/*!40000 ALTER TABLE `work_task_comments` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_tasks`
--

LOCK TABLES `work_tasks` WRITE;
/*!40000 ALTER TABLE `work_tasks` DISABLE KEYS */;
INSERT INTO `work_tasks` VALUES (3,1,1,'Công việc kiểm thử 3','Nội dung giả lập phục vụ kiểm thử','2026-07-22 19:50:00','2026-07-22 19:54:15','normal','2026-07-22 12:50:58','2026-07-22 12:54:15'),(7,1,1,'Công việc kiểm thử 7','Nội dung giả lập phục vụ kiểm thử','2026-07-26 11:41:00','2026-07-26 08:43:29','high','2026-07-26 01:41:58','2026-07-26 01:43:29'),(8,1,NULL,'Công việc kiểm thử 8','Nội dung giả lập phục vụ kiểm thử','2026-07-26 08:54:00',NULL,'high','2026-07-26 01:55:01','2026-07-26 01:55:36'),(9,1,1,'Công việc kiểm thử 9','Nội dung giả lập phục vụ kiểm thử','2026-07-27 08:57:00','2026-07-26 08:58:01','high','2026-07-26 01:57:22','2026-07-26 01:58:01');
/*!40000 ALTER TABLE `work_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'esky_automated_test'
--

--
-- Dumping routines for database 'esky_automated_test'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-28 19:43:10
