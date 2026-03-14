<?php

namespace Phattarachai\ExceptionLog\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Phattarachai\ExceptionLog\ExceptionLogServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExceptionLogServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('exception-log.enabled', true);
        $app['config']->set('exception-log.notify_email', null);
    }
}
