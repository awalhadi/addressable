<?php

declare(strict_types=1);

use Awalhadi\Addressable\Services\OptimizedCountryService;
use Awalhadi\Addressable\Services\OptimizedGeocodingService;
use Awalhadi\Addressable\Services\OptimizedRadiusSearchService;

if (!function_exists('country_name')) {
    /**
     * Get country name by country code.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return string|null Country name or null if not found
     */
    function country_name(string $countryCode): ?string
    {
        return OptimizedCountryService::getName($countryCode);
    }
}

if (!function_exists('country_currency')) {
    /**
     * Get country currency by country code.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return string|null Currency code or null if not found
     */
    function country_currency(string $countryCode): ?string
    {
        return OptimizedCountryService::getCurrency($countryCode);
    }
}

if (!function_exists('country_phone')) {
    /**
     * Get country phone code by country code.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return string|null Phone code or null if not found
     */
    function country_phone(string $countryCode): ?string
    {
        return OptimizedCountryService::getPhoneCode($countryCode);
    }
}

if (!function_exists('geocode_address')) {
    /**
     * Geocode an address to get coordinates.
     *
     * @param string $address Address to geocode
     * @return array|null Array with latitude, longitude, and provider info
     */
    function geocode_address(string $address): ?array
    {
        $service = app(OptimizedGeocodingService::class);
        return $service->geocode($address);
    }
}

if (!function_exists('reverse_geocode')) {
    /**
     * Reverse geocode coordinates to get address.
     *
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @return array|null Array with formatted address and components
     */
    function reverse_geocode(float $latitude, float $longitude): ?array
    {
        $service = app(OptimizedGeocodingService::class);
        return $service->reverseGeocode($latitude, $longitude);
    }
}

if (!function_exists('find_addresses_within_radius')) {
    /**
     * Find addresses within a specified radius.
     *
     * @param float $latitude Center latitude
     * @param float $longitude Center longitude
     * @param float $radius Radius distance
     * @param string $unit Distance unit (kilometers, miles, meters)
     * @param array $options Additional options
     * @return array Array of addresses within radius
     */
    function find_addresses_within_radius(
        float $latitude,
        float $longitude,
        float $radius,
        string $unit = 'kilometers',
        array $options = []
    ): array {
        $service = app(OptimizedRadiusSearchService::class);
        return $service->findWithinRadius($latitude, $longitude, $radius, $unit, $options);
    }
}

if (!function_exists('find_nearest_addresses')) {
    /**
     * Find nearest addresses to a point.
     *
     * @param float $latitude Center latitude
     * @param float $longitude Center longitude
     * @param int $limit Number of nearest addresses to return
     * @param string $unit Distance unit (kilometers, miles, meters)
     * @param array $options Additional options
     * @return array Array of nearest addresses
     */
    function find_nearest_addresses(
        float $latitude,
        float $longitude,
        int $limit = 10,
        string $unit = 'kilometers',
        array $options = []
    ): array {
        $service = app(OptimizedRadiusSearchService::class);
        return $service->findNearest($latitude, $longitude, $limit, $unit, $options);
    }
}

if (!function_exists('calculate_distance')) {
    /**
     * Calculate distance between two coordinates using Haversine formula.
     *
     * @param float $lat1 First latitude
     * @param float $lon1 First longitude
     * @param float $lat2 Second latitude
     * @param float $lon2 Second longitude
     * @param string $unit Distance unit (kilometers, miles, meters)
     * @return float Distance between the two points
     */
    function calculate_distance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
        string $unit = 'kilometers'
    ): float {
        $earthRadius = match ($unit) {
            'kilometers' => 6371,
            'miles' => 3959,
            'meters' => 6371000,
            'feet' => 20902231,
            default => 6371,
        };

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

if (!function_exists('addressable_stats')) {
    /**
     * Get performance statistics for all addressable services.
     *
     * @return array Performance statistics
     */
    function addressable_stats(): array
    {
        return [
            'country_service' => OptimizedCountryService::getStats(),
            'geocoding_service' => app(OptimizedGeocodingService::class)->getStats(),
            'radius_search_service' => app(OptimizedRadiusSearchService::class)->getPerformanceMetrics(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }
}
