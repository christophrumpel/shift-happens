<?php

namespace App\Http\Controllers;

use App\Support\GearRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GearController
{
    public function shift(Request $request): JsonResponse
    {
        $gear = $request->string('gear')->toString();

        /** @var array{label: string, job: class-string<ShouldQueue>, hold?: int}|null $action */
        $action = config("gears.map.{$gear}");

        if ($action === null) {
            return response()->json(['result' => 'ignored']);
        }

        if (GearRun::isRunning()) {
            return response()->json(['result' => 'busy'], 409);
        }

        GearRun::start($gear, $action['label']);

        dispatch(new $action['job']);

        return response()->json(['result' => 'queued']);
    }

    public function reset(): JsonResponse
    {
        if (! GearRun::isRunning()) {
            Cache::flush();
        }

        return response()->json(['result' => 'reset']);
    }

    public function status(): JsonResponse
    {
        return response()->json(GearRun::current());
    }
}
