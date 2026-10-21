# Campus Esports Ethiopia — Production Deployment Guide

This guide details the standard deployment process, server prerequisites, zero-downtime deployment pipelines, and disaster rollback strategies for the Campus Esports Ethiopia platform.

---

## 1. System Requirements & Architecture

- **Operating System:** Ubuntu 24.04 LTS or Debian 12 (bookworm)
- **Runtime:** PHP 8.3+ with required extensions (`bcmath`, `curl`, `mbstring`, `openssl`, `pdo_pgsql` / `pdo_mysql`, `redis`, `gd`, `intl`, `xml`, `zip`)
- **Process Manager:** PHP-FPM managed by `systemd` or Docker container
- **Web Server:** Nginx 1.24+ (Reverse proxy for PHP-FPM and Laravel Reverb WebSockets)
- **Primary Database:** PostgreSQL 16+ (or MySQL 8.0+) with connection pooling (e.g. PgBouncer)
- **In-Memory Cache & Queues:** Redis 7+
- **Real-Time WebSockets:** Laravel Reverb (port 8080 or reverse-proxied over WSS port 443)
- **Queue Supervisor:** Laravel Horizon running under `systemd` or Docker daemon
- **Object Storage:** AWS S3 or MinIO S3-compatible storage with CloudFront/CDN distribution

---

## 2. Environment Separation

Maintain strict isolation across environments:
1. **Local Development:** `APP_ENV=local`, `APP_DEBUG=true`, SQLite/PostgreSQL, Redis, Mailpit.
2. **Staging:** `APP_ENV=staging`, `APP_DEBUG=false`, staging databases. Keep `PAYMENTS_SANDBOX=true` only for local-style checkout stubs; do not treat that HMAC as a real PSP protocol.
3. **Production:** `APP_ENV=production`, `APP_DEBUG=false`, read/write PostgreSQL replica cluster. Keep `PAYMENTS_SANDBOX=false`. Live Telebirr, Chapa, M-Pesa, card, and bank settlement stay disabled until a provider-specific verifier is implemented.

---

## 3. Production Environment Configuration (`.env.production`)

Ensure the following variables are strictly set:

```ini
APP_NAME="Campus Esports Ethiopia"
APP_ENV=production
APP_KEY=base64:GENERATE_VIA_PHP_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://campusesports.et

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=10.0.0.5
DB_PORT=5432
DB_DATABASE=campus_esports_prod
DB_USERNAME=cee_prod_user
DB_PASSWORD=STRONG_PRODUCTION_SECRET

BROADCAST_CONNECTION=reverb
CACHE_STORE=redis
FILESYSTEM_DISK=s3
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.campusesports.et
SESSION_SECURE_COOKIE=true

REDIS_CLIENT=phpredis
REDIS_HOST=10.0.0.6
REDIS_PASSWORD=STRONG_REDIS_SECRET
REDIS_PORT=6379

REVERB_APP_ID=cee_live_prod
REVERB_APP_KEY=PROD_REVERB_KEY
REVERB_APP_SECRET=PROD_REVERB_SECRET
REVERB_HOST="campusesports.et"
REVERB_PORT=443
REVERB_SCHEME=https

# Payments: fail closed until a real provider verifier exists.
# Do not set PAYMENTS_SANDBOX=true in production. Merchant IDs and webhook
# secrets alone do not enable live Telebirr/Chapa/M-Pesa settlement.
PAYMENTS_SANDBOX=false
TELEBIRR_APP_ID=
TELEBIRR_APP_KEY=
TELEBIRR_SHORT_CODE=
TELEBIRR_PUBLIC_KEY=
TELEBIRR_WEBHOOK_SECRET=
CHAPA_SECRET_KEY=
CHAPA_WEBHOOK_SECRET=
MPESA_CONSUMER_KEY=
MPESA_CONSUMER_SECRET=
MPESA_WEBHOOK_SECRET=
```

---

## 4. Zero-Downtime Deployment Pipeline

Recommended deployment script using Git tags and symlink switching (e.g., Deployer or custom pipeline):

```bash
#!/usr/bin/env bash
set -e

RELEASE_DIR="/var/www/campusesports.et/releases/$(date +%Y%m%d%H%M%S)"
SHARED_DIR="/var/www/campusesports.et/shared"
CURRENT_DIR="/var/www/campusesports.et/current"

echo "Deploying Campus Esports Ethiopia to $RELEASE_DIR..."

# 1. Clone repository to release directory
git clone --depth 1 --branch main https://github.com/campus-esports/campus-esports-ethiopia.git "$RELEASE_DIR"
cd "$RELEASE_DIR/backend"

# 2. Link shared storage and production environment
ln -nfs "$SHARED_DIR/.env" "$RELEASE_DIR/backend/.env"
rm -rf "$RELEASE_DIR/backend/storage"
ln -nfs "$SHARED_DIR/storage" "$RELEASE_DIR/backend/storage"

# 3. Install backend dependencies (optimized for production)
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 4. Compile frontend assets
npm ci --prefer-offline --no-audit
npm run build

# 5. Run atomic database migrations
php artisan migrate --force

# 6. Optimize Laravel configurations and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Atomically switch symlink
ln -nfs "$RELEASE_DIR/backend" "$CURRENT_DIR"

# 8. Reload PHP-FPM and restart queue supervisor
sudo systemctl reload php8.3-fpm
php artisan horizon:terminate
php artisan reverb:restart

# 9. Health diagnostic check
php artisan cee:health-check

echo "Deployment completed successfully!"
```

Keep `PAYMENTS_SANDBOX=false` in production. Telebirr, Chapa, M-Pesa, card, and bank transfer do not settle payments until a provider-specific verifier exists. The Docker Compose `app` service sets `PAYMENTS_SANDBOX=true` for local development only; never copy that compose environment into a production host. Run `php artisan schedule:work` (or a system scheduler calling `php artisan schedule:run`) so expired suspensions and mutes are lifted. The release migrations add `users.suspended_until`, `users.muted_until`, `tickets.issuance_slot`, and `tournament_ranking_awards` (with a legacy `settings.ranking_ledger` backfill).

---

## 5. Rollback Strategy

If an unexpected regression or database failure occurs post-deployment:

### Automated Symlink Rollback
```bash
# 1. Point current symlink to previous release
PREVIOUS_RELEASE=$(ls -td /var/www/campusesports.et/releases/* | sed -n '2p')
ln -nfs "$PREVIOUS_RELEASE/backend" /var/www/campusesports.et/current

# 2. Reload services
sudo systemctl reload php8.3-fpm
/var/www/campusesports.et/current/artisan horizon:terminate
/var/www/campusesports.et/current/artisan reverb:restart
```

### Database Migration Rollback
If the issue was caused by a schema migration:
```bash
cd /var/www/campusesports.et/current
php artisan migrate:rollback --step=1 --force
```

### Disaster Point-in-Time Database Recovery
If database data corruption occurred:
```bash
# 1. Put platform in maintenance mode
php artisan down --secret="cee-emergency-key-2026"

# 2. Restore PostgreSQL dump from daily snapshot
psql -U cee_prod_user -d campus_esports_prod -f /backups/db/daily_snapshot_latest.sql

# 3. Re-cache and bring application back up
php artisan optimize
php artisan up
```
