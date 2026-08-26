<?php

use App\Jobs\DeployProduction;
use App\Jobs\RunPhpstan;
use App\Jobs\RunTests;

return [

    /*
    |--------------------------------------------------------------------------
    | Gear → action mapping
    |--------------------------------------------------------------------------
    | The shifter POSTs a gear number; this map decides what it means.
    | 'hold' (seconds) makes the gear an armed action: the lever must stay
    | in the gate that long before the action fires.
    */

    'map' => [

        '1' => [
            'label' => 'Run test suite',
            'job' => RunTests::class,
        ],

        '2' => [
            'label' => 'Run PHPStan',
            'job' => RunPhpstan::class,
        ],

        '5' => [
            'label' => 'Deploy production',
            'job' => DeployProduction::class,
            'hold' => 2,
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

];
