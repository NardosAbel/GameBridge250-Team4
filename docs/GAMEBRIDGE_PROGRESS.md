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

## Registration and login, 2026-10-23

The stuck form was a server hang, not a disabled-button bug. `processing` disables the submit button after Inertia starts the POST. `RegisterUserAction` sent the verification email inside the same database transaction, and `config/mail.php` left the SMTP timeout unset. Local mail is SMTP to `127.0.0.1:1025`. Inside a container that port is not Mailpit, so the only PHP worker waited forever, the access log stayed empty, and the user row never committed. Login waited behind that same worker.

The account is now committed before the email is sent. A mail failure is logged without the code and does not roll the user back. SMTP times out after 5 seconds unless `MAIL_TIMEOUT` is set. The local gitignored `.env` uses `MAIL_MAILER=log` so this machine does not wait on Mailpit.

Web regression tests cover a successful session registration, a registration that survives a thrown mailer, validation errors, session login, a rejected password, logout, and the dashboard auth redirect.

`php vendor/bin/pest tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/OtpUrlTest.php` with the temporary SQLite ini: **14 passed, 87 assertions**.

Host `php artisan serve` does not load that ini. `ServeCommand` starts `php -S` with `$_ENV` only, and this PHP has no default `php.ini`, so cookies failed with `openssl_cipher_iv_length()` missing. The working server is `php -c` on `127.0.0.1:8000`, with Postgres reached through a one-connection proxy on `127.0.0.1:55432` because IPv4 `5433` is a different local Postgres. A direct POST created exactly one `bridge.fix.2026@example.com` / `BridgeFix2026` profile and redirected to verify-otp. The same account and `player@campusesports.et` then redirected to `/dashboard`. A wrong password redirected back to `/login`.

Browser, same server: the register form for `browser.player.2026@example.com` / `BrowserPlayer26` submitted, landed on verify-otp, accepted the local code, and opened the dashboard as that player. `email_verified_at` is set. There are still **0** tournaments, so the organizer journey has nothing to open yet.

`resources/js` is the normal Inertia layout: `pages/`, `components/ui/`, `layouts/`, `hooks/`. It should stay there.

## Next

- Create one real open tournament as the seeded organizer and register the new player through the browser.
- Do not enable live payment providers.

## Not done

Phone SMS, Access Pass funding, and publishing double elimination, Swiss, or group knockout.
