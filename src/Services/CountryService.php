<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CountryService
{
    /**
     * Cache key for countries data.
     */
    private const CACHE_KEY = 'addressable_countries_data';

    /**
     * Get cache TTL from config.
     */
    private function getCacheTtl(): int
    {
        return config('addressable.caching.ttl.countries', 86400);
    }

    /**
     * Countries data.
     */
    private static ?array $countries = null;

    /**
     * Get all countries.
     */
    public function all(): array
    {
        return $this->getCountries();
    }

    /**
     * Get country by code.
     */
    public function get(string $code): ?array
    {
        $countries = $this->getCountries();
        $code = strtoupper($code);

        return $countries[$code] ?? null;
    }

    /**
     * Get country name by code.
     */
    public function getName(string $code): ?string
    {
        $country = $this->get($code);
        return $country['name'] ?? null;
    }

    /**
     * Get country dial code by code.
     */
    public function getDialCode(string $code): ?string
    {
        $country = $this->get($code);
        return $country['dial_code'] ?? null;
    }

    /**
     * Get country currency by code.
     */
    public function getCurrency(string $code): ?string
    {
        $country = $this->get($code);
        return $country['currency'] ?? null;
    }

    /**
     * Get country continent by code.
     */
    public function getContinent(string $code): ?string
    {
        $country = $this->get($code);
        return $country['continent'] ?? null;
    }

    /**
     * Check if country code exists.
     */
    public function exists(string $code): bool
    {
        return $this->get($code) !== null;
    }

    /**
     * Search countries by name.
     */
    public function search(string $query): array
    {
        $countries = $this->getCountries();
        $query = strtolower($query);
        $results = [];

        foreach ($countries as $country) {
            if (str_contains(strtolower($country['name']), $query)) {
                $results[] = $country;
            }
        }

        return $results;
    }

    /**
     * Get countries by continent.
     */
    public function getByContinent(string $continent): array
    {
        $countries = $this->getCountries();
        $results = [];

        foreach ($countries as $country) {
            if (strcasecmp($country['continent'], $continent) === 0) {
                $results[] = $country;
            }
        }

        return $results;
    }

    /**
     * Get countries as collection.
     */
    public function collect(): Collection
    {
        return collect($this->getCountries());
    }

    /**
     * Get countries for select options.
     */
    public function forSelect(): array
    {
        $countries = $this->getCountries();
        $options = [];

        foreach ($countries as $code => $country) {
            $options[$code] = $country['name'];
        }

        asort($options);
        return $options;
    }

    /**
     * Get popular countries (commonly used ones).
     */
    public function getPopular(): array
    {
        $popularCodes = config('addressable.countries.popular_countries', [
            'US',
            'GB',
            'CA',
            'AU',
            'DE',
            'FR',
            'IT',
            'ES',
            'NL',
            'JP',
            'CN',
            'IN',
            'BR',
            'MX'
        ]);

        $countries = $this->getCountries();
        $popular = [];

        foreach ($popularCodes as $code) {
            if (isset($countries[$code])) {
                $popular[$code] = $countries[$code];
            }
        }

        return $popular;
    }

    /**
     * Validate country code format.
     */
    public function isValidCode(string $code): bool
    {
        return preg_match('/^[A-Z]{2}$/', strtoupper($code)) === 1;
    }

    /**
     * Clear countries cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$countries = null;
    }

    /**
     * Refresh countries cache.
     */
    public function refreshCache(): array
    {
        $this->clearCache();
        return $this->getCountries();
    }

    /**
     * Get countries data with caching.
     */
    private function getCountries(): array
    {
        // Return cached in-memory data if available
        if (self::$countries !== null) {
            return self::$countries;
        }

        // Try to get from cache
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            self::$countries = $cached;
            return self::$countries;
        }

        // Load from file
        $countries = $this->loadCountriesFromFile();

        // Cache the data
        Cache::put(self::CACHE_KEY, $countries, $this->getCacheTtl());
        self::$countries = $countries;

        return $countries;
    }

    /**
     * Load countries from JSON file.
     */
    private function loadCountriesFromFile(): array
    {
        $filePath = __DIR__ . '/../data/countries.json';

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Countries data file not found at: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read countries data file: {$filePath}");
        }

        $countries = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in countries data file: " . json_last_error_msg());
        }

        return $countries;
    }

    /**
     * Get statistics about countries data.
     */
    public function getStats(): array
    {
        $countries = $this->getCountries();
        $continents = [];
        $currencies = [];

        foreach ($countries as $country) {
            $continent = $country['continent'];
            $currency = $country['currency'];

            $continents[$continent] = ($continents[$continent] ?? 0) + 1;
            if (!empty($currency)) {
                $currencies[$currency] = ($currencies[$currency] ?? 0) + 1;
            }
        }

        return [
            'total_countries' => count($countries),
            'continents' => $continents,
            'currencies' => count($currencies),
            'most_common_currency' => array_keys($currencies, max($currencies))[0] ?? null,
        ];
    }
}
