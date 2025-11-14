# Quick Start Guide

## 1. Prerequisites Check

Before starting, ensure you have:
- [x] Hostinger hosting account
- [x] PHP 8.0+
- [x] MySQL database created
- [x] Slack Bot Token
- [x] SSH or FTP access

## 2. Installation (5 minutes)

### Step 1: Upload Files
```bash
# Via SSH
git clone <your-repo> /path/to/public_html
# Or upload via FTP
```

### Step 2: Install Dependencies
```bash
cd /path/to/public_html
composer install --no-dev
```

### Step 3: Configure
```bash
cp .env.example .env
nano .env
```

Required `.env` settings:
```env
APP_URL=https://your-domain.com
DB_DATABASE=creodent_aox
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
SLACK_BOT_TOKEN=xoxb-your-token
SLACK_CANVAS_ID=your-canvas-id
```

### Step 4: Setup Database
```bash
php artisan key:generate
php artisan migrate
```

### Step 5: Create Admin User
```bash
php artisan tinker
```
```php
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@example.com';
$user->password = bcrypt('secure-password');
$user->role = 'admin';
$user->save();
exit
```

### Step 6: Set Permissions
```bash
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs
chmod -R 777 public/uploads
chmod 600 .env
```

### Step 7: Setup Cron (Optional)
In Hostinger Cron Jobs panel:
```bash
*/30 * * * * php /path/to/public_html/cron/sync.php
```

## 3. First Login

1. Visit: `https://your-domain.com`
2. Login with admin credentials
3. Click "Sync" to import from Slack
4. Done! 🎉

## 4. Common First Tasks

### Test Slack Connection
1. Click "Admin" button
2. Select "Test Connection"
3. Verify success message

### Manual Sync
1. Click "Sync" button
2. Wait for completion
3. View synced cases

### Add User
1. Go to Admin panel
2. Click "Add User"
3. Fill in details
4. Assign role

## 5. Troubleshooting

### Can't login?
- Check `.env` APP_KEY is set
- Verify user exists in database
- Check storage/logs/laravel.log

### Sync not working?
- Verify SLACK_BOT_TOKEN
- Check Slack app permissions
- Test connection in Admin panel

### Files not uploading?
- Check public/uploads permissions
- Verify PHP upload_max_filesize

## 6. Next Steps

- [x] Read full README.md
- [x] Review DEPLOYMENT.md for production tips
- [x] Configure auto-sync cron job
- [x] Set up database backups
- [x] Enable HTTPS

## Need Help?

- Check logs: `storage/logs/laravel.log`
- Review DEPLOYMENT.md troubleshooting section
- Contact support

---

**Estimated Setup Time**: 10-15 minutes
**Difficulty**: Intermediate
