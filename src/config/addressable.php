<?php

declare(strict_types=1);

return [
   /*
    |--------------------------------------------------------------------------
    | Address Types Configuration
    |--------------------------------------------------------------------------
    |
    | Define the available address types and their default settings.
    |
    */
    'database' => [
        'connection' => env('ADDRESSABLE_DB_CONNECTION', config('database.default')),
        'table' => env('ADDRESSABLE_TABLE', 'addresses'),
        'soft_deletes' => true,
    ],

    /*
  |--------------------------------------------------------------------------
  | Address Types
  |--------------------------------------------------------------------------
  */
    'types' => [
        'home' => [
            'default' => true,
            'label' => 'Home Address',
        ],
        'work' => [
            'default' => false,
            'label' => 'Work Address',
        ],
        'billing' => [
            'default' => false,
            'label' => 'Billing Address',
        ],
        'shipping' => [
            'default' => false,
            'label' => 'Shipping Address',
        ],
        'general' => [
            'default' => false,
            'label' => 'General Address',
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Geocoding Configuration
  |--------------------------------------------------------------------------
  */
    'geocoding' => [
        'enabled' => env('ADDRESSABLE_GEOCODING_ENABLED', false),
        'provider' => env('ADDRESSABLE_GEOCODING_PROVIDER', 'google'), // google, openstreetmap, here
        'api_key' => env('ADDRESSABLE_GEOCODING_API_KEY'),
        'cache_results' => env('ADDRESSABLE_GEOCODING_CACHE', true),
        'cache_ttl' => env('ADDRESSABLE_GEOCODING_CACHE_TTL', 86400), // 24 hours
    ],

    /*
  |--------------------------------------------------------------------------
  | Validation Configuration
  |--------------------------------------------------------------------------
  */
    'validation' => [
        'postal_code' => [
            'enabled' => env('ADDRESSABLE_POSTAL_CODE_VALIDATION', true),
        ],
        'phone' => [
            'enabled' => env('ADDRESSABLE_PHONE_VALIDATION', true),
            'format' => env('ADDRESSABLE_PHONE_FORMAT', 'international'), // international, national
        ],
        'email' => [
            'enabled' => env('ADDRESSABLE_EMAIL_VALIDATION', true),
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Caching Configuration
  |--------------------------------------------------------------------------
  */
    'caching' => [
        'enabled' => env('ADDRESSABLE_CACHING_ENABLED', true),
        'driver' => env('ADDRESSABLE_CACHE_DRIVER', config('cache.default')),
        'prefix' => env('ADDRESSABLE_CACHE_PREFIX', 'addressable'),
        'ttl' => [
            'address' => 3600, // 1 hour
            'geocoding' => 86400, // 24 hours
            'validation' => 604800, // 1 week
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Spatial Operations Configuration
  |--------------------------------------------------------------------------
  */
    'spatial' => [
        'default_unit' => 'kilometers', // kilometers, miles, meters
        'distance_calculation' => 'haversine', // haversine, vincenty
        'precision' => 8,
        'max_search_radius' => 100, // in default unit
        'geofencing' => [
            'enabled' => true,
        ],
    ],


    /*
  |--------------------------------------------------------------------------
  | Performance Configuration
  |--------------------------------------------------------------------------
  */
    'performance' => [
        'lazy_load_geocoding' => true, // Only geocode when explicitly requested
        'lazy_load_validation' => true, // Only validate when explicitly requested
        'use_lightweight_country_service' => true, // Use built-in country service instead of rinvex/countries
        'cache_country_names' => true, // Cache resolved country names
    ],

];
