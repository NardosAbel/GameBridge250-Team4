# Campus Esports Ethiopia - Final Production Launch Checklist

This document acts as the definitive gatekeeper and verification protocol for the public go-live deployment of **Campus Esports Ethiopia (CEE)**.

---

## 1. Executive Status & Launch Gates

| Gate | Category | Threshold | Status | Sign-off |
| :---: | :--- | :--- | :---: | :---: |
| **G1** | **Automated Tests** | 100% passing tests (138 tests, 600 assertions) | **PASS** | Lead Engineer |
| **G2** | **Security Audit** | OWASP Top 10 validated, no critical/high vulnerabilities | **PASS** | Security Lead |
| **G3** | **Health Diagnostics** | `php artisan cee:health-check` returns exit code 0 | **PASS** | DevOps Engineer |
| **G4** | **Payment Gateways** | Telebirr, Chapa, and M-Pesa webhooks verified & tested | **PASS** | FinTech Ops |
| **G5** | **Frontend Build** | Zero Vite build errors; WCAG 2.1 AA keyboard/contrast verified | **PASS** | Frontend Lead |

---

## 2. Pre-Flight Checklist (T-minus 48 Hours to T-minus 2 Hours)

### 2.1 Infrastructure & Environment Hardening
- [ ] **DNS & TLS:**
  - `A` / `AAAA` records pointed to production load balancer.
  - TLS 1.3 certificate installed and valid for `campusesports.et` and `api.campusesports.et`.
- [ ] **Environment Configuration (`.env`):**
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_KEY` securely generated and backed up.
  - `SESSION_SECURE_COOKIE=true` and `SESSION_HTTP_ONLY=true`.
  - `DEBUGBAR_ENABLED=false` and `TELESCOPE_ENABLED=false`.
- [ ] **Database & Performance Optimization:**
  - All migrations executed (`php artisan migrate --force`).
  - Production performance composite indexes verified (`2026_10_15_160000_add_production_performance_indexes_for_phase7.php`).
  - Redis cache and session stores responding with `< 2ms` latency.
  - Base lookup tables seeded (Universities, Game Titles, Core Badges, System Roles).

### 2.2 Payment Gateway Configurations
- [ ] **Telebirr Integration:**
  - Production App ID, App Key, and RSA Private/Public keys verified.
  - Return URL: `https://campusesports.et/payments/callback/telebirr`
  - Webhook URL: `https://campusesports.et/api/v1/payments/webhook/telebirr`
- [ ] **Chapa Integration:**
  - Live Secret Key (`CHASECK_LIVE-...`) and Public Key configured.
  - Webhook URL: `https://campusesports.et/api/v1/payments/webhook/chapa`
- [ ] **M-Pesa Integration:**
  - Production Consumer Key, Secret, Shortcode, and Passkey configured.
  - Webhook URL: `https://campusesports.et/api/v1/payments/webhook/mpesa`

### 2.3 Daemon & Worker Processes
- [ ] **Supervisor Queue Workers:**
  - Minimum 4 worker processes active (`cee-worker:cee-worker_00` through `03`).
  - Worker health verified via `php artisan queue:monitor redis:default`.
- [ ] **Laravel Reverb WebSocket Server:**
  - Daemon listening on port `8080`.
  - Reverse proxy configured with WebSocket upgrade headers (`Upgrade $http_upgrade`, `Connection "Upgrade"`).

---

## 3. Go-Live Protocol (Hour of Cutover)

```bash
# 1. Engage maintenance banner with bypass secret
php artisan down --secret="cee-launch-pass-2026" --render="errors::503"

# 2. Pull final production release tag
git fetch --tags
git checkout tags/v1.0.0-phase7

# 3. Install optimized production composer dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Build optimized frontend assets
npm ci
npm run build

# 5. Run any pending database migrations
php artisan migrate --force

# 6. Clear and compile all Laravel caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Restart background queues and WebSocket daemons
supervisorctl restart cee-worker:*
supervisorctl restart cee-reverb:*

# 8. Run system health check
php artisan cee:health-check

# 9. Perform smoke test (using bypass secret in browser URL)
# https://campusesports.et/cee-launch-pass-2026

# 10. Disable maintenance mode and go live!
php artisan up
```

---

## 4. Post-Launch Smoke Test Checklist

Test each core flow directly in production immediately after taking the platform out of maintenance mode:
- [ ] **Auth Flow:** Register test student account, verify SMS/OTP token, confirm session initiation.
- [ ] **Team Flow:** Create team, invite a teammate, accept invitation, verify roster view.
- [ ] **Tournament Engine:** Register for test tournament, ready-up check-in, verify bracket display.
- [ ] **Ticketing & Payments:** Purchase test ticket via Chapa test mode, verify receipt, render signed QR badge.
- [ ] **Gate Scan:** Scan QR badge via venue scanner interface; verify success; scan second time and confirm `HTTP 409 Conflict` duplicate rejection.
- [ ] **Learning Hub:** Enroll in digital literacy course, complete lesson, check streak update.

---

## 5. Post-Launch Monitoring (First 72 Hours)

1. **Uptime Monitoring:** External probe alerting team on Slack/Telegram if error rate exceeds 0.5%.
2. **Slow Query Tracking:** Check `/var/log/mysql/mysql-slow.log` every 6 hours.
3. **Queue Monitoring:** Confirm zero backlog in `redis:default` and `redis:notifications`.
4. **Dispute & Support Desk:** Dedicated channel monitoring live tournament disputes and referee alerts.
