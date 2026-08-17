# Evidence 1 — SQL Injection in Login

## Before (vulnerable pattern — do not deploy)
File: `public/login.php` (original prototype version)

```php
// UNSAFE: raw user input concatenated directly into SQL text.
$email = $_POST['email'];
$password = $_POST['password'];
$sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);
if ($user) {
    // logged in
}
```

**Why this is unsafe:** `$email` becomes part of the SQL grammar, not just data. Submitting
`' OR '1'='1' -- ` as the email turns the query into
`SELECT * FROM users WHERE email = '' OR '1'='1' -- ' AND password = '...'`, which matches
every row and lets an attacker log in as the first user in the table without knowing any
password. The same input could equally be used with `UNION SELECT` to read arbitrary columns
(e.g. `password_hash`) from `users`, which is the Information Disclosure threat in the Task 1
STRIDE worksheet.

**Affected data flow:** Student → P1 Login → D1 Users DB (see Task 1 DFD).

## After (fixed)
File: `public/login.php` + `src/repository.php::find_user_by_email()`

```php
// src/repository.php
function find_user_by_email(string $email): ?array
{
    $pdo = get_pdo(); // ATTR_EMULATE_PREPARES = false, see src/db.php
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    return $stmt->fetch() ?: null;
}
```

```php
// public/login.php
$user = find_user_by_email($email);
if ($user && password_verify($password, $user['password_hash'])) {
    // logged in — password is checked separately via password_verify(), never in the SQL text
}
```

**How parameterization separates data from code:** with `PDO::ATTR_EMULATE_PREPARES` set to
`false`, the query text (`SELECT ... WHERE email = :email`) is sent to the MySQL server and
compiled/planned *before* the value of `:email` is ever transmitted. The bound value is sent
afterwards in a separate protocol message and is only ever interpreted as the literal contents
of that one parameter — the server has no mechanism to reinterpret it as SQL syntax, regardless
of what characters it contains. `' OR '1'='1' -- ` is therefore compared literally against the
`email` column and simply fails to match any row.
