<x-app-layout>
    <x-slot name="header">Katalog</x-slot>

    <div class="space-y-6" x-data="{
        tab: 'barang', modal: null, kategori: '', namaBarang: @js($q),
        editBarang: { id: '', nama: '', kategori_id: '', harga_beli: '', harga_jual: '', batas_menipis: 5, gambar: null },
        editJasa: { id: '', nama: '', tarif: '' },
        editBarangImageUrl() {
            return this.editBarang?.gambar
                ? @js(\Illuminate\Support\Facades\Storage::disk('public')->url('')) + this.editBarang.gambar
                : @js(asset('images/sparepart-placeholder.svg'));
        },
        barangMatches(namaBarang, kategoriBarang) {
            const keyword = this.namaBarang.trim().toLocaleLowerCase();

            return (this.kategori === '' || this.kategori === kategoriBarang)
                && (keyword === '' || namaBarang.toLocaleLowerCase().includes(keyword));
        },
        hasVisibleBarang() {
            return [...this.$refs.barangGrid.querySelectorAll('[data-katalog-barang]')]
                .some((barang) => this.barangMatches(barang.dataset.namaBarang, barang.dataset.kategoriBarang));
        },
        openModal(name, item = null) {
            this.editBarang = name === 'edit-barang' ? item : { id: '', nama: '', kategori_id: '', harga_beli: '', harga_jual: '', batas_menipis: 5, gambar: null };
            this.editJasa = name === 'edit-jasa' ? item : { id: '', nama: '', tarif: '' };
            this.modal = name;
        },
        closeModal() {
            this.modal = null;
            this.editBarang = { id: '', nama: '', kategori_id: '', harga_beli: '', harga_jual: '', batas_menipis: 5, gambar: null };
            this.editJasa = { id: '', nama: '', tarif: '' };
        }
    }">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-blue-100 p-2 text-blue-800">
                        @include('components.icon.cube')
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Total barang</p>
                        <h2 class="text-xl font-bold text-slate-800">{{ $barang->count() }} item</h2>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('katalog') }}" class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                        <div class="relative min-w-[220px] flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                @include('components.icon.search')
                            </span>
                            <input type="search" name="q" value="{{ $q }}" x-model.debounce.200ms="namaBarang" placeholder="Cari nama barang..." aria-label="Cari nama barang" class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-3 text-sm text-slate-700" />
                        </div>
                        <select name="status" class="rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700">
                            <option value="">Semua</option>
                            @foreach(['Tersedia', 'Menipis', 'Habis'] as $statusOption)
                                <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ $statusOption }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-medium text-white">Cari</button>
                        @if($q || $status)
                            <a href="{{ route('katalog') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600">Bersihkan</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-xl bg-blue-800 px-4 py-2 text-sm font-medium text-white" x-on:click="openModal('create-barang')">Tambah barang</button>
                    <button type="button" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800" x-on:click="openModal('create-kategori')">Tambah kategori</button>
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700" x-on:click="openModal('create-jasa')">Tambah jasa</button>
                </div>
            </div>

            <div class="space-y-5">
                <div class="inline-flex rounded-xl bg-slate-100 p-1">
                    <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium" :class="tab === 'barang' ? 'bg-white text-blue-800 shadow-sm' : 'text-slate-500'" x-on:click="tab = 'barang'">Barang</button>
                    <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium" :class="tab === 'jasa' ? 'bg-white text-blue-800 shadow-sm' : 'text-slate-500'" x-on:click="tab = 'jasa'">Jasa</button>
                </div>

                <div x-show="tab === 'barang'" x-transition>
                    <div class="mb-4 flex space-x-2 overflow-x-auto whitespace-nowrap pb-2 no-scrollbar">
                        <button type="button" @click="kategori = ''" :class="kategori === '' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="shrink-0 rounded-full px-3 py-2 text-sm font-medium transition-colors">Semua</button>
                        @foreach($kategoris as $kategori)
                            <button type="button" @click="kategori = @js($kategori->nama)" :class="kategori === @js($kategori->nama) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="shrink-0 rounded-full px-3 py-2 text-sm font-medium transition-colors">{{ $kategori->nama }}</button>
                        @endforeach
                    </div>
                    @if($barang->isEmpty())
                        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-12 text-center">
                            <div class="mb-4 rounded-full bg-slate-200 p-4 text-slate-500">
                                @include('components.icon.cube')
                            </div>
                            <h3 class="text-lg font-semibold text-slate-800">Barang tidak ditemukan</h3>
                            <p class="mt-2 text-sm text-slate-500">Coba ubah kata kunci atau hapus filter pencarian.</p>
                            <a href="{{ route('katalog') }}" class="mt-4 inline-flex items-center rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-medium text-white">Hapus pencarian</a>
                        </div>
                    @else
                        <div x-ref="barangGrid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($barang as $item)
                                @php
                                    $badgeClass = match ($item->status) {
                                        'Tersedia' => 'bg-emerald-100 text-emerald-700',
                                        'Menipis' => 'bg-yellow-100 text-yellow-700',
                                        default => 'bg-red-100 text-red-700',
                                    };
                                    $gambarUrl = $item->gambar
                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($item->gambar)
                                        : asset('images/sparepart-placeholder.svg');
                                @endphp
                                <article data-katalog-barang data-nama-barang="{{ $item->nama }}" data-kategori-barang="{{ $item->kategori_nama }}" x-show="barangMatches($el.dataset.namaBarang, $el.dataset.kategoriBarang)" class="rounded-2xl border {{ $item->status === 'Menipis' ? 'border-l-4 border-l-yellow-400' : ($item->status === 'Habis' ? 'border-l-4 border-l-red-400 opacity-80' : 'border-slate-200') }} bg-white p-4 shadow-sm">
                                    <div class="relative isolate mb-4 h-48 w-full overflow-hidden rounded-xl bg-slate-100">
                                        <img src="{{ $gambarUrl }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full scale-110 object-cover opacity-60 blur-xl">
                                        <img src="{{ $gambarUrl }}" alt="{{ $item->nama }}" class="relative z-10 h-full w-full object-contain">
                                    </div>
                                    <div class="mb-4 flex items-start justify-end gap-3">
                                        <span class="rounded-full px-2 py-1 text-[11px] font-semibold {{ $badgeClass }}">{{ $item->status }}</span>
                                    </div>

                                    <h3 class="line-clamp-2 min-h-[2.6rem] text-base font-semibold text-slate-800">{{ $item->nama }}</h3>
                                    <p class="mt-4 text-2xl font-bold text-blue-800">{{ formatRupiah($item->harga_jual) }}</p>
                                    <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                                        <span>Stok</span>
                                        <span class="font-semibold text-slate-800">{{ $item->stok }} unit</span>
                                    </div>
                                    <div class="mt-4 flex items-center gap-2 pt-4 border-t border-slate-200">
                                        <button type="button" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700" x-on:click="openModal('edit-barang', @js(['id' => $item->id, 'nama' => $item->nama, 'kategori_id' => $item->kategori_id, 'harga_beli' => $item->harga_beli, 'harga_jual' => $item->harga_jual, 'batas_menipis' => $item->batas_menipis ?? 5, 'gambar' => $item->gambar]))">
                                            @include('components.icon.edit')
                                            Ubah
                                        </button>
                                        <form method="POST" action="{{ route('barang.destroy', $item->id) }}" class="flex-1" onsubmit="return confirm('Nonaktifkan barang ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
                                                @include('components.icon.trash')
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div x-show="!hasVisibleBarang()" x-cloak class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            Tidak ada barang yang cocok dengan pencarian atau kategori ini.
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'jasa'" x-transition>
                    <div class="space-y-3">
                        @foreach($jasa as $item)
                            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $item->nama }}</p>
                                    <p class="text-sm text-slate-500">{{ formatRupiah($item->tarif) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700" x-on:click="openModal('edit-jasa', @js(['id' => $item->id, 'nama' => $item->nama, 'tarif' => $item->tarif]))">@include('components.icon.edit') Ubah</button>
                                    <form method="POST" action="{{ route('jasa.destroy', $item->id) }}" onsubmit="return confirm('Hapus jasa ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">@include('components.icon.trash') Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div x-show="modal === 'create-kategori'" x-cloak class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/30 p-4 sm:items-center" @click.self="closeModal()" @keydown.escape.window="closeModal()">
                    <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-800">Tambah kategori</h3>
                            <button type="button" aria-label="Tutup modal" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" x-on:click="closeModal()">✕</button>
                        </div>
                        <form method="POST" action="{{ route('kategori.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="nama_kategori" class="mb-1 block text-sm font-medium text-slate-700">Nama kategori</label>
                                <input id="nama_kategori" type="text" name="nama" maxlength="50" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" placeholder="Contoh: Kampas Rem" required>
                            </div>
                            <div class="flex justify-end gap-3">
                                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700" x-on:click="closeModal()">Batal</button>
                                <button type="submit" class="rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-medium text-white">Simpan kategori</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="modal === 'create-barang'" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/30 p-4 sm:items-center" @click.self="closeModal()" @keydown.escape.window="closeModal()">
                    <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-800">Tambah barang</h3>
                            <button type="button" class="rounded-lg border border-slate-200 p-2 text-slate-500" x-on:click="closeModal()">✕</button>
                        </div>
                        <form method="POST" action="{{ route('barang.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama barang</label>
                                    <input type="text" name="nama" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                                    <select name="kategori_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                        <option value="">Pilih kategori</option>
                                        @foreach($kategoris as $kategoriItem)
                                            <option value="{{ $kategoriItem->id }}" @selected(old('kategori_id', $kategoris->first()?->id) == $kategoriItem->id)>{{ $kategoriItem->nama }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('kategori_id')" class="mt-1" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Gambar barang</label>
                                    <input type="file" name="gambar" accept="image/*" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Harga beli</label>
                                        <input type="number" name="harga_beli" min="1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Harga jual</label>
                                        <input type="number" name="harga_jual" min="1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Batas menipis</label>
                                        <input type="number" name="batas_menipis" value="5" min="0" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Stok awal</label>
                                        <input type="number" name="stok_awal" min="0" value="0" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700" x-on:click="closeModal()">Batal</button>
                                <button type="submit" class="rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-medium text-white">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="modal === 'create-jasa'" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/30 p-4 sm:items-center" @click.self="closeModal()" @keydown.escape.window="closeModal()">
                    <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-800">Tambah jasa</h3>
                            <button type="button" class="rounded-lg border border-slate-200 p-2 text-slate-500" x-on:click="closeModal()">✕</button>
                        </div>
                        <form method="POST" action="{{ route('jasa.store') }}">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama jasa</label>
                                    <input type="text" name="nama" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Tarif</label>
                                    <input type="number" name="tarif" min="0" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700" x-on:click="closeModal()">Batal</button>
                                <button type="submit" class="rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-medium text-white">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="modal === 'edit-barang'" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/30 p-4 sm:items-center" @click.self="closeModal()" @keydown.escape.window="closeModal()">
                    <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-800">Ubah barang</h3>
                            <button type="button" class="rounded-lg border border-slate-200 p-2 text-slate-500" x-on:click="closeModal()">✕</button>
                        </div>
                        <form :action="'/barang/' + editBarang?.id" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama barang</label>
                                    <input type="text" name="nama" x-model="editBarang.nama" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                                    <select name="kategori_id" x-model="editBarang.kategori_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                        <option value="">Pilih kategori</option>
                                        @foreach($kategoris as $kategoriItem)
                                            <option value="{{ $kategoriItem->id }}">{{ $kategoriItem->nama }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('kategori_id')" class="mt-1" />
                                </div>
                                <div>
                                    <div class="relative isolate mb-2 h-32 w-full overflow-hidden rounded-xl bg-slate-100">
                                        <img :src="editBarangImageUrl()" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full scale-110 object-cover opacity-60 blur-xl">
                                        <img :src="editBarangImageUrl()" alt="Pratinjau gambar barang" class="relative z-10 h-full w-full object-contain">
                                    </div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Ganti gambar barang</label>
                                    <input type="file" name="gambar" accept="image/*" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Harga beli</label>
                                        <input type="number" name="harga_beli" x-model="editBarang.harga_beli" min="1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Harga jual</label>
                                        <input type="number" name="harga_jual" x-model="editBarang.harga_jual" min="1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Batas menipis</label>
                                    <input type="number" name="batas_menipis" x-model="editBarang.batas_menipis" min="0" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700" x-on:click="closeModal()">Batal</button>
                                <button type="submit" class="rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-medium text-white">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="modal === 'edit-jasa'" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/30 p-4 sm:items-center" @click.self="closeModal()" @keydown.escape.window="closeModal()">
                    <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-800">Ubah jasa</h3>
                            <button type="button" class="rounded-lg border border-slate-200 p-2 text-slate-500" x-on:click="closeModal()">✕</button>
                        </div>
                        <form :action="'/jasa/' + editJasa?.id" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama jasa</label>
                                    <input type="text" name="nama" x-model="editJasa.nama" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Tarif</label>
                                    <input type="number" name="tarif" x-model="editJasa.tarif" min="0" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3" required>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700" x-on:click="closeModal()">Batal</button>
                                <button type="submit" class="rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-medium text-white">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>