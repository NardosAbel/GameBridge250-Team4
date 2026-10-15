# Campus Esports Ethiopia

[![CI](https://github.com/campus-esports/campus-esports-ethiopia/actions/workflows/ci.yml/badge.svg)](https://github.com/campus-esports/campus-esports-ethiopia/actions)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12%2F13-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-cyan.svg)](https://react.dev)
[![Inertia.js](https://img.shields.io/badge/Inertia-2.0-purple.svg)](https://inertiajs.com)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.8-blue.svg)](https://www.typescriptlang.org)
[![Pest](https://img.shields.io/badge/Tests-Pest-green.svg)](https://pestphp.com)

An integrated gaming, esports, and digital-awareness platform for Ethiopian university students, teams, gaming zones, tournament organizers, and sponsors.

---

## Architecture Overview

Built as a **Modular Monolith** adhering to [.cursor/rules/project.mdc](file:///.cursor/rules/project.mdc) and specifications in `/docs`:

campus-esports-ethiopia/
├── backend/                       # Laravel backend modular application
│   ├── app/
│   │   ├── Domain/                # Domain-Driven Modular Monolith
│   │   │   ├── Identity/          # Authentication, OTP, RBAC, Audit Logging
│   │   │   ├── Profiles/          # Player profiles, Games, Verification, Privacy
│   │   │   ├── Social/            # Feed, Posts, Media, Comments, Reactions, Shares, Bookmarks
│   │   │   ├── Teams/             # Rosters, Roles, Transfers, Invitations, Roster History
│   │   │   ├── Communities/       # Campus clubs, City hubs, Approval flows, Moderation
│   │   │   ├── Recruitment/       # LFT / LFP notice board with multi-attribute filtering
│   │   │   ├── Chat/              # Direct, Team, and Community messaging with @mentions
│   │   │   ├── Notifications/     # Multi-channel notification pipeline (In-app, push, email, SMS)
│   │   │   ├── Moderation/        # Reports, Sanctions (warn, mute, suspend, ban), Audit logging
│   │   │   ├── Tournaments/       # Deterministic Brackets, Match Rooms, Overrides, Disputes, Anti-Fraud, Rankings (Phase 3)
│   │   │   ├── Venues/            # Gaming lounges, LAN arenas, Haversine discovery, Reviews, Staff, Analytics (Phase 4)
│   │   │   ├── Ticketing/         # Inventory row-locking, HMAC-SHA256 signed QR entry, Gate scanners, Offline sync (Phase 4)
│   │   │   ├── Payments/          # Telebirr, Chapa, M-Pesa, Card, Bank, Idempotent webhooks, Refunds, Payouts (Phase 4)
│   │   │   ├── Education/         # 11 Digital Awareness Categories, Bilingual EN/AM, Quizzes, Certificates, Offline Bundles, University Paths, Campaigns (Phase 5)
│   │   │   ├── Sponsorship/       # Verified Brands, Packages, Deliverable Tracking, ROI Reports, Prize-Pool Funding (Phase 6)
│   │   │   └── Administration/    # Executive Platform Analytics, Creator Insights, Feature Flags, Health Monitor (Phase 6)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   # REST API Controllers (v1)
│   │   │   ├── Controllers/Web/   # Inertia Web Controllers (Feed, Teams, Communities, Tournaments, Rankings, Venues, Tickets, Scanner, Reports, Education, Certificates)
│   │   │   ├── Requests/          # Form Request Validators with Policy authorizations
│   │   │   ├── Resources/         # Standardized API Resources (Room credentials privacy gated, Signed QR tokens, Sanitized quiz questions)
│   │   │   └── Middleware/        # Inertia, RBAC, Rate-limiters, SecurityHeaders (HSTS, CSP)
│   │├── database/
│   │   ├── migrations/            # 41 Core Domain & Performance Index Migrations (PostgreSQL/MySQL/SQLite)
│   │   └── seeders/               # 9 Roles, Admin, Organizer, Player, Core Games, Badges & Awareness Courses
│   ├── resources/
│   │   ├── js/                    # React 19 + Inertia + TypeScript Frontend
│   │   │   ├── components/ui/     # Accessible base component library (WCAG 2.1 AA)
│   │   │   ├── layouts/           # Responsive AppLayout with Venues, Tickets, QR Gate Scanner, Learning Hub
│   │   │   ├── pages/             # Venues, Tickets, Gate Scanner, Education (Catalog, Course Show, Lesson Viewer, Quiz Player, Dashboard, Certificate Verification, Campaigns)
│   │   │   ├── hooks/             # useTranslation (EN/AM), useTheme (Dark/Light)
│   │   │   └── i18n/              # English (`en.json`) and Amharic (`am.json`)
│   └── tests/                     # 138 Pest Feature & Unit Tests (600 Assertions, 100% Green)
├── docs/                          # Architecture, Deployment, Runbooks, Checklists & Schemas
├── docker/                        # Multi-stage Dockerfile and Nginx configuration
├── docker-compose.yml             # Full-stack Docker Compose environment
└── .github/workflows/ci.yml       # GitHub Actions CI for Pint, Pest, TypeScript, Vite, Docker
```

---

## Technology Stack

- **Backend:** Laravel 12+ / 13 on PHP 8.3+
- **Frontend:** React 19 + Inertia.js 2.x + TypeScript + Tailwind CSS 4.x
- **Database:** PostgreSQL 16 (preferred in production; schema compatible with MySQL 8.x and SQLite)
- **Cache, Queues, Pub/Sub:** Redis 7
- **Real-Time WebSockets:** Laravel Reverb
- **Queue Management:** Laravel Horizon
- **API Authentication:** Laravel Sanctum
- **Role-Based Access Control (RBAC):** Spatie Laravel Permission (9 distinct roles)
- **Testing:** Pest Testing Framework
- **Code Style:** Laravel Pint
- **CI/CD:** GitHub Actions

---

## Quickstart & Local Setup

### 1. Prerequisites
- PHP 8.3+
- Composer 2.x
- Node.js 20+ and npm
- PostgreSQL or SQLite / MySQL
- Redis

### 2. Installation
```bash
# Clone the repository
git clone https://github.com/campus-esports/campus-esports-ethiopia.git
cd campus-esports-ethiopia

# Set up backend environment
cd backend
cp .env.example .env
php artisan key:generate

# Install dependencies
composer install
npm install

# Run database migrations and seed default data
php artisan migrate --seed

# Build frontend assets
npm run build
```

### 3. Launch Development Servers
You can run the full development stack using:
```bash
# In backend/ terminal 1:
php artisan serve

# In backend/ terminal 2:
npm run dev

# (Optional) Reverb WebSockets:
php artisan reverb:start

# (Optional) Horizon Queues:
php artisan horizon
```

---

## Docker Compose Setup

Run the entire platform including PostgreSQL, Redis, Mailpit, Reverb, and Horizon with one command:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

Services exposed:
- **Web Application / API:** http://localhost:8000
- **Vite Dev Server:** http://localhost:5173
- **Laravel Reverb WebSockets:** ws://localhost:8080
- **Mailpit Web Interface:** http://localhost:8025
- **PostgreSQL:** port 5432
- **Redis:** port 6379

---

## Testing & Code Quality

Run tests and verification tools:

```bash
cd backend

# Run full Pest test suite
vendor/bin/pest

# Check code style with Laravel Pint
vendor/bin/pint --test

# Fix code style automatically
vendor/bin/pint

# Run TypeScript type check
npx tsc --noEmit

# Production Vite build
npm run build
```

---

## Default Seeded Accounts

| Role | Email | Password | Details |
|---|---|---|---|
| **Super Admin** | `admin@campusesports.et` | `Password123!` | Full administrative privileges, audit log access |
| **Organizer** | `organizer@campusesports.et` | `Password123!` | Tournament creation and bracket management |
| **Player** | `player@campusesports.et` | `Password123!` | Competitive gamer tag `HabeshaSniper` (AAU) |

---

## Production Readiness & Operations (Phase 7)

### Diagnostics & Health Check
Verify database connectivity, Redis cache, filesystem permissions, and debug flags:
```bash
cd backend
php artisan cee:health-check
```

### Production Hardening & Optimization
```bash
# Run security and performance composite index migrations
php artisan migrate --force

# Warm configuration, route, view, and event caches
php artisan optimize

# Restart queues and Reverb WebSocket daemons
supervisorctl restart cee-worker:*
supervisorctl restart cee-reverb:*
```

---

## Production Guides & Checklists

Comprehensive production documentation is available in the [`docs/`](file:///c:/laragon/www/campus-esports-ethiopia/docs) directory:

1. [**DEPLOYMENT_GUIDE.md**](file:///c:/laragon/www/campus-esports-ethiopia/docs/DEPLOYMENT_GUIDE.md): Architecture specs, zero-downtime deployment script, system requirements, and rollbacks.
2. [**OPERATIONS_RUNBOOK.md**](file:///c:/laragon/www/campus-esports-ethiopia/docs/OPERATIONS_RUNBOOK.md): Monitoring, Horizon queues, Reverb daemons, backups, payment reconciliation, offline sync, and load testing.
3. [**SECURITY_CHECKLIST.md**](file:///c:/laragon/www/campus-esports-ethiopia/docs/SECURITY_CHECKLIST.md): HSTS, CSP, RBAC, rate-limiting, signed QR verification, and OWASP Top 10 matrix.
4. [**ACCESSIBILITY_CHECKLIST.md**](file:///c:/laragon/www/campus-esports-ethiopia/docs/ACCESSIBILITY_CHECKLIST.md): WCAG 2.1 AA audits, skip-to-content, keyboard focus rings, color contrast, and bilingual support.
5. [**FINAL_LAUNCH_CHECKLIST.md**](file:///c:/laragon/www/campus-esports-ethiopia/docs/FINAL_LAUNCH_CHECKLIST.md): Pre-flight verification, payment gateway credentials, cutover protocol, and smoke tests.
6. [**API_DOCUMENTATION.md**](file:///c:/laragon/www/campus-esports-ethiopia/docs/API_DOCUMENTATION.md): REST API v1 reference including security headers and rate limits.
7. [**DATABASE_SCHEMA.md**](file:///c:/laragon/www/campus-esports-ethiopia/docs/DATABASE_SCHEMA.md): Complete relational database schema with composite performance indexes.

---

## License

Proprietary - Campus Esports Ethiopia. All rights reserved.
