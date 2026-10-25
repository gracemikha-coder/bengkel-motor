<x-app-layout>
    <x-slot name="header">Kasir</x-slot>

    <div class="space-y-5" x-data="{
        tab: 'sparepart', category: '', categories: @js($kategoriOptions), search: @js($query), cart: @js($cart),
        products: @js($barang->map(fn ($item) => ['id' => (int) $item->id, 'nama' => $item->nama, 'harga' => (int) $item->harga_jual, 'stok' => (int) $item->stok, 'gambar' => $item->gambar_url, 'kategori' => $item->kategori_nama])->values()),
        services: @js($jasa->map(fn ($item) => ['id' => (int) $item->id, 'nama' => $item->nama, 'harga' => (int) $item->tarif])->values()),
        quickServices: @js($quickServices), packages: @js($paketServis), paymentMethod: @js(old('metode_pembayaran', 'tunai')),
        showQrisModal: false, showCustomerPhone: false,
        customer: @js(old('pelanggan', '')), plate: @js(old('nomor_plat', '')),
        whatsapp: @js(old('whatsapp', '')), discountPercent: @js(old('diskon_persen', 0)), amountPaid: @js(old('uang_dibayar', 0)),
        visibleProducts() {
            const term = this.search.trim().toLocaleLowerCase('id-ID');
            return this.products.filter((item) => (this.category === '' || item.kategori === this.category) && item.nama.toLocaleLowerCase('id-ID').includes(term));
        },
        visibleServices() {
            const term = this.search.trim().toLocaleLowerCase('id-ID');
            return this.services.filter((item) => item.nama.toLocaleLowerCase('id-ID').includes(term));
        },
        addItem(type, item) {
            const existing = this.cart.find((line) => line.tipe === type && line.id === item.id);
            if (existing) {
                if (type === 'barang' && existing.jumlah >= existing.stok) return;
                existing.jumlah++;
                return;
            }
            this.cart.push({ tipe: type, id: item.id, nama: item.nama, harga: Number(item.harga), gambar: item.gambar || '{{ asset('images/sparepart-placeholder.svg') }}', stok: Number(item.stok || 0), jumlah: 1 });
        },
        addPackage(item) { item.layanan.forEach((service) => this.addItem('jasa', service)); },
        changeQty(index, delta) {
            const item = this.cart[index];
            if (!item) return;
            const quantity = item.jumlah + delta;
            if (quantity < 1) return this.removeItem(index);
            if (item.tipe === 'barang' && quantity > item.stok) return;
            item.jumlah = quantity;
        },
        removeItem(index) { this.cart.splice(index, 1); },
        clearCart() { this.cart = []; },
        subtotal() { return this.cart.reduce((sum, item) => sum + Number(item.harga) * Number(item.jumlah), 0); },
        discountAmount() { return Math.round(this.subtotal() * Math.min(100, Math.max(0, Number(this.discountPercent) || 0)) / 100); },
        total() { return Math.max(0, this.subtotal() - this.discountAmount()); },
        cashDifference() { return Number(this.amountPaid || 0) - this.total(); },
        itemCount() { return this.cart.reduce((sum, item) => sum + Number(item.jumlah), 0); },
        selectPayment(method) { this.paymentMethod = method; this.showQrisModal = method === 'qris'; },
        money(value) { return 'Rp' + new Intl.NumberFormat('id-ID').format(Number(value) || 0); }
    }">
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('kasir.store') }}">
            @csrf
            <input type="hidden" name="items" :value="JSON.stringify(cart)">
            <input type="hidden" name="diskon_persen" :value="discountPercent">
            <input type="hidden" name="whatsapp" :value="whatsapp">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.55fr)_minmax(340px,0.95fr)]">
                <section class="min-w-0 space-y-5">
                    <header class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Point of Sale</p>
                            <h2 class="mt-1 text-2xl font-bold text-slate-900">Kasir</h2>
                            <p class="mt-1 text-sm text-slate-500">Transaksi penjualan sparepart dan jasa servis</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-right shadow-sm">
                            <p id="pos-clock" class="text-lg font-semibold tabular-nums text-slate-800">--:--:--</p>
                            <p id="pos-date" class="text-xs text-slate-500">Memuat tanggal...</p>
                        </div>
                    </header>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="pelanggan" class="mb-1 block text-sm font-medium text-slate-700">Pelanggan (opsional)</label>
                            <div class="flex gap-2">
                                <input id="pelanggan" name="pelanggan" x-model="customer" type="text" placeholder="Nama pelanggan" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                <button type="button" @click="showCustomerPhone = !showCustomerPhone" :aria-expanded="showCustomerPhone" title="Tambah nomor pelanggan" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-blue-700 hover:bg-blue-50">@include('components.icon.plus')</button>
                            </div>
                            <div x-show="showCustomerPhone" x-transition class="mt-2">
                                <label for="whatsapp-number" class="mb-1 block text-xs font-medium text-slate-500">Nomor WhatsApp (opsional)</label>
                                <input id="whatsapp-number" x-model="whatsapp" type="tel" inputmode="tel" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                            </div>
                            <x-input-error :messages="$errors->get('pelanggan')" class="mt-1" />
                            <x-input-error :messages="$errors->get('whatsapp')" class="mt-1" />
                        </div>
                        <div>
                            <label for="nomor_plat" class="mb-1 block text-sm font-medium text-slate-700">No. Plat / Kendaraan</label>
                            <input id="nomor_plat" name="nomor_plat" x-model="plate" type="text" maxlength="20" placeholder="D 1234 ABC" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 uppercase">
                            <x-input-error :messages="$errors->get('nomor_plat')" class="mt-1" />
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-4 pt-4 sm:px-5">
                            <div class="flex gap-2 overflow-x-auto">
                                <button type="button" @click="tab = 'sparepart'" :class="tab === 'sparepart' ? 'border-blue-700 text-blue-800' : 'border-transparent text-slate-500 hover:text-slate-800'" class="shrink-0 border-b-2 px-3 pb-3 text-sm font-semibold">Sparepart</button>
                                <button type="button" @click="tab = 'jasa'" :class="tab === 'jasa' ? 'border-blue-700 text-blue-800' : 'border-transparent text-slate-500 hover:text-slate-800'" class="shrink-0 border-b-2 px-3 pb-3 text-sm font-semibold">Jasa Servis</button>
                                <button type="button" @click="tab = 'paket'" :class="tab === 'paket' ? 'border-blue-700 text-blue-800' : 'border-transparent text-slate-500 hover:text-slate-800'" class="shrink-0 border-b-2 px-3 pb-3 text-sm font-semibold">Paket Servis</button>
                            </div>
                        </div>

                        <div class="space-y-4 p-4 sm:p-5">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">@include('components.icon.search')</span>
                                <input x-model.debounce.150ms="search" type="search" placeholder="Cari sparepart atau jasa servis..." class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div x-show="tab === 'sparepart'" class="space-y-4">
                                <div class="flex space-x-2 overflow-x-auto whitespace-nowrap pb-2 no-scrollbar">
                                    <button type="button" @click="category = ''" :class="category === '' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition-colors">Semua</button>
                                    <template x-for="categoryOption in categories" :key="categoryOption">
                                        <button type="button" @click="category = categoryOption" :class="category === categoryOption ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition-colors" x-text="categoryOption"></button>
                                    </template>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <template x-for="item in visibleProducts()" :key="item.id">
                                        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-blue-300 hover:shadow-md">
                                            <div class="relative">
                                                <img :src="item.gambar" :alt="item.nama" class="h-28 w-full bg-slate-100 object-cover sm:h-32">
                                                <span class="absolute right-2 top-2 rounded-full bg-emerald-700 px-2 py-1 text-[10px] font-semibold text-white" x-text="'Stok ' + item.stok"></span>
                                            </div>
                                            <div class="space-y-2 p-3">
                                                <h3 class="line-clamp-2 min-h-10 text-sm font-medium text-slate-800" x-text="item.nama"></h3>
                                                <p class="text-sm font-bold text-slate-900" x-text="money(item.harga)"></p>
                                                <button type="button" @click="addItem('barang', item)" class="w-full rounded-lg bg-blue-800 px-2 py-2 text-xs font-semibold text-white hover:bg-blue-700">+ Tambah</button>
                                            </div>
                                        </article>
                                    </template>
                                    <div x-show="visibleProducts().length === 0" class="col-span-full rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Sparepart tidak ditemukan.</div>
                                </div>
                                <div class="space-y-3 border-t border-slate-200 pt-4">
                                    <div class="flex items-center justify-between"><h3 class="text-sm font-semibold text-slate-800">Jasa Servis (Cepat)</h3><button type="button" @click="tab = 'jasa'" class="text-xs font-medium text-blue-700">Lihat semua</button></div>
                                    <div class="grid grid-cols-2 gap-2 xl:grid-cols-4">
                                        <template x-for="service in quickServices" :key="service.id">
                                            <button type="button" @click="addItem('jasa', service)" class="flex min-w-0 items-center gap-2 rounded-xl border border-slate-200 p-2.5 text-left hover:border-blue-300 hover:bg-blue-50">
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-cyan-50 text-cyan-800"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.5 6.5a5 5 0 0 0-6.6 6.6L3 18l3 3 4.9-4.9a5 5 0 0 0 6.6-6.6L14 13l-3-3 3.5-3.5Z"/></svg></span>
                                                <span class="min-w-0"><span class="block truncate text-xs font-semibold text-slate-800" x-text="service.label"></span><span class="block text-[11px] text-slate-500" x-text="money(service.harga)"></span></span>
                                            </button>
                                        </template>
                                        <p x-show="quickServices.length === 0" class="col-span-full text-xs text-slate-500">Belum ada jasa servis cepat di katalog.</p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="tab === 'jasa'" class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                <template x-for="service in visibleServices()" :key="service.id">
                                    <button type="button" @click="addItem('jasa', service)" class="rounded-xl border border-slate-200 p-3 text-left hover:border-blue-300 hover:bg-blue-50"><span class="block text-sm font-semibold text-slate-800" x-text="service.nama"></span><span class="mt-2 block text-sm font-bold text-blue-800" x-text="money(service.harga)"></span><span class="mt-3 block rounded-lg bg-blue-800 px-2 py-2 text-center text-xs font-semibold text-white">+ Tambah jasa</span></button>
                                </template>
                                <p x-show="visibleServices().length === 0" class="col-span-full rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Jasa servis tidak ditemukan.</p>
                            </div>

                            <div x-show="tab === 'paket'" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <template x-for="packageItem in packages" :key="packageItem.nama">
                                    <article class="rounded-xl border border-slate-200 p-4"><h3 class="font-semibold text-slate-800" x-text="packageItem.nama"></h3><p class="mt-2 text-xs text-slate-500" x-text="packageItem.layanan.map((service) => service.nama).join(' + ')"></p><p class="mt-3 text-lg font-bold text-blue-800" x-text="money(packageItem.harga)"></p><button type="button" @click="addPackage(packageItem)" class="mt-3 w-full rounded-lg bg-blue-800 px-3 py-2 text-sm font-semibold text-white">+ Tambah paket</button></article>
                                </template>
                                <p x-show="packages.length === 0" class="col-span-full rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Paket servis belum tersedia.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white shadow-sm lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)]">
                    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4 sm:px-5"><div><h2 class="font-semibold text-slate-900">Keranjang Transaksi</h2><p class="mt-0.5 text-xs text-slate-500" x-text="itemCount() + ' item'"></p></div><button type="button" @click="clearCart()" :disabled="cart.length === 0" class="text-xs font-semibold text-red-600 hover:text-red-700 disabled:opacity-40">Hapus Semua</button></div>
                    <div class="min-h-36 flex-1 space-y-3 overflow-y-auto p-4 sm:px-5" x-show="cart.length > 0">
                        <template x-for="(item, index) in cart" :key="item.tipe + '-' + item.id">
                            <article class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-start gap-3"><img :src="item.gambar || '{{ asset('images/sparepart-placeholder.svg') }}'" :alt="item.nama" class="h-12 w-12 shrink-0 rounded-lg bg-slate-100 object-cover"><div class="min-w-0 flex-1"><p class="line-clamp-2 text-sm font-medium text-slate-800" x-text="item.nama"></p><p class="mt-0.5 text-xs text-slate-500" x-text="money(item.harga) + ' / item'"></p></div><button type="button" @click="removeItem(index)" aria-label="Hapus item" class="rounded-md p-1 text-slate-400 hover:bg-red-50 hover:text-red-600"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16m-10 4v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></button></div>
                                <div class="mt-3 flex items-center justify-between gap-3"><div class="flex items-center rounded-lg border border-slate-200"><button type="button" @click="changeQty(index, -1)" aria-label="Kurangi jumlah" class="grid h-8 w-8 place-items-center text-slate-600">−</button><span class="min-w-8 text-center text-sm font-semibold tabular-nums" x-text="item.jumlah"></span><button type="button" @click="changeQty(index, 1)" :disabled="item.tipe === 'barang' && item.jumlah >= item.stok" aria-label="Tambah jumlah" class="grid h-8 w-8 place-items-center text-slate-600 disabled:opacity-40">+</button></div><span class="text-sm font-semibold text-slate-900" x-text="money(item.harga * item.jumlah)"></span></div>
                            </article>
                        </template>
                    </div>
                    <div x-show="cart.length === 0" class="m-4 rounded-xl border border-dashed border-slate-300 p-7 text-center text-sm text-slate-500">Keranjang masih kosong.</div>

                    <div class="space-y-3 border-t border-slate-200 p-4 sm:p-5">
                        <div class="flex items-center justify-between text-sm text-slate-600"><span>Subtotal</span><span class="font-medium text-slate-800" x-text="money(subtotal())"></span></div>
                        <label class="flex items-center justify-between gap-3 text-sm text-slate-600"><span>Diskon</span><span class="flex items-center gap-2"><input type="number" min="0" max="100" step="0.01" x-model.number="discountPercent" class="w-16 rounded-lg border border-slate-200 px-2 py-1.5 text-right text-sm"><span>%</span><span class="w-24 text-right font-medium text-slate-700" x-text="'-' + money(discountAmount())"></span></span></label>
                        <x-input-error :messages="$errors->get('diskon_persen')" />
                        <div class="flex items-end justify-between border-t border-slate-200 pt-3"><span class="text-sm font-semibold text-slate-700">Total Akhir</span><span class="text-2xl font-bold text-blue-800" x-text="money(total())"></span></div>
                        <div x-show="paymentMethod === 'tunai'" x-cloak class="space-y-2">
                            <label for="uang_dibayar" class="block text-sm font-medium text-slate-700">Uang dibayar</label>
                            <input id="uang_dibayar" name="uang_dibayar" type="number" min="0" max="2147483647" step="1" x-model.number="amountPaid" :disabled="paymentMethod !== 'tunai'" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-right">
                            <x-input-error :messages="$errors->get('uang_dibayar')" />
                            <p x-show="cashDifference() >= 0" class="text-right text-sm font-semibold text-emerald-700" x-text="'Estimasi kembalian: ' + money(cashDifference())"></p>
                            <p x-show="cashDifference() < 0" class="text-right text-sm font-semibold text-red-700" x-text="'Pembayaran kurang: ' + money(Math.abs(cashDifference()))"></p>
                        </div>
                        <p class="pt-1 text-sm font-semibold text-slate-800">Metode pembayaran</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="selectPayment('tunai')" :class="paymentMethod === 'tunai' ? 'border-blue-700 bg-blue-50 text-blue-800 ring-1 ring-blue-700' : 'border-slate-200 text-slate-600'" class="flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-sm font-semibold"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><path stroke-linecap="round" d="M7 10h.01M17 15h.01M12 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>Tunai</button>
                            <button type="button" @click="selectPayment('qris')" :class="paymentMethod === 'qris' ? 'border-blue-700 bg-blue-50 text-blue-800 ring-1 ring-blue-700' : 'border-slate-200 text-slate-600'" class="flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-sm font-semibold"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm10 0h2v2h-2zm4 0h2v6h-2zm-4 4h2v2h-2z"/></svg>QRIS</button>
                        </div>
                        <input type="hidden" name="metode_pembayaran" :value="paymentMethod">
                        <button type="submit" :disabled="cart.length === 0" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-800 px-4 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">Proses Pembayaran <span aria-hidden="true">→</span></button>
                        <p class="text-center text-[11px] text-slate-500">Pastikan item dan metode pembayaran sudah benar.</p>
                    </div>
                </aside>
            </div>
        </form>

        <div x-show="showQrisModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-4" @click.self="showQrisModal = false" @keydown.escape.window="showQrisModal = false">
            <section role="dialog" aria-modal="true" aria-labelledby="qris-title" class="w-full max-w-sm rounded-2xl bg-white p-5 text-center shadow-2xl">
                <div class="flex items-center justify-between text-left"><h2 id="qris-title" class="text-lg font-semibold text-slate-900">Pembayaran QRIS</h2><button type="button" @click="showQrisModal = false" aria-label="Tutup" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">×</button></div>
                <img src="{{ asset('images/qris-dummy.svg') }}" alt="QR dummy, bukan kode pembayaran" class="mx-auto mt-4 h-48 w-48 rounded-xl border border-slate-200 p-2">
                <p class="mt-4 text-sm font-semibold text-slate-800">Scan untuk pembayaran via All Payment / E-Wallet</p>
                <p class="mt-1 text-xs text-amber-700">QR ini hanya dummy dan tidak dapat menerima pembayaran.</p>
                <p class="mt-3 text-xl font-bold text-blue-800" x-text="money(total())"></p>
                <button type="button" @click="showQrisModal = false" class="mt-4 w-full rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white">Selesai</button>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-800">10 transaksi terakhir</h3></div>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="px-3 py-2 font-medium">No</th><th class="px-3 py-2 font-medium">Tanggal</th><th class="px-3 py-2 font-medium">Total</th><th class="px-3 py-2 font-medium">Aksi</th></tr></thead><tbody>
                @forelse($lastTransactions as $trx)
                    <tr class="border-t border-slate-200"><td class="px-3 py-2">#{{ $trx->id }}</td><td class="px-3 py-2">{{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('d M Y H:i') }}</td><td class="px-3 py-2 font-semibold text-slate-800">{{ formatRupiah($trx->total) }}</td><td class="px-3 py-2"><a href="{{ route('transaksi.nota', $trx->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700">Cetak ulang</a></td></tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-5 text-center text-slate-500">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const clock = document.getElementById('pos-clock');
                const date = document.getElementById('pos-date');
                const updateClock = () => {
                    const now = new Date();
                    if (clock) clock.textContent = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(now);
                    if (date) date.textContent = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(now);
                };
                updateClock();
                window.setInterval(updateClock, 1000);
            });
        </script>
    @endpush
</x-app-layout>