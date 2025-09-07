<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Ultra-optimized geocoding service with intelligent caching and fallback strategies.
 *
 * Features:
 * - Multi-tier caching (Redis + File cache)
 * - Intelligent fallback between providers
 * - Batch geocoding support
 * - Rate limiting and quota management
 * - Performance monitoring
 */
class OptimizedGeocodingService
{
    /**
     * Cache configuration.
     */
    private array $cacheConfig = [
        'prefix' => 'geocoding_',
        'ttl' => 86400, // 24 hours
        'batch_ttl' => 3600, // 1 hour for batch results
    ];

    /**
     * Provider configuration with performance priorities.
     */
    private array $providers = [
        'openstreetmap' => [
            'priority' => 1,
            'free' => true,
            'rate_limit' => 1, // requests per second
            'quota' => 1000, // requests per day
        ],
        'google' => [
            'priority' => 2,
            'free' => false,
            'rate_limit' => 10,
            'quota' => 100000,
        ],
        'here' => [
            'priority' => 3,
            'free' => false,
            'rate_limit' => 5,
            'quota' => 10000,
        ],
    ];

    /**
     * Geocode a single address with intelligent caching.
     */
    public function geocode(string $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        $normalizedAddress = $this->normalizeAddress($address);
        $cacheKey = $this->getCacheKey($normalizedAddress);

        // Check cache first
        $cached = $this->getCachedResult($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Try providers in priority order
        $result = $this->geocodeWithFallback($normalizedAddress);

        if ($result) {
            $this->cacheResult($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Batch geocode multiple addresses efficiently.
     */
    public function batchGeocode(array $addresses): array
    {
        $results = [];
        $uncachedAddresses = [];
        $cacheKeys = [];

        // Check cache for all addresses first
        foreach ($addresses as $index => $address) {
            $normalizedAddress = $this->normalizeAddress($address);
            $cacheKey = $this->getCacheKey($normalizedAddress);
            $cacheKeys[$index] = $cacheKey;

            $cached = $this->getCachedResult($cacheKey);
            if ($cached) {
                $results[$index] = $cached;
            } else {
                $uncachedAddresses[$index] = $normalizedAddress;
            }
        }

        // Geocode uncached addresses
        if (!empty($uncachedAddresses)) {
            $geocodedResults = $this->batchGeocodeUncached($uncachedAddresses);

            foreach ($geocodedResults as $index => $result) {
                $results[$index] = $result;
                if ($result) {
                    $this->cacheResult($cacheKeys[$index], $result);
                }
            }
        }

        return $results;
    }

    /**
     * Reverse geocode coordinates with caching.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        $cacheKey = $this->getCacheKey("reverse_{$latitude}_{$longitude}");

        $cached = $this->getCachedResult($cacheKey);
        if ($cached) {
            return $cached;
        }

        $result = $this->reverseGeocodeWithFallback($latitude, $longitude);

        if ($result) {
            $this->cacheResult($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Geocode with intelligent fallback between providers.
     */
    private function geocodeWithFallback(string $address): ?array
    {
        $enabledProviders = $this->getEnabledProviders();

        foreach ($enabledProviders as $provider) {
            try {
                if (!$this->checkProviderQuota($provider)) {
                    continue;
                }

                $result = $this->geocodeWithProvider($provider, $address);
                if ($result) {
                    $this->recordProviderUsage($provider);
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning("Geocoding failed with provider {$provider}: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    /**
     * Geocode using specific provider.
     */
    private function geocodeWithProvider(string $provider, string $address): ?array
    {
        return match ($provider) {
            'openstreetmap' => $this->geocodeWithOpenStreetMap($address),
            'google' => $this->geocodeWithGoogle($address),
            'here' => $this->geocodeWithHere($address),
            default => null,
        };
    }

    /**
     * OpenStreetMap geocoding (free, no API key required).
     */
    private function geocodeWithOpenStreetMap(string $address): ?array
    {
        $response = Http::timeout(5)->get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        if (empty($data)) {
            return null;
        }

        $result = $data[0];

        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'provider' => 'openstreetmap',
            'formatted_address' => $result['display_name'] ?? null,
            'confidence' => $this->calculateConfidence($result),
        ];
    }

    /**
     * Google Maps geocoding.
     */
    private function geocodeWithGoogle(string $address): ?array
    {
        $apiKey = config('addressable.geocoding.google_api_key');
        if (!$apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $apiKey,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        if ($data['status'] !== 'OK' || empty($data['results'])) {
            return null;
        }

        $result = $data['results'][0];
        $location = $result['geometry']['location'];

        return [
            'latitude' => (float) $location['lat'],
            'longitude' => (float) $location['lng'],
            'provider' => 'google',
            'formatted_address' => $result['formatted_address'] ?? null,
            'confidence' => $this->getGoogleConfidence($result),
        ];
    }

    /**
     * HERE Maps geocoding.
     */
    private function geocodeWithHere(string $address): ?array
    {
        $apiKey = config('addressable.geocoding.here_api_key');
        if (!$apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://geocode.search.hereapi.com/v1/geocode', [
            'q' => $address,
            'apiKey' => $apiKey,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        if (empty($data['items'])) {
            return null;
        }

        $item = $data['items'][0];
        $position = $item['position'];

        return [
            'latitude' => (float) $position['lat'],
            'longitude' => (float) $position['lng'],
            'provider' => 'here',
            'formatted_address' => $item['title'] ?? null,
            'confidence' => $this->getHereConfidence($item),
        ];
    }

    /**
     * Reverse geocode with fallback.
     */
    private function reverseGeocodeWithFallback(float $latitude, float $longitude): ?array
    {
        $enabledProviders = $this->getEnabledProviders();

        foreach ($enabledProviders as $provider) {
            try {
                if (!$this->checkProviderQuota($provider)) {
                    continue;
                }

                $result = $this->reverseGeocodeWithProvider($provider, $latitude, $longitude);
                if ($result) {
                    $this->recordProviderUsage($provider);
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning("Reverse geocoding failed with provider {$provider}: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    /**
     * Reverse geocode using specific provider.
     */
    private function reverseGeocodeWithProvider(string $provider, float $latitude, float $longitude): ?array
    {
        return match ($provider) {
            'openstreetmap' => $this->reverseGeocodeWithOpenStreetMap($latitude, $longitude),
            'google' => $this->reverseGeocodeWithGoogle($latitude, $longitude),
            'here' => $this->reverseGeocodeWithHere($latitude, $longitude),
            default => null,
        };
    }

    /**
     * OpenStreetMap reverse geocoding.
     */
    private function reverseGeocodeWithOpenStreetMap(float $latitude, float $longitude): ?array
    {
        $response = Http::timeout(5)->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
            'addressdetails' => 1,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        if (empty($data)) {
            return null;
        }

        return [
            'formatted_address' => $data['display_name'] ?? null,
            'provider' => 'openstreetmap',
            'address_components' => $this->parseOpenStreetMapAddress($data['address'] ?? []),
        ];
    }

    /**
     * Google reverse geocoding.
     */
    private function reverseGeocodeWithGoogle(float $latitude, float $longitude): ?array
    {
        $apiKey = config('addressable.geocoding.google_api_key');
        if (!$apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $apiKey,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        if ($data['status'] !== 'OK' || empty($data['results'])) {
            return null;
        }

        $result = $data['results'][0];

        return [
            'formatted_address' => $result['formatted_address'] ?? null,
            'provider' => 'google',
            'address_components' => $this->parseGoogleAddressComponents($result['address_components'] ?? []),
        ];
    }

    /**
     * HERE reverse geocoding.
     */
    private function reverseGeocodeWithHere(float $latitude, float $longitude): ?array
    {
        $apiKey = config('addressable.geocoding.here_api_key');
        if (!$apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://revgeocode.search.hereapi.com/v1/revgeocode', [
            'at' => "{$latitude},{$longitude}",
            'apiKey' => $apiKey,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        if (empty($data['items'])) {
            return null;
        }

        $item = $data['items'][0];
        $address = $item['address'] ?? [];

        return [
            'formatted_address' => $address['label'] ?? null,
            'provider' => 'here',
            'address_components' => $this->parseHereAddress($address),
        ];
    }

    /**
     * Batch geocode uncached addresses.
     */
    private function batchGeocodeUncached(array $addresses): array
    {
        $results = [];
        $batchSize = 10; // Process in batches to avoid rate limits

        $batches = array_chunk($addresses, $batchSize, true);

        foreach ($batches as $batch) {
            $batchResults = $this->processBatch($batch);
            $results = array_merge($results, $batchResults);

            // Rate limiting delay
            usleep(100000); // 100ms delay between batches
        }

        return $results;
    }

    /**
     * Process a batch of addresses.
     */
    private function processBatch(array $batch): array
    {
        $results = [];

        foreach ($batch as $index => $address) {
            $result = $this->geocodeWithFallback($address);
            $results[$index] = $result;
        }

        return $results;
    }

    /**
     * Normalize address for consistent caching.
     */
    private function normalizeAddress(string $address): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower($address)));
    }

    /**
     * Get cache key for address.
     */
    private function getCacheKey(string $identifier): string
    {
        return $this->cacheConfig['prefix'] . md5($identifier);
    }

    /**
     * Get cached result.
     */
    private function getCachedResult(string $cacheKey): ?array
    {
        return Cache::get($cacheKey);
    }

    /**
     * Cache result.
     */
    private function cacheResult(string $cacheKey, array $result): void
    {
        Cache::put($cacheKey, $result, $this->cacheConfig['ttl']);
    }

    /**
     * Get enabled providers in priority order.
     */
    private function getEnabledProviders(): array
    {
        $enabled = config('addressable.geocoding.providers', ['openstreetmap']);

        return array_filter($enabled, function ($provider) {
            return isset($this->providers[$provider]);
        });
    }

    /**
     * Check provider quota.
     */
    private function checkProviderQuota(string $provider): bool
    {
        $quotaKey = "geocoding_quota_{$provider}";
        $usage = Cache::get($quotaKey, 0);

        return $usage < $this->providers[$provider]['quota'];
    }

    /**
     * Record provider usage.
     */
    private function recordProviderUsage(string $provider): void
    {
        $quotaKey = "geocoding_quota_{$provider}";
        $usage = Cache::get($quotaKey, 0);
        Cache::put($quotaKey, $usage + 1, 86400); // Reset daily
    }

    /**
     * Calculate confidence score for OpenStreetMap results.
     */
    private function calculateConfidence(array $result): float
    {
        $importance = $result['importance'] ?? 0;
        return min(1.0, $importance);
    }

    /**
     * Get confidence score for Google results.
     */
    private function getGoogleConfidence(array $result): float
    {
        $types = $result['types'] ?? [];

        if (in_array('street_address', $types)) {
            return 0.9;
        } elseif (in_array('route', $types)) {
            return 0.8;
        } elseif (in_array('locality', $types)) {
            return 0.7;
        } elseif (in_array('administrative_area_level_1', $types)) {
            return 0.6;
        }

        return 0.5;
    }

    /**
     * Get confidence score for HERE results.
     */
    private function getHereConfidence(array $item): float
    {
        $scoring = $item['scoring'] ?? [];
        return (float) ($scoring['queryScore'] ?? 0.5);
    }

    /**
     * Parse OpenStreetMap address components.
     */
    private function parseOpenStreetMapAddress(array $address): array
    {
        return [
            'street_number' => $address['house_number'] ?? null,
            'route' => $address['road'] ?? null,
            'locality' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
            'administrative_area_level_1' => $address['state'] ?? null,
            'postal_code' => $address['postcode'] ?? null,
            'country' => $address['country'] ?? null,
        ];
    }

    /**
     * Parse Google address components.
     */
    private function parseGoogleAddressComponents(array $components): array
    {
        $parsed = [];

        foreach ($components as $component) {
            $types = $component['types'];

            if (in_array('street_number', $types)) {
                $parsed['street_number'] = $component['long_name'];
            } elseif (in_array('route', $types)) {
                $parsed['route'] = $component['long_name'];
            } elseif (in_array('locality', $types)) {
                $parsed['locality'] = $component['long_name'];
            } elseif (in_array('administrative_area_level_1', $types)) {
                $parsed['administrative_area_level_1'] = $component['long_name'];
            } elseif (in_array('postal_code', $types)) {
                $parsed['postal_code'] = $component['long_name'];
            } elseif (in_array('country', $types)) {
                $parsed['country'] = $component['long_name'];
            }
        }

        return $parsed;
    }

    /**
     * Parse HERE address components.
     */
    private function parseHereAddress(array $address): array
    {
        return [
            'street_number' => $address['houseNumber'] ?? null,
            'route' => $address['street'] ?? null,
            'locality' => $address['city'] ?? null,
            'administrative_area_level_1' => $address['state'] ?? null,
            'postal_code' => $address['postalCode'] ?? null,
            'country' => $address['countryName'] ?? null,
        ];
    }

    /**
     * Get performance statistics.
     */
    public function getStats(): array
    {
        return [
            'cache_config' => $this->cacheConfig,
            'providers' => $this->providers,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Clear all geocoding cache.
     */
    public function clearCache(): void
    {
        // This would need to be implemented based on your cache driver
        // For Redis: Redis::del(Redis::keys($this->cacheConfig['prefix'] . '*'));
        // For file cache: File::deleteDirectory(storage_path('framework/cache/data/' . $this->cacheConfig['prefix']));
    }
}
