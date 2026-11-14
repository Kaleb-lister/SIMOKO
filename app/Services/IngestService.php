<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Goal;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IngestService
{
    protected const VALID_COINS = [100, 200, 500, 1000];

    /**
     * Ingest a batch of events from device.
     */
    public function ingest(Device $device, string $firmware, array $events, ?string $clientIp = null): array
    {
        $results = [];
        $lastReportedBalance = null;

        DB::transaction(function () use ($device, $events, &$results, &$lastReportedBalance) {
            // Find current active goal
            $currentGoal = Goal::where('device_id', $device->id)
                ->whereIn('status', ['active', 'reached'])
                ->latest('id')
                ->first();

            foreach ($events as $rawEvent) {
                $eventId = $rawEvent['event_id'] ?? null;

                // Validate event_id regex
                if (! $eventId || ! preg_match('/^[0-9a-f]{8}-[0-9]{1,10}$/', (string) $eventId)) {
                    $results[] = [
                        'event_id' => $eventId ?? 'unknown',
                        'status' => 'rejected',
                        'reason' => 'invalid_event_id',
                    ];
                    continue;
                }

                $type = $rawEvent['type'] ?? null;
                if (! in_array($type, ['deposit', 'withdrawal', 'unknown_change'], true)) {
                    $results[] = [
                        'event_id' => $eventId,
                        'status' => 'rejected',
                        'reason' => 'invalid_type',
                    ];
                    continue;
                }

                $weightDelta = $rawEvent['weight_delta_g'] ?? null;
                if (! is_numeric($weightDelta) || (float) $weightDelta <= 0) {
                    $results[] = [
                        'event_id' => $eventId,
                        'status' => 'rejected',
                        'reason' => 'invalid_weight',
                    ];
                    continue;
                }

                $amount = $rawEvent['amount'] ?? null;
                $coinValue = array_key_exists('coin_value', $rawEvent) ? $rawEvent['coin_value'] : null;

                if ($type === 'unknown_change') {
                    if ((int) $amount !== 0 || $coinValue !== null) {
                        $results[] = [
                            'event_id' => $eventId,
                            'status' => 'rejected',
                            'reason' => 'invalid_amount',
                        ];
                        continue;
                    }
                } else {
                    if (! is_numeric($amount) || ! in_array((int) $amount, self::VALID_COINS, true)) {
                        $results[] = [
                            'event_id' => $eventId,
                            'status' => 'rejected',
                            'reason' => 'invalid_amount',
                        ];
                        continue;
                    }

                    if ($coinValue !== null && (int) $coinValue !== (int) $amount) {
                        $results[] = [
                            'event_id' => $eventId,
                            'status' => 'rejected',
                            'reason' => 'invalid_amount',
                        ];
                        continue;
                    }
                }

                // Check duplicate
                $existing = Transaction::where('device_id', $device->id)
                    ->where('event_id', $eventId)
                    ->first();

                if ($existing) {
                    $results[] = [
                        'event_id' => $eventId,
                        'status' => 'duplicate',
                    ];
                    if (isset($rawEvent['balance_after'])) {
                        $lastReportedBalance = (int) $rawEvent['balance_after'];
                    }
                    continue;
                }

                // Process time
                $timeEstimated = false;
                $occurredAt = null;

                if (! empty($rawEvent['occurred_at'])) {
                    try {
                        $parsedDate = Carbon::parse($rawEvent['occurred_at']);
                        if ($parsedDate->greaterThan(now()->addHours(24))) {
                            $occurredAt = now();
                            $timeEstimated = true;
                        } else {
                            $occurredAt = $parsedDate;
                        }
                    } catch (\Exception $e) {
                        $occurredAt = now();
                        $timeEstimated = true;
                    }
                } else {
                    $occurredAt = now();
                    $timeEstimated = true;
                }

                // Signed amount
                $signedAmount = match ($type) {
                    'deposit' => (int) $amount,
                    'withdrawal' => -((int) $amount),
                    'unknown_change' => 0,
                };

                Transaction::create([
                    'device_id' => $device->id,
                    'goal_id' => $currentGoal?->id,
                    'event_id' => $eventId,
                    'type' => $type,
                    'amount' => $signedAmount,
                    'coin_value' => $coinValue ? (int) $coinValue : null,
                    'weight_delta_g' => (float) $weightDelta,
                    'source' => 'device',
                    'occurred_at' => $occurredAt,
                    'received_at' => now(),
                    'time_estimated' => $timeEstimated,
                    'needs_review' => ($type === 'unknown_change'),
                    'status' => 'active',
                ]);

                if (isset($rawEvent['balance_after'])) {
                    $lastReportedBalance = (int) $rawEvent['balance_after'];
                }

                $results[] = [
                    'event_id' => $eventId,
                    'status' => 'created',
                ];
            }
        });

        // Refresh goal & update device
        $goal = GoalService::refresh($device);
        $balance = BalanceService::for($device);

        $deviceUpdate = [
            'last_seen_at' => now(),
            'firmware_version' => $firmware,
        ];

        if ($clientIp) {
            $deviceUpdate['last_ip'] = $clientIp;
        }

        if ($lastReportedBalance !== null) {
            $deviceUpdate['last_reported_balance'] = $lastReportedBalance;
        }

        $device->update($deviceUpdate);

        $goalResponse = null;
        if ($goal) {
            $goalResponse = [
                'target_amount' => $goal->target_amount,
                'status' => $goal->status,
            ];
        }

        return [
            'results' => $results,
            'goal' => $goalResponse,
            'balance' => $balance,
            'server_time' => now()->toIso8601ZuluString(),
        ];
    }
}

