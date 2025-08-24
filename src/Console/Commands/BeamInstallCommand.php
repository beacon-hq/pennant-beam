<?php

declare(strict_types=1);

namespace Beacon\PennantBeam\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BeamInstallCommand extends Command
{
    protected $signature = 'beam:install';

    protected $description = 'Install the Beam JS dependencies';

    public function handle(): int
    {
        if (file_exists(base_path('package-lock.json'))) {
            $this->components->task('Installing Beam JS dependencies using npm...', function () {
                Process::run('npm install @beacon-hq/beam --save')->throw();
            });

            return self::SUCCESS;
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->components->task('Installing Beam JS dependencies using yarm...', function () {
                Process::run('yarn add @beacon-hq/beam')->throw();
            });

            return self::SUCCESS;
        } elseif (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->components->task('Installing Beam JS dependencies using pnpm...', function () {
                Process::run('pnpm add @beacon-hq/beam')->throw();
            });

            return self::SUCCESS;
        } elseif (file_exists(base_path('bun.lockb'))) {
            $this->components->task('Installing Beam JS dependencies using bun...', function () {
                Process::run('bun add @beacon-hq/beam')->throw();
            });

            return self::SUCCESS;
        } else {
            $this->error('No package manager found (npm, yarn, pnpm, or bun). Please install one and try again.');

            return self::FAILURE;
        }
    }
}
