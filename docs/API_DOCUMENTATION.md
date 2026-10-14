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
