# Student Registration Web Application — IFT542 Practical Assignment

A minimal PHP/MySQL implementation used as the basis for Tasks 1–3: STRIDE threat modeling,
authentication/SQL-injection remediation, and web defenses + incident response.

## Requirements
- PHP 8.1+ with `pdo_mysql` and `curl` extensions
- MySQL 8.0+ (or MariaDB 10.6+)
- No Composer packages required — this app uses only PHP's standard library.

## Setup

1. Create the database and tables:
   ```bash
   mysql -u root -p < migrations/schema.sql
   ```
2. Create a dedicated, least-privilege application account (do not use `root`):
   ```sql
   CREATE USER 'student_reg_app'@'localhost' IDENTIFIED BY 'choose-a-local-password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON student_reg.* TO 'student_reg_app'@'localhost';
   FLUSH PRIVILEGES;
   ```
3. Copy the environment template and fill in the values from step 2:
   ```bash
   cp .env.example .env
   ```
4. Seed fictitious test data (also hashes the test passwords correctly for your PHP build):
   ```bash
   php migrations/seed.php
   ```
5. Run the built-in PHP server with `public/` as the document root:
   ```bash
   php -S localhost:8000 -t public
   ```
6. Visit `http://localhost:8000/login.php`.

## Test Accounts (fictitious — printed by seed.php)
| Role | Email | Password |
|---|---|---|
| Student | student1@example.test | Student123! |
| Student | student2@example.test | Student123! |
| Admin | admin@example.test | AdminPass123! |

## Running the Tests
```bash
php tests/run_tests.php
```
Exercises: valid login succeeds, invalid credentials are rejected, SQL-injection-shaped input
does not change query results, and stored passwords are bcrypt/Argon2id hashes rather than
plaintext.

## Project Layout
```
public/          Entry points (web root — point your server here, not the project root)
src/             Application logic: db, auth, csrf, validation, logging, SSRF-safe fetch
migrations/      schema.sql + seed.php
storage/         uploads/ and logs/ — kept outside public/, not web-accessible
evidence/        before/after vulnerability write-ups for the report
docs/            IR runbook, ethics statement
tests/           reproducible test script
```

## Security Notes
- `.env` is git-ignored; only `.env.example` (placeholders) is committed. See `.gitignore`.
- Sessions use `HttpOnly`, `SameSite=Lax` cookies and are regenerated on login.
- All DB access goes through parameterized queries in `src/repository.php` — see
  `evidence/before_after/01_sqli.md`.
- Passwords are hashed with Argon2id (falls back to bcrypt if unavailable).
- CSRF tokens protect every state-changing form; CSP and standard security headers are applied
  to every response (`src/security_headers.php`).
- The course "resource preview" feature only fetches allowlisted HTTPS hosts and blocks
  loopback/private/link-local/metadata addresses — see `evidence/before_after/05_ssrf.md`.
- Dependency status: zero third-party packages; only PHP core extensions (`pdo_mysql`, `curl`,
  `fileinfo`) are required, so there is no dependency-vulnerability surface to track for this
  submission.

## Authorised-Lab Restriction
This project is for local/lab use only, with fictitious data. Do not point it at, or test it
against, any FUT Minna system, public website, or third-party service.
