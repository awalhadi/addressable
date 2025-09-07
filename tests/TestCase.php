<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Tests;

use Awalhadi\Addressable\Providers\AddressableServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            AddressableServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup queue configuration
        $app['config']->set('queue.default', 'sync');

        // Setup cache configuration
        $app['config']->set('cache.default', 'array');

        // Setup app configuration
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
    }

    /**
     * Get application timezone.
     */
    protected function getApplicationTimezone($app): string
    {
        return 'UTC';
    }
}
