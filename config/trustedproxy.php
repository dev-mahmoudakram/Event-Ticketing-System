<?php

/*
 * Proxies whose X-Forwarded-* headers are believed (read by Laravel's TrustProxies middleware).
 *
 * Always trusted: the reverse proxy on this machine (127.0.0.1 and ::1, e.g. Plesk's nginx).
 * TRUST_CLOUDFLARE=true (or 1/yes/on; anything else is false) adds Cloudflare's published ranges (config/cloudflare.php) - only set it
 * when the domain is proxied through Cloudflare, otherwise anyone could route a request through
 * their own Cloudflare account and forge their IP.
 * TRUSTED_PROXIES adds more (comma-separated IPs/CIDRs) for a proxy that is not on this machine.
 * "*" trusts every caller; never use it in production.
 */
$extra = trim((string) env('TRUSTED_PROXIES', ''));

return [
    'proxies' => $extra === '*'
        ? '*'
        : array_values(array_unique(array_merge(
            filter_var(env('TRUST_CLOUDFLARE', false), FILTER_VALIDATE_BOOLEAN) ? (require __DIR__.'/cloudflare.php')['proxies'] : [],
            ['127.0.0.1', '::1'],
            array_filter(array_map('trim', explode(',', $extra))),
        ))),
];
