# Creodent Anonymous Voice

An anonymous employee feedback portal that turns input into action—safely, fairly, and measurably.

## Features

### MVP Features
- **Anonymous Post Submission** - Submit feedback without revealing identity
- **Content Safety & Moderation** - Automatic profanity/PII filtering and human review
- **Categorization & Assignment** - Categories, labels, owner assignment, status tracking
- **Discussion & Official Response** - Threaded comments with official response badges
- **Voting & Sorting** - Upvote to surface impact; sort by Trending, New, Unresolved
- **Admin Dashboard** - Queue management, SLA timers, category heatmap
- **Weekly Digests** - Public summaries of feedback and resolutions

### Tech Stack
- **Backend**: PHP 8.2 (plain PHP with minimal dependencies)
- **Database**: MySQL 8
- **Frontend**: Tailwind CSS (via CDN), Alpine.js for interactivity
- **Auth**: Session-based with RBAC (moderator, owner, exec, viewer)

## Installation

### Requirements
- PHP 8.2+
- MySQL 8.0+
- Composer

### Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd creodent-voice
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file and configure:
```bash
cp .env.example .env
# Edit .env with your database credentials
```

4. Run migrations:
```bash
php app.php migrate
```

5. Seed admin user:
```bash
php app.php seed
```

6. Configure your web server to point to `/public` directory.

### Default Admin Credentials
- Email: `admin@creodent.com`
- Password: `ChangeMe!2025`

**Important**: Change the password immediately after first login!

## Usage

### CLI Commands

```bash
# Run database migrations
php app.php migrate

# Fresh migrate (drop all tables and re-migrate)
php app.php migrate:fresh

# Seed initial data
php app.php seed

# Generate weekly digest
php app.php digest:weekly
```

### Routes

#### Public
- `GET /` - Homepage
- `GET /submit` - Submit feedback form
- `GET /posts` - Browse approved feedback
- `GET /posts/{id}` - View feedback details
- `GET /digests` - Weekly summaries

#### API
- `POST /api/posts/{id}/votes` - Vote on post
- `POST /api/posts/{id}/comments` - Add anonymous comment

#### Admin (requires login)
- `GET /admin` - Dashboard
- `GET /admin/queue` - Moderation queue
- `GET /admin/posts/{id}` - Post details
- `GET /admin/users` - User management
- `GET /admin/export.csv` - Export data

## Configuration

### Environment Variables

```env
# Application
APP_ENV=production
APP_URL=https://voice.yourcompany.com
APP_DEBUG=false

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=creodent_voice
DB_USER=your_user
DB_PASS=your_password

# Session
SESSION_SECRET=your-64-char-random-string

# Rate Limiting
RATE_LIMIT_POSTS=5    # Posts per hour per IP
RATE_LIMIT_VOTES=10   # Votes per minute per IP
UPLOAD_MAX_SIZE=3145728  # 3MB

# Slack (optional)
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...

# SMTP (optional)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your_user
SMTP_PASS=your_password
SMTP_FROM=noreply@yourcompany.com
```

## User Roles

| Role | Permissions |
|------|-------------|
| Moderator | Full access: approve/reject posts, assign owners, manage users |
| Owner | Can be assigned items, update status, add comments |
| Executive | Dashboard access, view reports and exports |
| Viewer | Read-only access to admin area |

## Content Guidelines

### What to Submit
- Focus on behavior, processes, or policies
- Be specific about the impact
- Suggest possible solutions
- Keep it constructive and professional

### What NOT to Submit
- Names or identifying information
- Personal attacks
- Offensive or threatening language
- Rumors or unverified claims

## SLA Guidelines

- **First Response**: Within 5 business days
- **Status Update**: At least every 14 days until resolved
- **Weekly Summary**: Published every week

## Project Structure

```
/public
  index.php         # Front controller
  /assets           # CSS/JS assets
  /uploads          # User uploads
/src
  /Controllers      # Request handlers
  /Models           # Data models (future)
  /Middleware       # Router, auth
  /Services         # Business logic
  /Views            # PHP templates
/config
  database.php      # DB connection
  routes.php        # Route definitions
/database
  /migrations       # SQL migrations
/tests              # PHPUnit tests
```

## Security

- CSRF protection on all forms
- Rate limiting per IP
- Content filtering (profanity, PII)
- Prepared statements (SQL injection prevention)
- XSS prevention via output escaping
- Upload validation (type, size)
- Session security (httponly, secure flags)

## License

Proprietary - Internal Use Only
