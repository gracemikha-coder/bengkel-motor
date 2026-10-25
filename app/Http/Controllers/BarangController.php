<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Kategori;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->q);
        $status = $request->status;
        $kategoris = Kategori::query()->orderBy('nama')->get(['id', 'nama']);

        $barang = DB::table('barang as b')
            ->leftJoin('kategori as k', 'k.id', '=', 'b.kategori_id')
            ->where('b.is_active', true)
            ->select('b.*', 'k.nama as kategori_nama')
            ->selectRaw("CASE WHEN b.stok <= 0 THEN 'Habis' WHEN b.stok <= b.batas_menipis THEN 'Menipis' ELSE 'Tersedia' END AS status")
            ->when($q !== '', fn ($query) => $query->where('b.nama', 'like', "%{$q}%"))
            ->when($status === 'Habis', fn ($query) => $query->where('b.stok', '<=', 0))
            ->when($status === 'Menipis', fn ($query) => $query->where('b.stok', '>', 0)->whereColumn('b.stok', '<=', 'b.batas_menipis'))
            ->when($status === 'Tersedia', fn ($query) => $query->whereColumn('b.stok', '>', 'b.batas_menipis'))
            ->orderBy('b.nama')
            ->get();

        $jasa = DB::table('jasa')
            ->orderBy('nama')
            ->get();

        return view('katalog.index', compact('barang', 'jasa', 'q', 'status', 'kategoris'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'harga_beli' => ['required', 'integer', 'min:1'],
            'harga_jual' => ['required', 'integer', 'min:1'],
            'batas_menipis' => ['required', 'integer', 'min:0'],
            'stok_awal' => ['nullable', 'integer', 'min:0'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nama.required' => 'Nama barang wajib diisi.',
            'nama.max' => 'Nama barang maksimal 100 karakter.',
            'kategori_id.required' => 'Kategori barang wajib dipilih.',
            'kategori_id.exists' => 'Kategori yang dipilih tidak valid.',
            'harga_beli.required' => 'Harga beli wajib diisi.',
            'harga_beli.integer' => 'Harga beli harus berupa angka.',
            'harga_beli.min' => 'Harga beli minimal 1.',
            'harga_jual.required' => 'Harga jual wajib diisi.',
            'harga_jual.integer' => 'Harga jual harus berupa angka.',
            'harga_jual.min' => 'Harga jual minimal 1.',
            'batas_menipis.required' => 'Batas menipis wajib diisi.',
            'batas_menipis.min' => 'Batas menipis tidak boleh negatif.',
            'stok_awal.integer' => 'Stok awal harus berupa angka.',
            'stok_awal.min' => 'Stok awal tidak boleh negatif.',
        ]);

        $gambar = $request->file('gambar')?->store('barang', 'public');

        try {
            DB::beginTransaction();

            $barangId = DB::table('barang')->insertGetId([
                'nama' => trim($validated['nama']),
                'kategori_id' => (int) $validated['kategori_id'],
                'harga_beli' => (int) $validated['harga_beli'],
                'harga_jual' => (int) $validated['harga_jual'],
                'stok' => 0,
                'batas_menipis' => (int) $validated['batas_menipis'],
                'gambar' => $gambar,
            ]);

            if (! empty($validated['stok_awal']) && (int) $validated['stok_awal'] > 0) {
                DB::table('barang_masuk')->insert([
                    'tanggal' => now()->toDateString(),
                    'barang_id' => $barangId,
                    'jumlah' => (int) $validated['stok_awal'],
                    'harga_beli' => (int) $validated['harga_beli'],
                    'toko' => 'Stok awal',
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($gambar) {
                Storage::disk('public')->delete($gambar);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Barang berhasil ditambah.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'harga_beli' => ['required', 'integer', 'min:1'],
            'harga_jual' => ['required', 'integer', 'min:1'],
            'batas_menipis' => ['required', 'integer', 'min:0'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nama.required' => 'Nama barang wajib diisi.',
            'nama.max' => 'Nama barang maksimal 100 karakter.',
            'kategori_id.required' => 'Kategori barang wajib dipilih.',
            'kategori_id.exists' => 'Kategori yang dipilih tidak valid.',
            'harga_beli.required' => 'Harga beli wajib diisi.',
            'harga_beli.integer' => 'Harga beli harus berupa angka.',
            'harga_beli.min' => 'Harga beli minimal 1.',
            'harga_jual.required' => 'Harga jual wajib diisi.',
            'harga_jual.integer' => 'Harga jual harus berupa angka.',
            'harga_jual.min' => 'Harga jual minimal 1.',
            'batas_menipis.required' => 'Batas menipis wajib diisi.',
            'batas_menipis.min' => 'Batas menipis tidak boleh negatif.',
        ]);

        $barang = DB::table('barang')->where('id', $id)->first();
        abort_unless($barang, 404);

        $gambar = $request->file('gambar')?->store('barang', 'public');
        DB::table('barang')->where('id', $id)->update([
            'nama' => trim($validated['nama']),
            'kategori_id' => (int) $validated['kategori_id'],
            'harga_beli' => (int) $validated['harga_beli'],
            'harga_jual' => (int) $validated['harga_jual'],
            'batas_menipis' => (int) $validated['batas_menipis'],
            'gambar' => $gambar ?? $barang->gambar,
        ]);
        if ($gambar && $barang->gambar) {
            Storage::disk('public')->delete($barang->gambar);
        }

        return back()->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $barang = Barang::query()->findOrFail($id);
        $barang->update(['is_active' => false]);

        return back()->with('success', 'Barang berhasil dinonaktifkan.');
    }
}
