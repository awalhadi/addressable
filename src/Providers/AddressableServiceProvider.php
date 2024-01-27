<?php

namespace Awalhadi\Addressable\Providers;

use Illuminate\Support\ServiceProvider;



class AddressableServiceProvider extends ServiceProvider
{

    public function register()
    {
    }

    public function boot()
    {

        // load database
        $this->loadMigrationsFrom(__DIR__ . "/../database/migrations");

        // load config file
        $this->mergeConfigFrom(__DIR__ . '/../config/addressable.php', 'addressable');

        // publish  config file
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/addressable.php' => config_path('addressable.php'),
            ], 'config');
        }
    }
}
