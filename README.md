# Creodent AoX Elevate Dashboard

A modern, professional web application for managing dental cases with Slack integration. Built with PHP, Tailwind CSS, and a clean, responsive UI.

![Creodent AoX Dashboard](https://img.shields.io/badge/version-1.0.0-blue)
![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF)
![License](https://img.shields.io/badge/license-MIT-green)

## 🌟 Features

- **Slack Integration**: Sync cases directly from Slack Lists
- **Case Management**: Comprehensive case tracking with patient information, surgery dates, and status
- **File Attachments**: Support for photos, STL files, CBCT scans, and other medical files
- **Activity Logging**: Track all changes and activities for each case
- **User Authentication**: Secure JWT-based authentication
- **Modern UI**: Built with Tailwind CSS for a responsive, professional look
- **Dashboard Analytics**: Real-time statistics and insights
- **RESTful API**: Well-structured API endpoints for all operations

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- Composer (optional, for dependency management)
- Slack Bot Token with appropriate permissions

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone <your-repository-url>
cd ClaudeCode
```

### 2. Configure Database

Edit `config/database.php` with your database credentials:

```php
'host' => '127.0.0.1',
'port' => '3306',
'database' => 'your_database_name',
'username' => 'your_username',
'password' => 'your_password',
```

### 3. Initialize Database

Run the SQL setup script:

```bash
mysql -u your_username -p your_database_name < database/setup.sql
```

Or import via phpMyAdmin:
1. Open phpMyAdmin
2. Select your database
3. Go to "Import" tab
4. Choose `database/setup.sql`
5. Click "Go"

### 4. Configure Slack Integration

Edit `config/services.php` with your Slack credentials:

```php
'slack' => [
    'bot_token' => 'xoxb-your-bot-token',
    'workspace_id' => 'T-your-workspace-id',
    'canvas_id' => 'F-your-canvas-id',
],
```

### 5. Set Directory Permissions

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public
```

### 6. Configure Web Server

#### Apache (.htaccess)

Create `.htaccess` in the `public` directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/ClaudeCode/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 7. Access the Application

Open your browser and navigate to:
- **Web Interface**: `http://your-domain.com`
- **Login**: `http://your-domain.com/login`
- **Dashboard**: `http://your-domain.com/dashboard`

**Default Credentials:**
- Email: `admin@creodent.com`
- Password: `admin123`

⚠️ **IMPORTANT**: Change the default password immediately after first login!

## 📁 Project Structure

```
ClaudeCode/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # API Controllers
│   │   └── Middleware/       # Authentication Middleware
│   ├── Models/               # Database Models
│   └── Services/             # Business Logic (Slack Service)
├── config/                   # Configuration Files
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── jwt.php
│   └── services.php
├── database/
│   ├── migrations/           # Database Migrations
│   ├── seeds/                # Database Seeds
│   └── setup.sql            # Initial Database Setup
├── public/                   # Public Web Root
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript Files
│   ├── api.php              # API Entry Point
│   └── index.php            # Web Entry Point
├── resources/
│   └── views/               # Frontend Views
│       ├── login.php
│       ├── dashboard.php
│       └── case-detail.php
├── routes/
│   └── api.php              # API Routes
├── storage/                 # File Storage
│   ├── app/
│   ├── logs/
│   └── framework/
└── README.md
```

## 🔌 API Endpoints

### Authentication

```http
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
```

### Cases

```http
GET    /api/cases              # List all cases
POST   /api/cases              # Create new case
GET    /api/cases/{id}         # Get case details
PUT    /api/cases/{id}         # Update case
DELETE /api/cases/{id}         # Delete case
GET    /api/cases/stats        # Get statistics
```

### Sync

```http
GET  /api/sync/test            # Test Slack connection
POST /api/sync                 # Sync cases from Slack
GET  /api/sync/history         # Get sync history
```

## 🔐 Security

- **JWT Authentication**: All API endpoints (except login) require a valid JWT token
- **Password Hashing**: Passwords are hashed using bcrypt
- **SQL Injection Prevention**: All queries use prepared statements
- **XSS Protection**: All user input is properly escaped
- **CORS**: Configurable CORS headers for API access

## 🎨 UI Components

The dashboard uses Tailwind CSS with the following features:

- **Responsive Design**: Works on all devices
- **Modern Icons**: Font Awesome 6 integration
- **Interactive Tables**: Sortable, filterable case tables
- **Real-time Updates**: Live statistics and notifications
- **Clean Forms**: User-friendly input validation
- **Status Badges**: Color-coded status and priority indicators

## 🔧 Configuration

### Database Configuration

Edit `config/database.php`:

```php
'connections' => [
    'mysql' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'your_database'),
        'username' => env('DB_USERNAME', 'your_username'),
        'password' => env('DB_PASSWORD', 'your_password'),
    ],
],
```

### Slack Configuration

Edit `config/services.php`:

```php
'slack' => [
    'bot_token' => 'xoxb-your-bot-token',
    'workspace_id' => 'T-your-workspace-id',
    'canvas_id' => 'F-your-canvas-id',
    'file_storage_strategy' => 'hybrid', // 'slack_url', 'local_download', or 'hybrid'
],
```

### JWT Configuration

Edit `config/jwt.php`:

```php
'secret' => 'your-secret-key',
'ttl' => 60, // Token lifetime in minutes
'refresh_ttl' => 20160, // 14 days
```

## 📊 Database Schema

### Main Tables

- **users**: User accounts and authentication
- **cases**: Patient case information
- **case_attachments**: File attachments (photos, STLs, scans)
- **case_steps**: Workflow steps for each case
- **case_activity_logs**: Activity tracking and audit trail
- **sync_logs**: Slack synchronization history

## 🔄 Slack Integration

### Setup Slack Bot

1. Go to [Slack API](https://api.slack.com/apps)
2. Create a new app
3. Add Bot Token Scopes:
   - `files:read`
   - `files:write`
   - `channels:read`
   - `users:read`
4. Install app to workspace
5. Copy Bot Token (xoxb-...)

### Sync Process

1. Click "Sync with Slack" button in dashboard
2. System fetches cases from Slack List
3. Cases are created or updated in database
4. File attachments are downloaded (based on strategy)
5. Sync log is created with results

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and user has permissions

**Slack Sync Not Working**
- Verify bot token is correct
- Check bot has required scopes
- Test connection with `/api/sync/test`

**Login Issues**
- Clear browser cache and localStorage
- Check JWT secret is configured
- Verify user exists in database

**File Upload Errors**
- Check `storage/` directory permissions
- Increase `upload_max_filesize` in php.ini
- Verify disk space available

## 📝 Development

### Adding New Features

1. Create model in `app/Models/`
2. Create controller in `app/Http/Controllers/`
3. Add routes in `routes/api.php`
4. Create views in `resources/views/`
5. Add JavaScript in `public/js/`

### Database Migrations

To add new tables or columns, create SQL migration files in `database/migrations/`.

## 🚢 Deployment

### Hostinger Deployment

1. Upload files to `public_html` directory
2. Import database via phpMyAdmin
3. Update `config/` files with production settings
4. Set `debug` to `false` in `config/app.php`
5. Set proper file permissions
6. Test all functionality

### Production Checklist

- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Configure Slack bot token
- [ ] Set `debug` to `false`
- [ ] Enable HTTPS
- [ ] Set proper file permissions
- [ ] Test all API endpoints
- [ ] Backup database regularly

## 📄 License

This project is licensed under the MIT License.

## 👥 Support

For issues and questions:
- Create an issue on GitHub
- Contact: support@creodent.com

## 🙏 Acknowledgments

- Built with PHP and MySQL
- UI powered by Tailwind CSS
- Icons by Font Awesome
- Integrated with Slack API

---

**Made with ❤️ for Creodent AoX**
