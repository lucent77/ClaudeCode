# Dental CRM System

A comprehensive Customer Relationship Management (CRM) system designed for dental labs and milling centers, built with PHP and MySQL for deployment on Hostinger.

## Features

### Core Functionality

- **Customer Data Management**
  - Import customers from CSV files
  - Store comprehensive customer information
  - Advanced search and filtering
  - Customer status tracking (Active/Inactive/Prospect)

- **User Roles & Permissions**
  - Admin: Full system access
  - Manager: View reports and manage assignments
  - Sales Rep: Manage assigned customers

- **Customer Assignment**
  - Assign customers to sales representatives
  - Filter by region, type, and status
  - Bulk assignment capabilities

- **Interaction Logging**
  - Log calls, emails, meetings, and visits
  - Track communication history
  - Set follow-up reminders
  - Record outcomes

- **Calendar & Scheduling**
  - Schedule customer meetings
  - Track upcoming appointments
  - Meeting reminders
  - Multiple meeting types (call, visit, online)

- **Reporting & Analytics**
  - Performance dashboards
  - Sales metrics tracking
  - Monthly active customer reports
  - Activity logs

### Best Practices Implemented

- ✅ User-friendly, intuitive interface
- ✅ Role-based access control
- ✅ Responsive design (mobile-friendly)
- ✅ Comprehensive customer 360° view
- ✅ Activity tracking and audit logs
- ✅ Scalable database design
- ✅ Secure authentication
- ✅ Fast search and filtering

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, vanilla JavaScript
- **Icons**: Bootstrap Icons
- **Hosting**: Optimized for Hostinger PHP hosting

## Installation

### Prerequisites

- Hostinger PHP hosting account (or any PHP 7.4+ hosting)
- MySQL database
- FTP/SFTP access or File Manager

### Step 1: Upload Files

1. Download/clone this repository
2. Upload the `hostinger-crm` folder to your Hostinger public_html directory
3. Ensure all files are uploaded successfully

### Step 2: Create MySQL Database

1. Log in to your Hostinger control panel
2. Go to **Databases** → **MySQL Databases**
3. Create a new database (e.g., `dentalcrm_db`)
4. Create a database user with a strong password
5. Grant all privileges to the user for this database
6. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

### Step 3: Configure Database Connection

1. Open `/config/database.php`
2. Update the following constants with your database credentials:

```php
define('DB_HOST', 'localhost');           // Usually localhost
define('DB_NAME', 'your_database_name');  // Your database name
define('DB_USER', 'your_database_user');  // Your database username
define('DB_PASS', 'your_database_pass');  // Your database password
```

### Step 4: Import Database Schema

1. Go to **phpMyAdmin** in your Hostinger control panel
2. Select your database
3. Click **Import**
4. Upload the file `/sql/schema.sql`
5. Click **Go** to execute

This will create all necessary tables and insert default admin user.

### Step 5: Configure Application Settings

1. Open `/config/config.php`
2. Update the site URL:

```php
define('SITE_URL', 'https://yourdomain.com'); // Update with your domain
```

3. (Optional) Configure email settings for future notifications

### Step 6: Set Permissions

Ensure the following directories are writable:

```bash
chmod 755 /hostinger-crm
chmod 755 /hostinger-crm/uploads
```

### Step 7: Access the System

1. Navigate to: `https://yourdomain.com/hostinger-crm/`
2. You will be redirected to the login page

**Default Login Credentials:**
- Username: `admin`
- Password: `admin123`

⚠️ **Important**: Change the admin password immediately after first login!

## Usage Guide

### First Steps

1. **Login** with admin credentials
2. **Import Customers**: Go to Admin → Import Customers
3. **Create Users**: Go to Admin → Manage Users (create sales reps)
4. **Assign Customers**: Go to Admin → Assignments

### For Administrators

- **Import Customers**: Upload CSV files with customer data
- **Manage Users**: Create and manage sales rep accounts
- **Assign Customers**: Allocate customers to sales representatives
- **View Reports**: Monitor team performance and metrics
- **System Logs**: Track all system activities

### For Sales Representatives

- **View Customers**: Access assigned customer list
- **Customer Details**: View complete customer profile
- **Log Interactions**: Record calls, emails, meetings
- **Schedule Meetings**: Create appointments with customers
- **Follow-ups**: Track pending follow-up tasks

### For Managers

- **View Reports**: Access performance dashboards
- **Monitor Team**: Track sales team activities
- **View All Customers**: See complete customer database
- **Assign Customers**: Allocate customers to team members

## CSV Import Format

The system accepts CSV files with the following columns:

```
Customer Key, Account Number, BillTo Account Number, Account Type, Account Class,
Customer Status, Customer Status Description, Customer Status Reason, Title,
First Name, Last Name, Middle Initial, Practice Name, Address Line 1, Address Line 2,
Address Line 3, City, State Code, Zip Code, Website, Phone Number, Fax Number,
Email Address, Cell Phone, Route Key, Route Order, Route Visit Timeframe,
BillTo Flag, ShipTo Flag, Allow Case Entry, Always Visit, Date Created,
Date Of First Case, Date Of Last Case, New Customer, Credit Card AutoPay Group
```

**Important Notes:**
- First row must be the header
- Dates should be in format: YYYY-MM-DD HH:MM:SS
- Boolean fields: TRUE/FALSE or 1/0
- Duplicate customers (by Customer Key or Account Number) will be skipped

## Security

### Best Practices

1. **Change Default Password**: Immediately change admin password
2. **Use HTTPS**: Enable SSL certificate (free with Hostinger)
3. **Regular Backups**: Backup database regularly
4. **Update PHP**: Keep PHP version updated
5. **Secure Credentials**: Never share database credentials

### Built-in Security Features

- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- Session management with timeout
- Role-based access control
- Audit logging

## Database Structure

### Main Tables

- **users**: System users (admin, managers, sales reps)
- **customers**: Customer records with complete information
- **assignments**: Customer-to-sales rep mappings
- **interactions**: Communication logs (calls, emails, etc.)
- **meetings**: Scheduled appointments
- **sales_performance**: Monthly performance metrics
- **system_logs**: Audit trail

## Troubleshooting

### Cannot Login

- Verify database connection in `/config/database.php`
- Check if database schema was imported successfully
- Clear browser cache and cookies

### CSV Import Fails

- Ensure CSV file has header row
- Check file encoding (UTF-8 recommended)
- Verify date formats
- Check for special characters

### Database Connection Error

- Verify database credentials in config file
- Check if database exists
- Ensure database user has proper privileges
- Confirm MySQL service is running

### Page Not Found (404)

- Check if files are in correct directory
- Verify .htaccess file exists (if using Apache)
- Check file permissions

## Maintenance

### Regular Tasks

- **Daily**: Monitor system logs for errors
- **Weekly**: Review customer assignments
- **Monthly**: Backup database
- **Quarterly**: Review and archive old data

### Database Backup

Via phpMyAdmin:
1. Select your database
2. Click **Export**
3. Choose **Quick** export method
4. Click **Go**
5. Save the SQL file securely

## Support & Contact

For technical support or questions:
- Review this README thoroughly
- Check database logs for errors
- Verify configuration files
- Contact your hosting provider for server-related issues

## License

This CRM system is proprietary software developed for internal use.

## Credits

Developed for dental lab and milling center operations.
Built with PHP, MySQL, Bootstrap 5, and Bootstrap Icons.

---

**Version**: 1.0.0
**Last Updated**: 2024
**Hosting**: Optimized for Hostinger PHP hosting
