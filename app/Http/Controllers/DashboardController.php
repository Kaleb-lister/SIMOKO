<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Goal;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Services\EtaCalculator;
use App\Services\GoalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        /** @var Device|null $device */
        $device = $user->device;

        if (! $device) {
            return view('dashboard-no-device');
        }

        // Refresh goal state
        $goal = GoalService::refresh($device);
        $balance = BalanceService::for($device);

        // Active deposits for ETA & chart
        $allActiveDeposits = Transaction::where('device_id', $device->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('type', 'deposit')
                    ->orWhere(function ($sub) {
                        $sub->where('type', 'manual_adjust')->where('amount', '>', 0);
                    });
            })
            ->get();

        // Target & ETA
        $targetAmount = $goal ? $goal->target_amount : 0;
        $eta = EtaCalculator::calculate($targetAmount, $balance, $allActiveDeposits);

        // Progress percentage
        $progressPercent = $targetAmount > 0 ? min(100, round(($balance / $targetAmount) * 100, 1)) : 0;

        // Deposits today
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $depositsToday = Transaction::where('device_id', $device->id)
            ->where('status', 'active')
            ->where('type', 'deposit')
            ->whereBetween('occurred_at', [$todayStart, $todayEnd])
            ->sum('amount');

        // Needs review banner count
        $needsReviewCount = Transaction::where('device_id', $device->id)
            ->where('status', 'active')
            ->where('needs_review', true)
            ->count();

        // 14 days chart data preparation
        $chartLabels = [];
        $chartDeposits = [];
        $chartCumulativeBalance = [];

        // Pre-fetch transactions in the last 14 days and before
        $fourteenDaysAgo = Carbon::today()->subDays(13)->startOfDay();

        // Cumulative balance before 14 days ago
        $balanceBefore = (int) Transaction::where('device_id', $device->id)
            ->where('status', 'active')
            ->where('occurred_at', '<', $fourteenDaysAgo)
            ->sum('amount');

        $transactions14d = Transaction::where('device_id', $device->id)
            ->where('status', 'active')
            ->where('occurred_at', '>=', $fourteenDaysAgo)
            ->orderBy('occurred_at')
            ->get();

        $runningCumBalance = $balanceBefore;

        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayKey = $day->format('Y-m-d');
            $chartLabels[] = $day->translatedFormat('d M');

            // Daily deposits
            $dayDeposits = $transactions14d->filter(function ($t) use ($dayKey) {
                return Carbon::parse($t->occurred_at)->format('Y-m-d') === $dayKey && $t->type === 'deposit';
            })->sum('amount');

            // Net change on this day for cumulative line
            $dayNetChange = $transactions14d->filter(function ($t) use ($dayKey) {
                return Carbon::parse($t->occurred_at)->format('Y-m-d') === $dayKey;
            })->sum('amount');

            $runningCumBalance += $dayNetChange;

            $chartDeposits[] = (int) $dayDeposits;
            $chartCumulativeBalance[] = max(0, (int) $runningCumBalance);
        }

        // Device connection status
        $isOnline = false;
        $lastSeenText = 'Belum pernah terhubung';
        if ($device->last_seen_at) {
            $diffMinutes = $device->last_seen_at->diffInMinutes(now());
            $isOnline = $diffMinutes < 10;
            $lastSeenText = $device->last_seen_at->diffForHumans();
        }

        $balanceMismatch = ($device->last_reported_balance !== $balance && $device->last_seen_at !== null);

        return view('dashboard', [
            'device' => $device,
            'goal' => $goal,
            'balance' => $balance,
            'targetAmount' => $targetAmount,
            'progressPercent' => $progressPercent,
            'depositsToday' => $depositsToday,
            'eta' => $eta,
            'needsReviewCount' => $needsReviewCount,
            'isOnline' => $isOnline,
            'lastSeenText' => $lastSeenText,
            'balanceMismatch' => $balanceMismatch,
            'chartLabels' => $chartLabels,
            'chartDeposits' => $chartDeposits,
            'chartCumulativeBalance' => $chartCumulativeBalance,
        ]);
    }
}
