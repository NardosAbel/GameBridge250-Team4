# GameBridge execution plan

1. Keep the existing security remediation. Do not reopen live payment webhooks.
2. Make the judge demo use persisted data: dashboard, tournament detail, registration, organizer result confirmation, rankings, player passport.
3. Keep authorization on the server for profiles, tournaments, payments, and chat.
4. Leave unfinished formats unpublished rather than shipping a broken bracket.
5. Do not invent sponsors, funding, or player counts.

## Acceptance for the passport slice

- Dashboard lists only tournaments in `published`, `registration_open`, `check_in`, or `live`.
- Profile rating and tournament counts come from rankings and approved registrations.
- A placement is shown only when `tournament_registrations.placement` is set.
- A private profile returns 404 on `/players/{handle}`.
