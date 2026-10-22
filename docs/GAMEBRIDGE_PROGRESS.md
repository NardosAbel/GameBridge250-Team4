# GameBridge progress

## Done this session

- Replaced the dashboard sample tournaments with a query in `DashboardController`.
- Added `PlayerPassportService` and wired it to `/profile` and public `/players/{handle}`.
- Profile verification labels now use `email_verified_at` and `phone_verified_at`.
- Learning shortcut points at `/education`.

## Tests actually run

`vendor/bin/pest tests/Feature/Web/PlayerDashboardAndPassportTest.php tests/Feature/Profile/ProfileTest.php`

Result: **12 passed, 69 assertions** (SQLite via `phpunit.xml`).

`vendor/bin/pint --dirty` passed before the last test assertion tweak.

`npx tsc --noEmit` from `backend` exited 0.

The full PostgreSQL suite was **not** rerun after this slice.

## Next

- Rerun the PostgreSQL Pest suite.
- Walk register → tournament register → result → passport in the browser if the app can start.
- Do not enable live payment providers.

## Not done

Phone SMS, Access Pass funding, and publishing double elimination, Swiss, or group knockout.
