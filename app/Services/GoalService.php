<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Goal;

class GoalService
{
    /**
     * Recalculate goal status based on the device balance.
     */
    public static function refresh(Device $device): ?Goal
    {
        // Get the latest goal for this device
        $latestGoal = Goal::where('device_id', $device->id)
            ->latest('id')
            ->first();

        if (! $latestGoal) {
            return null;
        }

        // Archive all other goals
        Goal::where('device_id', $device->id)
            ->where('id', '!=', $latestGoal->id)
            ->where('status', '!=', 'archived')
            ->update(['status' => 'archived']);

        $currentBalance = BalanceService::for($device);

        if ($currentBalance >= $latestGoal->target_amount) {
            if ($latestGoal->status !== 'reached') {
                $latestGoal->update([
                    'status' => 'reached',
                    'reached_at' => $latestGoal->reached_at ?? now(),
                ]);
            }
        } else {
            if ($latestGoal->status === 'reached') {
                $latestGoal->update([
                    'status' => 'active',
                    'reached_at' => null,
                ]);
            }
        }

        return $latestGoal->fresh();
    }
}

