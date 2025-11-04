# CREODENT Integrated Work Management System

> Modern PHP-based dental laboratory work management system with Evolution Portal integration

## 🚀 Overview

CREODENT Work Manager is a comprehensive web-based system designed to replace the traditional Google Sheets + VB.NET Windows Forms workflow. It provides centralized, database-driven management for dental laboratory operations across multiple departments.

## ✨ Key Features

- **Evolution Portal V18 Integration**
  - XML API synchronization
  - SQL Server direct connection (NYC & HV locations)
  - Automated hourly imports

- **Multi-Department Support**
  - Solidex (Implant systems)
  - 3D Print (Model printing)
  - CoCr/ZEST (Metal frameworks)

- **Concurrent User Management**
  - Optimistic locking for data integrity
  - 30+ simultaneous users supported
  - Real-time conflict detection

- **Role-Based Access Control (RBAC)**
  - Super Admin, Admin, Manager, Worker roles
  - Department-specific permissions
  - Granular feature access

- **Comprehensive Audit Trail**
  - Before/after change tracking
  - User attribution with IP logging
  - Tamper-proof append-only logs

- **Windows App Integration**
  - REST API for VB.NET application
  - JSON data import/export
  - Seamless legacy system compatibility

## 🛠️ Technology Stack

### Backend
- **PHP**: 8.0+
- **MySQL**: 8.0+ (Hostinger)
- **PDO**: Database abstraction
- **Composer**: Dependency management (PSR-4 autoloading)

### Frontend
- **Tailwind CSS**: Modern utility-first styling
- **Alpine.js**: Lightweight reactive framework
- **Chart.js**: Data visualization
- **Vanilla JavaScript**: AJAX and DOM manipulation

### External Integrations
- **Evolution Portal**: XML API + SQL Server (sqlsrv extension)
- **Windows VB.NET App**: RESTful API endpoints

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx with mod_rewrite
- PHP Extensions:
  - PDO
  - PDO_MySQL
  - cURL
  - JSON
  - XML
  - mbstring
  - sqlsrv (optional, for SQL Server)

## 🔧 Installation

### Quick Start

1. **Clone or upload files to Hostinger**
   ```bash
   # Via FTP or File Manager to public_html/
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure database**
   - Edit `config/config.php`
   - Update database credentials:
     ```php
     'database' => [
         'host' => '127.0.0.1',
         'database' => 'u359033001_CADCAM_WORK1',
         'username' => 'u359033001_CADCAM_WORK1',
         'password' => 'Creo$10001',
     ],
     ```

4. **Run installation wizard**
   ```
   http://yourdomain.com/install.php
   ```

5. **First login**
   - Username: `admin`
   - Password: `Admin@123`
   - **⚠️ Change password immediately!**

6. **Delete install.php** (security)
   ```bash
   rm install.php
   ```

### Manual Installation

See [INSTALL.md](INSTALL.md) for detailed step-by-step instructions.

## 📁 Project Structure

```
Creodent_CADCAM_WORK_V2/
├── app/
│   ├── Controllers/       # Request handlers
│   ├── Core/              # Framework classes (Router, Database, Session)
│   ├── Middleware/        # Authentication, CSRF protection
│   ├── Models/            # Data models
│   ├── Repositories/      # Data access layer
│   └── Services/          # Business logic
├── config/
│   ├── config.php         # Main configuration (ignored by git)
│   └── config.example.php # Configuration template
├── database/
│   ├── schema.sql         # Database schema
│   └── migrations/        # Schema updates
├── storage/
│   ├── cache/             # Application cache
│   ├── logs/              # Error and access logs
│   └── uploads/           # User-uploaded files
├── views/                 # HTML templates
│   ├── auth/              # Login pages
│   ├── cases/             # Case management UI
│   ├── components/        # Reusable UI components
│   └── layout.php         # Base layout
├── cron/
│   └── import_evo.php     # Automated Evolution import
├── public/
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── .htaccess              # URL rewriting rules
├── index.php              # Application entry point
├── composer.json          # PHP dependencies
└── README.md              # This file
```

## 🔐 Security Features

- Session-based authentication with database storage
- CSRF token protection on all state-changing operations
- Password hashing with bcrypt
- Login attempt limiting (5 attempts → 15min lockout)
- Role-based access control (RBAC)
- SQL injection prevention (prepared statements)
- XSS protection (output escaping)
- API token authentication for external integrations

## 📊 Database Schema

- **16 main tables** with proper relationships
- **3 department-specific tables** for raw data preservation
- **Optimistic locking** via version columns
- **Full-text search** indexes for fast lookups
- **Audit logging** with before/after snapshots

## 🔗 API Endpoints

### Authentication
- `POST /login` - User login
- `POST /logout` - User logout

### Case Management
- `GET /api/cases` - List cases (paginated, filterable)
- `POST /api/cases` - Create new case
- `GET /cases/{id}` - View case details
- `POST /cases/{id}` - Update case
- `POST /cases/{id}/delete` - Delete case
- `POST /cases/{id}/assign` - Assign to worker

### Evolution Integration
- `POST /api/cases/fetch-evolution` - Fetch case from Evolution Portal
- `POST /import/evolution` - Import cases from Evolution

### Windows App Integration
- `POST /api/import/3dprint` - Import 3D Print data
- `POST /api/import/cocr` - Import CoCr data
- `POST /api/import/solidex` - Import Solidex data

## 🔄 Automated Tasks (Cron)

Set up in Hostinger cPanel → Cron Jobs:

```bash
# Hourly Evolution import
0 * * * * /usr/bin/php /home/u359033001/public_html/cron/import_evo.php >> /home/u359033001/public_html/storage/logs/cron.log 2>&1
```

## 🐛 Troubleshooting

### Database Connection Failed
- Check `config/config.php` credentials
- Verify database exists in Hostinger cPanel
- Ensure MySQL user has proper permissions

### 500 Internal Server Error
- Check `.htaccess` file exists
- Verify file permissions (755 for directories, 644 for files)
- Review error logs: `storage/logs/php_errors.log`

### Evolution Import Not Working
- Test connection: Import page → "Test Connection"
- Verify Evolution credentials in `config/config.php`
- Check logs: `storage/logs/import_YYYY-MM-DD.log`

## 📖 Documentation

- **[Installation Guide](docs/INSTALLATION.md)** - Detailed setup instructions
- **[API Reference](docs/API.md)** - Complete API documentation
- **[User Guide](docs/USER_GUIDE.md)** - End-user instructions
- **[Developer Guide](docs/DEVELOPER.md)** - Architecture and code standards

## 🤝 Support

- **Technical Issues**: admin@creodent.com
- **Hosting Support**: support.hostinger.com

## 📄 License

Proprietary - CREODENT © 2025

---

**Version**: 2.0.0
**Last Updated**: January 2025
**Built with** ❤️ **by CREODENT Development Team**
