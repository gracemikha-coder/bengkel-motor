<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota transaksi #{{ $transaksi->id }}</title>
    <style>
        @page { size: A6 portrait; margin: 8mm; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { margin: 0; font-size: 17px; text-align: center; }
        .subtitle { margin: 3px 0 14px; color: #64748b; text-align: center; }
        .meta { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px 0; vertical-align: top; }
        .line { border-top: 1px dashed #94a3b8; }
        .right { text-align: right; }
        .total { border-top: 1px solid #334155; font-size: 13px; font-weight: bold; }
        .footer { margin-top: 16px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <h1>Bengkel Motor</h1>
    <p class="subtitle">Nota transaksi</p>
    <div class="meta">No. {{ $transaksi->id }}<br>{{ \Carbon\Carbon::parse($transaksi->tanggal)->translatedFormat('d M Y H:i') }}<br>Pelanggan: {{ $transaksi->pelanggan ?: '-' }}<br>Kendaraan: {{ $transaksi->nomor_plat ?: '-' }}<br>Pembayaran: {{ strtoupper($transaksi->metode_pembayaran ?? 'tunai') }}</div>
    <table>
        @foreach($details as $detail)
            <tr class="line">
                <td>{{ $detail->nama_item }}<br>{{ $detail->jumlah }} x {{ formatRupiah($detail->harga_jual) }}</td>
                <td class="right">{{ formatRupiah($detail->jumlah * $detail->harga_jual) }}</td>
            </tr>
        @endforeach
        <tr><td>Subtotal</td><td class="right">{{ formatRupiah($subtotal) }}</td></tr>
        @if($discountAmount > 0)
            <tr><td>Diskon ({{ number_format((float) $transaksi->diskon_persen, 2) }}%)</td><td class="right">-{{ formatRupiah($discountAmount) }}</td></tr>
        @endif
        <tr>
            <td class="total">Total</td>
            <td class="right total">{{ formatRupiah($transaksi->total) }}</td>
        </tr>
        @if(($transaksi->metode_pembayaran ?? 'tunai') === 'tunai')
            <tr><td>Dibayar</td><td class="right">{{ formatRupiah($amountPaid) }}</td></tr>
            <tr><td>Kembalian</td><td class="right">{{ formatRupiah($change) }}</td></tr>
        @endif
    </table>
    <p class="footer">Terima kasih telah berbelanja.</p>
</body>
</html>