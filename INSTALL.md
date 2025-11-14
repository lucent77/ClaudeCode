# Quick Installation Guide
## Creodent AoX Elevate Dashboard

Follow these steps to get your dashboard up and running in minutes.

## Prerequisites Checklist

Before you begin, make sure you have:

- [ ] Hosting account (Hostinger, cPanel, or VPS)
- [ ] PHP 7.4 or higher
- [ ] MySQL 5.7 or higher
- [ ] Slack workspace with admin access
- [ ] FTP/SSH access to your server

## Step-by-Step Installation

### 1. Upload Files (5 minutes)

**IMPORTANT FOR HOSTINGER USERS**:
- Upload ALL files directly to `public_html/` directory
- The `index.php` file MUST be in the root (`public_html/index.php`)
- This structure is optimized for Hostinger's default PHP configuration

**File Structure After Upload:**
```
public_html/
├── index.php          ← Must be here!
├── case.php
├── .htaccess
├── api/
├── config/
├── database/
├── includes/
└── uploads/
```

**Option A: Via FTP (Hostinger/cPanel)**
1. Connect to your hosting via FTP (FileZilla recommended)
2. Upload all files to `public_html/` directory
3. Verify that `index.php` is in the root of `public_html/`

**Option B: Via SSH/Git**
```bash
cd /path/to/web/directory
git clone https://github.com/yourusername/creodent-aox-dashboard.git .
```

### 2. Create Database (3 minutes)

**Via cPanel:**
1. Log into cPanel
2. Go to "MySQL Databases"
3. Create new database: `creodent_aox`
4. Create new MySQL user with password
5. Add user to database with ALL PRIVILEGES
6. Go to phpMyAdmin
7. Select your database
8. Click "Import" tab
9. Choose file: `database/schema.sql`
10. Click "Go"

**Via SSH:**
```bash
mysql -u root -p
CREATE DATABASE creodent_aox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'creodent_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON creodent_aox.* TO 'creodent_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u creodent_user -p creodent_aox < database/schema.sql
```

### 3. Configure Application (5 minutes)

1. Copy configuration template:
```bash
cp config/config.example.php config/config.php
```

2. Edit `config/config.php`:

```php
// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'creodent_aox');
define('DB_USER', 'creodent_user');
define('DB_PASS', 'your_secure_password');

// Slack settings (get token in next step)
define('SLACK_BOT_TOKEN', 'xoxb-paste-your-token-here');

// Application URL
define('APP_URL', 'https://yourdomain.com');
```

3. Set file permissions:
```bash
chmod 600 config/config.php
chmod 755 public/uploads
```

### 4. Set Up Slack Bot (10 minutes)

1. **Create Slack App:**
   - Go to: https://api.slack.com/apps
   - Click "Create New App"
   - Choose "From scratch"
   - App Name: "AoX File Manager"
   - Pick your workspace
   - Click "Create App"

2. **Configure Bot Permissions:**
   - In left sidebar, click "OAuth & Permissions"
   - Scroll to "Scopes" section
   - Under "Bot Token Scopes", click "Add an OAuth Scope"
   - Add these scopes:
     - `files:read` - View files in workspace
     - `files:write` - Upload files
     - `channels:history` - View messages in public channels
     - `groups:history` - View messages in private channels

3. **Install App to Workspace:**
   - Scroll to top of "OAuth & Permissions" page
   - Click "Install to Workspace"
   - Click "Allow"

4. **Copy Bot Token:**
   - After installation, you'll see "Bot User OAuth Token"
   - It starts with `xoxb-`
   - Copy this token
   - Paste it into `config/config.php` as `SLACK_BOT_TOKEN`

5. **Invite Bot to Channels (Important!):**
   - Open Slack
   - Go to each channel where files are stored
   - Type: `/invite @AoX File Manager`
   - Press Enter

### 5. Test Installation (2 minutes)

1. Open your browser
2. Navigate to your domain (e.g., `https://yourdomain.com`)
3. You should see the dashboard
4. Click "Sync Slack Files" button
5. Wait for sync to complete
6. You should see cases appear!

### 6. Set Up Automatic Sync (3 minutes)

**Via cPanel Cron Jobs:**
1. Log into cPanel
2. Find "Cron Jobs"
3. Add new cron job:
   - **Minute:** `*/5` (every 5 minutes)
   - **Hour:** `*`
   - **Day:** `*`
   - **Month:** `*`
   - **Weekday:** `*`
   - **Command:** `php /home/username/public_html/api/sync.php`
4. Click "Add New Cron Job"

**Via Command Line:**
```bash
crontab -e
# Add this line:
*/5 * * * * php /path/to/your/public/api/sync.php > /dev/null 2>&1
```

## Verification Checklist

After installation, verify everything works:

- [ ] Dashboard loads at your domain
- [ ] Statistics show on homepage
- [ ] "Sync Slack Files" button works
- [ ] Cases appear after sync
- [ ] Can click on a case to view details
- [ ] Files are displayed in case detail page
- [ ] Activity log shows sync events

## Common Issues & Solutions

### Issue: "Database connection failed"
**Solution:**
- Verify DB credentials in `config/config.php`
- Test MySQL connection: `mysql -u username -p database_name`
- Check if MySQL service is running

### Issue: "Slack API Error" or "Failed to connect"
**Solution:**
- Double-check bot token in config
- Verify bot has correct scopes
- Make sure bot is invited to channels
- Test token: `curl -H "Authorization: Bearer xoxb-your-token" https://slack.com/api/auth.test`

### Issue: "No cases found after sync"
**Solution:**
- Check sync logs in database: `SELECT * FROM sync_logs ORDER BY created_at DESC LIMIT 5;`
- Verify file naming matches patterns (see README.md)
- Check if files exist in Slack workspace
- Look for errors in sync_logs.error_message

### Issue: "Permission denied" errors
**Solution:**
```bash
# Set correct permissions
chmod 755 public/
chmod 755 public/uploads/
chmod 644 public/*.php
chmod 600 config/config.php
```

### Issue: Photos not displaying
**Solution:**
- Check if preview_url is stored in database
- Verify Slack bot has access to files
- Try opening file URL directly in browser
- Check browser console for CORS errors

## Security Recommendations

### For Production:

1. **Enable HTTPS:**
   - Get free SSL certificate (Let's Encrypt via cPanel)
   - Uncomment HTTPS redirect in `.htaccess`

2. **Protect Config:**
```bash
chmod 600 config/config.php
```

3. **Add API Authentication:**
   - Edit API files to require API key
   - Use environment variables for secrets

4. **Regular Backups:**
   - Set up daily database backups
   - Keep backups of uploaded files

5. **Update Regularly:**
```bash
git pull origin main
```

## Getting Help

If you encounter issues:

1. Check error logs:
   - PHP error log: Usually in `/var/log/php-errors.log`
   - Apache error log: `/var/log/apache2/error.log`
   - Check cPanel error logs

2. Enable debug mode:
   - In `config/config.php`, set `define('APP_DEBUG', true);`
   - Check browser console for JavaScript errors

3. Test API endpoints:
```bash
# Test database connection
curl http://yourdomain.com/api/stats.php

# Test Slack sync
curl http://yourdomain.com/api/sync.php

# Get all cases
curl http://yourdomain.com/api/cases.php
```

## Next Steps

After successful installation:

1. **Customize File Patterns**: Edit `FILE_PATTERNS` in config to match your naming conventions
2. **Invite Team**: Share the dashboard URL with your team
3. **Set Up Naming Standards**: Ensure everyone follows file naming conventions
4. **Monitor Sync Logs**: Check sync_logs table regularly
5. **Plan Features**: Review roadmap in README.md

## Support

- GitHub Issues: [Create an issue](https://github.com/yourusername/creodent-aox-dashboard/issues)
- Email: support@creodent.com
- Documentation: See README.md

---

**Congratulations! Your AoX Dashboard is now running! 🎉**

Total setup time: ~30 minutes
