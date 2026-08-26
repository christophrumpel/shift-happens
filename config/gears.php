<?php

use App\Jobs\DeployProduction;
use App\Jobs\ResetDemo;
use App\Jobs\RunClaudeFix;
use App\Jobs\RunPhpstan;
use App\Jobs\RunTests;

return [

    /*
    |--------------------------------------------------------------------------
    | Gear → action mapping
    |--------------------------------------------------------------------------
    | The shifter POSTs a gear number; this map decides what it means.
    | Unmapped gears do nothing (gear 1 is deliberately free — a safe
    | "just show the gear" gate). 'hold' (seconds) makes a gear an armed
    | action: the lever must stay in the gate that long before it fires.
    */

    'map' => [

        '2' => [
            'label' => 'Run test suite',
            'job' => RunTests::class,
        ],

        '3' => [
            'label' => 'Run PHPStan',
            'job' => RunPhpstan::class,
        ],

        '4' => [
            'label' => 'Fix with Claude',
            'job' => RunClaudeFix::class,
        ],

        '5' => [
            'label' => 'Deploy production',
            'job' => DeployProduction::class,
            'hold' => 2,
        ],

        'R' => [
            'label' => 'Reset demo',
            'job' => ResetDemo::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Cloud CLI
    |--------------------------------------------------------------------------
    | project_path: the repo `cloud deploy` runs in — a project bound to a
    | Cloud app via `cloud repo:config`. Defaults to this app.
    */

    'cloud' => [
        'binary' => env('CLOUD_BINARY', 'cloud'),
        'project_path' => env('CLOUD_PROJECT_PATH', base_path()),
    ],

    'claude' => [
        'binary' => env('CLAUDE_BINARY', 'claude'),
    ],

];
