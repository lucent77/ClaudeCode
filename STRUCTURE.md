# Project Structure - Hostinger Optimized

This project is structured for direct deployment to Hostinger's `public_html` directory.

## Directory Structure

```
public_html/ (Root Directory)
│
├── index.php              # Main entry point (redirects to login/dashboard)
├── api.php                # API entry point (handles all /api.php/* requests)
├── login.php              # Login page
├── dashboard.php          # Main dashboard
├── case-detail.php        # Case details page
├── .htaccess              # Apache configuration
│
├── app/                   # Application Logic
│   ├── Http/
│   │   └── Controllers/   # AuthController, CaseController, SyncController
│   ├── Models/            # Database models (User, Case, etc.)
│   ├── Services/          # SlackService for API integration
│   └── helpers.php        # Helper functions (env, config, etc.)
│
├── config/                # Configuration Files
│   ├── app.php            # Application settings
│   ├── auth.php           # Authentication config
│   ├── database.php       # Database connection (use env() for credentials)
│   ├── jwt.php            # JWT authentication settings
│   └── services.php       # Slack API configuration (use env() for tokens)
│
├── database/              # Database Setup
│   └── setup.sql          # Complete database initialization script
│
├── routes/                # API Routes
│   └── api.php            # Route definitions for all API endpoints
│
├── storage/               # File Storage (writable)
│   ├── app/attachments/   # Uploaded files
│   └── logs/              # Application logs
│
├── js/                    # JavaScript Files
│   └── dashboard.js       # Dashboard functionality
│
├── css/                   # Stylesheets
│   └── (custom styles if needed)
│
├── .env.example           # Environment variables template
├── composer.json          # PHP dependencies
├── README.md              # Project documentation
└── DEPLOYMENT.md          # Deployment guide

```

## File Permissions (Required)

After uploading to Hostinger:

```bash
chmod 755 storage
chmod 755 storage/logs
chmod 755 storage/app/attachments
chmod 644 .htaccess
```

## URL Structure

- **Homepage**: `https://yourdomain.com/` → redirects to login or dashboard
- **Login**: `https://yourdomain.com/login.php`
- **Dashboard**: `https://yourdomain.com/dashboard.php`
- **Case Detail**: `https://yourdomain.com/case-detail.php?id={case_id}`
- **API Endpoints**: `https://yourdomain.com/api.php/{endpoint}`

## API Endpoints

All API calls go through `api.php`:

- `POST /api.php/auth/login` - User authentication
- `GET /api.php/cases` - List all cases
- `GET /api.php/cases/{id}` - Get case details
- `POST /api.php/sync` - Sync with Slack
- `GET /api.php/sync/test` - Test Slack connection

## Environment Configuration

Create a `.env` file in the root directory (or configure directly in `config/*.php`):

```env
# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Slack API
SLACK_BOT_TOKEN=xoxb-your-actual-token
SLACK_WORKSPACE_ID=T-your-workspace-id
SLACK_CANVAS_ID=F-your-canvas-id

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

## Deployment Checklist

1. ✅ Upload all files to `public_html`
2. ✅ Import `database/setup.sql` via phpMyAdmin
3. ✅ Update `config/database.php` with database credentials
4. ✅ Update `config/services.php` with Slack tokens
5. ✅ Set correct file permissions (755 for directories, 644 for files)
6. ✅ Test database connection: visit `/api.php/sync/test`
7. ✅ Login with default credentials and change password
8. ✅ Test Slack sync functionality

## Security Notes

- `.htaccess` prevents direct access to sensitive directories
- All credentials should use `env()` function
- Change default admin password immediately
- Set `APP_DEBUG=false` in production
- Never commit `.env` file to git

## Default Login

- **Email**: `admin@creodent.com`
- **Password**: `admin123`

⚠️ **Change this immediately after first login!**
