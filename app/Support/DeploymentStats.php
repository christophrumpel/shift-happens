<?php

declare(strict_types=1);

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
        return now()->toDateTimeString();
    }
}
