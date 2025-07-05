<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style type="text/css">
        @page {
            size: auto;
            margin: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #333;
            font-size: 12px;
        }

        .container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        /* --- Perubahan Header --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f2f2f2;
        }

        .header-left .logo {
            max-width: 150px; /* Atur ukuran logo */
            height: auto;
        }

        .header-right {
            text-align: right;
        }

        .header-right h2 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
            font-weight: bold;
        }

        .header-right p {
            margin: 3px 0;
            font-size: 11px;
            color: #555;
        }
        /* --- Akhir Perubahan Header --- */

        .invoice-header-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .invoice-details,
        .customer-details {
            width: 48%;
        }

        .invoice-details p,
        .customer-details p {
            margin: 5px 0;
            font-size: 12px;
        }

        .invoice-details strong,
        .customer-details strong {
            display: inline-block;
            width: 110px;
            font-weight: bold;
            color: #333;
        }

        .customer-details h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        h3.section-title {
            font-size: 14px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .totals {
            width: 60%;
            max-width: 350px;
            margin-left: auto;
            margin-top: 20px;
        }

        .totals p {
            margin: 7px 0;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
        }

        .totals p strong {
            font-weight: bold;
            text-align: left;
        }

        .totals p span {
            text-align: right;
        }

        .totals .grand-total {
            font-weight: bold;
            font-size: 14px;
            color: #000;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }

        .footer-signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
        }

        .signature-area {
            text-align: center;
            width: 45%;
        }

        .signature-area p {
            margin: 5px 0;
        }

        .signature-line {
            margin-top: 60px;
            border-bottom: 1px solid #333;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .terms {
            margin-top: 30px;
            font-size: 11px;
            color: #555;
        }

        .terms h3 {
            margin-bottom: 5px;
            font-size: 13px;
            border: none;
        }

        .thank-you {
            text-align: center;
            font-size: 12px;
            color: #777;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        @media print {
            body {
                margin: 5mm;
                font-size: 10pt;
                background-color: #fff !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .container {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 0;
                border: none !important;
                box-shadow: none !important;
            }

            .header {
                border-bottom: 2px solid #ccc !important;
            }

            .header-right h2 {
                font-size: 20pt;
            }

            h3.section-title, .customer-details h3 {
                border-bottom: 1px solid #ccc !important;
            }

            table, table th, table td {
                border: 1px solid #aaa !important;
            }

            .totals {
                width: 50%;
            }

            .footer-signatures {
                page-break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <img src="https://via.placeholder.com/150x60.png?text=Logo+Anda" alt="Logo Perusahaan" class="logo">
                </div>
            <div class="header-right">
                <h2>FAKTUR PENJUALAN</h2>
                <p><strong>Brie Sejahtera Mobil</strong></p>
                <p>Jl. Teuku Iskandar No.Km.6, Meunasah Intan</p>
                <p>Kec. Krueng Barona Jaya, Aceh Besar, Aceh 24411</p>
                <p>Telepon: (021) 1234567</p>
            </div>
        </div>

        <div class="invoice-header-details">
            <div class="invoice-details">
                <p><strong>No. Faktur:</strong> <span>{{ $invoice->invoice_number }}</span></p>
                <p><strong>Tanggal Faktur:</strong> <span>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</span></p>
                <p><strong>Jatuh Tempo:</strong> <span>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</span></p>
                <p><strong>Status:</strong> <span>{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span></p>
            </div>

            <div class="customer-details">
                <h3>Pelanggan</h3>
                <p><strong>Nama:</strong> <span>{{ $invoice->customer->name }}</span></p>
                <p><strong>No. Polisi:</strong> <span>{{ $invoice->vehicle->license_plate }}</span></p>
                <p><strong>Kendaraan:</strong> <span>{{ $invoice->vehicle->brand }} {{ $invoice->vehicle->model }} ({{ $invoice->vehicle->year }})</span></p>
                <p><strong>Telepon:</strong> <span>{{ $invoice->customer->phone ?? '-' }}</span></p>
                <p><strong>Alamat:</strong> <span>{{ $invoice->customer->address ?? '-' }}</span></p>
            </div>
        </div>

        <h3 class="section-title">Detail Jasa / Layanan</h3>
        @if ($invoice->services->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Nama Jasa</th>
                        <th>Deskripsi</th>
                        <th style="text-align: right;">Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>{{ $service->pivot->description ?? '-' }}</td>
                            <td style="text-align: right;">Rp {{ number_format($service->pivot->price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: #777; margin: 20px 0;"><em>Tidak ada jasa yang ditambahkan.</em></p>
        @endif

        <h3 class="section-title">Detail Barang / Suku Cadang</h3>
        @if ($invoice->items->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Deskripsi</th>
                        <th style="text-align: center;">Kuantitas</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->product->name }} {{ $item->name }}</td>
                            <td>{{ $item->pivot->description ?? '-' }}</td>
                            <td style="text-align: center;">{{ $item->pivot->quantity }} {{ $item->unit }}</td>
                            <td style="text-align: right;">Rp {{ number_format($item->pivot->price, 0, ',', '.') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($item->pivot->quantity * $item->pivot->price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: #777; margin: 20px 0;"><em>Tidak ada barang yang ditambahkan.</em></p>
        @endif

        <div class="totals">
            <p><strong>Subtotal:</strong> <span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></p>
            <p>
                <strong>Diskon:</strong>
                <span>
                    @if ($invoice->discount_type === 'percentage')
                        {{ $invoice->discount_value }}% (Rp {{ number_format(($invoice->subtotal * $invoice->discount_value) / 100, 0, ',', '.') }})
                    @else
                        Rp {{ number_format($invoice->discount_value, 0, ',', '.') }}
                    @endif
                </span>
            </p>
            <p class="grand-total"><strong>Total Akhir:</strong> <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></p>
            <p style="margin-top: 15px;"><strong>Total Dibayar:</strong> <span>Rp {{ number_format($invoice->total_paid_amount, 0, ',', '.') }}</span></p>

            @if ($invoice->balance_due > 0)
                <p><strong>Sisa Tagihan:</strong> <span style="color: red; font-weight: bold;">Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}</span></p>
            @endif
            @if ($invoice->overpayment > 0)
                <p><strong>Kembalian:</strong> <span style="color: green; font-weight: bold;">Rp {{ number_format($invoice->overpayment, 0, ',', '.') }}</span></p>
            @endif
        </div>

        @if ($invoice->terms)
            <div class="terms">
                <h3>Syarat & Ketentuan:</h3>
                <p>{!! nl2br(e($invoice->terms)) !!}</p>
            </div>
        @endif

        <div class="footer-signatures">
            <div class="signature-area">
                <p>Hormat Kami,</p>
                <div class="signature-line"></div>
                <p>(Brie Sejahtera Mobil / Kasir)</p>
            </div>
            <div class="signature-area">
                <p>Penerima,</p>
                <div class="signature-line"></div>
                <p>({{ $invoice->customer->name }})</p>
            </div>
        </div>

        <div class="thank-you">
            <p>Terima kasih atas kepercayaan Anda.</p>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 20px; padding-top:15px; border-top: 1px solid #eee;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; background-color: #4CAF50; color: white; border: none; border-radius: 4px; margin-right: 10px;">Cetak Faktur</button>
            <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; background-color: #f44336; color: white; border: none; border-radius: 4px;">Tutup</button>
        </div>
    </div>
</body>

</html>

