<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Services;

use Awalhadi\Addressable\Contracts\GeocodingDriver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService implements GeocodingDriver
{
    /**
     * Unified, production-grade geocoding with multi-provider fallback, caching and batch support.
     */
    public function geocode(string $address): ?array
    {
        if ($address === '') {
            return null;
        }

        $normalized = $this->normalize($address);
        $cacheKey = $this->cacheKey('geocode_'.$normalized);

        if ($cached = $this->cacheGet($cacheKey)) {
            return $cached;
        }

        $result = $this->geocodeWithFallback($normalized);
        if ($result) {
            $this->cachePut($cacheKey, $result);
        }

        return $result;
    }

    public function batchGeocode(array $addresses): array
    {
        $results = [];
        $uncached = [];
        $keys = [];

        foreach ($addresses as $i => $address) {
            $normalized = $this->normalize((string) $address);
            $cacheKey = $this->cacheKey('geocode_'.$normalized);
            $keys[$i] = $cacheKey;

            if ($cached = $this->cacheGet($cacheKey)) {
                $results[$i] = $cached;
            } else {
                $uncached[$i] = $normalized;
            }
        }

        if ($uncached) {
            foreach ($uncached as $i => $addr) {
                $res = $this->geocodeWithFallback($addr);
                $results[$i] = $res;
                if ($res) {
                    $this->cachePut($keys[$i], $res);
                }
            }
        }

        return $results;
    }

    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        $key = $this->cacheKey("reverse_{$latitude}_{$longitude}");
        if ($cached = $this->cacheGet($key)) {
            return $cached;
        }

        $result = $this->reverseWithFallback($latitude, $longitude);
        if ($result) {
            $this->cachePut($key, $result);
        }

        return $result;
    }

    private function geocodeWithFallback(string $address): ?array
    {
        $providers = config('addressable.geocoding.providers', ['openstreetmap', 'google', 'here']);

        foreach ($providers as $provider) {
            try {
                $result = match ($provider) {
                    'openstreetmap' => $this->geocodeWithOpenStreetMap($address),
                    'google' => $this->geocodeWithGoogle($address),
                    'here' => $this->geocodeWithHere($address),
                    default => null,
                };

                if ($result) {
                    return $result;
                }
            } catch (\Throwable $e) {
                Log::warning("Geocoding provider {$provider} failed: ".$e->getMessage());
            }
        }

        return null;
    }

    private function reverseWithFallback(float $latitude, float $longitude): ?array
    {
        $providers = config('addressable.geocoding.providers', ['openstreetmap', 'google', 'here']);

        foreach ($providers as $provider) {
            try {
                $result = match ($provider) {
                    'openstreetmap' => $this->reverseWithOpenStreetMap($latitude, $longitude),
                    'google' => $this->reverseWithGoogle($latitude, $longitude),
                    'here' => $this->reverseWithHere($latitude, $longitude),
                    default => null,
                };

                if ($result) {
                    return $result;
                }
            } catch (\Throwable $e) {
                Log::warning("Reverse geocoding provider {$provider} failed: ".$e->getMessage());
            }
        }

        return null;
    }

    private function geocodeWithOpenStreetMap(string $address): ?array
    {
        $response = Http::timeout(5)->withHeaders([
            'User-Agent' => 'Addressable/1.0',
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1,
        ]);

        if (! $response->successful()) {
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
        ];
    }

    private function geocodeWithGoogle(string $address): ?array
    {
        $apiKey = config('addressable.geocoding.google_api_key') ?: config('addressable.geocoding.api_key');
        if (! $apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $apiKey,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (($data['status'] ?? 'ZERO_RESULTS') !== 'OK' || empty($data['results'])) {
            return null;
        }

        $result = $data['results'][0]['geometry']['location'] ?? null;
        if (! $result) {
            return null;
        }

        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lng'],
            'provider' => 'google',
            'formatted_address' => $data['results'][0]['formatted_address'] ?? null,
        ];
    }

    private function geocodeWithHere(string $address): ?array
    {
        $apiKey = config('addressable.geocoding.here_api_key') ?: config('addressable.geocoding.api_key');
        if (! $apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://geocode.search.hereapi.com/v1/geocode', [
            'q' => $address,
            'apiKey' => $apiKey,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (empty($data['items'])) {
            return null;
        }

        $position = $data['items'][0]['position'] ?? null;
        if (! $position) {
            return null;
        }

        return [
            'latitude' => (float) $position['lat'],
            'longitude' => (float) $position['lng'],
            'provider' => 'here',
            'formatted_address' => $data['items'][0]['title'] ?? null,
        ];
    }

    private function reverseWithOpenStreetMap(float $latitude, float $longitude): ?array
    {
        $response = Http::timeout(5)->withHeaders([
            'User-Agent' => 'Addressable/1.0',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
            'addressdetails' => 1,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $addr = $data['address'] ?? [];

        return [
            'formatted_address' => $data['display_name'] ?? null,
            'street_number' => $addr['house_number'] ?? null,
            'route' => $addr['road'] ?? null,
            'locality' => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null,
            'administrative_area_level_1' => $addr['state'] ?? null,
            'postal_code' => $addr['postcode'] ?? null,
            'country' => $addr['country'] ?? null,
        ];
    }

    private function reverseWithGoogle(float $latitude, float $longitude): ?array
    {
        $apiKey = config('addressable.geocoding.google_api_key') ?: config('addressable.geocoding.api_key');
        if (! $apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $apiKey,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (($data['status'] ?? 'ZERO_RESULTS') !== 'OK' || empty($data['results'])) {
            return null;
        }

        $components = $data['results'][0]['address_components'] ?? [];

        return [
            'formatted_address' => $data['results'][0]['formatted_address'] ?? null,
            'street_number' => $this->component($components, 'street_number'),
            'route' => $this->component($components, 'route'),
            'locality' => $this->component($components, 'locality'),
            'administrative_area_level_1' => $this->component($components, 'administrative_area_level_1'),
            'postal_code' => $this->component($components, 'postal_code'),
            'country' => $this->component($components, 'country'),
        ];
    }

    private function reverseWithHere(float $latitude, float $longitude): ?array
    {
        $apiKey = config('addressable.geocoding.here_api_key') ?: config('addressable.geocoding.api_key');
        if (! $apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://revgeocode.search.hereapi.com/v1/revgeocode', [
            'at' => "{$latitude},{$longitude}",
            'apiKey' => $apiKey,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (empty($data['items'])) {
            return null;
        }

        $address = $data['items'][0]['address'] ?? [];

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

    private function component(array $components, string $type): ?string
    {
        foreach ($components as $c) {
            if (in_array($type, $c['types'] ?? [])) {
                return $c['long_name'] ?? null;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower($value)) ?? '');
    }

    private function cacheKey(string $key): string
    {
        return 'geocoding_'.md5($key);
    }

    private function cacheGet(string $key): ?array
    {
        return config('addressable.geocoding.cache_results', true) ? Cache::get($key) : null;
    }

    private function cachePut(string $key, array $value): void
    {
        if (! config('addressable.geocoding.cache_results', true)) {
            return;
        }
        Cache::put($key, $value, (int) config('addressable.geocoding.cache_ttl', 86400));
    }

    public function clearGeocodingCache(string $address): void
    {
        Cache::forget($this->cacheKey('geocode_'.$this->normalize($address)));
    }

    public function clearAllGeocodingCache(): void
    {
        Log::info('Requested geocoding cache clear (no-op without cache tags)');
    }
}
