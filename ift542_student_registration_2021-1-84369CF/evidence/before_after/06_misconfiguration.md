# Evidence 6 — Security Misconfiguration

## Before (vulnerable pattern)
```php
// UNSAFE: verbose errors + default/hardcoded credentials in source control.
ini_set('display_errors', 1);
error_reporting(E_ALL);
$conn = mysqli_connect('localhost', 'root', 'root', 'student_reg');
```
Stack traces and DB errors shown to users leak file paths, query text and library versions,
which helps an attacker fingerprint and exploit the app. A default `root`/`root` credential
committed to source control gives anyone with repo access full database control.

## After (fixed)
Files: `src/bootstrap.php`, `.env.example`, `src/db.php`, `src/security_headers.php`

- `display_errors` is forced off and `error_reporting(0)` when `APP_ENV=production`
  (`src/bootstrap.php`), so users only ever see the app's own generic error text.
- DB credentials are read from `.env` (git-ignored) via `getenv()`, never hardcoded; `.env.example`
  ships placeholders only (`DB_PASS=change-me-locally`).
- A dedicated `student_reg_app` DB account (see README) is used instead of `root`, scoped to only
  the `student_reg` schema.
- `src/security_headers.php` sends `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`, `Permissions-Policy` and a restrictive CSP on every response.
- Dependency status: this app has zero third-party PHP packages (stdlib PDO/cURL only), so there
  is no `composer.lock` surface to go stale — documented explicitly in the README rather than
  left unstated.
