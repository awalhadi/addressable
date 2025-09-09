<?php

declare(strict_types=1);

return [
    'geocoding' => [
        // 'basic' uses GeocodingService, 'optimized' uses
        'driver' => env('ADDRESSABLE_GEOCODING_DRIVER', 'basic'),

        // Legacy provider options used by GeocodingService
        'provider' => env('ADDRESSABLE_GEOCODING_PROVIDER', 'google'),
        'api_key' => env('ADDRESSABLE_GEOCODING_API_KEY'),

        // Optimized service keys
        'google_api_key' => env('ADDRESSABLE_GOOGLE_API_KEY'),
        'here_api_key' => env('ADDRESSABLE_HERE_API_KEY'),

        // Cache controls
        'cache_results' => env('ADDRESSABLE_GEOCODING_CACHE_RESULTS', true),
        'cache_ttl' => env('ADDRESSABLE_GEOCODING_CACHE_TTL', 86400),

        // Ordered list of providers for the optimized driver
        'providers' => ['openstreetmap', 'google', 'here'],
        'enabled' => env('ADDRESSABLE_GEOCODING_ENABLED', true),
    ],

    'countries' => [
        'preload_popular' => true,
        'cache_enabled' => true,
        'popular_countries' => [
            'US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'JP', 'CN', 'IN', 'BR', 'MX',
        ],
    ],

    'caching' => [
        'ttl' => [
            'countries' => 86400,
        ],
    ],

    'spatial' => [
        'default_unit' => 'kilometers',
        'distance_calculation' => 'haversine',
    ],

    'types' => [
        'home' => ['default' => true],
    ],
];
