<?php

declare(strict_types=1);

return [
    'beam' => [
        // Route paths used by this package
        'routes' => [
            // GET route that issues a JWT cookie
            'token' => '/beam/token',

            // POST route that resolves a feature flag; will be suffixed with /{featureFlag}
            'feature_flags' => '/beam/feature-flag',
        ],

        // Time-to-live for issued JWTs, in seconds
        'jwt' => [
            'ttl' => 300, // default 5 minutes
        ],

        // Cookie name used to store the JWT
        'cookie_name' => 'BEAM-TOKEN',
    ],
];
