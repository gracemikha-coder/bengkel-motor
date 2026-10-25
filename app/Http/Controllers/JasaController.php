<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JasaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tarif' => ['required', 'integer', 'min:0'],
        ], [
            'nama.required' => 'Nama jasa wajib diisi.',
            'nama.max' => 'Nama jasa maksimal 100 karakter.',
            'tarif.required' => 'Tarif jasa wajib diisi.',
            'tarif.integer' => 'Tarif jasa harus berupa angka.',
            'tarif.min' => 'Tarif jasa tidak boleh negatif.',
        ]);

        DB::table('jasa')->insert([
            'nama' => trim($validated['nama']),
            'tarif' => (int) $validated['tarif'],
        ]);

        return back()->with('success', 'Jasa berhasil ditambah.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tarif' => ['required', 'integer', 'min:0'],
        ], [
            'nama.required' => 'Nama jasa wajib diisi.',
            'nama.max' => 'Nama jasa maksimal 100 karakter.',
            'tarif.required' => 'Tarif jasa wajib diisi.',
            'tarif.integer' => 'Tarif jasa harus berupa angka.',
            'tarif.min' => 'Tarif jasa tidak boleh negatif.',
        ]);

        DB::table('jasa')->where('id', $id)->update([
            'nama' => trim($validated['nama']),
            'tarif' => (int) $validated['tarif'],
        ]);

        return back()->with('success', 'Jasa berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (DB::table('detail_transaksi')->where('jasa_id', $id)->exists()) {
            return back()->withErrors(['jasa' => 'Jasa tidak dapat dihapus karena sudah dipakai di transaksi.']);
        }

        DB::table('jasa')->where('id', $id)->delete();

        return back()->with('success', 'Jasa berhasil dihapus.');
    }
}
