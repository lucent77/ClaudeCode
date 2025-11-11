# Production Management System (PMS)

A comprehensive web-based production management system for manufacturing environments, designed to manage equipment, tools, and production records efficiently.

## Features

- **User Authentication**: Session-based authentication with role-based access control (Admin/Operator)
- **Dashboard**: Real-time metrics, equipment status monitoring, and tool replacement alerts
- **Equipment Management**: Track 8 CNC machines with status monitoring and runtime tracking
- **Tool Management**: Complete tool lifecycle management with usage tracking and stock alerts
- **Equipment-Tool Settings**: Manage tool installations and usage ratios per equipment
- **Production Records**: Daily production tracking with automatic achievement calculations
- **Daily Production Input**: Streamlined data entry for all equipment with auto-calculations
- **Bulk Upload**: CSV/JSON import for tools and production data
- **Automatic Tool Lifespan Tracking**: Automated usage tracking based on production runtime

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, Tailwind CSS (CDN), Vanilla JavaScript (ES6+)
- **Server**: Apache with mod_rewrite

## Installation Guide

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.3+)
- Apache web server with mod_rewrite enabled
- Minimum 256MB RAM

### Step 1: Upload Files

Upload all files to your Hostinger web hosting account via FTP or File Manager.

### Step 2: Create Database

1. Log in to your Hostinger control panel
2. Navigate to "MySQL Databases"
3. Create a new database (e.g., `production_management`)
4. Create a database user and assign it to the database
5. Note down the database name, username, and password

### Step 3: Import Database Schema

1. Access phpMyAdmin from your Hostinger control panel
2. Select your database
3. Click "Import"
4. Choose the `database.sql` file
5. Click "Go" to import the schema

### Step 4: Configure Database Connection

Edit `config/database.php` and update the following constants:

```php
define('DB_HOST', 'localhost');              // Usually 'localhost' for Hostinger
define('DB_NAME', 'your_database_name');    // Your database name
define('DB_USER', 'your_username');         // Your database username
define('DB_PASS', 'your_password');         // Your database password
```

### Step 5: Set Directory Permissions

Ensure the following directories are writable (755 or 775):

```
logs/
uploads/
```

### Step 6: Access the System

1. Navigate to your domain (e.g., `https://yourdomain.com`)
2. You'll be redirected to the login page

**Default Admin Credentials:**
- Username: `admin`
- Password: `admin123`

**Default Operator Credentials:**
- Username: `operator`
- Password: `admin123`

⚠️ **IMPORTANT**: Change these default passwords immediately after first login!

### Step 7: Initial Setup

1. Log in as admin
2. Navigate to "Equipment" to verify 8 CNC machines are created (MP1-MP3, HW1-HW5)
3. Go to "Upload Data" to import tool data from CSV
4. Configure equipment-tool settings in "Tool Settings"
5. Start entering production data in "Daily Input"

## Usage Guide

### Dashboard

The dashboard provides an overview of:
- Total equipment and active tools
- Average OEE (Overall Equipment Effectiveness)
- Weekly runtime statistics
- Equipment status (Running/Idle/Maintenance/Error)
- Tool replacement alerts (80%+ usage)
- Recent production records

### Equipment Management

- View all equipment with current status
- Filter by status (Running/Idle/Maintenance/Error)
- Admin can change equipment status
- View installed tools and production records per equipment

### Tool Management

- Add, edit, and delete tools
- Track tool stock levels (current vs. minimum)
- Monitor tool usage and lifespan
- Search and filter tools by status or category
- Low stock alerts

### Equipment Tool Settings

- Install tools on equipment
- Set tool slot numbers (e.g., T01, T02)
- Configure usage ratio (percentage of runtime applied to tool)
- Remove tools and track replacement history

### Production Records

- View all production records with filters
- Filter by date range and equipment
- View statistics (total records, runtime, achievement, production)
- Detailed view of individual records

### Daily Production Input

The core feature for daily data entry:

**Features:**
- Single-page form for all 8 equipment
- Date navigation (Previous/Next day)
- Auto-calculations:
  - **Total Achievement % = (Unit Total / Plan Count) × 100**
  - **CNC Run Time = Current Working Time - Previous Working Time**
  - Day Achievement % and 24H Achievement %
- Previous working time reference display
- Load Latest Data: Populate fields from most recent records
- Reset: Clear selected equipment data
- Sticky equipment column and headers for easy navigation

### Bulk Upload

**Tools CSV Upload:**
- CSV format with headers: Tool Code, Tool Name, Category Name, Tool Size, Supplier Name, Supplier Model Number, Current Stock, Minimum Stock, Lifespan Type, Lifespan Limit, Description
- Existing tools (by Tool Code) will be updated
- New tools will be added

**Production CSV Upload:**
- CSV format matching Swiss.csv structure
- Option to clear existing data before upload
- Upload logs stored in `logs/` directory

## Auto-Calculated Fields

The system automatically calculates:

1. **CNC Run Time**: Difference between current and previous working time
2. **Day Achievement %**: (Unit / Exped Count) × 100
3. **24H Achievement %**: (Unit / Exped Count 24H) × 100
4. **Total Achievement %**: (Unit Total / Plan Count) × 100

## Tool Lifespan Tracking

The system automatically tracks tool usage:

1. When production data is saved with CNC run time
2. For each tool installed on the equipment
3. Usage hours calculated based on usage ratio
4. Tool status automatically updated (new → in_use)
5. Usage history recorded in database
6. Alerts shown when usage reaches 80%+

## Security Features

- Password hashing with bcrypt
- SQL injection prevention (Prepared Statements)
- XSS protection (htmlspecialchars)
- CSRF protection ready (optional implementation)
- File upload validation
- Directory protection (.htaccess)
- Session-based authentication
- Role-based access control

## File Structure

```
production_management/
├── api/                          # API endpoints
│   ├── save_production_records.php
│   ├── get_previous_working_time.php
│   ├── get_latest_production_data.php
│   ├── update_equipment_status.php
│   ├── add_tool.php
│   ├── update_tool.php
│   ├── delete_tool.php
│   ├── add_tool_to_equipment.php
│   ├── remove_tool_from_equipment.php
│   ├── update_tool_setting.php
│   ├── upload_tools_csv.php
│   └── upload_production_csv.php
├── config/
│   └── database.php              # Database configuration
├── includes/
│   ├── header.php                # Common header
│   └── footer.php                # Common footer
├── logs/                         # Upload logs (protected)
├── uploads/                       # Uploaded files (protected)
├── .htaccess                     # Apache configuration
├── database.sql                  # Database schema
├── index.php                     # Landing page
├── login.php                     # Login page
├── logout.php                    # Logout
├── dashboard.php                 # Main dashboard
├── equipment.php                 # Equipment management
├── tools.php                     # Tool management
├── tool_settings.php             # Equipment-tool settings
├── production.php                # Production records list
├── production_input.php          # Daily production input
├── upload.php                    # Bulk upload interface
└── README.md                     # This file
```

## Database Schema

### Tables

1. **users**: User accounts and roles
2. **equipment**: CNC machines/equipment
3. **tools**: Manufacturing tools
4. **equipment_tool_settings**: Equipment-to-tool relationships
5. **production_records**: Daily production data
6. **tool_usage_history**: Tool usage tracking
7. **tool_changes**: Tool replacement history

### Default Equipment

- MP1, MP2, MP3 (Milling Machines)
- HW1, HW2, HW3, HW4, HW5 (High-precision Workstations)

## API Endpoints

All API endpoints return JSON responses with format:
```json
{
  "success": true/false,
  "message": "Response message",
  "data": {}  // Optional data
}
```

## Troubleshooting

### Login Issues
- Verify database connection in `config/database.php`
- Check if users table has data
- Ensure session is working (check PHP session settings)

### File Upload Errors
- Check directory permissions (logs/ and uploads/ should be writable)
- Verify PHP upload settings (upload_max_filesize, post_max_size)
- Check .htaccess is working

### Database Errors
- Verify MySQL version is 5.7+
- Check database credentials
- Ensure all tables are created
- Check foreign key constraints

### Display Issues
- Ensure Tailwind CSS CDN is accessible
- Check browser console for JavaScript errors
- Verify PHP version is 7.4+

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review server error logs
3. Check `logs/` directory for upload errors
4. Verify all prerequisites are met

## Security Recommendations

1. Change default admin/operator passwords immediately
2. Use HTTPS (SSL certificate) in production
3. Regularly backup your database
4. Keep PHP and MySQL updated
5. Restrict database access to localhost
6. Set `DEBUG_MODE` to `false` in production
7. Regularly review access logs
8. Implement strong password policies

## Maintenance

### Regular Tasks
- Backup database weekly
- Review and clear old logs monthly
- Monitor disk space (uploads/ directory)
- Update tool stock levels
- Review tool usage and plan replacements

### Database Backup
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

## License

Proprietary - All rights reserved

## Version

Version 1.0 - Released January 2025

---

**Built with Hostinger PHP Web Services and MySQL Database**
