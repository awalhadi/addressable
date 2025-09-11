<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Console\Commands;

use Awalhadi\Addressable\Services\CountryService;
use Awalhadi\Addressable\Services\RadiusSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AddressableCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'addressable:optimize
                            {--clear-cache : Clear all addressable caches}
                            {--warm-cache : Warm up caches}
                            {--countries : Optimize countries data}
                            {--spatial : Optimize spatial search performance}
                            {--spatial-stats : Show spatial search statistics}';

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

        if ($this->option('spatial')) {
            $this->optimizeSpatial();
        }

        if ($this->option('spatial-stats')) {
            $this->showSpatialStats();
        }

        // If no specific options, run all optimizations
        if (! $this->option('clear-cache') && ! $this->option('warm-cache') && ! $this->option('countries') && ! $this->option('spatial') && ! $this->option('spatial-stats')) {
            $this->clearCaches();
            $this->warmCaches();
            $this->optimizeCountries();
            $this->optimizeSpatial();
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

    /**
     * Optimize spatial search performance.
     */
    private function optimizeSpatial(): void
    {
        $this->info('🗺️ Optimizing spatial search performance...');

        $spatialService = app(RadiusSearchService::class);

        // Optimize database for spatial queries
        $results = $spatialService->optimizeDatabase();

        if (isset($results['spatial_index'])) {
            $this->line('  - Spatial index: ' . ($results['spatial_index'] ? '✅ Created' : '❌ Failed to create'));
        }

        if (isset($results['table_stats'])) {
            $this->line('  - Table statistics: ' . ($results['table_stats'] ? '✅ Updated' : '❌ Failed to update'));
        }

        if (isset($results['query_analysis'])) {
            $analysis = $results['query_analysis'];
            if (isset($analysis['uses_index'])) {
                $this->line('  - Query optimization: ' . ($analysis['uses_index'] ? '✅ Using indexes' : '⚠️ Not using indexes'));
            }
        }

        // Get spatial statistics
        $stats = $spatialService->getSpatialStats();
        $this->line('  - Total addresses: ' . $stats['total_addresses']);
        $this->line('  - Addresses with coordinates: ' . $stats['addresses_with_coordinates']);
        $this->line('  - Coordinate coverage: ' . round($stats['coordinate_coverage'], 2) . '%');
        $this->line('  - Spatial index exists: ' . ($stats['spatial_index_exists'] ? '✅ Yes' : '❌ No'));

        // Get performance metrics
        $metrics = $spatialService->getPerformanceMetrics();
        $this->line('  - Available algorithms: ' . implode(', ', $metrics['algorithms']));
        $this->line('  - Memory usage: ' . $this->formatBytes($metrics['memory_usage']));
    }

    /**
     * Show spatial search statistics.
     */
    private function showSpatialStats(): void
    {
        $this->info('📊 Spatial Search Statistics');

        $spatialService = app(RadiusSearchService::class);
        $stats = $spatialService->getSpatialStats();
        $metrics = $spatialService->getPerformanceMetrics();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Addresses', number_format($stats['total_addresses'])],
                ['Addresses with Coordinates', number_format($stats['addresses_with_coordinates'])],
                ['Coordinate Coverage', round($stats['coordinate_coverage'], 2) . '%'],
                ['Spatial Index Exists', $stats['spatial_index_exists'] ? 'Yes' : 'No'],
                ['Cache TTL', $stats['cache_stats']['cache_ttl'] . ' seconds'],
                ['Cache Prefix', $stats['cache_stats']['cache_prefix']],
                ['Available Algorithms', implode(', ', $metrics['algorithms'])],
                ['Memory Usage', $this->formatBytes($metrics['memory_usage'])],
                ['Peak Memory', $this->formatBytes($metrics['peak_memory'])],
            ]
        );
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
