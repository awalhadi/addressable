<?php

declare(strict_types=1);

// Intentionally no unused service imports here. Resolve via app() within helpers.

if (! function_exists('country_name')) {
    /**
     * Get country name by country code.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @return string|null Country name or null if not found
     */
    function country_name(string $countryCode): ?string
    {
        $countryService = app(\Awalhadi\Addressable\Services\CountryService::class);

        return $countryService->getName($countryCode);
    }
}

if (! function_exists('country_currency')) {
    /**
     * Get country currency by country code.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @return string|null Currency code or null if not found
     */
    function country_currency(string $countryCode): ?string
    {
        $countryService = app(\Awalhadi\Addressable\Services\CountryService::class);

        return $countryService->getCurrency($countryCode);
    }
}

if (! function_exists('get_dial_code')) {
    /**
     * Get country phone code by country code.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @return string|null Phone code or null if not found
     */
    function get_dial_code(string $countryCode): ?string
    {
        $countryService = app(\Awalhadi\Addressable\Services\CountryService::class);

        return $countryService->getDialCode($countryCode);
    }
}

if (! function_exists('calculate_distance')) {
    /**
     * Calculate distance between two coordinates using Haversine formula.
     *
     * @param  float  $lat1  First latitude
     * @param  float  $lon1  First longitude
     * @param  float  $lat2  Second latitude
     * @param  float  $lon2  Second longitude
     * @param  string  $unit  Distance unit (kilometers, miles, meters)
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

if (! function_exists('country')) {
    /**
     * Get country information by code.
     *
     * @param  string  $code  Country code (ISO 3166-1 alpha-2)
     */
    function country(string $code): \Awalhadi\Addressable\Support\Country
    {
        return new \Awalhadi\Addressable\Support\Country($code);
    }
}

if (! function_exists('countries')) {
    /**
     * Get the country service instance.
     */
    function countries(): \Awalhadi\Addressable\Services\CountryService
    {
        return app(\Awalhadi\Addressable\Services\CountryService::class);
    }
}
