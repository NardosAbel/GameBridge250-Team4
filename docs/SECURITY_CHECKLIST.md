# Campus Esports Ethiopia - Security Hardening Checklist

This document details the security architecture, threat mitigation strategies, and pre-launch compliance checks implemented across **Campus Esports Ethiopia (CEE)** in alignment with **OWASP Top 10 (2021)** and financial-grade security standards.

---

## 1. Network & Transport Security

- [x] **Strict Transport Security (HSTS):** Enforced via `SecurityHeadersMiddleware.php` with `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`.
- [x] **TLS Encryption:** Minimum TLS 1.3 required on production Nginx configurations. Deprecated TLS 1.0/1.1 and insecure ciphers disabled.
- [x] **Encrypted Cookies:** All session cookies configure `Secure`, `HttpOnly`, and `SameSite=Lax` (or `Strict` for administrative portals).

---

## 2. HTTP Security Headers

Registered globally in `bootstrap/app.php` via `App\Http\Middleware\SecurityHeadersMiddleware`:

| Header | Production Setting | Purpose |
| :--- | :--- | :--- |
| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' wss: https:;` | Mitigates Cross-Site Scripting (XSS) and unauthorized data exfiltration. |
| `X-Frame-Options` | `DENY` | Eliminates Clickjacking vulnerabilities by forbidding iframe embedding. |
| `X-Content-Type-Options` | `nosniff` | Blocks MIME-type sniffing attacks. |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Prevents sensitive query parameters leaking to external origins. |
| `Permissions-Policy` | `camera=(self), microphone=(), geolocation=(), payment=(self)` | Restricts browser hardware access to authorized features only. |

---

## 3. Authentication & RBAC Authorization

- [x] **Multi-Tier RBAC:** Strict authorization matrix enforcing roles:
  - `student`, `team_captain`, `organizer`, `venue_admin`, `scanner`, `sponsor`, `referee`, `system_admin`.
- [x] **Policy Enforcement:** All mutation controllers inherit from Laravel Policies:
  - `TournamentPolicy`, `TeamPolicy`, `MatchPolicy`, `TicketPolicy`, `VenuePolicy`, `SponsorshipPolicy`.
- [x] **Mass Assignment Prevention:** Every Eloquent model strictly defines `$fillable` with whitelisted attributes. `$guarded = []` is strictly forbidden.
- [x] **IDOR (Insecure Direct Object Reference) Protection:**
  - Resource access queries are scoped by the authenticated user's organization or team ID.
  - Team invitations and transfers require explicit recipient confirmation.
- [x] **Rate Limiting:**
  - Login & OTP verification: 5 requests / minute (`throttle:5,1`).
  - Ticket checkout: 30 requests / minute (`throttle:30,1`).
  - Gate QR scanning: 120 requests / minute (`throttle:120,1`).
  - Offline scan synchronization: 60 batches / minute (`throttle:60,1`).
  - Payment initiation: 30 requests / minute (`throttle:30,1`).
  - Payment webhooks: 100 requests / minute (`throttle:100,1`).

---

## 4. Payment & Gate Verification Security

- [x] **Webhook Signature Verification:**
  - Telebirr, Chapa, and M-Pesa webhooks verify cryptographic signatures (HMAC-SHA256) before payload parsing.
  - Unauthenticated requests trigger `HTTP 401 Unauthorized` or `HTTP 403 Forbidden`.
- [x] **Idempotency & Replay Attack Protection:**
  - Webhook transactions record unique provider transaction IDs.
  - Duplicate callbacks are acknowledged idempotently without double-crediting tickets or wallets.
- [x] **Cryptographically Signed QR Tokens (`QrTokenService`):**
  - QR codes contain only a ticket reference UUID and a HMAC-SHA256 signature:
    ```json
    {
      "ref": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
      "sig": "3a7b5...sha256_hash..."
    }
    ```
  - **No personal identification information (PII), phone numbers, or credit card details are ever encoded into QR payloads.**
- [x] **Atomic Gate Entry & Double-Scan Prevention:**
  - Ticket scans employ atomic database locks (`check_in_at` timestamps).
  - Attempted secondary scans trigger immediate `HTTP 409 Conflict` (`duplicate_scan`), flagging the entry station console.

---

## 5. OWASP Top 10 (2021) Compliance Matrix

| Vulnerability Category | Mitigation Status | Implemented Controls |
| :--- | :--- | :--- |
| **A01: Broken Access Control** | **PASS** | Laravel policies on all CRUD operations; route-level RBAC middleware; scoped database queries. |
| **A02: Cryptographic Failures** | **PASS** | TLS 1.3 only; bcrypt password hashing (cost 12); HMAC-SHA256 signed QR badges; AES-256 for payment provider credentials. |
| **A03: Injection** | **PASS** | Parameterized PDO statements via Eloquent; strict input validation via Form Requests; Blade auto-escaping. |
| **A04: Insecure Design** | **PASS** | State machine guards for tournament brackets, match disputes, and ticket life cycles. |
| **A05: Security Misconfiguration** | **PASS** | `APP_DEBUG=false` enforced in staging/prod; Telescope/Debugbar disabled; comprehensive security headers. |
| **A06: Vulnerable & Outdated Components** | **PASS** | Audited composer dependencies (`composer audit`) and npm packages (`npm audit`). |
| **A07: Identification & Auth Failures** | **PASS** | OTP throttling; multi-factor capability; Sanctum token invalidation on logout. |
| **A08: Software & Data Integrity Failures** | **PASS** | Webhook signature verification; signed release artifacts; checksum checks on file uploads. |
| **A09: Security Logging & Monitoring Failures** | **PASS** | Monolog logging for authentication failures, dispute initiations, and gate anomalies; health monitoring via `cee:health-check`. |
| **A10: Server-Side Request Forgery (SSRF)** | **PASS** | File upload pipeline validates MIME types and restricts storage destinations to internal S3 buckets; no user-supplied arbitrary URL fetches. |

---

## 6. Pre-Launch Verification Commands

Execute the following commands before cutting over to production:
```bash
# 1. Verify Composer package security
composer audit

# 2. Verify NPM package security
npm audit --production

# 3. Verify health status and debug mode disabled
php artisan cee:health-check

# 4. Verify route caching and middleware registration
php artisan route:list --except-vendor

# 5. Run full automated test suite (including E2E security flows)
php artisan test --testsuite=Feature
```
