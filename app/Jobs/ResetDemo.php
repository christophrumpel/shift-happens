<?php

namespace App\Jobs;

use App\Support\GearRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ResetDemo implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    /**
     * Two intentional PHPStan level 7 errors: averageDuration() promises
     * an int but returns float|int, lastDeployedAt() promises a string
     * but returns mixed.
     */
    private const BROKEN_CLASS = <<<'PHP'
        <?php

        namespace App\Support;

        class DeploymentStats
        {
            /**
             * @param  array<string, int>  $durations
             */
            public function averageDuration(array $durations): int
            {
                return array_sum($durations) / count($durations);
            }

            public function lastDeployedAt(): string
            {
                return cache()->get('shifter:last-deploy', 'never');
            }
        }

        PHP;

    public function handle(): void
    {
        GearRun::running();
        GearRun::append('$ resetting the demo');
        GearRun::append('');

        file_put_contents(app_path('Support/DeploymentStats.php'), self::BROKEN_CLASS);

        GearRun::append('app/Support/DeploymentStats.php is broken again.');
        GearRun::append('Shift into 3 to let PHPStan find it, then into 4 to let Claude fix it.');
        GearRun::finish(true);
    }

    public function failed(?Throwable $exception): void
    {
        GearRun::append('Job failed: '.($exception?->getMessage() ?? 'unknown error'));
        GearRun::finish(false);
    }
}
