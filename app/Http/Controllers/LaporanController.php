<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->query('mode', 'weekly');

        if ($mode === 'monthly') {
            $labels = [];
            $series = [];
            $startDate = now()->subMonths(5)->startOfMonth();
            $periodStart = $startDate->copy();

            foreach (range(0, 5) as $monthOffset) {
                $period = $startDate->copy()->addMonths($monthOffset);
                $labels[] = $period->translatedFormat('M Y');
                $sum = DB::table('transaksi')
                    ->whereYear('tanggal', $period->year)
                    ->whereMonth('tanggal', $period->month)
                    ->sum('total');
                $series[] = (int) ($sum ?? 0);
            }

            $start = $periodStart->format('Y-m-d');
            $end = now()->format('Y-m-d');
        } else {
            $labels = [];
            $series = [];
            $start = now()->subDays(6)->format('Y-m-d');
            $end = now()->format('Y-m-d');

            foreach (range(6, 0) as $dayOffset) {
                $date = now()->subDays($dayOffset);
                $labels[] = $date->translatedFormat('d M');
                $sum = DB::table('transaksi')
                    ->whereDate('tanggal', $date->toDateString())
                    ->sum('total');
                $series[] = (int) ($sum ?? 0);
            }
        }

        $summary = DB::selectOne('CALL sp_laporan(?, ?)', [$start, $end]);
        $summary = $summary ?? (object) ['uang_masuk' => 0, 'modal_barang' => 0, 'untung_bersih' => 0];

        $dailyDetail = [];
        if ($mode === 'weekly') {
            foreach (range(6, 0) as $offset) {
                $date = now()->subDays($offset);
                $rows = DB::table('detail_transaksi as dt')
                    ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
                    ->selectRaw('COUNT(DISTINCT t.id) as jumlah_transaksi, COALESCE(SUM(dt.jumlah * dt.harga_jual), 0) as uang_masuk, COALESCE(SUM(dt.jumlah * dt.harga_beli), 0) as modal_barang, COALESCE(SUM(dt.jumlah * (dt.harga_jual - dt.harga_beli)), 0) as untung')
                    ->whereDate('t.tanggal', $date->toDateString())
                    ->first();

                if ($rows->jumlah_transaksi > 0) {
                    $dailyDetail[] = [
                        'periode' => $date->translatedFormat('d M'),
                        'jumlah_transaksi' => (int) $rows->jumlah_transaksi,
                        'uang_masuk' => (int) ($rows->uang_masuk ?? 0),
                        'modal_barang' => (int) ($rows->modal_barang ?? 0),
                        'untung' => (int) ($rows->untung ?? 0),
                    ];
                }
            }
        } else {
            $startMonth = now()->subMonths(5)->startOfMonth();
            foreach (range(0, 5) as $index) {
                $period = $startMonth->copy()->addMonths($index);
                $rows = DB::table('detail_transaksi as dt')
                    ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
                    ->selectRaw('COUNT(DISTINCT t.id) as jumlah_transaksi, COALESCE(SUM(dt.jumlah * dt.harga_jual), 0) as uang_masuk, COALESCE(SUM(dt.jumlah * dt.harga_beli), 0) as modal_barang, COALESCE(SUM(dt.jumlah * (dt.harga_jual - dt.harga_beli)), 0) as untung')
                    ->whereYear('t.tanggal', $period->year)
                    ->whereMonth('t.tanggal', $period->month)
                    ->first();

                if ($rows->jumlah_transaksi > 0) {
                    $dailyDetail[] = [
                        'periode' => $period->translatedFormat('M Y'),
                        'jumlah_transaksi' => (int) $rows->jumlah_transaksi,
                        'uang_masuk' => (int) ($rows->uang_masuk ?? 0),
                        'modal_barang' => (int) ($rows->modal_barang ?? 0),
                        'untung' => (int) ($rows->untung ?? 0),
                    ];
                }
            }
        }

        $pendapatanBarang = (int) DB::table('detail_transaksi as dt')
            ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
            ->whereNotNull('dt.barang_id')
            ->sum(DB::raw('dt.jumlah * dt.harga_jual'));

        $pendapatanJasa = (int) DB::table('detail_transaksi as dt')
            ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
            ->whereNotNull('dt.jasa_id')
            ->sum(DB::raw('dt.jumlah * dt.harga_jual'));

        $barangTerlaris = DB::table('detail_transaksi as dt')
            ->join('barang as b', 'b.id', '=', 'dt.barang_id')
            ->select('b.nama', DB::raw('SUM(dt.jumlah) as total'))
            ->whereNotNull('dt.barang_id')
            ->groupBy('b.id', 'b.nama')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('laporan.index', compact('mode', 'labels', 'series', 'summary', 'dailyDetail', 'pendapatanBarang', 'pendapatanJasa', 'barangTerlaris'));
    }

    public function export(Request $request)
    {
        $mode = $request->query('mode') === 'monthly' ? 'monthly' : 'weekly';
        $today = now();
        $endDate = $today->toDateString();
        $startDate = $mode === 'monthly'
            ? $today->copy()->subMonths(5)->startOfMonth()->toDateString()
            : $today->copy()->subDays(6)->toDateString();

        $rows = DB::table('detail_transaksi as dt')
            ->join('transaksi as t', 't.id', '=', 'dt.transaksi_id')
            ->selectRaw('DATE(t.tanggal) as tanggal')
            ->selectRaw('COUNT(DISTINCT t.id) as jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(CASE WHEN dt.barang_id IS NOT NULL THEN dt.jumlah * dt.harga_jual ELSE 0 END), 0) as pendapatan_barang')
            ->selectRaw('COALESCE(SUM(CASE WHEN dt.jasa_id IS NOT NULL THEN dt.jumlah * dt.harga_jual ELSE 0 END), 0) as pendapatan_jasa')
            ->selectRaw('COALESCE(SUM(dt.jumlah * dt.harga_jual), 0) as uang_masuk')
            ->selectRaw('COALESCE(SUM(dt.jumlah * dt.harga_beli), 0) as modal')
            ->selectRaw('COALESCE(SUM(dt.jumlah * (dt.harga_jual - dt.harga_beli)), 0) as untung_bersih')
            ->whereDate('t.tanggal', '>=', $startDate)
            ->whereDate('t.tanggal', '<=', $endDate)
            ->groupByRaw('DATE(t.tanggal)')
            ->orderBy('tanggal')
            ->get();

        if ($mode === 'monthly') {
            $rows = $rows->groupBy(fn ($row) => substr($row->tanggal, 0, 7))
                ->map(fn ($periodRows, $period) => (object) [
                    'tanggal' => $period,
                    'jumlah_transaksi' => $periodRows->sum('jumlah_transaksi'),
                    'pendapatan_barang' => $periodRows->sum('pendapatan_barang'),
                    'pendapatan_jasa' => $periodRows->sum('pendapatan_jasa'),
                    'uang_masuk' => $periodRows->sum('uang_masuk'),
                    'modal' => $periodRows->sum('modal'),
                    'untung_bersih' => $periodRows->sum('untung_bersih'),
                ])
                ->values();
        }

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Tanggal',
                'Jumlah Transaksi',
                'Pendapatan Barang (Rp)',
                'Pendapatan Jasa (Rp)',
                'Uang Masuk (Rp)',
                'Modal (Rp)',
                'Untung Bersih (Rp)',
            ], ';', '"', '');

            foreach ($rows as $row) {
                fputcsv($stream, [
                    $row->tanggal,
                    (int) $row->jumlah_transaksi,
                    (int) $row->pendapatan_barang,
                    (int) $row->pendapatan_jasa,
                    (int) $row->uang_masuk,
                    (int) $row->modal,
                    (int) $row->untung_bersih,
                ], ';', '"', '');
            }

            fclose($stream);
        }, 'laporan-'.$mode.'-'.$endDate.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
