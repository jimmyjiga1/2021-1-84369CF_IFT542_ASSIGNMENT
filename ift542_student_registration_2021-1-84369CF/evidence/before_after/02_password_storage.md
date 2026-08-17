# Evidence 2 — Password Storage

## Before (vulnerable pattern)
```php
// UNSAFE: plaintext comparison, plaintext storage.
$sql = "INSERT INTO users (email, password) VALUES ('$email', '$password')";
// ... and at login:
if ($password === $row['password']) { /* logged in */ }
```
A leaked `users` table (via the SQLi above, a backup file, or DB access) directly exposes every
account's real password, which is also commonly reused on other systems by the same person.

## After (fixed)
File: `migrations/seed.php`, `public/register.php`, `public/login.php`

```php
$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
$hash = password_hash($password, $algo);   // registration: store only this
// ...
password_verify($password, $user['password_hash']); // login: constant-time, salt embedded in hash
```

**Evidence of hashing (run after seeding):**
```sql
SELECT email, password_hash FROM users LIMIT 1;
-- password_hash begins with $argon2id$ or $2y$ (bcrypt), never the plaintext password.
```
Argon2id/bcrypt are deliberately slow and include a per-password random salt, so identical
passwords produce different hashes and offline brute-forcing is computationally expensive.
