<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('SIMOKO');
        $response->assertSee('Username Alat');
    }

    public function test_user_can_login_using_username(): void
    {
        $user = User::create([
            'username' => 'simoko-0001',
            'password' => Hash::make('password123'),
            'must_change_credentials' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'simoko-0001',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_user_with_must_change_credentials_is_redirected(): void
    {
        $user = User::create([
            'username' => 'simoko-0001',
            'password' => Hash::make('initialpass'),
            'must_change_credentials' => true,
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/akun/ganti-kredensial');
    }

    public function test_user_can_change_credentials(): void
    {
        $user = User::create([
            'username' => 'simoko-0001',
            'password' => Hash::make('initialpass'),
            'must_change_credentials' => true,
        ]);

        $response = $this->actingAs($user)->post('/akun/ganti-kredensial', [
            'username' => 'tabungan_baru',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/');
        $user->refresh();

        $this->assertEquals('tabungan_baru', $user->username);
        $this->assertFalse($user->must_change_credentials);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'username' => 'simoko-0001',
            'password' => Hash::make('password123'),
            'must_change_credentials' => false,
        ]);

        $response = $this->actingAs($user)->post('/logout');
        $this->assertGuest();
        $response->assertRedirect('/');
    }
}

