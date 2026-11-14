<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected Device $device;
    protected string $plainToken;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
            'must_change_credentials' => false,
        ]);

        $this->plainToken = 'test-token-' . Str::random(30);

        $this->device = Device::create([
            'user_id' => $user->id,
            'device_uid' => 'SIMOKO-TEST',
            'token_hash' => hash('sha256', $this->plainToken),
            'last_reported_balance' => 0,
        ]);
    }

    // Kasus 1: Tanpa header Authorization -> 401
    public function test_case_1_without_authorization_header(): void
    {
        $response = $this->postJson('/api/v1/device/events', [
            'firmware' => '0.3.0',
            'events' => [],
        ]);

        $response->assertStatus(401);
        $response->assertJson(['code' => 'unauthorized']);
    }

    // Kasus 2: Token salah -> 401
    public function test_case_2_wrong_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer wrong-token-xyz')
            ->postJson('/api/v1/device/events', [
                'firmware' => '0.3.0',
                'events' => [],
            ]);

        $response->assertStatus(401);
        $response->assertJson(['code' => 'unauthorized']);
    }

    // Kasus 3: Batch 1 deposit valid -> 200, created, balance naik
    public function test_case_3_valid_deposit(): void
    {
        $payload = [
            'firmware' => '0.3.0',
            'events' => [
                [
                    'event_id' => 'a1b2c3d4-1',
                    'type' => 'deposit',
                    'amount' => 500,
                    'coin_value' => 500,
                    'weight_delta_g' => 3.12,
                    'occurred_at' => '2026-11-15T03:20:10Z',
                    'uptime_ms' => 123456,
                    'balance_after' => 500,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/device/events', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'results' => [
                ['event_id' => 'a1b2c3d4-1', 'status' => 'created'],
            ],
            'balance' => 500,
        ]);

        $this->assertDatabaseHas('transactions', [
            'device_id' => $this->device->id,
            'event_id' => 'a1b2c3d4-1',
            'amount' => 500,
            'status' => 'active',
        ]);
    }

    // Kasus 4: Kirim ulang body yang sama persis -> 200, duplicate, saldo tidak berubah
    public function test_case_4_duplicate_events(): void
    {
        $payload = [
            'firmware' => '0.3.0',
            'events' => [
                [
                    'event_id' => 'a1b2c3d4-2',
                    'type' => 'deposit',
                    'amount' => 1000,
                    'coin_value' => 1000,
                    'weight_delta_g' => 4.5,
                    'occurred_at' => '2026-11-15T03:20:10Z',
                ],
            ],
        ];

        // First send
        $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/device/events', $payload)
            ->assertStatus(200);

        // Re-send same payload
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/device/events', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'results' => [
                ['event_id' => 'a1b2c3d4-2', 'status' => 'duplicate'],
            ],
            'balance' => 1000,
        ]);

        $this->assertEquals(1, Transaction::where('event_id', 'a1b2c3d4-2')->count());
    }

    // Kasus 5: Batch campuran valid + amount: 300 -> 200, satu created, satu rejected/invalid_amount
    public function test_case_5_mixed_valid_and_invalid_amount(): void
    {
        $payload = [
            'firmware' => '0.3.0',
            'events' => [
                [
                    'event_id' => 'a1b2c3d4-3',
                    'type' => 'deposit',
                    'amount' => 500,
                    'coin_value' => 500,
                    'weight_delta_g' => 3.1,
                ],
                [
                    'event_id' => 'a1b2c3d4-4',
                    'type' => 'deposit',
                    'amount' => 300, // Invalid coin value
                    'coin_value' => 300,
                    'weight_delta_g' => 2.5,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/device/events', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'results' => [
                ['event_id' => 'a1b2c3d4-3', 'status' => 'created'],
                ['event_id' => 'a1b2c3d4-4', 'status' => 'rejected', 'reason' => 'invalid_amount'],
            ],
            'balance' => 500,
        ]);
    }

    // Kasus 6: occurred_at: null -> created, time_estimated=true
    public function test_case_6_occurred_at_null(): void
    {
        $payload = [
            'firmware' => '0.3.0',
            'events' => [
                [
                    'event_id' => 'a1b2c3d4-5',
                    'type' => 'deposit',
                    'amount' => 200,
                    'coin_value' => 200,
                    'weight_delta_g' => 2.38,
                    'occurred_at' => null,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/device/events', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'results' => [
                ['event_id' => 'a1b2c3d4-5', 'status' => 'created'],
            ],
        ]);

        $this->assertDatabaseHas('transactions', [
            'event_id' => 'a1b2c3d4-5',
            'time_estimated' => true,
        ]);
    }

    // Kasus 7: 21 event dalam satu batch -> 422
    public function test_case_7_more_than_20_events(): void
    {
        $events = [];
        for ($i = 1; $i <= 21; $i++) {
            $events[] = [
                'event_id' => sprintf('a1b2c3d4-%d', $i),
                'type' => 'deposit',
                'amount' => 100,
                'weight_delta_g' => 1.79,
            ];
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/device/events', [
                'firmware' => '0.3.0',
                'events' => $events,
            ]);

        $response->assertStatus(422);
    }

    // Kasus 8: unknown_change -> created, saldo tidak berubah, needs_review=true
    public function test_case_8_unknown_change(): void
    {
        $payload = [
            'firmware' => '0.3.0',
            'events' => [
                [
                    'event_id' => 'a1b2c3d4-8',
                    'type' => 'unknown_change',
                    'amount' => 0,
                    'coin_value' => null,
                    'weight_delta_g' => 7.8,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/device/events', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'results' => [
                ['event_id' => 'a1b2c3d4-8', 'status' => 'created'],
            ],
            'balance' => 0,
        ]);

        $this->assertDatabaseHas('transactions', [
            'event_id' => 'a1b2c3d4-8',
            'type' => 'unknown_change',
            'amount' => 0,
            'needs_review' => true,
        ]);
    }

    // Kasus 9: PUT /device/goal 10000, ulangi, lalu 15000 -> 200 semua, target akhir 15000, hanya 1 goal aktif
    public function test_case_9_put_device_goal(): void
    {
        // Set goal 10000
        $r1 = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->putJson('/api/v1/device/goal', ['target_amount' => 10000]);
        $r1->assertStatus(200);
        $r1->assertJson(['goal' => ['target_amount' => 10000, 'status' => 'active']]);

        // Ulangi 10000
        $r2 = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->putJson('/api/v1/device/goal', ['target_amount' => 10000]);
        $r2->assertStatus(200);

        // Ubah jadi 15000
        $r3 = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->putJson('/api/v1/device/goal', ['target_amount' => 15000]);
        $r3->assertStatus(200);
        $r3->assertJson(['goal' => ['target_amount' => 15000, 'status' => 'active']]);

        $activeGoalsCount = Goal::where('device_id', $this->device->id)->whereIn('status', ['active', 'reached'])->count();
        $this->assertEquals(1, $activeGoalsCount);
    }

    // Kasus 10: GET /device/state -> 200, last_seen_at berubah
    public function test_case_10_get_device_state(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->getJson('/api/v1/device/state');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'goal',
            'balance',
            'server_time',
        ]);

        $this->device->refresh();
        $this->assertNotNull($this->device->last_seen_at);
    }

    // Kasus 11: Withdrawal 200 setelah deposit 500 -> saldo 300
    public function test_case_11_withdrawal_after_deposit(): void
    {
        $payload = [
            'firmware' => '0.3.0',
            'events' => [
                [
                    'event_id' => 'a1b2c3d4-11',
                    'type' => 'deposit',
                    'amount' => 500,
                    'coin_value' => 500,
                    'weight_delta_g' => 3.1,
                ],
                [
                    'event_id' => 'a1b2c3d4-12',
                    'type' => 'withdrawal',
                    'amount' => 200,
                    'coin_value' => 200,
                    'weight_delta_g' => 2.38,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/device/events', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'balance' => 300,
        ]);
    }
}
