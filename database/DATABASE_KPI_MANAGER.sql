SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE DATABASE IF NOT EXISTS `kpi_laravel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kpi_laravel`;

DROP TABLE IF EXISTS `login_logs`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `excess_payments`;
DROP TABLE IF EXISTS `kpi_records`;
DROP TABLE IF EXISTS `import_batches`;
DROP TABLE IF EXISTS `kpi_targets`;
DROP TABLE IF EXISTS `kpi_plans`;
DROP TABLE IF EXISTS `user_permissions`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `modules`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `personnels`;
DROP TABLE IF EXISTS `password_reset_tokens`;

CREATE TABLE `personnels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NULL,
  `name` VARCHAR(150) NOT NULL,
  `normalized_name` VARCHAR(180) NOT NULL,
  `type` ENUM('teacher','employee','leader','collaborator','admin') NOT NULL,
  `position` VARCHAR(150) NULL,
  `email` VARCHAR(150) NULL,
  `phone` VARCHAR(30) NULL,
  `default_kpi` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `has_kpi` TINYINT(1) NOT NULL DEFAULT 1,
  `payment_type` ENUM('none','percentage','per_student','fixed') NOT NULL DEFAULT 'none',
  `payment_value` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personnels_code_unique` (`code`),
  KEY `personnels_normalized_idx` (`normalized_name`),
  KEY `personnels_type_active_idx` (`type`,`active`),
  KEY `personnels_deleted_idx` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(500) NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnel_id` BIGINT UNSIGNED NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
  `last_login_at` DATETIME NULL,
  `last_login_ip` VARCHAR(45) NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_personnel_unique` (`personnel_id`),
  KEY `users_role_idx` (`role_id`),
  KEY `users_active_idx` (`active`,`deleted_at`),
  CONSTRAINT `fk_users_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(80) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `module_id` BIGINT UNSIGNED NOT NULL,
  `can_view` TINYINT(1) NOT NULL DEFAULT 0,
  `can_create` TINYINT(1) NOT NULL DEFAULT 0,
  `can_update` TINYINT(1) NOT NULL DEFAULT 0,
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
  `can_export` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_module_unique` (`role_id`,`module_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `module_id` BIGINT UNSIGNED NOT NULL,
  `can_view` TINYINT(1) NOT NULL DEFAULT 0,
  `can_create` TINYINT(1) NOT NULL DEFAULT 0,
  `can_update` TINYINT(1) NOT NULL DEFAULT 0,
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
  `can_export` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module_unique` (`user_id`,`module_id`),
  CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `courses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NULL,
  `name` VARCHAR(200) NOT NULL,
  `normalized_name` VARCHAR(220) NOT NULL,
  `category` VARCHAR(150) NULL,
  `conversion_quantity` DECIMAL(10,2) NOT NULL DEFAULT 1,
  `conversion_kpi` DECIMAL(10,2) NOT NULL DEFAULT 1,
  `conversion_mode` ENUM('proportional','full_group') NOT NULL DEFAULT 'proportional',
  `default_excess_rate` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_code_unique` (`code`),
  UNIQUE KEY `courses_normalized_unique` (`normalized_name`),
  KEY `courses_active_idx` (`active`,`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kpi_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `year` SMALLINT UNSIGNED NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `status` ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
  `settlement_scope` ENUM('month','quarter','year') NOT NULL DEFAULT 'quarter',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_plans_year_unique` (`year`),
  CONSTRAINT `fk_kpi_plans_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kpi_targets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `personnel_id` BIGINT UNSIGNED NOT NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `period_type` ENUM('month','quarter','year') NOT NULL,
  `quarter` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `month` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `target_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `target_revenue` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `is_mandatory` TINYINT(1) NOT NULL DEFAULT 1,
  `excess_payment_per_kpi` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_target_period_unique` (`plan_id`,`personnel_id`,`course_id`,`period_type`,`quarter`,`month`,`deleted_at`),
  KEY `kpi_target_period_idx` (`plan_id`,`period_type`,`quarter`,`month`),
  KEY `kpi_target_person_idx` (`personnel_id`),
  CONSTRAINT `fk_kpi_targets_plan` FOREIGN KEY (`plan_id`) REFERENCES `kpi_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kpi_targets_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kpi_targets_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_targets_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `import_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `import_type` ENUM('target','result') NOT NULL DEFAULT 'result',
  `period_type` ENUM('month','quarter','year') NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `quarter` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `month` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_path` VARCHAR(500) NOT NULL,
  `file_hash` CHAR(64) NOT NULL,
  `status` ENUM('processing','completed','failed') NOT NULL DEFAULT 'processing',
  `total_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `success_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `error_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_revenue` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `error_details` JSON NULL,
  `imported_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `import_hash_idx` (`file_hash`),
  KEY `import_period_idx` (`year`,`period_type`,`quarter`,`month`),
  CONSTRAINT `fk_import_batches_user` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kpi_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `import_batch_id` BIGINT UNSIGNED NULL,
  `source_row_no` INT UNSIGNED NULL,
  `personnel_id` BIGINT UNSIGNED NOT NULL,
  `collaborator_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `student_name` VARCHAR(200) NOT NULL,
  `class_name` VARCHAR(200) NULL,
  `raw_quantity` DECIMAL(12,2) NOT NULL DEFAULT 1,
  `revenue` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `receipt_no` VARCHAR(100) NULL,
  `record_date` DATE NOT NULL,
  `record_year` SMALLINT UNSIGNED NOT NULL,
  `record_quarter` TINYINT UNSIGNED NOT NULL,
  `record_month` TINYINT UNSIGNED NOT NULL,
  `conversion_quantity` DECIMAL(10,2) NOT NULL DEFAULT 1,
  `conversion_kpi` DECIMAL(10,2) NOT NULL DEFAULT 1,
  `conversion_mode` ENUM('proportional','full_group') NOT NULL DEFAULT 'proportional',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `kpi_records_period_idx` (`record_year`,`record_quarter`,`record_month`),
  KEY `kpi_records_person_period_idx` (`personnel_id`,`record_year`,`record_month`),
  KEY `kpi_records_collab_period_idx` (`collaborator_id`,`record_year`,`record_month`),
  KEY `kpi_records_course_idx` (`course_id`),
  CONSTRAINT `fk_kpi_records_batch` FOREIGN KEY (`import_batch_id`) REFERENCES `import_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_records_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_kpi_records_collaborator` FOREIGN KEY (`collaborator_id`) REFERENCES `personnels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kpi_records_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_kpi_records_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `excess_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_key` VARCHAR(120) NOT NULL,
  `payment_kind` ENUM('excess_kpi','collaborator') NOT NULL,
  `personnel_id` BIGINT UNSIGNED NOT NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `period_type` ENUM('month','quarter','year') NOT NULL,
  `period_value` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `target_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `actual_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `excess_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `revenue_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `payment_rate` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `payment_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `status` ENUM('pending','approved','paid','cancelled') NOT NULL DEFAULT 'pending',
  `approved_at` DATETIME NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `paid_at` DATETIME NULL,
  `paid_by` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `calculated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_period_unique` (`payment_key`,`year`,`period_type`,`period_value`),
  KEY `payment_status_idx` (`status`,`year`),
  CONSTRAINT `fk_excess_payments_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_excess_payments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_excess_payments_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_excess_payments_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_excess_payments_calculated_by` FOREIGN KEY (`calculated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `module` VARCHAR(80) NOT NULL,
  `action` VARCHAR(80) NOT NULL,
  `description` VARCHAR(500) NOT NULL,
  `subject_type` VARCHAR(255) NULL,
  `subject_id` BIGINT UNSIGNED NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_user_idx` (`user_id`,`created_at`),
  KEY `activity_module_idx` (`module`,`created_at`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `email` VARCHAR(150) NOT NULL,
  `event` ENUM('login_success','login_failed','logout') NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login_logs_email_idx` (`email`,`created_at`),
  KEY `login_logs_user_idx` (`user_id`,`created_at`),
  CONSTRAINT `fk_login_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`,`code`,`name`,`description`,`is_system`,`created_at`,`updated_at`) VALUES
(1,'admin','Admin','Toàn quyền hệ thống',1,NOW(),NOW()),
(2,'leader','Lãnh đạo','Giám đốc, Phó giám đốc và người quản lý',1,NOW(),NOW()),
(3,'teacher','Giáo viên','Mặc định chỉ xem tổng quan cá nhân; Admin cấp thêm module nếu cần',1,NOW(),NOW()),
(4,'staff','Nhân viên','Mặc định chỉ xem tổng quan cá nhân; Admin cấp thêm module nếu cần',1,NOW(),NOW());

INSERT INTO `modules` (`id`,`code`,`name`,`icon`,`sort_order`,`created_at`,`updated_at`) VALUES
(1,'personnel','Nhân sự & cộng tác viên','bi-people-fill',10,NOW(),NOW()),
(2,'users','Tài khoản','bi-person-lock',20,NOW(),NOW()),
(3,'roles','Vai trò & quyền','bi-shield-check',30,NOW(),NOW()),
(4,'kpis','Chỉ tiêu KPI','bi-bullseye',40,NOW(),NOW()),
(5,'courses','Khóa học','bi-journal-bookmark-fill',50,NOW(),NOW()),
(6,'imports','Nhập Excel','bi-file-earmark-spreadsheet',60,NOW(),NOW()),
(7,'reports','Báo cáo','bi-bar-chart-line-fill',70,NOW(),NOW()),
(8,'payments','Thanh toán vượt','bi-cash-coin',80,NOW(),NOW()),
(9,'logs','Nhật ký hệ thống','bi-clock-history',90,NOW(),NOW());

INSERT INTO `role_permissions` (`role_id`,`module_id`,`can_view`,`can_create`,`can_update`,`can_delete`,`can_export`,`created_at`,`updated_at`)
SELECT 1,id,1,1,1,1,1,NOW(),NOW() FROM `modules`;

INSERT INTO `role_permissions` (`role_id`,`module_id`,`can_view`,`can_create`,`can_update`,`can_delete`,`can_export`,`created_at`,`updated_at`) VALUES
(2,1,1,1,1,0,0,NOW(),NOW()),
(2,4,1,1,1,0,0,NOW(),NOW()),
(2,5,1,0,0,0,0,NOW(),NOW()),
(2,6,1,1,0,0,0,NOW(),NOW()),
(2,7,1,0,0,0,1,NOW(),NOW()),
(2,8,1,1,1,0,0,NOW(),NOW());

INSERT INTO `personnels` (`id`,`code`,`name`,`normalized_name`,`type`,`position`,`email`,`default_kpi`,`has_kpi`,`payment_type`,`payment_value`,`active`,`created_at`,`updated_at`) VALUES
(1,'ADM001','Quản trị viên','quan tri vien','admin','Admin hệ thống','admin@kpi.local',0,0,'none',0,1,NOW(),NOW()),
(2,'GV001','Giáo viên mẫu','giao vien mau','teacher','Giáo viên','giaovien@kpi.local',42,1,'none',0,1,NOW(),NOW()),
(3,'NV001','Nhân viên mẫu','nhan vien mau','employee','Nhân viên','nhanvien@kpi.local',55,1,'none',0,1,NOW(),NOW()),
(4,'LD001','Lãnh đạo mẫu','lanh dao mau','leader','Giám đốc','lanhdao@kpi.local',0,0,'none',0,1,NOW(),NOW()),
(5,'CTV001','Cộng tác viên mẫu','cong tac vien mau','collaborator','Cộng tác viên',NULL,0,0,'percentage',5,1,NOW(),NOW());

INSERT INTO `users` (`personnel_id`,`role_id`,`name`,`email`,`password`,`active`,`must_change_password`,`created_at`,`updated_at`) VALUES
(1,1,'Quản trị viên','admin@kpi.local','$2y$12$TcLovTFlAEMf.tsGOe1Giel5RvCN6LI.F5U83wXO8Q2rMaRmm1mre',1,0,NOW(),NOW());

INSERT INTO `courses` (`id`,`code`,`name`,`normalized_name`,`category`,`conversion_quantity`,`conversion_kpi`,`conversion_mode`,`default_excess_rate`,`active`,`note`,`created_at`,`updated_at`) VALUES
(1,'B1','Chứng nhận B1','chung nhan b1','Chứng chỉ ngoại ngữ',2,1,'full_group',100000,1,'Đủ 2 lượt mới tính 1 KPI',NOW(),NOW()),
(2,'UDCNTT','Ứng dụng CNTT cơ bản','ung dung cntt co ban','Tin học',1,1,'proportional',100000,1,'Một lượt tính một KPI',NOW(),NOW()),
(3,'AI-VP','AI trong công tác văn phòng','ai trong cong tac van phong','AI - Tin học',1,1,'proportional',100000,1,'Một lượt tính một KPI',NOW(),NOW());

INSERT INTO `kpi_plans` (`id`,`year`,`name`,`status`,`settlement_scope`,`note`,`created_by`,`created_at`,`updated_at`) VALUES
(1,2026,'Kế hoạch chỉ tiêu năm 2026','active','quarter','Kế hoạch mẫu',1,NOW(),NOW());

INSERT INTO `kpi_targets` (`plan_id`,`personnel_id`,`course_id`,`period_type`,`quarter`,`month`,`target_quantity`,`target_revenue`,`is_mandatory`,`excess_payment_per_kpi`,`note`,`created_by`,`created_at`,`updated_at`) VALUES
(1,2,NULL,'month',3,7,42,0,1,100000,'KPI tổng giáo viên tháng 7',1,NOW(),NOW()),
(1,3,NULL,'month',3,7,55,0,1,100000,'KPI tổng nhân viên tháng 7',1,NOW(),NOW());

SET FOREIGN_KEY_CHECKS=1;
