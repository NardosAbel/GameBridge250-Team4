# GameBridge security review

## Fixed earlier on this branch and still in the tree

- Payment webhooks fail closed outside sandbox, require HMAC, and match amount in cents plus currency before tickets are issued.
- Disputed matches cannot be confirmed.
- Profile visibility honors followers-only and private settings.
- Chat sends are rejected when `isMuted()` is true.
- Ticket inventory release is claimed with `inventory_released_at` under a row lock.

## This session

- Public passport uses `Profile::isVisibleTo()` and returns 404 when the viewer may not see the profile.
- The profile page no longer labels an unverified email or phone as verified.
- The dashboard no longer renders competitions that are not in the database.
- Public tournament listing and detail now hide `draft` and `archived` events, and private events, unless the viewer organizes, staffs, or is registered for that event.
- `TournamentController` and `DisputeController` called `authorize()` on a base controller that does not provide it. Those calls now use `Gate`, so delete, status changes, and dispute detail no longer crash before the policy runs.

## Residual risk

- Sandbox webhooks are intentionally accepted when `PAYMENTS_SANDBOX=true`. Production must keep that flag false until a provider-specific verifier exists.
- Phone OTP is not delivered. The code is not returned outside local and testing.
- Historical inventory rows that the old notes marker skipped are not automatically repaired.
