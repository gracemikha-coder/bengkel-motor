<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JasaController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/katalog', [BarangController::class, 'index'])->name('katalog');
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::post('/barang', [BarangController::class, 'store'])->name('barang.store');
    Route::put('/barang/{id}', [BarangController::class, 'update'])->name('barang.update');
    Route::delete('/barang/{id}', [BarangController::class, 'destroy'])->name('barang.destroy');

    Route::post('/jasa', [JasaController::class, 'store'])->name('jasa.store');
    Route::put('/jasa/{id}', [JasaController::class, 'update'])->name('jasa.update');
    Route::delete('/jasa/{id}', [JasaController::class, 'destroy'])->name('jasa.destroy');

    Route::get('/barang-masuk', [BarangMasukController::class, 'index'])->name('barang-masuk');
    Route::post('/barang-masuk', [BarangMasukController::class, 'store'])->name('barang-masuk.store');

    Route::get('/kasir', [KasirController::class, 'index'])->name('kasir');
    Route::post('/kasir', [KasirController::class, 'store'])->name('kasir.store');
    Route::get('/transaksi/{id}/nota', [KasirController::class, 'nota'])->name('transaksi.nota');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
    Route::get('/laporan/unduh', [LaporanController::class, 'export'])->name('laporan.export');
});

Route::get('/transaksi/{id}/nota/pdf', [KasirController::class, 'pdf'])
    ->middleware('signed')
    ->name('transaksi.nota.pdf');

require __DIR__.'/auth.php';
