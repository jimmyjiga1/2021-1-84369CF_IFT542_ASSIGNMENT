<?php
/**
 * Reproducible test script for Task 2's required evidence.
 * Run against the seeded local database:
 *   php tests/run_tests.php
 *
 * This talks to the same repository functions the app uses (not a mock), so it
 * exercises the real parameterized queries and password hashing.
 */

require __DIR__ . '/../src/repository.php';

$pass = 0;
$fail = 0;

function check(string $label, bool $condition): void
{
    global $pass, $fail;
    if ($condition) {
        echo "  [PASS] $label\n";
        $pass++;
    } else {
        echo "  [FAIL] $label\n";
        $fail++;
    }
}

echo "== Test 1: valid login works ==\n";
$user = find_user_by_email('student1@example.test');
check('seeded user found', $user !== null);
check('correct password verifies', $user && password_verify('Student123!', $user['password_hash']));

echo "\n== Test 2: invalid credentials are rejected ==\n";
$user2 = find_user_by_email('student1@example.test');
check('wrong password fails verification', $user2 && !password_verify('WrongPassword!', $user2['password_hash']));
$noUser = find_user_by_email('does-not-exist@example.test');
check('unknown email returns no user', $noUser === null);

echo "\n== Test 3: SQL-injection-shaped input does not change query meaning ==\n";
$injection = "' OR '1'='1";
$result = find_user_by_email($injection);
check('injection-shaped email matches no row (parameterized query)', $result === null);

$injection2 = "nonexistent' UNION SELECT id, matric_no, full_name, email, password_hash, role, 0, NULL, created_at FROM users -- ";
$result2 = find_user_by_email($injection2);
check('UNION-shaped email matches no row (parameterized query)', $result2 === null);

echo "\n== Test 4: stored passwords are not plaintext ==\n";
$user3 = find_user_by_email('admin@example.test');
check('admin user found', $user3 !== null);
if ($user3) {
    $hash = $user3['password_hash'];
    check('stored value is not the known plaintext password', $hash !== 'AdminPass123!');
    check('stored value looks like a bcrypt/argon2id hash', (bool) preg_match('/^\$(2y|argon2id)\$/', $hash));
}

echo "\n---\n$pass passed, $fail failed.\n";
exit($fail > 0 ? 1 : 0);
