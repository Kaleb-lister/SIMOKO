<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Goal;
use App\Models\Transaction;
use App\Services\GoalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        /** @var Device $device */
        $device = $user->device;

        if (! $device) {
            abort(404, 'Alat tidak ditemukan.');
        }

        $days = (int) $request->input('days', 30);
        if (! in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        $onlyReview = (bool) $request->input('review', false);

        $startDate = Carbon::today()->subDays($days - 1)->startOfDay();

        $query = Transaction::where('device_id', $device->id);

        if ($onlyReview) {
            $query->where('needs_review', true);
        } else {
            $query->where('occurred_at', '>=', $startDate);
        }

        $transactions = $query->orderBy('occurred_at', 'desc')->get();

        // Group transactions by date
        $grouped = $transactions->groupBy(function ($item) {
            return Carbon::parse($item->occurred_at)->format('Y-m-d');
        });

        return view('transaksi.index', [
            'device' => $device,
            'grouped' => $grouped,
            'currentDays' => $days,
            'onlyReview' => $onlyReview,
            'totalCount' => $transactions->count(),
        ]);
    }

    /**
     * Store a new manual transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        /** @var Device $device */
        $device = $user->device;

        $request->validate([
            'amount' => ['required', 'integer', 'not_in:0'],
            'occurred_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $occurredAt = $request->filled('occurred_at')
            ? Carbon::parse($request->input('occurred_at'))
            : now();

        $currentGoal = Goal::where('device_id', $device->id)
            ->whereIn('status', ['active', 'reached'])
            ->latest('id')
            ->first();

        Transaction::create([
            'device_id' => $device->id,
            'goal_id' => $currentGoal?->id,
            'event_id' => null,
            'type' => 'manual_adjust',
            'amount' => (int) $request->input('amount'),
            'coin_value' => null,
            'weight_delta_g' => null,
            'source' => 'web',
            'occurred_at' => $occurredAt,
            'received_at' => now(),
            'time_estimated' => false,
            'needs_review' => false,
            'status' => 'active',
            'note' => $request->input('note'),
        ]);

        GoalService::refresh($device);

        return redirect()->route('transaksi.index')->with('status', 'Transaksi manual berhasil ditambahkan.');
    }

    /**
     * Correct the nominal amount of an existing transaction.
     */
    public function koreksi(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $device = $user->device;

        $transaction = Transaction::where('id', $id)->firstOrFail();

        if ($transaction->device_id !== $device->id) {
            abort(403, 'Akses ditolak: transaksi bukan milik alat Anda.');
        }

        if ($transaction->status === 'voided') {
            return redirect()->back()->withErrors(['error' => 'Transaksi yang sudah dibatalkan tidak dapat dikoreksi.']);
        }

        $request->validate([
            'amount' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $newAmount = (int) $request->input('amount');

        $updateData = [
            'amount' => $newAmount,
            'needs_review' => false,
        ];

        if ($transaction->original_amount === null) {
            $updateData['original_amount'] = $transaction->amount;
        }

        if ($request->filled('note')) {
            $updateData['note'] = $request->input('note');
        }

        $transaction->update($updateData);

        GoalService::refresh($device);

        return redirect()->back()->with('status', 'Nominal transaksi berhasil dikoreksi.');
    }

    /**
     * Void / cancel an existing transaction.
     */
    public function batal(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $device = $user->device;

        $transaction = Transaction::where('id', $id)->firstOrFail();

        if ($transaction->device_id !== $device->id) {
            abort(403, 'Akses ditolak: transaksi bukan milik alat Anda.');
        }

        $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ]);

        $transaction->update([
            'status' => 'voided',
            'voided_at' => now(),
            'void_reason' => $request->input('void_reason'),
            'needs_review' => false,
        ]);

        GoalService::refresh($device);

        return redirect()->back()->with('status', 'Transaksi berhasil dibatalkan.');
    }
}
