# CREODENT Work Management System - Deployment Guide

This guide provides step-by-step instructions for deploying the CREODENT Integrated Work Management System to production.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Installation Steps](#installation-steps)
4. [Configuration](#configuration)
5. [Database Setup](#database-setup)
6. [Evolution Portal Integration](#evolution-portal-integration)
7. [Windows App Integration](#windows-app-integration)
8. [Cron Jobs Setup](#cron-jobs-setup)
9. [Security Hardening](#security-hardening)
10. [Testing](#testing)
11. [Troubleshooting](#troubleshooting)
12. [Backup and Recovery](#backup-and-recovery)

---

## Prerequisites

- Hostinger hosting account with:
  - PHP 8.0 or higher
  - MySQL 8.0 or higher
  - Composer support
  - Cron job access
  - SSH access (optional but recommended)
- Evolution Portal V18 access credentials
- SQL Server access for HV and NYC locations
- Windows VB.NET application (optional)

---

## Server Requirements

### Minimum Requirements

- **PHP:** 8.0+
- **MySQL:** 8.0+
- **Disk Space:** 1 GB minimum
- **RAM:** 512 MB minimum (1 GB recommended)
- **PHP Extensions:**
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - curl
  - openssl
  - sqlsrv (for SQL Server direct connection)

### Recommended Requirements

- **PHP:** 8.1+
- **MySQL:** 8.0+
- **Disk Space:** 5 GB
- **RAM:** 2 GB
- **PHP Extensions:** All minimum + sqlsrv, redis (for caching)

---

## Installation Steps

### 1. Upload Files to Hostinger

#### Using File Manager (cPanel)

1. Log in to Hostinger cPanel
2. Go to File Manager
3. Navigate to `public_html` directory
4. Create a new folder: `creodent`
5. Upload all project files to `/public_html/creodent/`
6. Extract if uploaded as ZIP

#### Using FTP

```bash
# Using FileZilla or similar FTP client
Host: ftp.your-domain.com
Username: your_username
Password: your_password
Port: 21

# Upload to: /public_html/creodent/
```

#### Using Git (Recommended)

```bash
# SSH into Hostinger
ssh username@your-domain.com

# Navigate to public_html
cd public_html

# Clone repository
git clone https://github.com/your-repo/ClaudeCode.git creodent

# Or pull specific branch
cd creodent
git checkout main
```

### 2. Install Dependencies

```bash
# SSH into server
ssh username@your-domain.com

# Navigate to project directory
cd public_html/creodent

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# If composer is not available, install it first:
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php composer.phar install --no-dev --optimize-autoloader
```

### 3. Set Permissions

```bash
# Set proper permissions
chmod -R 755 public_html/creodent
chmod -R 777 storage/logs
chmod -R 777 storage/uploads
chmod 640 config/config.php
chmod 640 .env

# Set owner (replace 'username' with your hosting username)
chown -R username:username public_html/creodent
```

---

## Configuration

### 1. Configure Application

Edit `config/config.php`:

```php
<?php

return [
    // Application Settings
    'app' => [
        'name' => 'CREODENT Work Manager',
        'version' => '2.0.0',
        'env' => 'production',  // Change to production
        'debug' => false,       // IMPORTANT: Set to false in production
        'timezone' => 'America/New_York',
        'url' => 'https://your-domain.com',
        'session_lifetime' => 7200,
    ],

    // Database Configuration
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'u359033001_CADCAM_WORK1',
        'username' => 'u359033001_CADCAM_WORK1',
        'password' => 'Creo$10001',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    // Evolution Portal Configuration
    'evolution' => [
        'base_url' => 'https://your-evolution-portal-url.com',
        'username' => 'your_evolution_username',
        'password' => 'your_evolution_password',
        'timeout' => 30,
        'retry_count' => 3,
        'retry_delay' => 2,

        'sql_servers' => [
            'NYC' => [
                'enabled' => true,
                'host' => 'CDIMHN-SVR2-P,5000',
                'wan_ip' => '98.113.78.100',
                'database' => 'Evolution_Test',
                'username' => 'sa',
                'password' => 'your_nyc_password',
            ],
            'HV' => [
                'enabled' => true,
                'host' => 'CDIFSH-SVR2-P,8662',
                'wan_ip' => '71.169.7.90',
                'database' => 'Evolution_Test',
                'username' => 'sa',
                'password' => 'Cr@o3eetH',
            ],
        ],
    ],

    // Windows App Integration
    'windows_app' => [
        'enabled' => true,
        'api_key' => bin2hex(random_bytes(32)), // Generate new key for production
        'allowed_ips' => ['192.168.1.100'], // Add your Windows app IPs
    ],

    // Security Settings
    'security' => [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_special' => false,
        'max_login_attempts' => 5,
        'lockout_duration' => 900,
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'info', // Use 'info' in production, not 'debug'
        'path' => 'storage/logs',
        'filename_format' => 'Y-m-d',
        'max_files' => 30,
    ],
];
```

### 2. Configure .htaccess

Ensure `.htaccess` is properly configured for URL rewriting:

```apache
# .htaccess in root directory

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^(config\.php|composer\.json|composer\.lock|\.env)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## Database Setup

### 1. Create Database (via cPanel)

1. Log in to cPanel
2. Go to "MySQL Databases"
3. Create new database: `u359033001_CADCAM_WORK1`
4. Create user: `u359033001_CADCAM_WORK1`
5. Set password: `Creo$10001`
6. Add user to database with ALL PRIVILEGES

### 2. Import Schema

#### Using phpMyAdmin

1. Go to phpMyAdmin in cPanel
2. Select database: `u359033001_CADCAM_WORK1`
3. Click "Import"
4. Choose file: `database/schema.sql`
5. Click "Go"

#### Using SSH

```bash
mysql -u u359033001_CADCAM_WORK1 -p u359033001_CADCAM_WORK1 < database/schema.sql
```

### 3. Run Installation Wizard

Visit: `https://your-domain.com/install.php`

1. Click "Start Installation"
2. Database connection will be tested
3. Create super admin account
4. Complete installation
5. **IMPORTANT:** Delete `install.php` after completion

```bash
rm install.php
```

---

## Evolution Portal Integration

### 1. Test XML API Connection

```bash
curl -X POST "https://your-evolution-portal-url.com/api.php" \
  -H "Content-Type: application/xml" \
  -d '<?xml version="1.0"?>
      <request>
        <event>account_login</event>
        <username>your_username</username>
        <password>your_password</password>
      </request>'
```

Expected response:
```xml
<?xml version="1.0"?>
<response>
  <status>success</status>
  <message>Login successful</message>
</response>
```

### 2. Test SQL Server Connection

#### Install SQL Server PHP Extension

```bash
# For Ubuntu/Debian
sudo apt-get install php8.1-sqlsrv

# For CentOS/RHEL
sudo yum install php-sqlsrv

# Restart web server
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

#### Test Connection via Application

1. Log in as super admin
2. Go to: `/imports`
3. Click "Test Connection"
4. Verify both XML API and SQL Server connections

---

## Windows App Integration

### 1. Generate API Key

Run this PHP script once to generate a secure API key:

```php
<?php
echo "API Key: " . bin2hex(random_bytes(32)) . "\n";
```

Or use online generator: https://randomkeygen.com/

### 2. Update Configuration

Add the API key to `config/config.php`:

```php
'windows_app' => [
    'enabled' => true,
    'api_key' => 'your_generated_api_key_here',
    'allowed_ips' => [
        '192.168.1.100', // 3D Print workstation
        '192.168.1.101', // CoCr workstation
        '192.168.1.102', // Solidex workstation
    ],
],
```

### 3. Configure Windows Application

Update Windows app config with:
- **API URL:** `https://your-domain.com/api`
- **API Key:** (the generated key)

### 4. Test Connection

```bash
curl -X GET "https://your-domain.com/api/health" \
  -H "X-API-KEY: your_api_key"
```

Expected response:
```json
{
    "success": true,
    "message": "Service is healthy",
    "data": {
        "status": "online",
        "timestamp": "2025-11-04 10:30:00",
        "version": "2.0.0"
    }
}
```

---

## Cron Jobs Setup

### 1. Add Cron Jobs via cPanel

1. Log in to cPanel
2. Go to "Cron Jobs"
3. Add the following cron jobs:

**Daily HV Import (6:00 AM):**
```
0 6 * * * /usr/bin/php /home/username/public_html/creodent/cron/import-evolution.php HV 1 >> /home/username/public_html/creodent/storage/logs/cron-hv.log 2>&1
```

**Daily NYC Import (6:30 AM):**
```
30 6 * * * /usr/bin/php /home/username/public_html/creodent/cron/import-evolution.php NYC 1 >> /home/username/public_html/creodent/storage/logs/cron-nyc.log 2>&1
```

**Weekly Backup Sync (Sunday 2:00 AM):**
```
0 2 * * 0 /usr/bin/php /home/username/public_html/creodent/cron/import-evolution.php HV 7 >> /home/username/public_html/creodent/storage/logs/cron-hv-weekly.log 2>&1
```

### 2. Test Cron Jobs

```bash
# Test manually via SSH
ssh username@your-domain.com
cd public_html/creodent
php cron/import-evolution.php HV 1
```

### 3. Monitor Cron Logs

```bash
# View recent logs
tail -f storage/logs/cron-hv.log

# Check for errors
grep "Error" storage/logs/cron-*.log
```

---

## Security Hardening

### 1. SSL/TLS Certificate

Install SSL certificate via cPanel:
1. Go to "SSL/TLS Status"
2. Click "Run AutoSSL"
3. Or install Let's Encrypt certificate

### 2. File Permissions

```bash
# Restrict config file
chmod 640 config/config.php

# Protect logs
chmod 750 storage/logs
chmod 640 storage/logs/*.log

# Protect uploads
chmod 750 storage/uploads
```

### 3. Disable Directory Listing

Add to `.htaccess`:
```apache
Options -Indexes
```

### 4. Hide PHP Version

Add to `.htaccess`:
```apache
<IfModule mod_headers.c>
    Header unset X-Powered-By
</IfModule>
```

Or edit `php.ini`:
```ini
expose_php = Off
```

### 5. Enable HTTPS Only

Add to `.htaccess`:
```apache
# Redirect HTTP to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 6. Implement IP Whitelisting (Optional)

For admin panel, add to `.htaccess`:
```apache
<Location /admin>
    Order deny,allow
    Deny from all
    Allow from 192.168.1.0/24
    Allow from your.office.ip.address
</Location>
```

### 7. Regular Security Updates

```bash
# Update Composer dependencies regularly
composer update

# Check for security vulnerabilities
composer audit
```

---

## Testing

### 1. Test Authentication

- Visit: `https://your-domain.com/login`
- Log in with super admin credentials
- Verify dashboard loads correctly

### 2. Test Case Management

- Create a new case
- Edit the case
- Archive the case
- Delete the case

### 3. Test Evolution Import

- Manual import: `/imports`
- Test connection button
- Import with date range
- Verify cases appear in list

### 4. Test Windows App API

```bash
# Health check
curl -X GET "https://your-domain.com/api/health" \
  -H "X-API-KEY: your_api_key"

# Import 3D Print item
curl -X POST "https://your-domain.com/api/import/3dprint" \
  -H "X-API-KEY: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "case_no": "TEST-001",
        "patient_name": "Test Patient",
        "lab_name": "Test Lab",
        "work_type": "3DPRINT",
        "quantity": 1,
        "due_date": "2025-11-10"
      }
    ]
  }'
```

### 5. Test Cron Jobs

```bash
# Run manually
php cron/import-evolution.php HV 1

# Check output
cat storage/logs/cron-hv.log
```

---

## Troubleshooting

### Database Connection Error

**Problem:** "Database connection failed"

**Solution:**
1. Verify database credentials in `config/config.php`
2. Check if database exists in cPanel
3. Verify user has correct privileges
4. Test connection:
   ```bash
   mysql -u username -p database_name
   ```

### 500 Internal Server Error

**Problem:** Website shows 500 error

**Solution:**
1. Check PHP error logs: `tail -f storage/logs/php_errors.log`
2. Enable debug mode temporarily in `config/config.php`:
   ```php
   'debug' => true
   ```
3. Check .htaccess syntax
4. Verify file permissions

### Evolution Portal Connection Failed

**Problem:** Cannot fetch cases from Evolution Portal

**Solution:**
1. Test XML API connection (see above)
2. Verify firewall allows outbound connections
3. Check Evolution credentials
4. Try SQL Server direct connection instead

### Cron Jobs Not Running

**Problem:** Automated imports not working

**Solution:**
1. Verify cron job syntax in cPanel
2. Check PHP path: `which php`
3. Test script manually: `php cron/import-evolution.php HV 1`
4. Check cron logs for errors

### Windows App Cannot Connect

**Problem:** "Unauthorized" error from Windows app

**Solution:**
1. Verify API key in both config and Windows app
2. Check IP whitelist in `config/config.php`
3. Test health endpoint with curl
4. Check firewall rules

---

## Backup and Recovery

### 1. Database Backup

#### Manual Backup (via phpMyAdmin)

1. Go to phpMyAdmin
2. Select database
3. Click "Export"
4. Choose format: SQL
5. Click "Go"

#### Automated Backup (via cron)

Add to crontab:
```cron
# Daily backup at 1:00 AM
0 1 * * * mysqldump -u username -p'password' database_name | gzip > /path/to/backups/db-$(date +\%Y\%m\%d).sql.gz

# Delete backups older than 30 days
0 1 * * * find /path/to/backups -name "db-*.sql.gz" -mtime +30 -delete
```

### 2. File Backup

```bash
# Create backup of entire application
tar -czf creodent-backup-$(date +%Y%m%d).tar.gz public_html/creodent/

# Backup only uploaded files
tar -czf uploads-backup-$(date +%Y%m%d).tar.gz public_html/creodent/storage/uploads/
```

### 3. Restore from Backup

#### Restore Database

```bash
# Extract backup
gunzip db-20251104.sql.gz

# Restore
mysql -u username -p database_name < db-20251104.sql
```

#### Restore Files

```bash
# Extract backup
tar -xzf creodent-backup-20251104.tar.gz

# Copy to production
cp -r public_html/creodent/* /path/to/production/
```

---

## Post-Deployment Checklist

- [ ] All files uploaded to server
- [ ] Composer dependencies installed
- [ ] File permissions set correctly
- [ ] Configuration updated for production
- [ ] Database created and schema imported
- [ ] Installation wizard completed
- [ ] `install.php` deleted
- [ ] SSL certificate installed
- [ ] Evolution Portal connection tested
- [ ] Windows App API tested
- [ ] Cron jobs configured and tested
- [ ] First admin user created
- [ ] Security headers configured
- [ ] Backup system configured
- [ ] Error logging working
- [ ] All features tested
- [ ] Documentation reviewed by team

---

## Support and Maintenance

### Regular Maintenance Tasks

**Daily:**
- Monitor cron logs for import errors
- Check error logs for issues

**Weekly:**
- Review audit logs
- Check disk space usage
- Test backup restoration

**Monthly:**
- Update dependencies (`composer update`)
- Review user access and permissions
- Analyze system performance
- Clean up old logs and backups

### Getting Help

- **Documentation:** Check README.md and this deployment guide
- **Logs:** Review application logs in `storage/logs/`
- **GitHub Issues:** Report bugs at repository issues page
- **Email Support:** support@creodent.com

---

## Conclusion

Your CREODENT Work Management System should now be fully deployed and operational. Regular monitoring and maintenance will ensure smooth operation and optimal performance.

For any questions or issues not covered in this guide, please refer to the main documentation or contact support.

**Happy Managing!** 🦷✨
