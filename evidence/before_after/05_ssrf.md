# Evidence 5 — SSRF in Course Resource Preview

## Before (vulnerable pattern)
```php
// UNSAFE: fetches whatever URL is stored/supplied, no restrictions.
$url = $_GET['url'];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
echo curl_exec($ch);
```
An attacker could set the preview target to `http://127.0.0.1:3306`, `http://169.254.169.254/`
(cloud metadata service) or an internal admin panel not reachable from the public internet, and
the server — which *can* reach those addresses — would fetch it on the attacker's behalf and
return the response, defeating network-level access controls.

## After (fixed)
File: `src/safe_fetch.php`, `public/url_preview.php`

```php
function safe_fetch_preview(string $url): string {
    $parts = parse_url($url);
    if (($parts['scheme'] ?? '') !== 'https') { throw new SsrfBlockedException(...); }
    if (!in_array(strtolower($parts['host']), allowlisted_hosts(), true)) { throw ...; }
    foreach (dns_get_record($parts['host'], DNS_A + DNS_AAAA) as $r) {
        if (is_blocked_ip($r['ip'] ?? $r['ipv6'])) { throw ...; } // loopback/private/link-local/metadata
    }
    // CURLOPT_FOLLOWLOCATION disabled — a redirect can't bypass the checks above.
}
```
Only `https://` URLs whose hostname is on `URL_PREVIEW_ALLOWLIST` (`.env`) are fetched at all;
the resolved IP is then independently checked against loopback/private/link-local/metadata
ranges so an attacker cannot bypass the hostname allowlist via DNS rebinding, and redirects are
never followed automatically so a permitted host can't 302 the request to an internal one.
