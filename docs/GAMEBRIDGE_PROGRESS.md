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

## Next

- Walk register → tournament register → result → passport in the browser. The dev server was not started in this pass.
- Rerun the full PostgreSQL suite on the final tree when there is time. The last Docker run exited 0 but began before the later commits.
- Do not enable live payment providers.

## Not done

Phone SMS, Access Pass funding, and publishing double elimination, Swiss, or group knockout.
