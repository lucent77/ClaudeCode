# Hostinger Installation Guide

## Quick Start Guide for Hostinger Hosting

### Step-by-Step Installation

#### 1. Prepare Your Files

**Option A: Download ZIP**
- Download the project as a ZIP file
- Extract it to your local computer

**Option B: Use Git (if available)**
```bash
git clone <repository-url>
```

#### 2. Access Hostinger Control Panel

1. Log into your Hostinger account at https://hpanel.hostinger.com
2. Select your hosting plan
3. Go to **File Manager** or use **FTP/SFTP**

#### 3. Upload Files to Hostinger

**Using File Manager:**
1. Click **File Manager** in hPanel
2. Navigate to `public_html` directory
3. Delete default `index.html` if present
4. Click **Upload Files**
5. Upload all project files OR upload ZIP and extract

**Using FTP Client (FileZilla, etc.):**
1. Get FTP credentials from hPanel → **FTP Accounts**
2. Connect to your server
3. Navigate to `public_html`
4. Upload all files maintaining folder structure

**Final structure should look like:**
```
public_html/
├── index.php               # Main files in root
├── login.php
├── logout.php
├── task_detail.php
├── sync.php
├── users.php
├── .htaccess
├── api/
├── config/
│   └── .htaccess          # Protected from web access
├── includes/
│   └── .htaccess          # Protected from web access
├── uploads/
├── database.sql
└── README.md
```

#### 4. Create MySQL Database

1. In hPanel, go to **Databases** → **MySQL Databases**
2. Click **Create New Database**
3. Enter database name: `slack_list_manager` (or your choice)
4. Click **Create**
5. Note down:
   - Database name
   - Username
   - Password
   - Host (usually `localhost`)

#### 5. Import Database Schema

**Method 1: phpMyAdmin (Recommended)**
1. In hPanel, click **phpMyAdmin**
2. Select your database from left sidebar
3. Click **Import** tab
4. Click **Choose File**
5. Select `database.sql` from your computer
6. Click **Go** at bottom
7. Wait for success message

**Method 2: File Manager + Import**
1. Upload `database.sql` to File Manager
2. Use phpMyAdmin import tool
3. Delete `database.sql` from server after import

#### 6. Configure Application

1. In File Manager, navigate to `public_html/config/`
2. Right-click `config.php` → **Edit**
3. Update these lines:

```php
// Database Settings - UPDATE THESE!
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_actual_database_name');  // Change this
define('DB_USER', 'your_database_username');     // Change this
define('DB_PASS', 'your_database_password');     // Change this

// Website URL - UPDATE THIS!
define('BASE_URL', 'https://yourdomain.com');    // Change to your domain

// Security Key - MUST CHANGE!
define('ENCRYPTION_KEY', 'generate-random-key-here');  // Use random string
```

**To generate a random encryption key:**
- Use https://www.random.org/strings/
- Or use this PHP snippet: `echo bin2hex(random_bytes(32));`
- Copy the result and paste as ENCRYPTION_KEY

4. Click **Save Changes**

#### 7. Set Directory Permissions

1. In File Manager, right-click `uploads` folder
2. Select **Permissions**
3. Set to **755** (or 775 if 755 doesn't work)
4. Click **Apply**

#### 8. Test Installation

1. Open your website in browser: `https://yourdomain.com`
2. You should see the login page
3. If you see errors, check Step 9 below

#### 9. First Login

**Default Credentials:**
- **Admin Account**
  - Username: `admin`
  - Password: `password`

- **Worker Account**
  - Username: `worker`
  - Password: `password`

**IMPORTANT**: Change these passwords immediately!

#### 10. Post-Installation Setup

1. **Change Admin Password**
   - Log in as admin
   - Go to Users page
   - Update password

2. **Create New Users**
   - Add team members with appropriate roles

3. **Sync Your First Data**
   - Export CSV from Slack List
   - Go to Sync page
   - Upload CSV file

## Common Installation Issues

### Issue: "Database connection failed"

**Solution:**
- Verify database credentials in `config/config.php`
- Check database exists in hPanel
- Ensure database user has ALL PRIVILEGES
- Try changing `DB_HOST` to server IP if `localhost` fails

### Issue: "Blank white page"

**Solution:**
- Enable error reporting temporarily
- Add to top of `config/config.php`:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Refresh page to see actual error
- Fix error, then disable error display

### Issue: "Cannot upload files"

**Solution:**
- Check `uploads` folder exists
- Set permissions to 755 or 775
- Check PHP upload limits in hPanel
- Increase if needed: `upload_max_filesize` and `post_max_size`

### Issue: "Session errors"

**Solution:**
- Clear browser cookies
- Check PHP session settings in `.htaccess`
- Ensure session directory is writable

### Issue: "404 Not Found" on pages

**Solution:**
- Check `.htaccess` file exists in `public` folder
- Verify mod_rewrite is enabled (usually is on Hostinger)
- Update all page links to use full paths

## Hostinger-Specific Settings

### PHP Version
- Recommended: PHP 7.4 or higher
- Change in hPanel → **Advanced** → **PHP Configuration**

### Enable HTTPS (SSL)
1. In hPanel, go to **SSL**
2. Click **Setup** next to your domain
3. Select **Free SSL** (Let's Encrypt)
4. Wait for activation (5-10 minutes)

### Update .htaccess for HTTPS
Once SSL is active, uncomment these lines in `public/.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Increase PHP Limits (if needed)
1. Go to hPanel → **Advanced** → **PHP Configuration**
2. Adjust:
   - `upload_max_filesize`: 50M
   - `post_max_size`: 50M
   - `max_execution_time`: 300
   - `memory_limit`: 256M

## Security Checklist

- [ ] Changed default admin password
- [ ] Changed default worker password
- [ ] Updated ENCRYPTION_KEY in config.php
- [ ] Enabled HTTPS/SSL
- [ ] Set proper file permissions (755 for folders, 644 for files)
- [ ] Removed or secured database.sql file after import
- [ ] Disabled error display in production
- [ ] Set strong database password
- [ ] Regular backups enabled in Hostinger

## Backup Strategy

### Automatic Backups (Hostinger)
1. Go to hPanel → **Backups**
2. Enable automatic backups
3. Set frequency (daily recommended)

### Manual Backup
1. **Files**: Download via FTP or File Manager
2. **Database**: Export via phpMyAdmin
3. Store securely off-server

## Getting Help

**Hostinger Support:**
- Live Chat: Available 24/7 in hPanel
- Knowledge Base: https://support.hostinger.com
- Email: Via support ticket system

**Application Issues:**
- Check `error_log` in File Manager
- Review activity logs in database
- Consult README.md for feature documentation

## Maintenance Tips

1. **Weekly**: Check for sync errors, review user activity
2. **Monthly**: Database backup, review file storage usage
3. **Quarterly**: Update PHP version, security audit
4. **As Needed**: Sync new data from Slack

## Next Steps After Installation

1. ✅ Complete installation
2. ✅ Change default passwords
3. ✅ Create user accounts for team
4. ✅ Export and upload first CSV from Slack
5. ✅ Test all features
6. ✅ Enable HTTPS
7. ✅ Setup automatic backups
8. ✅ Train team members on usage

---

**Need Help?** Review the troubleshooting section or contact your system administrator.

**Installation Time:** Approximately 15-30 minutes for first-time setup.
