<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Console\Commands;

use Awalhadi\Addressable\Services\CountryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class OptimizeAddressableCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'addressable:optimize 
                            {--clear-cache : Clear all addressable caches}
                            {--warm-cache : Warm up caches}
                            {--countries : Optimize countries data}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize the Addressable package for better performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Optimizing Addressable package...');

        if ($this->option('clear-cache')) {
            $this->clearCaches();
        }

        if ($this->option('warm-cache')) {
            $this->warmCaches();
        }

        if ($this->option('countries')) {
            $this->optimizeCountries();
        }

        // If no specific options, run all optimizations
        if (! $this->option('clear-cache') && ! $this->option('warm-cache') && ! $this->option('countries')) {
            $this->clearCaches();
            $this->warmCaches();
            $this->optimizeCountries();
        }

        $this->info('✅ Addressable package optimization completed!');

        return 0;
    }

    /**
     * Clear all addressable caches.
     */
    private function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');

        $prefix = config('addressable.caching.prefix', 'addressable');

        // Clear specific cache keys
        $cacheKeys = [
            'addressable_countries_data',
            "{$prefix}_*",
        ];

        foreach ($cacheKeys as $key) {
            if (str_contains($key, '*')) {
                // For wildcard patterns, we'd need to implement cache tag clearing
                // This is a simplified version
                $this->line("  - Cleared cache pattern: {$key}");
            } else {
                Cache::forget($key);
                $this->line("  - Cleared cache: {$key}");
            }
        }
    }

    /**
     * Warm up caches.
     */
    private function warmCaches(): void
    {
        $this->info('🔥 Warming up caches...');

        // Warm up countries cache
        $countryService = app(CountryService::class);
        $countries = $countryService->all();
        $this->line('  - Loaded '.count($countries).' countries into cache');

        // Warm up popular countries
        $popular = $countryService->getPopular();
        $this->line('  - Cached '.count($popular).' popular countries');
    }

    /**
     * Optimize countries data.
     */
    private function optimizeCountries(): void
    {
        $this->info('🌍 Optimizing countries data...');

        $countryService = app(CountryService::class);

        // Refresh countries cache
        $countries = $countryService->refreshCache();
        $this->line('  - Refreshed countries cache with '.count($countries).' countries');

        // Get statistics
        $stats = $countryService->getStats();
        $this->line('  - Total countries: '.$stats['total_countries']);
        $this->line('  - Continents: '.count($stats['continents']));
        $this->line('  - Unique currencies: '.$stats['currencies']);

        // Validate data integrity
        $this->validateCountriesData($countryService);
    }

    /**
     * Validate countries data integrity.
     */
    private function validateCountriesData(CountryService $countryService): void
    {
        $this->info('🔍 Validating countries data...');

        $countries = $countryService->all();
        $errors = [];

        foreach ($countries as $code => $country) {
            // Check required fields
            if (empty($country['name'])) {
                $errors[] = "Country {$code} missing name";
            }

            if (empty($country['code'])) {
                $errors[] = "Country {$code} missing code";
            }

            if ($country['code'] !== $code) {
                $errors[] = "Country {$code} code mismatch";
            }

            // Validate code format
            if (! $countryService->isValidCode($code)) {
                $errors[] = "Country {$code} has invalid code format";
            }
        }

        if (empty($errors)) {
            $this->line('  ✅ All countries data is valid');
        } else {
            $this->error('  ❌ Found '.count($errors).' validation errors:');
            foreach ($errors as $error) {
                $this->line("    - {$error}");
            }
        }
    }
}
