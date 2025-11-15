# Slack List Hostinger Integration

A modern web application for managing Slack List tasks on Hostinger PHP hosting with database integration. This system allows you to sync, view, and manage your Slack List data through an intuitive web interface.

## Features

- **User Authentication**: Secure login system with Admin and Worker roles
- **Slack List Integration**: Import and sync tasks from Slack List via CSV export
- **Modern Dashboard**: Clean, responsive interface built with Tailwind CSS
- **Task Management**: View, filter, and manage patient tasks and surgery schedules
- **File Management**: Track and access photos, STL files, CBCT scans, and other attachments
- **User Management**: Admin panel for managing users and permissions
- **Activity Logging**: Complete audit trail of all system actions
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ (Hostinger Database)
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Icons**: Font Awesome 6
- **Authentication**: Session-based with secure password hashing

## Installation on Hostinger

### Prerequisites

- Hostinger hosting account with PHP 7.4+ support
- MySQL database access
- FTP/SFTP client or File Manager access

### Step 1: Upload Files

1. Upload all files from the `slack-hostinger-integration` folder to your Hostinger public_html directory
2. Ensure the following structure:
   ```
   public_html/
   ├── index.php
   ├── login.php
   ├── logout.php
   ├── task_detail.php
   ├── sync.php
   ├── users.php
   ├── .htaccess
   ├── config/
   │   └── .htaccess (access denied)
   ├── includes/
   │   └── .htaccess (access denied)
   ├── api/
   ├── uploads/
   ├── database.sql
   └── README.md
   ```

### Step 2: Create Database

1. Log into your Hostinger control panel
2. Go to **Databases** → **MySQL Databases**
3. Create a new database (e.g., `slack_list_manager`)
4. Create a database user and password
5. Assign the user to the database with all privileges

### Step 3: Import Database Schema

1. Go to **phpMyAdmin** in Hostinger control panel
2. Select your newly created database
3. Click **Import** tab
4. Upload the `database.sql` file
5. Click **Go** to execute

### Step 4: Configure Application

1. Open `config/config.php` in a text editor
2. Update the following settings:

```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Base URL
define('BASE_URL', 'https://yourdomain.com');

// Security (IMPORTANT: Change this!)
define('ENCRYPTION_KEY', 'your-unique-secret-key-here');
```

### Step 5: Set Permissions

Ensure the `uploads` directory is writable:
- Via FTP: Set permissions to 755 or 775
- Via File Manager: Right-click → Permissions → 755

### Step 6: Access the Application

1. Navigate to your domain in a web browser
2. You should be redirected to the login page
3. Use default credentials to log in:
   - **Admin**: username: `admin`, password: `password`
   - **Worker**: username: `worker`, password: `password`
4. **IMPORTANT**: Change these passwords immediately after first login!

## Usage Guide

### First Time Setup

1. **Log in as Admin**
   - Use the default admin credentials
   - Navigate to Users page
   - Change the default password

2. **Create Additional Users**
   - Go to **Users** in the navigation
   - Click **Create New User**
   - Fill in user details and assign appropriate role

3. **Sync Data from Slack**
   - Go to **Sync** in the navigation
   - Export your Slack List as CSV from Slack
   - Upload the CSV file to sync tasks

### Exporting from Slack List

1. Open your Slack workspace
2. Navigate to the Canvas containing your List
3. Click the **"..."** menu in the top right
4. Select **"Export to CSV"**
5. Save the CSV file to your computer
6. Upload it via the Sync page

### Managing Tasks

- **Dashboard**: View all tasks with statistics and filters
- **Search**: Filter by patient name, date range, arch, or status
- **Task Details**: Click "View" on any task to see complete information
- **Files**: All file attachments are linked to Slack for viewing

### User Roles

**Admin**:
- Full access to all features
- Can create/manage users
- Can sync data from Slack
- Can update task statuses

**Worker**:
- View all tasks
- Search and filter tasks
- View task details
- Access file links
- Cannot manage users or sync data

## Security Best Practices

1. **Change Default Passwords**: Immediately change all default passwords
2. **Use Strong Passwords**: Minimum 8 characters with mixed case, numbers, and symbols
3. **Enable HTTPS**: Configure SSL certificate in Hostinger (free with Let's Encrypt)
4. **Update Encryption Key**: Change the ENCRYPTION_KEY in config.php
5. **Restrict Database Access**: Use strong database passwords
6. **Regular Backups**: Enable automatic backups in Hostinger control panel
7. **Keep Software Updated**: Regularly update PHP version in Hostinger settings

## File Structure

```
slack-hostinger-integration/ (upload to public_html)
├── index.php                   # Main dashboard (root)
├── login.php                   # Login page (root)
├── logout.php                  # Logout handler (root)
├── task_detail.php             # Task details page (root)
├── sync.php                    # Data sync page (root)
├── users.php                   # User management (root)
├── .htaccess                   # Apache configuration
├── config/
│   ├── config.php              # Main configuration file
│   └── .htaccess               # Deny web access
├── includes/
│   ├── auth.php                # Authentication handler
│   ├── slack_service.php       # Slack API integration
│   └── .htaccess               # Deny web access
├── api/
│   ├── tasks.php               # Tasks API endpoint
│   └── users.php               # Users API endpoint
├── uploads/                    # File upload directory
├── database.sql                # Database schema
├── README.md                   # Documentation
└── INSTALLATION_GUIDE.md       # Installation instructions
```

## Troubleshooting

### Database Connection Error
- Verify database credentials in config/config.php
- Ensure database exists and is accessible
- Check if database user has proper privileges

### Cannot Upload Files
- Check uploads directory permissions (755 or 775)
- Verify PHP upload_max_filesize setting
- Ensure disk space is available

### CSV Sync Not Working
- Verify CSV file format matches Slack export
- Check for special characters in CSV data
- Review sync logs in the Sync page

### Login Issues
- Clear browser cookies and cache
- Verify user is active in database
- Check session settings in config.php

### File Access Issues
- Ensure Slack Bot Token is valid and has file access permissions
- Verify file IDs are correct in database
- Check network connectivity to Slack

## Support and Maintenance

### Regular Maintenance Tasks

1. **Database Backups**: Weekly backups via Hostinger control panel
2. **Review Logs**: Check activity logs for suspicious activity
3. **Update Tasks**: Regular sync from Slack to keep data current
4. **User Audit**: Review active users and remove inactive accounts

### Getting Help

- Check Hostinger knowledge base for hosting-related issues
- Review error logs in Hostinger File Manager
- Contact your system administrator for custom modifications

## Slack Configuration

The application requires Slack credentials to be configured in `config/config.php`:

- **Bot Token**: Your Slack Bot OAuth Token (starts with `xoxb-`)
- **Workspace ID**: Your Slack Workspace ID
- **Canvas ID**: The Canvas/List document ID containing your tasks
- **Canvas URL**: The shareable URL to your Slack Canvas

**Important Security Note**:
- Keep your Slack Bot Token secure and never commit it to version control
- Never share your credentials publicly
- Update `config.php` with your actual credentials after installation
- The `config.php` file is excluded from git via `.gitignore`

## Features in Detail

### Dashboard Statistics
- Total tasks count
- Pending vs. completed tasks
- Tasks ready for surgery
- Today's surgeries count

### Task Filtering
- Search by patient name or notes
- Filter by status (pending/completed)
- Filter by arch type (Upper/Lower/Both)
- Date range filtering

### File Type Support
- Photos (camera icon, purple badges)
- STL files (cube icon, blue badges)
- CBCT scans (x-ray icon, green badges)
- Pre/Post-op scans

### Activity Logging
All user actions are logged including:
- Login/logout events
- User creation and modifications
- Task status updates
- Data sync operations

## License

Proprietary - For internal use only

## Version

Version 1.0.0 - Initial Release

---

**Important Security Notice**: This application contains sensitive medical and patient information. Ensure all security best practices are followed and access is restricted to authorized personnel only.
