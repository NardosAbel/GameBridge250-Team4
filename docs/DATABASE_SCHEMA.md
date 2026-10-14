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
