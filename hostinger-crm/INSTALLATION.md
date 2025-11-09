# Hostinger CRM System - Installation Guide

Complete step-by-step guide to install the Dental CRM system on Hostinger.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Step-by-Step Installation](#step-by-step-installation)
3. [Configuration](#configuration)
4. [Testing](#testing)
5. [Post-Installation](#post-installation)
6. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before starting the installation, ensure you have:

- ✅ Hostinger hosting account (Business or Premium plan recommended)
- ✅ Domain name (or subdomain)
- ✅ FTP/SFTP credentials OR access to File Manager
- ✅ MySQL database access
- ✅ The CRM system files (this folder)

### Minimum Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- 256 MB PHP memory limit
- 10 MB upload file size limit

---

## Step-by-Step Installation

### 1. Upload Files to Hostinger

#### Option A: Using File Manager (Recommended for beginners)

1. Log in to your Hostinger control panel (hPanel)
2. Navigate to **Files** → **File Manager**
3. Go to the `public_html` directory
4. Click **Upload Files** button
5. Compress the `hostinger-crm` folder to ZIP (if not already)
6. Upload the ZIP file
7. Right-click the uploaded ZIP and select **Extract**
8. Delete the ZIP file after extraction

#### Option B: Using FTP/SFTP

1. Open your FTP client (FileZilla, WinSCP, etc.)
2. Connect using your Hostinger FTP credentials:
   - Host: Your domain or Hostinger FTP hostname
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21 (FTP) or 22 (SFTP)
3. Navigate to `public_html` directory
4. Upload the entire `hostinger-crm` folder
5. Wait for all files to upload

**Result**: Your CRM files should now be at `/public_html/hostinger-crm/`

---

### 2. Create MySQL Database

1. In Hostinger hPanel, go to **Databases** → **MySQL Databases**
2. Click **Create New Database**
3. Fill in the details:
   - **Database name**: `dentalcrm_db` (or your preferred name)
   - Click **Create**
4. Create a database user:
   - **Username**: `dentalcrm_user` (or your preferred name)
   - **Password**: Create a strong password (use Hostinger's generator)
   - Click **Create**
5. Add user to database:
   - Select your database
   - Select your user
   - Grant **ALL PRIVILEGES**
   - Click **Add User to Database**

**Important**: Write down these credentials:
```
Database Host: localhost
Database Name: dentalcrm_db (or your chosen name)
Database User: dentalcrm_user (or your chosen username)
Database Password: (your generated password)
```

---

### 3. Import Database Schema

1. In Hostinger hPanel, go to **Databases** → **phpMyAdmin**
2. Select your database (`dentalcrm_db`) from the left sidebar
3. Click the **Import** tab at the top
4. Click **Choose File** button
5. Navigate to your computer and select:
   - File: `hostinger-crm/sql/schema.sql`
6. Leave all other settings as default
7. Click **Go** button at the bottom
8. Wait for import to complete

**Success Message**: You should see "Import has been successfully finished"

**Tables Created**: 7 tables
- users
- customers
- assignments
- interactions
- meetings
- sales_performance
- system_logs

---

### 4. Configure Database Connection

1. In File Manager, navigate to:
   ```
   public_html/hostinger-crm/config/database.php
   ```

2. Right-click the file and select **Edit**

3. Update lines 8-11 with YOUR database credentials:

```php
define('DB_HOST', 'localhost');              // Keep as localhost
define('DB_NAME', 'dentalcrm_db');           // Your database name
define('DB_USER', 'dentalcrm_user');         // Your database username
define('DB_PASS', 'your_strong_password');   // Your database password
```

4. Click **Save Changes**

---

### 5. Configure Application Settings

1. Edit the file: `public_html/hostinger-crm/config/config.php`

2. Update line 11 with your domain:

```php
define('SITE_URL', 'https://yourdomain.com/hostinger-crm');
```

**Examples**:
- Main domain: `https://example.com/hostinger-crm`
- Subdomain: `https://crm.example.com`

3. Save the file

---

### 6. Set File Permissions

**Important for security!**

Set the following permissions using File Manager:

1. Right-click `hostinger-crm` folder → **Permissions**
2. Set to: **755** (rwxr-xr-x)

3. Create uploads directory if it doesn't exist:
   - Create folder: `uploads` inside `hostinger-crm`
   - Set permissions to: **755**

---

### 7. Enable SSL Certificate (HTTPS)

1. In Hostinger hPanel, go to **Security** → **SSL**
2. Select your domain
3. Install free SSL certificate (Let's Encrypt)
4. Wait 15-30 minutes for activation

**After SSL is active**:
1. Edit `.htaccess` file
2. Uncomment lines 8-9 to force HTTPS:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Configuration

### Default Login Credentials

After installation, use these credentials:

```
URL: https://yourdomain.com/hostinger-crm/
Username: admin
Password: admin123
```

⚠️ **CRITICAL**: Change this password immediately after first login!

---

## Testing

### 1. Test Database Connection

Visit: `https://yourdomain.com/hostinger-crm/`

**Expected Result**: Login page appears

**If you see an error**:
- Check database credentials in `config/database.php`
- Verify database exists in phpMyAdmin
- Check error logs in File Manager

### 2. Test Login

1. Enter credentials:
   - Username: `admin`
   - Password: `admin123`

2. Click **Login**

**Expected Result**: Redirects to Dashboard

### 3. Test CSV Import

1. Go to **Admin** → **Import Customers**
2. Download the sample CSV or use your own
3. Upload the file
4. Check for success message

### 4. Test Customer Assignment

1. Go to **Admin** → **Assignments**
2. Create a test sales rep user first (Admin → Manage Users)
3. Select customers and assign to sales rep
4. Verify assignment worked

---

## Post-Installation

### 1. Change Admin Password

1. Log in as admin
2. Click your name (top right) → **Profile**
3. Change password to a strong password
4. Save changes

### 2. Create Users

1. Go to **Admin** → **Manage Users**
2. Create sales representatives:
   - Username
   - Email
   - Password
   - Role: Sales Rep
3. Create managers if needed

### 3. Import Customer Data

1. Prepare your CSV file with customer data
2. Go to **Admin** → **Import Customers**
3. Upload CSV file
4. Review import results

### 4. Assign Customers

1. Go to **Admin** → **Assignments**
2. Filter customers as needed
3. Select customers
4. Choose sales rep
5. Click **Assign Selected**

### 5. Configure Email (Optional)

Edit `config/config.php`:

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@yourdomain.com');
define('SMTP_PASS', 'your-email-password');
define('SMTP_FROM', 'noreply@yourdomain.com');
```

---

## Troubleshooting

### Problem: "Database connection failed"

**Solution**:
1. Verify credentials in `config/database.php`
2. Check database exists in phpMyAdmin
3. Ensure database user has ALL PRIVILEGES
4. Test connection in phpMyAdmin

### Problem: "Page not found" or blank page

**Solution**:
1. Check file permissions (should be 755)
2. Verify files uploaded correctly
3. Check if .htaccess file exists
4. Review error logs in File Manager

### Problem: CSV import fails

**Solution**:
1. Ensure CSV has header row
2. Check file encoding (UTF-8)
3. Verify file size < 10MB
4. Check date formats (YYYY-MM-DD HH:MM:SS)

### Problem: Cannot login

**Solution**:
1. Clear browser cache and cookies
2. Try different browser
3. Check if database schema imported correctly
4. Verify users table has admin user:
   ```sql
   SELECT * FROM users WHERE username = 'admin';
   ```

### Problem: Upload files too large

**Solution**:
Edit `.htaccess` file:
```apache
php_value upload_max_filesize 20M
php_value post_max_size 20M
```

Or contact Hostinger support to increase limits.

### Problem: Session timeout too short

**Solution**:
Edit `config/config.php`:
```php
define('SESSION_TIMEOUT', 7200); // 2 hours in seconds
```

---

## Security Checklist

After installation, verify:

- ✅ Changed admin password
- ✅ SSL certificate installed and working
- ✅ Database credentials are secure
- ✅ File permissions set correctly (755 for folders, 644 for files)
- ✅ Error display is OFF in production
- ✅ Regular backups scheduled

---

## Backup Instructions

### Manual Backup

**Database**:
1. phpMyAdmin → Select database → Export → Go
2. Save SQL file securely

**Files**:
1. File Manager → Select `hostinger-crm` folder
2. Compress → Download

**Schedule**: Weekly recommended

---

## Support Resources

- **Hostinger Knowledge Base**: https://support.hostinger.com
- **PHP Manual**: https://www.php.net/manual/
- **MySQL Documentation**: https://dev.mysql.com/doc/

---

## Next Steps

1. ✅ System installed and tested
2. ✅ Admin password changed
3. ✅ Users created
4. ✅ Customers imported
5. ✅ Customers assigned
6. → Start using the CRM!

---

**Installation Complete!** 🎉

Your Dental CRM system is now ready to use. Log in and start managing your customer relationships efficiently.

For any issues, refer to the troubleshooting section or check the main README.md file.
