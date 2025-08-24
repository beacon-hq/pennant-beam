<?php

declare(strict_types=1);

use Beacon\PennantBeam\Http\Controllers\FeatureFlagController;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;

it('registers the beam routes', function () {
    $route = Route::getRoutes()->getByName('beam.feature-flags');

    expect($route)->not->toBeNull();

    // Assert HTTP method
    expect($route->methods())
        ->toContain('POST');

    // Assert URI
    expect($route->uri())
        ->toBe('beam/feature-flag/{featureFlag}');

    // Assert controller/action points to the invokable FeatureFlagController
    $actionName = $route->getActionName();
    expect($actionName)
        ->toContain(FeatureFlagController::class);
});

it('should not encrypt the beam cookie', function () {
    $encryptCookies = resolve(EncryptCookies::class);

    expect(prop($encryptCookies, 'except'))->toBe([
        'BEAM-TOKEN',
    ]);
});
