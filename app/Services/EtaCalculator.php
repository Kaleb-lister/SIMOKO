<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class EtaCalculator
{
    /**
     * Calculate estimated days to reach savings goal.
     *
     * @param int $target
     * @param int $balance
     * @param Collection|array $deposits Active deposit or positive manual_adjust records
     * @param Carbon|null $now Reference date (defaults to today)
     * @return array
     */
    public static function calculate(int $target, int $balance, $deposits = [], ?Carbon $now = null): array
    {
        $today = $now ? $now->copy()->startOfDay() : Carbon::today();

        if ($target <= 0) {
            return [
                'status' => 'insufficient_data',
                'days' => null,
                'target_date' => null,
                'daily_average' => 0,
                'message' => 'Belum ada target',
            ];
        }

        if ($balance >= $target) {
            return [
                'status' => 'reached',
                'days' => 0,
                'target_date' => null,
                'daily_average' => 0,
                'message' => 'Target tercapai',
            ];
        }

        $depositsCol = collect($deposits);
        if ($depositsCol->isEmpty()) {
            return [
                'status' => 'insufficient_data',
                'days' => null,
                'target_date' => null,
                'daily_average' => 0,
                'message' => 'Belum cukup data',
            ];
        }

        // Window = last 7 days including today (start is at most today - 6 days)
        $sevenDaysAgo = $today->copy()->subDays(6);

        // Find the earliest deposit date overall
        $firstDepositDate = $depositsCol->map(function ($item) {
            $val = is_array($item) ? ($item['occurred_at'] ?? $item['date']) : ($item->occurred_at ?? $item->date);
            return $val instanceof Carbon ? $val->copy()->startOfDay() : Carbon::parse($val)->startOfDay();
        })->min();

        if (! $firstDepositDate) {
            return [
                'status' => 'insufficient_data',
                'days' => null,
                'target_date' => null,
                'daily_average' => 0,
                'message' => 'Belum cukup data',
            ];
        }

        $startDate = $sevenDaysAgo->greaterThan($firstDepositDate) ? $sevenDaysAgo : $firstDepositDate;
        $span = (int) $startDate->diffInDays($today) + 1;

        if ($span < 2) {
            return [
                'status' => 'insufficient_data',
                'days' => null,
                'target_date' => null,
                'daily_average' => 0,
                'message' => 'Belum cukup data',
            ];
        }

        // Sum deposits within the window [startDate, today endOfDay]
        $windowDeposits = $depositsCol->filter(function ($item) use ($startDate, $today) {
            $val = is_array($item) ? ($item['occurred_at'] ?? $item['date']) : ($item->occurred_at ?? $item->date);
            $date = $val instanceof Carbon ? $val : Carbon::parse($val);
            return $date->between($startDate->copy()->startOfDay(), $today->copy()->endOfDay());
        });

        $totalDepositsInWindow = $windowDeposits->sum(function ($item) {
            return is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0);
        });

        $dailyAverage = $totalDepositsInWindow / $span;

        if ($dailyAverage <= 0) {
            return [
                'status' => 'insufficient_data',
                'days' => null,
                'target_date' => null,
                'daily_average' => 0,
                'message' => 'Belum cukup data',
            ];
        }

        $remaining = $target - $balance;
        $days = (int) ceil($remaining / $dailyAverage);
        $targetDate = $today->copy()->addDays($days);

        return [
            'status' => 'estimated',
            'days' => $days,
            'target_date' => $targetDate,
            'daily_average' => (int) round($dailyAverage),
            'message' => "± {$days} hari lagi (≈ " . $targetDate->translatedFormat('d M Y') . ")",
        ];
    }
}
