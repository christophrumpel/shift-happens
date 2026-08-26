<?php

namespace App\Jobs;

use App\Support\GearRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Throwable;

class RunPhpstan implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function handle(): void
    {
        GearRun::running();
        GearRun::append('$ vendor/bin/phpstan analyse');

        $result = Process::path(base_path())
            ->timeout(570)
            ->env(['TERM' => 'dumb', 'NO_COLOR' => '1'])
            ->run(['vendor/bin/phpstan', 'analyse', '--no-progress', '--memory-limit=1G'], function (string $type, string $output) {
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
