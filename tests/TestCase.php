<?php

declare(strict_types=1);

namespace Tests;

use Beacon\PennantBeam\Providers\BeamServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers for the application.
     *
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [BeamServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        tap($app['config'], function (Repository $config) {
            $config->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
            $config->set('pennant', require __DIR__ . '/../workbench/config/pennant.php');
        });
    }
}
