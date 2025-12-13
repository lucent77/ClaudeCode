# CAD/CAM Workflow System

A real-time web-based CAD/CAM workflow management system for dental laboratories. This system replaces multiple Google Sheets with a unified platform supporting COCR, SOLIDEX, and 3D Print departments.

## Features

- **Multi-Department Workflow Management**: COCR, SOLIDEX, and 3D Print workflows
- **Per-Tooth Tracking for SOLIDEX**: Unique tooth-level workflow tracking
- **Role-Based Access Control**: Super Admin, Department Manager, and Operator roles
- **Real-Time Concurrency Handling**: Optimistic locking prevents data conflicts
- **Bulk Operations**: Multi-select and batch complete/hold operations
- **HOLD Management**: Track and manage cases on hold with reasons and duration
- **Note Tags System**: Categorize cases with color-coded tags (RUSH, CR, IMPLANT, etc.)
- **Google Drive Integration**: File management linked to cases
- **Gmail Integration**: Design Confirm email automation
- **Audit Logging**: Complete history of all workflow changes
- **Responsive UI**: Modern Tailwind CSS design

## Requirements

- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled
- cURL extension for Google API integration

## Installation

### 1. Upload Files

Upload all files to your Hostinger web hosting:
```
public_html/
├── public/          (web root - point domain here)
├── config/
├── database/
├── includes/
└── views/
```

### 2. Create Database

1. Create a new MySQL database in Hostinger panel
2. Import the schema:
   ```sql
   -- Run the contents of database/schema.sql
   ```

### 3. Configure Application

1. Copy `config/config.php` to `config/config.local.php`
2. Update database credentials:
   ```php
   define('DB_HOST', 'your-db-host');
   define('DB_NAME', 'your-db-name');
   define('DB_USER', 'your-db-user');
   define('DB_PASS', 'your-db-password');
   ```

### 4. Set Web Root

Configure your domain to point to the `public/` directory.

### 5. Login

Default credentials:
- Username: `admin`
- Password: `password`

**IMPORTANT**: Change the password immediately after first login!

## Google API Setup (Optional)

### Google Drive Integration

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable Google Drive API
4. Create OAuth 2.0 credentials
5. Add redirect URI: `https://your-domain.com/auth/google-callback.php`
6. Update config:
   ```php
   define('GOOGLE_CLIENT_ID', 'your-client-id');
   define('GOOGLE_CLIENT_SECRET', 'your-client-secret');
   ```

### Gmail Integration

1. Enable Gmail API in the same project
2. Add Gmail send scope to OAuth consent screen
3. The same OAuth credentials will work for both

## User Roles

### Super Admin
- Full access to all departments
- Manage users and system settings
- Configure workflow steps and note tags
- View global dashboard and reports

### Department Manager
- Full access to assigned department
- Override step completion
- Manage holds within department
- Access department reports

### Operator
- View only assigned steps
- Complete step tasks (single and bulk)
- Cannot modify settings or override

## Workflow Steps

### COCR
1. TRANS (Transfer)
2. DESIGN
3. CAM
4. CNC (requires machine name)
5. OVENS (requires machine name)

### SOLIDEX (Per-Tooth Tracking)
1. TRANSCAN
2. PRECAD
3. CAD
4. PRECAM
5. CNC (requires machine name)
6. QC

### 3D Print
1. TRANSSCAN
2. PRECAD
3. DESIGN
4. NESTING

## API Endpoints

### Workflow Operations
- `POST /api/workflow/complete-step.php` - Complete a single step
- `POST /api/workflow/bulk-complete.php` - Bulk complete steps
- `POST /api/workflow/set-hold.php` - Put item on hold
- `POST /api/workflow/release-hold.php` - Release from hold
- `POST /api/workflow/bulk-hold.php` - Bulk set hold

### Request Format
```json
{
  "entity_id": 123,
  "department": "COCR",
  "step_code": "DESIGN",
  "machine_name": "CNC-01",
  "notes": "Optional notes"
}
```

## Database Schema

Key tables:
- `cases` - Main case data shared across departments
- `case_cocr` - COCR department data
- `case_solidex` - SOLIDEX department data
- `solidex_teeth_tasks` - Per-tooth tracking
- `case_3d_print` - 3D Print department data
- `workflow_steps` - Step configuration
- `step_transitions` - Audit log of step changes
- `hold_history` - Hold/release history
- `note_tags` - Tag configuration

## Security Features

- CSRF protection on all forms
- Password hashing with bcrypt
- Session security with regeneration
- Role-based access control
- SQL injection prevention via PDO
- XSS prevention via output escaping

## Troubleshooting

### Database Connection Error
- Verify credentials in `config/config.local.php`
- Check MySQL service is running
- Verify database exists

### 403 Forbidden
- Check file permissions (755 for directories, 644 for files)
- Verify .htaccess is being read

### Session Issues
- Check session directory permissions
- Verify session cookie settings

### Google API Errors
- Verify OAuth credentials
- Check redirect URI matches exactly
- Ensure APIs are enabled

## Support

For issues or questions, contact your system administrator.

## License

Proprietary software. All rights reserved.
