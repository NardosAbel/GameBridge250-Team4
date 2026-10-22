# GameBridge progress

## Done this session

- Replaced the dashboard sample tournaments with a query in `DashboardController`.
- Added `PlayerPassportService` and wired it to `/profile` and public `/players/{handle}`.
- Profile verification labels now use `email_verified_at` and `phone_verified_at`.
- Learning shortcut points at `/education`.
- Public tournament discovery hides drafts, archived events, and private events from people who are not the organizer, staff, or a registrant.
- Tournament and dispute authorization uses `Gate` because the base controller has no `authorize()` method.

## Tests actually run

`vendor/bin/pest tests/Feature/Web/PlayerDashboardAndPassportTest.php tests/Feature/Profile/ProfileTest.php`

Result: **12 passed, 69 assertions** (SQLite via `phpunit.xml`).

`vendor/bin/pint --dirty` passed before the last test assertion tweak.

`npx tsc --noEmit` from `backend` exited 0.

`npm run build` from `backend` exited 0 (Vite production build, about 1m 31s).

Local commit `b769354` on `security/p0-p1-remediation`. Not pushed. `docker/Dockerfile.pest` was left untracked.

`php vendor/bin/pest tests/Feature/Tournaments/TournamentVisibilityTest.php` — **4 passed, 15 assertions** (SQLite).

`php vendor/bin/pint --dirty` fixed `TournamentController` import order.

A full PostgreSQL Pest run was started in Docker after the passport commit and before this visibility change finished. Its result is recorded when that process exits. It is not evidence for the visibility change.

## Next

- Confirm the PostgreSQL Pest process result.
- Walk register → tournament register → result → passport in the browser if the app can start.
- Do not enable live payment providers.

## Not done

Phone SMS, Access Pass funding, and publishing double elimination, Swiss, or group knockout.
