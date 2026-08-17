<?php
declare(strict_types=1);

// APP_ENV=production must disable display_errors — no stack traces/paths leaked to users.
require_once __DIR__ . '/env.php';
if ((getenv('APP_ENV') ?: 'development') === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/security_headers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validate.php';
require_once __DIR__ . '/logger.php';

apply_security_headers();
start_secure_session();

function e(string $value): string
{
    // Central output-encoding helper — the Task 3 XSS fix. Every value interpolated
    // into HTML in this app is passed through e() rather than echoed raw.
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
