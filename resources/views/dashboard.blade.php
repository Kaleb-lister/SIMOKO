<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">
                    Monitoring Celengan
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Alat: <span class="font-mono font-semibold text-indigo-600">{{ $device->device_uid }}</span></p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $isOnline ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                    <span class="w-2 h-2 me-1.5 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                    {{ $isOnline ? 'Online' : 'Terakhir: ' . $lastSeenText }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Session Status -->
            @if (session('status'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Banner Review Diperlukan -->
            @if ($needsReviewCount > 0)
                <div class="p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-900 rounded-lg shadow-sm flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <p class="font-bold text-sm">Ada {{ $needsReviewCount }} transaksi yang perlu ditinjau!</p>
                            <p class="text-xs text-amber-700">Terdapat perubahan berat koin tak dikenal yang belum diverifikasi.</p>
                        </div>
                    </div>
                    <a href="{{ route('transaksi.index', ['review' => 1]) }}" class="inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-md shadow-sm">
                        Tinjau Sekarang &rarr;
                    </a>
                </div>
            @endif

            <!-- Warning Selisih Saldo Alat vs Server -->
            @if ($balanceMismatch)
                <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 rounded-lg text-xs space-y-1">
                    <p class="font-semibold">Perhatian: Perbedaan Saldo Terdeteksi</p>
                    <p>Saldo menurut laporan alat adalah <strong>Rp {{ number_format($device->last_reported_balance, 0, ',', '.') }}</strong>, sedangkan saldo buku besar resmi server adalah <strong>Rp {{ number_format($balance, 0, ',', '.') }}</strong>.</p>
                </div>
            @endif

            <!-- 4 Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Card 1: Saldo -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Saldo</span>
                        <div class="w-9 h-9 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center font-bold">
                            Rp
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-extrabold text-gray-900">
                            Rp {{ number_format($balance, 0, ',', '.') }}
                        </span>
                        <p class="text-xs text-gray-400 mt-1">Saldo aktif celengan saat ini</p>
                    </div>
                </div>

                <!-- Card 2: Target -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Target Tabungan</span>
                        <div class="w-9 h-9 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center text-lg">
                            🎯
                        </div>
                    </div>
                    <div class="mt-3">
                        @if ($goal)
                            <div class="flex items-baseline space-x-2">
                                <span class="text-2xl font-extrabold text-gray-900">
                                    Rp {{ number_format($goal->target_amount, 0, ',', '.') }}
                                </span>
                                @if ($goal->status === 'reached')
                                    <span class="px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-800 rounded-full">Tercapai</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-semibold bg-indigo-100 text-indigo-800 rounded-full">Aktif</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Ditetapkan dari menu alat ESP32</p>
                        @else
                            <span class="text-lg font-bold text-gray-400">Belum ada target</span>
                            <p class="text-xs text-indigo-600 mt-1">Atur target di alat celengan</p>
                        @endif
                    </div>
                </div>

                <!-- Card 3: Estimasi (ETA) -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Estimasi Tercapai</span>
                        <div class="w-9 h-9 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-lg">
                            ⏱️
                        </div>
                    </div>
                    <div class="mt-3">
                        @if ($eta['status'] === 'reached')
                            <span class="text-xl font-bold text-emerald-600">Target Tercapai! 🎉</span>
                            <p class="text-xs text-gray-400 mt-1">Selamat atas capaian targetmu</p>
                        @elseif ($eta['status'] === 'estimated')
                            <span class="text-xl font-extrabold text-gray-900">± {{ $eta['days'] }} Hari Lagi</span>
                            <p class="text-xs text-gray-500 mt-1">Perkiraan: {{ $eta['target_date']->translatedFormat('d M Y') }}</p>
                        @else
                            <span class="text-base font-medium text-gray-500">Belum cukup data</span>
                            <p class="text-xs text-gray-400 mt-1">Butuh riwayat setoran ≥ 2 hari</p>
                        @endif
                    </div>
                </div>

                <!-- Card 4: Setoran Hari Ini -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Setoran Hari Ini</span>
                        <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center text-lg">
                            📥
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-extrabold text-emerald-600">
                            +Rp {{ number_format($depositsToday, 0, ',', '.') }}
                        </span>
                        <p class="text-xs text-gray-400 mt-1">Total koin dimasukkan hari ini</p>
                    </div>
                </div>

            </div>

            <!-- Progress Bar Card -->
            <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-gray-700">Progres Menuju Target</span>
                    <span class="text-sm font-bold text-indigo-600">{{ $progressPercent }}%</span>
                </div>
                
                <div class="w-full bg-gray-100 rounded-full h-3.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-emerald-500 h-3.5 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                </div>

                <div class="flex justify-between items-center text-xs text-gray-500 mt-2">
                    <span>Terkumpul: <strong>Rp {{ number_format($balance, 0, ',', '.') }}</strong></span>
                    <span>Target: <strong>Rp {{ number_format($targetAmount, 0, ',', '.') }}</strong></span>
                </div>
            </div>

            <!-- Chart.js: Grafik 14 Hari Terakhir -->
            <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-gray-800 text-base">Aktivitas Tabungan 14 Hari Terakhir</h3>
                        <p class="text-xs text-gray-500">Batang menunjukkan setoran harian, garis menunjukkan saldo kumulatif</p>
                    </div>
                    <div class="mt-2 sm:mt-0 flex items-center space-x-3 text-xs">
                        <span class="inline-flex items-center"><span class="w-3 h-3 bg-indigo-500 rounded-sm me-1.5"></span> Setoran Harian (Rp)</span>
                        <span class="inline-flex items-center"><span class="w-3 h-1 bg-emerald-500 me-1.5"></span> Akumulasi Saldo (Rp)</span>
                    </div>
                </div>

                <div class="relative w-full h-72">
                    <canvas id="savingsChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <!-- Chart.js Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('savingsChart').getContext('2d');
            const labels = @json($chartLabels);
            const depositsData = @json($chartDeposits);
            const balanceData = @json($chartCumulativeBalance);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Akumulasi Saldo',
                            data: balanceData,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2.5,
                            tension: 0.3,
                            pointRadius: 3,
                            yAxisID: 'y1',
                        },
                        {
                            type: 'bar',
                            label: 'Setoran Harian',
                            data: depositsData,
                            backgroundColor: '#6366f1',
                            borderRadius: 4,
                            yAxisID: 'y',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Setoran (Rp)',
                                font: { size: 11 }
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            title: {
                                display: true,
                                text: 'Akumulasi (Rp)',
                                font: { size: 11 }
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>
