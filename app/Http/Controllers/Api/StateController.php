<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\BalanceService;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $device->update([
            'last_seen_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        $goal = GoalService::refresh($device);
        $balance = BalanceService::for($device);

        return response()->json([
            'goal' => $goal ? [
                'target_amount' => $goal->target_amount,
                'status' => $goal->status,
            ] : null,
            'balance' => $balance,
            'server_time' => now()->toIso8601ZuluString(),
        ], 200);
    }
}

