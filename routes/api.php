<?php

declare(strict_types=1);

use Beacon\PennantBeam\Http\Controllers\FeatureFlagController;
use Illuminate\Support\Facades\Route;

Route::get((string) config('pennant.beam.routes.token', '/beam/token'), function () {
    return response()->noContent();
})->middleware(['beam.issue_jwt'])->name('beam.token');

// Preflight OPTIONS for feature-flag endpoint (handled within EnsureBeamAuthenticated for CORS)
Route::options((string) config('pennant.beam.routes.feature_flags', '/beam/feature-flag') . '/{featureFlag}', function () {
    return response()->noContent();
})->middleware(['beam.auth']);

Route::post((string) config('pennant.beam.routes.feature_flags', '/beam/feature-flag') . '/{featureFlag}', FeatureFlagController::class)
    ->middleware(['beam.auth'])
    ->name('beam.feature-flags');
