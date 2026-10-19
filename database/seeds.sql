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
