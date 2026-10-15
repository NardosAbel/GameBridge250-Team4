# Campus Esports Ethiopia — Database Schema Documentation

This document provides a comprehensive reference of all database tables, columns, relationships, and indexing strategies implemented in Phase 1 and Phase 2 of Campus Esports Ethiopia.

---

## 1. Authentication, Identity & Audit

### `users`
Core user identity supporting dual credentialing (Email and Phone/SMS for Ethiopian telecoms).
- `id`: Bigint unsigned, Primary Key
- `name`: Varchar(255)
- `email`: Varchar(255), Unique
- `phone`: Varchar(50), Unique, Nullable
- `password`: Varchar(255)
- `email_verified_at`: Timestamp, Nullable
- `phone_verified_at`: Timestamp, Nullable
- `remember_token`: Varchar(100), Nullable
- `created_at`, `updated_at`: Timestamps

### `profiles`
Extended esports identity, handle, verification status, and gameplay telemetry.
- `id`: Bigint unsigned, Primary Key
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `handle`: Varchar(50), Unique, Nullable
- `avatar_url`: Varchar(255), Nullable
- `banner_url`: Varchar(255), Nullable
- `bio`: Text, Nullable
- `gamer_tag`: Varchar(100), Nullable
- `university`: Varchar(255), Nullable
- `department`: Varchar(255), Nullable
- `student_id_number`: Varchar(100), Nullable
- `location`: Varchar(255), Nullable
- `preferred_games`: JSON (array of titles)
- `roles`: JSON (array of preferred roles, e.g. Sniper, IGL)
- `platforms`: JSON (array of platforms, e.g. PC, Mobile, Console)
- `availability`: Varchar(255), Nullable
- `privacy_settings`: JSON (visibility toggles)
- `achievements`: JSON (tournament trophies, medals)
- `badges`: JSON (verification, pioneer, varsity badges)
- `verification_status`: Varchar(50), Default `'pending'`
- `verified_at`: Timestamp, Nullable
- `created_at`, `updated_at`: Timestamps

### `audit_logs`
Immutable compliance and security ledger.
- `id`: Bigint unsigned, Primary Key
- `user_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `event`: Varchar(100) (e.g., `moderation.action`, `auth.login`, `team.ownership_transfer`)
- `auditable_type`: Varchar(255), Nullable
- `auditable_id`: Bigint unsigned, Nullable
- `old_values`: JSON, Nullable
- `new_values`: JSON, Nullable
- `ip_address`: Varchar(45), Nullable
- `user_agent`: Text, Nullable
- `created_at`: Timestamp

---

## 2. Social Network, Feed & Engagements

### `posts`
Collegiate and athlete social posts with rich media, tags, and interactive polls.
- `id`: Bigint unsigned, Primary Key
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `community_id`: Foreign key -> `communities(id)` ON DELETE SET NULL, Nullable
- `content`: Text
- `post_type`: Enum (`text`, `image`, `video`, `clip`, `livestream`, `poll`), Default `'text'`
- `visibility`: Enum (`public`, `university`, `team`, `followers`), Default `'public'`
- `is_pinned`: Boolean, Default `false`
- `is_announcement`: Boolean, Default `false`
- `livestream_url`: Varchar(255), Nullable
- `game_tags`: JSON, Nullable
- `location_tag`: Varchar(255), Nullable
- `poll_data`: JSON, Nullable (question, options, expires_at)
- `poll_votes`: JSON, Nullable (voter ID mappings)
- `likes_count`, `comments_count`, `shares_count`: Integers, Default `0`
- `created_at`, `updated_at`: Timestamps

### `post_media`
Media items associated with posts (screenshots, highlight clips, optimized thumbnails).
- `id`: Bigint unsigned, Primary Key
- `post_id`: Foreign key -> `posts(id)` ON DELETE CASCADE
- `media_type`: Enum (`image`, `video`, `clip`)
- `file_path`: Varchar(255)
- `thumbnail_path`: Varchar(255), Nullable
- `width`, `height`, `duration_seconds`: Integers, Nullable
- `sort_order`: Integer, Default `0`
- `created_at`, `updated_at`: Timestamps

### `comments`
Threaded discussion comments on posts.
- `id`: Bigint unsigned, Primary Key
- `post_id`: Foreign key -> `posts(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `parent_id`: Foreign key -> `comments(id)` ON DELETE CASCADE, Nullable
- `body`: Text
- `likes_count`: Integer, Default `0`
- `created_at`, `updated_at`: Timestamps

### `reactions`
Polymorphic reactions (`like`, `love`, `fire`, `gg`, `trophy`, `cry`).
- `id`: Bigint unsigned, Primary Key
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `reactable_type`: Varchar(255)
- `reactable_id`: Bigint unsigned
- `type`: Varchar(50), Default `'like'`
- `created_at`, `updated_at`: Timestamps
- *Index:* Unique(`user_id`, `reactable_type`, `reactable_id`)

### `follows`
Polymorphic graph edges connecting users to players, teams, communities, organizers, and tournaments.
- `id`: Bigint unsigned, Primary Key
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `followable_type`: Varchar(255)
- `followable_id`: Bigint unsigned
- `created_at`, `updated_at`: Timestamps
- *Index:* Unique(`user_id`, `followable_type`, `followable_id`)

### `shares` & `bookmarks`
- `shares`: `user_id`, `post_id`, `caption`, timestamps
- `bookmarks`: `user_id`, `post_id`, timestamps (Unique index on `user_id`, `post_id`)

### `user_blocks` & `user_mutes`
Safety tools filtering malicious or unwanted interactions and feed surfacing.
- `user_blocks`: `user_id`, `blocked_user_id` (Unique index)
- `user_mutes`: `user_id`, `muted_user_id` (Unique index)

---

## 3. Teams & Rosters

### `teams`
Competitive esports rosters with university affiliation and recruitment status.
- `id`: Bigint unsigned, Primary Key
- `owner_id`: Foreign key -> `users(id)` ON DELETE RESTRICT
- `name`: Varchar(255)
- `slug`: Varchar(255), Unique
- `tag`: Varchar(10) (e.g. `AAG`)
- `logo_url`: Varchar(255), Nullable
- `banner_url`: Varchar(255), Nullable
- `bio`: Text, Nullable
- `game`: Varchar(100)
- `university`: Varchar(255), Nullable
- `city`: Varchar(100), Nullable
- `is_recruiting`: Boolean, Default `false`
- `achievements`: JSON, Nullable
- `created_at`, `updated_at`: Timestamps

### `team_members`
Active roster assignments with specialized esports roles.
- `id`: Bigint unsigned, Primary Key
- `team_id`: Foreign key -> `teams(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `role`: Enum (`owner`, `captain`, `coach`, `manager`, `player`, `substitute`), Default `'player'`
- `is_starter`: Boolean, Default `true`
- `joined_at`: Timestamp
- `created_at`, `updated_at`: Timestamps
- *Index:* Unique(`team_id`, `user_id`)

### `team_roster_history`
Audit and verification history of roster join/leave events and role promotions.
- `id`: Bigint unsigned, Primary Key
- `team_id`: Foreign key -> `teams(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `action`: Enum (`joined`, `left`, `removed`, `promoted`, `transferred`)
- `previous_role`: Varchar(50), Nullable
- `new_role`: Varchar(50), Nullable
- `recorded_at`: Timestamp

### `team_invitations`
Formal team invitation and join request lifecycle management.
- `id`: Bigint unsigned, Primary Key
- `team_id`: Foreign key -> `teams(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `inviter_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `role`: Varchar(50), Default `'player'`
- `type`: Enum (`invitation`, `join_request`)
- `status`: Enum (`pending`, `accepted`, `declined`, `canceled`), Default `'pending'`
- `message`: Text, Nullable
- `responded_at`: Timestamp, Nullable
- `created_at`, `updated_at`: Timestamps

---

## 4. Communities

### `communities`
Collegiate, regional, game-specific, and creator gaming clubs.
- `id`: Bigint unsigned, Primary Key
- `owner_id`: Foreign key -> `users(id)` ON DELETE RESTRICT
- `name`: Varchar(255)
- `slug`: Varchar(255), Unique
- `description`: Text, Nullable
- `type`: Enum (`university`, `school`, `game`, `city`, `venue`, `interest`, `creator`)
- `visibility`: Enum (`public`, `private`, `approval`), Default `'public'`
- `avatar_url`: Varchar(255), Nullable
- `banner_url`: Varchar(255), Nullable
- `rules`: Text, Nullable
- `members_count`: Integer, Default `1`
- `created_at`, `updated_at`: Timestamps

### `community_members`
- `id`: Bigint unsigned, Primary Key
- `community_id`: Foreign key -> `communities(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `role`: Enum (`leader`, `moderator`, `member`), Default `'member'`
- `status`: Enum (`active`, `pending`, `banned`), Default `'active'`
- `joined_at`: Timestamp
- `created_at`, `updated_at`: Timestamps
- *Index:* Unique(`community_id`, `user_id`)

---

## 5. Recruitment Board

### `recruitment_posts`
"Looking for Team" (LFT) and "Looking for Players" (LFP) notice board.
- `id`: Bigint unsigned, Primary Key
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `team_id`: Foreign key -> `teams(id)` ON DELETE CASCADE, Nullable
- `type`: Enum (`lft`, `lfp`)
- `game`: Varchar(100)
- `title`: Varchar(255)
- `description`: Text
- `role_needed`: Varchar(100), Nullable
- `rank_requirement`: Varchar(100), Nullable
- `platform`: Varchar(50), Nullable
- `university_requirement`: Varchar(255), Nullable
- `location`: Varchar(255), Nullable
- `availability_requirement`: Varchar(255), Nullable
- `contact_info`: Varchar(255), Nullable
- `is_active`: Boolean, Default `true`
- `created_at`, `updated_at`: Timestamps
- *Indexes:* `(game, is_active)`, `(type, is_active)`

---

## 6. Chat, Messaging & Notifications

### `chat_channels` & `chat_channel_members`
Direct, team, and community real-time messaging hubs.
- `chat_channels`: `id`, `name`, `type` (`direct`, `team`, `community`), `team_id`, `community_id`, timestamps
- `chat_channel_members`: `channel_id`, `user_id`, `role`, `last_read_at`, timestamps (Unique index on `channel_id`, `user_id`)

### `chat_messages`
- `id`: Bigint unsigned, Primary Key
- `channel_id`: Foreign key -> `chat_channels(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `body`: Text
- `mentions`: JSON, Nullable (array of mentioned user IDs)
- `created_at`, `updated_at`: Timestamps
- *Index:* `(channel_id, created_at)`

### `notifications`
Multi-channel notification inbox (In-app, push, email, SMS).
- `id`: UUID, Primary Key
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `type`: Varchar(255)
- `title`: Varchar(255)
- `message`: Text
- `data`: JSON, Nullable
- `channel`: Enum (`in_app`, `email`, `sms`, `push`), Default `'in_app'`
- `read_at`: Timestamp, Nullable
- `created_at`, `updated_at`: Timestamps
- *Index:* `(user_id, read_at)`

### `notification_preferences`
- `id`: Bigint unsigned, Primary Key
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE, Unique
- `in_app`: Boolean, Default `true`
- `email`: Boolean, Default `true`
- `sms`: Boolean, Default `false`
- `push`: Boolean, Default `true`
- `created_at`, `updated_at`: Timestamps

---

## 7. Moderation, Reports & Sanctions

### `reports`
User and automated flags for toxic behavior, cheating, spam, or misconduct.
- `id`: Bigint unsigned, Primary Key
- `reporter_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `reportable_type`: Varchar(255)
- `reportable_id`: Bigint unsigned
- `category`: Varchar(100)
- `description`: Text, Nullable
- `status`: Enum (`pending`, `under_review`, `resolved`, `dismissed`), Default `'pending'`
- `moderator_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `moderator_notes`: Text, Nullable
- `action_taken`: Varchar(100), Nullable
- `created_at`, `updated_at`: Timestamps

### `moderation_cases`
Action logs tracking sanctions (warnings, mutes, suspensions, bans).
- `id`: Bigint unsigned, Primary Key
- `report_id`: Foreign key -> `reports(id)` ON DELETE SET NULL, Nullable
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `moderator_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `action`: Enum (`warn`, `mute`, `suspend`, `ban`, `delete_content`, `dismiss`)
- `reason`: Text
- `duration_minutes`: Integer, Nullable
- `expires_at`: Timestamp, Nullable
- `created_at`, `updated_at`: Timestamps

---

## 8. Tournament Engine & Brackets

### `tournaments`
Core tournament entity governing metadata, format, eligibility rules, and lifecycle states.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `organizer_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `game_id`: Foreign key -> `games(id)` ON DELETE RESTRICT
- `title`: Varchar(255)
- `slug`: Varchar(255), Unique
- `description`: Text, Nullable
- `format`: Enum (`single_elimination`, `double_elimination`, `round_robin`, `swiss`, `group_stage_knockout`)
- `match_format`: Enum (`bo1`, `bo3`, `bo5`), Default `'bo1'`
- `entry_type`: Enum (`free`, `paid`, `invitation`, `qualification`), Default `'free'`
- `status`: Enum (`draft`, `published`, `registration_open`, `registration_closed`, `check_in`, `live`, `completed`, `cancelled`, `archived`), Default `'draft'`
- `capacity`: Integer unsigned, Default 16
- `platform`: Varchar(50), Default `'all'` (e.g., `pc`, `mobile`, `console`)
- `region`: Varchar(100), Default `'Ethiopia'`
- `visibility`: Enum (`public`, `unlisted`, `private`), Default `'public'`
- `banner_url`, `logo_url`: Varchar(255), Nullable
- `theme_color`: Varchar(20), Nullable
- `stream_url`: Varchar(255), Nullable (YouTube / Twitch livestream embed)
- `rules`: LongText, Nullable
- `prize_pool`: Decimal(12, 2), Default 0.00
- `eligibility_rules`: JSON (allowed universities, regions, platforms, required student ID, min age)
- `points_distribution`: JSON (points awarded per placement)
- `settings`: JSON (invitation codes, auto-approval flags, check-in window duration)
- `registration_start_at`, `registration_end_at`: Timestamps, Nullable
- `check_in_start_at`, `check_in_end_at`: Timestamps, Nullable
- `starts_at`, `ends_at`: Timestamps, Nullable
- `created_at`, `updated_at`: Timestamps, SoftDeletes

### `tournament_registrations`
Participants (Solo or Teams) registered for a tournament with roster snapshot and fraud flags.
- `id`: Bigint unsigned, Primary Key
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE, Nullable
- `team_id`: Foreign key -> `teams(id)` ON DELETE CASCADE, Nullable
- `status`: Enum (`pending`, `approved`, `rejected`, `checked_in`, `disqualified`), Default `'pending'`
- `seed`: Integer unsigned, Nullable
- `checked_in`: Boolean, Default `false`
- `checked_in_at`: Timestamp, Nullable
- `roster_snapshot`: JSON, Nullable (frozen copy of players and subs at registration lock)
- `qualification_score`: Decimal(8, 2), Nullable
- `invitation_code`: Varchar(100), Nullable
- `anti_fraud_flags`: JSON, Nullable
- `placement`: Integer unsigned, Nullable (e.g. 1st, 2nd, 3rd)
- `points_awarded`: Integer unsigned, Default 0
- `created_at`, `updated_at`: Timestamps

### `tournament_staff`
Appointed officials with scoped administrative permissions.
- `id`: Bigint unsigned, Primary Key
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `role`: Enum (`admin`, `co_organizer`, `referee`, `scorekeeper`, `caster`)
- `created_at`, `updated_at`: Timestamps

### `brackets`
Logical bracket stages (e.g., Upper Bracket, Lower Bracket, Group A, Playoffs).
- `id`: Bigint unsigned, Primary Key
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE
- `name`: Varchar(255)
- `stage`: Enum (`upper`, `lower`, `group_stage`, `playoffs`, `final`)
- `type`: Enum (`elimination`, `round_robin`, `swiss`)
- `status`: Enum (`pending`, `active`, `completed`), Default `'pending'`
- `settings`: JSON, Nullable
- `created_at`, `updated_at`: Timestamps

### `bracket_nodes`
Directed acyclic graph nodes linking upstream matches to downstream destination matches.
- `id`: Bigint unsigned, Primary Key
- `bracket_id`: Foreign key -> `brackets(id)` ON DELETE CASCADE
- `round`: Integer unsigned
- `node_order`: Integer unsigned
- `group_number`: Integer unsigned, Nullable (used in group stage multi-round-robin)
- `next_node_id`: Foreign key -> `bracket_nodes(id)` ON DELETE SET NULL, Nullable
- `loser_next_node_id`: Foreign key -> `bracket_nodes(id)` ON DELETE SET NULL, Nullable (Double Elimination)
- `is_bye`: Boolean, Default `false`
- `created_at`, `updated_at`: Timestamps

### `matches` (`TournamentMatch`)
Discrete competitive fixtures between two participants with restricted room access.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE
- `bracket_node_id`: Foreign key -> `bracket_nodes(id)` ON DELETE SET NULL, Nullable
- `round`: Integer unsigned, Default 1
- `match_number`: Integer unsigned, Default 1
- `match_format`: Enum (`bo1`, `bo3`, `bo5`), Default `'bo1'`
- `participant1_id`, `participant2_id`: Foreign keys, Nullable
- `participant1_type`, `participant2_type`: Enum (`user`, `team`), Nullable
- `participant1_name`, `participant2_name`: Varchar(255), Nullable
- `participant1_score`, `participant2_score`: Integer, Default 0
- `participant1_checked_in`, `participant2_checked_in`: Boolean, Default `false`
- `participant1_ready`, `participant2_ready`: Boolean, Default `false`
- `winner_id`: Bigint unsigned, Nullable
- `status`: Enum (`pending`, `ready`, `live`, `completed`, `disputed`, `cancelled`), Default `'pending'`
- `is_bye`: Boolean, Default `false`
- `room_id`: Varchar(255), Nullable (Restricted access)
- `room_password`: Varchar(255), Nullable (Restricted access)
- `room_server`: Varchar(100), Nullable (Restricted access)
- `room_instructions`: Text, Nullable (Restricted access)
- `referee_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `scheduled_at`, `completed_at`: Timestamps, Nullable
- `created_at`, `updated_at`: Timestamps

### `match_results` & `match_evidence`
Submitted scores, opponent confirmations, and media proofs.
- `match_results`: `id`, `match_id`, `submitted_by_id`, `confirmed_by_id`, `participant1_score`, `participant2_score`, `status` (`submitted`, `confirmed`, `disputed`, `overridden`), `notes`, `game_metadata` (JSON), `created_at`, `updated_at`
- `match_evidence`: `id`, `match_result_id`, `user_id`, `type` (`screenshot`, `video_clip`, `match_id_code`), `url`, `description`, `created_at`, `updated_at`

### `disputes`
Protest workflow for disputed match outcomes.
- `id`: Bigint unsigned, Primary Key
- `match_id`: Foreign key -> `matches(id)` ON DELETE CASCADE
- `raised_by_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `assigned_to_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `reason`: Text
- `evidence_urls`: JSON, Nullable
- `status`: Enum (`open`, `under_review`, `resolved`, `rejected`), Default `'open'`
- `action_taken`: Enum (`confirm_result`, `overturn_result`, `rematch`, `disqualify_participant`), Nullable
- `resolution_notes`: Text, Nullable
- `resolved_at`: Timestamp, Nullable
- `created_at`, `updated_at`: Timestamps

### `rankings` & `ranking_snapshots`
Seasonal Elo ratings, cumulative points, and immutable leaderboard captures.
- `rankings`: `id`, `season_id`, `game_id`, `user_id`, `team_id`, `points`, `rating`, `rank`, `created_at`, `updated_at`
- `ranking_snapshots`: `id`, `season_id`, `game_id`, `snapshot_date`, `type` (`global`, `seasonal`, `monthly`), `rankings_data` (JSON array of top 100), `created_at`, `updated_at`

### `tournament_fraud_signals`
Automated flags detecting duplicate IPs, student ID reuse across accounts, and multi-team roster tampering.
- `id`: Bigint unsigned, Primary Key
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE
- `registration_id`: Foreign key -> `tournament_registrations(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `signal_type`: Enum (`duplicate_ip`, `duplicate_student_id`, `multi_team_roster`, `rapid_submission`, `vpn_detected`)
- `severity`: Enum (`low`, `medium`, `high`, `critical`)
- `metadata`: JSON, Nullable
- `is_reviewed`: Boolean, Default `false`
- `reviewed_by`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `review_notes`: Text, Nullable
- `created_at`, `updated_at`: Timestamps

---

## Phase 4: Ticketing, Local Payments, QR Gate Entry, and Venue Network

### `venues`
Physical LAN gaming cafes, university gaming lounges, and tournament arenas.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `name`: Varchar(255)
- `slug`: Varchar(255), Unique
- `description`: Text, Nullable
- `owner_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `address`: Varchar(255)
- `city`: Varchar(100), Indexed
- `latitude`: Decimal(10, 7), Nullable (Used for Haversine distance search)
- `longitude`: Decimal(10, 7), Nullable (Used for Haversine distance search)
- `map_link`: Varchar(500), Nullable
- `facilities`: JSON, Nullable (`fiber_internet`, `rtx_gaming_pcs`, `ps5_consoles`, `streaming_studio`, etc.)
- `opening_hours`: JSON, Nullable
- `contact_details`: JSON, Nullable (phone, email, telegram)
- `rules`: JSON, Nullable
- `capacity`: Integer, Default 50
- `hourly_rate`: Decimal(10, 2), Nullable
- `pricing_details`: JSON, Nullable
- `currency`: Varchar(3), Default `'ETB'`
- `verified`: Boolean, Default `false`
- `is_featured`: Boolean, Default `false`
- `photos`: JSON, Nullable
- `created_at`, `updated_at`, `deleted_at`: Timestamps

### `venue_staff`
Role-scoped personnel assignments for gate scanning, moderation, or operations.
- `id`: Bigint unsigned, Primary Key
- `venue_id`: Foreign key -> `venues(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `role`: Enum (`manager`, `moderator`, `scanner`), Default `'scanner'`
- `is_active`: Boolean, Default `true`
- `created_at`, `updated_at`: Timestamps

### `venue_reviews`
Attendee ratings and qualitative feedback on equipment, ping, and atmosphere.
- `id`: Bigint unsigned, Primary Key
- `venue_id`: Foreign key -> `venues(id)` ON DELETE CASCADE
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `rating`: Tinyint unsigned (1-5)
- `comment`: Text, Nullable
- `is_verified_visit`: Boolean, Default `false`
- `created_at`, `updated_at`: Timestamps

### `events`
Scheduled community LAN parties, viewings, and venue-hosted tournaments.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `venue_id`: Foreign key -> `venues(id)` ON DELETE SET NULL, Nullable
- `organizer_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE SET NULL, Nullable
- `title`: Varchar(255)
- `slug`: Varchar(255), Unique
- `description`: Text, Nullable
- `banner_url`: Varchar(500), Nullable
- `event_type`: Enum (`lan_tournament`, `viewing_party`, `community_scrim`, `bootcamp`, `meetup`, `recurring_league`)
- `recurring_pattern`: Enum (`none`, `weekly`, `biweekly`, `monthly`), Default `'none'`
- `capacity`: Integer, Default 100
- `start_time`, `end_time`: Timestamps
- `status`: Enum (`draft`, `published`, `live`, `completed`, `cancelled`), Default `'draft'`
- `created_at`, `updated_at`: Timestamps

### `ticket_types`
Configurable admission tiers (General Admission, VIP, Student, Player Pass, Complimentary).
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE, Nullable
- `event_id`: Foreign key -> `events(id)` ON DELETE CASCADE, Nullable
- `name`: Varchar(150)
- `description`: Text, Nullable
- `category`: Enum (`free_rsvp`, `general_admission`, `vip`, `student`, `player_pass`, `team_pass`, `staff`, `press`, `vendor`, `early_bird`, `group`, `complimentary`)
- `price`: Decimal(10, 2), Default 0.00
- `currency`: Varchar(3), Default `'ETB'`
- `quantity_total`: Integer unsigned
- `quantity_sold`: Integer unsigned, Default 0
- `min_per_order`: Integer unsigned, Default 1
- `max_per_order`: Integer unsigned, Default 10
- `service_fee`: Decimal(10, 2), Default 0.00
- `sales_start_at`, `sales_end_at`: Timestamps, Nullable
- `settings`: JSON, Nullable (perks list, seat allocations)
- `is_active`: Boolean, Default `true`
- `created_at`, `updated_at`: Timestamps

### `promo_codes`
Discount codes and campaign vouchers with usage limits and expiration windows.
- `id`: Bigint unsigned, Primary Key
- `code`: Varchar(50), Unique
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE, Nullable
- `event_id`: Foreign key -> `events(id)` ON DELETE CASCADE, Nullable
- `discount_type`: Enum (`fixed`, `percentage`), Default `'percentage'`
- `discount_value`: Decimal(10, 2)
- `usage_limit`: Integer unsigned, Nullable
- `used_count`: Integer unsigned, Default 0
- `is_active`: Boolean, Default `true`
- `expires_at`: Timestamp, Nullable
- `created_at`, `updated_at`: Timestamps

### `ticket_orders` & `ticket_order_items`
Purchases locked transactionally to ensure strictly zero overselling.
- `ticket_orders`: `id`, `uuid`, `order_number` (Unique), `user_id`, `event_id`, `tournament_id`, `promo_code_id`, `subtotal`, `discount_amount`, `service_fee`, `total_amount`, `currency` (ETB), `payment_method`, `status` (`pending`, `paid`, `cancelled`, `refunded`, `partially_refunded`), `created_at`, `updated_at`
- `ticket_order_items`: `id`, `ticket_order_id`, `ticket_type_id`, `unit_price`, `quantity`, `subtotal`, `created_at`, `updated_at`

### `tickets`
Individual admission passes containing cryptographically signed HMAC-SHA256 QR tokens.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `ticket_order_id`: Foreign key -> `ticket_orders(id)` ON DELETE CASCADE
- `ticket_type_id`: Foreign key -> `ticket_types(id)` ON DELETE CASCADE
- `event_id`: Foreign key -> `events(id)` ON DELETE CASCADE, Nullable
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE, Nullable
- `venue_id`: Foreign key -> `venues(id)` ON DELETE SET NULL, Nullable
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE (Attendee)
- `ticket_code`: Varchar(50), Unique
- `qr_token_hash`: Varchar(64), Unique
- `qr_signature`: Varchar(255), Nullable (HMAC-SHA256 signature)
- `status`: Enum (`pending_payment`, `active`, `checked_in`, `used`, `expired`, `revoked`, `refunded`, `transferred`), Default `'active'`
- `checked_in_at`: Timestamp, Nullable
- `checked_in_by_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `transferred_to_user_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `transfer_history`: JSON, Nullable
- `expires_at`: Timestamp, Nullable
- `created_at`, `updated_at`: Timestamps

### `ticket_scans`
Gate activity audit log capturing real-time and synchronized offline scans.
- `id`: Bigint unsigned, Primary Key
- `ticket_id`: Foreign key -> `tickets(id)` ON DELETE CASCADE
- `venue_id`: Foreign key -> `venues(id)` ON DELETE SET NULL, Nullable
- `event_id`: Foreign key -> `events(id)` ON DELETE SET NULL, Nullable
- `scanned_by_id`: Foreign key -> `users(id)` ON DELETE CASCADE (Staff)
- `device_id`: Varchar(100), Nullable
- `status`: Enum (`valid`, `duplicate`, `expired`, `revoked`, `wrong_event`, `invalid`)
- `scanned_at`: Timestamp
- `created_at`, `updated_at`: Timestamps

### `payments` & `payment_webhooks`
Gateway abstractions supporting Telebirr, Chapa, M-Pesa, Card, and Bank Transfers.
- `payments`: `id`, `uuid`, `ticket_order_id`, `user_id`, `amount`, `fee`, `currency`, `provider` (`telebirr`, `chapa`, `mpesa`, `card`, `bank_transfer`, `manual`), `provider_reference`, `status` (`pending`, `paid`, `failed`, `cancelled`, `refunded`, `partially_refunded`), `idempotency_key` (Unique), `webhook_payload` (JSON), `metadata` (JSON), `paid_at`, `created_at`, `updated_at`
- `payment_webhooks`: `id`, `provider`, `event_type`, `provider_reference`, `payload` (JSON), `signature`, `status` (`processed`, `duplicate`, `failed`), `error_message`, `created_at`, `updated_at`

### `refunds` & `payouts`
Attendee refund approval workflows and organizer fee-transparent settlement payouts.
- `refunds`: `id`, `uuid`, `payment_id`, `ticket_order_id`, `user_id` (Requester), `processed_by_id`, `amount`, `reason`, `reason_category` (`event_cancelled`, `event_rescheduled`, `attendee_unavailable`, `duplicate_purchase`, `other`), `status` (`pending`, `approved`, `rejected`, `completed`), `rejection_reason`, `processed_at`, `created_at`, `updated_at`
- `payouts`: `id`, `uuid`, `user_id` (Organizer), `event_id`, `tournament_id`, `amount` (Net amount), `gross_amount`, `platform_fee` (5% transparent fee), `currency` (ETB), `provider` (`telebirr`, `cbe`, `awash`, `mpesa`), `account_number`, `status` (`pending`, `approved`, `processing`, `completed`, `rejected`), `notes`, `processed_at`, `created_at`, `updated_at`

---

## Phase 5: Digital Awareness and Learning Hub

### `courses`
Foundational and specialized courses across 11 digital awareness categories.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `title`, `title_am`: Varchar(255) (Bilingual English & Amharic)
- `slug`: Varchar(255), Unique
- `description`, `description_am`: Text, Nullable
- `cover_image`: Varchar(500), Nullable
- `category`: Enum (`online_safety`, `scam_awareness`, `cyberbullying_prevention`, `healthy_gaming`, `digital_literacy`, `ai_basics`, `coding`, `content_creation`, `esports_careers`, `data_skills`, `digital_entrepreneurship`)
- `level`: Enum (`beginner`, `intermediate`, `advanced`)
- `instructor_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `status`: Enum (`draft`, `scheduled`, `published`, `archived`)
- `is_published`: Boolean, Default `false`
- `scheduled_for`: Timestamp, Nullable
- `language_support`: JSON (`['en', 'am']`)
- `estimated_duration_minutes`: Integer unsigned, Default 60
- `total_points`: Integer unsigned, Default 100
- `is_offline_available`: Boolean, Default `true`
- `created_at`, `updated_at`: Timestamps

### `course_modules`
Curriculum module chapters grouping related lessons.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `course_id`: Foreign key -> `courses(id)` ON DELETE CASCADE
- `title`, `title_am`: Varchar(255)
- `description`: Text, Nullable
- `order`: Smallint unsigned, Default 1
- `created_at`, `updated_at`: Timestamps

### `lessons`
Multi-format learning units supporting articles, videos, PDFs, and interactive exercises.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `course_id`: Foreign key -> `courses(id)` ON DELETE CASCADE
- `module_id`: Foreign key -> `course_modules(id)` ON DELETE SET NULL, Nullable
- `title`, `title_am`: Varchar(255)
- `slug`: Varchar(255)
- `lesson_type`: Enum (`article`, `video`, `pdf`, `quiz`, `interactive`)
- `content`, `content_am`: LongText, Nullable
- `media_url`, `video_embed_url`, `pdf_url`: Varchar(500), Nullable
- `interactive_payload`: JSON, Nullable
- `order`: Smallint unsigned, Default 1
- `duration_minutes`: Smallint unsigned, Default 15
- `is_preview`: Boolean, Default `false`
- `is_downloadable`: Boolean, Default `true`
- `offline_package`: JSON, Nullable
- `created_at`, `updated_at`: Timestamps

### `quizzes` & `quiz_attempts`
Assessments with bilingual questions, passing scores, and retries.
- `quizzes`: `id`, `uuid`, `course_id`, `lesson_id`, `title`, `title_am`, `description`, `description_am`, `passing_score` (Default 70), `max_retries` (Default 3), `time_limit_minutes`, `questions` (JSON with English/Amharic options, correct answer index, explanations), `created_at`, `updated_at`
- `quiz_attempts`: `id`, `quiz_id`, `user_id`, `score`, `passed`, `answers` (JSON), `attempt_number`, `completed_at`, `created_at`, `updated_at`

### `certificates`
Official cryptographically verified digital credentials.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `course_id`: Foreign key -> `courses(id)` ON DELETE CASCADE
- `certificate_number`: Varchar(64), Unique (e.g. `CEE-CERT-2026-XXXXX`)
- `verification_code`: Varchar(64), Unique (e.g. `CEE-XXXX-YYYY`)
- `verification_hash`: Varchar(64), Unique (SHA-256 cryptographic hash)
- `issued_at`: Timestamp
- `metadata`: JSON (student name, university, course title, grade score, issuer signature)
- `created_at`, `updated_at`: Timestamps

### `course_enrollments` & `lesson_progress`
Individual student tracking, resume capability, and course progress percentage.
- `course_enrollments`: `id`, `uuid`, `user_id`, `course_id`, `status` (`active`, `completed`, `dropped`), `progress_percentage`, `last_accessed_lesson_id`, `started_at`, `completed_at`, `created_at`, `updated_at`
- `lesson_progress`: `id`, `user_id`, `course_id`, `lesson_id`, `status` (`started`, `completed`), `last_position_seconds`, `time_spent_seconds`, `completed_at`, `created_at`, `updated_at`

### `learning_streaks`, `learning_badges` & `user_badges`
Gamification engine with daily active streaks, points, and achievement badges.
- `learning_streaks`: `id`, `user_id` (Unique), `current_streak`, `longest_streak`, `last_activity_date`, `total_lessons_completed`, `total_quizzes_passed`, `total_points`, `created_at`, `updated_at`
- `learning_badges`: `id`, `slug` (Unique), `name`, `name_am`, `description`, `description_am`, `icon`, `criteria_type`, `criteria_threshold`, `created_at`, `updated_at`
- `user_badges`: `id`, `user_id`, `badge_id`, `awarded_at`, `created_at`, `updated_at`

### `learning_paths` & `learning_path_assignments`
University cohort curricula and assigned mandatory paths.
- `learning_paths`: `id`, `uuid`, `title`, `title_am`, `slug`, `description`, `description_am`, `banner_url`, `institution_type` (`university`, `school`, `all`), `target_university`, `is_mandatory`, `deadline`, `created_by_id`, `is_published`, `created_at`, `updated_at`
- `learning_path_courses`: `id`, `learning_path_id`, `course_id`, `order`, `created_at`, `updated_at`
- `learning_path_assignments`: `id`, `uuid`, `learning_path_id`, `user_id`, `assigned_by_id`, `university_name`, `due_date`, `status` (`assigned`, `in_progress`, `completed`), `completed_at`, `created_at`, `updated_at`

### `awareness_campaigns`
High-impact national campaigns with articles, video blocks, infographic tips, shareable assets, and platform share tracking.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `title`, `title_am`: Varchar(255)
- `slug`: Varchar(255), Unique
- `category`: Enum (matching 11 awareness categories)
- `description`, `description_am`: Text, Nullable
- `banner_url`: Varchar(500), Nullable
- `content_blocks`: JSON (articles, video embeds, infographic tips)
- `shareable_assets`: JSON (posters, social media card graphics)
- `linked_quiz_id`, `linked_course_id`: Foreign keys, Nullable
- `status`: Enum (`draft`, `scheduled`, `published`, `completed`)
- `start_date`, `end_date`: Timestamps, Nullable
- `target_institutions`: JSON (list of participating universities)
- `analytics`: JSON (`views_count`, `shares_count`, `quiz_completions`, platforms breakdown)
- `created_by_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `created_at`, `updated_at`: Timestamps

---

## 7. Sponsorship Marketplace, Creator Tools & Platform Analytics (Phase 6)

### `brand_profiles`
Verified corporate and brand sponsor accounts.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `company_name`: Varchar(255)
- `slug`: Varchar(255), Unique
- `tagline`: Varchar(255), Nullable
- `description`: Text, Nullable
- `industry`: Varchar(100)
- `logo_url`, `banner_url`, `website_url`: Varchar(500), Nullable
- `contact_email`: Varchar(255)
- `contact_phone`: Varchar(50), Nullable
- `social_links`: JSON (LinkedIn, Telegram, X, Facebook)
- `verified`: Boolean, Default `false`
- `created_at`, `updated_at`: Timestamps

### `sponsorship_packages`
Modular sponsorship packages published by tournament organizers, teams, venues, or creators.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `creator_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `title`: Varchar(255)
- `slug`: Varchar(255), Unique
- `target_type`: Enum (`tournament`, `team`, `player`, `venue`, `creator`, `league`, `awareness_campaign`, `platform`)
- `target_id`: Bigint unsigned, Nullable
- `tier`: Enum (`title`, `presenting`, `gold`, `silver`, `bronze`, `custom`)
- `price`: Decimal(12,2)
- `currency`: Varchar(3), Default `'ETB'`
- `benefits`: JSON (array of bulleted deliverables/benefits)
- `deliverables`: JSON (deliverable definitions with types)
- `duration_days`: Integer, Default `30`
- `audience_estimate`: Integer, Default `0`
- `is_published`: Boolean, Default `true`
- `created_at`, `updated_at`: Timestamps

### `sponsorships` (Enhanced)
Active contracts, applications, approvals, and ROI telemetry.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `contract_number`: Varchar(50), Unique (e.g. `CEE-SPON-2026-XXXXXX`)
- `sponsor_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `brand_profile_id`: Foreign key -> `brand_profiles(id)` ON DELETE SET NULL, Nullable
- `package_id`: Foreign key -> `sponsorship_packages(id)` ON DELETE SET NULL, Nullable
- `applicant_user_id`: Foreign key -> `users(id)` ON DELETE CASCADE
- `reviewed_by_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `package_name`: Varchar(255)
- `target_type`: Enum (matching targets)
- `target_id`: Bigint unsigned, Nullable
- `amount`: Decimal(12,2)
- `currency`: Varchar(3), Default `'ETB'`
- `contract_terms`: Text, Nullable
- `status`: Enum (`applied`, `under_review`, `approved`, `contract_signed`, `active`, `completed`, `rejected`, `cancelled`)
- `start_date`, `end_date`: Timestamps, Nullable
- `rejection_reason`: Text, Nullable
- `total_impressions`: Bigint unsigned, Default `0`
- `total_clicks`: Bigint unsigned, Default `0`
- `roi_metrics`: JSON (cached CTR, CPM, CPC, estimated media value, deliverables fulfillment rate)
- `created_at`, `updated_at`: Timestamps

### `sponsorship_deliverables`
Granular deliverables fulfillment tracking with proof uploads and audit verification.
- `id`: Bigint unsigned, Primary Key
- `uuid`: UUID, Unique
- `sponsorship_id`: Foreign key -> `sponsorships(id)` ON DELETE CASCADE
- `title`: Varchar(255)
- `description`: Text, Nullable
- `deliverable_type`: Varchar(50) (e.g. `stream_overlay`, `physical_banner`, `social_post`, `booth_space`)
- `due_date`: Timestamp, Nullable
- `evidence_url`: Varchar(500), Nullable (URL to image proof, clip, or document)
- `evidence_notes`: Text, Nullable
- `status`: Enum (`pending`, `submitted`, `verified`, `rejected`)
- `verified_by_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `verified_at`: Timestamp, Nullable
- `rejection_notes`: Text, Nullable
- `created_at`, `updated_at`: Timestamps

### `prize_pool_contributions`
Transparent crowdfunding and corporate sponsorship additions to verified tournament prize pools.
- `id`: Bigint unsigned, Primary Key
- `tournament_id`: Foreign key -> `tournaments(id)` ON DELETE CASCADE
- `sponsor_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `brand_profile_id`: Foreign key -> `brand_profiles(id)` ON DELETE SET NULL, Nullable
- `amount`: Decimal(12,2)
- `currency`: Varchar(3), Default `'ETB'`
- `donor_name`: Varchar(255), Default `'Community Supporter'`
- `is_anonymous`: Boolean, Default `false`
- `message`: Text, Nullable
- `status`: Varchar(50), Default `'paid'`
- `paid_at`: Timestamp, Nullable
- `created_at`, `updated_at`: Timestamps

### `brand_placements`
Telemetry tracking for media exposures across livestreams, physical banners, and digital overlays.
- `id`: Bigint unsigned, Primary Key
- `sponsorship_id`: Foreign key -> `sponsorships(id)` ON DELETE CASCADE
- `brand_profile_id`: Foreign key -> `brand_profiles(id)` ON DELETE CASCADE
- `placement_type`: Varchar(50)
- `location_identifier`: Varchar(255), Nullable
- `impressions_count`: Bigint unsigned, Default `0`
- `clicks_count`: Bigint unsigned, Default `0`
- `media_urls`: JSON, Nullable
- `created_at`, `updated_at`: Timestamps

### `platform_feature_flags`
Dynamic platform capability toggles.
- `id`: Bigint unsigned, Primary Key
- `key`: Varchar(100), Unique
- `name`: Varchar(255)
- `description`: Text, Nullable
- `is_enabled`: Boolean, Default `true`
- `audience`: Varchar(50), Default `'all'`
- `metadata`: JSON, Nullable
- `updated_by_id`: Foreign key -> `users(id)` ON DELETE SET NULL, Nullable
- `created_at`, `updated_at`: Timestamps



