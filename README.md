# School Admission CRM & Lead Management System

A secure, production-grade **School Admission CRM & Lead Management System** custom-built for **GD Goenka Public School, Purnea** to manage student admission enquiries for the **2027–28 academic session** and future sessions.

Built strictly with **Core PHP 8.2+ (PDO, prepared statements)**, **MySQL 8+/MariaDB**, **Bootstrap 5**, **Inter typography**, and **vanilla JavaScript**. 

> **Zero React. Zero TypeScript. Zero Vite. Zero Node.js. Zero Supabase.** Designed specifically for rock-solid stability, standard shared hosting, cPanel, DirectAdmin, or dedicated Apache/Nginx web servers.

---

## 🏫 Key Features

### 1. Dynamic Academic Session Management
- **Initial Active Session:** Seeded with **`2027–28`** as the default active admission session.
- **Session Switching Invariance:** Changing or activating a new session context dynamically filters views without modifying the session of historical leads.
- Full administration: create, edit, activate, and archive academic sessions.

### 2. Robust Lead & Enquiry Pipeline
- Unique auto-generated references (e.g., `GDG-2728-0001`).
- Phone numbers stored strictly as strings preserving leading zeros and country codes.
- Real-time client & server duplicate detection matching phone numbers within the active session.
- Full pipeline statuses: *New, Assigned, Contact Attempted, Contacted, Interested, Follow-up Required, School Visit Scheduled, School Visit Completed, Application In Progress, Admission Confirmed, Not Interested, Lost / Unreachable*.
- Dedicated **Unassigned Leads Queue** for rapid allocation.

### 3. Excel / CSV Multi-step Import Wizard
- **Downloadable Template:** Pre-formatted CSV with headers and realistic sample rows for GD Goenka Public School.
- **Upload & Preview:** Preview raw spreadsheet data before committing.
- **Column Mapping:** Intelligent auto-matching of spreadsheet columns to CRM fields.
- **Validation & Duplicate Strategies:** 
  - *Skip duplicates* (default & recommended).
  - *Update existing lead records* with appended remarks.
  - *Allow duplicate entries* (for siblings sharing contact details).
- **Import History:** Batch audit logging with row-level error reports.

### 4. Lead Allocation & Workload Tracking
- Single assignment and bulk assignment with confirmation.
- Optional automated creation of an initial follow-up task upon assignment.
- Complete assignment history: records previous counselor, new counselor, reason, and actor.
- Counselor workload overview displaying active enquiries, completed admissions, pending tasks, and overdue items.

### 5. Tasks, Calls, Remarks & Dynamic Overdue Calculation
- Categorized tasks (*Follow-up Call, WhatsApp Follow-up, Schedule Visit, Conduct Visit, Collect Documents, etc.*).
- Dynamic overdue state calculated automatically from `due_date < NOW() AND status != 'Completed'`.
- Chronological Lead Timeline unifying assignment changes, call logs, remarks, task completions, and document updates.

### 6. Admission Documents Checklist (5 Default Items)
Configurable document checklist for verified admissions:
1. Birth Certificate (Attested Copy)
2. Previous Year's Academic Report Card
3. Transfer Certificate (if applicable)
4. Passport-size Photographs (Student & Parents)
5. Address Proof
- Per-item independent statuses (*Pending, Available, Not Available, Not Applicable*).
- Physical documents can be marked *Available* with or without a digital file.
- Private, access-controlled document storage in `storage/private_uploads/` with streaming download authentication.

### 7. Executive Dashboards & Analytics
- Real-time database metrics (zero fabricated numbers).
- Conversion rate formula: `(Confirmed Admissions / Total Session Enquiries) × 100%`.
- Enquiries segmented by status, class/grade, and acquisition source.
- Counselor activity and overdue alerts.

--- 

## 🚀 Local Development Setup

### Prerequisites
- PHP 8.0+ or 8.2+ with `pdo_mysql`, `mbstring`, `openssl` extensions enabled.
- MySQL 8.0+ or MariaDB 10.4+ (e.g. via XAMPP, Laragon, or standalone).

### 1. Database Creation
Using MySQL CLI or phpMyAdmin:
```sql
CREATE DATABASE school_admission_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import schema and seed data:
```bash
mysql -u root school_admission_crm < database/schema.sql
mysql -u root school_admission_crm < database/seeds.sql
```

### 2. Environment Configuration
Check `.env` in the root directory:
```ini
APP_NAME="School Admission CRM"
APP_ENV=development
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=school_admission_crm
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

### 3. Run Locally
You can run the application directly using PHP's built-in web server:
```bash
php -S localhost:8080 -t public
```
Or place the folder in `C:\xampp\htdocs\school_admission_crm` and open:
```
http://localhost/school_admission_crm/
```

---

## 🧪 Automated Verification Test Suite

A complete verification script tests all 30 acceptance criteria:
```bash
php tests/test_workflows.php
```

All 30 tests pass with zero errors, validating:
- Database PDO connection
- Default active session 2027–28
- Bcrypt authentication for admin and counselors
- String mobile preservation
- Lead creation & GDG ref format
- Real-time duplicate detection
- Single & bulk assignment with audit history
- Dynamic task overdue calculations and completions
- Call logs updating status without overwriting history
- 5 default document checklist items with private download authorization
- CSV formula injection protection (`'=` prefixing)
- Real database metric calculations

---

## 🌐 cPanel / DirectAdmin / Production Deployment

1. **Upload Files:** Upload all project files to your server directory (e.g., `public_html` or a subfolder).
2. **Web Root:** 
   - Point your domain/subdomain's document root to the `public/` directory for optimal security.
   - If hosting on shared cPanel without custom document root, the included root `index.php` and `.htaccess` will automatically route traffic into `public/`.
3. **Database Import:** Create the MySQL database in cPanel and import `database/schema.sql` and `database/seeds.sql`.
4. **Permissions:** Ensure `storage/logs/`, `storage/private_uploads/`, and `storage/imports/` are writable (`chmod 755` or `775`).
5. **Backups:** Schedule regular database dumps via cron:
   ```bash
   mysqldump -u username -p'password' school_admission_crm > backup_$(date +\%F).sql
   ```
