<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->q);

        $barang = DB::table('barang')
            ->leftJoin('kategori as k', 'k.id', '=', 'barang.kategori_id')
            ->select('barang.*')
            ->selectRaw("COALESCE(k.nama, 'Lainnya') as kategori_nama")
            ->where('barang.is_active', true)
            ->where('barang.stok', '>', 0)
            ->when($query !== '', fn ($builder) => $builder->where('barang.nama', 'like', "%{$query}%"))
            ->orderBy('barang.nama')
            ->limit(100)
            ->get()
            ->map(function ($item) {
                $item->gambar_url = $item->gambar
                    ? Storage::disk('public')->url($item->gambar)
                    : asset('images/sparepart-placeholder.svg');

                return $item;
            });
        $kategoriOptions = Kategori::query()->orderBy('nama')->pluck('nama')->values();
        if (DB::table('barang')->where('stok', '>', 0)->whereNull('kategori_id')->exists()
            && ! $kategoriOptions->contains('Lainnya')) {
            $kategoriOptions->push('Lainnya');
        }

        $jasa = DB::table('jasa')->orderBy('nama')->limit(100)->get();
        $quickServiceDefinitions = [
            ['label' => 'Ganti Oli', 'keywords' => ['ganti oli', 'oli']],
            ['label' => 'Tune Up', 'keywords' => ['tune up', 'tune-up']],
            ['label' => 'Ganti Ban', 'keywords' => ['ganti ban', 'ban']],
            ['label' => 'Service Rem', 'keywords' => ['service rem', 'servis rem', 'rem']],
        ];
        $quickServices = collect($quickServiceDefinitions)
            ->map(function ($definition) use ($jasa) {
                $service = $jasa->first(fn ($item) => Str::contains(Str::lower($item->nama), $definition['keywords']));

                return $service ? [
                    'label' => $definition['label'],
                    'id' => (int) $service->id,
                    'nama' => $service->nama,
                    'harga' => (int) $service->tarif,
                ] : null;
            })
            ->filter()
            ->values();

        $quickServiceLookup = $quickServices->keyBy('label');
        $paketServis = collect([
            ['nama' => 'Paket Servis Ringan', 'layanan' => ['Ganti Oli', 'Tune Up']],
            ['nama' => 'Paket Servis Berkala', 'layanan' => ['Ganti Oli', 'Tune Up', 'Service Rem']],
        ])->map(function ($package) use ($quickServiceLookup) {
            $services = collect($package['layanan'])
                ->map(fn ($label) => $quickServiceLookup->get($label))
                ->filter()
                ->values();

            return $services->count() > 1 ? [
                'nama' => $package['nama'],
                'layanan' => $services,
                'harga' => (int) $services->sum('harga'),
            ] : null;
        })->filter()->values();

        $lastTransactions = DB::table('transaksi')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $cart = collect(session('kasir_cart', []))
            ->map(function ($line) use ($barang, $jasa) {
                $item = ($line['tipe'] ?? null) === 'barang'
                    ? $barang->firstWhere('id', (int) ($line['id'] ?? 0))
                    : $jasa->firstWhere('id', (int) ($line['id'] ?? 0));

                if (! $item) {
                    return null;
                }

                return [
                    'tipe' => $line['tipe'],
                    'id' => (int) $item->id,
                    'nama' => $item->nama,
                    'harga' => (int) ($item->harga_jual ?? $item->tarif),
                    'gambar' => $line['tipe'] === 'barang' ? $item->gambar_url : asset('images/sparepart-placeholder.svg'),
                    'stok' => (int) ($item->stok ?? 0),
                    'jumlah' => (int) ($line['jumlah'] ?? 1),
                ];
            })
            ->filter()
            ->values();

        return view('kasir.pos', compact('barang', 'jasa', 'query', 'lastTransactions', 'cart', 'quickServices', 'paketServis', 'kategoriOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'metode_pembayaran' => ['required', 'in:tunai,qris'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
            'pelanggan' => ['nullable', 'string', 'max:120'],
            'nomor_plat' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\s.-]+$/'],
            'diskon_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'uang_dibayar' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
        ]);

        $items = $request->input('items', []);
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (! is_array($items) || empty($items)) {
            return back()->with('error', 'Keranjang transaksi masih kosong.')->with('kasir_cart', []);
        }

        $cart = [];
        foreach ($items as $item) {
            $type = $item['tipe'] ?? null;
            $id = (int) ($item['id'] ?? 0);
            $jumlah = (int) ($item['jumlah'] ?? 0);

            if (($type === 'barang' || $type === 'jasa') && $id > 0 && $jumlah > 0) {
                $cart[] = [
                    'tipe' => $type,
                    'id' => $id,
                    'jumlah' => $jumlah,
                ];
            }
        }

        if (empty($cart)) {
            return back()->with('error', 'Item yang akan dijual belum valid.')->with('kasir_cart', []);
        }

        $transaksiId = null;

        try {
            DB::beginTransaction();
            DB::statement('CALL sp_buat_transaksi(@new_id)');
            $trx = DB::selectOne('SELECT @new_id AS id');
            $transaksiId = $trx?->id ?? null;

            if (! $transaksiId) {
                throw new \RuntimeException('Transaksi gagal dibuat.');
            }

            foreach ($cart as $item) {
                $barangId = $item['tipe'] === 'barang' ? $item['id'] : null;
                $jasaId = $item['tipe'] === 'jasa' ? $item['id'] : null;

                DB::statement('CALL sp_tambah_item(?, ?, ?, ?)', [
                    $transaksiId,
                    $barangId,
                    $jasaId,
                    $item['jumlah'],
                ]);
            }

            DB::statement('CALL sp_selesai_transaksi(?)', [$transaksiId]);
            $subtotal = (int) DB::table('transaksi')->where('id', $transaksiId)->value('total');
            $discountPercent = (float) ($validated['diskon_persen'] ?? 0);
            $finalTotal = (int) round($subtotal * (100 - $discountPercent) / 100);
            $amountPaid = $validated['metode_pembayaran'] === 'tunai'
                ? (int) ($validated['uang_dibayar'] ?? 0)
                : $finalTotal;

            if ($amountPaid < $finalTotal) {
                throw new \RuntimeException('Uang pembayaran kurang dari total belanja!');
            }

            $change = $amountPaid - $finalTotal;

            DB::table('transaksi')->where('id', $transaksiId)->update([
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'pelanggan' => $validated['pelanggan'] ?? null,
                'nomor_plat' => Str::upper(trim($validated['nomor_plat'] ?? '')) ?: null,
                'diskon_persen' => $discountPercent,
                'total' => $finalTotal,
                'uang_dibayar' => $amountPaid,
                'kembalian' => $change,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($e instanceof QueryException && str_contains($e->getMessage(), 'Stok tidak cukup')) {
                session(['kasir_cart' => $cart]);

                return redirect()->route('kasir')->with('error', 'Stok tidak cukup untuk salah satu barang. Transaksi dibatalkan.')->withInput();
            }

            session(['kasir_cart' => $cart]);

            return back()->with('error', $e->getMessage())->withInput();
        }

        session()->forget('kasir_cart');

        $whatsapp = preg_replace('/\D+/', '', (string) ($validated['whatsapp'] ?? ''));
        if (str_starts_with($whatsapp, '0')) {
            $whatsapp = '62'.substr($whatsapp, 1);
        }

        return redirect()->route('transaksi.nota', $transaksiId)
            ->with('success', 'Transaksi berhasil disimpan.')
            ->with('whatsapp', $whatsapp);
    }

    public function nota(Request $request, $id)
    {
        $receipt = $this->receiptData($id);
        $pdfUrl = URL::temporarySignedRoute('transaksi.nota.pdf', now()->addDays(7), ['id' => $id]);
        $whatsapp = (string) $request->session()->get('whatsapp', '');

        return view('kasir.nota', [...$receipt, 'pdfUrl' => $pdfUrl, 'whatsapp' => $whatsapp]);
    }

    public function pdf($id)
    {
        $receipt = $this->receiptData($id);

        return Pdf::loadView('kasir.nota-pdf', $receipt)
            ->setPaper('a6', 'portrait')
            ->stream('nota-'.$id.'.pdf');
    }

    private function receiptData($id): array
    {
        $transaksi = DB::table('transaksi')->where('id', $id)->first();

        if (! $transaksi) {
            abort(404);
        }

        $details = DB::table('detail_transaksi as dt')
            ->leftJoin('barang as b', 'b.id', '=', 'dt.barang_id')
            ->leftJoin('jasa as j', 'j.id', '=', 'dt.jasa_id')
            ->select('dt.*', DB::raw('COALESCE(b.nama, j.nama) as nama_item'), DB::raw('COALESCE(b.harga_jual, j.tarif) as harga_satuan'))
            ->where('dt.transaksi_id', $id)
            ->get();

        $subtotal = (int) $details->sum(fn ($detail) => $detail->jumlah * $detail->harga_jual);
        $discountAmount = max(0, $subtotal - (int) $transaksi->total);
        $amountPaid = (int) ($transaksi->uang_dibayar ?: $transaksi->total);
        $change = (int) ($transaksi->kembalian ?? 0);

        return compact('transaksi', 'details', 'subtotal', 'discountAmount', 'amountPaid', 'change');
    }
}
