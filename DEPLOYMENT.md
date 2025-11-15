# Deployment Guide - Creodent AoX Dashboard

This guide provides step-by-step instructions for deploying the Creodent AoX Dashboard to Hostinger or any PHP hosting service.

## 📋 Pre-Deployment Checklist

- [ ] Hostinger account with PHP 7.4+ support
- [ ] Database created in Hostinger control panel
- [ ] Slack Bot Token obtained
- [ ] FTP/SFTP credentials ready
- [ ] All configuration values prepared

## 🚀 Deployment Steps

### Step 1: Prepare Your Hostinger Environment

1. **Log into Hostinger Control Panel**
   - Go to https://hpanel.hostinger.com

2. **Create MySQL Database**
   - Navigate to "Databases" → "MySQL Databases"
   - Click "Create New Database"
   - Database name: `u359033001_SLACK` (or your preferred name)
   - Username: `u359033001_SLACK`
   - Password: Create a strong password
   - Click "Create"

3. **Note Your Database Credentials**
   ```
   Host: localhost (or 127.0.0.1)
   Port: 3306
   Database: u359033001_SLACK
   Username: u359033001_SLACK
   Password: [your password]
   ```

### Step 2: Upload Files to Hostinger

#### Option A: Using File Manager (Recommended for beginners)

1. In Hostinger control panel, go to "Files" → "File Manager"
2. Navigate to `public_html` directory
3. Upload all files from your local `ClaudeCode` directory
4. Ensure the following structure:
   ```
   public_html/
   ├── index.php          # Main entry point
   ├── api.php            # API entry point
   ├── login.php          # Login page
   ├── dashboard.php      # Dashboard page
   ├── case-detail.php    # Case detail page
   ├── .htaccess          # Apache configuration
   ├── app/               # Application logic
   ├── config/            # Configuration files
   ├── database/          # Database setup
   ├── routes/            # API routes
   ├── storage/           # File storage and logs
   ├── js/                # JavaScript files
   └── css/               # Stylesheets
   ```

#### Option B: Using FTP/SFTP

1. Use FileZilla or similar FTP client
2. Connect using your Hostinger FTP credentials:
   - Host: ftp.yourdomain.com
   - Username: [from Hostinger]
   - Password: [from Hostinger]
   - Port: 21 (FTP) or 22 (SFTP)
3. Upload all files to `public_html`

### Step 3: Configure the Application

1. **Update Database Configuration**

   Edit `public_html/config/database.php`:
   ```php
   'mysql' => [
       'host' => '127.0.0.1',
       'port' => '3306',
       'database' => 'u359033001_SLACK', // Your database name
       'username' => 'u359033001_SLACK', // Your username
       'password' => 'your_password',    // Your password
   ],
   ```

2. **Update Slack Configuration**

   Edit `public_html/config/services.php`:
   ```php
   'slack' => [
       'bot_token' => 'xoxb-YOUR-ACTUAL-BOT-TOKEN',
       'workspace_id' => 'T-YOUR-WORKSPACE-ID',
       'canvas_id' => 'F-YOUR-CANVAS-ID',
   ],
   ```

3. **Update Application URL**

   Edit `public_html/config/app.php`:
   ```php
   'url' => 'https://yourdomain.com', // Your actual domain
   'debug' => false, // Set to false for production
   ```

### Step 4: Initialize Database

#### Option A: Using phpMyAdmin (Recommended)

1. In Hostinger control panel, go to "Databases" → "phpMyAdmin"
2. Select your database (`u359033001_SLACK`)
3. Click "Import" tab
4. Choose file: `public_html/database/setup.sql`
5. Click "Go"
6. Wait for "Import has been successfully finished"

#### Option B: Using SSH (Advanced)

```bash
ssh your_username@your_server
cd public_html
mysql -u u359033001_SLACK -p u359033001_SLACK < database/setup.sql
```

### Step 5: Set File Permissions

Using File Manager or SSH:

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public
chmod 644 .htaccess
```

Or in File Manager:
- Right-click on `storage` folder → Permissions → Set to 755
- Right-click on `bootstrap/cache` → Permissions → Set to 755
- Right-click on `public` folder → Permissions → Set to 755

### Step 6: Configure Web Root (Important!)

⚠️ **This step is crucial for security**

1. In Hostinger control panel, go to "Website" → "Settings"
2. Find "Document Root" or "Web Root" setting
3. Change from `public_html` to `public_html/public`
4. Save changes

This ensures that only the `public` directory is accessible from the web.

### Step 7: Configure SSL Certificate (Recommended)

1. In Hostinger control panel, go to "Security" → "SSL"
2. Enable SSL certificate for your domain
3. Force HTTPS redirect:

   Add to `.htaccess` (if not already present):
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{HTTPS} off
       RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   </IfModule>
   ```

### Step 8: Verify Installation

1. **Test Database Connection**
   - Visit: `https://yourdomain.com/api/sync/test`
   - Should return JSON with Slack connection status

2. **Test Login Page**
   - Visit: `https://yourdomain.com/login`
   - Should see login form

3. **Login with Default Credentials**
   - Email: `admin@creodent.com`
   - Password: `admin123`

4. **Change Default Password Immediately!**
   - Go to Profile settings
   - Update password to something secure

### Step 9: Test Slack Integration

1. Login to dashboard
2. Click "Sync with Slack" button
3. Check for successful sync
4. Verify cases appear in dashboard

## 🔧 Post-Deployment Configuration

### Update Default Admin Password

**CRITICAL: Do this immediately after deployment!**

1. Login with default credentials
2. Generate a new password hash:
   ```php
   <?php
   echo password_hash('your_new_password', PASSWORD_BCRYPT);
   ```
3. Update in database:
   ```sql
   UPDATE users
   SET password = '$2y$12$...'
   WHERE email = 'admin@creodent.com';
   ```

### Configure Automatic Sync (Optional)

Set up a cron job to sync automatically:

1. In Hostinger control panel, go to "Advanced" → "Cron Jobs"
2. Add new cron job:
   ```
   */30 * * * * curl -X POST https://yourdomain.com/api/sync
   ```
   This syncs every 30 minutes.

### Enable Error Logging

Edit `config/app.php`:
```php
'debug' => false, // Must be false in production
```

Check logs in `storage/logs/laravel.log`

## 🐛 Troubleshooting

### Issue: White Screen / 500 Error

**Solution:**
1. Check `storage/logs/laravel.log` for errors
2. Verify file permissions (755 for directories, 644 for files)
3. Enable error display temporarily:
   ```php
   // In public/index.php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

### Issue: Database Connection Failed

**Solution:**
1. Verify credentials in `config/database.php`
2. Check database exists in phpMyAdmin
3. Test connection:
   ```php
   <?php
   $pdo = new PDO(
       'mysql:host=127.0.0.1;dbname=u359033001_SLACK',
       'u359033001_SLACK',
       'your_password'
   );
   echo "Connected!";
   ```

### Issue: Login Not Working

**Solution:**
1. Clear browser cache and cookies
2. Check JWT secret is set in `config/jwt.php`
3. Verify admin user exists:
   ```sql
   SELECT * FROM users WHERE email = 'admin@creodent.com';
   ```

### Issue: Slack Sync Failing

**Solution:**
1. Test connection: `/api/sync/test`
2. Verify bot token is correct
3. Check bot has required permissions
4. Review sync logs in database:
   ```sql
   SELECT * FROM sync_logs ORDER BY started_at DESC LIMIT 10;
   ```

### Issue: Files Not Uploading

**Solution:**
1. Check PHP upload limits in `.htaccess`
2. Verify `storage/app/attachments` exists and is writable
3. Check disk space on server

## 📊 Monitoring and Maintenance

### Daily Tasks

- [ ] Check application logs
- [ ] Monitor sync success rate
- [ ] Review case activity

### Weekly Tasks

- [ ] Backup database
- [ ] Review sync logs for errors
- [ ] Check disk space usage

### Monthly Tasks

- [ ] Update admin password
- [ ] Review user access logs
- [ ] Clean old log files

## 🔒 Security Best Practices

1. **Change Default Credentials**
   - Update admin password immediately
   - Use strong, unique passwords

2. **Keep Tokens Secret**
   - Never commit tokens to git
   - Use environment variables in production

3. **Regular Backups**
   - Backup database daily
   - Keep backups in secure location

4. **Update Dependencies**
   - Keep PHP version updated
   - Monitor for security patches

5. **Monitor Access**
   - Review activity logs regularly
   - Set up alerts for failed logins

## 📞 Support

If you encounter issues:

1. Check this guide first
2. Review `storage/logs/laravel.log`
3. Check Hostinger knowledge base
4. Contact support: support@creodent.com

## ✅ Deployment Verification Checklist

- [ ] Files uploaded successfully
- [ ] Database created and populated
- [ ] Configuration files updated
- [ ] File permissions set correctly
- [ ] Web root configured to `public` directory
- [ ] SSL certificate enabled
- [ ] Login page accessible
- [ ] Can login with default credentials
- [ ] Default password changed
- [ ] Dashboard loads correctly
- [ ] Slack connection test passes
- [ ] Sync functionality works
- [ ] Cases display in dashboard
- [ ] API endpoints responding
- [ ] Error logging configured
- [ ] Backup system in place

---

**Congratulations! Your Creodent AoX Dashboard is now deployed! 🎉**
