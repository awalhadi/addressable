<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Services;

use Awalhadi\Addressable\Models\Address;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ValidationService
{
    /**
     * Verify an address using external services.
     */
    public function verifyAddress(Address $address): bool
    {
        if (!$address->isComplete()) {
            return false;
        }

        // Check cache first
        $cacheKey = $this->getValidationCacheKey($address);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $isValid = false;

        try {
            // Try multiple validation services
            $isValid = $this->verifyWithUSPS($address) ||
                $this->verifyWithGoogle($address) ||
                $this->verifyWithHere($address);
        } catch (\Exception $e) {
            Log::error("Address verification failed for address {$address->id}: " . $e->getMessage());
        }

        // Cache the result
        Cache::put($cacheKey, $isValid, config('addressable.caching.ttl.validation', 604800));

        return $isValid;
    }

    /**
     * Verify address using USPS API (US addresses only).
     */
    protected function verifyWithUSPS(Address $address): bool
    {
        if ($address->country_code !== 'US') {
            return false;
        }

        // USPS API requires registration and has specific requirements
        // This is a placeholder implementation
        Log::info("USPS verification would be performed for address {$address->id}");

        return true; // Placeholder
    }

    /**
     * Verify address using Google Maps API.
     */
    protected function verifyWithGoogle(Address $address): bool
    {
        $apiKey = config('addressable.geocoding.api_key');
        if (!$apiKey) {
            return false;
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address->full_address,
                'key' => $apiKey,
            ]);

            if (!$response->successful()) {
                return false;
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return false;
            }

            $result = $data['results'][0];

            // Check if the geocoded address matches the input address
            $geocodedAddress = $result['formatted_address'];
            $similarity = $this->calculateAddressSimilarity($address->full_address, $geocodedAddress);

            return $similarity >= 0.8; // 80% similarity threshold
        } catch (\Exception $e) {
            Log::error("Google address verification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify address using HERE Geocoding API.
     */
    protected function verifyWithHere(Address $address): bool
    {
        $apiKey = config('addressable.geocoding.api_key');
        if (!$apiKey) {
            return false;
        }

        try {
            $response = Http::get('https://geocode.search.hereapi.com/v1/geocode', [
                'q' => $address->full_address,
                'apiKey' => $apiKey,
            ]);

            if (!$response->successful()) {
                return false;
            }

            $data = $response->json();

            if (empty($data['items'])) {
                return false;
            }

            $item = $data['items'][0];
            $geocodedAddress = $item['address']['label'] ?? '';

            $similarity = $this->calculateAddressSimilarity($address->full_address, $geocodedAddress);

            return $similarity >= 0.8; // 80% similarity threshold
        } catch (\Exception $e) {
            Log::error("HERE address verification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate similarity between two addresses.
     */
    protected function calculateAddressSimilarity(string $address1, string $address2): float
    {
        // Normalize addresses
        $normalized1 = $this->normalizeAddress($address1);
        $normalized2 = $this->normalizeAddress($address2);

        // Calculate Levenshtein distance
        $distance = levenshtein($normalized1, $normalized2);
        $maxLength = max(strlen($normalized1), strlen($normalized2));

        if ($maxLength === 0) {
            return 1.0;
        }

        return 1 - ($distance / $maxLength);
    }

    /**
     * Normalize address for comparison.
     */
    protected function normalizeAddress(string $address): string
    {
        // Convert to lowercase
        $normalized = strtolower($address);

        // Remove common punctuation
        $normalized = preg_replace('/[^\w\s]/', ' ', $normalized);

        // Normalize whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Remove common words
        $commonWords = ['street', 'st', 'avenue', 'ave', 'road', 'rd', 'boulevard', 'blvd', 'drive', 'dr'];
        $normalized = str_replace($commonWords, '', $normalized);

        // Trim whitespace
        return trim($normalized);
    }

    /**
     * Validate postal code format for a country.
     */
    public function validatePostalCode(string $postalCode, string $countryCode): bool
    {
        $patterns = [
            'US' => '/^\d{5}(-\d{4})?$/',
            'CA' => '/^[A-Za-z]\d[A-Za-z] \d[A-Za-z]\d$/',
            'GB' => '/^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/i',
            'DE' => '/^\d{5}$/',
            'FR' => '/^\d{5}$/',
            'IT' => '/^\d{5}$/',
            'ES' => '/^\d{5}$/',
            'NL' => '/^\d{4} ?[A-Z]{2}$/i',
            'BE' => '/^\d{4}$/',
            'CH' => '/^\d{4}$/',
            'AT' => '/^\d{4}$/',
            'AU' => '/^\d{4}$/',
            'JP' => '/^\d{3}-\d{4}$/',
            'CN' => '/^\d{6}$/',
            'IN' => '/^\d{6}$/',
            'BR' => '/^\d{5}-\d{3}$/',
            'MX' => '/^\d{5}$/',
        ];

        $countryCode = strtoupper($countryCode);

        if (!isset($patterns[$countryCode])) {
            return true; // No validation pattern for this country
        }

        return (bool) preg_match($patterns[$countryCode], $postalCode);
    }

    /**
     * Validate phone number format for a country.
     */
    public function validatePhoneNumber(string $phone, string $countryCode): bool
    {
        $patterns = [
            'US' => '/^\+?1?\s*\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})$/',
            'CA' => '/^\+?1?\s*\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})$/',
            'GB' => '/^\+?44\s?([0-9]{4,5})\s?([0-9]{6})$/',
            'DE' => '/^\+?49\s?([0-9]{3,4})\s?([0-9]{3,4})\s?([0-9]{2,4})$/',
            'FR' => '/^\+?33\s?([0-9]{1})\s?([0-9]{2})\s?([0-9]{2})\s?([0-9]{2})\s?([0-9]{2})$/',
            'IT' => '/^\+?39\s?([0-9]{3})\s?([0-9]{3})\s?([0-9]{4})$/',
            'ES' => '/^\+?34\s?([0-9]{3})\s?([0-9]{3})\s?([0-9]{3})$/',
            'AU' => '/^\+?61\s?([0-9]{2})\s?([0-9]{4})\s?([0-9]{4})$/',
            'JP' => '/^\+?81\s?([0-9]{2})\s?([0-9]{4})\s?([0-9]{4})$/',
            'CN' => '/^\+?86\s?([0-9]{3})\s?([0-9]{4})\s?([0-9]{4})$/',
            'IN' => '/^\+?91\s?([0-9]{5})\s?([0-9]{5})$/',
            'BR' => '/^\+?55\s?([0-9]{2})\s?([0-9]{4,5})\s?([0-9]{4})$/',
            'MX' => '/^\+?52\s?([0-9]{2})\s?([0-9]{4})\s?([0-9]{4})$/',
        ];

        $countryCode = strtoupper($countryCode);

        if (!isset($patterns[$countryCode])) {
            return true; // No validation pattern for this country
        }

        return (bool) preg_match($patterns[$countryCode], $phone);
    }

    /**
     * Validate email address format.
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate country code.
     */
    public function validateCountryCode(string $countryCode): bool
    {
        try {
            $country = country($countryCode);
            return $country->getName() !== 'Unknown';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get validation cache key.
     */
    protected function getValidationCacheKey(Address $address): string
    {
        $data = [
            'street' => $address->street,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country_code' => $address->country_code,
        ];

        $hash = md5(serialize($data));
        return "address_validation_{$hash}";
    }

    /**
     * Clear validation cache for an address.
     */
    public function clearValidationCache(Address $address): void
    {
        $cacheKey = $this->getValidationCacheKey($address);
        Cache::forget($cacheKey);
    }

    /**
     * Get validation statistics.
     */
    public function getValidationStats(): array
    {
        // This would typically query the database for validation statistics
        // For now, return placeholder data
        return [
            'total_addresses' => 0,
            'verified_addresses' => 0,
            'verification_rate' => 0.0,
            'last_verification' => null,
        ];
    }
}
