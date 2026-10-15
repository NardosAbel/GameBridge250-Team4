# Campus Esports Ethiopia - Operations Runbook

This operations runbook provides actionable procedures and operating guidelines for maintaining, monitoring, and troubleshooting the **Campus Esports Ethiopia (CEE)** production infrastructure.

---

## 1. System Overview & Service Topology

| Component | Technology | Primary Function | Failure Impact |
| :--- | :--- | :--- | :--- |
| **Web Server / Application** | PHP 8.2+ / Laravel 11 / Nginx | Core REST API and Inertia SSR/SPA | System inaccessible (HTTP 502/500) |
| **Database** | MySQL 8.0+ / PostgreSQL 15+ | Relational persistence | Data read/write freeze |
| **Cache & Session** | Redis 7.0+ | Key-value store, rankings cache, locks | Degraded API latency, session drop |
| **Queue Workers** | Laravel Queue / Redis / Supervisor | Async jobs (media, notifications, emails) | Delayed notifications, background processing backlog |
| **Realtime WebSockets** | Laravel Reverb / Pusher | Live match brackets, chat, announcements | Brackets require manual refresh |
| **Object Storage** | S3 / MinIO / Cloudflare R2 | Avatars, banners, evidence, certificates | Media upload failures, missing assets |

---

## 2. Health Monitoring & Diagnostics

### 2.1 Automated Health Check Command
Run the built-in diagnostic CLI command on demand or schedule via cron:
```bash
php artisan cee:health-check
```
**Exit Codes:**
- `0` (Success): Database, Redis cache, and storage disks are operational; debug mode is disabled.
- `1` (Failure): At least one critical subsystem has degraded or failed.

### 2.2 HTTP Health Endpoint
The health route is configured at `/api/v1/health` (or `/up` in Laravel 11).
- **Normal Response:** `HTTP 200 OK` with JSON payload `{"status":"ok","timestamp":"..."}`
- **Synthetic Monitoring:** Configure external uptime probes (e.g., UptimeKuma, Datadog, BetterUptime) with 30-second polling against `/up` or `/api/v1/health`.

---

## 3. Queue & Worker Management

Background workers handle critical asynchronous tasks: media encoding, certificate generation, payment webhook reconciliation, and transactional emails.

### 3.1 Supervisor Configuration (`/etc/supervisor/conf.d/cee-worker.conf`)
```ini
[program:cee-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cee/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/cee-worker.log
```

### 3.2 Handling Failed Queue Jobs
1. **List failed jobs:**
   ```bash
   php artisan queue:failed
   ```
2. **Inspect payload and stack trace:**
   ```bash
   php artisan queue:failed:table
   ```
3. **Retry failed jobs:**
   ```bash
   # Retry specific UUID
   php artisan queue:retry <UUID>

   # Retry all failed jobs
   php artisan queue:retry all
   ```
4. **Purge dead jobs:**
   ```bash
   php artisan queue:forget <UUID>
   # or
   php artisan queue:flush
   ```

---

## 4. Real-time WebSocket Service (Laravel Reverb)

Live tournament brackets, gate scan syncs, and match chat rely on WebSockets.

### 4.1 Reverb Daemon Configuration (`/etc/supervisor/conf.d/cee-reverb.conf`)
```ini
[program:cee-reverb]
process_name=%(program_name)s
command=php /var/www/cee/backend/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/cee-reverb.log
```

### 4.2 Verifying Reverb Health
```bash
curl -i http://127.0.0.1:8080/health
# Check active connections
netstat -tulpn | grep 8080
```

---

## 5. Database Administration: Backups & Slow Queries

### 5.1 Automated Daily Backup Script (`/usr/local/bin/cee-db-backup.sh`)
```bash
#!/usr/bin/env bash
set -euo pipefail

BACKUP_DIR="/var/backups/cee/mysql"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="cee_prod_${TIMESTAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"
mysqldump --single-transaction --quick --routines --triggers -u cee_user -p"$DB_PASSWORD" cee_production | gzip > "${BACKUP_DIR}/${FILENAME}"

# Retain local backups for 14 days
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +14 -delete

# Sync to encrypted offsite S3 bucket
aws s3 cp "${BACKUP_DIR}/${FILENAME}" s3://cee-db-backups-secure/daily/
```

### 5.2 Database Restore Procedure
```bash
# 1. Place application in maintenance mode
php artisan down --secret="cee-restore-bypass"

# 2. Decompress and restore database
gunzip < /var/backups/cee/mysql/cee_prod_YYYYMMDD_HHMMSS.sql.gz | mysql -u cee_user -p cee_production

# 3. Run pending migrations
php artisan migrate --force

# 4. Clear and warm application caches
php artisan optimize:clear
php artisan optimize

# 5. Bring application back online
php artisan up
```

### 5.3 Slow Query Profiling
Enable MySQL slow query log in `/etc/mysql/my.cnf`:
```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 0.5
log_queries_not_using_indexes = 1
```
Analyze slow queries with `mysqldumpslow`:
```bash
mysqldumpslow -s t -t 10 /var/log/mysql/mysql-slow.log
```

---

## 6. Payment Webhook Recovery & Reconciliation

Campus Esports Ethiopia integrates with **Telebirr**, **Chapa**, and **M-Pesa**.

### 6.1 Diagnostic Workflow for Orphaned Payments
If a user reports completed payment but unissued tickets/registrations:
1. Check webhook signature verification logs:
   ```bash
   grep -i "webhook" /var/www/cee/backend/storage/logs/laravel.log | grep -E "Telebirr|Chapa|Mpesa"
   ```
2. Check `payments` table for matching reference / transaction ID:
   ```sql
   SELECT id, user_id, amount, currency, status, provider, provider_reference, updated_at 
   FROM payments 
   WHERE provider_reference = 'CHAPA_TX_REF_HERE' OR reference = 'CEE-PAY-...';
   ```
3. Check `tickets` associated with transaction:
   ```sql
   SELECT id, ticket_type_id, user_id, status, qr_token FROM tickets WHERE payment_id = <PAYMENT_ID>;
   ```
4. If payment is `pending` but gateway confirmed `success`, trigger reconciliation:
   ```bash
   php artisan cee:reconcile-payment --reference="CEE-PAY-..."
   ```

---

## 7. Gate Scanner & Offline Sync Troubleshooting

During on-site LAN and arena events, intermittent mobile networks may occur.

### 7.1 Offline Scan Queuing
- Venue staff scanners record ticket validation attempts in IndexedDB on their mobile client (`status: queued`).
- Once connectivity is restored, the client posts batches to `/api/v1/tickets/sync-scans`.

### 7.2 Conflict & Duplicate Rejection
- If the backend receives an already-scanned ticket, it returns `HTTP 409 Conflict` with `status: duplicate`.
- Staff scanner interface displays a prominent **RED ALERT** with timestamp and scanner station name of original scan.
- If a badge fails QR signature verification, the backend returns `HTTP 422 Unprocessable Entity` (`status: invalid_signature`). Do not admit user without supervisor escalation.

---

## 8. High-Traffic Peak Preparation (Load Testing)

Ahead of major national esports tournaments:

### 8.1 Artillery / k6 Load Testing Script (`tests/load/k6-bracket-load.js`)
```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '2m', target: 500 },  // Ramp up to 500 concurrent users
    { duration: '5m', target: 2000 }, // Peak registration/bracket browsing
    { duration: '2m', target: 0 },    // Ramp down
  ],
};

export default function () {
  const res = http.get('https://campusesports.et/api/v1/tournaments/1/bracket');
  check(res, { 'status is 200': (r) => r.status === 200 });
  sleep(1);
}
```

### 8.2 Pre-Tournament Checklist
1. Pre-warm Redis cache for tournament details, bracket trees, and leaderboards.
2. Scale Redis worker processes from 4 to 12.
3. Verify rate limit thresholds in `routes/api.php` accommodate expected gate traffic.
4. Scale Nginx worker connections and PHP-FPM pool max children:
   ```ini
   pm = dynamic
   pm.max_children = 64
   pm.start_servers = 16
   pm.min_spare_servers = 8
   pm.max_spare_servers = 24
   ```

---

## 9. Incident Response Protocol

### Severity Levels
- **SEV-1 (Critical Outage):** Core platform down, tournament engine paralyzed, or payment processing failure during live event.
- **SEV-2 (High Impact):** Ticket scanner offline across an entire venue; WebSocket live updates failing.
- **SEV-3 (Medium Impact):** Background notifications delayed; course PDF downloads slow.
- **SEV-4 (Low Impact):** Minor cosmetic glitch, non-blocking UI anomaly.

### Incident Commander Checklist
1. **Acknowledge:** Declare incident in operations channel within 5 minutes (SEV-1/2).
2. **Mitigate:** Apply rollbacks or maintenance mode if data integrity is at risk.
3. **Communicate:** Post platform status banner for players and organizers.
4. **Resolve:** Deploy fix and verify through `php artisan cee:health-check`.
5. **Post-Mortem:** Complete incident review document within 48 hours.
