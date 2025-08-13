<?php

declare(strict_types=1);

namespace Beacon\PennantBeam\Providers;

use Beacon\PennantBeam\Http\Middleware\EnsureBeamAuthenticated;
use Beacon\PennantBeam\Http\Middleware\EnsureBeamJwt;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class BeamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/pennant.php', 'pennant');

        $this->publishes([
            __DIR__ . '/../../config/pennant.php' => config_path('pennant.php'),
        ], 'pennant-config');
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('beam.auth', EnsureBeamAuthenticated::class);
        $router->aliasMiddleware('beam.issue_jwt', EnsureBeamJwt::class);

        $this->app->afterResolving(EncryptCookies::class, function ($middleware) {
            $cookieName = (string) config('pennant.beam.cookie_name', 'BEAM-TOKEN');
            $middleware->disableFor($cookieName);
        });

        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
