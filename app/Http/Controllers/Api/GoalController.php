<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Goal;
use App\Services\BalanceService;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_amount' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'set_at' => ['nullable', 'string'],
        ]);

        /** @var Device $device */
        $device = $request->attributes->get('device');
        $newTarget = (int) $validated['target_amount'];

        $latestGoal = Goal::where('device_id', $device->id)->latest('id')->first();

        if (! $latestGoal) {
            // Rule 1: Tidak ada goal -> buat goal baru (active)
            $goal = Goal::create([
                'device_id' => $device->id,
                'target_amount' => $newTarget,
                'status' => 'active',
            ]);
        } elseif (in_array($latestGoal->status, ['active', 'reached'], true) && $latestGoal->target_amount === $newTarget) {
            // Rule 2: Nilai sama -> tidak ada perubahan
            $goal = $latestGoal;
        } elseif ($latestGoal->status === 'active') {
            // Rule 3: Goal terbaru active dan nilai berbeda -> ubah target di tempat
            $latestGoal->update(['target_amount' => $newTarget]);
            $goal = $latestGoal;
        } else {
            // Rule 4: Goal terbaru reached dan nilai berbeda -> goal lama archived, buat baru
            $latestGoal->update(['status' => 'archived']);
            $goal = Goal::create([
                'device_id' => $device->id,
                'target_amount' => $newTarget,
                'status' => 'active',
            ]);
        }

        $refreshedGoal = GoalService::refresh($device);
        $balance = BalanceService::for($device);

        return response()->json([
            'goal' => [
                'target_amount' => $refreshedGoal->target_amount,
                'status' => $refreshedGoal->status,
            ],
            'balance' => $balance,
            'server_time' => now()->toIso8601ZuluString(),
        ], 200);
    }
}

