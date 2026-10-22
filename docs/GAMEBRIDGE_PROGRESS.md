# GameBridge progress

## Done this session

- Replaced the dashboard sample tournaments with a query in `DashboardController`.
- Added `PlayerPassportService` and wired it to `/profile` and public `/players/{handle}`.
- Profile verification labels now use `email_verified_at` and `phone_verified_at`.
- Learning shortcut points at `/education`.
- Public tournament discovery hides drafts, archived events, and private events from people who are not the organizer, staff, or a registrant.
- Tournament and dispute authorization uses `Gate` because the base controller has no `authorize()` method.
- Leaderboard rows for players link to `/players/{gamer_tag}` and the page no longer claims a national 2026 season.
- Refund approval leaves tickets unchanged when the provider does not confirm the refund.
- The public landing page counts profiles, teams, open competitions, listed campuses, and saved games. Sample totals and the tournament form's 5000 ETB prize default are gone.

## Tests actually run

`vendor/bin/pest tests/Feature/Web/PlayerDashboardAndPassportTest.php tests/Feature/Profile/ProfileTest.php`

Result: **12 passed, 69 assertions** (SQLite via `phpunit.xml`).

`vendor/bin/pint --dirty` passed before the last test assertion tweak.

`npx tsc --noEmit` from `backend` exited 0, including a run after the landing page edit.

`npm run build` from `backend` exited 0 after the rankings and landing edits.

Local commit `b769354` on `security/p0-p1-remediation`. Not pushed. `docker/Dockerfile.pest` was left untracked.

`php vendor/bin/pest tests/Feature/Tournaments/TournamentVisibilityTest.php` — **4 passed, 15 assertions** (SQLite).

`php vendor/bin/pest tests/Feature/Payments/RefundAndTicketRevocationTest.php` — **4 passed, 21 assertions** (SQLite).

`php vendor/bin/pest tests/Feature/Web/LandingStatsTest.php tests/Feature/ExampleTest.php` — **2 passed, 19 assertions** (SQLite). `ExampleTest` now migrates the test database because the home page reads live counts.

`php vendor/bin/pint --dirty` passed after the refund change.

A PostgreSQL Pest run in Docker finished with exit code 0: **173 warnings, 750 assertions, no failures** (about 18 minutes). The warnings are `file_get_contents(.env)` because the container has no `.env`. That run started before the visibility, refund, and landing commits, and it loaded test files as it went, so it is not a clean proof of the final tree. The focused SQLite tests above cover those commits. `docker/Dockerfile.pest` remains untracked. Nothing was pushed.

## Continuation

The previous turn ended normally after commit `6be9483`. It was not an error or a partial write.

Host PHP in this shell had no `php.ini`, so the first SQLite run failed with `could not find driver`. A temporary ini enabled `pdo_sqlite`. Rerun: **21 passed, 123 assertions** across visibility, refunds, profiles, landing, and passport.

PostgreSQL Pest on the current tree, with an empty file mounted over `/var/www/html/.env` inside the container: **180 passed, 789 assertions, exit 0**. No `file_get_contents(.env)` warnings. `docker/Dockerfile.pest` is the local test image recipe and should stay tracked. It contains no secrets. The image already on this machine has more extensions than that recipe; rebuilding from the file only guarantees `pdo_pgsql`.

`backend/.env` was created from the example for local use and is gitignored. `DB_PORT=5433` does not reach Docker Postgres: a local `postgres.exe` owns IPv4 port 5433, while Docker owns IPv6. Migrations and the web server therefore ran in a container on `campus_esport_ethiopia_campus_network` against `postgres:5432` / `campus_esports`. `php artisan migrate --seed` exited 0.

The register page rendered. A filled registration for `hanna.bekele.bridge@example.com` did not create a user. A second login attempt, after restarting the server, kept the seeded player email and a 12-character password in the form and disabled the button, but PHP never logged the POST within 90 seconds. Organizer result confirmation and the public passport were not clicked through.

## Next

- Publish Docker Postgres on a host port that local `postgres.exe` does not own, then repeat the browser journey.
- Do not enable live payment providers.

## Not done

Phone SMS, Access Pass funding, and publishing double elimination, Swiss, or group knockout.
