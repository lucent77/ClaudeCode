# Creodent AoX Elevate Dashboard

A modern, file-first AoX case management dashboard based on Slack Unified Files API integration. Built with PHP, MySQL, and Tailwind CSS for Hostinger deployment.

## 🚀 Features

- **Automatic Slack File Synchronization**: Automatically fetches and organizes files from Slack workspace
- **Smart File Classification**: AI-powered file naming pattern matching to auto-categorize files
- **Case-Based Organization**: Automatically groups files by patient cases
- **Modern Dashboard UI**: Clean, responsive interface built with Tailwind CSS
- **File Gallery Views**: Photo galleries, STL file lists, CBCT archives, and design files
- **Activity Logging**: Complete audit trail of all case activities
- **RESTful API**: Full REST API for case and file management

## 📋 System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (MariaDB 10.2+ also supported)
- **Web Server**: Apache or Nginx
- **PHP Extensions**:
  - PDO
  - PDO_MySQL
  - cURL
  - JSON
  - mbstring

## 🏗️ Project Structure

```
creodent-aox-dashboard/
├── api/                    # REST API endpoints
│   ├── cases.php          # Case management API
│   ├── sync.php           # Slack synchronization API
│   └── stats.php          # Statistics API
├── config/                # Configuration files
│   ├── config.php         # Main configuration (create from example)
│   └── config.example.php # Configuration template
├── database/              # Database schema
│   └── schema.sql         # MySQL schema and sample data
├── includes/              # PHP classes and utilities
│   ├── Database.php       # Database connection handler
│   ├── SlackClient.php    # Slack API integration
│   ├── CaseModel.php      # Case management model
│   └── FileClassifier.php # File classification logic
└── public/                # Public web root
    ├── index.php          # Dashboard home (case list)
    ├── case.php           # Case detail page
    └── uploads/           # Local file storage (optional)
```

## 🔧 Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/creodent-aox-dashboard.git
cd creodent-aox-dashboard
```

### Step 2: Create MySQL Database

1. Create a new database:

```sql
CREATE DATABASE creodent_aox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the schema:

```bash
mysql -u your_username -p creodent_aox < database/schema.sql
```

Or via phpMyAdmin:
- Select your database
- Go to "Import" tab
- Choose `database/schema.sql`
- Click "Go"

### Step 3: Configure Application

1. Copy the configuration template:

```bash
cp config/config.example.php config/config.php
```

2. Edit `config/config.php` with your settings:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'creodent_aox');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Slack API Configuration
define('SLACK_BOT_TOKEN', 'xoxb-your-slack-bot-token');
define('SLACK_WORKSPACE_ID', 'YOUR_WORKSPACE_ID');
```

### Step 4: Set Up Slack Bot

1. Go to [Slack API Dashboard](https://api.slack.com/apps)
2. Create a new app or select existing app
3. Navigate to "OAuth & Permissions"
4. Add these **Bot Token Scopes**:
   - `files:read` - View files in workspace
   - `files:write` - Upload and modify files
   - `channels:history` - View messages in channels
   - `groups:history` - View messages in private channels
5. Install the app to your workspace
6. Copy the **Bot User OAuth Token** (starts with `xoxb-`)
7. Paste the token into `config/config.php`

### Step 5: Configure Web Server

#### For Hostinger (cPanel)

1. Upload all files to your hosting account
2. Set document root to `/public` directory
3. Create `.htaccess` in public folder:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ /api/$1 [L]
```

#### For Apache (Local/VPS)

```apache
<VirtualHost *:80>
    ServerName creodent-aox.local
    DocumentRoot /path/to/creodent-aox-dashboard/public

    <Directory /path/to/creodent-aox-dashboard/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### For Nginx

```nginx
server {
    listen 80;
    server_name creodent-aox.local;
    root /path/to/creodent-aox-dashboard/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Step 6: Set Permissions

```bash
chmod 755 public/
chmod 755 public/uploads/
chmod 644 config/config.php
```

### Step 7: Test Installation

1. Open your browser and navigate to your domain
2. You should see the dashboard
3. Click "Sync Slack Files" to test the integration
4. Check the database to verify cases and files were imported

## 📊 Database Schema

### Tables

- **cases**: Patient case records
- **case_attachments**: File attachments from Slack
- **case_activity_logs**: Audit trail of case activities
- **sync_logs**: Slack synchronization history

### Views

- **v_case_summary**: Case summary with file counts (used by dashboard)

## 🔄 Slack File Synchronization

### How It Works

1. **Fetch Files**: Retrieves all files from Slack via `files.list` API
2. **Extract Case Code**: Parses filename to identify patient case (e.g., `Nicolas_S_2025_11_18`)
3. **Create Case**: Auto-creates case if it doesn't exist
4. **Classify File**: Determines file type (photo, STL, CBCT, etc.) based on patterns
5. **Store Metadata**: Saves file metadata to database
6. **Update Dashboard**: Files appear in dashboard immediately

### File Naming Conventions

The system recognizes these patterns:

#### Case Code Formats
- `FirstName_LastName_YYYY_MM_DD` - Full format with surgery date
- `FirstName_LastName` - Name only format

#### File Type Keywords

| Type | Extensions | Keywords |
|------|-----------|----------|
| Photos | jpg, png, gif | photo, img_, image, face, intraoral |
| STL Files | stl, obj, ply | stl, scan, model, 3d |
| Pre-op Scan | stl, dcm | preop, pre-op, initial, before |
| Post-op Scan | stl, dcm | postop, post-op, final, after |
| Pre-op CBCT | dcm, zip | cbct, ct, preop |
| Post-op CBCT | dcm, zip | cbct, ct, postop |
| Design Files | 3shape, blend | design, planning, guide |
| Radiograph | jpg, dcm | xray, radiograph, panoramic |

### Example Filenames

```
Nicolas_S_2025_11_18_preop_scan.stl          → Case: Nicolas_S_2025_11_18, Type: preop_scan
Tom_Bischof_postop_cbct.zip                  → Case: Tom_Bischof, Type: postop_cbct
Margaret_Colarusso_photos_01.jpg             → Case: Margaret_Colarusso, Type: photo
Van_Pham_design_final_upper.stl              → Case: Van_Pham, Type: design
```

### Manual Sync

Run synchronization manually:

```bash
curl http://your-domain.com/api/sync.php
```

### Automated Sync (Cron Job)

Set up a cron job for automatic synchronization every 5 minutes:

```bash
*/5 * * * * curl -s http://your-domain.com/api/sync.php > /dev/null
```

Or via cPanel:
1. Go to "Cron Jobs"
2. Add new cron job
3. Command: `php /path/to/public/api/sync.php`
4. Schedule: Every 5 minutes

## 📡 API Endpoints

### Cases API

#### Get All Cases
```http
GET /api/cases.php
GET /api/cases.php?search=patient_name
GET /api/cases.php?status=open
GET /api/cases.php?limit=10&offset=0
```

#### Get Single Case
```http
GET /api/cases.php?id=123
```

#### Create Case
```http
POST /api/cases.php
Content-Type: application/json

{
  "case_code": "John_Doe_2025_11_14",
  "patient_name": "John Doe",
  "surgery_date": "2025-11-14",
  "arch": "Upper",
  "surgeon": "Dr. Smith",
  "status": "open"
}
```

#### Update Case
```http
PUT /api/cases.php?id=123
Content-Type: application/json

{
  "status": "completed",
  "notes": "Surgery completed successfully"
}
```

#### Delete Case
```http
DELETE /api/cases.php?id=123
```

### Sync API

#### Trigger Slack Sync
```http
GET /api/sync.php
```

Response:
```json
{
  "success": true,
  "status": "success",
  "stats": {
    "total_files": 150,
    "matched_files": 140,
    "new_cases": 5,
    "new_files": 45
  },
  "execution_time": "3.45s"
}
```

### Statistics API

#### Get Dashboard Stats
```http
GET /api/stats.php
```

## 🎨 Customization

### File Type Patterns

Edit `config/config.php` to customize file classification:

```php
define('FILE_PATTERNS', [
    'custom_type' => [
        'extensions' => ['custom', 'ext'],
        'keywords' => ['keyword1', 'keyword2']
    ]
]);
```

### UI Customization

The dashboard uses Tailwind CSS. Customize colors in `public/index.php` and `public/case.php`:

```html
<!-- Change primary color from indigo to blue -->
<button class="bg-blue-600 hover:bg-blue-700 text-white...">
```

## 🔒 Security

### Configuration Security

1. Protect `config/config.php`:

```bash
chmod 600 config/config.php
```

2. Add to `.gitignore`:

```
config/config.php
```

### API Security

For production, add authentication:

```php
// In api files
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey !== 'your-secret-api-key') {
    http_response_code(401);
    die('Unauthorized');
}
```

## 🐛 Troubleshooting

### Sync Not Working

1. Check Slack bot token in `config/config.php`
2. Verify bot has correct scopes in Slack App settings
3. Check PHP error log: `tail -f /var/log/php-errors.log`
4. Test API manually: `curl http://your-domain.com/api/sync.php`

### Database Connection Failed

1. Verify database credentials in `config/config.php`
2. Check MySQL is running: `service mysql status`
3. Test connection: `mysql -u username -p database_name`

### Files Not Displaying

1. Check file URLs in database
2. Verify Slack bot has access to files
3. Check browser console for errors
4. Test file URL directly in browser

### No Cases Appearing

1. Run sync manually
2. Check sync logs in database: `SELECT * FROM sync_logs ORDER BY created_at DESC LIMIT 10;`
3. Verify file naming matches patterns
4. Check for errors in `sync_logs.error_message`

## 📚 Additional Resources

- [Slack API Documentation](https://api.slack.com/docs)
- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

## 📄 License

MIT License - feel free to use this for your dental practice!

## 👥 Support

For issues and questions:
- Create an issue on GitHub
- Email: support@creodent.com

## 🎯 Roadmap

- [ ] STL file 3D viewer integration (Three.js)
- [ ] DICOM viewer for CBCT files
- [ ] Image annotation tools
- [ ] Multi-user authentication
- [ ] Role-based access control
- [ ] Export to PDF reports
- [ ] Email notifications
- [ ] Mobile app

---

Built with ❤️ for modern dental practices
