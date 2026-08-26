<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The single shared "current run" record.
 *
 * The queue worker writes to it while an action runs;
 * the browser polls it via GET /gear/status.
 *
 * @phpstan-type Run array{
 *     gear: string,
 *     label: string,
 *     status: string,
 *     lines: list<string>,
 *     started_at: string,
 *     finished_at: string|null,
 * }
 */
class GearRun
{
    private const KEY = 'shifter:current-run';

    private const MAX_LINES = 400;

    public static function start(string $gear, string $label): void
    {
        self::put([
            'gear' => $gear,
            'label' => $label,
            'status' => 'queued',
            'lines' => [],
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
        ]);
    }

    public static function running(): void
    {
        self::update(['status' => 'running']);
    }

    public static function append(string $output): void
    {
        $run = self::current();

        if ($run === null) {
            return;
        }

        // strip ANSI colour codes so the browser gets clean text
        $clean = (string) preg_replace('/\e\[[0-9;?]*[A-Za-z]/', '', $output);

        foreach (preg_split('/\r\n|\r|\n/', rtrim($clean, "\r\n")) ?: [] as $line) {
            $run['lines'][] = rtrim($line);
        }

        $run['lines'] = array_slice($run['lines'], -self::MAX_LINES);

        self::put($run);
    }

    public static function finish(bool $success): void
    {
        self::update([
            'status' => $success ? 'passed' : 'failed',
            'finished_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return Run|null
     */
    public static function current(): ?array
    {
        /** @var Run|null */
        return Cache::get(self::KEY);
    }

    public static function isRunning(): bool
    {
        $run = self::current();

        return $run !== null && in_array($run['status'], ['queued', 'running'], true);
    }

    /**
     * @param  Run  $run
     */
    private static function put(array $run): void
    {
        Cache::put(self::KEY, $run, now()->addHour());
    }

    /**
     * @param  array{status?: string, finished_at?: string|null}  $attributes
     */
    private static function update(array $attributes): void
    {
        $run = self::current();

        if ($run === null) {
            return;
        }

        self::put(array_merge($run, $attributes));
    }
}
