<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Show mandatory credentials change form after first login.
     */
    public function showChangeCredentials(): View
    {
        return view('account.change-credentials', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Process mandatory credentials change.
     */
    public function updateCredentials(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
                function ($attribute, $value, $fail) use ($user) {
                    if (Str::lower($value) === Str::lower($user->username)) {
                        $fail('Username baru harus berbeda dari username bawaan.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8),
                function ($attribute, $value, $fail) use ($user) {
                    if (Hash::check($value, $user->password)) {
                        $fail('Password baru harus berbeda dari password lama.');
                    }
                },
            ],
        ], [
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik (.), strip (-), dan garis bawah (_).',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user->update([
            'username' => Str::lower($request->input('username')),
            'password' => Hash::make($request->input('password')),
            'must_change_credentials' => false,
        ]);

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Kredensial berhasil diperbarui. Selamat datang di SIMOKO!');
    }

    /**
     * Show account profile / credentials settings page.
     */
    public function edit(): View
    {
        return view('account.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update account credentials from profile page.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8),
            ],
        ]);

        $updateData = [
            'username' => Str::lower($request->input('username')),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->input('password'));
        }

        $user->update($updateData);

        return redirect()->route('account.edit')->with('status', 'Profil akun berhasil diperbarui.');
    }
}
