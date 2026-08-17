<?php
/**
 * Seeds fictitious test data. Run once after schema.sql:
 *   php migrations/seed.php
 *
 * Passwords are hashed here (never hardcode a hash string picked at random) so the
 * algorithm used matches whatever PASSWORD_DEFAULT/ARGON2ID resolves to on this PHP build.
 * All accounts are dummy / fictitious, per the assignment's authorised-lab restriction.
 */

require __DIR__ . '/../src/db.php';

$pdo = get_pdo();

$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

$users = [
    ['MIN/23/COM/0001', 'Amina Bello (Student)', 'student1@example.test', 'Student123!', 'student'],
    ['MIN/23/COM/0002', 'Chidi Okafor (Student)', 'student2@example.test', 'Student123!', 'student'],
    ['MIN/20/ADM/0001', 'Fatima Sani (Admin)',    'admin@example.test',    'AdminPass123!', 'admin'],
];

$stmt = $pdo->prepare(
    'INSERT INTO users (matric_no, full_name, email, password_hash, role)
     VALUES (:matric_no, :full_name, :email, :password_hash, :role)
     ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)'
);

foreach ($users as [$matric, $name, $email, $plainPassword, $role]) {
    $stmt->execute([
        ':matric_no'     => $matric,
        ':full_name'     => $name,
        ':email'         => $email,
        ':password_hash' => password_hash($plainPassword, $algo),
        ':role'          => $role,
    ]);
}

$courses = [
    ['IFT542', 'Application Security',      40, 'https://example.test/syllabus/ift542.pdf'],
    ['IFT501', 'Web Systems Development',   50, 'https://example.test/syllabus/ift501.pdf'],
    ['IFT530', 'Database Administration',   35, null],
];

$cstmt = $pdo->prepare(
    'INSERT INTO courses (code, title, capacity, resource_url)
     VALUES (:code, :title, :capacity, :resource_url)
     ON DUPLICATE KEY UPDATE title = VALUES(title)'
);
foreach ($courses as [$code, $title, $cap, $url]) {
    $cstmt->execute([':code' => $code, ':title' => $title, ':capacity' => $cap, ':resource_url' => $url]);
}

echo "Seed complete.\n";
echo "Test accounts (fictitious, for local grading only):\n";
foreach ($users as [$matric, $name, $email, $plainPassword, $role]) {
    echo "  $role: $email / $plainPassword\n";
}
