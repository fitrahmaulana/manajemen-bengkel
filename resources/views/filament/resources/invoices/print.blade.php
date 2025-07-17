<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur #{{ $invoice->invoice_number }}</title>
    <style>
        /* CSS ini dirancang khusus untuk printer dot-matrix */
        @page {
            size: auto;
            margin: 1cm 0.5cm; /* Margin atas-bawah dan kiri-kanan */
        }

        body {
            /* WAJIB: Gunakan font monospace agar semua karakter sama lebar */
            font-family: 'Courier New', Courier, monospace;
            font-size: 10pt; /* Ukuran standar untuk keterbacaan di kertas kontinu */
            color: #000;
            background-color: #fff;
            line-height: 1.3; /* Sedikit spasi antar baris */
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 80ch; /* Batasi lebar sekitar 80 karakter */
            margin: 0 auto;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2ch;
        }

        .info-col p {
            margin: 0;
        }

        /* Tabel untuk item */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1ch;
            margin-bottom: 1ch;
        }

        .items-table th, .items-table td {
            padding: 2px 4px;
            text-align: left;
        }

        .items-table thead tr {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .items-table tfoot tr {
            border-top: 1px solid #000;
        }

        /* --- PERUBAHAN CSS UNTUK TOTALS --- */
        .totals-wrapper {
            width: 50%;
            margin-left: 50%;
            margin-top: 2ch;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 1px 0; /* Padding vertikal minimal, tanpa padding horizontal */
        }

        /* Mengatur lebar kolom agar rapi */
        .totals-table .label { width: auto; } /* Kolom label, lebar otomatis */
        .totals-table .currency { width: 3ch; } /* Kolom 'Rp', lebar tetap */
        .totals-table .amount { width: 13ch; text-align: right; } /* Kolom angka, rata kanan */

        .line-top {
            border-top: 1px solid #000;
            padding-top: 4px !important;
        }
        /* --- AKHIR PERUBAHAN CSS --- */


        /* Signatures section */
        .signatures {
            margin-top: 4ch;
            display: grid;
            grid-template-columns: 1fr 1fr;
            text-align: center;
        }

        .signatures .signature-area {
            margin-top: 8ch; /* Jarak untuk tanda tangan */
        }

        /* Menghilangkan tombol saat print */
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
            }
            .container {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="text-center">
            <h3 style="margin:0; font-size: 12pt;">BRIE SEJAHTERA MOBIL</h3>
            <p style="margin:0;">Jl. Teuku Iskandar No.Km.6, Meunasah Intan, Krueng Barona Jaya</p>
            <p style="margin:0;">Kabupaten Aceh Besar, Aceh 24411. Telp: (021) 1234567</p>
        </div>

        <pre style="margin: 1ch 0; text-align: center; font-family: 'Courier New', monospace;">======================================================================</pre>

        <h3 class="text-center font-bold" style="margin: 0;">FAKTUR PENJUALAN</h3>

        <div class="info-grid" style="margin-top: 2ch;">
            <div class="info-col">
                <p>No. Faktur  : {{ $invoice->invoice_number }}</p>
                <p>Tanggal     : {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</p>
                <p>Jatuh Tempo : {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</p>
            </div>
            <div class="info-col">
                <p>Pelanggan   : {{ $invoice->customer->name }}</p>
                <p>No. Polisi  : {{ $invoice->vehicle->license_plate }}</p>
                <p>Kendaraan   : {{ $invoice->vehicle->brand }} {{ $invoice->vehicle->model }}</p>
            </div>
        </div>

        <pre style="margin: 1ch 0; text-align: center; font-family: 'Courier New', monospace;">----------------------------------------------------------------------</pre>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi Layanan / Barang</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->services as $service)
                <tr>
                    <td>{{ $service->name }}</td>
                    <td class="text-center">1</td>
                    <td class="text-right">{{ number_format($service->pivot->price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($service->pivot->price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                @foreach ($invoice->items as $item)
                <tr>
                    <td>
                        @if ($item->product)
                            {{ $item->product->name }}
                        @endif
                        {{ $item->name == 'Eceran' ? '' : $item->name }}
                        @if ($item->pivot->description)
                            <br><small class="text-muted">{{ $item->pivot->description }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->pivot->quantity }} {{ $item->unit }}</td>
                    <td class="text-center">{{ $item->pivot->quantity }}</td>
                    <td class="text-right">{{ number_format($item->pivot->price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->pivot->quantity * $item->pivot->price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td></td><td></td><td></td><td></td></tr>
            </tfoot>
        </table>

        <div class="totals-wrapper">
            <table class="totals-table">
                <tbody>
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="currency">Rp</td>
                        <td class="amount">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Diskon</td>
                        <td class="currency">Rp</td>
                        <td class="amount">
                            @php
                                $discountAmount = ($invoice->discount_type === 'percentage') ? ($invoice->subtotal * $invoice->discount_value) / 100 : $invoice->discount_value;
                                echo number_format($discountAmount, 0, ',', '.');
                            @endphp
                        </td>
                    </tr>
                    <tr class="font-bold">
                        <td class="label line-top">TOTAL AKHIR</td>
                        <td class="currency line-top">Rp</td>
                        <td class="amount line-top">{{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Dibayar</td>
                        <td class="currency">Rp</td>
                        <td class="amount">{{ number_format($invoice->total_paid_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if ($invoice->balance_due > 0)
                    <tr class="font-bold">
                        <td class="label">Sisa Tagihan</td>
                        <td class="currency">Rp</td>
                        <td class="amount">{{ number_format($invoice->balance_due, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if ($invoice->overpayment > 0)
                    <tr class="font-bold">
                        <td class="label">Kembalian</td>
                        <td class="currency">Rp</td>
                        <td class="amount">{{ number_format($invoice->overpayment, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($invoice->terms)
        <div style="margin-top: 3ch; border-top: 1px solid #000; padding-top: 1ch;">
            <p style="margin: 0;" class="font-bold">Syarat & Ketentuan:</p>
            <p style="margin: 0;">{{ $invoice->terms }}</p>
        </div>
        @endif

        <div class="signatures">
            <div class="signature-area">
                <p>Penerima,</p>
                <p style="margin-top: 8ch;">(_______________)</p>
                <p>{{ $invoice->customer->name }}</p>
            </div>
            <div class="signature-area">
                <p>Hormat Kami,</p>
                <p style="margin-top: 8ch;">(_______________)</p>
                <p>Brie Sejahtera Mobil</p>
            </div>
        </div>

        <p class="text-center" style="margin-top: 3ch; font-size: 9pt;">--- Terima kasih atas kepercayaan Anda.---</p>

    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 8px 15px; font-size: 14px; cursor: pointer;">Cetak Faktur</button>
        <button onclick="window.close()" style="padding: 8px 15px; font-size: 14px; cursor: pointer;">Tutup</button>
    </div>
</body>
</html>
