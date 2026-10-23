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

## Competitions and landing, 2026-10-23

`DemoCompetitionSeeder` is idempotent and is called from `DatabaseSeeder`. On this machine it created three public `registration_open` single-elimination events and one organizer-only draft. Re-running it did not duplicate them. HabeshaSniper and three other synthetic players are approved on the AAU PUBG Mobile Open. Prize pools are 0. Records are marked `settings.demo`.

The landing page and `/tournaments` now render those records instead of a gradient hero and identical cards. A local banner is shared when `APP_ENV=local` and a demo tournament exists. Drafts stay off the public list.

SQLite: `DemoCompetitionSeederTest` and `LandingStatsTest` — **2 passed, 36 assertions**. `npx tsc --noEmit` exit 0. `npm run build` exit 0. Pint passed.

Browser on `http://127.0.0.1:8000`: home and the competition list show the three open events; `/tournaments/1` opens AAU PUBG Mobile Open. Registration from that page, bracket generation, and result confirmation were not clicked through.

These UI and seeder edits are not committed. Auth commit remains `9b8fd59`. Nothing was pushed.

## Tournament page and competition workflow, 2026-10-23

The public tournament page now uses the GameBridge header: game, format, organizer, schedule, deadline, capacity, rules, and prize only when those values exist. Individual entry says Register. Team entry says Register team and requires a team. Signed-out, full, closed, already registered, and ineligible states come from the show payload (`viewer_registration`, `viewer_eligibility`). The bracket empty state does not invent matches. The Players tab loads `/api/v1/tournaments/{id}/participants`. Rules no longer invent a points table.

`/tournaments/{id}/manage` and `/tournaments/{id}/prize-pool` now call `Gate::authorize('update')`. A signed-in player gets 403. The organizer still opens the manage page.

Dashboard drops the gradient command-center cards and lists the competitions `DashboardController` already loads. The passport uses the same shell and links each result to its tournament. The footer no longer claims WCAG compliance, an official university network, or a `/learning` route. Assigned learning paths no longer fall back to course id 1.

SQLite, current tree, `PHPRC` set to the local ini:

`php vendor/bin/pest tests/Feature/Tournaments/CompetitionJourneyTest.php tests/Feature/Web/LandingStatsTest.php tests/Feature/Web/PlayerDashboardAndPassportTest.php tests/Feature/Tournaments/DemoCompetitionSeederTest.php`

Result: **7 passed, 121 assertions**. A later rerun of `CompetitionJourneyTest` alone was **2 passed, 47 assertions** after the manage and prize-pool gates. That test registers one player, rejects the duplicate, lets the organizer confirm the only match, writes placement 1 and a ranking, shows that place on `/players/JourneyWinner`, forbids an outsider from confirm, override, manage, and prize-pool, and reports a university restriction before registration.

`php vendor/bin/pint --dirty` passed. `npx tsc --noEmit` exit 0. There is no frontend lint script in `backend/package.json`. `npm run build` exit 0 (about 6s).

Browser on `http://127.0.0.1:8000`, after that build:

- Signed in as BrowserPlayer26, `/tournaments/3` showed Register, not Register team, and an empty bracket.
- Confirming registration created one `tournament_registrations` row for tournament 3, user 5, status approved. A second POST returned 422, "You are already registered for this tournament." The count stayed 1. The Players tab listed BrowserPlayer26.
- The same player received HTTP 403 for `PUT /api/v1/tournaments/3/status` and `POST /api/v1/tournaments/3/bracket`, and the manage page rendered Laravel's 403.
- After logout, `organizer@campusesports.et` reached `/dashboard` and `/tournaments/3/manage`. Seeding showed one participant, Browser Player. Bracket generation was not clicked, so the live qualifier was not moved to live.
- `/teams` listed Kilo Rifles, Hawassa Green, and THE OZES. The two seeded teams show 0 players because the seeder does not create roster rows.
- The PHP development server log had no 500 responses during this pass.

The confirmed-result passport path was exercised by `CompetitionJourneyTest` on SQLite, not by finishing a match in the browser. These edits are not committed. Nothing was pushed.

## UI shell, theme, and team applications, 2026-10-23

The shared shell no longer shows a language control or the yellow “Local demonstration data” banner. `LanguageSwitcher.tsx` is deleted. A search of `backend/resources` found no `LanguageSwitcher`, `setLocale`, `አማርኛ`, or “Local demonstration”. Education pages keep English and no longer render an EN/አማርኛ toggle. `useTranslation` remains for existing labels. Seeders and demo rows were not deleted.

Theme is `localStorage.theme` (`dark` or `light`) plus the `dark` class on `documentElement`. The default, with no stored value, is dark. `app.blade.php` removes `dark` before paint when the stored value is `light`. Light mode remaps the existing slate utilities in `app.css`. Reduced motion is respected.

Team “apply” is a join request, not a tournament entry. `POST /api/v1/teams/{id}/invite` with `type=join_request` stores one invitation as role `player` for the signed-in user. Closed recruitment and a second pending request return 422. `POST /api/v1/team-invitations/{id}/respond` accepts a join request only for a roster manager (`super_admin`, `leader_id`, or owner/captain member). The applicant or a manager can decline. The team page shows `viewer_request` and, for managers, `pending_requests`. A captain does not see the apply form.

SQLite, `PHPRC` set to the local ini:

`php vendor/bin/pest tests/Feature/Teams/TeamApplicationTest.php`

Result: **1 passed, 15 assertions**. It covers one stored application, a rejected duplicate, a stranger who cannot apply as someone else or accept, the applicant payload, captain accept creating an active member, and closed recruitment. An earlier combined run with `CompetitionJourneyTest` was **3 passed, 62 assertions**.

`php vendor/bin/pint --dirty` passed after the leader-id policy change. `npx tsc --noEmit` exit 0. There is no frontend lint script. `npm run build` exit 0. The PHP server log for `127.0.0.1:8000` showed no 500 responses during this pass.

Browser on that server, signed in as YonasBekele unless noted:

- Light theme was applied, stored, and still light after opening `/teams/3`. Toggling back to dark on `/tournaments` at a 390×844 viewport set `localStorage.theme` to `dark` and the `dark` class. A reload of `/` kept dark. Body background was the dark slate token. No horizontal overflow (`scrollWidth` 390, `clientWidth` 390) on `/`, `/tournaments`, `/tournaments/3`, `/teams/3`, or `/education`.
- The mobile header is a menu button. Opening it showed Competitions, Rankings, Teams, Venues, Learning Hub, My Tickets, Gate QR Scanner, the account link, and Log Out. No language item and no demo banner.
- Hawassa Green (`/teams/3`) after the latest build did not show Apply to join for the captain. The roster listed TournamentDirector as player, and history said Campus League Organizer joined as player. Postgres already had invitation 2 (team 3, user 2, join_request, accepted) and an active `team_members` row from the earlier Accept click. That Accept was clicked in the browser before this reload; the duplicate and unauthorized paths were not re-clicked.
- `/tournaments/3` at 390 showed Nationwide Valorant Qualifier, a Register action, and “No matches yet”. Bracket generation and match confirmation were not clicked.
- `/education` had no language toggle.

These edits are not committed. Nothing was pushed.

## Social feed, communities, and identity, 2026-10-23

The existing posts, follows, comments, reactions, communities, and chat tables were reused. New work connects them into the product shell.

- Navigation puts Feed, Competitions, Communities, and Teams first. Signed-in mobile screens use a bottom bar: Feed, Events, Hubs, Alerts, Profile. Rankings, venues, learning, tickets, messages, and moderation stay under More.
- Headings use Space Grotesk. Body text stays Inter. The accent is `#A3E635` on charcoal. Light mode remaps lime text so it stays readable. Theme persistence is unchanged.
- `/feed` is a persisted composer: text, one image (jpeg, png, or webp, 5 MB), an optional public tournament, organizer announcements, and a sponsor flag. Video and livestream types are rejected. Posts are rate-limited.
- A player can like once; a second tap removes the like. Comments and new likes notify the author in the existing notification table. The post link is `/posts/{id}`.
- An announcement must be tied to a tournament the author can update. `sponsored` is stored only for a sponsor or super admin. A team id is accepted only for a member or leader. A community id requires membership.
- A community page lists real channels, creates a general channel for a member when none exists, and lets a manager add a named channel. Messages are stored and refreshed about every 8 seconds. There is no fake online presence or unread count. A non-member cannot read a private community channel.
- A public passport shows follower count from `follows`, a follow button, and that player's public posts. Likes do not affect rankings.

SQLite, `PHPRC` set, environment database variables cleared so phpunit uses sqlite:

`php vendor/bin/pest tests/Feature/Social/SocialPlatformTest.php`

Result: **5 passed, 28 assertions**. Covers a post linked to a published tournament, like then unlike, a comment notification, a forbidden delete, a rejected player announcement, an organizer announcement, a rejected fake sponsorship, a sponsor post, a rejected team post, private channel 403, and follow/unfollow.

`php vendor/bin/pest tests/Feature/Web/PlayerDashboardAndPassportTest.php` — **3 passed, 48 assertions**.

`php vendor/bin/pint --dirty` fixed import order in `PostResource.php` and otherwise passed. `npx tsc --noEmit` exit 0. There is no frontend lint script. `npm run build` exit 0.

The new columns `posts.tournament_id` and `posts.sponsored` were applied inside the Docker database `campus_esports` and recorded in `migrations`. No rows were deleted. The container role password was set to the password in `docker-compose.yml` so it matches that file. Host IPv4 port 5433 is still a different local Postgres, and the container address `172.19.0.3` is not reachable from this Windows host, so `php artisan migrate` from the host did not run.

Browser: `/login` rendered the new header and footer. A login POST was started as `player@campusesports.et` and did not finish, because the PHP server could not complete a Postgres connection from the host. The composer, like, comment, and community message were not clicked. Nothing was committed or pushed.

## Next

- Generate a bracket only when an event has enough real entrants, then confirm a result in the browser and open that player's passport.
- The organizer manage page body still uses the older panel layout. Venues, tickets, scanner, rankings, and sponsorship were not redesigned in this pass.
- Video posts, WebSocket delivery, unread badges, and typing presence are not implemented. Messages refresh on an 8 second poll.
- Feed, community chat, and follow were not clicked in the browser. Widths 360, 768, 1024, and 1440 were not measured in this pass.
- Kilo Rifles still has no roster rows, so it can show 0 players. THE OZES has a pre-existing pending join request that was not reviewed in the browser.
- Tablet and wide-desktop layouts were not measured separately. The 390×844 check used device emulation and was cleared afterward.
- Do not enable live payment providers.

## Not done

Phone SMS, Access Pass funding, and publishing double elimination, Swiss, or group knockout.
