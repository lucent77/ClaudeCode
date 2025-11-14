# Creodent AoX Elevate Dashboard - Deployment Guide for Hostinger

## Prerequisites

- Hostinger hosting account with:
  - PHP 8.0 or higher
  - MySQL database
  - SSH access (recommended)
  - Composer installed
  - Cron job support

## Step 1: Prepare Your Environment

### 1.1 Create MySQL Database

1. Log in to Hostinger control panel
2. Navigate to MySQL Databases
3. Create a new database: `creodent_aox`
4. Create a database user and grant all privileges
5. Note down the database credentials

### 1.2 Get Slack API Credentials

1. Go to https://api.slack.com/apps
2. Create a new app or use existing app
3. Enable required permissions:
   - `files:read` - Read files
   - `channels:read` - Read channel information
   - `users:read` - Read user information
4. Install app to your workspace
5. Note down the Bot Token (starts with `xoxb-`)
6. Get your Canvas ID from Slack

## Step 2: Upload Project Files

### Option A: Via SSH (Recommended)

```bash
# Connect to Hostinger via SSH
ssh your_username@your_domain.com

# Navigate to public_html or your web root
cd public_html

# Clone or upload project files
# If using git:
git clone your-repository-url .

# Or upload via SFTP to this directory
```

### Option B: Via File Manager

1. Compress project files locally (zip)
2. Upload via Hostinger File Manager
3. Extract in your web root directory

## Step 3: Install Dependencies

```bash
# SSH into your server
ssh your_username@your_domain.com

# Navigate to project directory
cd public_html

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Set proper permissions
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs
chmod -R 777 public/uploads
```

## Step 4: Configure Environment

```bash
# Copy example environment file
cp .env.example .env

# Edit environment file
nano .env
# or use File Manager text editor
```

Update `.env` with your settings:

```env
APP_NAME="Creodent AoX Dashboard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Generate key: php artisan key:generate
APP_KEY=base64:YOUR_GENERATED_KEY_HERE

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=creodent_aox
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SLACK_BOT_TOKEN=xoxb-your-slack-bot-token
SLACK_WORKSPACE_ID=your_workspace_id
SLACK_CANVAS_ID=your_canvas_id

FILE_STORAGE_STRATEGY=hybrid
SYNC_ENABLED=true
SYNC_INTERVAL_MINUTES=30

JWT_SECRET=YOUR_RANDOM_SECRET_HERE
```

## Step 5: Generate Application Key

```bash
php artisan key:generate
```

## Step 6: Run Database Migrations

```bash
# Run migrations to create tables
php artisan migrate

# If asked to confirm (production), type: yes
```

## Step 7: Create First Admin User

```bash
php artisan tinker

# In tinker console, run:
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@yourdomain.com';
$user->password = bcrypt('your-secure-password');
$user->role = 'admin';
$user->is_active = true;
$user->save();
exit
```

## Step 8: Configure Web Server

### For Apache (Hostinger default)

Create or edit `.htaccess` in your web root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect to public directory
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L,QSA]
</IfModule>
```

### Symlink for Storage (if needed)

```bash
php artisan storage:link
```

## Step 9: Set Up Cron Job for Auto-Sync

1. Log in to Hostinger control panel
2. Navigate to Advanced → Cron Jobs
3. Add new cron job:

**Command:**
```bash
/usr/bin/php /home/your_username/public_html/cron/sync.php >> /home/your_username/public_html/storage/logs/cron.log 2>&1
```

**Schedule:**
```
*/30 * * * *
```
(Runs every 30 minutes)

**Alternative schedule options:**
- Every hour: `0 * * * *`
- Every 15 minutes: `*/15 * * * *`
- Twice daily: `0 9,17 * * *`

## Step 10: Test the Installation

### 10.1 Test Web Access

Visit: `https://your-domain.com`

You should see the login page.

### 10.2 Test Login

- Email: `admin@yourdomain.com`
- Password: (the password you set in Step 7)

### 10.3 Test Slack Connection

1. Log in to dashboard
2. Click "Admin" button
3. Test Slack API connection

### 10.4 Test Manual Sync

1. Click "Sync" button in dashboard
2. Check if cases are synced from Slack Canvas

## Step 11: Security Hardening

### 11.1 Restrict File Permissions

```bash
# Secure .env file
chmod 600 .env

# Secure storage
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Make sure uploads directory is writable
chmod -R 777 public/uploads
```

### 11.2 Enable HTTPS

1. In Hostinger control panel
2. Go to Security → SSL/TLS
3. Enable SSL for your domain
4. Force HTTPS redirect

### 11.3 Disable Directory Listing

Add to `.htaccess`:
```apache
Options -Indexes
```

### 11.4 Hide Laravel Version

Remove or comment out in `public/index.php`:
```php
// Don't display Laravel version in headers
```

## Step 12: Configure Backup (Optional but Recommended)

### 12.1 Database Backup

Create a backup script `/home/your_username/backup-db.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/home/your_username/backups"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u your_db_user -p'your_db_password' creodent_aox > $BACKUP_DIR/db_backup_$DATE.sql
find $BACKUP_DIR -name "db_backup_*.sql" -mtime +7 -delete
```

Add to crontab (daily at 2 AM):
```
0 2 * * * /home/your_username/backup-db.sh
```

### 12.2 File Backup

Use Hostinger's built-in backup feature or configure manual backups.

## Troubleshooting

### Issue: 500 Internal Server Error

**Solutions:**
1. Check file permissions: `chmod -R 755 storage bootstrap/cache`
2. Check `.env` file exists and is configured
3. Check error logs: `tail -f storage/logs/laravel.log`

### Issue: Database Connection Error

**Solutions:**
1. Verify database credentials in `.env`
2. Check if database exists
3. Test connection: `mysql -u user -p database_name`

### Issue: Slack Sync Not Working

**Solutions:**
1. Verify Slack token in `.env`
2. Check Slack app permissions
3. Test connection via dashboard Admin panel
4. Check cron logs: `tail -f storage/logs/cron.log`

### Issue: Files Not Uploading

**Solutions:**
1. Check `public/uploads` permissions: `chmod -R 777 public/uploads`
2. Check PHP upload limits in `php.ini`:
   - `upload_max_filesize = 50M`
   - `post_max_size = 50M`

### Issue: Cron Job Not Running

**Solutions:**
1. Check cron syntax in Hostinger panel
2. Make sync script executable: `chmod +x cron/sync.php`
3. Check cron logs
4. Test manually: `php cron/sync.php`

## Maintenance

### Update Application

```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations if any
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Monitor Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Cron logs
tail -f storage/logs/cron.log

# Apache error logs
tail -f /path/to/apache/error.log
```

### Check Sync Status

Via dashboard:
1. Login as admin
2. Go to Admin panel
3. View Sync Logs

Or via command line:
```bash
php artisan tinker
App\Models\SyncLog::latest()->take(10)->get();
```

## Support

For issues or questions:
- Check Laravel documentation: https://laravel.com/docs
- Check Slack API docs: https://api.slack.com/docs
- Review application logs in `storage/logs/`

## Performance Optimization

### Enable OPcache

Add to `php.ini` or `.user.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Scaling Considerations

As your usage grows, consider:

1. **Database Optimization**: Add indexes, optimize queries
2. **File Storage**: Use CDN for large files
3. **Caching**: Implement Redis for session/cache
4. **Queue System**: Use queue workers for heavy tasks
5. **Horizontal Scaling**: Load balance across multiple servers

---

**Congratulations!** Your Creodent AoX Elevate Dashboard should now be fully deployed and operational on Hostinger.
