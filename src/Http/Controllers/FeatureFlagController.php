<?php

declare(strict_types=1);

namespace Beacon\PennantBeam\Http\Controllers;

use Beacon\PennantDriver\BeaconScope;
use Illuminate\Http\JsonResponse;
use Laravel\Pennant\Feature;

class FeatureFlagController
{
    public function __invoke(string $featureFlag): JsonResponse
    {
        $active = Feature::for(new BeaconScope(\request()->json()))
            ->active($featureFlag);

        $value = $active !== false ? Feature::for(new BeaconScope(\request()->json()))->value($featureFlag) : null;

        return response()->json([
            'featureFlag' => $featureFlag,
            'status' => $active,
            'value' => !is_bool($value) ? $value : null,
        ]);
    }
}
