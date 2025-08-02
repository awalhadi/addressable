<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Traits;

use Illuminate\Support\Facades\Cache;

trait HasAddressCaching
{
    /**
     * Get the cache key for this address.
     */
    public function getCacheKey(string $suffix = ''): string
    {
        $prefix = config('addressable.caching.prefix', 'addressable');
        $key = "{$prefix}:address:{$this->id}";

        return $suffix ? "{$key}:{$suffix}" : $key;
    }

    /**
     * Get the cache key for addressable addresses.
     */
    public function getAddressableCacheKey(string $suffix = ''): string
    {
        $prefix = config('addressable.caching.prefix', 'addressable');
        $key = "{$prefix}:addressable:{$this->addressable_type}:{$this->addressable_id}:addresses";

        return $suffix ? "{$key}:{$suffix}" : $key;
    }

    /**
     * Cache the address data.
     */
    public function cacheAddress(): bool
    {
        if (!config('addressable.caching.enabled')) {
            return false;
        }

        $ttl = config('addressable.caching.ttl.address', 3600);
        $data = $this->toArray();

        return Cache::put($this->getCacheKey(), $data, $ttl);
    }

    /**
     * Get cached address data.
     */
    public function getCachedAddress(): ?array
    {
        if (!config('addressable.caching.enabled')) {
            return null;
        }

        return Cache::get($this->getCacheKey());
    }

    /**
     * Clear the address cache.
     */
    public function clearAddressCache(): bool
    {
        if (!config('addressable.caching.enabled')) {
            return false;
        }

        $keys = [
            $this->getCacheKey(),
            $this->getCacheKey('api'),
            $this->getAddressableCacheKey(),
            $this->getAddressableCacheKey('primary'),
            $this->getAddressableCacheKey('billing'),
            $this->getAddressableCacheKey('shipping'),
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        return true;
    }

    /**
     * Cache the addressable's addresses.
     */
    public function cacheAddressableAddresses(array $addresses, string $type = 'all'): bool
    {
        if (!config('addressable.caching.enabled')) {
            return false;
        }

        $ttl = config('addressable.caching.ttl.address', 3600);
        $key = $this->getAddressableCacheKey($type);

        return Cache::put($key, $addresses, $ttl);
    }

    /**
     * Get cached addresses for the addressable.
     */
    public function getCachedAddressableAddresses(string $type = 'all'): ?array
    {
        if (!config('addressable.caching.enabled')) {
            return null;
        }

        return Cache::get($this->getAddressableCacheKey($type));
    }

    /**
     * Cache geocoding results.
     */
    public function cacheGeocodingResult(string $address, array $coordinates): bool
    {
        if (!config('addressable.caching.enabled')) {
            return false;
        }

        $ttl = config('addressable.caching.ttl.geocoding', 86400);
        $key = $this->getGeocodingCacheKey($address);

        return Cache::put($key, $coordinates, $ttl);
    }

    /**
     * Get cached geocoding results.
     */
    public function getCachedGeocodingResult(string $address): ?array
    {
        if (!config('addressable.caching.enabled')) {
            return null;
        }

        return Cache::get($this->getGeocodingCacheKey($address));
    }

    /**
     * Get the geocoding cache key.
     */
    protected function getGeocodingCacheKey(string $address): string
    {
        $prefix = config('addressable.caching.prefix', 'addressable');
        $hash = md5($address);

        return "{$prefix}:geocoding:{$hash}";
    }

    /**
     * Cache validation results.
     */
    public function cacheValidationResult(string $type, array $data, bool $isValid): bool
    {
        if (!config('addressable.caching.enabled')) {
            return false;
        }

        $ttl = config('addressable.caching.ttl.validation', 604800);
        $key = $this->getValidationCacheKey($type, $data);

        return Cache::put($key, $isValid, $ttl);
    }

    /**
     * Get cached validation results.
     */
    public function getCachedValidationResult(string $type, array $data): ?bool
    {
        if (!config('addressable.caching.enabled')) {
            return null;
        }

        return Cache::get($this->getValidationCacheKey($type, $data));
    }

    /**
     * Get the validation cache key.
     */
    protected function getValidationCacheKey(string $type, array $data): string
    {
        $prefix = config('addressable.caching.prefix', 'addressable');
        $hash = md5(serialize($data));

        return "{$prefix}:validation:{$type}:{$hash}";
    }

    /**
     * Cache distance calculations.
     */
    public function cacheDistanceCalculation(float $lat1, float $lon1, float $lat2, float $lon2, string $unit, float $distance): bool
    {
        if (!config('addressable.caching.enabled')) {
            return false;
        }

        $ttl = config('addressable.caching.ttl.address', 3600);
        $key = $this->getDistanceCacheKey($lat1, $lon1, $lat2, $lon2, $unit);

        return Cache::put($key, $distance, $ttl);
    }

    /**
     * Get cached distance calculation.
     */
    public function getCachedDistanceCalculation(float $lat1, float $lon1, float $lat2, float $lon2, string $unit): ?float
    {
        if (!config('addressable.caching.enabled')) {
            return null;
        }

        return Cache::get($this->getDistanceCacheKey($lat1, $lon1, $lat2, $lon2, $unit));
    }

    /**
     * Get the distance cache key.
     */
    protected function getDistanceCacheKey(float $lat1, float $lon1, float $lat2, float $lon2, string $unit): string
    {
        $prefix = config('addressable.caching.prefix', 'addressable');
        $coordinates = "{$lat1},{$lon1}:{$lat2},{$lon2}";
        $hash = md5($coordinates . $unit);

        return "{$prefix}:distance:{$hash}";
    }

    /**
     * Clear all related caches when address is updated.
     */
    public function clearAllRelatedCaches(): bool
    {
        if (!config('addressable.caching.enabled')) {
            return false;
        }

        // Clear address cache
        $this->clearAddressCache();

        // Clear addressable caches
        $addressableKeys = [
            $this->getAddressableCacheKey(),
            $this->getAddressableCacheKey('primary'),
            $this->getAddressableCacheKey('billing'),
            $this->getAddressableCacheKey('shipping'),
            $this->getAddressableCacheKey('verified'),
        ];

        foreach ($addressableKeys as $key) {
            Cache::forget($key);
        }

        // Clear geocoding cache if coordinates changed
        if ($this->wasChanged(['latitude', 'longitude']) && $this->full_address) {
            Cache::forget($this->getGeocodingCacheKey($this->full_address));
        }

        return true;
    }

    /**
     * Warm up the cache for this address.
     */
    public function warmCache(): bool
    {
        if (!config('addressable.caching.enabled')) {
            return false;
        }

        // Cache the address data
        $this->cacheAddress();

        // Cache API format
        Cache::put(
            $this->getCacheKey('api'),
            $this->toApiArray(),
            config('addressable.caching.ttl.address', 3600)
        );

        return true;
    }

    /**
     * Get cache statistics for this address.
     */
    public function getCacheStats(): array
    {
        if (!config('addressable.caching.enabled')) {
            return [];
        }

        $keys = [
            'address' => $this->getCacheKey(),
            'api' => $this->getCacheKey('api'),
            'addressable' => $this->getAddressableCacheKey(),
            'primary' => $this->getAddressableCacheKey('primary'),
            'billing' => $this->getAddressableCacheKey('billing'),
            'shipping' => $this->getAddressableCacheKey('shipping'),
        ];

        $stats = [];
        foreach ($keys as $type => $key) {
            $stats[$type] = [
                'exists' => Cache::has($key),
                'key' => $key,
            ];
        }

        return $stats;
    }
}
