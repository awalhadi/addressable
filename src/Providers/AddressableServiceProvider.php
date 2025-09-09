<?php

namespace Awalhadi\Addressable\Providers;

use Awalhadi\Addressable\Contracts\GeocodingDriver;
use Awalhadi\Addressable\Services\CountryService;
use Awalhadi\Addressable\Services\GeocodingService;
use Awalhadi\Addressable\Services\OptimizedGeocodingService;
use Awalhadi\Addressable\Services\ValidationService;
use Illuminate\Support\ServiceProvider;

class AddressableServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register services as singletons for better performance
        $this->app->singleton(CountryService::class, function ($app) {
            return new CountryService;
        });

        $this->app->singleton(GeocodingService::class, fn ($app) => new GeocodingService);

        // Bind the geocoding driver contract to the configured implementation
        $this->app->singleton(GeocodingDriver::class, function ($app) {
            $driver = config('addressable.geocoding.driver', 'basic');

            return match ($driver) {
                'optimized' => $app->make(OptimizedGeocodingService::class),
                default => $app->make(GeocodingService::class),
            };
        });

        $this->app->singleton(ValidationService::class, function ($app) {
            return new ValidationService;
        });
    }

    public function boot()
    {
        // Load database migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load config file
        $this->mergeConfigFrom(__DIR__.'/../config/addressable.php', 'addressable');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Awalhadi\Addressable\Console\Commands\OptimizeAddressableCommand::class,
            ]);

            // Publish config file
            $this->publishes([
                __DIR__.'/../config/addressable.php' => config_path('addressable.php'),
            ], 'addressable-config');

            // Publish countries data file
            $this->publishes([
                __DIR__.'/../data/countries.json' => resource_path('data/countries.json'),
            ], 'addressable-data');
        }

        // Preload popular countries if enabled
        if (config('addressable.countries.preload_popular', true)) {
            $this->preloadPopularCountries();
        }
    }

    /**
     * Preload popular countries for better performance.
     */
    private function preloadPopularCountries(): void
    {
        $this->app->booted(function () {
            if (config('addressable.countries.cache_enabled', true)) {
                try {
                    $countryService = app(CountryService::class);
                    $countryService->getPopular();
                } catch (\Exception $e) {
                    // Silently fail to avoid breaking the application
                }
            }
        });
    }
}
