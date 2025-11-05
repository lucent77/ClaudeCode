# Installation Guide - Task Management System

This guide provides step-by-step instructions for installing the Task Management System on Hostinger or any Apache/PHP hosting environment.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Hostinger Installation](#hostinger-installation)
3. [Local Development Setup](#local-development-setup)
4. [VPS/Dedicated Server Installation](#vpsdedicated-server-installation)
5. [Post-Installation](#post-installation)
6. [Troubleshooting](#troubleshooting)

## Prerequisites

### Hosting Requirements
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache web server with mod_rewrite
- Minimum 256MB RAM (512MB recommended)
- 50MB+ disk space

### Required PHP Extensions
- PDO (PHP Data Objects)
- PDO MySQL
- mbstring
- JSON
- OpenSSL
- Session

Check your PHP version and extensions:
```bash
php -v
php -m
```

## Hostinger Installation

### Step 1: Upload Files

#### Option A: Using File Manager
1. Log into Hostinger control panel (hPanel)
2. Go to **Files** → **File Manager**
3. Navigate to `public_html/` directory
4. Upload the entire `task-management-system` folder
5. Extract if uploaded as ZIP

#### Option B: Using FTP
1. Connect via FTP client (FileZilla recommended)
   - Host: Your domain or FTP hostname
   - Username: Your Hostinger FTP username
   - Password: Your FTP password
   - Port: 21
2. Upload all files to `public_html/task-management-system/`

### Step 2: Create MySQL Database

1. In hPanel, go to **Databases** → **MySQL Databases**
2. Click **"Create New Database"**
3. Database name: `u123456789_tasks` (or your preferred name)
4. Click **"Create"**

### Step 3: Create Database User

1. In the same MySQL Databases section
2. Click **"Create New User"**
3. Username: `u123456789_taskuser`
4. Password: Generate a strong password (save it!)
5. Click **"Create"**

### Step 4: Grant User Permissions

1. Scroll to **"Add User to Database"**
2. Select your user and database
3. Click **"Add"**
4. Grant **ALL PRIVILEGES**
5. Click **"Save Changes"**

### Step 5: Import Database Schema

#### Option A: phpMyAdmin
1. In hPanel, go to **Databases** → **phpMyAdmin**
2. Select your database from left sidebar
3. Click **Import** tab
4. Click **"Choose File"** and select `database/schema.sql`
5. Click **"Go"** at bottom
6. Wait for success message

#### Option B: Command Line (Advanced Hostinger SSH)
```bash
mysql -u u123456789_taskuser -p u123456789_tasks < database/schema.sql
```

### Step 6: (Optional) Import Sample Data

1. In phpMyAdmin, with your database selected
2. Click **Import** tab
3. Choose `database/seed_data.sql`
4. Click **"Go"**

### Step 7: Configure Database Connection

1. In File Manager, navigate to `task-management-system/config/`
2. Right-click `database.php` and select **Edit**
3. Update the following:
```php
return [
    'host' => 'localhost',
    'database' => 'u123456789_tasks',     // Your database name
    'username' => 'u123456789_taskuser',  // Your database username
    'password' => 'YOUR_STRONG_PASSWORD',  // Your database password
    // ... rest stays the same
];
```
4. Click **"Save & Close"**

### Step 8: Set Up Domain/Subdomain

#### Option A: Subdomain
1. In hPanel, go to **Domains** → **Subdomains**
2. Click **"Create Subdomain"**
3. Subdomain: `tasks` (creates tasks.yourdomain.com)
4. Document Root: `public_html/task-management-system/public`
5. Click **"Create"**

#### Option B: Main Domain
1. Go to **Domains** → **Manage**
2. Click on your domain
3. Change **Document Root** to: `public_html/task-management-system/public`
4. Click **"Save"**

### Step 9: Set File Permissions (Important!)

In File Manager:
1. Right-click `uploads/` folder → **Permissions**
   - Set to **755** (rwxr-xr-x)
2. Right-click `logs/` folder → **Permissions**
   - Set to **755** (rwxr-xr-x)
3. Right-click `config/` folder → **Permissions**
   - Set to **755** (rwxr-xr-x)

### Step 10: Test Installation

1. Open your browser
2. Navigate to: `https://tasks.yourdomain.com` (or your configured URL)
3. You should see the login page

Default credentials:
- Email: `admin@example.com`
- Password: `admin123`

**⚠️ IMPORTANT**: Change this password immediately after first login!

## Local Development Setup

### Using XAMPP (Windows/Mac/Linux)

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install with Apache and MySQL components

2. **Copy Project Files**
   ```bash
   # Copy to XAMPP htdocs directory
   cp -r task-management-system /path/to/xampp/htdocs/
   ```

3. **Start Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

4. **Create Database**
   - Open http://localhost/phpmyadmin
   - Click "New" to create database named `task_management`
   - Go to "Import" tab
   - Select `database/schema.sql` and import
   - (Optional) Import `database/seed_data.sql`

5. **Configure Database**
   - Edit `config/database.php`:
   ```php
   'host' => 'localhost',
   'database' => 'task_management',
   'username' => 'root',
   'password' => '',  // Empty for default XAMPP
   ```

6. **Access Application**
   - Navigate to http://localhost/task-management-system/public/

### Using MAMP (Mac)

Similar to XAMPP:
1. Install MAMP from https://www.mamp.info/
2. Place project in `/Applications/MAMP/htdocs/`
3. Start servers
4. Access phpMyAdmin at http://localhost:8888/phpMyAdmin
5. Import database schema
6. Configure `config/database.php`
7. Access at http://localhost:8888/task-management-system/public/

## VPS/Dedicated Server Installation

### Ubuntu 20.04+ / Debian 11+

1. **Install LAMP Stack**
```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-mbstring php-xml php-json
sudo systemctl start apache2
sudo systemctl start mysql
```

2. **Secure MySQL**
```bash
sudo mysql_secure_installation
```

3. **Clone/Upload Project**
```bash
cd /var/www/html
sudo git clone your-repo-url task-management-system
# OR
sudo tar -xzf task-management-system.tar.gz
```

4. **Create Database**
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE task_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'task_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON task_management.* TO 'task_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

5. **Import Schema**
```bash
mysql -u task_user -p task_management < /var/www/html/task-management-system/database/schema.sql
```

6. **Configure Apache**
```bash
sudo nano /etc/apache2/sites-available/task-management.conf
```

Add:
```apache
<VirtualHost *:80>
    ServerName tasks.yourdomain.com
    DocumentRoot /var/www/html/task-management-system/public

    <Directory /var/www/html/task-management-system/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/task-management-error.log
    CustomLog ${APACHE_LOG_DIR}/task-management-access.log combined
</VirtualHost>
```

7. **Enable Site and Rewrite Module**
```bash
sudo a2ensite task-management
sudo a2enmod rewrite
sudo systemctl restart apache2
```

8. **Set Permissions**
```bash
sudo chown -R www-data:www-data /var/www/html/task-management-system
sudo chmod -R 755 /var/www/html/task-management-system
sudo chmod -R 775 /var/www/html/task-management-system/uploads
sudo chmod -R 775 /var/www/html/task-management-system/logs
```

9. **Configure Application**
```bash
sudo nano /var/www/html/task-management-system/config/database.php
```
Update with your database credentials.

10. **Enable SSL (Recommended)**
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tasks.yourdomain.com
```

## Post-Installation

### 1. Change Default Admin Password

1. Login with default credentials
2. Go to Admin Dashboard
3. Click "Edit" on admin user
4. Enter new strong password
5. Click "Save User"

### 2. Create Additional Users

1. In Admin Dashboard, click "Add User"
2. Fill in details:
   - Name: User's full name
   - Email: Valid email address
   - Password: Strong password
   - Role: Select appropriate role
   - Department: Select if department user
3. Click "Save User"

### 3. Configure Application Settings

Edit `config/app.php`:
- Set correct timezone
- Update base URL
- Configure session settings for HTTPS (if using SSL)
- Adjust file upload limits if needed

### 4. Test All Features

1. **Authentication**
   - Login/logout
   - Access control (try accessing restricted pages)

2. **Task Management**
   - Create a test task
   - Update task status
   - View task history

3. **Department Pages**
   - Access each department page
   - Verify filters work
   - Test real-time updates (open in two browsers)

4. **JSON Import**
   - Prepare a test JSON file
   - Import via Front Desk page
   - Verify tasks appear correctly

5. **User Management** (Admin)
   - Create a test user
   - Edit user details
   - Deactivate/activate user

## Troubleshooting

### Issue: "Database connection failed"

**Solution:**
1. Verify database credentials in `config/database.php`
2. Check if MySQL service is running
3. Test database connection:
```bash
mysql -u your_username -p your_database_name
```
4. Ensure user has proper permissions

### Issue: "500 Internal Server Error"

**Solution:**
1. Check Apache error logs:
   - Hostinger: In hPanel → Files → Error Logs
   - Linux: `/var/log/apache2/error.log`
2. Common causes:
   - Incorrect `.htaccess` syntax
   - PHP errors (enable error display temporarily)
   - File permission issues
3. Add to `public/.htaccess` temporarily:
```apache
php_flag display_errors on
php_value error_reporting E_ALL
```

### Issue: "Access Denied" or "Permission Denied"

**Solution:**
1. Check file permissions:
```bash
# Should be 755 for directories
# Should be 644 for files
sudo chmod -R 755 /path/to/task-management-system
sudo chmod -R 775 uploads/ logs/
```
2. Verify web server user owns files:
```bash
sudo chown -R www-data:www-data /path/to/task-management-system
```

### Issue: Real-time updates not working

**Solution:**
1. Open browser developer console (F12)
2. Check for JavaScript errors
3. Go to Network tab
4. Verify AJAX requests to `/public/api/tasks.php?since=...` are successful
5. Check `assets/js/app.js` is loading
6. Clear browser cache

### Issue: File upload fails

**Solution:**
1. Check PHP upload limits:
```bash
php -i | grep upload_max_filesize
php -i | grep post_max_size
```
2. Increase limits in `php.ini` or `.htaccess`:
```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
```
3. Verify `uploads/` directory permissions (775)
4. Check disk space

### Issue: Session/Login problems

**Solution:**
1. Clear browser cookies
2. Check session directory is writable
3. Verify session settings in `config/app.php`
4. Ensure clock/timezone is correct on server
5. Check for conflicting session names

### Issue: "Headers already sent" error

**Solution:**
1. Check for whitespace before `<?php` tags
2. Ensure no output before `header()` calls
3. Check file encoding (should be UTF-8 without BOM)
4. Review PHP error message for specific file/line

## Additional Configuration

### Enable Debug Mode (Development Only)

Edit `config/app.php`:
```php
'debug' => true,
'display_errors' => true,
```

**Never enable in production!**

### Configure Email Notifications (Future Enhancement)

Add to `config/app.php`:
```php
'email' => [
    'from' => 'noreply@yourdomain.com',
    'smtp_host' => 'smtp.yourdomain.com',
    'smtp_port' => 587,
    'smtp_user' => 'your_email@yourdomain.com',
    'smtp_pass' => 'your_password',
],
```

### Backup Procedures

**Database Backup:**
```bash
# Command line
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Hostinger: Use phpMyAdmin Export feature
```

**File Backup:**
```bash
tar -czf backup_files_$(date +%Y%m%d).tar.gz task-management-system/
```

**Automated Backup (Cron):**
```bash
# Add to crontab
0 2 * * * /path/to/backup_script.sh
```

## Getting Help

If you encounter issues not covered here:

1. Check the main README.md for general information
2. Review Apache/PHP error logs
3. Enable debug mode (development only)
4. Contact your hosting support (for hosting-specific issues)
5. Open an issue in the project repository

## Next Steps

After successful installation:
1. ✅ Change default admin password
2. ✅ Create department users
3. ✅ Configure application settings
4. ✅ Set up SSL certificate (production)
5. ✅ Configure automated backups
6. ✅ Test all functionality
7. ✅ Import your existing data (if any)
8. ✅ Train users on the system

Congratulations! Your Task Management System is now installed and ready to use.
