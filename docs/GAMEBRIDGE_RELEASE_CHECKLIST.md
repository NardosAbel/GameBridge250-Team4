# GameBridge release checklist

- [x] Focused passport and profile tests passed on SQLite (12 tests, 69 assertions).
- [x] Tournament visibility tests passed on SQLite (4 tests, 15 assertions).
- [x] Refund revocation tests passed on SQLite (4 tests, 21 assertions).
- [x] Landing count test and home page test passed on SQLite (2 tests, 19 assertions).
- [x] PostgreSQL Pest exited 0 (173 `.env` warnings, 750 assertions, no failures). That process started before the visibility, refund, and landing commits.
- [x] `npx tsc --noEmit` exited 0 after the landing page edit.
- [x] `npm run build` exited 0 after the rankings and landing edits (Vite, about 9s on the second run).
- [x] Live payment providers remain disabled outside sandbox.
- [x] Private player passports and unpublished tournaments are not public.
- [ ] Browser walkthrough of register, tournament registration, result confirmation, and passport.
- [ ] Phone OTP delivery. Not a release blocker for an email-verified demo. Do not claim SMS works.

Known limitation: double elimination, Swiss, and group knockout stay unpublished.
