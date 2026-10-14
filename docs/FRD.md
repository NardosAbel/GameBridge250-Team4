# Functional Requirements Document

## Requirement ID format

- FR-AUTH: Authentication and accounts
- FR-PRO: Profiles and social network
- FR-TEAM: Teams and communities
- FR-TOUR: Tournaments
- FR-TICK: Ticketing
- FR-VEN: Venues
- FR-EDU: Learning and awareness
- FR-SPON: Sponsorship
- FR-SAFE: Safety and moderation
- FR-ADMIN: Administration

## Authentication

| ID | Requirement | Priority |
|---|---|---|
| FR-AUTH-01 | Users can register using email or phone. | Must |
| FR-AUTH-02 | Users verify email/phone using OTP. | Must |
| FR-AUTH-03 | Users can reset passwords securely. | Must |
| FR-AUTH-04 | System enforces role-based access control. | Must |
| FR-AUTH-05 | Users can enable two-factor authentication. | Should |
| FR-AUTH-06 | Users can view and revoke active sessions. | Should |
| FR-AUTH-07 | Admins can suspend, ban, and restore accounts. | Must |
| FR-AUTH-08 | Minor accounts require safeguarding controls. | Must |
| FR-AUTH-09 | Sensitive actions create audit logs. | Must |

## Profiles and social network

| ID | Requirement | Priority |
|---|---|---|
| FR-PRO-01 | Users can create gaming profiles. | Must |
| FR-PRO-02 | Profiles display achievements, teams, stats, and tournament history. | Must |
| FR-PRO-03 | Users can follow players, teams, organizers, venues, and tournaments. | Must |
| FR-PRO-04 | Users can post text, images, videos, clips, livestream links, and polls. | Must |
| FR-PRO-05 | Users can comment, react, share, bookmark, and tag content. | Must |
| FR-PRO-06 | Users receive a personalized feed. | Should |
| FR-PRO-07 | Users control profile visibility. | Must |
| FR-PRO-08 | Users can report, block, and mute others. | Must |
| FR-PRO-09 | Creators can view content analytics. | Should |

## Teams and communities

| ID | Requirement | Priority |
|---|---|---|
| FR-TEAM-01 | Users can create teams. | Must |
| FR-TEAM-02 | Teams support owner, captain, coach, manager, player, and substitute roles. | Must |
| FR-TEAM-03 | Authorized users can manage rosters and invitations. | Must |
| FR-TEAM-04 | Teams have match history, achievements, rankings, and media. | Must |
| FR-TEAM-05 | Users can create communities by game, school, city, venue, or interest. | Must |
| FR-TEAM-06 | Communities support public, private, and approval-based membership. | Must |
| FR-TEAM-07 | Communities support discussions, announcements, polls, and events. | Must |
| FR-TEAM-08 | Users can find teams or players through recruitment posts. | Should |
| FR-TEAM-09 | Community moderators can manage members and content. | Must |

## Tournaments

| ID | Requirement | Priority |
|---|---|---|
| FR-TOUR-01 | Verified organizers can create tournaments. | Must |
| FR-TOUR-02 | Tournaments support game, platform, region, dates, rules, prize pool, and capacity. | Must |
| FR-TOUR-03 | Tournaments support individual and team registration. | Must |
| FR-TOUR-04 | Tournaments support free, paid, invitation-only, and qualification entry. | Must |
| FR-TOUR-05 | System supports single elimination, double elimination, round robin, Swiss, and group-plus-knockout. | Must |
| FR-TOUR-06 | System generates brackets, handles byes, schedules matches, and advances winners. | Must |
| FR-TOUR-07 | System supports Best-of-1, Best-of-3, and Best-of-5. | Must |
| FR-TOUR-08 | System supports online, offline, and hybrid tournaments. | Must |
| FR-TOUR-09 | Participants can check in and confirm readiness. | Must |
| FR-TOUR-10 | Authorized users can submit results and evidence. | Must |
| FR-TOUR-11 | Results require confirmation or organizer override. | Must |
| FR-TOUR-12 | Users can open disputes and appeals. | Must |
| FR-TOUR-13 | Live brackets and standings update in real time. | Must |
| FR-TOUR-14 | System calculates rankings and seasonal leaderboards. | Must |

## Ticketing

| ID | Requirement | Priority |
|---|---|---|
| FR-TICK-01 | Organizers can create free and paid ticket types. | Must |
| FR-TICK-02 | System supports ticket categories, inventory, discounts, promo codes, and fees. | Must |
| FR-TICK-03 | System supports Telebirr, Chapa, M-Pesa, cards, bank transfer, and manual verification. | Must |
| FR-TICK-04 | Tickets are issued only after successful payment or approved manual verification. | Must |
| FR-TICK-05 | Each ticket has a unique signed QR token. | Must |
| FR-TICK-06 | QR codes are validated against the backend. | Must |
| FR-TICK-07 | Duplicate, expired, revoked, refunded, and wrong-event tickets are rejected. | Must |
| FR-TICK-08 | Scanner supports offline mode and synchronization. | Must |
| FR-TICK-09 | Organizers receive sales, revenue, attendance, refund, and scanner reports. | Must |

## Venues

| ID | Requirement | Priority |
|---|---|---|
| FR-VEN-01 | Gaming zones can create verified venue profiles. | Must |
| FR-VEN-02 | Venues display location, facilities, photos, hours, capacity, and pricing. | Must |
| FR-VEN-03 | Venues can host tournaments and events. | Must |
| FR-VEN-04 | Venues can sell tickets and scan QR entries. | Must |
| FR-VEN-05 | Users can discover nearby venues and events. | Must |
| FR-VEN-06 | Venues receive attendance and revenue analytics. | Should |

## Learning and awareness

| ID | Requirement | Priority |
|---|---|---|
| FR-EDU-01 | Platform provides digital-awareness and technology-learning content. | Must |
| FR-EDU-02 | Content is organized into courses, modules, lessons, quizzes, and certificates. | Must |
| FR-EDU-03 | Platform supports English and Amharic. | Must |
| FR-EDU-04 | Selected lessons can be downloaded for offline access. | Should |
| FR-EDU-05 | System tracks progress, quiz scores, certificates, and streaks. | Must |
| FR-EDU-06 | Schools can assign learning paths. | Should |

## Sponsorship

| ID | Requirement | Priority |
|---|---|---|
| FR-SPON-01 | Verified sponsors can create brand profiles. | Must |
| FR-SPON-02 | Sponsors can sponsor tournaments, teams, players, venues, creators, and campaigns. | Must |
| FR-SPON-03 | Organizers can create sponsorship packages and deliverables. | Must |
| FR-SPON-04 | Sponsors receive performance reports. | Must |
| FR-SPON-05 | Platform supports transparent prize-pool funding. | Should |

## Safety

| ID | Requirement | Priority |
|---|---|---|
| FR-SAFE-01 | Users can report users, posts, comments, media, teams, communities, messages, and tournaments. | Must |
| FR-SAFE-02 | Moderators have a case-management dashboard. | Must |
| FR-SAFE-03 | Platform supports automated and human moderation. | Must |
| FR-SAFE-04 | Platform applies warnings, mutes, disqualifications, suspensions, and bans. | Must |
| FR-SAFE-05 | Users can appeal penalties. | Must |
| FR-SAFE-06 | All moderation actions are audited. | Must |
