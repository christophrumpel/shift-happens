<?php

namespace App\Jobs;

use App\Support\GearRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Throwable;

class DeployProduction implements ShouldQueue
{
    use Queueable;

    /** Never retry a deploy — a duplicate deploy is worse than a failed one. */
    public int $tries = 1;

    public int $timeout = 1800;

    public function handle(): void
    {
        GearRun::running();

        $binary = (string) config('gears.cloud.binary');
        $path = (string) config('gears.cloud.project_path');

        GearRun::append("$ {$binary} deploy");
        GearRun::append("  in {$path}");

        $result = Process::path($path)
            ->timeout(1740)
            ->env(['TERM' => 'dumb', 'NO_COLOR' => '1'])
            ->run([$binary, 'deploy'], function (string $type, string $output) {
                GearRun::append($output);
            });

        GearRun::finish($result->successful());
    }

    public function failed(?Throwable $exception): void
    {
        GearRun::append('Job failed: '.($exception?->getMessage() ?? 'unknown error'));
        GearRun::finish(false);
    }
}
