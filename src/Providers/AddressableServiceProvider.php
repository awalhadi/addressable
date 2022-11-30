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
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // load views
        $this->loadViewsFrom(__DIR__."/../views", 'addressable');

        // load database
        $this->loadMigrationsFrom(__DIR__."/../database/migrations");

        // load config file
        $this->mergeConfigFrom(__DIR__.'/../config/addressable.php', 'addressable');

        // publish  config file
        if ($this->app->runningInConsole()) {

            $this->publishes([
              __DIR__.'/../config/addressable.php' => config_path('addressable.php'),
            ], 'config');

          }
    }
}
// php artisan vendor:publish --provider="Hadi\Addressable\Providers\AddressableServiceProvider" --tag="config"
