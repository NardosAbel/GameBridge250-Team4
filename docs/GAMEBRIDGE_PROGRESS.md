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

## Visual redesign, 2026-10-23

This pass did not add product features. It changed the rendered shell.

The host still cannot authenticate to the Docker Postgres on `127.0.0.1:5433` because that port is shared with a different local Postgres. A one-shot container, `gb_pg_bridge`, forwards `127.0.0.1:55432` to `postgres:5432` on the Docker network. The role password was not changed. No rows were deleted. The PHP server on `127.0.0.1:8000` uses that bridge.

Shared surfaces are charcoal (`#141414`) in dark mode and warm paper in light mode. The accent stays `#A3E635`. Headings stay Space Grotesk and body text stays Inter; both resolved in the browser (`document.fonts.check` for Space Grotesk became true, and the computed heading family was Space Grotesk). Cards and dialogs no longer use blur, large radius, or drop shadows. Buttons are flat lime, slate, outline, or rose.

What changed on the pages that were opened:

- Competitions are a list, not a card grid. Each row shows the stored game name, status, start date, registration deadline, format, and entered count. Stored banner or icon URLs are used when present. These three events had none, so the rows are typographic. The first row is larger. The action label is View.
- Tournament detail keeps the server registration state. Tabs use the lime accent. An empty bracket says no matches exist. Winner styling in a generated bracket is lime text, not a green box.
- Teams and communities use the same list composition. Hawassa Green shows its tag, game, campus, apply form, and the real roster row. AAU Esports Club shows its description and member count.
- The feed composer is a single column. The desktop sidebar lists published competitions already returned by the tournament API. It does not repeat the header. A personalized feed query was sending preferred game names into `posts.game_id`. Names are now resolved to ids, and a 500 no longer prints SQL in the composer. This account had no posts, so the empty state is what rendered.
- The passport for HabeshaSniper shows the tag, campus, bio, follower count, entered count, and the AAU PUBG registration. It does not use statistic cards.
- Organizer manage for tournament 3 uses the same type and status buttons. Facts are a list. Seeding shows the one entrant and a seed field. The old metric cards are gone.
- Signed-in mobile navigation is icon plus label: Feed, Events, Clubs, Alerts, Profile.

Browser, signed in, against the live database:

- `/login` as HabeshaSniper, then `/tournaments`, `/tournaments/3`, `/feed`, `/teams`, `/teams/3` (Hawassa Green), `/communities`, `/communities/1`, `/players/HabeshaSniper`.
- Light theme on `/tournaments` set `localStorage.theme` to `light`, removed `dark`, and painted the body `rgb(246, 243, 236)`. It was switched back to dark before the organizer check.
- Organizer login as TournamentDirector, then `/tournaments/3/manage`. Status controls and the seeding tab rendered. No status was changed.
- AAU Esports Club still tells a non-member, including this organizer, to join before reading channels. That message is now in the conversation column as well as the channel list. Nobody was joined, so no channel transcript was opened.
- Measured layout widths with no horizontal overflow: 390, 960 (a 768 request landed here), 1025, and 1800 (a 1440 request landed here). The screenshot pane is 860px wide, so wide layouts are cropped on the right in the captures. The 390 and light-mode captures show the full column.

`npx tsc --noEmit` exit 0. `npm run build` exit 0. `php vendor/bin/pint --dirty` passed. With `DB_*` cleared so Pest uses sqlite: `SocialPlatformTest` and `PlayerDashboardAndPassportTest` together, **8 passed, 76 assertions**.

## Social layout, Discord communities, and registration approval, 2026-10-23

Desktop navigation is a left rail. The horizontal desktop bar is gone. The rail has GameBridge, Feed, Competitions, Communities, Teams, Notifications, Profile, then Rankings, Venues, Learning, Messages, and Tickets. There is no Discover route, so none was added. Theme and the signed-in account sit at the bottom of the rail. Mobile keeps a separate header and a bottom bar: Feed, Events, Clubs, Teams, and Profile. Guests get Home and Sign in in those end slots. The secondary menu holds the rest.

The feed is a central column with the current player's picture, a text composer, an image field, a tournament link, and a publish button. Posts show an avatar or a letter fallback, the handle, the time, the text, and like, comment, and copy-link actions. An image can be opened larger. Older posts load from the existing paginated API. Counts come from stored reactions and comments.

Communities use a server rail, a channel column, and a conversation column inside the page. On a phone the channel list is the first screen and the conversation replaces it, with a way back. A non-member sees the join gate and does not see the channel-creation form. Messages still refresh about every 8 seconds. There is no WebSocket delivery, unread count, typing indicator, or online list.

Registration is pending unless the tournament setting `auto_approve` is explicitly true. The public button says Apply to compete, or Apply with your team. Pending, approved, and rejected copy is distinct. Check-in and seeding only accept approved rows. Pending applications still reserve a place at apply time. Approval locks the tournament, counts approved rows only, and rechecks eligibility, so two approvals cannot pass capacity. Organizer manage has an Applications tab with approve and reject. Existing approved rows were left approved.

Profile pictures upload through `POST /api/v1/profile/avatar`. The server requires a real image, jpg, png, or webp, at most 2 MB, and stores it on the public disk. `php artisan storage:link` was run because `public/storage` was missing. Avatars render in the rail, composer, posts, comments, passport, profile, team roster, and chat, with a letter fallback when the file is missing. Comment JSON no longer includes the author's email or phone.

Browser, signed in as TournamentDirector, against Docker Postgres through `gb_pg_bridge` on `127.0.0.1:55432`:

- `/feed` showed the left rail in dark and light themes. A text post was published and persisted: "Scrim notes from the campus lab. Looking for a PUBG Mobile duo before Saturday." Like moved the count from 0 to 1 and unlike returned it to 0. A comment, "I can play Saturday evening.", was saved and shown. `/posts/1` opened that post.
- `/tournaments/1` showed Apply to compete, the rules, the deadline, and 6 approved entrants of 32. No application was submitted.
- `/tournaments/1/manage` showed Applications (0 pending) and Seeding (6). The six existing approved players stayed approved. No approve, reject, or status button was clicked.
- `/communities/1` showed the server chip, channel column, and conversation column. The non-member message remained. Nobody was joined, so no channel transcript was opened and no message was sent.
- `/profile` showed the letter avatar and Change profile picture. No file was uploaded in the browser.
- Theme toggle stored `dark` and painted the body `rgb(20, 20, 20)` with light text. Light mode earlier in the same check used the warm paper surface and dark text.
- No horizontal overflow at a 390 viewport on the profile page after the long gamer tag was allowed to wrap. A 768 request measured 941px wide, a 1024 request measured 1261px, and a 1440 request measured 1781px. Scroll width matched client width at each of those. The screenshot pane still crops the right side of wide pages. No HTTP 500 appeared in the PHP server log for these requests.

SQLite Pest, with `DB_*` cleared: `RegistrationReviewTest`, `CompetitionJourneyTest`, `ProductionCriticalFlowsTest`, `SocialPlatformTest`, and `PlayerDashboardAndPassportTest` together, **22 passed, 188 assertions**. A later social run after the comment resource, `SocialPlatformTest` and `SocialInteractionsTest`, **11 passed, 60 assertions**. `npx tsc --noEmit` exit 0. `npm run build` exit 0. `php vendor/bin/pint --dirty` passed.

## Next

- Venues, tickets, scanner, rankings, education, and sponsorship still use the older panels, gradients, and cyan accents.
- Tournament create and match-detail dialogs still have some rounded emerald boxes.
- No stored game artwork, so competition rows cannot be image-led until banners exist.
- An image was not attached in the browser. The composer accepts an image, and the server already validates image uploads, but no image post was clicked.
- Community chat was not opened. Joining AAU Esports Club would create a membership, and that was not done.
- A live tournament application was not submitted or approved. The pending-to-approved path was proven in Pest, and the live manage screen was only read.
- Kilo Rifles still has no roster rows. THE OZES still has a pre-existing pending join request.
- Video posts, WebSocket delivery, unread badges, and typing presence are not implemented.
- Do not enable live payment providers.

## Not done

Phone SMS, Access Pass funding, and publishing double elimination, Swiss, or group knockout.
