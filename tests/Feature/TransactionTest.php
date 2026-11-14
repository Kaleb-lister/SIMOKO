<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected Device $device1;
    protected User $user2;
    protected Device $device2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::create([
            'username' => 'owner1',
            'password' => Hash::make('password123'),
            'must_change_credentials' => false,
        ]);

        $this->device1 = Device::create([
            'user_id' => $this->user1->id,
            'device_uid' => 'SIMOKO-0001',
            'token_hash' => hash('sha256', 'token1'),
            'last_reported_balance' => 0,
        ]);

        $this->user2 = User::create([
            'username' => 'owner2',
            'password' => Hash::make('password123'),
            'must_change_credentials' => false,
        ]);

        $this->device2 = Device::create([
            'user_id' => $this->user2->id,
            'device_uid' => 'SIMOKO-0002',
            'token_hash' => hash('sha256', 'token2'),
            'last_reported_balance' => 0,
        ]);
    }

    public function test_user_can_view_transactions_page(): void
    {
        $response = $this->actingAs($this->user1)->get('/transaksi');
        $response->assertStatus(200);
        $response->assertSee('Riwayat Transaksi');
        $response->assertSee('SIMOKO-0001');
    }

    public function test_user_can_add_manual_transaction(): void
    {
        $response = $this->actingAs($this->user1)->post('/transaksi', [
            'amount' => 5000,
            'note' => 'Koin kembalian',
        ]);

        $response->assertRedirect('/transaksi');
        $this->assertDatabaseHas('transactions', [
            'device_id' => $this->device1->id,
            'type' => 'manual_adjust',
            'amount' => 5000,
            'source' => 'web',
            'note' => 'Koin kembalian',
        ]);

        $this->assertEquals(5000, BalanceService::for($this->device1));
    }

    public function test_user_can_koreksi_transaction(): void
    {
        $transaction = Transaction::create([
            'device_id' => $this->device1->id,
            'type' => 'deposit',
            'amount' => 200,
            'source' => 'device',
            'occurred_at' => now(),
            'received_at' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user1)->post("/transaksi/{$transaction->id}/koreksi", [
            'amount' => 500,
            'note' => 'Ternyata koin 500',
        ]);

        $response->assertSessionHasNoErrors();
        $transaction->refresh();

        $this->assertEquals(500, $transaction->amount);
        $this->assertEquals(200, $transaction->original_amount);
        $this->assertEquals('Ternyata koin 500', $transaction->note);
        $this->assertEquals(500, BalanceService::for($this->device1));
    }

    public function test_user_can_void_transaction_with_reason(): void
    {
        $transaction = Transaction::create([
            'device_id' => $this->device1->id,
            'type' => 'deposit',
            'amount' => 1000,
            'source' => 'device',
            'occurred_at' => now(),
            'received_at' => now(),
            'status' => 'active',
        ]);

        $this->assertEquals(1000, BalanceService::for($this->device1));

        $response = $this->actingAs($this->user1)->post("/transaksi/{$transaction->id}/batal", [
            'void_reason' => 'Senggolan sensor',
        ]);

        $response->assertSessionHasNoErrors();
        $transaction->refresh();

        $this->assertEquals('voided', $transaction->status);
        $this->assertEquals('Senggolan sensor', $transaction->void_reason);
        $this->assertNotNull($transaction->voided_at);

        // Voided transaction no longer counts towards balance
        $this->assertEquals(0, BalanceService::for($this->device1));
    }

    public function test_user_cannot_edit_another_users_transaction(): void
    {
        $transaction = Transaction::create([
            'device_id' => $this->device2->id,
            'type' => 'deposit',
            'amount' => 1000,
            'source' => 'device',
            'occurred_at' => now(),
            'received_at' => now(),
            'status' => 'active',
        ]);

        // User 1 attempts to koreksi User 2's transaction -> 403
        $response = $this->actingAs($this->user1)->post("/transaksi/{$transaction->id}/koreksi", [
            'amount' => 500,
        ]);
        $response->assertStatus(403);

        // User 1 attempts to void User 2's transaction -> 403
        $response2 = $this->actingAs($this->user1)->post("/transaksi/{$transaction->id}/batal", [
            'void_reason' => 'hack',
        ]);
        $response2->assertStatus(403);
    }
}

