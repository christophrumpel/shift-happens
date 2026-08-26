<?php

namespace App\Jobs;

use App\Support\GearRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Throwable;

class RunTests implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function handle(): void
    {
        GearRun::running();
        GearRun::append('$ php artisan test');

        $result = Process::path(base_path())
            ->timeout(570)
            ->env([...$this->withoutInheritedEnv(), 'TERM' => 'dumb', 'NO_COLOR' => '1'])
            ->run(['php', 'artisan', 'test', '--colors=never'], function (string $type, string $output) {
                GearRun::append($output);
            });

        GearRun::finish($result->successful());
    }

    public function failed(?Throwable $exception): void
    {
        GearRun::append('Job failed: '.($exception?->getMessage() ?? 'unknown error'));
        GearRun::finish(false);
    }

    /**
     * The queue worker's environment carries everything from .env
     * (APP_ENV=local included), and real environment variables beat
     * phpunit.xml's <env> entries in child processes. Setting a key
     * to false removes it from the child's environment, so the test
     * run actually runs in the "testing" environment.
     *
     * @return array<string, false>
     */
    private function withoutInheritedEnv(): array
    {
        $lines = file(base_path('.env')) ?: [];

        $env = [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*[A-Z0-9_]+\s*=/', $line) === 1) {
                $env[trim(explode('=', $line, 2)[0])] = false;
            }
        }

        return $env;
    }
}
