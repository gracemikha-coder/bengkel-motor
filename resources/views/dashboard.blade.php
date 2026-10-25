<x-app-layout>
    <x-slot name="header">Beranda</x-slot>

    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-400">Selamat datang</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-800">{{ Auth::user()->name }}</h2>
                </div>
                <div class="rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    {{ now()->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total barang</p>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-3xl font-bold text-slate-800">{{ $totalBarang }}</span>
                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Barang</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Stok menipis</p>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-3xl font-bold text-slate-800">{{ $stokMenipis }}</span>
                    <span class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-700">Cek</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Stok habis</p>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-3xl font-bold text-slate-800">{{ $stokHabis }}</span>
                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Urgent</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Transaksi hari ini</p>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-3xl font-bold text-slate-800">{{ $transaksiHariIni }}</span>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.8fr_1fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Pendapatan 7 hari terakhir</h3>
                    <span class="text-xs uppercase tracking-[0.2em] text-slate-400">chart</span>
                </div>
                <div class="relative h-64 min-w-0">
                    <canvas id="dashboardChart"></canvas>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Perlu dibeli</h3>
                    <a href="{{ route('barang-masuk') }}" class="text-sm font-medium text-blue-700">Lihat semua</a>
                </div>
                <div class="space-y-3">
                    @forelse($perluBeli as $item)
                        <a href="{{ route('barang-masuk', ['barang_id' => $item->id]) }}" class="flex items-center justify-between rounded-xl border border-slate-200 p-3 transition hover:border-blue-200 hover:bg-blue-50">
                            <div>
                                <p class="font-medium text-slate-800">{{ $item->nama }}</p>
                                <p class="text-sm text-slate-500">Stok {{ $item->stok }}</p>
                            </div>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $item->status === 'Habis' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $item->status }}</span>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Semua stok dalam kondisi aman.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">5 transaksi terakhir</h3>
                <a href="{{ route('kasir') }}" class="text-sm font-medium text-blue-700">Ke kasir</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-medium">No</th>
                            <th class="px-3 py-3 font-medium">Tanggal</th>
                            <th class="px-3 py-3 font-medium">Total</th>
                            <th class="px-3 py-3 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transaksiTerakhir as $trx)
                            <tr class="border-t border-slate-200">
                                <td class="px-3 py-3 text-slate-700">#{{ $trx->id }}</td>
                                <td class="px-3 py-3 text-slate-700">{{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('d M Y') }}</td>
                                <td class="px-3 py-3 font-semibold text-slate-800">{{ formatRupiah($trx->total) }}</td>
                                <td class="px-3 py-3">
                                    <a href="{{ route('transaksi.nota', $trx->id) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:border-blue-200 hover:text-blue-700">Cetak</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-500">Belum ada transaksi.</td>
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
                const ctx = document.getElementById('dashboardChart');
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: [{
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
                            fill: true,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
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
