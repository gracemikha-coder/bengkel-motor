<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangMasukController extends Controller
{
    public function index(Request $request)
    {
        $barang = DB::table('barang')->orderBy('nama')->get();

        $latest = DB::table('barang_masuk as bm')
            ->join('barang as b', 'b.id', '=', 'bm.barang_id')
            ->select('bm.*', 'b.nama as nama_barang')
            ->orderByDesc('bm.id')
            ->limit(20)
            ->get();

        $perluBeli = DB::table('barang')
            ->select('barang.*')
            ->selectRaw("CASE WHEN stok <= 0 THEN 'Habis' WHEN stok <= batas_menipis THEN 'Menipis' ELSE 'Tersedia' END AS status")
            ->where(function ($query) {
                $query->where('stok', '<=', 0)
                    ->orWhere(function ($query) {
                        $query->where('stok', '>', 0)->whereColumn('stok', '<=', 'batas_menipis');
                    });
            })
            ->orderByRaw('CASE WHEN stok <= 0 THEN 0 ELSE 1 END')
            ->orderBy('stok', 'asc')
            ->limit(10)
            ->get();

        $selectedBarangId = $request->query('barang_id');

        return view('barang-masuk.index', compact('barang', 'latest', 'perluBeli', 'selectedBarangId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'barang_id' => ['required', 'integer', 'exists:barang,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'harga_beli' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'toko' => ['nullable', 'string', 'max:100'],
        ], [
            'barang_id.required' => 'Barang wajib dipilih.',
            'barang_id.exists' => 'Barang yang dipilih tidak valid.',
            'jumlah.required' => 'Jumlah barang masuk wajib diisi.',
            'jumlah.min' => 'Jumlah barang masuk minimal 1.',
            'harga_beli.required' => 'Harga beli per unit wajib diisi.',
            'harga_beli.min' => 'Harga beli minimal 1.',
            'tanggal.required' => 'Tanggal masuk wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
        ]);

        $barang = DB::table('barang')->where('id', $validated['barang_id'])->first();

        DB::transaction(function () use ($validated) {
            // Memanggil stored procedure untuk mencatat stock masuk dan menaikkan stok.
            DB::statement('CALL sp_catat_barang_masuk(?, ?, ?, ?)', [
                (int) $validated['barang_id'],
                (int) $validated['jumlah'],
                (int) $validated['harga_beli'],
                $validated['toko'] ?? null,
            ]);
        });

        $stokBaru = DB::table('barang')->where('id', $validated['barang_id'])->value('stok');

        return redirect()->route('barang-masuk')->with(
            'success',
            $barang->nama.' berhasil ditambahkan. Stok saat ini: '.$stokBaru.' unit.'
        );
    }
}
