<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:50', 'unique:kategori,nama'],
        ]);

        Kategori::create(['nama' => trim($validated['nama'])]);

        return redirect()->route('katalog')->with('success', 'Kategori berhasil ditambahkan.');
    }
}
