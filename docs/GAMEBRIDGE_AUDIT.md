# GameBridge audit

Branch: `security/p0-p1-remediation`. Stack, from the repository: Laravel modular monolith, Inertia React + TypeScript, Pest, PostgreSQL in CI, SQLite in-memory for local `phpunit.xml`.

## Working

- Registration, login, OTP email delivery, profile privacy, teams, communities, tournament registration, single-elimination and round-robin brackets, result confirmation, disputes, rankings, ticketing, QR scans, and sandbox payment webhooks.
- Live payment providers stay disabled until a real verifier exists (`WebhookAuthenticator`).
- Double elimination, Swiss, and group knockout cannot be published.
- Inventory release uses `inventory_released_at`, not buyer notes.

## Defects

| Sev | Issue | State |
|---|---|---|
| P1 | Dashboard showed two invented tournaments and a Details button with no destination | Fixed this session |
| P1 | Profile showed rating `1000` and tournament count `0`, and treated any email or phone as verified | Fixed this session |
| P1 | Learning card linked to `/learning`; the route is `/education` | Fixed this session |
| P2 | Phone OTP is stored but not sent; no SMS provider | Open, documented |
| P2 | Incomplete bracket formats remain unpublished | Intentional |
| P2 | Historical orders with a forged `[inventory_released]` note were not backfilled | Open; do not auto-adjust `quantity_sold` |

No new secrets were added. `.env` is not in the working changes.
