# Campus Esports Ethiopia — API Documentation

This document describes the REST API endpoints available in the Campus Esports Ethiopia platform (Phase 1 & Phase 2). All API endpoints return standardized JSON structures with `Content-Type: application/json`.

---

## 1. Authentication & Identity (`/api/v1/auth`)

### Register
- **Endpoint:** `POST /api/v1/auth/register`
- **Auth:** Public (Rate-limited: 5 req/min)
- **Request Body:**
  ```json
  {
    "name": "Abebe Bikila",
    "email": "abebe@aau.edu.et",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "phone": "+251911223344",
    "gamer_tag": "HabeshaSniper",
    "university": "Addis Ababa University",
    "preferred_games": ["PUBG Mobile", "FC 25"]
  }
  ```
- **Response:** `201 Created` with Sanctum token, user object, and OTP notification trigger.

### Login
- **Endpoint:** `POST /api/v1/auth/login`
- **Auth:** Public (Rate-limited: 5 req/min)
- **Request Body:**
  ```json
  {
    "email": "abebe@aau.edu.et",
    "password": "Password123!",
    "device_name": "Web Browser"
  }
  ```
- **Response:** `200 OK` with `token` and `user`.

### Verify OTP
- **Endpoint:** `POST /api/v1/auth/verify-otp`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "type": "phone",
    "otp": "123456"
  }
  ```
- **Response:** `200 OK` with verification confirmation.

### Resend OTP
- **Endpoint:** `POST /api/v1/auth/resend-otp`
- **Auth:** `auth:sanctum` (Rate-limited: 3 req/min)
- **Request Body:**
  ```json
  {
    "type": "phone"
  }
  ```
- **Response:** `200 OK`

### Logout
- **Endpoint:** `POST /api/v1/auth/logout`
- **Auth:** `auth:sanctum`
- **Response:** `200 OK`

---

## 2. Profiles (`/api/v1/profile`)

### Get Current User Profile
- **Endpoint:** `GET /api/v1/profile`
- **Auth:** `auth:sanctum`
- **Response:** `200 OK` with profile, verification status, roles, platforms, availability, badges, achievements.

### Update Profile
- **Endpoint:** `PUT /api/v1/profile`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "handle": "habesha_sniper",
    "bio": "Competitive esports athlete at AAU.",
    "location": "Addis Ababa, Ethiopia",
    "university": "Addis Ababa University",
    "department": "Computer Science",
    "preferred_games": ["PUBG Mobile", "Valorant"],
    "roles": ["Sniper", "IGL"],
    "platforms": ["PC", "Mobile"],
    "availability": "Weekends & Evenings after 6 PM",
    "privacy_settings": {
      "profile_visibility": "public",
      "show_email": false,
      "allow_dms": true
    }
  }
  ```
- **Response:** `200 OK`

---

## 3. Social Network, Feed & Posts (`/api/v1`)

### Get Personalized Feed
- **Endpoint:** `GET /api/v1/feed`
- **Auth:** `auth:sanctum`
- **Query Parameters:** `page` (int)
- **Description:** Returns posts scored and ranked based on followed users, university/interest affinity, engagement velocity, and recency. Excludes blocked or muted accounts.

### Create Post
- **Endpoint:** `POST /api/v1/posts`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "content": "Looking forward to the inter-campus tournament! #EsportsEthiopia",
    "post_type": "text",
    "visibility": "public",
    "game_tags": ["PUBG Mobile"],
    "location_tag": "Addis Ababa",
    "media": [
      {
        "media_type": "image",
        "file_path": "social/media/clip1.jpg",
        "thumbnail_path": "social/media/thumbs/clip1.jpg",
        "sort_order": 0
      }
    ],
    "poll": {
      "question": "Which game should be featured next?",
      "options": ["Valorant", "Mobile Legends", "FC 25"],
      "expires_at": "2026-11-01T00:00:00Z"
    }
  }
  ```
- **Response:** `201 Created` with `PostResource`.

### Vote on Poll
- **Endpoint:** `POST /api/v1/posts/{post}/poll/vote`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "option_index": 0
  }
  ```

### Add Comment
- **Endpoint:** `POST /api/v1/posts/{post}/comments`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "body": "Count me in! Let's scrim tonight.",
    "parent_id": null
  }
  ```

### Reactions
- **Endpoint:** `POST /api/v1/social/react`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "reactable_type": "post",
    "reactable_id": 1,
    "type": "fire"
  }
  ```
  *(Supported reaction types: `like`, `love`, `fire`, `gg`, `trophy`, `cry`)*

### Follow / Unfollow
- **Endpoint:** `POST /api/v1/social/follow`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "followable_type": "user",
    "followable_id": 2
  }
  ```
  *(Supported targets: `user`, `team`, `community`, `tournament`)*

### Share Post
- **Endpoint:** `POST /api/v1/posts/{post}/share`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "caption": "Check out this crazy play!"
  }
  ```

### Bookmark Post
- **Endpoint:** `POST /api/v1/posts/{post}/bookmark`
- **Auth:** `auth:sanctum`

### Block User
- **Endpoint:** `POST /api/v1/social/block`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "target_user_id": 5
  }
  ```

### Mute User
- **Endpoint:** `POST /api/v1/social/mute`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "target_user_id": 5
  }
  ```

### Media Upload
- **Endpoint:** `POST /api/v1/media/upload`
- **Auth:** `auth:sanctum`
- **Content-Type:** `multipart/form-data`
- **Parameters:** `file` (image max 10MB or video max 100MB)
- **Response:** `200 OK` with file URL, path, thumbnail URL, and dimensions.

---

## 4. Teams (`/api/v1/teams`)

### List Teams
- **Endpoint:** `GET /api/v1/teams`
- **Auth:** `auth:sanctum`

### Create Team
- **Endpoint:** `POST /api/v1/teams`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "name": "Addis Ababa Gladiators",
    "tag": "AAG",
    "game": "PUBG Mobile",
    "bio": "Official varsity PUBG Mobile team for Addis Ababa University.",
    "university": "Addis Ababa University",
    "city": "Addis Ababa",
    "is_recruiting": true
  }
  ```
- **Response:** `201 Created`

### Get Team Details
- **Endpoint:** `GET /api/v1/teams/{team}`
- **Auth:** `auth:sanctum`
- **Description:** Returns team roster, active roles (owner, captain, coach, manager, player, substitute), achievements, and full roster history.

### Update Team
- **Endpoint:** `PUT /api/v1/teams/{team}`
- **Auth:** `auth:sanctum` (Policy: Captain / Owner / Manager)

### Send Invitation or Join Request
- **Endpoint:** `POST /api/v1/teams/{team}/invite`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "user_id": 4,
    "role": "player",
    "type": "invitation",
    "message": "We would love to have you on our starting roster!"
  }
  ```

### Respond to Invitation or Join Request
- **Endpoint:** `POST /api/v1/team-invitations/{invitation}/respond`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "action": "accept"
  }
  ```
  *(Supported actions: `accept`, `decline`, `cancel`)*

### Update Member Role
- **Endpoint:** `PUT /api/v1/teams/{team}/members/{member}/role`
- **Auth:** `auth:sanctum` (Policy: Captain / Owner)
- **Request Body:**
  ```json
  {
    "role": "captain"
  }
  ```

### Remove Member
- **Endpoint:** `DELETE /api/v1/teams/{team}/members/{member}`
- **Auth:** `auth:sanctum` (Policy: Captain / Owner or self-leave)

### Transfer Ownership
- **Endpoint:** `POST /api/v1/teams/{team}/transfer-ownership`
- **Auth:** `auth:sanctum` (Policy: Team Owner only)
- **Request Body:**
  ```json
  {
    "new_owner_id": 4
  }
  ```

---

## 5. Communities (`/api/v1/communities`)

### List Communities
- **Endpoint:** `GET /api/v1/communities`
- **Auth:** `auth:sanctum`
- **Query Parameters:** `type` (`university`, `game`, `city`, `creator`, etc.)

### Create Community
- **Endpoint:** `POST /api/v1/communities`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "name": "AAU Esports Club",
    "slug": "aau-esports-club",
    "description": "The central hub for all collegiate gaming at Addis Ababa University.",
    "type": "university",
    "visibility": "public",
    "rules": "1. Respect all players. 2. No toxicity. 3. Campus verification required for tournament reps."
  }
  ```

### Join / Request Access
- **Endpoint:** `POST /api/v1/communities/{community}/join`
- **Auth:** `auth:sanctum`

### Approve Member (Private/Approval Communities)
- **Endpoint:** `POST /api/v1/communities/{community}/members/{member}/approve`
- **Auth:** `auth:sanctum` (Policy: Community Leader / Moderator)

---

## 6. Recruitment Board (`/api/v1/recruitment`)

### List Recruitment Notices
- **Endpoint:** `GET /api/v1/recruitment`
- **Auth:** `auth:sanctum`
- **Query Filters:** `type` (`lft` / `lfp`), `game`, `rank`, `role`, `location`, `university`, `platform`

### Post Recruitment Notice
- **Endpoint:** `POST /api/v1/recruitment`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "type": "lfp",
    "team_id": 1,
    "game": "PUBG Mobile",
    "title": "Gladiators searching for dedicated Assaulter/Fragger",
    "description": "Must have competitive experience and be available for scrims Mon-Wed evenings.",
    "role_needed": "Fragger",
    "rank_requirement": "Ace Dominator",
    "platform": "Mobile",
    "university_requirement": "Addis Ababa University",
    "location": "Addis Ababa",
    "availability_requirement": "Evenings (6-9 PM)",
    "contact_info": "DM @habesha_sniper or email esports@aau.edu.et"
  }
  ```

### Close Recruitment Notice
- **Endpoint:** `POST /api/v1/recruitment/{recruitment}/close`
- **Auth:** `auth:sanctum` (Author or Team Owner)

---

## 7. Chat & Messaging (`/api/v1/chat`)

### Get or Create Channel
- **Endpoint:** `POST /api/v1/chat/channels`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "type": "team",
    "team_id": 1
  }
  ```
  *(Supported types: `direct`, `team`, `community`)*

### List Channel Messages
- **Endpoint:** `GET /api/v1/chat/channels/{channel}/messages`
- **Auth:** `auth:sanctum` (Must be channel member)

### Send Message
- **Endpoint:** `POST /api/v1/chat/channels/{channel}/messages`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "body": "Scrim starts in 15 minutes! @habesha_sniper be ready."
  }
  ```
  *Auto-detects `@handle` mentions and issues instant in-app/push alerts.*

---

## 8. Notifications (`/api/v1/notifications`)

### List Notifications
- **Endpoint:** `GET /api/v1/notifications`
- **Auth:** `auth:sanctum`

### Mark Notification as Read
- **Endpoint:** `PUT /api/v1/notifications/{id}/read`
- **Auth:** `auth:sanctum`

### Mark All as Read
- **Endpoint:** `POST /api/v1/notifications/mark-all-read`
- **Auth:** `auth:sanctum`

### Update Notification Preferences
- **Endpoint:** `PUT /api/v1/notifications/preferences`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "in_app": true,
    "email": true,
    "sms": false,
    "push": true
  }
  ```

---

## 9. Moderation & Trust & Safety (`/api/v1/moderation`)

### Submit Report
- **Endpoint:** `POST /api/v1/moderation/report`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "reportable_type": "post",
    "reportable_id": 14,
    "category": "harassment",
    "description": "User is using abusive language in comments."
  }
  ```

### List Moderation Queue
- **Endpoint:** `GET /api/v1/moderation/queue`
- **Auth:** `auth:sanctum` (Policy: Moderator / Admin)
- **Query Filters:** `status` (`pending`, `under_review`, `resolved`, `dismissed`)

### Execute Sanction / Moderation Decision
- **Endpoint:** `POST /api/v1/moderation/action`
- **Auth:** `auth:sanctum` (Policy: Moderator / Admin)
- **Request Body:**
  ```json
  {
    "report_id": 1,
    "action": "mute",
    "duration_minutes": 1440,
    "reason": "Repeated toxic behavior in public discussions."
  }
  ```
  *(Supported actions: `warn`, `mute`, `suspend`, `ban`, `delete_content`, `dismiss`)*
  *Generates an immutable audit log entry in `audit_logs`.*

---

## 10. Tournament Engine (`/api/v1/tournaments`)

### List Tournaments
- **Endpoint:** `GET /api/v1/tournaments`
- **Auth:** Public
- **Query Filters:** `status`, `game_id`, `format`, `platform`, `region`, `search`, `page`

### View Tournament Details & Brackets
- **Endpoint:** `GET /api/v1/tournaments/{tournament}`
- **Auth:** Public
- **Response:** Tournament metadata, approved participants, interactive bracket nodes, matches, standings, and active staff.

### Create Tournament
- **Endpoint:** `POST /api/v1/tournaments`
- **Auth:** `auth:sanctum` (Verified Organizer / Admin)
- **Request Body:**
  ```json
  {
    "title": "Ethiopian Collegiate Masters 2026",
    "game_id": 1,
    "format": "single_elimination",
    "match_format": "bo3",
    "platform": "pc",
    "region": "Addis Ababa",
    "capacity": 16,
    "entry_type": "free",
    "rules": "Standard esports rules apply. 15-minute disqualification timer.",
    "starts_at": "2026-11-01T10:00:00Z",
    "eligibility_rules": {
      "allowed_universities": ["Addis Ababa University", "HilCoE"],
      "require_student_id": true
    }
  }
  ```

### Update Tournament Lifecycle Status
- **Endpoint:** `PUT /api/v1/tournaments/{tournament}/status`
- **Auth:** `auth:sanctum` (Organizer / Admin)
- **Request Body:**
  ```json
  {
    "status": "registration_open"
  }
  ```
  *(Supported transitions: `draft` -> `published` -> `registration_open` -> `registration_closed` -> `check_in` -> `live` -> `completed` / `cancelled` -> `archived`)*

### Register for Tournament
- **Endpoint:** `POST /api/v1/tournaments/{tournament}/register`
- **Auth:** `auth:sanctum` (Enforces university, student verification, platform, and capacity checks)
- **Request Body (Solo):** `{}`
- **Request Body (Team):**
  ```json
  {
    "team_id": 3
  }
  ```

### Tournament Check-In
- **Endpoint:** `POST /api/v1/tournaments/{tournament}/check-in`
- **Auth:** `auth:sanctum` (Registered participant or Organizer override)

### Update Participant Seeding
- **Endpoint:** `PUT /api/v1/tournaments/{tournament}/seeding`
- **Auth:** `auth:sanctum` (Organizer / Staff)
- **Request Body:**
  ```json
  {
    "seeds": {
      "12": 1,
      "15": 2
    }
  }
  ```

### Generate Brackets
- **Endpoint:** `POST /api/v1/tournaments/{tournament}/bracket`
- **Auth:** `auth:sanctum` (Organizer / Staff)
- **Description:** Generates deterministic bracket nodes (Single Elim, Double Elim, Round Robin, Swiss, or Group Stage + Knockout) with power-of-2 seed placement, automatic bye advancement, and transactional database integrity.

### Assign Tournament Staff
- **Endpoint:** `POST /api/v1/tournaments/{tournament}/staff`
- **Auth:** `auth:sanctum` (Organizer / Super Admin)
- **Request Body:**
  ```json
  {
    "user_id": 9,
    "role": "referee"
  }
  ```
  *(Roles: `admin`, `co_organizer`, `referee`, `scorekeeper`, `caster`)*

---

## 11. Matches & Game Rooms (`/api/v1/matches`)

### View Match Details
- **Endpoint:** `GET /api/v1/matches/{match}`
- **Auth:** Public / Authenticated.
- **Privacy Security:** Restricted match room credentials (`room_id`, `room_password`, `room_server`, `room_instructions`) are returned **only** to the playing participants, assigned referees, tournament staff, and administrators. Unauthorized users receive `null`.

### Match Check-In / Ready-Up
- **Endpoint:** `POST /api/v1/matches/{match}/check-in`
- **Auth:** `auth:sanctum` (Match Participant)
- **Description:** Sets participant ready flag. When both participants ready up, match automatically transitions to `live`.

### Update Game Room Credentials
- **Endpoint:** `PUT /api/v1/matches/{match}/room`
- **Auth:** `auth:sanctum` (Organizer, Referee, or Staff)
- **Request Body:**
  ```json
  {
    "room_id": "LOBBY_99182",
    "room_password": "secure_pw_2026",
    "room_server": "Africa-East",
    "room_instructions": "Lobby created by referee. Join within 10 minutes."
  }
  ```

### Submit Match Result
- **Endpoint:** `POST /api/v1/matches/{match}/result`
- **Auth:** `auth:sanctum` (Match Captain, Organizer, or Referee)
- **Request Body:**
  ```json
  {
    "participant1_score": 2,
    "participant2_score": 1,
    "winner_id": 4,
    "evidence": [
      {
        "type": "screenshot",
        "url": "https://s3.campus-esports.et/evidence/match_12_score.png",
        "description": "Final scoreboard screenshot"
      }
    ]
  }
  ```

### Confirm Match Result
- **Endpoint:** `POST /api/v1/matches/{match}/confirm`
- **Auth:** `auth:sanctum` (Opponent Captain, Referee, or Organizer)
- **Description:** Confirms match score, advances winner to downstream bracket node, and triggers placement calculations if final match.

### Organizer Result Override
- **Endpoint:** `POST /api/v1/matches/{match}/override`
- **Auth:** `auth:sanctum` (Organizer / Referee / Admin)
- **Description:** Overrules match score and winner with cascading downstream invalidation and recalculation.

---

## 12. Disputes & Appeals (`/api/v1/disputes`)

### Open Dispute
- **Endpoint:** `POST /api/v1/matches/{match}/disputes`
- **Auth:** `auth:sanctum` (Match Participant)
- **Request Body:**
  ```json
  {
    "reason": "Opponent used illegal ringers on roster.",
    "evidence_urls": ["https://s3.campus-esports.et/evidence/dispute_proof.png"]
  }
  ```
  *Pauses downstream bracket advancement and notifies tournament staff.*

### Resolve Dispute
- **Endpoint:** `POST /api/v1/disputes/{dispute}/resolve`
- **Auth:** `auth:sanctum` (Organizer, Referee, or Admin)
- **Request Body:**
  ```json
  {
    "action_taken": "overturn_result",
    "participant1_score": 2,
    "participant2_score": 0,
    "winner_id": 5,
    "resolution_notes": "Official review confirms player substitution violation. Match overturned."
  }
  ```
  *(Supported actions: `confirm_result`, `overturn_result`, `rematch`, `disqualify_participant`)*

---

## 13. Rankings & Leaderboards (`/api/v1/rankings`)

### View Leaderboards
- **Endpoint:** `GET /api/v1/rankings`
- **Auth:** Public
- **Query Filters:** `game_id`, `season_id`, `page`

### View Ranking Snapshots
- **Endpoint:** `GET /api/v1/rankings/snapshots`
- **Auth:** Public
- **Query Filters:** `game_id`, `season_id`

### Capture Ranking Snapshot
- **Endpoint:** `POST /api/v1/rankings/snapshots`
- **Auth:** `auth:sanctum` (Admin / Organizer)
- **Request Body:**
  ```json
  {
    "game_id": 1,
    "season_id": 1,
    "type": "seasonal"
  }
  ```

---

## 14. Venues & Arenas (`/api/v1/venues`)

### Discover Venues
- **Endpoint:** `GET /api/v1/venues`
- **Auth:** Public
- **Query Parameters:** `city`, `facilities`, `search`, `latitude`, `longitude`, `radius_km`
- **Description:** Returns gaming lounges, LAN cafes, and arenas. If coordinates are provided, calculates Haversine distance and orders by proximity.

### Get Venue Details
- **Endpoint:** `GET /api/v1/venues/{venue}`
- **Auth:** Public
- **Description:** Returns venue specifications, photos, opening hours, pricing, reviews, and upcoming events.

### Create Venue Profile
- **Endpoint:** `POST /api/v1/venues`
- **Auth:** `auth:sanctum` (Organizer / Venue Owner)

### Assign Venue Staff
- **Endpoint:** `POST /api/v1/venues/{venue}/staff`
- **Auth:** `auth:sanctum` (Venue Owner / Manager)
- **Request Body:**
  ```json
  {
    "user_id": 12,
    "role": "scanner"
  }
  ```

### Submit Venue Review
- **Endpoint:** `POST /api/v1/venues/{venue}/reviews`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "rating": 5,
    "comment": "Top-tier fiber connection and PS5 setups!"
  }
  ```

### Venue Analytics
- **Endpoint:** `GET /api/v1/venues/{venue}/analytics`
- **Auth:** `auth:sanctum` (Venue Staff / Manager)

---

## 15. Events (`/api/v1/events`)

### List Events
- **Endpoint:** `GET /api/v1/events`
- **Auth:** Public
- **Query Parameters:** `venue_id`, `event_type`, `status`

### Get Event Details
- **Endpoint:** `GET /api/v1/events/{event}`
- **Auth:** Public

### Create Event
- **Endpoint:** `POST /api/v1/events`
- **Auth:** `auth:sanctum` (Organizer / Venue Manager)

---

## 16. Ticketing & Gate Entry (`/api/v1/tickets`)

### List Ticket Tiers
- **Endpoint:** `GET /api/v1/tickets/types`
- **Auth:** Public
- **Query Parameters:** `event_id`, `tournament_id`

### Create Ticket Tier
- **Endpoint:** `POST /api/v1/tickets/types`
- **Auth:** `auth:sanctum` (Event Organizer / Venue Manager)

### Checkout Ticket Order
- **Endpoint:** `POST /api/v1/tickets/checkout`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "items": [
      { "ticket_type_id": 1, "quantity": 2 }
    ],
    "promo_code": "STUDENT25"
  }
  ```
- **Description:** Uses database pessimistic row-locking (`lockForUpdate()`) inside transactions to completely prevent ticket overselling.

### Get My Tickets
- **Endpoint:** `GET /api/v1/tickets/my-tickets`
- **Auth:** `auth:sanctum`
- **Description:** Returns attendee passes with HMAC-SHA256 signed QR payloads.

### Transfer Ticket
- **Endpoint:** `POST /api/v1/tickets/{ticket}/transfer`
- **Auth:** `auth:sanctum` (Ticket Holder)
- **Request Body:**
  ```json
  {
    "recipient_email": "friend@university.edu.et"
  }
  ```

### Real-Time Gate Scan Validation
- **Endpoint:** `POST /api/v1/tickets/scan`
- **Auth:** `auth:sanctum` (Authorized Scanner / Staff)
- **Request Body:**
  ```json
  {
    "qr_payload": "{\"ref\":\"<uuid>\",\"sig\":\"<signature>\"}",
    "venue_id": 1,
    "event_id": 1,
    "device_id": "scanner-01"
  }
  ```
- **Responses:**
  - `200 OK`: Valid pass, checked in.
  - `409 Conflict`: Duplicate scan rejection.
  - `422 Unprocessable`: Wrong event, expired, revoked, or invalid signature.

### Sync Offline Scans (Batch)
- **Endpoint:** `POST /api/v1/tickets/sync-scans`
- **Auth:** `auth:sanctum` (Authorized Scanner / Staff)
- **Request Body:**
  ```json
  {
    "scans": [
      {
        "qr_payload": "{\"ref\":\"<uuid>\",\"sig\":\"<signature>\"}",
        "device_id": "offline-tablet-01",
        "scanned_at": "2026-10-15T10:00:00Z"
      }
    ]
  }
  ```

### Ticketing & Attendance Reports
- **Endpoint:** `GET /api/v1/tickets/reports`
- **Auth:** `auth:sanctum` (Organizer / Admin)
- **Query Parameters:** `event_id`, `tournament_id`

### Export Attendance CSV
- **Endpoint:** `GET /api/v1/tickets/reports/export`
- **Auth:** `auth:sanctum` (Organizer / Admin)

---

## 17. Local Payments, Webhooks, Refunds & Payouts (`/api/v1/payments`)

### Initiate Local Payment
- **Endpoint:** `POST /api/v1/payments/initiate`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "order_id": 1,
    "provider": "telebirr"
  }
  ```
- *(Supported providers: `telebirr`, `chapa`, `mpesa`, `card`, `bank_transfer`, `manual`)*

### Payment Webhook (Idempotent)
- **Endpoint:** `POST /api/v1/payments/webhook/{provider}`
- **Auth:** Public (Signature Verified)
- **Description:** Verifies webhook signature, acknowledges duplicate calls idempotently without double-issuing tickets, and automatically marks order as paid and issues signed QR tickets.

### Request Refund
- **Endpoint:** `POST /api/v1/payments/orders/{order}/refund`
- **Auth:** `auth:sanctum` (Order Owner)
- **Request Body:**
  ```json
  {
    "amount": 200.00,
    "reason": "Exam conflict",
    "reason_category": "attendee_unavailable"
  }
  ```

### Process Refund
- **Endpoint:** `POST /api/v1/payments/refunds/{refund}/process`
- **Auth:** `auth:sanctum` (Organizer / Venue Manager / Admin)
- **Request Body:**
  ```json
  {
    "approve": true
  }
  ```
- *Revokes tickets immediately upon refund approval.*

### Request Organizer Payout
- **Endpoint:** `POST /api/v1/payments/payouts`
- **Auth:** `auth:sanctum` (Organizer)
- **Request Body:**
  ```json
  {
    "gross_amount": 5000.00,
    "provider": "cbe",
    "account_number": "1000123456789"
  }
  ```
- *Transparently deducts standard 5% platform fee.*

---

## 18. Digital Awareness and Learning Hub

### List Courses
- **Endpoint:** `GET /api/v1/courses`
- **Auth:** Public
- **Query Parameters:** `category`, `level`, `search`, `per_page`
- **Supported Categories:** `online_safety`, `scam_awareness`, `cyberbullying_prevention`, `healthy_gaming`, `digital_literacy`, `ai_basics`, `coding`, `content_creation`, `esports_careers`, `data_skills`, `digital_entrepreneurship`

### Get Course Details
- **Endpoint:** `GET /api/v1/courses/{id}`
- **Auth:** Public (Includes user enrollment progress if authenticated)

### Enroll in Course
- **Endpoint:** `POST /api/v1/education/courses/{id}/enroll`
- **Auth:** `auth:sanctum`

### View Lesson Content
- **Endpoint:** `GET /api/v1/education/lessons/{id}`
- **Auth:** `auth:sanctum` (or preview allowed)
- **Description:** Returns article content, video player embed URL, PDF guide links, and interactive safety exercises.

### Complete Lesson
- **Endpoint:** `POST /api/v1/education/lessons/{id}/complete`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "time_spent_seconds": 180
  }
  ```
- **Description:** Advances course enrollment percentage, increments daily learning streak, awards points, and checks achievement badges.

### Download Offline Lesson Package
- **Endpoint:** `GET /api/v1/education/lessons/{id}/offline-package`
- **Auth:** `auth:sanctum`
- **Description:** Bundles content, quizzes, and exercises for offline caching in low-connectivity campus environments.

### Sync Offline Completed Lessons (Batch)
- **Endpoint:** `POST /api/v1/education/lessons/sync-offline`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "lessons": [
      { "lesson_id": 1, "time_spent_seconds": 300 }
    ]
  }
  ```

### Take Quiz Assessment
- **Endpoint:** `GET /api/v1/education/quizzes/{id}`
- **Auth:** `auth:sanctum`
- **Description:** Returns sanitized assessment questions without leaking correct answer indices.

### Submit Quiz Assessment
- **Endpoint:** `POST /api/v1/education/quizzes/{id}/submit`
- **Auth:** `auth:sanctum`
- **Request Body:**
  ```json
  {
    "answers": {
      "q1": 1,
      "q2": 1
    }
  }
  ```
- **Description:** Evaluates score, enforces max retries, issues official certificate if passing course requirements.

### Public Certificate Verification
- **Endpoint:** `GET /api/v1/certificates/verify/{code}`
- **Auth:** Public
- **Description:** Cryptographically verifies certificate authenticity by verification code or hash; returns student name, university, course, score, and issue date.

### Learner Progress Dashboard
- **Endpoint:** `GET /api/v1/education/dashboard`
- **Auth:** `auth:sanctum`
- **Description:** Returns current day streak, points, enrolled courses, unlocked badges, certificates wallet, and university-assigned learning paths.

### List & Assign Learning Paths
- **Endpoint:** `GET /api/v1/learning-paths` (Public)
- **Endpoint:** `POST /api/v1/education/learning-paths/{id}/assign` (University Admin / School Admin / Super Admin)
- **Request Body:**
  ```json
  {
    "university_name": "Addis Ababa University",
    "due_date": "2026-11-30T23:59:59Z"
  }
  ```

### Awareness Campaigns & Engagement
- **Endpoint:** `GET /api/v1/campaigns` (Public)
- **Endpoint:** `GET /api/v1/campaigns/{id}` (Public - Records view count)
- **Endpoint:** `POST /api/v1/campaigns/{id}/share` (Public - Records share event on Telegram, X, Facebook, Direct link)

---

## 7. Sponsorship Marketplace, Creator Tools & Platform Analytics (Phase 6)

### Brand Profiles
- **Endpoint:** `GET /api/v1/sponsors/brands`
- **Auth:** Public
- **Query Params:** `verified_only=1`, `industry=Telecommunications`, `search=Ethio`
- **Description:** Returns paginated list of verified brands, company details, taglines, and social links.

- **Endpoint:** `POST /api/v1/sponsors/brands`
- **Auth:** `auth:sanctum` (Sponsor, Organizer, Super Admin)
- **Request Body:**
  ```json
  {
    "company_name": "Ethio Telecom",
    "tagline": "Connecting the Nation",
    "industry": "Telecommunications",
    "contact_email": "sponsors@ethiotelecom.et",
    "website_url": "https://www.ethiotelecom.et"
  }
  ```

- **Endpoint:** `PUT /api/v1/sponsors/brands/{id}`
- **Auth:** `auth:sanctum` (Brand Owner, Super Admin)

### Sponsorship Packages
- **Endpoint:** `GET /api/v1/sponsors/packages`
- **Auth:** Public
- **Query Params:** `target_type=tournament`, `tier=title`, `max_price=100000`
- **Description:** Returns available sponsorship inventory with deliverables, benefits, duration, and audience estimates.

- **Endpoint:** `POST /api/v1/sponsors/packages`
- **Auth:** `auth:sanctum` (Organizer, Sponsor, Creator, Team Captain, Venue Admin, Super Admin)
- **Request Body:**
  ```json
  {
    "title": "AAU Grand Finals Title Sponsorship",
    "target_type": "tournament",
    "tier": "title",
    "price": 150000.00,
    "currency": "ETB",
    "benefits": ["Stage LED Branding", "Livestream Overlays"],
    "deliverables": [
      {"title": "Lower Third Broadcast Overlay", "deliverable_type": "stream_overlay"},
      {"title": "Physical Stage Banner", "deliverable_type": "stage_banner"}
    ],
    "duration_days": 30,
    "audience_estimate": 25000
  }
  ```

### Sponsorship Applications & Deals
- **Endpoint:** `GET /api/v1/sponsorships`
- **Auth:** `auth:sanctum`
- **Description:** Returns user's applications or reviewed sponsorship deals.

- **Endpoint:** `POST /api/v1/sponsorships/apply`
- **Auth:** `auth:sanctum` (Sponsor, Organizer, Super Admin)
- **Request Body:**
  ```json
  {
    "brand_profile_id": 1,
    "package_id": 1
  }
  ```

- **Endpoint:** `POST /api/v1/sponsorships/{id}/review`
- **Auth:** `auth:sanctum` (Package Owner / Target Admin / Super Admin)
- **Request Body:**
  ```json
  {
    "approve": true,
    "contract_terms": "Formal agreement signed under Ethiopian Esports Federation rules."
  }
  ```

### Deliverable Proof & Verification
- **Endpoint:** `POST /api/v1/sponsorships/deliverables/{id}/evidence`
- **Auth:** `auth:sanctum` (Deliverable Fulfiller / Applicant / Organizer)
- **Request Body:**
  ```json
  {
    "evidence_url": "https://campusesports.et/evidence/overlay_proof.png",
    "evidence_notes": "Aired during Grand Finals Game 3 broadcast"
  }
  ```

- **Endpoint:** `POST /api/v1/sponsorships/deliverables/{id}/verify`
- **Auth:** `auth:sanctum` (Brand Owner / Reviewer / Super Admin)
- **Request Body:**
  ```json
  {
    "verified": true
  }
  ```

### Performance Reports & ROI Export
- **Endpoint:** `GET /api/v1/sponsorships/{id}/report`
- **Auth:** `auth:sanctum` (Deal Parties, Super Admin)
- **Description:** Computes real-time impressions, clicks, CTR %, CPM, CPC, estimated media value in ETB, and deliverables fulfillment rate %.

- **Endpoint:** `GET /api/v1/sponsorships/{id}/report/export`
- **Auth:** `auth:sanctum`
- **Description:** Generates dynamic, formatted CSV report stream for sponsorship ROI presentations.

### Transparent Tournament Prize Pool Funding
- **Endpoint:** `GET /api/v1/tournaments/{id}/prize-pool`
- **Auth:** Public
- **Description:** Returns total prize pool, community/brand contributions, and top contributors leaderboard.

- **Endpoint:** `POST /api/v1/tournaments/{id}/prize-pool/contribute`
- **Auth:** `auth:sanctum` (Optional auth - allows anonymous community backing)
- **Request Body:**
  ```json
  {
    "amount": 500.00,
    "donor_name": "AAU Esports Guild",
    "is_anonymous": false,
    "message": "Good luck to all student teams!"
  }
  ```

### Creator Tools & Studio Insights
- **Endpoint:** `GET /api/v1/creator/insights`
- **Auth:** `auth:sanctum` (Creator, Organizer, Super Admin)
- **Description:** Returns post performance, audience breakdown by university, top games, and tailored brand sponsorship opportunities.

### Executive Platform Analytics & Admin Control
- **Endpoint:** `GET /api/v1/admin/overview`
- **Auth:** `auth:sanctum` (Super Admin)
- **Description:** Returns executive cross-domain metrics and feature flags.

- **Endpoint:** `GET /api/v1/admin/analytics`
- **Auth:** `auth:sanctum` (Super Admin)
- **Description:** Detailed executive metrics: users, retention (WAU/MAU), tournament completion rate, dispute rate, gate attendance %, ticketing revenue, university participation ranking, learning completions, and sponsorship volume.

- **Endpoint:** `GET /api/v1/admin/analytics/export`
- **Auth:** `auth:sanctum` (Super Admin)
- **Description:** Dynamic platform executive analytics CSV export.

- **Endpoint:** `GET /api/v1/admin/feature-flags`
- **Auth:** `auth:sanctum` (Super Admin)

- **Endpoint:** `PUT /api/v1/admin/feature-flags/{key}`
- **Auth:** `auth:sanctum` (Super Admin)
- **Request Body:**
  ```json
  {
    "is_enabled": false
  }
  ```

- **Endpoint:** `GET /api/v1/admin/system-health`
- **Auth:** `auth:sanctum` (Super Admin)



