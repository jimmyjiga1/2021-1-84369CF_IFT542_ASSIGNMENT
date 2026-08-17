# Evidence 4 — CSRF on Course Registration

## Before (vulnerable pattern)
```html
<!-- no token; any site can render an auto-submitting form targeting this URL -->
<form method="post" action="/courses.php">
    <input type="hidden" name="course_id" value="3">
    <button>Register</button>
</form>
```
If a logged-in student visits a malicious page containing an auto-submitting copy of this form,
their browser sends the request with their valid session cookie attached, enrolling them in a
course they never chose — because the server has no way to tell this request apart from one the
student intentionally submitted from the real site.

## After (fixed)
File: `src/csrf.php`, `public/courses.php`

```php
// src/csrf.php
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403); exit('Invalid or missing CSRF token.');
    }
}
```
```php
<!-- public/courses.php -->
<form method="post" action="/courses.php">
    <?= csrf_field() ?> <!-- hidden input with the per-session token -->
    <input type="hidden" name="course_id" value="3">
    <button>Register</button>
</form>
```
A third-party page cannot read the student's session token (it's never exposed to other
origins), so it cannot include a valid value in its forged form; `verify_csrf()` then rejects
the request with HTTP 403. The session cookie is additionally set with `SameSite=Lax`
(`src/auth.php`), which stops the browser from attaching it to most cross-site POST requests
in the first place — a second, independent layer.
