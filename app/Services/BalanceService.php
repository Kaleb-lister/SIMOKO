<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Transaction;

class BalanceService
{
    /**
     * Calculate current official active balance for the given device.
     */
    public static function for(Device $device): int
    {
        return (int) Transaction::where('device_id', $device->id)
            ->where('status', 'active')
            ->sum('amount');
    }
}

