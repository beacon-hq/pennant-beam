<?php

declare(strict_types=1);

use Beacon\PennantBeam\Console\Commands\BeamInstallCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    // Mock the Process facade
    Process::fake([
        'npm install @beacon-hq/beam --save' => Process::result(),
        'yarn add @beacon-hq/beam' => Process::result(),
        'pnpm add @beacon-hq/beam' => Process::result(),
        'bun add @beacon-hq/beam' => Process::result(),
    ]);
});

it('registers the beam:install command', function () {
    $this->artisan('list')
        ->assertSuccessful()
        ->expectsOutputToContain('beam:install');
});

it('installs beam package using npm when package-lock.json exists', function () {
    // Create a temporary package-lock.json file in the base directory
    $packageLockPath = base_path('package-lock.json');
    file_put_contents($packageLockPath, '{}');

    try {
        // Act
        $this->artisan(BeamInstallCommand::class)
            ->assertSuccessful();

        // Assert
        Process::assertRan('npm install @beacon-hq/beam --save');
    } finally {
        // Clean up
        if (file_exists($packageLockPath)) {
            unlink($packageLockPath);
        }
    }
});

it('installs beam package using yarn when yarn.lock exists', function () {
    // Create a temporary yarn.lock file in the base directory
    $yarnLockPath = base_path('yarn.lock');
    file_put_contents($yarnLockPath, '{}');

    try {
        // Act
        $this->artisan(BeamInstallCommand::class)
            ->assertSuccessful();

        // Assert
        Process::assertRan('yarn add @beacon-hq/beam');
    } finally {
        // Clean up
        if (file_exists($yarnLockPath)) {
            unlink($yarnLockPath);
        }
    }
});

it('installs beam package using pnpm when pnpm-lock.yaml exists', function () {
    // Create a temporary pnpm-lock.yaml file in the base directory
    $pnpmLockPath = base_path('pnpm-lock.yaml');
    file_put_contents($pnpmLockPath, '{}');

    try {
        // Act
        $this->artisan(BeamInstallCommand::class)
            ->assertSuccessful();

        // Assert
        Process::assertRan('pnpm add @beacon-hq/beam');
    } finally {
        // Clean up
        if (file_exists($pnpmLockPath)) {
            unlink($pnpmLockPath);
        }
    }
});

it('installs beam package using bun when bun.lockb exists', function () {
    // Create a temporary bun.lockb file in the base directory
    $bunLockPath = base_path('bun.lockb');
    file_put_contents($bunLockPath, '{}');

    try {
        // Act
        $this->artisan(BeamInstallCommand::class)
            ->assertSuccessful();

        // Assert
        Process::assertRan('bun add @beacon-hq/beam');
    } finally {
        // Clean up
        if (file_exists($bunLockPath)) {
            unlink($bunLockPath);
        }
    }
});

it('shows an error when no package manager is found', function () {
    // Make sure none of the lock files exist
    $packageLockPath = base_path('package-lock.json');
    $yarnLockPath = base_path('yarn.lock');
    $pnpmLockPath = base_path('pnpm-lock.yaml');
    $bunLockPath = base_path('bun.lockb');

    // Delete any existing lock files if they exist (unlikely in test environment)
    foreach ([$packageLockPath, $yarnLockPath, $pnpmLockPath, $bunLockPath] as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // Act & Assert
    $this->artisan(BeamInstallCommand::class)
        ->expectsOutput('No package manager found (npm, yarn, pnpm, or bun). Please install one and try again.')
        ->assertFailed();

    // Assert no process was run
    Process::assertNothingRan();
});
