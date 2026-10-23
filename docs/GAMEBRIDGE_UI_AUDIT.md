# GameBridge UI audit

Date: 2026-10-23. Branch `security/p0-p1-remediation`. Auth fix `9b8fd59` is still in the tree. UI work after that commit is uncommitted.

`resources/js` stays the Inertia frontend. Pages live in `resources/js/pages`, the shell in `resources/js/layouts/AppLayout.tsx`, and primitives in `resources/js/components/ui`.

## What changed

- Landing no longer uses a gradient hero, pulsing badge, or feature cards that claim every bracket format, live Telebirr checkout, and offline QR entry. It lists real open tournaments from `HomeController`.
- The competition index is a list: game, format, region, host, date, entries, and prize only when a prize is stored. The empty state tells an organizer to host the first event.
- The header wordmark is GameBridge. Primary links are Competitions, Rankings, Teams, Venues, and Learning. Feed, communities, recruitment, tickets, chat, and alerts sit under More. The old `/tickets` footer link pointed nowhere; it now goes to `/my-tickets`.
- Outside production, a local banner says the seeded competitions are demonstration data. The Inertia prop `demonstration` is true on this machine.

## Still the old visual system

Manage, teams, venues, education, profile edit, and admin pages still use the darker card panels. Tournament detail, the dashboard, the passport, and the footer were brought onto the same type, spacing, and amber accent as the landing page. The footer no longer claims WCAG compliance or a low-bandwidth certification.

## Verified in the browser

On `http://127.0.0.1:8000` after `npm run build`:

- `/tournaments/3` for an individual free event shows Register, the published rules, and "No matches yet".
- After one real registration the status is "You are registered (approved)." and the Players tab lists BrowserPlayer26.
- A player opening `/tournaments/3/manage` gets 403. The organizer account opens it and sees that registration under Seeding.
- `/dashboard` for the organizer lists open competitions without the old command-center cards.
- `/teams` lists the seeded teams. Their player count is 0 because no roster rows were seeded.
