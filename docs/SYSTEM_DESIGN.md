# System Design Specification

## Architecture

Use a Laravel modular monolith with React + Inertia + TypeScript.

```text
Users
├── Web / PWA
├── Organizer Dashboard
├── Venue Scanner
├── Sponsor Portal
└── Admin / Moderation Console
          │
     Laravel Application
          │
┌─────────┼──────────┬────────────┬────────────┐
│         │          │            │            │
Identity  Social    Tournament   Ticketing    Venue &
& RBAC    Network   Engine       Payments     Event Ops
│         │          │            │            │
│       Media      Bracket       QR Token     Scanner
│       Service    Service       Service      Sync
└─────────┴──────────┴────────────┴────────────┘
                     │
 PostgreSQL + Redis + Object Storage + Search
                     │
 Background workers: media, notifications, rankings,
 reports, payments, moderation, analytics
```

## Domain modules

- Identity
- Profiles
- Social
- Teams
- Communities
- Tournaments
- Ticketing
- Payments
- Venues
- Education
- Sponsorship
- Moderation
- Administration

## Core tables

- users
- profiles
- games
- teams
- team_members
- communities
- community_members
- posts
- post_media
- comments
- reactions
- follows
- tournaments
- tournament_registrations
- tournament_staff
- brackets
- bracket_nodes
- matches
- match_results
- match_evidence
- disputes
- rankings
- seasons
- venues
- events
- ticket_types
- ticket_orders
- tickets
- ticket_scans
- payments
- refunds
- payouts
- courses
- lessons
- quizzes
- quiz_attempts
- certificates
- sponsorships
- reports
- moderation_cases
- audit_logs

## Real-time channels

```text
private-user.{userId}
private-team.{teamId}
private-community.{communityId}
private-tournament.{tournamentId}
private-match.{matchId}
private-event.{eventId}
private-venue.{venueId}
private-scanner.{scannerId}
```

## Ticket QR flow

1. User completes payment.
2. System creates a ticket.
3. System generates a signed QR token.
4. QR payload contains only a ticket reference and signature.
5. Scanner validates the token with the backend.
6. Backend checks ticket status, event, expiry, scanner permission, and duplicate scans.
7. Offline scans are queued and synchronized later.

## Security controls

- HTTPS, HSTS, CSP, CSRF protection
- Sanctum authentication
- Role-based policies
- Rate limiting
- Signed URLs for private media
- Payment webhook signature verification
- QR token hashing and backend validation
- Audit logs
- Backup and disaster-recovery strategy

## Deployment

Use separate local, staging, and production environments.

CI/CD should run:

- Laravel Pint
- PHPStan/Larastan
- Pest tests
- Frontend lint and build
- Dependency audit
- Database migration checks
- Docker build where applicable
