<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #333;
            font-size: 12px; /* Base font size */
        }
        .container {
            width: 90%; /* Slightly wider for better content fit */
            max-width: 800px; /* Max width to prevent it from becoming too wide on large screens */
            margin: 15px auto; /* Reduced margin */
            padding: 15px;
            border: 1px solid #eee;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 20px; /* Reduced H1 size */
            color: #333;
        }
        .header p {
            margin: 3px 0;
            font-size: 12px;
        }
        .invoice-header-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .invoice-details, .customer-details {
            width: 48%; /* Assign width for side-by-side layout */
        }
        .invoice-details p, .customer-details p {
            margin: 4px 0; /* Reduced margin */
            font-size: 12px; /* Consistent font size */
        }
        .invoice-details strong, .customer-details strong {
            display: inline-block;
            width: 120px; /* Adjusted width */
            font-weight: bold;
        }
        .customer-details h3 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 14px;
        }
        h3 { /* General H3 styling for sections */
            font-size: 14px;
            margin-top: 15px;
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px; /* Reduced padding */
            text-align: left;
            font-size: 12px; /* Consistent font size */
        }
        table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .totals {
            width: 60%; /* Adjust width as needed */
            max-width: 350px; /* Max width for totals block */
            margin-left: auto; /* Aligns the block to the right */
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .totals p {
            margin: 6px 0; /* Adjusted margin */
            font-size: 13px; /* Slightly larger for emphasis */
            display: flex;
            justify-content: space-between;
        }
        .totals p strong {
            margin-right: 10px;
            font-weight: bold;
            flex-basis: 50%; /* Give label a basis */
            text-align: left;
        }
        .totals p span {
            text-align: right;
            flex-basis: 50%; /* Give value a basis */
        }
        .footer-signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 30px; /* Increased margin for separation */
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
        }
        .signature-area {
            text-align: center;
            width: 45%; /* Assign width to each signature area */
        }
        .signature-area p {
            margin: 5px 0;
        }
        .signature-line {
            margin-top: 50px; /* Space for signature */
            border-bottom: 1px solid #333;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .terms {
            margin-top: 20px;
            font-size: 11px;
            color: #555;
        }
        .terms h3 {
            margin-bottom: 5px;
            font-size: 13px;
            border-bottom: none; /* No border for terms H3 */
        }
        .thank-you {
            text-align:center;
            font-size: 12px;
            color: #777;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        @media print {
            body {
                margin: 0;
                padding: 5mm; /* Standard print margin */
                font-size: 10pt; /* Base print font size */
                background-color: #fff !important; /* Ensure background is white for printing */
                -webkit-print-color-adjust: exact; /* For Chrome/Safari */
                color-adjust: exact; /* Standard */
            }
            .container {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 0;
                border: none !important;
                box-shadow: none !important;
            }
            .header h1 {
                font-size: 16pt;
            }
            .header p, .invoice-details p, .customer-details p, table th, table td, .terms, .footer-signatures p, .thank-you p {
                font-size: 9pt;
            }
            h3 {
                font-size: 11pt;
            }
            table th, table td {
                padding: 4px; /* Further reduce padding for print */
            }
            .totals p {
                font-size: 10pt;
            }
            .totals {
                width: 50%; /* Can be adjusted for print if needed */
                max-width: none;
            }
            .footer-signatures {
                margin-top: 15mm; /* More space before signatures in print */
                page-break-inside: avoid; /* Avoid breaking signatures across pages */
            }
            .signature-line {
                margin-top: 10mm; /* More space for actual signature */
                border-bottom: 1px solid #000 !important; /* Ensure line is visible */
            }
            .no-print {
                display: none !important;
            }
            table, table th, table td {
                border: 1px solid #aaa !important; /* Lighter border for print */
            }
            .invoice-header-details, .totals, .footer-signatures, .thank-you {
                 border-top: 1px solid #aaa !important;
            }
             h3 {
                border-bottom: 1px solid #aaa !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FAKTUR PENJUALAN</h1>
            <!-- You can make these dynamic if needed -->
            <p>Bengkel XYZ</p>
            <p>Jl. Raya Contoh No. 123, Kota Contoh</p>
            <p>Telepon: (021) 1234567</p>
        </div>

        <div class="invoice-header-details">
            <div class="invoice-details">
                <p><strong>No. Faktur:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Tanggal Faktur:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</p>
                <p><strong>Jatuh Tempo:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</p>
                <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</p>
            </div>

            <div class="customer-details">
                <h3>Pelanggan</h3>
                <p><strong>Nama:</strong> {{ $invoice->customer->name }}</p>
                <p><strong>No. Polisi:</strong> {{ $invoice->vehicle->license_plate }}</p>
                <p><strong>Kendaraan:</strong> {{ $invoice->vehicle->brand }} {{ $invoice->vehicle->model }} ({{ $invoice->vehicle->year }})</p>
                <p><strong>Telepon:</strong> {{ $invoice->customer->phone ?? '-' }}</p>
                <p><strong>Alamat:</strong> {{ $invoice->customer->address ?? '-' }}</p>
            </div>
        </div>


        <h3>Detail Jasa / Layanan</h3>
        @if($invoice->services->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Nama Jasa</th>
                        <th>Deskripsi</th>
                        <th style="text-align: right;">Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>{{ $service->pivot->description ?? '-' }}</td>
                            <td style="text-align: right;">Rp {{ number_format($service->pivot->price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: #777;"><em>Tidak ada jasa yang ditambahkan.</em></p>
        @endif

        <h3>Detail Barang / Suku Cadang</h3>
        @if($invoice->items->isNotEmpty())
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
                    @foreach($invoice->items as $item)
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
            <p style="text-align: center; color: #777;"><em>Tidak ada barang yang ditambahkan.</em></p>
        @endif

        <div class="totals">
            <p><strong>Subtotal:</strong> <span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></p>
            <p>
                <strong>Diskon:</strong>
                <span>
                    @if($invoice->discount_type === 'percentage')
                        {{ $invoice->discount_value }}%
                        (Rp {{ number_format(($invoice->subtotal * $invoice->discount_value / 100), 0, ',', '.') }})
                    @else
                        Rp {{ number_format($invoice->discount_value, 0, ',', '.') }}
                    @endif
                </span>
            </p>
            <p><strong>Total Akhir:</strong> <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></p>
            <hr style="border: none; border-top: 1px dashed #ccc; margin: 5px 0;">
            <p><strong>Total Dibayar:</strong> <span>Rp {{ number_format($invoice->total_paid_amount, 0, ',', '.') }}</span></p>
            @if($invoice->balance_due > 0)
                <p><strong>Sisa Tagihan:</strong> <span style="color: red; font-weight: bold;">Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}</span></p>
            @endif
            @if($invoice->overpayment > 0)
                 <p><strong>Kembalian:</strong> <span style="color: green; font-weight: bold;">Rp {{ number_format($invoice->overpayment, 0, ',', '.') }}</span></p>
            @endif
        </div>

        @if($invoice->terms)
        <div class="terms">
            <h3>Syarat & Ketentuan:</h3>
            <p>{{ nl2br(e($invoice->terms)) }}</p>
        </div>
        @endif

        <div class="footer-signatures">
            <div class="signature-area">
                <p>Hormat Kami,</p>
                <p>Bengkel XYZ</p>
                <div class="signature-line"></div>
                <p>(Pemilik Toko / Kasir)</p>
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
            <button onclick="window.print()" style="padding: 8px 15px; font-size: 14px; cursor: pointer; background-color: #4CAF50; color: white; border: none; border-radius: 4px; margin-right: 10px;">Cetak Faktur</button>
            <button onclick="window.close()" style="padding: 8px 15px; font-size: 14px; cursor: pointer; background-color: #f44336; color: white; border: none; border-radius: 4px;">Tutup</button>
        </div>
    </div>
</body>
</html>
