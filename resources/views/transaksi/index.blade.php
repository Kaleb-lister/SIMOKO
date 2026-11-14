<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">
                    Riwayat Transaksi
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Alat: <span class="font-mono font-semibold text-indigo-600">{{ $device->device_uid }}</span> &bull; Total: {{ $totalCount }} transaksi</p>
            </div>
            
            <div class="flex items-center space-x-2">
                <!-- Filter Hari -->
                <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-xs shadow-sm">
                    <a href="{{ route('transaksi.index', ['days' => 7]) }}" class="px-3 py-1 rounded-md font-medium {{ $currentDays == 7 && ! $onlyReview ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900' }}">7 Hari</a>
                    <a href="{{ route('transaksi.index', ['days' => 30]) }}" class="px-3 py-1 rounded-md font-medium {{ $currentDays == 30 && ! $onlyReview ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900' }}">30 Hari</a>
                    <a href="{{ route('transaksi.index', ['days' => 90]) }}" class="px-3 py-1 rounded-md font-medium {{ $currentDays == 90 && ! $onlyReview ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900' }}">90 Hari</a>
                    @if ($onlyReview)
                        <span class="px-3 py-1 rounded-md font-semibold bg-amber-500 text-white">Perlu Ditinjau</span>
                    @endif
                </div>

                <!-- Tambah Manual Button -->
                <button type="button" @click="$dispatch('open-modal', 'tambah-manual-modal')" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">
                    + Tambah Manual
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Session Status -->
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

            @if ($grouped->isEmpty())
                <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-gray-100">
                    <span class="text-4xl">📭</span>
                    <h3 class="mt-3 text-base font-bold text-gray-800">Belum ada transaksi</h3>
                    <p class="text-xs text-gray-500 mt-1">Tidak ada transaksi ditemukan pada rentang waktu ini.</p>
                </div>
            @else
                <!-- Daftar Transaksi Grouped per Tanggal -->
                <div class="space-y-6">
                    @foreach ($grouped as $date => $transactions)
                        @php
                            $dateCarbon = \Carbon\Carbon::parse($date);
                            $dailySubtotal = $transactions->where('status', 'active')->sum('amount');
                        @endphp

                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <!-- Header Tanggal -->
                            <div class="bg-gray-50/75 px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-sm text-gray-800">{{ $dateCarbon->translatedFormat('l, d F Y') }}</span>
                                    <span class="text-xs text-gray-400">({{ $transactions->count() }} transaksi)</span>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Subtotal hari ini: </span>
                                    <span class="text-xs font-bold {{ $dailySubtotal >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $dailySubtotal >= 0 ? '+' : '' }}Rp {{ number_format($dailySubtotal, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>

                            <!-- List Baris Transaksi -->
                            <div class="divide-y divide-gray-100">
                                @foreach ($transactions as $t)
                                    <div class="p-4 hover:bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $t->status === 'voided' ? 'opacity-50 bg-gray-50' : '' }}">
                                        
                                        <!-- Info Kiri: Waktu & Ikon & Detail -->
                                        <div class="flex items-center space-x-3.5">
                                            <!-- Ikon Jenis -->
                                            @if ($t->type === 'deposit')
                                                <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                                                    ↑
                                                </div>
                                            @elseif ($t->type === 'withdrawal')
                                                <div class="w-9 h-9 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-sm">
                                                    ↓
                                                </div>
                                            @elseif ($t->type === 'unknown_change')
                                                <div class="w-9 h-9 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                                                    ?
                                                </div>
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                                                    ✎
                                                </div>
                                            @endif

                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-semibold text-gray-900">
                                                        @if ($t->type === 'deposit') Setoran Koin
                                                        @elseif ($t->type === 'withdrawal') Pengambilan Koin
                                                        @elseif ($t->type === 'unknown_change') Perubahan Tak Dikenal
                                                        @else Penyesuaian Manual
                                                        @endif
                                                    </span>

                                                    @if ($t->time_estimated)
                                                        <span class="text-xs text-amber-600 font-mono" title="Waktu perkiraan (NTP alat offline saat kejadian)">~</span>
                                                    @endif

                                                    <span class="text-xs text-gray-400 font-mono">
                                                        {{ \Carbon\Carbon::parse($t->occurred_at)->format('H:i') }}
                                                    </span>

                                                    <!-- Badge Sumber -->
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-mono {{ $t->source === 'device' ? 'bg-gray-100 text-gray-600' : 'bg-blue-50 text-blue-600' }}">
                                                        {{ $t->source === 'device' ? 'alat' : 'web' }}
                                                    </span>

                                                    <!-- Badge Perlu Ditinjau -->
                                                    @if ($t->needs_review)
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold bg-amber-100 text-amber-800">
                                                            Perlu Ditinjau
                                                        </span>
                                                    @endif

                                                    <!-- Badge Dibatalkan -->
                                                    @if ($t->status === 'voided')
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold bg-rose-100 text-rose-800">
                                                            Dibatalkan
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="text-xs text-gray-500 mt-0.5 flex flex-wrap gap-x-2">
                                                    @if ($t->weight_delta_g !== null)
                                                        <span>Berat: {{ number_format($t->weight_delta_g, 2) }} g</span>
                                                    @endif
                                                    @if ($t->original_amount !== null)
                                                        <span class="text-gray-400">(awal: Rp {{ number_format($t->original_amount, 0, ',', '.') }})</span>
                                                    @endif
                                                    @if ($t->note)
                                                        <span class="italic text-gray-600">"{{ $t->note }}"</span>
                                                    @endif
                                                    @if ($t->status === 'voided' && $t->void_reason)
                                                        <span class="text-rose-600 font-medium">Alasan batal: {{ $t->void_reason }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Nominal & Aksi Kanan -->
                                        <div class="flex items-center justify-between sm:justify-end space-x-4 pl-12 sm:pl-0">
                                            <div class="text-right">
                                                <span class="text-base font-extrabold {{ $t->status === 'voided' ? 'line-through text-gray-400' : ($t->amount > 0 ? 'text-emerald-600' : ($t->amount < 0 ? 'text-rose-600' : 'text-gray-600')) }}">
                                                    {{ $t->amount > 0 ? '+' : '' }}Rp {{ number_format($t->amount, 0, ',', '.') }}
                                                </span>
                                            </div>

                                            @if ($t->status !== 'voided')
                                                <div class="flex items-center space-x-1.5" x-data="{ openKoreksi: false, openBatal: false }">
                                                    <!-- Tombol Koreksi -->
                                                    <button @click="openKoreksi = true" class="px-2 py-1 text-xs text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded font-medium">
                                                        Koreksi
                                                    </button>

                                                    <!-- Tombol Batal -->
                                                    <button @click="openBatal = true" class="px-2 py-1 text-xs text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded font-medium">
                                                        Batalkan
                                                    </button>

                                                    <!-- Modal Koreksi -->
                                                    <div x-show="openKoreksi" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 flex items-center justify-center p-4">
                                                        <div @click.away="openKoreksi = false" class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5 text-left">
                                                            <h4 class="font-bold text-base text-gray-800 mb-2">Koreksi Nominal Transaksi</h4>
                                                            <p class="text-xs text-gray-500 mb-4">Gunakan ini jika alat keliru mengenali pecahan koin atau berat.</p>

                                                            <form method="POST" action="{{ route('transaksi.koreksi', $t->id) }}">
                                                                @csrf
                                                                <div class="space-y-3">
                                                                    <div>
                                                                        <label class="block text-xs font-semibold text-gray-700">Nominal Baru (Rp)</label>
                                                                        <input type="number" name="amount" value="{{ $t->amount }}" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                        <span class="text-[10px] text-gray-400">Gunakan tanda minus (-) jika pengambilan</span>
                                                                    </div>
                                                                    <div>
                                                                        <label class="block text-xs font-semibold text-gray-700">Catatan (opsional)</label>
                                                                        <input type="text" name="note" value="{{ $t->note }}" placeholder="misal: salah koin 500" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                    </div>
                                                                </div>

                                                                <div class="mt-5 flex justify-end space-x-2">
                                                                    <button type="button" @click="openKoreksi = false" class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800">Tutup</button>
                                                                    <button type="submit" class="px-3 py-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow-sm">Simpan Koreksi</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <!-- Modal Batal -->
                                                    <div x-show="openBatal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 flex items-center justify-center p-4">
                                                        <div @click.away="openBatal = false" class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5 text-left">
                                                            <h4 class="font-bold text-base text-rose-600 mb-2">Batalkan Transaksi?</h4>
                                                            <p class="text-xs text-gray-600 mb-4">Transaksi ini akan dinonaktifkan dari saldo dan tidak dapat dibatalkan balik.</p>

                                                            <form method="POST" action="{{ route('transaksi.batal', $t->id) }}">
                                                                @csrf
                                                                <div>
                                                                    <label class="block text-xs font-semibold text-gray-700">Alasan Pembatalan</label>
                                                                    <input type="text" name="void_reason" required placeholder="misal: celengan tersenggol tidak sengaja" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500">
                                                                </div>

                                                                <div class="mt-5 flex justify-end space-x-2">
                                                                    <button type="button" @click="openBatal = false" class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800">Tutup</button>
                                                                    <button type="submit" class="px-3 py-1.5 text-xs bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-md shadow-sm">Ya, Batalkan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                </div>
                                            @endif

                                        </div>

                                    </div>
                                @endforeach
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

    <!-- Modal Tambah Transaksi Manual -->
    <x-modal name="tambah-manual-modal" :show="false">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Tambah Transaksi Manual</h3>
            <p class="text-xs text-gray-500 mb-4">Catat setoran atau pengeluaran yang tidak terdeteksi otomatis oleh alat sensor.</p>

            <form method="POST" action="{{ route('transaksi.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <x-input-label for="amount" value="Nominal Rupiah (Rp)" />
                        <x-text-input id="amount" name="amount" type="number" required class="mt-1 block w-full" placeholder="misal: 1000 atau -500" />
                        <p class="text-[11px] text-gray-500 mt-1">Gunakan angka positif untuk setoran (+), angka negatif untuk pengeluaran (-).</p>
                    </div>

                    <div>
                        <x-input-label for="occurred_at" value="Tanggal & Jam (opsional)" />
                        <x-text-input id="occurred_at" name="occurred_at" type="datetime-local" class="mt-1 block w-full" />
                    </div>

                    <div>
                        <x-input-label for="note" value="Catatan (opsional)" />
                        <x-text-input id="note" name="note" type="text" placeholder="misal: koin kembalian belanja" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        Batal
                    </x-secondary-button>
                    <x-primary-button>
                        Simpan Transaksi
                    </x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>
