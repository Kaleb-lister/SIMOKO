<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'username' => 'demo',
            'password' => Hash::make('demo1234'),
            'must_change_credentials' => false,
            'last_login_at' => now(),
        ]);

        $token = 'demo-token-123456789012345678901234567890';
        $device = Device::create([
            'user_id' => $user->id,
            'device_uid' => 'SIMOKO-DEMO',
            'token_hash' => hash('sha256', $token),
            'last_seen_at' => now(),
            'last_ip' => '127.0.0.1',
            'firmware_version' => '1.0.0',
            'last_reported_balance' => 0,
        ]);

        $goal = Goal::create([
            'device_id' => $device->id,
            'target_amount' => 50000,
            'status' => 'active',
        ]);

        $coinValues = [100, 200, 500, 1000];
        $coinWeights = [
            100 => 1.79,
            200 => 2.38,
            500 => 3.10,
            1000 => 4.50,
        ];

        $runningBalance = 0;
        $bootId = 'demo1234';
        $seq = 1;

        // Seed 14 days of realistic deposit events
        for ($day = 13; $day >= 0; $day--) {
            $depositCount = rand(1, 4);
            $dayDate = Carbon::today()->subDays($day);

            for ($i = 0; $i < $depositCount; $i++) {
                $coin = $coinValues[array_rand($coinValues)];
                $runningBalance += $coin;
                $occurredAt = $dayDate->copy()->addHours(8 + ($i * 3))->addMinutes(rand(0, 59));

                Transaction::create([
                    'device_id' => $device->id,
                    'goal_id' => $goal->id,
                    'event_id' => sprintf('%s-%d', $bootId, $seq++),
                    'type' => 'deposit',
                    'amount' => $coin,
                    'coin_value' => $coin,
                    'weight_delta_g' => $coinWeights[$coin],
                    'source' => 'device',
                    'occurred_at' => $occurredAt,
                    'received_at' => $occurredAt,
                    'time_estimated' => false,
                    'needs_review' => false,
                    'status' => 'active',
                ]);
            }
        }

        // Add 1 unknown_change on yesterday for review demo
        Transaction::create([
            'device_id' => $device->id,
            'goal_id' => $goal->id,
            'event_id' => sprintf('%s-%d', $bootId, $seq++),
            'type' => 'unknown_change',
            'amount' => 0,
            'coin_value' => null,
            'weight_delta_g' => 6.25,
            'source' => 'device',
            'occurred_at' => Carbon::yesterday()->setHour(14)->setMinute(10),
            'received_at' => Carbon::yesterday()->setHour(14)->setMinute(10),
            'time_estimated' => false,
            'needs_review' => true,
            'status' => 'active',
        ]);

        $device->update(['last_reported_balance' => $runningBalance]);
    }
}
