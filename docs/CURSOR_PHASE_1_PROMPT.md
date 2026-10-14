# Cursor Phase 1 Prompt

```text
Read .cursor/rules/project.mdc and all files inside /docs before writing code.

This is the Campus Esports Ethiopia platform: an integrated gaming, esports, and digital-awareness platform for Ethiopian university students.

Use Laravel 12+ or the latest stable Laravel version, PHP 8.3+, React + Inertia + TypeScript, Tailwind CSS, PostgreSQL, Redis, Laravel Reverb, Laravel Horizon, Sanctum, and Spatie Laravel Permission.

Do not implement all features at once.

For Phase 1:

1. Inspect the environment and report installed versions of PHP, Composer, Node.js, npm/pnpm, Git, Docker, PostgreSQL/MySQL, and Redis.
2. Create a Laravel backend inside /backend.
3. Configure PostgreSQL, Redis, queues, scheduler, storage, mail testing, and Laravel Reverb.
4. Install Sanctum, Spatie Laravel Permission, Horizon, Pest, Laravel Pint, and PHPStan/Larastan.
5. Create a React + Inertia + TypeScript frontend with Tailwind CSS, dark/light mode, responsive layout, English/Amharic localization, and accessible base components.
6. Create the base database schema for users, profiles, roles, permissions, teams, communities, tournaments, matches, tickets, venues, courses, reports, and audit logs. Do not implement full business logic yet.
7. Create authentication APIs and pages for register, login, email/phone verification, password reset, logout, and profile setup.
8. Implement RBAC with roles: player, team_captain, organizer, venue_admin, school_admin, sponsor, creator, moderator, super_admin.
9. Create base API architecture: versioned routes, form requests, API resources, standardized JSON responses, exception handling, rate limiting, and audit logging.
10. Create GitHub Actions CI for Pint, PHPStan/Larastan, Pest, frontend lint/build, dependency audit, and Docker build.
11. Create Docker Compose for Laravel, PostgreSQL, Redis, Reverb, Horizon, mail testing, and frontend development.
12. Create .env.example with every required variable and comments.
13. Create README.md with setup, architecture, commands, and deployment notes.
14. Run migrations, seeders, frontend build, and all tests.
15. Commit the stable Phase 1 bootstrap.

After implementation, provide environment report, installed packages, file structure, database tables, API endpoints, local commands, test results, QA checklist, assumptions, and limitations.

Do not begin Phase 2 until I approve Phase 1.
```
