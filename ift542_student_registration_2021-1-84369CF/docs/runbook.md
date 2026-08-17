# Incident Response Runbook — Student Registration Web Application

Scope: local/lab deployment only, fictitious data. Owner: [your name], IFT542.

## 1. Preparation
- Maintain this runbook, the `.env.example`, and `migrations/schema.sql` under version control.
- Keep `storage/logs/security.log` and the `audit_log` DB table as the two evidence sources.
- Confirm before every test session that the app points at the local dummy database only
  (check `DB_HOST` in `.env`) — never a shared or production system.
- Maintain a contact list: module lecturer, and (if applicable) the lab administrator.

## 2. Identification
- Detect via `storage/logs/security.log` (grep for `login_failed`, `access_denied`,
  `ssrf_blocked`, `validation_rejected`) and the `audit_log` table (`SELECT * FROM audit_log
  ORDER BY created_at DESC LIMIT 50;`).
- Triage criteria: a burst of `login_failed` for one account (>5 in 5 minutes) = suspected
  brute force; repeated `access_denied` from one session = suspected privilege-escalation
  attempt; any `ssrf_blocked` entry = suspected SSRF probing.
- Record the first-seen timestamp, affected account/IP, and event type.

## 3. Containment
- Short-term: the app already auto-locks an account for 5 minutes after 5 failed logins
  (`record_failed_login()` in `src/repository.php`) — no manual action needed for brute force.
- For a suspected compromised session: delete the row from the session store / ask the user to
  log out everywhere, which invalidates `SRWA_SESSID`.
- For a suspected compromised account: set `locked_until` far in the future for that user and
  require a password reset before re-enabling.
- If a vulnerability (not just an attack attempt) is confirmed, take the affected route out of
  service (comment out the route) until patched.

## 4. Eradication
- Identify root cause using the before/after evidence in `evidence/before_after/` as a checklist
  (unparameterized query, missing output encoding, missing CSRF token, missing SSRF allowlist,
  or a misconfiguration).
- Apply/confirm the corresponding fix is deployed; re-run `tests/run_tests.php`.
- Rotate any credential that may have been exposed (DB password in `.env`, admin account
  password) even if exposure is only suspected.

## 5. Recovery
- Restore service to the affected route once the fix is verified in a test run.
- Re-seed or restore the database from `migrations/schema.sql` + `migrations/seed.php` if data
  integrity is in doubt (fictitious data only, so this is low-cost).
- Monitor `security.log` closely for the following 24 hours (or session) for recurrence.

## 6. Lessons Learned
- Log: what was detected, how, what was affected, what was done, and the time from detection to
  containment.
- Update this runbook and `evidence/before_after/` if a new vulnerability class was found.
- Add a corresponding case to `tests/run_tests.php` so a regression is caught automatically next
  time.

---
### Sample Incident Record (fictitious, for grading evidence)
| Field | Value |
|---|---|
| Detected | `security.log` entry, `login_failed` x6 for `student1@example.test` within 90s |
| Classification | Suspected credential brute force |
| Containment | Automatic lockout (5 min) triggered by built-in control |
| Eradication | N/A — control functioned as designed |
| Recovery | Account unlocked automatically after cooldown |
| Lesson | Confirmed lockout threshold behaves correctly; no runbook change needed |
