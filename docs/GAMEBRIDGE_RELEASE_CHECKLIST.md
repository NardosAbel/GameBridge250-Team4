# GameBridge release checklist

- [x] Focused passport and profile tests passed on SQLite (12 tests, 69 assertions).
- [ ] Full PostgreSQL Pest suite after the passport change.
- [x] `npx tsc --noEmit` after the React changes (exit 0).
- [ ] `npm run build` after the React changes.
- [x] Live payment providers remain disabled outside sandbox.
- [x] Private player passports are not public.
- [ ] Browser walkthrough of register, tournament registration, result confirmation, and passport.
- [ ] Phone OTP delivery. Not a release blocker for an email-verified demo. Do not claim SMS works.

Known limitation: double elimination, Swiss, and group knockout stay unpublished.
