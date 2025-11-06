# 📦 Purchase Management System

A complete web-based purchase order management system built with **PHP 8** and **MySQL**, designed for deployment on **Hostinger** hosting services.

## 🎯 Overview

This system allows organizations to:
- Manage purchase requests from multiple departments
- Track vendors and products with historical pricing
- Approve/reject purchase orders with admin controls
- Export data to CSV for reporting
- Maintain purchase history with intelligent re-ordering

## ✨ Features

### For Users (Departments)
- 🔐 Secure login per department
- 📝 Submit new purchase requests with photo uploads
- 🔍 Auto-complete product search from past purchases
- 📊 View department purchase history
- 🔔 Real-time notifications on request status changes
- 📈 Dashboard with spending statistics

### For Administrators
- 👥 Manage users and departments
- 🏢 Manage vendors and products
- ✅ Approve/reject purchase requests
- 📝 Update order status (Pending → Approved → Ordered → Delivered → Completed)
- 💰 View spending analytics by department
- 📥 Export data to CSV/Excel
- 📊 Comprehensive dashboard with insights

## 🛠️ Tech Stack

- **Backend:** PHP 8.x
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, Tailwind CSS 3.x
- **JavaScript:** Vanilla JS (AJAX for dynamic features)
- **Authentication:** Session-based with password hashing
- **File Upload:** Support for images and PDFs

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server (included in Hostinger)
- Minimum 100MB storage space
- SSL Certificate (recommended for production)

## 🚀 Installation Guide for Hostinger

### Step 1: Download and Prepare Files

1. Download all project files from this repository
2. Ensure you have the following structure:
```
purchase-system/
├── admin/
├── api/
├── assets/
├── config/
├── includes/
├── uploads/
├── database.sql
├── index.php
├── login.php
└── ... (other files)
```

### Step 2: Upload Files via FTP

1. **Connect to Hostinger via FTP:**
   - Use FileZilla or your preferred FTP client
   - Host: Your domain (e.g., `ftp.yourdomain.com`)
   - Username: Your Hostinger FTP username
   - Password: Your Hostinger FTP password
   - Port: 21

2. **Upload files to public_html:**
   - Navigate to `/public_html/` (or `/public_html/purchase-system/`)
   - Upload all files and folders
   - Ensure the `uploads/` directory has write permissions (755 or 777)

### Step 3: Create MySQL Database

1. **Login to Hostinger Control Panel (hPanel)**

2. **Navigate to MySQL Databases:**
   - Click on "MySQL Databases"
   - Create a new database (e.g., `u123456_purchase_db`)
   - Create a database user with a strong password
   - Grant all privileges to the user for this database

3. **Import Database Schema:**
   - Go to phpMyAdmin (accessible from hPanel)
   - Select your newly created database
   - Click "Import" tab
   - Choose the `database.sql` file
   - Click "Go" to execute

### Step 4: Configure Database Connection

1. **Edit `config/config.php`:**
```php
define('DB_HOST', 'localhost');              // Usually 'localhost' on Hostinger
define('DB_USER', 'u123456_dbuser');         // Your database username
define('DB_PASS', 'your_secure_password');   // Your database password
define('DB_NAME', 'u123456_purchase_db');    // Your database name
define('BASE_URL', 'https://yourdomain.com'); // Your actual domain
```

2. **Set Upload Directory (if needed):**
   - Ensure `UPLOAD_DIR` in config.php points to the correct path
   - Default is usually fine for most setups

### Step 5: Set File Permissions

Using FTP or SSH, set the following permissions:

```bash
chmod 755 /public_html/purchase-system/
chmod 755 /public_html/purchase-system/uploads/
chmod 644 /public_html/purchase-system/config/config.php
```

For the uploads directory to work properly:
```bash
chmod 777 /public_html/purchase-system/uploads/
```

### Step 6: Access the System

1. **Open your browser and navigate to:**
   - `https://yourdomain.com/purchase-system/` (or your configured path)

2. **Default Login Credentials:**

   **Administrator:**
   - Username: `admin`
   - Password: `admin123`

   **Department Users:**
   - Username: `it_dept`, `hr_dept`, `finance_dept`, `marketing_dept`
   - Password: `password123` (for all demo users)

   **⚠️ IMPORTANT:** Change these passwords immediately after first login!

### Step 7: Initial Configuration

1. **Login as admin**
2. **Change admin password** via Profile page
3. **Create department users** via Admin Panel → Manage Users
4. **Add vendors** via Admin Panel → Manage Vendors
5. **Configure email settings** (optional) in `config/config.php`

## 📁 Project Structure

```
purchase-system/
│
├── admin/                      # Admin panel pages
│   ├── index.php              # Admin dashboard
│   ├── edit-request.php       # Edit purchase requests
│   ├── manage-requests.php    # Manage all requests
│   ├── manage-users.php       # User management
│   ├── manage-vendors.php     # Vendor management
│   └── export-csv.php         # CSV export functionality
│
├── api/                       # API endpoints
│   └── search-products.php    # Product autocomplete API
│
├── assets/                    # Static assets
│   ├── css/                   # Custom CSS (if any)
│   ├── js/                    # Custom JavaScript
│   └── images/                # Images
│
├── config/                    # Configuration files
│   ├── config.php            # Main configuration
│   └── database.php          # Database connection class
│
├── includes/                  # Reusable components
│   ├── header.php            # Header template
│   ├── footer.php            # Footer template
│   └── functions.php         # Helper functions
│
├── uploads/                   # User uploaded files
│
├── dashboard.php             # User dashboard
├── database.sql              # Database schema
├── index.php                 # Landing page
├── login.php                 # Login page
├── logout.php                # Logout handler
├── new-request.php           # New purchase request form
├── notifications.php         # User notifications
├── profile.php               # User profile
├── view-request.php          # View request details
└── README.md                 # This file
```

## 🔒 Security Features

- ✅ Password hashing using `password_hash()` (bcrypt)
- ✅ Prepared statements for SQL injection prevention
- ✅ CSRF token validation on forms
- ✅ Session-based authentication
- ✅ Input sanitization on all user inputs
- ✅ Role-based access control (Admin/User)
- ✅ File upload validation (type, size)

## 📊 Database Schema

### Tables

1. **users** - User accounts (departments and admins)
2. **vendors** - Vendor information
3. **products** - Product catalog with pricing history
4. **purchase_requests** - All purchase orders
5. **notifications** - User notifications

See `database.sql` for complete schema with relationships.

## 🎨 Customization

### Change Color Scheme

The system uses Tailwind CSS. To customize colors, modify the color classes in the template files:

```html
<!-- Change primary color from blue to purple -->
<button class="bg-purple-600 hover:bg-purple-700">
```

### Add Custom Fields

1. Add column to database table
2. Update PHP files to handle new field
3. Add input field to forms

### Email Notifications

To enable email notifications:

1. Edit `config/config.php`:
```php
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@yourdomain.com');
define('SMTP_PASS', 'your_email_password');
```

2. Emails will be sent automatically on status changes

## 🐛 Troubleshooting

### Database Connection Error
- Verify database credentials in `config/config.php`
- Ensure database exists and user has proper privileges
- Check that MySQL service is running

### File Upload Fails
- Check `uploads/` directory permissions (should be 755 or 777)
- Verify `MAX_FILE_SIZE` in `config/config.php`
- Check PHP `upload_max_filesize` and `post_max_size` settings

### Page Not Found (404)
- Ensure `.htaccess` file exists (for Apache)
- Update `BASE_URL` in `config/config.php`
- Check file paths match your hosting structure

### Session Issues
- Ensure sessions are enabled in PHP configuration
- Check session directory has write permissions
- Verify `session.save_path` in PHP settings

## 📈 Usage Workflow

### For Department Users:

1. **Login** with department credentials
2. **View Dashboard** to see past requests and statistics
3. **Create New Request:**
   - Select request type
   - Enter product name (auto-complete suggestions appear)
   - Choose or add vendor
   - Specify quantity and shipping
   - Upload photo (optional)
   - Submit request
4. **Track Status** through notifications and dashboard

### For Administrators:

1. **Login** as admin
2. **Review Pending Requests** in Admin Panel
3. **Approve/Reject** requests with notes
4. **Update Status** as order progresses
5. **Manage Vendors** and update product pricing
6. **Export Reports** to CSV for accounting
7. **Create Users** for new departments

## 🔄 Backup and Maintenance

### Database Backup (Recommended: Daily)

Using phpMyAdmin:
1. Login to phpMyAdmin
2. Select database
3. Click "Export"
4. Choose "Quick" method
5. Download SQL file

### File Backup

Use FTP to download:
- `/uploads/` directory (user uploaded files)
- `/config/config.php` (configuration)

### Updates

To update the system:
1. Backup current files and database
2. Upload new files via FTP
3. Run any migration scripts (if provided)
4. Clear browser cache

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review Hostinger documentation
3. Consult PHP/MySQL documentation

## 📜 License

This project is open-source and available for commercial use.

## 🙏 Credits

- Built with PHP 8 and MySQL
- Styled with Tailwind CSS
- Icons from Heroicons
- Designed for Hostinger deployment

---

## 🎉 Quick Start Checklist

- [ ] Upload files to Hostinger via FTP
- [ ] Create MySQL database and import `database.sql`
- [ ] Update `config/config.php` with database credentials
- [ ] Set uploads directory permissions to 755
- [ ] Login with default admin credentials
- [ ] Change admin password
- [ ] Create department users
- [ ] Add vendors
- [ ] Test purchase request flow
- [ ] Configure email notifications (optional)
- [ ] Set up automated backups

---

**Built with ❤️ for efficient purchase management**
