# Creodent AoX Elevate Dashboard

A comprehensive case management system for AoX dental cases with Slack Canvas integration, image/file management, and automated workflow tracking.

## Features

### Core Features
- ✅ **Slack Canvas Integration** - Automatic sync from Slack Canvas tables
- ✅ **File Management** - Photos, STL files, CBCT scans with flexible storage
- ✅ **Case Management** - Full CRUD operations for dental cases
- ✅ **Workflow Tracking** - Multi-step workflow management
- ✅ **Dashboard Analytics** - Real-time statistics and filtering
- ✅ **Activity Logging** - Complete audit trail for all actions
- ✅ **Auto-Sync** - Scheduled synchronization via cron jobs
- ✅ **Authentication** - JWT-based secure authentication
- ✅ **Role-Based Access** - Admin, Manager, and User roles

### File Handling
- **Three Storage Strategies:**
  1. **Slack URL Only** - Minimal storage, proxy files from Slack
  2. **Local Download** - Full independence, all files stored locally
  3. **Hybrid** (Recommended) - Photos from Slack, STL/CBCT stored locally

### Supported File Types
- 📷 Photos (JPG, PNG, GIF)
- 🧊 STL 3D Models
- 🔬 CBCT/DICOM Scans
- 📄 Other files (ZIP, PDF, etc.)

## Technology Stack

### Backend
- **Framework**: Laravel 10.x
- **Language**: PHP 8.0+
- **Database**: MySQL/MariaDB
- **Authentication**: JWT (tymon/jwt-auth)
- **HTTP Client**: Guzzle

### Frontend
- **Framework**: Vanilla JavaScript
- **Styling**: TailwindCSS
- **Icons**: Font Awesome
- **Architecture**: SPA (Single Page Application)

### Infrastructure
- **Hosting**: Hostinger (optimized for)
- **Web Server**: Apache with mod_rewrite
- **Cron**: Automated sync scheduling
- **SSL**: HTTPS support

## Project Structure

```
creodent-aox-dashboard/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/          # API controllers
│   │   └── Middleware/        # Custom middleware
│   ├── Models/                # Eloquent models
│   ├── Services/              # Business logic services
│   │   ├── SlackApiService.php
│   │   ├── SlackSyncService.php
│   │   └── FileStorageService.php
│   └── Repositories/          # Data access layer
├── config/
│   └── services.php           # Third-party service config
├── database/
│   └── migrations/            # Database migrations
├── public/                    # Web root
│   ├── index.html            # Main dashboard UI
│   ├── js/
│   │   └── app.js            # Frontend JavaScript
│   └── uploads/              # File storage
├── routes/
│   └── api.php               # API routes
├── cron/
│   └── sync.php              # Cron sync script
├── .env.example              # Environment template
├── composer.json             # PHP dependencies
├── DEPLOYMENT.md             # Deployment guide
└── README.md                 # This file
```

## Installation

### Quick Start

```bash
# Clone repository
git clone <repository-url>
cd creodent-aox-dashboard

# Install dependencies
composer install

# Configure environment
cp .env.example .env
nano .env  # Edit with your settings

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Create admin user
php artisan tinker
# Then in tinker:
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@example.com';
$user->password = bcrypt('password');
$user->role = 'admin';
$user->save();
exit

# Start development server
php artisan serve
```

Visit `http://localhost:8000`

### Production Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed Hostinger deployment instructions.

## Configuration

### Environment Variables

Key configuration options in `.env`:

```env
# Application
APP_NAME="Creodent AoX Dashboard"
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=creodent_aox
DB_USERNAME=root
DB_PASSWORD=

# Slack API
SLACK_BOT_TOKEN=xoxb-your-bot-token
SLACK_CANVAS_ID=your-canvas-id

# File Storage Strategy
FILE_STORAGE_STRATEGY=hybrid
# Options: slack_url, local_download, hybrid

# Sync Settings
SYNC_ENABLED=true
SYNC_INTERVAL_MINUTES=30
```

### Slack App Setup

1. Create Slack App at https://api.slack.com/apps
2. Add Bot Token Scopes:
   - `files:read`
   - `channels:read`
   - `users:read`
3. Install to workspace
4. Copy Bot Token to `.env`

### File Storage Configuration

#### Strategy: slack_url
- **Pros**: Minimal storage, no downloads
- **Cons**: Requires Slack token for access
- **Best for**: Small teams, limited storage

#### Strategy: local_download
- **Pros**: Complete independence, offline access
- **Cons**: Higher storage usage
- **Best for**: Large files, long-term storage

#### Strategy: hybrid (Recommended)
- **Pros**: Balanced approach
- **Implementation**: Photos from Slack, STL/CBCT stored locally
- **Best for**: Most use cases

## API Documentation

### Authentication

```bash
# Login
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {...}
}
```

### Cases

```bash
# Get all cases
GET /api/cases?status=open&search=patient

# Get single case
GET /api/cases/{id}

# Create case
POST /api/cases
{
  "patient_name": "John Doe",
  "surgery_date": "2024-12-01",
  "assignee_name": "Dr. Smith"
}

# Update case
PUT /api/cases/{id}

# Delete case
DELETE /api/cases/{id}

# Get statistics
GET /api/cases/statistics
```

### Attachments

```bash
# Get attachments for case
GET /api/cases/{caseId}/attachments

# Upload attachment
POST /api/cases/{caseId}/attachments
FormData:
  - file: [binary]
  - type: photo|stl|cbct|scan|other
  - label: "Pre-op scan"

# Download attachment
GET /api/cases/{caseId}/attachments/{id}/download

# Delete attachment
DELETE /api/cases/{caseId}/attachments/{id}
```

### Sync

```bash
# Trigger manual sync
POST /api/sync/trigger

# Get sync logs
GET /api/sync/logs

# Test Slack connection
GET /api/sync/test

# Get sync statistics
GET /api/sync/statistics
```

## Database Schema

### Cases Table
- Patient information
- Surgery and due dates
- Status and priority
- Assignee tracking
- Slack integration

### Case Attachments Table
- File metadata
- Multiple storage options
- Type categorization
- Thumbnail support

### Case Steps Table
- Workflow management
- Progress tracking
- Time logging

### Activity Logs Table
- Complete audit trail
- User actions
- Change history

### Sync Logs Table
- Sync history
- Success/failure tracking
- Performance metrics

## Workflow Management

Default workflow steps:
1. **Intake** - Initial case creation
2. **Pre-op Scan** - Scanning and data collection
3. **Planning** - Treatment planning
4. **Fabrication** - Creating guides/appliances
5. **QA Review** - Quality assurance
6. **Delivery** - Shipping to clinic
7. **Surgery** - Procedure date
8. **Follow-up** - Post-operative care
9. **Completed** - Case closed

## Security

### Implemented Security Features
- ✅ JWT authentication with expiration
- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ File type validation
- ✅ File size limits
- ✅ Role-based access control
- ✅ HTTPS enforcement (production)
- ✅ Input sanitization
- ✅ CORS configuration

### Best Practices
- Use strong passwords
- Keep `.env` file secure (chmod 600)
- Regular security updates
- Enable HTTPS
- Limit file upload sizes
- Regular database backups

## Cron Jobs

### Auto-Sync Cron

Add to crontab:
```bash
*/30 * * * * php /path/to/project/cron/sync.php >> /path/to/project/storage/logs/cron.log 2>&1
```

### Database Backup Cron

```bash
0 2 * * * /path/to/backup-db.sh
```

## Troubleshooting

### Common Issues

**Issue: Slack sync not working**
- Check SLACK_BOT_TOKEN in .env
- Verify app permissions
- Test connection: `GET /api/sync/test`

**Issue: Files not uploading**
- Check permissions: `chmod -R 777 public/uploads`
- Check PHP limits: `upload_max_filesize` and `post_max_size`

**Issue: 500 error**
- Check logs: `storage/logs/laravel.log`
- Verify .env configuration
- Check file permissions

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

### Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Roadmap

### Phase 2 Features (Future)
- [ ] Web → Slack bidirectional sync
- [ ] AI-based case classification
- [ ] STL 3D viewer (Three.js integration)
- [ ] DICOM viewer integration
- [ ] Advanced analytics and reporting
- [ ] Multi-tenant support
- [ ] Mobile app (React Native)
- [ ] Email notifications
- [ ] Slack notifications
- [ ] Export to PDF/Excel

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## License

Proprietary - All rights reserved

## Support

For support and questions:
- Email: support@creodent.com
- Documentation: See DEPLOYMENT.md
- Logs: Check `storage/logs/`

## Credits

Developed for Creodent AoX Team

Built with:
- Laravel Framework
- TailwindCSS
- Font Awesome
- Guzzle HTTP

## Changelog

### Version 1.0.0 (2024-01-01)
- Initial release
- Slack Canvas integration
- File management system
- Dashboard UI
- Auto-sync capabilities
- JWT authentication
- Complete CRUD operations
