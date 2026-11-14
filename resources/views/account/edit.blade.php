<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            Pengaturan Akun
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-lg shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-800 text-sm rounded-lg shadow-sm">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Info Perangkat -->
            @if ($user->device)
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">Informasi Perangkat SIMOKO</h3>
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-gray-500">Device UID:</span>
                            <p class="font-mono font-bold text-indigo-700 mt-0.5">{{ $user->device->device_uid }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Versi Firmware:</span>
                            <p class="font-mono text-gray-800 mt-0.5">{{ $user->device->firmware_version ?? '1.0.0' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Terakhir Terhubung:</span>
                            <p class="text-gray-800 mt-0.5">{{ $user->device->last_seen_at ? $user->device->last_seen_at->translatedFormat('d M Y, H:i') : 'Belum pernah' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">IP Terakhir:</span>
                            <p class="font-mono text-gray-800 mt-0.5">{{ $user->device->last_ip ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Form Edit Profil -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-base font-bold text-gray-900 mb-1">Perbarui Kredensial Akun</h3>
                <p class="text-xs text-gray-500 mb-6">Ubah username atau kata sandi yang Anda gunakan untuk masuk ke website.</p>

                <form method="POST" action="{{ route('account.update') }}" class="space-y-4">
                    @csrf

                    <!-- Username -->
                    <div>
                        <x-input-label for="username" value="Username" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $user->username)" required />
                        <p class="text-[11px] text-gray-400 mt-1">4–30 karakter (huruf kecil, angka, titik, strip, garis bawah).</p>
                    </div>

                    <!-- Password Saat Ini -->
                    <div class="pt-2">
                        <x-input-label for="current_password" value="Password Saat Ini (Wajib)" />
                        <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" required />
                    </div>

                    <!-- Password Baru -->
                    <div>
                        <x-input-label for="password" value="Password Baru (Kosongkan jika tidak ingin ganti)" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                    </div>

                    <!-- Konfirmasi Password Baru -->
                    <div>
                        <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                    </div>

                    <div class="pt-4 flex justify-end">
                        <x-primary-button>
                            Simpan Perubahan
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
