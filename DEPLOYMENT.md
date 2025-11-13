# 🚀 Deployment Checklist

Complete step-by-step deployment guide for Hostinger (or any PHP hosting).

## Pre-Deployment Checklist

- [ ] All files ready
- [ ] Database schema prepared
- [ ] VAPID keys generated
- [ ] Icons created (see `assets/icons/ICONS.md`)
- [ ] Domain/subdomain configured
- [ ] SSL certificate ready

## Step 1: Prepare Files Locally

### 1.1 Install Dependencies

```bash
composer install
```

This will install the web-push library in the `vendor/` folder.

### 1.2 Generate VAPID Keys

Visit: https://web-push-codelab.glitch.me/

You'll get something like:
```
Public Key: BFxD4...
Private Key: 8Hdk2...
```

Save these keys - you'll need them in Step 3.

### 1.3 Create Icons

Follow the guide in `assets/icons/ICONS.md` to create PWA icons.

At minimum, create:
- `icon-192.png` (192x192)
- `icon-512.png` (512x512)

## Step 2: Upload to Hostinger

### 2.1 Connect via FTP/SFTP

**Using FileZilla:**
1. Host: Your domain or IP
2. Username: From Hostinger control panel
3. Password: From Hostinger control panel
4. Port: 21 (FTP) or 22 (SFTP)

**Or use Hostinger File Manager:**
1. Log into Hostinger control panel
2. Go to Files → File Manager
3. Navigate to `public_html`

### 2.2 Upload Files

Upload ALL files to `public_html/`:
```
public_html/
├── api/
├── assets/
├── cron/
├── database/
├── pages/
├── push/
├── vendor/           ← Important!
├── .htaccess
├── composer.json
├── index.html
├── manifest.json
├── service-worker.js
└── README.md
```

**Important**: Make sure the `vendor/` folder is uploaded!

### 2.3 Set Permissions

Set the following permissions (via FTP or File Manager):

```
logs/           → 755 (create if doesn't exist)
vendor/         → 755
api/            → 755
cron/           → 755
```

## Step 3: Database Setup

### 3.1 Create Database

1. Hostinger Control Panel → Databases → MySQL Databases
2. Click "Create New Database"
3. Database name: `u123456789_pricetracker` (or your choice)
4. Click "Create"

### 3.2 Create Database User

1. In the same section, click "Create New User"
2. Username: `u123456789_admin` (or your choice)
3. Password: Generate a strong password
4. Click "Create"

### 3.3 Link User to Database

1. Find "Add User to Database" section
2. Select your user and database
3. Grant "ALL PRIVILEGES"
4. Click "Add"

### 3.4 Import Schema

**Via phpMyAdmin:**
1. Hostinger Control Panel → Databases → phpMyAdmin
2. Select your database from the left sidebar
3. Click "Import" tab
4. Choose file: `database/schema.sql`
5. Click "Go"
6. Wait for "Import has been successfully finished"

**Via MySQL CLI (if SSH access available):**
```bash
mysql -u your_user -p your_database < database/schema.sql
```

## Step 4: Configure Application

### 4.1 Update Database Config

Edit `api/config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_pricetracker');  // ← Your database name
define('DB_USER', 'u123456789_admin');         // ← Your database user
define('DB_PASS', 'your_strong_password');     // ← Your database password
define('DB_CHARSET', 'utf8mb4');

// Application URL
define('APP_URL', 'https://yourdomain.com');   // ← Your actual domain

// VAPID Keys (from Step 1.2)
define('VAPID_PUBLIC_KEY', 'BFxD4...');        // ← Your public key
define('VAPID_PRIVATE_KEY', '8Hdk2...');       // ← Your private key
define('VAPID_SUBJECT', 'mailto:you@email.com');
```

### 4.2 Update Push.js

Edit `assets/js/push.js`:

```javascript
// Line 6
const VAPID_PUBLIC_KEY = 'BFxD4...';  // ← Same public key as above
```

### 4.3 Create Logs Directory

Via File Manager or FTP:
1. Create folder: `logs`
2. Set permissions: 755

Via SSH:
```bash
mkdir logs
chmod 755 logs
```

## Step 5: SSL Certificate

### 5.1 Enable SSL

1. Hostinger Control Panel → SSL
2. Select your domain
3. Click "Install" (free Let's Encrypt SSL)
4. Wait 5-10 minutes for activation

### 5.2 Force HTTPS

1. Same SSL section
2. Toggle "Force HTTPS" to ON
3. Or ensure `.htaccess` has the rewrite rules (already included)

### 5.3 Verify SSL

Visit: `https://yourdomain.com`

You should see a padlock icon in the browser.

## Step 6: Cron Job Setup

### 6.1 Get Full Path

Via SSH:
```bash
pwd
# Output: /home/u123456789/public_html
```

Or check in Hostinger File Manager (shows at the top).

Full path will be: `/home/u123456789/public_html/cron/fetch_prices.php`

### 6.2 Create Cron Job

1. Hostinger Control Panel → Advanced → Cron Jobs
2. Click "Create New Cron Job"

**Settings:**
- **Type**: Custom
- **Common Settings**: Every 30 minutes
  - OR manually set: `*/30 * * * *`
- **Command**:
  ```bash
  php -q /home/u123456789/public_html/cron/fetch_prices.php
  ```
- **Email notifications**: Optional (recommended for errors)

3. Click "Create"

### 6.3 Test Cron Manually

Via SSH:
```bash
cd /home/u123456789/public_html
php cron/fetch_prices.php
```

Check `logs/cron.log` for output.

## Step 7: Testing

### 7.1 Basic Tests

- [ ] Visit homepage: `https://yourdomain.com`
- [ ] Should redirect to login page
- [ ] Register a new account
- [ ] Login successfully
- [ ] View dashboard
- [ ] Add a test product
- [ ] View product details
- [ ] Edit target price
- [ ] Delete product

### 7.2 PWA Installation Test

**iOS:**
1. Open in Safari
2. Share → Add to Home Screen
3. Launch from home screen
4. Should open in full-screen mode

**Android:**
1. Open in Chrome
2. Menu → Install App
3. Launch installed app
4. Should open as standalone app

### 7.3 Push Notifications Test

1. On dashboard, click "Enable Alerts"
2. Accept browser permission
3. Should see "Push notifications enabled!" toast
4. Check browser console for errors

To test actual notifications:
```php
// Temporarily add to end of dashboard.html:
<script>
  setTimeout(() => {
    window.PushNotifications.showTest();
  }, 3000);
</script>
```

### 7.4 Cron Job Test

1. Add a product with current price available
2. Wait 30 minutes for cron to run
3. Check `logs/cron.log`:
   ```
   [2024-01-15 10:30:00] === Starting price fetch cron job ===
   [2024-01-15 10:30:01] Found 1 products to check
   [2024-01-15 10:30:02] Checking product #1: Test Product (Amazon)
   [2024-01-15 10:30:05]   ✓ Price found: $299.99
   [2024-01-15 10:30:05] === Cron job completed ===
   ```

4. Refresh dashboard - should see updated price

### 7.5 Database Test

Via phpMyAdmin:
```sql
-- Check users
SELECT * FROM users;

-- Check products
SELECT * FROM products;

-- Check price history
SELECT * FROM price_history ORDER BY checked_at DESC LIMIT 10;
```

## Step 8: Post-Deployment

### 8.1 Security Hardening

1. **Change default admin password**:
   - Login with: `admin@pricetracker.com` / `admin123`
   - Or delete this user via phpMyAdmin

2. **Secure config.php**:
   ```bash
   chmod 644 api/config.php
   ```

3. **Disable error display**:
   In `api/config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);  // Make sure this is 0
   ```

### 8.2 Performance Optimization

1. **Enable OPcache** (if available):
   Hostinger Control Panel → PHP Configuration → Enable OPcache

2. **Gzip Compression**:
   Already in `.htaccess`, verify it works:
   ```bash
   curl -H "Accept-Encoding: gzip" -I https://yourdomain.com
   # Should see: Content-Encoding: gzip
   ```

### 8.3 Monitoring

1. **Set up uptime monitoring**:
   - Use UptimeRobot (free): https://uptimerobot.com/
   - Monitor: `https://yourdomain.com`

2. **Monitor cron logs**:
   Check `logs/cron.log` weekly for errors

3. **Database backups**:
   - Hostinger auto-backup: Enabled by default
   - Manual backup: phpMyAdmin → Export

### 8.4 Domain Configuration (Optional)

If using a custom domain:

1. **Update DNS**:
   - A Record: `@` → Your server IP
   - A Record: `www` → Your server IP

2. **Wait for propagation**: 24-48 hours

3. **Update config.php** with new domain

## Troubleshooting

### Issue: "Database connection failed"

**Solution**:
1. Verify credentials in `api/config.php`
2. Check if database user has privileges
3. Test connection via phpMyAdmin

### Issue: Cron job not running

**Solution**:
1. Check cron command path is correct
2. Test manually: `php cron/fetch_prices.php`
3. Check `logs/cron.log` for errors
4. Verify cron is active in Hostinger panel

### Issue: Push notifications not working

**Solution**:
1. Verify HTTPS is enabled
2. Check VAPID keys are correct in both files
3. Clear browser cache
4. Try different browser/device
5. Check browser console for errors

### Issue: PWA not installing

**Solution**:
1. Verify HTTPS is active
2. Check `manifest.json` is accessible
3. Verify `service-worker.js` is registered
4. Check browser console for errors
5. Test with Lighthouse (Chrome DevTools)

### Issue: Icons not showing

**Solution**:
1. Verify icons exist in `assets/icons/`
2. Check file permissions (644)
3. Clear browser cache
4. Regenerate icons if needed

### Issue: Scraping not working

**Solution**:
1. Test specific store URL manually
2. Check `logs/cron.log` for errors
3. Verify store HTML hasn't changed
4. Update scraper patterns if needed
5. Check if IP is rate-limited

## Rollback Plan

If something goes wrong:

1. **Restore database**:
   ```bash
   mysql -u user -p database < backup.sql
   ```

2. **Revert files**:
   - Delete current files
   - Re-upload previous version

3. **Check logs**:
   - `logs/cron.log`
   - `logs/php_errors.log`

## Support Resources

- **Hostinger Support**: https://www.hostinger.com/tutorials
- **PHP Documentation**: https://www.php.net/docs.php
- **PWA Documentation**: https://web.dev/progressive-web-apps/
- **Web Push Library**: https://github.com/web-push-libs/web-push-php

---

## Deployment Complete! ✅

Your Price Tracker PWA should now be live at `https://yourdomain.com`

**Next Steps:**
1. Share with users
2. Monitor logs regularly
3. Add more products
4. Customize design (optional)
5. Add more stores (optional)

**Maintenance Schedule:**
- **Daily**: Check if cron is running
- **Weekly**: Review error logs
- **Monthly**: Database backup
- **Quarterly**: Update dependencies

---

Need help? Check the troubleshooting section or review the main README.md.
