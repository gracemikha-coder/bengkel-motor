<x-app-layout>
    <x-slot name="header">Laporan</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex gap-2">
                <a href="{{ route('laporan', ['mode' => 'weekly']) }}" class="rounded-xl px-4 py-2 text-sm font-medium {{ $mode === 'weekly' ? 'bg-blue-800 text-white' : 'border border-slate-200 bg-white text-slate-700' }}">Mingguan</a>
                <a href="{{ route('laporan', ['mode' => 'monthly']) }}" class="rounded-xl px-4 py-2 text-sm font-medium {{ $mode === 'monthly' ? 'bg-blue-800 text-white' : 'border border-slate-200 bg-white text-slate-700' }}">Bulanan</a>
            </div>
            <div>
                <a href="{{ route('laporan.export', ['mode' => $mode]) }}" class="inline-flex items-center justify-center rounded-xl bg-slate-800 px-4 py-2 text-sm font-medium text-white">Unduh Laporan Excel</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Uang masuk</p>
                <p class="mt-3 text-3xl font-bold text-slate-800">{{ formatRupiah($summary->uang_masuk ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Modal barang</p>
                <p class="mt-3 text-3xl font-bold text-slate-800">{{ formatRupiah($summary->modal_barang ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Untung bersih</p>
                <p class="mt-3 text-3xl font-bold text-emerald-600">{{ formatRupiah($summary->untung_bersih ?? 0) }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,0.9fr)]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Grafik pendapatan</h3>
                </div>
                <div class="relative h-72 min-w-0">
                    <canvas id="laporanChart"></canvas>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-slate-800">Pendapatan barang dan jasa</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between rounded-xl bg-blue-50 p-3">
                            <span class="text-sm text-slate-600">Barang</span>
                            <span class="font-semibold text-slate-800">{{ formatRupiah($pendapatanBarang) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-slate-100 p-3">
                            <span class="text-sm text-slate-600">Jasa</span>
                            <span class="font-semibold text-slate-800">{{ formatRupiah($pendapatanJasa) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-slate-800">5 barang terlaris</h3>
                    <div class="space-y-3">
                        @forelse($barangTerlaris as $item)
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 p-3">
                                <span class="font-medium text-slate-700">{{ $item->nama }}</span>
                                <span class="text-sm text-slate-500">{{ $item->total }} unit</span>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Belum ada data penjualan.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Rincian per periode</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-medium">Periode</th>
                            <th class="px-3 py-3 font-medium">Jumlah transaksi</th>
                            <th class="px-3 py-3 font-medium">Uang masuk</th>
                            <th class="px-3 py-3 font-medium">Modal barang</th>
                            <th class="px-3 py-3 font-medium">Untung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyDetail as $row)
                            <tr class="border-t border-slate-200">
                                <td class="px-3 py-3 text-slate-700">{{ $row['periode'] }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ $row['jumlah_transaksi'] }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ formatRupiah($row['uang_masuk']) }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ formatRupiah($row['modal_barang']) }}</td>
                                <td class="px-3 py-3 font-semibold text-emerald-600">{{ formatRupiah($row['untung']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-slate-500">Belum ada data laporan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('laporanChart');
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            label: 'Pendapatan',
                            data: @json($series),
                            borderColor: '#0f766e',
                            backgroundColor(context) {
                                const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
                                gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
                                return gradient;
                            },
                            pointBackgroundColor: '#0f766e',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 8,
                            pointHitRadius: 12,
                            pointHoverBackgroundColor: '#2563eb',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: (value) => 'Rp' + Number(value).toLocaleString('id-ID') }
                            }
                        }
                    }
                });

            });
        </script>
    @endpush
</x-app-layout>
