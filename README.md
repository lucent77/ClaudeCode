# 📱 Price Tracker PWA

A full-featured **Progressive Web App** for tracking product prices across multiple stores with real-time notifications, multi-user support, and beautiful Apple-style UI.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)

## ✨ Features

### 🔐 User Management
- ✅ Secure user registration and login
- ✅ Session-based authentication
- ✅ Password hashing with bcrypt
- ✅ Multi-user support with isolated data

### 📊 Product Tracking
- ✅ Track unlimited products per user
- ✅ Support for 11+ major retailers:
  - Amazon, Best Buy, Target, Walmart
  - Sony, B&H Photo, Newegg, Costco
  - Adorama, Micro Center, eBay, and more
- ✅ Set custom target prices
- ✅ Automatic price checking via cron jobs
- ✅ Price history with Chart.js visualization
- ✅ Statistics (average, lowest, highest prices)

### 🔔 Smart Notifications
- ✅ Web Push notifications (works on desktop & mobile)
- ✅ Alerts when target price is reached
- ✅ Alerts on significant price drops (5%+)
- ✅ User-specific notification delivery

### 📱 Progressive Web App
- ✅ Installable on iOS and Android
- ✅ Offline capability with Service Worker
- ✅ App-like experience on mobile
- ✅ Splash screen and custom icons
- ✅ Standalone mode (no browser UI)

### 🎨 Modern UI/UX
- ✅ Apple-inspired design language
- ✅ TailwindCSS for styling
- ✅ Responsive layout (mobile-first)
- ✅ Smooth animations and transitions
- ✅ Dark mode ready (easily extensible)

## 🏗 Architecture

```
┌─────────────────────┐
│   Frontend (PWA)    │
│  HTML + TailwindCSS │
│  + Service Worker   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│      PHP API        │
│  • Auth endpoints   │
│  • Product CRUD     │
│  • Push subscriptions│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│       MySQL DB      │
│  • users            │
│  • products         │
│  • price_history    │
│  • subscribers      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│    Cron Job         │
│  Price Scraper      │
│  Multi-store support│
│  Notification sender│
└─────────────────────┘
```

## 📋 Prerequisites

- **Web Server**: Apache/Nginx with PHP 7.4+
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **PHP Extensions**: PDO, cURL, OpenSSL
- **Composer**: For dependency management
- **HTTPS**: Required for PWA and Web Push
- **Cron**: For automatic price checking

## 🚀 Installation Guide (Hostinger)

### Step 1: Upload Files

1. Download or clone this repository
2. Upload all files to your Hostinger `public_html` directory via FTP or File Manager
3. Ensure the folder structure is preserved

### Step 2: Database Setup

1. **Create MySQL Database**
   - Log into Hostinger control panel
   - Navigate to MySQL Databases
   - Create a new database (e.g., `price_tracker`)
   - Create a database user and grant all privileges

2. **Import Schema**
   - Open phpMyAdmin
   - Select your database
   - Click "Import" tab
   - Upload and execute `database/schema.sql`

### Step 3: Configuration

1. **Update Database Config** (`api/config.php`)
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');

   // Update your domain
   define('APP_URL', 'https://yourdomain.com');
   ```

2. **Generate VAPID Keys** (for Push Notifications)
   - Visit: https://web-push-codelab.glitch.me/
   - Copy the generated keys
   - Update in `api/config.php`:
   ```php
   define('VAPID_PUBLIC_KEY', 'your_public_key');
   define('VAPID_PRIVATE_KEY', 'your_private_key');
   define('VAPID_SUBJECT', 'mailto:your-email@example.com');
   ```
   - Also update in `assets/js/push.js`:
   ```javascript
   const VAPID_PUBLIC_KEY = 'your_public_key';
   ```

### Step 4: Install Dependencies

1. **Connect via SSH** (if available)
   ```bash
   cd public_html
   composer install
   ```

2. **Or manually upload** `vendor` folder from local installation:
   ```bash
   # On your local machine
   composer install
   # Then upload the entire vendor folder to Hostinger
   ```

### Step 5: Set Up Cron Job

1. Go to Hostinger control panel → Advanced → Cron Jobs
2. Add new cron job:
   - **Type**: Custom
   - **Common Settings**: Every 30 minutes
   - **Command**:
     ```bash
     php -q /home/your_username/public_html/cron/fetch_prices.php
     ```
3. Save the cron job

### Step 6: Enable HTTPS

1. In Hostinger control panel, go to SSL
2. Click "Install SSL" (free SSL certificate)
3. Wait for SSL to be activated
4. Enable "Force HTTPS" in settings

### Step 7: Create Log Directory

```bash
mkdir logs
chmod 755 logs
```

Or via File Manager:
- Create a folder named `logs` in the root directory
- Set permissions to 755

### Step 8: Test Installation

1. Visit your domain: `https://yourdomain.com`
2. You should be redirected to the login page
3. Try creating an account
4. Add a test product
5. Check if push notifications work

## 📱 PWA Installation

### iOS (iPhone/iPad)

1. Open Safari and visit your website
2. Tap the **Share** button (square with arrow)
3. Scroll and tap **Add to Home Screen**
4. Tap **Add**
5. The app icon will appear on your home screen

### Android

1. Open Chrome and visit your website
2. Tap the menu (three dots)
3. Tap **Install App** or **Add to Home Screen**
4. Confirm installation
5. The app will be added to your app drawer

### Desktop

1. Visit your website in Chrome or Edge
2. Look for the install icon in the address bar
3. Click and confirm installation
4. The app will open in a standalone window

## 🔧 Configuration Options

### Scraping Settings

Edit `api/config.php`:

```php
// User agent for scraping
define('USER_AGENT', 'Mozilla/5.0...');

// Timeout for scraping (seconds)
define('SCRAPE_TIMEOUT', 15);

// Number of retry attempts
define('SCRAPE_RETRY_COUNT', 3);

// Price drop threshold for alerts (5% = 0.05)
define('PRICE_DROP_THRESHOLD', 0.05);
```

### Session Timeout

```php
// Session timeout in seconds (default: 7 days)
define('SESSION_TIMEOUT', 3600 * 24 * 7);
```

## 🛠 Adding New Stores

To add support for a new store, edit `cron/scrapers/store_scrapers.php`:

1. Add a new case in the `scrapePrice()` method:
   ```php
   case 'newstore':
       return $this->scrapeNewStore($url);
   ```

2. Create the scraper method:
   ```php
   private function scrapeNewStore($url) {
       $html = $this->fetchHTML($url);
       $patterns = [
           '/<span class="price">([0-9,]+\.?[0-9]*)<\/span>/',
           // Add more patterns as needed
       ];
       return $this->extractPrice($html, $patterns);
   }
   ```

3. Add the store to the dropdown in `pages/add_product.html`

## 📊 Database Maintenance

### Backup Database

```bash
mysqldump -u username -p database_name > backup.sql
```

### Clean Old Price History

To keep only the last 90 days of price history:

```sql
DELETE FROM price_history
WHERE checked_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Monitor Database Size

```sql
SELECT
    table_name AS `Table`,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `Size (MB)`
FROM information_schema.TABLES
WHERE table_schema = 'your_database_name'
ORDER BY (data_length + index_length) DESC;
```

## 🐛 Troubleshooting

### Push Notifications Not Working

1. **Check HTTPS**: Push notifications require HTTPS
2. **Verify VAPID Keys**: Make sure keys are correctly set in both PHP and JS files
3. **Check Permissions**: Browser must allow notifications
4. **Test Subscription**: Open browser console and check for errors

### Cron Job Not Running

1. **Check Path**: Ensure the full path to PHP and script is correct
2. **Check Permissions**: Make sure `fetch_prices.php` is readable
3. **View Logs**: Check `logs/cron.log` for errors
4. **Test Manually**: Run the script via SSH to see errors:
   ```bash
   php /home/your_username/public_html/cron/fetch_prices.php
   ```

### Scraping Failures

1. **Check User Agent**: Some sites block unknown user agents
2. **Rate Limiting**: Sites may block if requests are too frequent
3. **HTML Changes**: Store websites update their HTML, requiring pattern updates
4. **Use Proxies**: Consider using proxy rotation for better success rates

### Database Connection Errors

1. **Check Credentials**: Verify database name, username, and password
2. **Check Host**: Usually `localhost` on Hostinger
3. **Check Privileges**: Ensure user has full privileges on database

## 🔒 Security Best Practices

1. **Change Default Password**: The default admin password should be changed
2. **Use Strong Passwords**: Enforce minimum 8 characters
3. **Regular Updates**: Keep PHP and dependencies updated
4. **Limit Login Attempts**: Add rate limiting to prevent brute force
5. **Use Prepared Statements**: Already implemented, never use raw SQL
6. **Validate All Input**: Always validate and sanitize user input
7. **HTTPS Only**: Never run without SSL in production
8. **Regular Backups**: Back up database regularly

## 📈 Performance Optimization

### Enable Caching

Add to `.htaccess`:

```apache
# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Compress Responses

```apache
# Enable Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript
</IfModule>
```

### Database Indexing

Indexes are already created in the schema, but you can add more:

```sql
CREATE INDEX idx_user_store ON products(user_id, store);
CREATE INDEX idx_product_checked ON price_history(product_id, checked_at);
```

## 📝 License

This project is licensed under the MIT License.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📧 Support

For issues and questions:
- Open an issue on GitHub
- Check the troubleshooting section
- Review the logs in `logs/` directory

## 🎉 Credits

- **TailwindCSS**: Styling framework
- **Chart.js**: Price history visualization
- **Minishlink/web-push**: PHP Web Push library
- **Design**: Apple-inspired UI/UX

---

**Built with ❤️ for smart shoppers**
