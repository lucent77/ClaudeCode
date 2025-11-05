# Task Management System

A comprehensive web-based task management platform built with PHP, MySQL, and Tailwind CSS. Designed for dental labs and similar businesses with multiple departments requiring real-time task tracking and workflow management.

## Features

### Core Functionality
- **Real-time Updates**: Tasks update automatically across all users using AJAX polling
- **Role-Based Access Control (RBAC)**: Admin, Front Desk, and Department User roles
- **Department-Specific Pages**: SOLIDEX, COCR, 3D Print, and Front Desk views
- **Task Management**: Create, update, track, and complete tasks with full history logging
- **JSON Import**: Bulk import tasks from Google Sheets exports
- **Inline Editing**: Update task status directly from the task list
- **Responsive Design**: Works on desktop, tablet, and mobile devices

### Security Features
- Secure authentication with bcrypt password hashing
- CSRF protection on all forms
- XSS and SQL injection prevention
- Session security with HTTP-only cookies
- Input sanitization and validation

### Department Features
- **Front Desk**: Overview of all tasks, create cases, import JSON data
- **SOLIDEX**: Manage implant system tasks with teeth, design, and lab tracking
- **COCR**: Track crown and bridge cases with tooth numbers and implant types
- **3D Print**: Manage 3D printing cases with patient and case numbers
- **Admin Dashboard**: System statistics and user management

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: HTML5, JavaScript (ES6), Tailwind CSS
- **Server**: Apache with mod_rewrite
- **Real-time**: AJAX polling (SSE-ready)

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache 2.4+ with mod_rewrite enabled
- 256MB RAM minimum (512MB recommended)
- 50MB disk space

## Installation

### 1. Clone or Download
```bash
git clone https://github.com/your-repo/task-management-system.git
cd task-management-system
```

### 2. Configure Database
Create a MySQL database and user:
```sql
CREATE DATABASE task_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'task_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON task_management.* TO 'task_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Import Database Schema
```bash
mysql -u task_user -p task_management < database/schema.sql
```

### 4. (Optional) Load Sample Data
```bash
mysql -u task_user -p task_management < database/seed_data.sql
```

### 5. Configure Application
Copy the example environment file and update with your settings:
```bash
cp .env.example .env
```

Edit `config/database.php` with your database credentials:
```php
'host' => 'localhost',
'database' => 'task_management',
'username' => 'task_user',
'password' => 'your_secure_password',
```

### 6. Set Permissions
```bash
chmod 755 public
chmod 755 uploads logs
chmod 644 config/*.php
```

### 7. Configure Web Server

#### Apache (Hostinger/cPanel)
The `.htaccess` files are already configured. Ensure:
- Document root points to `/public` directory
- mod_rewrite is enabled
- `.htaccess` files are allowed (AllowOverride All)

#### Apache Virtual Host Example
```apache
<VirtualHost *:80>
    ServerName taskmanagement.local
    DocumentRoot /path/to/task-management-system/public

    <Directory /path/to/task-management-system/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 8. Access the Application
Navigate to your domain or localhost. Default login credentials:
- **Email**: admin@example.com
- **Password**: admin123

**⚠️ IMPORTANT**: Change the default admin password immediately after first login!

## Configuration

### Database Configuration
Edit `config/database.php` to set your database connection details.

### Application Configuration
Edit `config/app.php` to customize:
- Application name and timezone
- Session settings
- Security settings (password cost, CSRF token name)
- File upload limits and allowed types
- Real-time update method (SSE or polling)
- Pagination settings

### Real-Time Updates
The system uses AJAX polling by default (5-second interval). To use Server-Sent Events (SSE):
1. Set `'method' => 'sse'` in `config/app.php`
2. Create an SSE endpoint in `public/api/sse.php`
3. Update JavaScript to use EventSource

## Usage

### Managing Users (Admin Only)
1. Go to Admin Dashboard
2. Click "Add User"
3. Fill in user details:
   - Name and email
   - Password
   - Role (Admin, Front Desk, or Department User)
   - Department (if applicable)
4. Click "Save User"

### Creating Tasks
1. Navigate to the appropriate department page
2. Click "New Task"
3. Fill in task details
4. Click "Create Task"

### Importing JSON Data
1. Go to Front Desk page
2. Click "Import JSON"
3. Select department
4. Choose JSON file exported from Google Sheets
5. Click "Import"

JSON files should contain arrays of objects with fields like:
```json
[
  {
    "ID": "2025-71014",
    "DATE": "11/04",
    "TYPE": "CR",
    "TOOTH #": "12",
    "IMPLANT TYPE": "ACT35 IO 2B-A SA",
    "DESIGN": "ST",
    "LAB #": "E & R Dental Lab"
  }
]
```

### Updating Task Status
Click the status dropdown on any task row to change its status. Changes are logged in task history and broadcast to all users in real-time.

### Viewing Task History
Click the history icon (clock) next to any task to view all changes made to that task, including who made the change and when.

## Project Structure

```
task-management-system/
├── assets/
│   ├── css/              # CSS files
│   └── js/
│       └── app.js        # Main JavaScript application
├── config/
│   ├── app.php           # Application configuration
│   └── database.php      # Database configuration
├── database/
│   ├── schema.sql        # Database schema
│   └── seed_data.sql     # Sample data
├── includes/
│   ├── Auth.php          # Authentication handler
│   ├── Database.php      # Database connection
│   ├── Security.php      # Security utilities
│   ├── TaskManager.php   # Task operations
│   └── UserManager.php   # User management
├── public/
│   ├── api/              # API endpoints
│   │   ├── import.php    # JSON import
│   │   ├── tasks.php     # Task CRUD
│   │   └── users.php     # User management
│   ├── 3d-print.php      # 3D Print department
│   ├── admin.php         # Admin dashboard
│   ├── cocr.php          # COCR department
│   ├── front-desk.php    # Front Desk page
│   ├── index.php         # Main dashboard
│   ├── login.php         # Login page
│   ├── logout.php        # Logout handler
│   └── solidex.php       # Solidex department
├── uploads/              # Uploaded files
├── logs/                 # Application logs
└── views/
    ├── components/       # Reusable components
    └── layouts/          # Page layouts
```

## Security Considerations

### Production Deployment
1. **Change Default Credentials**: Update admin password immediately
2. **Use HTTPS**: Enable SSL/TLS certificates
3. **Update Config**: Set `'secure' => true` in session config
4. **Set Strong Passwords**: Enforce password policies
5. **Regular Backups**: Set up automated database backups
6. **Keep Updated**: Update PHP and MySQL regularly
7. **Restrict File Permissions**: Ensure proper file/folder permissions
8. **Enable Error Logging**: Log errors to files, not browser
9. **Use Environment Variables**: Store sensitive config in `.env` file

### Recommended Security Headers
Add to `.htaccess`:
```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

## Troubleshooting

### Database Connection Error
- Check database credentials in `config/database.php`
- Verify MySQL service is running
- Ensure database exists and user has permissions

### Real-Time Updates Not Working
- Check browser console for JavaScript errors
- Verify `assets/js/app.js` is loading
- Ensure AJAX polling is initialized (check network tab)

### File Upload Issues
- Check `upload_max_filesize` in PHP configuration
- Verify `uploads/` directory has write permissions
- Check Apache/PHP error logs

### Login Issues
- Clear browser cookies and sessions
- Verify user exists in database
- Check password hash is correct
- Review `logs/` directory for errors

## API Documentation

### Tasks API (`/public/api/tasks.php`)

#### Get Tasks
```
GET /public/api/tasks.php
GET /public/api/tasks.php?department_id=2
GET /public/api/tasks.php?since=2025-11-05%2012:00:00
```

#### Create Task
```
POST /public/api/tasks.php
Content-Type: application/json

{
  "department_id": 2,
  "date": "2025-11-05",
  "type": "CR",
  "tooth": "12",
  "design": "ST",
  "notes": "Rush order"
}
```

#### Update Task
```
PUT /public/api/tasks.php?id=123
Content-Type: application/json

{
  "status_id": 2,
  "notes": "Updated notes"
}
```

#### Delete Task (Admin Only)
```
DELETE /public/api/tasks.php?id=123
```

### Users API (`/public/api/users.php`) - Admin Only

#### Get Users
```
GET /public/api/users.php
GET /public/api/users.php?id=5
```

#### Create User
```
POST /public/api/users.php
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secure_password",
  "role_id": 2,
  "department_id": 2
}
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is proprietary software. All rights reserved.

## Support

For issues, questions, or feature requests, please contact the development team or open an issue in the repository.

## Changelog

### Version 1.0.0 (2025-11-05)
- Initial release
- Core task management functionality
- Role-based access control
- Department-specific pages
- Real-time updates via AJAX polling
- JSON import capability
- User management
- Task history logging
