<x-app-layout>
    <x-slot name="header">Barang masuk</x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,0.9fr)]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Catat barang masuk</h2>
                </div>

                <form method="POST" action="{{ route('barang-masuk.store') }}" x-data="{ jumlah: {{ old('jumlah', 1) }}, harga: {{ old('harga_beli', 0) }}, total() { return Number(this.jumlah || 0) * Number(this.harga || 0); } }">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Barang</label>
                            <select name="barang_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                <option value="">Pilih barang</option>
                                @foreach($barang as $item)
                                    <option value="{{ $item->id }}" @selected(old('barang_id', $selectedBarangId) == $item->id)>{{ $item->nama }} (stok {{ $item->stok }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Jumlah</label>
                            <input type="number" name="jumlah" x-model.number="jumlah" min="1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Harga beli per unit</label>
                            <input type="number" name="harga_beli" x-model.number="harga" min="1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Toko</label>
                            <input type="text" name="toko" value="{{ old('toko') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" placeholder="Opsional">
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl bg-blue-50 p-4">
                        <div class="flex items-center justify-between text-sm text-slate-600">
                            <span>Total belanja</span>
                            <span class="text-2xl font-bold text-blue-800" x-text="formatRupiah(total())"></span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="rounded-xl bg-blue-800 px-5 py-3 text-sm font-medium text-white">Simpan barang masuk</button>
                    </div>
                </form>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">Riwayat 20 belanja terakhir</h3>
                    </div>
                    <div class="space-y-3">
                        @forelse($latest as $item)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-medium text-slate-800">{{ $item->nama_barang }}</p>
                                    <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M') }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-sm text-slate-600">
                                    <span>{{ $item->jumlah }} unit</span>
                                    <span>{{ formatRupiah($item->harga_beli) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Belum ada riwayat barang masuk.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">Perlu dibeli</h3>
                    </div>
                    <div class="space-y-3">
                        @forelse($perluBeli as $item)
                            <a href="{{ route('barang-masuk', ['barang_id' => $item->id]) }}" class="block rounded-xl border border-slate-200 p-3 transition hover:border-blue-200 hover:bg-blue-50">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-medium text-slate-800">{{ $item->nama }}</p>
                                    <span class="rounded-full px-2 py-1 text-[11px] font-semibold {{ $item->status === 'Habis' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $item->status }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">Stok saat ini: {{ $item->stok }} unit</p>
                            </a>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Tidak ada barang yang perlu dibeli saat ini.</div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
