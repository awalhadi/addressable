<?php

declare(strict_types=1);

return [
    /*
  |--------------------------------------------------------------------------
  | Addressable Configuration
  |--------------------------------------------------------------------------
  |
  | This file contains the configuration for the addressable package.
  | You can customize these settings based on your application needs.
  |
  */

    /*
  |--------------------------------------------------------------------------
  | Database Configuration
  |--------------------------------------------------------------------------
  */
    'database' => [
        'connection' => env('ADDRESSABLE_DB_CONNECTION', config('database.default')),
        'table' => env('ADDRESSABLE_TABLE', 'addresses'),
        'uuid_primary_key' => env('ADDRESSABLE_UUID_PRIMARY_KEY', true),
        'spatial_support' => env('ADDRESSABLE_SPATIAL_SUPPORT', true),
    ],

    /*
  |--------------------------------------------------------------------------
  | Address Types
  |--------------------------------------------------------------------------
  */
    'types' => [
        'home' => [
            'label' => 'Home Address',
            'icon' => 'home',
            'default' => true,
        ],
        'work' => [
            'label' => 'Work Address',
            'icon' => 'briefcase',
            'default' => false,
        ],
        'billing' => [
            'label' => 'Billing Address',
            'icon' => 'credit-card',
            'default' => false,
        ],
        'shipping' => [
            'label' => 'Shipping Address',
            'icon' => 'truck',
            'default' => false,
        ],
        'general' => [
            'label' => 'General Address',
            'icon' => 'map-pin',
            'default' => false,
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
        'rate_limit' => [
            'requests_per_minute' => env('ADDRESSABLE_GEOCODING_RATE_LIMIT', 100),
            'requests_per_day' => env('ADDRESSABLE_GEOCODING_DAILY_LIMIT', 2500),
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Validation Configuration
  |--------------------------------------------------------------------------
  */
    'validation' => [
        'postal_code' => [
            'enabled' => env('ADDRESSABLE_POSTAL_CODE_VALIDATION', true),
            'strict' => env('ADDRESSABLE_POSTAL_CODE_STRICT', false),
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
            'address' => env('ADDRESSABLE_CACHE_ADDRESS_TTL', 3600), // 1 hour
            'geocoding' => env('ADDRESSABLE_CACHE_GEOCODING_TTL', 86400), // 24 hours
            'validation' => env('ADDRESSABLE_CACHE_VALIDATION_TTL', 604800), // 1 week
            'countries' => env('ADDRESSABLE_CACHE_COUNTRIES_TTL', 86400), // 24 hours
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Countries Configuration
  |--------------------------------------------------------------------------
  */
    'countries' => [
        'data_source' => env('ADDRESSABLE_COUNTRIES_DATA_SOURCE', 'internal'), // internal, external
        'cache_enabled' => env('ADDRESSABLE_COUNTRIES_CACHE_ENABLED', true),
        'preload_popular' => env('ADDRESSABLE_COUNTRIES_PRELOAD_POPULAR', true),
        'popular_countries' => [
            'US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'JP', 'CN', 'IN', 'BR', 'MX'
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Spatial Operations Configuration
  |--------------------------------------------------------------------------
  */
    'spatial' => [
        'distance_calculation' => env('ADDRESSABLE_DISTANCE_CALCULATION', 'haversine'), // haversine, vincenty
        'default_unit' => env('ADDRESSABLE_DISTANCE_UNIT', 'kilometers'), // kilometers, miles, meters
        'max_search_radius' => env('ADDRESSABLE_MAX_SEARCH_radius', 100), // in default unit
        'geofencing' => [
            'enabled' => env('ADDRESSABLE_GEOFENCING_ENABLED', true),
            'precision' => env('ADDRESSABLE_GEOFENCING_PRECISION', 6), // decimal places
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Security Configuration
  |--------------------------------------------------------------------------
  */
    'security' => [
        'encryption' => [
            'enabled' => env('ADDRESSABLE_ENCRYPTION_ENABLED', false),
            'fields' => [
                'phone',
                'email',
                'postal_code',
            ],
        ],
        'data_masking' => [
            'enabled' => env('ADDRESSABLE_DATA_MASKING_ENABLED', true),
            'fields' => [
                'phone' => 'partial', // full, partial, none
                'email' => 'partial',
                'postal_code' => 'none',
            ],
        ],
        'gdpr' => [
            'enabled' => env('ADDRESSABLE_GDPR_ENABLED', true),
            'auto_delete' => env('ADDRESSABLE_GDPR_AUTO_DELETE', false),
            'retention_period' => env('ADDRESSABLE_GDPR_RETENTION_DAYS', 2555), // 7 years
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Performance Configuration
  |--------------------------------------------------------------------------
  */
    'performance' => [
        'eager_loading' => [
            'enabled' => env('ADDRESSABLE_EAGER_LOADING_ENABLED', true),
            'default_relations' => ['addresses'],
        ],
        'bulk_operations' => [
            'enabled' => env('ADDRESSABLE_BULK_OPERATIONS_ENABLED', true),
            'batch_size' => env('ADDRESSABLE_BULK_BATCH_SIZE', 1000),
        ],
        'query_optimization' => [
            'enabled' => env('ADDRESSABLE_QUERY_OPTIMIZATION_ENABLED', true),
            'n_plus_one_prevention' => env('ADDRESSABLE_N_PLUS_ONE_PREVENTION', true),
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Events Configuration
  |--------------------------------------------------------------------------
  */
    'events' => [
        'enabled' => env('ADDRESSABLE_EVENTS_ENABLED', true),
        'listeners' => [
            'address_created' => \Awalhadi\Addressable\Listeners\AddressCreatedListener::class,
            'address_updated' => \Awalhadi\Addressable\Listeners\AddressUpdatedListener::class,
            'address_deleted' => \Awalhadi\Addressable\Listeners\AddressDeletedListener::class,
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | API Configuration
  |--------------------------------------------------------------------------
  */
    'api' => [
        'enabled' => env('ADDRESSABLE_API_ENABLED', true),
        'version' => env('ADDRESSABLE_API_VERSION', 'v1'),
        'rate_limiting' => [
            'enabled' => env('ADDRESSABLE_API_RATE_LIMITING', true),
            'requests_per_minute' => env('ADDRESSABLE_API_RATE_LIMIT', 60),
        ],
        'pagination' => [
            'default_per_page' => env('ADDRESSABLE_API_PER_PAGE', 15),
            'max_per_page' => env('ADDRESSABLE_API_MAX_PER_PAGE', 100),
        ],
    ],

    /*
  |--------------------------------------------------------------------------
  | Monitoring Configuration
  |--------------------------------------------------------------------------
  */
    'monitoring' => [
        'enabled' => env('ADDRESSABLE_MONITORING_ENABLED', false),
        'metrics' => [
            'query_performance' => env('ADDRESSABLE_MONITOR_QUERY_PERFORMANCE', true),
            'spatial_operations' => env('ADDRESSABLE_MONITOR_SPATIAL_OPERATIONS', true),
            'geocoding_usage' => env('ADDRESSABLE_MONITOR_GEOCODING_USAGE', true),
        ],
        'logging' => [
            'enabled' => env('ADDRESSABLE_LOGGING_ENABLED', false),
            'level' => env('ADDRESSABLE_LOGGING_LEVEL', 'info'),
            'channel' => env('ADDRESSABLE_LOGGING_CHANNEL', 'addressable'),
        ],
    ],
];
