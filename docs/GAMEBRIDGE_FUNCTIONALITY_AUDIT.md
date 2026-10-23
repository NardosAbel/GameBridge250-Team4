# GameBridge functionality audit

Date: 2026-10-23. Classifications are from the current tree and the local Postgres database, not from an earlier test run.

## Working and verified

- Registration and login. Commit `9b8fd59`. Session, CSRF, and the login throttle were not changed in this pass.
- Public tournament list hides drafts. API test in `DemoCompetitionSeederTest` and the browser list both omit Draft Campus Invitational.
- Landing counts and the featured list come from the database. `LandingStatsTest` expects the open event and not a second, draft row.
- Demo seed is idempotent. Running `DemoCompetitionSeeder` twice does not add another copy of `aau-pubg-mobile-open` or extra registrations.

## Implemented, needs the rest of the journey

- Tournament detail, registration, duplicate rejection, organizer confirmation, placement, ranking, and the public passport are covered by `CompetitionJourneyTest` on SQLite. In the browser, BrowserPlayer26 registered once for the Nationwide Valorant Qualifier and the duplicate POST was rejected. The organizer can open manage and see that player. A live match was not confirmed in the browser, so that qualifier has no bracket and no placement.
- Organizer create stays a draft until a status change. The host form on `/tournaments` posts to `POST /api/v1/tournaments`. Publishing is a separate authorized transition. Double elimination, Swiss, and group knockout still cannot be published.

## Partially implemented

- Teams, communities, and one venue have demonstration rows (`kilo-rifles`, `hawassa-green`, `aau-esports-club`, `bole-lan-house`). `/teams` loads those rows. Seeded teams have no members, so the list shows 0 players. Communities and the venue page were not clicked in this pass.
- Manage and prize-pool pages are authorized. A player who is not staff gets 403. The manage screen itself is still the older organizer panel.
- Most web pages are Inertia shells that fetch `/api/v1` from the browser. That is the current architecture, not a second frontend app.

## Missing from the demo

- No confirmed match result and no ranking row were seeded. The public passport therefore cannot yet show a seeded verified result.
- Phone SMS is still not delivered. Live payment webhooks stay fail-closed.

## How to show the local data

`php artisan db:seed` calls `DemoCompetitionSeeder` after the accounts and games. It is safe to run again. It will not duplicate the slugs above and will not reset a tournament that already exists.

Demonstration accounts already in `DatabaseSeeder`, password `Password123!`:

- `organizer@campusesports.et`
- `player@campusesports.et` (gamer tag HabeshaSniper, entered in the PUBG Mobile Open)
