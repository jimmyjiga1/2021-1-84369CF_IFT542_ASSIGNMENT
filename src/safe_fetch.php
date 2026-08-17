<?php
/**
 * Task 3 SSRF fix for the course "resource preview" feature.
 *
 * Defenses:
 *   1. Scheme allowlist (https only).
 *   2. Hostname allowlist read from URL_PREVIEW_ALLOWLIST (.env) — reject anything else outright.
 *   3. Resolve the hostname and reject loopback, private (RFC1918), link-local and
 *      cloud metadata addresses (169.254.169.254) even if the hostname passed the allowlist,
 *      to stop DNS-rebinding.
 *   4. No redirects followed automatically (CURLOPT_FOLLOWLOCATION disabled) — a redirect
 *      to an internal host would otherwise bypass the checks above.
 *   5. Tight timeout and byte-size cap so the endpoint can't be used as an amplifier.
 */

class SsrfBlockedException extends Exception {}

function is_blocked_ip(string $ip): bool
{
    // Loopback / private / link-local / metadata ranges.
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return true;
    }
    if ($ip === '169.254.169.254') { // cloud metadata endpoint
        return true;
    }
    return false;
}

function allowlisted_hosts(): array
{
    $raw = getenv('URL_PREVIEW_ALLOWLIST') ?: '';
    return array_filter(array_map('trim', explode(',', $raw)));
}

/**
 * @throws SsrfBlockedException
 */
function safe_fetch_preview(string $url): string
{
    $parts = parse_url($url);
    if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
        throw new SsrfBlockedException('Only https:// URLs with an explicit host are allowed.');
    }

    $host = strtolower($parts['host']);
    if (!in_array($host, allowlisted_hosts(), true)) {
        throw new SsrfBlockedException("Host '{$host}' is not on the allowlist.");
    }

    $ips = @dns_get_record($host, DNS_A + DNS_AAAA);
    if ($ips === false || $ips === []) {
        throw new SsrfBlockedException('Host did not resolve.');
    }
    foreach ($ips as $record) {
        $ip = $record['ip'] ?? $record['ipv6'] ?? null;
        if ($ip && is_blocked_ip($ip)) {
            throw new SsrfBlockedException('Resolved address is not publicly routable.');
        }
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false, // never auto-follow redirects
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_RANGE          => '0-65535', // cap response size
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new SsrfBlockedException("Fetch failed: {$err}");
    }
    return $body;
}
