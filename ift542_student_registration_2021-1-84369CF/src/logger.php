<?php
/**
 * Appends a structured JSON line to storage/logs/security.log for events that
 * also matter operationally (grep/SIEM-friendly), in addition to audit_log()
 * in src/repository.php which is the queryable DB record used by the app.
 *
 * NEVER pass $context containing passwords, tokens, or full card/session data —
 * every call site in this app only logs who/what/when + a short reason.
 */
function security_log(string $event, array $context = []): void
{
    $entry = [
        'ts'      => date('c'),
        'event'   => $event,     // e.g. login_failed, access_denied, validation_rejected
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'context' => $context,
    ];
    $line = json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $path = __DIR__ . '/../storage/logs/security.log';
    file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}
