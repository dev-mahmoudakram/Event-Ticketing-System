<?php

/*
 * Proxies whose X-Forwarded-* headers are believed (read by Laravel's TrustProxies middleware).
 * Cloudflare's ranges, the host's own reverse proxy on this machine, and anything listed in the
 * optional TRUSTED_PROXIES env value (comma-separated, or "*" to trust every caller, e.g. ngrok
 * in local development only).
 */
$extra = trim((string) env('TRUSTED_PROXIES', ''));

return [
    'proxies' => $extra === '*'
        ? '*'
        : array_values(array_unique(array_merge(
            (require __DIR__.'/cloudflare.php')['proxies'],
            ['127.0.0.1', '::1'],
            array_filter(array_map('trim', explode(',', $extra))),
        ))),
];
