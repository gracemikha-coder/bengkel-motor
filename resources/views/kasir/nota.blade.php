<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Nota transaksi #{{ $transaksi->id }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 p-4">
        @php
            $messageLines = [
                'Nota Bengkel Motor #'.$transaksi->id,
                'Tanggal: '.\Carbon\Carbon::parse($transaksi->tanggal)->translatedFormat('d M Y H:i'),
            ];
            if ($transaksi->pelanggan) {
                $messageLines[] = 'Pelanggan: '.$transaksi->pelanggan;
            }
            if ($transaksi->nomor_plat) {
                $messageLines[] = 'No. plat: '.$transaksi->nomor_plat;
            }
            foreach ($details as $detail) {
                $messageLines[] = $detail->nama_item.' - '.$detail->jumlah.' x '.formatRupiah($detail->harga_jual);
            }
            $messageLines[] = 'Subtotal: '.formatRupiah($subtotal);
            if ($discountAmount > 0) {
                $messageLines[] = 'Diskon: '.formatRupiah($discountAmount);
            }
            $messageLines[] = 'Total pembayaran: '.formatRupiah($transaksi->total);
            if (($transaksi->metode_pembayaran ?? 'tunai') === 'tunai') {
                $messageLines[] = 'Dibayar: '.formatRupiah($amountPaid);
                $messageLines[] = 'Kembalian: '.formatRupiah($change);
            }
            $messageLines[] = 'Unduh nota PDF: '.$pdfUrl;
            $whatsappUrl = ($whatsapp !== '' ? 'https://wa.me/'.$whatsapp : 'https://wa.me/').'?text='.rawurlencode(implode("\n", $messageLines));
        @endphp
        <div class="print-sheet mx-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-lg">
            <div class="mb-4 text-center">
                <h1 class="text-xl font-bold text-slate-800">Bengkel Motor</h1>
                <p class="text-sm text-slate-500">Nota transaksi</p>
            </div>

            <div class="mb-4 flex items-center justify-between text-sm text-slate-600">
                <span>No. {{ $transaksi->id }}</span>
                <span>{{ \Carbon\Carbon::parse($transaksi->tanggal)->translatedFormat('d M Y H:i') }}</span>
            </div>
            @if($transaksi->pelanggan || $transaksi->nomor_plat)
                <div class="mb-3 text-sm text-slate-600">
                    @if($transaksi->pelanggan)<p>Pelanggan: {{ $transaksi->pelanggan }}</p>@endif
                    @if($transaksi->nomor_plat)<p>Kendaraan: {{ $transaksi->nomor_plat }}</p>@endif
                </div>
            @endif
            <p class="mb-3 text-sm text-slate-600">Pembayaran: {{ strtoupper($transaksi->metode_pembayaran ?? 'tunai') }}</p>

            <div class="space-y-2 border-y border-slate-200 py-3">
                @foreach($details as $detail)
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-slate-800">{{ $detail->nama_item }}</p>
                            <p class="text-xs text-slate-500">{{ $detail->jumlah }} x {{ formatRupiah($detail->harga_jual) }}</p>
                        </div>
                        <p class="font-medium text-slate-800">{{ formatRupiah($detail->jumlah * $detail->harga_jual) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 space-y-2 text-sm text-slate-700">
                <div class="flex items-center justify-between"><span>Subtotal</span><span>{{ formatRupiah($subtotal) }}</span></div>
                @if($discountAmount > 0)
                    <div class="flex items-center justify-between"><span>Diskon ({{ number_format((float) $transaksi->diskon_persen, 2) }}%)</span><span>-{{ formatRupiah($discountAmount) }}</span></div>
                @endif
                <div class="flex items-center justify-between">
                    <span>Total</span>
                    <span class="text-xl font-bold text-slate-800">{{ formatRupiah($transaksi->total) }}</span>
                </div>
                @if(($transaksi->metode_pembayaran ?? 'tunai') === 'tunai')
                    <div class="flex items-center justify-between"><span>Dibayar</span><span>{{ formatRupiah($amountPaid) }}</span></div>
                    <div class="flex items-center justify-between font-semibold text-emerald-700"><span>Kembalian</span><span>{{ formatRupiah($change) }}</span></div>
                @endif
            </div>

            <div class="no-print mt-6 flex justify-end gap-3">
                <a href="{{ route('kasir') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">Kembali</a>
                <a href="{{ $pdfUrl }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">Unduh PDF</a>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-medium text-white">Kirim struk ke WA</a>
                <button type="button" onclick="window.print()" class="rounded-xl bg-blue-800 px-4 py-2 text-sm font-medium text-white">Cetak nota</button>
            </div>
        </div>
    </body>
</html>
