# Hostinger Deployment Guide
## Creodent AoX Elevate Dashboard

This document provides specific instructions for deploying to Hostinger.

## ✅ Hostinger-Ready Structure

The project is now structured with `index.php` in the root directory, which is required by Hostinger's default PHP web service configuration.

## 📁 Correct File Structure for Hostinger

After uploading to Hostinger, your `public_html/` should look like this:

```
public_html/
├── index.php              ← MUST BE HERE (Root file)
├── case.php               ← Case detail page
├── .htaccess              ← Apache configuration (included)
│
├── api/                   ← API endpoints
│   ├── cases.php
│   ├── sync.php
│   └── stats.php
│
├── config/                ← Configuration
│   ├── config.php         ← Edit this with your credentials
│   └── config.example.php
│
├── database/              ← Database schema
│   └── schema.sql
│
├── includes/              ← PHP classes
│   ├── CaseModel.php
│   ├── Database.php
│   ├── FileClassifier.php
│   └── SlackClient.php
│
├── uploads/               ← File uploads (auto-created)
└── assets/                ← Static assets (optional)
```

## 🚀 Quick Deployment Steps

### 1. Upload Files

**Via Hostinger File Manager:**
1. Log into Hostinger control panel
2. Go to "Files" → "File Manager"
3. Navigate to `public_html/`
4. Delete any existing `index.php` or default files
5. Upload ALL project files directly to `public_html/`
6. Verify `index.php` is in the root

**Via FTP (FileZilla):**
1. Connect to your Hostinger account
   - Host: Your domain or FTP hostname
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21
2. Navigate to `public_html/`
3. Upload all files (drag and drop entire folder contents)
4. Verify structure matches above

### 2. Create MySQL Database

1. In Hostinger control panel, go to "Databases" → "MySQL Databases"
2. Click "Create Database"
3. Database name: `u123456789_creodent` (Hostinger format)
4. Create a database user with strong password
5. Grant ALL PRIVILEGES to the user
6. Note down:
   - Database name
   - Database user
   - Database password
   - Database host (usually `localhost`)

### 3. Import Database Schema

**Option A: Via phpMyAdmin (Recommended)**
1. In Hostinger, go to "Databases" → "phpMyAdmin"
2. Click on your database name
3. Click "Import" tab
4. Click "Choose File"
5. Select `database/schema.sql` from your computer
6. Click "Go" at the bottom
7. Wait for success message

**Option B: Via File Manager + Command**
1. Upload `schema.sql` to `public_html/database/`
2. In Hostinger control panel, use Terminal (if available)
3. Run:
   ```bash
   mysql -u your_db_user -p your_db_name < database/schema.sql
   ```

### 4. Configure Application

1. In File Manager, navigate to `public_html/config/`
2. Right-click `config.php` → "Edit"
3. Update these values:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_creodent');  // Your database name
define('DB_USER', 'u123456789_user');       // Your database user
define('DB_PASS', 'your_secure_password');  // Your database password

// Slack Configuration
define('SLACK_BOT_TOKEN', 'xoxb-your-actual-slack-token');

// Application URL
define('APP_URL', 'https://yourdomain.com');  // Your actual domain
```

4. Save the file

### 5. Set Permissions

In File Manager:
1. Right-click `uploads/` folder
2. Select "Permissions"
3. Set to `755` or check "Read, Write, Execute" for owner
4. Apply to all subdirectories

### 6. Test Installation

1. Open browser and go to your domain: `https://yourdomain.com`
2. You should see the dashboard
3. Check if statistics show "0" (normal for new installation)
4. Click "Sync Slack Files" to test

### 7. Set Up Cron Job (Automatic Sync)

1. In Hostinger control panel, go to "Advanced" → "Cron Jobs"
2. Click "Create Cron Job"
3. Configure:
   - **Type**: Custom
   - **Command**: `php /home/u123456789/public_html/api/sync.php`
   - **Frequency**: Every 5 minutes (*/5 * * * *)
4. Save

**Note**: Replace `/home/u123456789/` with your actual home directory path (shown in File Manager).

## 🔍 Verification Checklist

After deployment, verify:

- [ ] Dashboard loads at your domain
- [ ] No 404 errors (if you see 404, check index.php location)
- [ ] Statistics cards show on homepage
- [ ] Can click "Sync Slack Files" button
- [ ] Database connection works (no errors)
- [ ] API endpoints work (`yourdomain.com/api/stats.php`)

## ❗ Common Hostinger Issues

### Issue: "404 Not Found" when accessing homepage

**Solution:**
- Verify `index.php` is directly in `public_html/`
- NOT in a subdirectory like `public_html/public/`
- File name must be exactly `index.php` (lowercase)

### Issue: "Database connection failed"

**Solution:**
- Double-check database credentials in `config/config.php`
- Use the FULL database name including prefix (e.g., `u123456789_creodent`)
- Use the FULL database username including prefix
- Verify database user has ALL PRIVILEGES

### Issue: "Permission denied" for uploads

**Solution:**
- In File Manager, right-click `uploads/` → Permissions → 755
- Make sure folder exists (create if needed)

### Issue: Slack sync not working

**Solution:**
- Verify Slack bot token in `config/config.php`
- Check token starts with `xoxb-`
- Ensure bot has correct permissions in Slack
- Test manually: `yourdomain.com/api/sync.php`

### Issue: White screen / blank page

**Solution:**
- Enable debug mode in `config/config.php`:
  ```php
  define('APP_DEBUG', true);
  ```
- Check error logs in Hostinger control panel
- Verify PHP version is 7.4 or higher

## 🎯 URLs You Should Test

After deployment, these URLs should work:

- `https://yourdomain.com` - Dashboard homepage
- `https://yourdomain.com/case.php?id=1` - Case detail page
- `https://yourdomain.com/api/stats.php` - Statistics API
- `https://yourdomain.com/api/cases.php` - Cases API
- `https://yourdomain.com/api/sync.php` - Sync API (triggers sync)

## 📞 Need Help?

If you encounter issues:

1. **Check Error Logs**:
   - Hostinger Control Panel → Files → Error Log
   - Look for PHP errors

2. **Enable Debug Mode**:
   - Edit `config/config.php`
   - Set `define('APP_DEBUG', true);`
   - Refresh page and check for detailed errors

3. **Test Individual Components**:
   - Database: Try accessing phpMyAdmin
   - PHP: Create test file with `<?php phpinfo(); ?>`
   - Permissions: Check File Manager permissions

4. **Verify Hostinger Plan**:
   - Ensure you have PHP support
   - Ensure you have MySQL database access
   - Check PHP version is 7.4+

## ✅ Success Criteria

Your deployment is successful when:

1. Dashboard loads at your domain
2. Statistics show real numbers (after sync)
3. Cases appear in grid view
4. Can click into case details
5. Files display in galleries
6. Sync button works without errors
7. Cron job runs every 5 minutes

## 🎉 Next Steps After Successful Deployment

1. **Secure Your Installation**:
   - Change database password to something strong
   - Ensure `config.php` has correct permissions (644)
   - Enable HTTPS if not already enabled

2. **Customize**:
   - Update file classification patterns if needed
   - Adjust sync frequency in cron job
   - Add team member access

3. **Monitor**:
   - Check sync logs regularly
   - Monitor disk space (uploads folder)
   - Review error logs weekly

---

**Congratulations! Your Creodent AoX Dashboard is now live on Hostinger! 🎉**

Need the full documentation? See `README.md`
Need step-by-step setup? See `INSTALL.md`
