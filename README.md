# CAD/CAM Real-Time Web Service

A production-ready PHP (Laravel) + MySQL + Redis + WebSockets web application for dental lab operations management with Tailwind CSS frontend.

## Features

- **Real-time task queues** per worker (auto-filtered), with one-click Complete → advance to next stage + initials saved
- **Per-tooth tracking** for SOLIDEX with separate store + case-level completion roll-up
- **Strong concurrency**: optimistic locking + WebSockets/SSE live refresh
- **Unified NOTES** with tag chips (e.g., [3D PRINT] [CR]) across departments
- **Role views**: Super Admin (all), Department Lead (dept scope), Worker (my tasks)
- **Windows intake app** → secure API ingestion → Google Sheets sync → DB source of truth

## System Requirements

- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- Composer 2.x
- Node.js 18+ (for Cypress tests)

## Installation

1. Clone the repository
2. Copy `.env.example` to `.env` and configure:
   ```bash
   cp .env.example .env
   ```

3. Install dependencies:
   ```bash
   composer install
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Generate JWT secret:
   ```bash
   php artisan jwt:secret
   ```

6. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

## Default Users

| Email | Password | Role | Department |
|-------|----------|------|------------|
| admin@cadcam.local | password123 | ADMIN | MULTI |
| cocr.lead@cadcam.local | password123 | LEAD | COCR |
| john.smith@cadcam.local | password123 | WORKER | COCR |
| solidex.lead@cadcam.local | password123 | LEAD | SOLIDEX |
| jane.doe@cadcam.local | password123 | WORKER | SOLIDEX |
| print.lead@cadcam.local | password123 | LEAD | PRINT |
| mike.johnson@cadcam.local | password123 | WORKER | PRINT |

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login with email/password
- `GET /api/me` - Get current user profile
- `POST /api/auth/logout` - Logout
- `POST /api/auth/refresh` - Refresh JWT token

### Cases
- `POST /api/intake` - Create new case (from Windows app)
- `GET /api/cases` - List cases with filters
- `GET /api/cases/{id}` - Get case details
- `PATCH /api/cases/{id}` - Update case (with optimistic locking)

### COCR
- `GET /api/cocr/{caseId}/meta` - Get COCR metadata
- `PATCH /api/cocr/{caseId}/meta` - Update COCR metadata
- `GET /api/cocr/{caseId}/stages` - Get COCR stages
- `POST /api/cocr/stages/{stageId}/complete` - Complete stage

### SOLIDEX
- `GET /api/solidex/{caseId}/meta` - Get SOLIDEX metadata
- `GET /api/solidex/{caseId}/teeth` - Get teeth with stages
- `POST /api/solidex/tooth/stages/{stageId}/complete` - Complete tooth stage
- `GET /api/solidex/{caseId}/isComplete` - Check if case complete

### 3D Print
- `GET /api/print/{caseId}/meta` - Get print metadata
- `GET /api/print/{caseId}/stages` - Get print stages
- `POST /api/print/stages/{stageId}/complete` - Complete stage

### Worker Tasks
- `GET /api/my/tasks` - Get tasks assigned to current user

## Stage Workflow

### COCR
TRANS → DESIGN → CAM → CNC (machine required) → OVENS (oven required) → QC

### SOLIDEX (per tooth)
TRANSCAN → PRECAD → CAD → PRECAM (subtasks) → CNC (machine required) → QC

### 3D Print
TRANS → PREP → DESIGN → NESTING (printer required) → PRINT (printer required) → POST → QC

## Optimistic Locking

All mutable records include a `version` field. When updating:

1. Send the current version in `If-Match-Version` header or `version` field
2. Server validates version matches current record
3. If match: update succeeds, version increments
4. If mismatch: returns `409 Conflict` with current version

## Real-time Updates

The system supports real-time updates via:
- **SSE (Server-Sent Events)**: Primary fallback, available at `/api/stream`
- **WebSockets**: For higher performance (requires Redis pub/sub)

## Running Tests

```bash
# Unit tests
php artisan test

# Cypress E2E tests
npx cypress run --config-file tests/E2E/cypress.config.js
```

## Deployment (Hostinger)

1. Configure PHP 8.2+ with required extensions
2. Set up MySQL 8 database
3. Configure Redis for caching and pub/sub
4. Set environment variables in Hostinger panel
5. Configure Supervisor for queue workers
6. Set up NGINX/OpenLiteSpeed with SSL

## Project Structure

```
├── app/
│   ├── Events/           # Broadcast events
│   ├── Exceptions/       # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/  # API controllers
│   │   ├── Middleware/   # Auth & role middleware
│   │   └── Requests/     # Form requests
│   ├── Jobs/             # Queue jobs (Google Sync)
│   ├── Models/           # Eloquent models
│   ├── Providers/        # Service providers
│   ├── Services/         # Business logic
│   └── Traits/           # Reusable traits
├── config/               # Configuration files
├── database/
│   ├── migrations/       # Database migrations
│   └── seeders/          # Database seeders
├── resources/views/      # Blade templates
├── routes/               # Route definitions
└── tests/E2E/            # Cypress E2E tests
```

## License

Proprietary - All rights reserved.
