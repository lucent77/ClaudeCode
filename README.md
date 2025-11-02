# CREODENT Integrated Work Management System

A comprehensive web-based work management system that integrates Evolution Web Portal data with department-specific workflows for Solidex, 3D Print, and CoCr/ZEST departments.

## Features

- **Evolution Web Portal Integration**: Automatic synchronization with Evolution V18 via XML API
- **Multi-Department Support**: Separate workflows for Solidex, 3D Print, and CoCr departments
- **Concurrent User Access**: Optimistic locking prevents data conflicts when multiple users edit simultaneously
- **Role-Based Access Control**: Super Admin, Admin, Manager, and Worker roles with different permissions
- **Audit Logging**: Complete history of all changes with before/after snapshots
- **Real-time Dashboard**: Live statistics and workload distribution across departments
- **Responsive UI**: Modern interface built with Tailwind CSS
- **API Support**: RESTful API for external integrations (VB.NET, etc.)
- **Google Sheets Compatibility**: Optional sync layer for backward compatibility

## System Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Apache/Nginx**: with mod_rewrite enabled
- **PHP Extensions**: PDO, PDO_MySQL, cURL, JSON, XML
- **Disk Space**: Minimum 100MB for application files
- **RAM**: Minimum 512MB (1GB+ recommended for production)

## Project Structure

```
ClaudeCode/
├── app/
│   ├── Controllers/       # Request handlers
│   ├── Core/             # Core framework classes
│   ├── Middleware/       # Authentication middleware
│   ├── Repositories/     # Data access layer
│   └── Services/         # Business logic
├── config/               # Configuration files
├── cron/                 # Scheduled tasks
├── database/             # Database schema
├── public/               # Web root
│   ├── .htaccess        # Apache configuration
│   └── index.php        # Application entry point
├── storage/              # Logs and uploads
├── vendor/               # Autoloader
├── views/                # HTML templates
│   ├── auth/            # Login pages
│   ├── cases/           # Case management
│   ├── components/      # Reusable components
│   └── dashboard.php    # Main dashboard
├── composer.json         # Composer configuration
├── install.php          # Installation wizard
└── README.md            # This file
```

## Installation

### Step 1: Upload Files

Upload all files to your Hostinger public_html directory (or subdirectory).

### Step 2: Configure Database

1. Create a MySQL database in Hostinger cPanel
2. Note your database credentials:
   - Host: 127.0.0.1:3306
   - Database: u359033001_TOOL
   - Username: u359033001_TOOL
   - Password: Creo$10001

### Step 3: Run Installation Wizard

1. Navigate to `http://yourdomain.com/install.php`
2. Follow the installation wizard
3. The installer will:
   - Check system requirements
   - Create necessary directories
   - Set up database tables
   - Create default admin user

### Step 4: First Login

**Default credentials:**
- Username: `admin`
- Password: `Admin@123`

**⚠️ IMPORTANT: Change this password immediately after first login!**

### Step 5: Configure Evolution Portal

1. Log in as admin
2. Edit `config/config.php`
3. Update Evolution Web Portal settings:

```php
'evolution' => [
    'base_url' => 'https://your-evolution-portal-url',
    'username' => 'your_username',
    'password' => 'your_password',
    // ... other settings
],
```

### Step 6: Set Up Cron Job

Add this cron job in Hostinger cPanel to import cases every hour:

```bash
0 * * * * /usr/bin/php /home/username/public_html/cron/import_evo.php >> /home/username/storage/logs/cron.log 2>&1
```

### Step 7: Delete Installation File

For security, delete `install.php` after successful installation.

## Configuration

All configuration is in `config/config.php`:

### Application Settings

```php
'app' => [
    'name' => 'CREODENT Work Manager',
    'env' => 'production',
    'debug' => false,  // Set to false in production
    'timezone' => 'America/New_York',
    'session_lifetime' => 7200,  // 2 hours
],
```

### Database Settings

```php
'database' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'u359033001_TOOL',
    'username' => 'u359033001_TOOL',
    'password' => 'Creo$10001',
],
```

### Evolution Portal Settings

```php
'evolution' => [
    'base_url' => 'https://your-portal-url',
    'username' => 'your_username',
    'password' => 'your_password',
    'timeout' => 30,
    'retry_count' => 3,
],
```

## User Roles & Permissions

### Super Admin
- Full system access
- User management
- System configuration
- All department access

### Admin
- Case management
- Import/export data
- View all departments
- Assign cases

### Manager
- Department-specific case management
- Assign cases to workers
- View department statistics

### Worker
- View assigned cases
- Update case status
- Add notes and comments

## Usage Guide

### Dashboard

The dashboard provides:
- Total active cases
- New cases today
- Cases in progress
- Overdue cases
- Department workload distribution
- Recent cases list

### Case Management

**Creating a Case:**
1. Go to Cases → New Case
2. Enter case details
3. Assign to department
4. Click Save

**Updating a Case:**
1. Open case detail page
2. Edit required fields
3. System will check for concurrent modifications
4. Save changes

**Assigning Cases:**
1. Open case
2. Select worker from dropdown
3. Click Assign
4. Worker will see case in their queue

### Import from Evolution Portal

**Manual Import:**
1. Go to Import page
2. Select date range
3. Click "Import Now"
4. Review import results

**Automatic Import:**
- Runs every hour via cron job
- Imports yesterday and today's cases
- Logs all activities

### Department-Specific Workflows

#### Solidex Department
- Fields: Implant System, Teeth, Instructions, Preferences
- Status tracking: Pending → Assigned → Working → Done

#### 3D Print Department
- Fields: Tooth #, Count, Location, Upload to Korea
- Material tracking and print queue management

#### CoCr/ZEST Department
- Fields: Disk Material, COMBO, MC IO, Design, CAM, CNC
- Metal framework and attachment workflows

## API Documentation

### Authentication

All API requests require authentication. Include session cookie or API token.

### Endpoints

#### Get Cases
```
GET /api/cases
Parameters:
  - page (int): Page number
  - per_page (int): Items per page
  - search (string): Search query
  - status (string): Filter by status
  - location (string): Filter by location

Response:
{
  "success": true,
  "data": {
    "data": [...],
    "pagination": {...}
  }
}
```

#### Get Single Case
```
GET /api/cases/{id}

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "external_case_no": "2025-70665",
    "items": [...],
    "audit_logs": [...]
  }
}
```

#### Update Case
```
POST /api/cases/{id}
Headers:
  Content-Type: application/json
  X-CSRF-TOKEN: {token}

Body:
{
  "version": 1,
  "status": "in_progress",
  "due_date": "2025-11-15"
}

Response:
{
  "success": true,
  "data": {...},
  "message": "Case updated successfully"
}
```

#### External Case Import (for VB.NET)
```
POST /api/external/case-import
Headers:
  Content-Type: application/json
  X-API-TOKEN: {your-api-token}

Body:
{
  "external_case_no": "2025-70665",
  "lab_name": "Lab Name",
  "patient_name": "Patient Name",
  "due_date": "2025-11-15",
  ...
}

Response:
{
  "success": true,
  "data": {
    "case_id": 123
  },
  "message": "Case imported successfully"
}
```

## Concurrency Control

### Optimistic Locking

The system uses optimistic locking to prevent data conflicts:

1. When a record is loaded, its `version` number is included
2. When saving, the system checks if `version` matches
3. If version changed (someone else edited), save fails with error:
   ```
   "Concurrent modification detected. Please refresh and try again."
   ```
4. User must refresh and re-apply changes

### Audit Logging

All changes are logged with:
- Who made the change
- When it was made
- What changed (before/after values)
- IP address and user agent

View audit logs on case detail page.

## Troubleshooting

### Installation Issues

**Database connection fails:**
- Check database credentials in `config/config.php`
- Verify MySQL server is running
- Check if user has proper permissions

**Permission denied errors:**
- Ensure `storage/` directory is writable (chmod 755)
- Check Apache/PHP user permissions

### Import Issues

**Evolution Portal connection fails:**
- Verify `base_url` is correct
- Check username/password
- Test connection in Import → Test Connection

**No cases imported:**
- Check date range
- Verify Evolution Portal has cases for that period
- Review `import_jobs` table for error details

### Performance Issues

**Slow page loads:**
- Enable opcode cache (OPcache)
- Optimize MySQL queries
- Consider increasing PHP memory_limit

**Database locks:**
- Check for long-running transactions
- Review concurrent user count
- Optimize frequently-accessed queries

## Security Best Practices

1. **Change default admin password** immediately
2. **Delete install.php** after installation
3. **Use HTTPS** in production (free SSL via Hostinger)
4. **Keep config.php secure** (not publicly accessible)
5. **Regular backups** of database and files
6. **Update PHP** to latest stable version
7. **Monitor error logs** for suspicious activity
8. **Use strong passwords** for all users
9. **Limit failed login attempts** (configured in config)
10. **Regular security audits** of user accounts

## Backup & Restore

### Database Backup

```bash
# Export database
mysqldump -u u359033001_TOOL -p u359033001_TOOL > backup_$(date +%Y%m%d).sql

# Import database
mysql -u u359033001_TOOL -p u359033001_TOOL < backup_20251102.sql
```

### File Backup

Backup these directories:
- `config/` - Configuration
- `storage/` - Logs and uploads
- `database/` - Schema (optional)

## Support & Maintenance

### Log Files

- **Application logs**: `storage/logs/app.log`
- **Cron logs**: `storage/logs/import_YYYY-MM-DD.log`
- **Apache logs**: Check Hostinger cPanel

### Database Maintenance

```sql
-- Check table sizes
SELECT
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'u359033001_TOOL';

-- Clean old audit logs (older than 90 days)
DELETE FROM case_audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Clean old sessions
DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

## Upgrade Guide

When updating to a new version:

1. **Backup** database and files
2. **Download** new version
3. **Replace** application files (keep config.php)
4. **Run** any database migrations
5. **Test** in staging environment first
6. **Deploy** to production
7. **Clear** cache if needed

## FAQ

**Q: Can I use this without Evolution Portal?**
A: Yes, you can manually create cases or use the API for integration.

**Q: How many users can access simultaneously?**
A: Tested with 30+ concurrent users. Scales based on server resources.

**Q: Can I customize the workflow?**
A: Yes, modify controllers and views. Use git for version control.

**Q: Is Google Sheets still needed?**
A: No, database is now the single source of truth. Sheets sync is optional.

**Q: How do I create API tokens?**
A: Currently done via SQL. Admin UI for token management coming soon.

## License

Copyright © 2025 CREODENT. All rights reserved.

Proprietary software - Unauthorized copying or distribution is prohibited.

## Credits

Built with:
- PHP 8.x
- MySQL 8.x
- Tailwind CSS
- Alpine.js
- Chart.js

---

**For support, contact:** admin@creodent.com

**Version:** 1.0.0
**Last Updated:** November 2, 2025
