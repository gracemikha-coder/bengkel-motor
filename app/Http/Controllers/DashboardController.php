<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalBarang = DB::table('barang')->count();
        $stokMenipis = DB::table('barang')->where('stok', '>', 0)->whereColumn('stok', '<=', 'batas_menipis')->count();
        $stokHabis = DB::table('barang')->where('stok', '<=', 0)->count();
        $transaksiHariIni = DB::table('transaksi')->whereDate('tanggal', today())->count();

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
            ->limit(5)
            ->get();

        $labels = [];
        $series = [];

        foreach (range(6, 0) as $dayOffset) {
            $date = now()->subDays($dayOffset);
            $labels[] = $date->translatedFormat('d M');
            $total = DB::table('transaksi')
                ->whereDate('tanggal', $date->toDateString())
                ->sum('total');
            $series[] = (int) ($total ?? 0);
        }

        $transaksiTerakhir = DB::table('transaksi')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalBarang',
            'stokMenipis',
            'stokHabis',
            'transaksiHariIni',
            'perluBeli',
            'labels',
            'series',
            'transaksiTerakhir'
        ));
    }
}
