<x-app-layout>
    <x-slot name="header">Kasir</x-slot>

    <div class="space-y-6">
        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div x-data="{
            cart: @js($cart ?? []),
            paymentMethod: 'tunai',
            addItem(type, id, name, price, stock = null) {
                const existing = this.cart.find((item) => item.tipe === type && item.id === id);
                if (existing) {
                    if (type === 'barang' && existing.jumlah >= stock) {
                        return;
                    }
                    existing.jumlah += 1;
                    return;
                }

                this.cart.push({ tipe: type, id, nama: name, harga: Number(price), stok: Number(stock || 0), jumlah: 1 });
            },
            changeQty(index, delta) {
                const item = this.cart[index];
                if (!item) return;
                const next = item.jumlah + delta;
                if (next <= 0) {
                    this.cart.splice(index, 1);
                    return;
                }
                if (item.tipe === 'barang' && next > item.stok) {
                    return;
                }
                item.jumlah = next;
            },
            removeItem(index) {
                this.cart.splice(index, 1);
            },
            subtotal(item) {
                return Number(item.harga) * Number(item.jumlah);
            },
            total() {
                return this.cart.reduce((sum, item) => sum + this.subtotal(item), 0);
            }
        }" class="grid gap-6 lg:grid-cols-[1.3fr_0.9fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Cari barang & jasa</h2>
                    <form method="GET" action="{{ route('kasir') }}" class="flex w-full max-w-sm gap-2">
                        <input type="text" name="q" value="{{ $query }}" placeholder="Cari nama barang atau jasa" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3">
                        <button type="submit" class="rounded-xl bg-blue-800 px-4 py-2 text-sm font-medium text-white">Cari</button>
                    </form>
                </div>

                <div class="space-y-5">
                    @if($query)
                        <div>
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Barang</h3>
                            @forelse($barang as $item)
                                <div class="mb-3 flex items-center justify-between rounded-2xl border border-slate-200 p-3">
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $item->nama }}</p>
                                        <p class="text-sm text-slate-500">{{ formatRupiah($item->harga_jual) }} · stok {{ $item->stok }}</p>
                                    </div>
                                    <button type="button" @click="addItem('barang', {{ $item->id }}, '{{ addslashes($item->nama) }}', {{ $item->harga_jual }}, {{ $item->stok }})" class="rounded-xl bg-blue-800 px-3 py-2 text-sm font-medium text-white" :disabled="{{ $item->stok }} === 0">Tambah</button>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Tidak ada barang yang cocok.</div>
                            @endforelse
                        </div>

                        <div>
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Jasa</h3>
                            @forelse($jasa as $item)
                                <div class="mb-3 flex items-center justify-between rounded-2xl border border-slate-200 p-3">
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $item->nama }}</p>
                                        <p class="text-sm text-slate-500">{{ formatRupiah($item->tarif) }}</p>
                                    </div>
                                    <button type="button" @click="addItem('jasa', {{ $item->id }}, '{{ addslashes($item->nama) }}', {{ $item->tarif }})" class="rounded-xl bg-slate-800 px-3 py-2 text-sm font-medium text-white">Tambah</button>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Tidak ada jasa yang cocok.</div>
                            @endforelse
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">
                            Cari barang atau jasa untuk mulai transaksi.
                        </div>
                    @endif
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Keranjang</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600" x-text="cart.length + ' item'"></span>
                </div>

                <div class="space-y-3" x-show="cart.length > 0">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="rounded-2xl border border-slate-200 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-slate-800" x-text="item.nama"></p>
                                    <p class="text-sm text-slate-500" x-text="item.tipe === 'barang' ? 'Barang' : 'Jasa'"></p>
                                </div>
                                <button type="button" class="text-sm text-red-600" @click="removeItem(index)">Hapus</button>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50">
                                    <button type="button" class="px-2 py-2 text-lg text-slate-600" @click="changeQty(index, -1)">−</button>
                                    <span class="min-w-[2ch] text-center text-sm font-medium text-slate-700" x-text="item.jumlah"></span>
                                    <button type="button" class="px-2 py-2 text-lg text-slate-600" @click="changeQty(index, 1)">+</button>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-slate-500">Subtotal</p>
                                    <p class="font-semibold text-slate-800" x-text="'Rp' + subtotal(item).toLocaleString('id-ID')"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="cart.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                    Keranjang masih kosong.
                </div>

                <div class="mt-5 border-t border-slate-200 pt-4">
                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>Total</span>
                        <span class="text-2xl font-bold text-blue-800" x-text="'Rp' + total().toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <form method="POST" action="{{ route('kasir.store') }}" class="mt-5">
                    @csrf
                    <input type="hidden" name="items" :value="JSON.stringify(cart)" />
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        <p class="text-sm font-medium text-slate-700">Metode pembayaran</p>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700">
                                <input type="radio" name="metode_pembayaran" value="tunai" x-model="paymentMethod" class="text-teal-700" required>
                                Tunai
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700">
                                <input type="radio" name="metode_pembayaran" value="qris" x-model="paymentMethod" class="text-teal-700" required>
                                QRIS
                            </label>
                        </div>
                        <div x-show="paymentMethod === 'qris'" x-transition class="rounded-xl border border-dashed border-teal-300 bg-teal-50 p-4 text-center" x-cloak>
                            <img src="{{ asset('images/qris-dummy.svg') }}" alt="QRIS dummy, bukan kode pembayaran" class="mx-auto h-40 w-40 rounded-lg bg-white p-2">
                            <p class="mt-2 text-sm font-semibold text-teal-900">Scan untuk pembayaran via All Payment / E-Wallet</p>
                            <p class="mt-1 text-xs text-teal-800">Kode QR demo, bukan kode pembayaran resmi.</p>
                        </div>
                        <div>
                            <label for="whatsapp" class="mb-1 block text-sm font-medium text-slate-700">Nomor WhatsApp pelanggan (opsional)</label>
                            <input id="whatsapp" type="tel" name="whatsapp" value="{{ old('whatsapp') }}" inputmode="tel" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <x-input-error :messages="$errors->get('whatsapp')" class="mt-2" />
                        </div>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-blue-800 px-4 py-3 text-sm font-semibold text-white" :disabled="cart.length === 0">Simpan transaksi</button>
                </form>
            </aside>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">10 transaksi terakhir</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-3 py-2 font-medium">No</th>
                            <th class="px-3 py-2 font-medium">Tanggal</th>
                            <th class="px-3 py-2 font-medium">Total</th>
                            <th class="px-3 py-2 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lastTransactions as $trx)
                            <tr class="border-t border-slate-200">
                                <td class="px-3 py-2">#{{ $trx->id }}</td>
                                <td class="px-3 py-2">{{ \Carbon\Carbon::parse($trx->tanggal)->translatedFormat('d M Y H:i') }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-800">{{ formatRupiah($trx->total) }}</td>
                                <td class="px-3 py-2"><a href="{{ route('transaksi.nota', $trx->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700">Cetak ulang</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-5 text-center text-slate-500">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
