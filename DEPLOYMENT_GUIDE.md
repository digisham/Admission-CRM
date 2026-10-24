# 🚀 GD Goenka Admission CRM — Production Deployment Guide

This guide explains how to deploy this CRM to any standard web hosting (cPanel, Hostinger, GoDaddy, Namecheap, Plesk, or Cloud VPS).

---

## ⚡ Quick 3-Step Deployment Overview

1. **Upload Files:** Upload the project files to your hosting `public_html` directory.
2. **Import Database:** In **phpMyAdmin**, import `database/install_complete.sql`.
3. **Configure Database:** Open `app/Config/Database.php` and enter your database name, username, and password.

Everything else (clean URLs, assets, session handling, anti-spam captcha) works automatically!

---

## 📋 Detailed Step-by-Step Instructions

### Step 1: Upload Files to Hosting
1. Compress all files in this project into a `.zip` archive (you can exclude `.git`, `.agent`, `.agents`, and `scratch` folders).
2. Log into your hosting **cPanel** (or FTP) and navigate to **File Manager** &rarr; `public_html`.
3. Upload the `.zip` file and extract it directly inside `public_html`.

> [!NOTE]
> Ensure the hidden `.htaccess` file is uploaded into `public_html`. It handles clean URLs (`/enquire`) and security guards automatically.

---

### Step 2: Create & Import the Database
1. In cPanel, go to **MySQL Databases**:
   - Create a new database (e.g. `u123456_gdg_crm`).
   - Create a new database user (e.g. `u123456_admin`) with a secure password.
   - Assign the user to the database with **ALL PRIVILEGES**.
2. Open **phpMyAdmin**:
   - Select your newly created database on the left.
   - Click the **Import** tab at the top.
   - Choose file: `database/install_complete.sql` from your computer or server.
   - Click **Import** (or **Go**) at the bottom.
   - ✅ *All 14 tables, default roles, settings, academic session 2027–28, and default logins are set up in 1 click!*

---

### Step 3: Enter Database Credentials
Open **`app/Config/Database.php`** using cPanel File Manager Code Editor:

Lines 17–21:
```php
public static string $host     = 'localhost';              // Keep 'localhost'
public static string $port     = '3306';                   // Keep '3306'
public static string $database = 'your_hosting_dbname';    // e.g. u123456_gdg_crm
public static string $username = 'your_hosting_username';  // e.g. u123456_admin
public static string $password = 'your_hosting_password';  // Your database password
```

Click **Save Changes**.

---

### Step 4: Verify Folder Permissions
Ensure the following directories have write permissions (`755` or `775`):
- `storage/`
- `storage/logs/`
- `storage/imports/`
- `storage/private_uploads/`

*(In cPanel File Manager, right-click the `storage` folder, click "Change Permissions", and set to `755` or `775`).*

---

## 🔑 Default Login Credentials

| Role | Email | Password |
|---|---|---|
| **Super Administrator** | `admin@gdgoenkapurnea.com` | `Admin@12345` |
| **Senior Counselor** | `priya@gdgoenkapurnea.com` | `Priya@12345` |
| **Admission Counselor** | `rahul@gdgoenkapurnea.com` | `Rahul@12345` |

*(You can change passwords immediately after first login under **My Profile** &rarr; Change Password).*

---

## 🌐 Live Clean URLs & Public Enquiry Form

- **Main CRM Portal:** `https://your-domain.com/` (routes to Login / Dashboard)
- **Public Admission Enquiry Form (for ads & campaign links):**
  - Clean URL: `https://your-domain.com/enquire`
  - Also works with: `https://your-domain.com/inquire`

---

## 🛡️ Security Built-In
- Direct web access to `app/`, `storage/private_uploads/`, `database/`, and config files is automatically blocked by `.htaccess`.
- Math Captcha, honeypot traps, speed traps, and rate limiting protect the public enquiry form from spam.
