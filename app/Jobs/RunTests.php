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
            ->env($this->preparedEnvironment())
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
     * The environment for the test process: every variable from .env is
     * removed — real environment variables beat phpunit.xml's <env>
     * settings, so the tests would otherwise run in the "local"
     * environment. Colors and spinners are disabled for clean output.
     *
     * @return array<string, string|false>
     */
    private function preparedEnvironment(): array
    {
        $withoutEnvFile = collect(file(base_path('.env')) ?: [])
            ->filter(fn (string $line): bool => preg_match('/^\s*[A-Z0-9_]+\s*=/', $line) === 1)
            ->mapWithKeys(fn (string $line): array => [trim(explode('=', $line, 2)[0]) => false])
            ->all();

        return [...$withoutEnvFile, 'TERM' => 'dumb', 'NO_COLOR' => '1'];
    }
}
