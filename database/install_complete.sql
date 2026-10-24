-- =============================================================================
-- GD Goenka Public School, Purnea - School Admission CRM & Lead Management System
-- Complete 1-Click Database Setup (Tables + Seed Data + Default Admin Login)
-- Compatible with MySQL 8.0+ and MariaDB 10.4+ (cPanel, Plesk, VPS, Cloud)
-- =============================================================================

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
  `completed_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tasks_assigned` (`assigned_to`),
  INDEX `idx_tasks_completed_by` (`completed_by`),
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
  `contact_method` VARCHAR(50) NOT NULL DEFAULT 'Phone',
  `outcome` VARCHAR(50) NOT NULL DEFAULT 'Other',
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

-- 14. Notifications
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `sender_id` INT NULL,
  `lead_id` INT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user` (`user_id`, `is_read`),
  INDEX `idx_notif_created` (`created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notif_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =============================================================================
-- INITIAL DEFAULT SEED DATA
-- =============================================================================

-- GD Goenka Public School, Purnea
-- Seed Data for School Admission CRM

-- 1. Roles
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `permissions`) VALUES
(1, 'Super Admin', 'super_admin', 'Full system access and administration', '["*"]'),
(2, 'Admission Manager', 'admin', 'Manages leads, assignments, follow-ups, reports and imports', '["leads.view_all","leads.create","leads.edit","leads.assign","tasks.manage","followups.manage","reports.view","imports.manage","sessions.view"]'),
(3, 'Admission Counselor', 'employee', 'Manages assigned leads, calls, tasks and follow-ups', '["leads.view_assigned","leads.update_status","tasks.assigned","followups.create","documents.update"]'),
(4, 'Viewer', 'read_only', 'Read-only access to approved reports and leads', '["leads.view_all","reports.view"]')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 2. Initial Active Academic Session (2027–28)
INSERT INTO `academic_sessions` (`id`, `session_label`, `start_date`, `end_date`, `admission_open_date`, `admission_close_date`, `is_active`, `is_archived`, `notes`) VALUES
(1, '2027–28', '2027-04-01', '2028-03-31', '2026-10-01', '2027-07-31', 1, 0, 'Active academic session for new admissions')
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- 3. Required Document Types (5 Default Document Checklist Items)
INSERT INTO `document_types` (`id`, `name`, `code`, `description`, `is_required`, `sort_order`) VALUES
(1, 'Birth Certificate (Attested Copy)', 'birth_certificate', 'Government issued municipal birth certificate', 1, 1),
(2, 'Previous Year\'s Academic Report Card', 'report_card', 'Official report card or mark sheet from prior school', 1, 2),
(3, 'Transfer Certificate (if applicable)', 'transfer_certificate', 'Original TC signed and counter-signed if required', 0, 3),
(4, 'Passport-size Photographs (Student & Parents)', 'photographs', 'Recent passport-size colour photographs', 1, 4),
(5, 'Address Proof', 'address_proof', 'Aadhaar Card, Passport, Electricity Bill, or Voter ID', 1, 5)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 4. Initial Users (Super Admin & Admission Counselors)
-- Passwords:
-- Admin: Admin@12345
-- Priya: Priya@12345
-- Rahul: Rahul@12345
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `phone`, `designation`, `is_active`) VALUES
(1, 1, 'Super Administrator', 'admin@gdgoenkapurnea.com', '$2y$10$s4k9VDS.efXfsI0IARfn9utobsDxZ6kvZKjiReWdBLVM6S1TxARvi', '9876543210', 'Head of Admissions', 1),
(2, 3, 'Priya Verma', 'priya@gdgoenkapurnea.com', '$2y$10$k3Gu7VXTyehaxBvAMUwoQuMvPGOrx1zAOmDHx349PquXWmdnJ663e', '9876543211', 'Senior Admission Counselor', 1),
(3, 3, 'Rahul Sharma', 'rahul@gdgoenkapurnea.com', '$2y$10$1LiaUUevNX1NG1xx1lmeFeKIt/dZ6KBkC1eQSr8dzERT5.oiOnniW', '9876543212', 'Admission Counselor', 1)
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- 5. School Settings
INSERT INTO `school_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('school_name', 'GD Goenka Public School, Purnea', 'general', 'Official School Name'),
('school_email', 'admissions@gdgoenkapurnea.com', 'general', 'Primary Admissions Email'),
('school_phone', '+91 99999 99999', 'general', 'Admissions Hotline'),
('school_address', 'NH-31, Near Chunapur Road, Purnea, Bihar 854301', 'general', 'Campus Address'),
('lead_sources', '["Website","Phone Call","Walk-in","Referral","Social Media","Advertisement","School Event","Existing Parent","Other"]', 'leads', 'Configured Lead Sources'),
('lead_statuses', '["New","Assigned","Contact Attempted","Contacted","Interested","Follow-up Required","School Visit Scheduled","School Visit Completed","Application In Progress","Admission Confirmed","Not Interested","Lost / Unreachable"]', 'leads', 'Configured Lead Statuses'),
('classes_offered', '["Pre-Nursery","Nursery","LKG","UKG","Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6","Grade 7","Grade 8","Grade 9","Grade 10","Grade 11 - Science","Grade 11 - Commerce","Grade 11 - Humanities","Grade 12 - Science","Grade 12 - Commerce","Grade 12 - Humanities"]', 'academics', 'Available Classes for Admission'),
('document_unlock_status', '["Admission Confirmed","Application In Progress"]', 'documents', 'Statuses that enable the admission document checklist')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 6. Sample Initial Leads for Demonstration & Immediate Testing
INSERT INTO `leads` (`id`, `lead_ref`, `session_id`, `student_name`, `parent_name`, `mobile`, `alternate_mobile`, `email`, `class_applied`, `enquiry_date`, `enquiry_source`, `assigned_to`, `status`, `priority`, `next_followup_date`, `address`, `city`, `remarks`) VALUES
(1, 'GDG-2728-0001', 1, 'Aarav Kumar', 'Rajesh Kumar', '9811223344', '9811223345', 'rajesh.kumar@example.com', 'Grade 1', CURDATE() - INTERVAL 5 DAY, 'Walk-in', 2, 'Contacted', 'High', NOW() + INTERVAL 2 HOUR, 'Line Bazar', 'Purnea', 'Parent visited campus, interested in CBSE curriculum and transport facilities.'),
(2, 'GDG-2728-0002', 1, 'Ananya Singh', 'Dr. Vikram Singh', '9822334455', NULL, 'vikram.singh@example.com', 'Grade 6', CURDATE() - INTERVAL 4 DAY, 'Website', 2, 'Follow-up Required', 'Urgent', NOW() + INTERVAL 1 DAY, 'Bhatta Bazar', 'Purnea', 'Submitted online query. Looking for hostel and sports academy details.'),
(3, 'GDG-2728-0003', 1, 'Rohan Jha', 'Manoj Jha', '9833445566', '9833445567', 'manoj.jha@example.com', 'Nursery', CURDATE() - INTERVAL 3 DAY, 'Referral', 3, 'School Visit Scheduled', 'Medium', NOW() + INTERVAL 2 DAY, 'Gulabbagh', 'Purnea', 'Referred by existing parent Mr. R. Sharma. Scheduled visit this Saturday 11 AM.'),
(4, 'GDG-2728-0004', 1, 'Sanya Mehta', 'Sunil Mehta', '9844556677', NULL, 'sunil.mehta@example.com', 'Grade 9', CURDATE() - INTERVAL 2 DAY, 'Social Media', 3, 'Admission Confirmed', 'High', NULL, 'Madhubani', 'Purnea', 'Admission form filled and registration fee cleared.'),
(5, 'GDG-2728-0005', 1, 'Kabir Ali', 'Farhan Ali', '9855667788', NULL, NULL, 'Grade 3', CURDATE() - INTERVAL 1 DAY, 'Phone Call', NULL, 'New', 'Medium', NULL, 'Katihar Road', 'Purnea', 'Enquired regarding school fee structure and admission test dates.');

-- 7. Sample Lead Assignment History
INSERT INTO `lead_assignment_history` (`lead_id`, `previous_user_id`, `new_user_id`, `assigned_by`, `reason`) VALUES
(1, NULL, 2, 1, 'Initial assignment on lead creation'),
(2, NULL, 2, 1, 'Assigned based on Grade 6 enquiry'),
(3, NULL, 3, 1, 'Assigned to counselor Rahul'),
(4, NULL, 3, 1, 'Assigned to counselor Rahul');

-- 8. Sample Tasks
INSERT INTO `tasks` (`lead_id`, `session_id`, `title`, `task_type`, `description`, `assigned_to`, `due_date`, `priority`, `status`, `created_by`) VALUES
(1, 1, 'Call Parent for Campus Tour Confirmation', 'Follow-up Call', 'Discuss transport route and confirm campus visit schedule with Mr. Rajesh Kumar.', 2, NOW() + INTERVAL 4 HOUR, 'High', 'Pending', 1),
(2, 1, 'Send Fee Structure & Brochure on WhatsApp', 'WhatsApp Follow-up', 'Share Grade 6 fee breakdown and brochure with Dr. Vikram Singh.', 2, NOW() - INTERVAL 3 HOUR, 'Urgent', 'Pending', 1),
(3, 1, 'Conduct Campus Tour & Counselling', 'Conduct School Visit', 'Host Mr. Manoj Jha and student Rohan for preschool tour.', 3, NOW() + INTERVAL 2 DAY, 'Medium', 'Pending', 1);

-- 9. Sample Follow-up Call Logs
INSERT INTO `follow_up_logs` (`lead_id`, `contact_method`, `outcome`, `remarks`, `next_followup_date`, `created_by`, `created_at`) VALUES
(1, 'Phone', 'Interested', 'Spoke to father. Satisfied with CBSE affiliation and teaching faculty. Requested follow-up call tomorrow.', NOW() + INTERVAL 1 DAY, 2, NOW() - INTERVAL 1 DAY),
(3, 'WhatsApp', 'Visit Scheduled', 'Sent appointment confirmation for Saturday campus tour. Parent acknowledged.', NOW() + INTERVAL 2 DAY, 3, NOW() - INTERVAL 6 HOUR),
(4, 'In Person', 'Documents Requested', 'Parent paid registration fee. Informed parent to submit Transfer Certificate and Report Card.', NULL, 3, NOW() - INTERVAL 12 HOUR);

-- 10. Sample Lead Documents for Confirmed Lead
INSERT INTO `lead_documents` (`lead_id`, `document_type_id`, `status`, `remarks`, `verification_status`) VALUES
(4, 1, 'Available', 'Physical attested copy received during walk-in verification.', 'Verified'),
(4, 2, 'Available', 'Grade 8 final term mark sheet verified.', 'Verified'),
(4, 3, 'Pending', 'Awaiting original TC from previous school.', 'Not Reviewed'),
(4, 4, 'Available', '4 copies submitted.', 'Verified'),
(4, 5, 'Available', 'Parent Aadhaar card copy submitted.', 'Verified');
