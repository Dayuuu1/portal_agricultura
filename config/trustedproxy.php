<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Addresses of the reverse proxy or load balancer in front of the portal
    | (comma separated, CIDR allowed). Only their X-Forwarded-* headers are
    | believed, so the visitor's real IP reaches the rate limits and the
    | activity log. Leave it empty when the portal is reached directly.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
