<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard SIMOKO
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center space-y-4">
                <span class="text-4xl">⚠️</span>
                <h3 class="text-lg font-bold text-gray-800">Alat Belum Terhubung</h3>
                <p class="text-sm text-gray-600">
                    Akun Anda belum memiliki perangkat SIMOKO yang terhubung. Silakan hubungi administrator atau jalankan perintah:
                </p>
                <div class="p-3 bg-gray-100 rounded text-xs font-mono text-gray-800">
                    php artisan simoko:provision SIMOKO-0001
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
