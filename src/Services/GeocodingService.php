<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeocodingService
{
    /**
     * Geocode an address using the configured provider.
     */
    public function geocode(string $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        // Check cache first
        $cached = $this->getCachedGeocodingResult($address);
        if ($cached) {
            return $cached;
        }

        $provider = config('addressable.geocoding.provider', 'google');
        $coordinates = null;

        try {
            switch ($provider) {
                case 'google':
                    $coordinates = $this->geocodeWithGoogle($address);
                    break;
                case 'openstreetmap':
                    $coordinates = $this->geocodeWithOpenStreetMap($address);
                    break;
                case 'here':
                    $coordinates = $this->geocodeWithHere($address);
                    break;
                default:
                    Log::warning("Unknown geocoding provider: {$provider}");
                    return null;
            }

            if ($coordinates) {
                $this->cacheGeocodingResult($address, $coordinates);
            }

            return $coordinates;
        } catch (\Exception $e) {
            Log::error("Geocoding failed for address '{$address}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Geocode using Google Maps API.
     */
    protected function geocodeWithGoogle(string $address): ?array
    {
        $apiKey = config('addressable.geocoding.api_key');
        if (!$apiKey) {
            Log::warning('Google Maps API key not configured');
            return null;
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $apiKey,
        ]);

        if (!$response->successful()) {
            Log::error('Google Geocoding API request failed: ' . $response->body());
            return null;
        }

        $data = $response->json();

        if ($data['status'] !== 'OK' || empty($data['results'])) {
            Log::warning("Google Geocoding API returned status: {$data['status']}");
            return null;
        }

        $location = $data['results'][0]['geometry']['location'];

        return [
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
            'provider' => 'google',
            'formatted_address' => $data['results'][0]['formatted_address'] ?? null,
        ];
    }

    /**
     * Geocode using OpenStreetMap Nominatim API.
     */
    protected function geocodeWithOpenStreetMap(string $address): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Addressable-Package/1.0',
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);

        if (!$response->successful()) {
            Log::error('OpenStreetMap Geocoding API request failed: ' . $response->body());
            return null;
        }

        $data = $response->json();

        if (empty($data)) {
            Log::warning("OpenStreetMap Geocoding API returned no results for: {$address}");
            return null;
        }

        $result = $data[0];

        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'provider' => 'openstreetmap',
            'formatted_address' => $result['display_name'] ?? null,
        ];
    }

    /**
     * Geocode using HERE Geocoding API.
     */
    protected function geocodeWithHere(string $address): ?array
    {
        $apiKey = config('addressable.geocoding.api_key');
        if (!$apiKey) {
            Log::warning('HERE API key not configured');
            return null;
        }

        $response = Http::get('https://geocode.search.hereapi.com/v1/geocode', [
            'q' => $address,
            'apiKey' => $apiKey,
        ]);

        if (!$response->successful()) {
            Log::error('HERE Geocoding API request failed: ' . $response->body());
            return null;
        }

        $data = $response->json();

        if (empty($data['items'])) {
            Log::warning("HERE Geocoding API returned no results for: {$address}");
            return null;
        }

        $item = $data['items'][0];
        $position = $item['position'];

        return [
            'latitude' => $position['lat'],
            'longitude' => $position['lng'],
            'provider' => 'here',
            'formatted_address' => $item['address']['label'] ?? null,
        ];
    }

    /**
     * Reverse geocode coordinates to address.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        $cacheKey = "reverse_geocode_{$latitude}_{$longitude}";

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $provider = config('addressable.geocoding.provider', 'google');
        $address = null;

        try {
            switch ($provider) {
                case 'google':
                    $address = $this->reverseGeocodeWithGoogle($latitude, $longitude);
                    break;
                case 'openstreetmap':
                    $address = $this->reverseGeocodeWithOpenStreetMap($latitude, $longitude);
                    break;
                case 'here':
                    $address = $this->reverseGeocodeWithHere($latitude, $longitude);
                    break;
                default:
                    Log::warning("Unknown geocoding provider: {$provider}");
                    return null;
            }

            if ($address) {
                Cache::put($cacheKey, $address, config('addressable.geocoding.cache_ttl', 86400));
            }

            return $address;
        } catch (\Exception $e) {
            Log::error("Reverse geocoding failed for coordinates {$latitude}, {$longitude}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Reverse geocode using Google Maps API.
     */
    protected function reverseGeocodeWithGoogle(float $latitude, float $longitude): ?array
    {
        $apiKey = config('addressable.geocoding.api_key');
        if (!$apiKey) {
            return null;
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
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
        $addressComponents = $result['address_components'];

        return [
            'formatted_address' => $result['formatted_address'],
            'street_number' => $this->extractAddressComponent($addressComponents, 'street_number'),
            'route' => $this->extractAddressComponent($addressComponents, 'route'),
            'locality' => $this->extractAddressComponent($addressComponents, 'locality'),
            'administrative_area_level_1' => $this->extractAddressComponent($addressComponents, 'administrative_area_level_1'),
            'postal_code' => $this->extractAddressComponent($addressComponents, 'postal_code'),
            'country' => $this->extractAddressComponent($addressComponents, 'country'),
        ];
    }

    /**
     * Reverse geocode using OpenStreetMap Nominatim API.
     */
    protected function reverseGeocodeWithOpenStreetMap(float $latitude, float $longitude): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Addressable-Package/1.0',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        return [
            'formatted_address' => $data['display_name'] ?? null,
            'street_number' => $data['address']['house_number'] ?? null,
            'route' => $data['address']['road'] ?? null,
            'locality' => $data['address']['city'] ?? $data['address']['town'] ?? null,
            'administrative_area_level_1' => $data['address']['state'] ?? null,
            'postal_code' => $data['address']['postcode'] ?? null,
            'country' => $data['address']['country'] ?? null,
        ];
    }

    /**
     * Reverse geocode using HERE Geocoding API.
     */
    protected function reverseGeocodeWithHere(float $latitude, float $longitude): ?array
    {
        $apiKey = config('addressable.geocoding.api_key');
        if (!$apiKey) {
            return null;
        }

        $response = Http::get('https://revgeocode.search.hereapi.com/v1/revgeocode', [
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
        $address = $item['address'];

        return [
            'formatted_address' => $address['label'] ?? null,
            'street_number' => $address['houseNumber'] ?? null,
            'route' => $address['street'] ?? null,
            'locality' => $address['city'] ?? null,
            'administrative_area_level_1' => $address['state'] ?? null,
            'postal_code' => $address['postalCode'] ?? null,
            'country' => $address['countryName'] ?? null,
        ];
    }

    /**
     * Extract address component from Google's address components array.
     */
    protected function extractAddressComponent(array $components, string $type): ?string
    {
        foreach ($components as $component) {
            if (in_array($type, $component['types'])) {
                return $component['long_name'];
            }
        }

        return null;
    }

    /**
     * Get cached geocoding result.
     */
    protected function getCachedGeocodingResult(string $address): ?array
    {
        if (!config('addressable.geocoding.cache_results', true)) {
            return null;
        }

        $cacheKey = 'geocoding_' . md5($address);
        return Cache::get($cacheKey);
    }

    /**
     * Cache geocoding result.
     */
    protected function cacheGeocodingResult(string $address, array $coordinates): void
    {
        if (!config('addressable.geocoding.cache_results', true)) {
            return;
        }

        $cacheKey = 'geocoding_' . md5($address);
        $ttl = config('addressable.geocoding.cache_ttl', 86400);

        Cache::put($cacheKey, $coordinates, $ttl);
    }

    /**
     * Clear geocoding cache for an address.
     */
    public function clearGeocodingCache(string $address): void
    {
        $cacheKey = 'geocoding_' . md5($address);
        Cache::forget($cacheKey);
    }

    /**
     * Clear all geocoding cache.
     */
    public function clearAllGeocodingCache(): void
    {
        // This is a simple implementation - in production you might want to use cache tags
        // or a more sophisticated cache clearing mechanism
        Log::info('Geocoding cache cleared');
    }
}
