<?php

namespace App\Jobs;

use App\Support\GearRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Throwable;

class RunClaudeFix implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    private string $buffer = '';

    private const PROMPT = <<<'PROMPT'
        Run `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`.
        If it reports errors, fix them in the application code with minimal
        changes. Do not modify phpstan.neon and do not lower the level.
        Re-run PHPStan afterwards to confirm it passes.
        PROMPT;

    public function handle(): void
    {
        GearRun::running();
        GearRun::append('$ claude -p "fix phpstan" --permission-mode acceptEdits');

        $binary = (string) config('gears.claude.binary');

        $result = Process::path(base_path())
            ->timeout(870)
            ->env(['TERM' => 'dumb', 'NO_COLOR' => '1'])
            ->run([
                $binary, '-p', self::PROMPT,
                '--permission-mode', 'acceptEdits',
                '--allowedTools', 'Bash(vendor/bin/phpstan:*)',
                '--output-format', 'stream-json',
                '--verbose',
            ], function (string $type, string $output) {
                $this->consume($output);
            });

        GearRun::finish($result->successful());
    }

    public function failed(?Throwable $exception): void
    {
        GearRun::append('Job failed: '.($exception?->getMessage() ?? 'unknown error'));
        GearRun::finish(false);
    }

    /**
     * Output arrives in arbitrary chunks; events are one JSON object
     * per line, so buffer until each newline completes.
     */
    private function consume(string $output): void
    {
        $this->buffer .= $output;

        while (($position = strpos($this->buffer, "\n")) !== false) {
            $line = trim(substr($this->buffer, 0, $position));
            $this->buffer = substr($this->buffer, $position + 1);

            if ($line !== '') {
                $this->handleEvent($line);
            }
        }
    }

    /**
     * Translate a stream-json event into human-readable console lines.
     */
    private function handleEvent(string $line): void
    {
        $event = json_decode($line, true);

        if (! is_array($event)) {
            GearRun::append($line);

            return;
        }

        $type = $event['type'] ?? null;

        if ($type === 'assistant') {
            $content = data_get($event, 'message.content');

            if (! is_array($content)) {
                return;
            }

            foreach ($content as $block) {
                if (! is_array($block)) {
                    continue;
                }

                if (($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                    GearRun::append($block['text']);
                }

                if (($block['type'] ?? null) === 'tool_use') {
                    $tool = is_string($block['name'] ?? null) ? $block['name'] : 'tool';
                    $target = data_get($block, 'input.file_path') ?? data_get($block, 'input.command');
                    GearRun::append('⏺ '.$tool.(is_string($target) ? ': '.$target : ''));
                }
            }
        }

        if ($type === 'result') {
            $verdict = is_string($event['result'] ?? null) ? $event['result'] : 'done';
            GearRun::append('');
            GearRun::append($verdict);
        }
    }
}
