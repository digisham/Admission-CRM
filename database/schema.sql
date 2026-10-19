-- GD Goenka Public School, Purnea
-- School Admission CRM & Lead Management System Database Schema
-- Compatible with MySQL 8.0+ and MariaDB 10.4+

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `import_row_errors`;
DROP TABLE IF EXISTS `import_batches`;
DROP TABLE IF EXISTS `lead_documents`;
DROP TABLE IF EXISTS `document_types`;
DROP TABLE IF EXISTS `follow_up_logs`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `lead_assignment_history`;
DROP TABLE IF EXISTS `leads`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `school_settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `academic_sessions`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Academic Sessions
CREATE TABLE `academic_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_label` VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g. 2027–28',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `admission_open_date` DATE NULL,
  `admission_close_date` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Roles
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `permissions` LONGTEXT NULL COMMENT 'JSON array of permissions',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Users
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `designation` VARCHAR(100) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `reset_token` VARCHAR(100) NULL,
  `reset_token_expiry` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role_id`),
  INDEX `idx_users_active` (`is_active`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Leads
CREATE TABLE `leads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lead_ref` VARCHAR(50) NOT NULL UNIQUE,
  `session_id` INT NOT NULL,
  `student_name` VARCHAR(150) NOT NULL,
  `parent_name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(30) NOT NULL,
  `alternate_mobile` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `class_applied` VARCHAR(50) NOT NULL,
  `enquiry_date` DATE NOT NULL,
  `enquiry_source` VARCHAR(100) NOT NULL,
  `assigned_to` INT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'New',
  `priority` ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
  `next_followup_date` DATETIME NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `remarks` TEXT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT NULL,
  `updated_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_leads_session` (`session_id`),
  INDEX `idx_leads_status` (`status`),
  INDEX `idx_leads_mobile` (`mobile`),
  INDEX `idx_leads_assigned` (`assigned_to`),
  INDEX `idx_leads_followup` (`next_followup_date`),
  INDEX `idx_leads_created` (`created_at`),
  CONSTRAINT `fk_leads_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_leads_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Lead Assignment History
CREATE TABLE `lead_assignment_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lead_id` INT NOT NULL,
  `previous_user_id` INT NULL,
  `new_user_id` INT NOT NULL,
  `assigned_by` INT NULL,
  `reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_lah_lead` (`lead_id`),
  CONSTRAINT `fk_lah_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tasks
CREATE TABLE `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lead_id` INT NOT NULL,
  `session_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `task_type` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `assigned_to` INT NOT NULL,
  `due_date` DATETIME NOT NULL,
  `priority` ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
  `status` ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `completion_remarks` TEXT NULL,
  `created_by` INT NULL,
  `completed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tasks_assigned` (`assigned_to`),
  INDEX `idx_tasks_due` (`due_date`),
  INDEX `idx_tasks_status` (`status`),
  INDEX `idx_tasks_session` (`session_id`),
  CONSTRAINT `fk_tasks_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Follow-up & Call Logs
CREATE TABLE `follow_up_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lead_id` INT NOT NULL,
  `contact_method` ENUM('Phone', 'WhatsApp', 'Email', 'In Person', 'Other') NOT NULL DEFAULT 'Phone',
  `outcome` ENUM('No Answer', 'Busy', 'Call Back Requested', 'Interested', 'Not Interested', 'Visit Scheduled', 'Documents Requested', 'Other') NOT NULL,
  `remarks` TEXT NOT NULL,
  `next_followup_date` DATETIME NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ful_lead` (`lead_id`),
  INDEX `idx_ful_creator` (`created_by`),
  CONSTRAINT `fk_ful_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ful_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Document Types
CREATE TABLE `document_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Lead Documents
CREATE TABLE `lead_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lead_id` INT NOT NULL,
  `document_type_id` INT NOT NULL,
  `status` ENUM('Pending', 'Available', 'Not Available', 'Not Applicable') NOT NULL DEFAULT 'Pending',
  `file_path` VARCHAR(255) NULL,
  `file_name` VARCHAR(255) NULL,
  `file_size` INT NULL,
  `mime_type` VARCHAR(100) NULL,
  `remarks` TEXT NULL,
  `verification_status` ENUM('Not Reviewed', 'Verified', 'Rejected') NOT NULL DEFAULT 'Not Reviewed',
  `verification_remarks` TEXT NULL,
  `uploaded_by` INT NULL,
  `uploaded_at` DATETIME NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_lead_doc` (`lead_id`, `document_type_id`),
  CONSTRAINT `fk_ld_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ld_type` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Import Batches
CREATE TABLE `import_batches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `file_hash` VARCHAR(64) NULL,
  `imported_by` INT NOT NULL,
  `total_rows` INT NOT NULL DEFAULT 0,
  `imported_rows` INT NOT NULL DEFAULT 0,
  `skipped_rows` INT NOT NULL DEFAULT 0,
  `failed_rows` INT NOT NULL DEFAULT 0,
  `status` ENUM('Processing', 'Completed', 'Failed') NOT NULL DEFAULT 'Completed',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ib_user` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Import Row Errors
CREATE TABLE `import_row_errors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `batch_id` INT NOT NULL,
  `row_number` INT NOT NULL,
  `error_message` VARCHAR(255) NOT NULL,
  `raw_data` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ire_batch` FOREIGN KEY (`batch_id`) REFERENCES `import_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Audit Logs
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT NULL,
  `old_values` LONGTEXT NULL,
  `new_values` LONGTEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_user` (`user_id`),
  INDEX `idx_audit_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. School Settings
CREATE TABLE `school_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` LONGTEXT NULL,
  `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
