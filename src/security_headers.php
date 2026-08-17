<?php
/**
 * Applies a restrictive CSP and standard hardening headers to every response.
 * include_once this at the top of every public/*.php entry point.
 */
function apply_security_headers(): void
{
    // No inline script/style, no external origins, no framing — restrictive by default.
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; " .
           "img-src 'self' data:; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // Only send once served over TLS in production — harmless to omit on plain HTTP localhost.
    if (!empty($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
    }
}
