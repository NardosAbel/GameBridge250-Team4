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

```text
campus-esports-ethiopia/
├── backend/                       # Laravel backend modular application
│   ├── app/
│   │   ├── Domain/                # Domain-Driven Modular Monolith
│   │   │   ├── Identity/          # Authentication, OTP, RBAC, Audit Logging
│   │   │   ├── Profiles/          # Player profiles, Games, Verification
│   │   │   ├── Social/            # Feed, Posts, Media, Comments, Reactions
│   │   │   ├── Teams/             # Rosters, Roles, Transfers, Invitations
│   │   │   ├── Communities/       # Campus clubs, City hubs, Moderation
│   │   │   ├── Tournaments/       # Brackets, Matches, Results, Disputes, Rankings
│   │   │   ├── Ticketing/         # Orders, Offline-ready Signed QR Tokens, Scanners
│   │   │   ├── Payments/          # Telebirr, Chapa, M-Pesa abstractions
│   │   │   ├── Venues/            # Gaming zones, Facilities, Check-ins
│   │   │   ├── Education/         # Digital literacy courses, Quizzes, Certificates
│   │   │   ├── Sponsorship/       # Packages, Deliverables, Brand reports
│   │   │   └── Moderation/        # Reports, Sanctions, Trust & Safety
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   # Thin API Controllers (v1)
│   │   │   ├── Controllers/Web/   # Inertia Web Controllers
│   │   │   ├── Requests/          # Form Request Validators
│   │   │   ├── Resources/         # Standardized API Resources
│   │   │   └── Middleware/        # Inertia, RBAC, Rate-limiters
│   ├── database/
│   │   ├── migrations/            # 29 Core Domain Migrations (PostgreSQL/MySQL/SQLite)
│   │   └── seeders/               # 9 Roles, Admin, Organizer, Player, Core Games
│   ├── resources/
│   │   ├── js/                    # React 19 + Inertia + TypeScript Frontend
│   │   │   ├── components/ui/     # Accessible base component library (WCAG 2.1 AA)
│   │   │   ├── layouts/           # Responsive AppLayout with Header, Footer, Flash alerts
│   │   │   ├── pages/             # Landing, Register, Login, VerifyOtp, ResetPassword, Profile, Dashboard
│   │   │   ├── hooks/             # useTranslation (EN/AM), useTheme (Dark/Light)
│   │   │   └── i18n/              # English (`en.json`) and Amharic (`am.json`)
│   └── tests/                     # Pest Feature & Unit Test Suite
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

## License

Proprietary - Campus Esports Ethiopia. All rights reserved.
