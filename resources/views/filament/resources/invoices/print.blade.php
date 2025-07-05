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
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .invoice-details, .customer-details {
            margin-bottom: 20px;
        }
        .invoice-details p, .customer-details p {
            margin: 5px 0;
            font-size: 14px;
        }
        .invoice-details strong, .customer-details strong {
            display: inline-block;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 14px;
        }
        table th {
            background-color: #f9f9f9;
        }
        .totals {
            text-align: right;
            margin-top: 20px;
        }
        .totals p {
            margin: 5px 0;
            font-size: 16px;
        }
        .totals strong {
            display: inline-block;
            width: 150px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #777;
        }
        @media print {
            body {
                margin: 0;
                padding: 10px; /* Adjust padding for printing */
                font-size: 12pt; /* Ensure consistent font size for printing */
            }
            .container {
                width: 100%;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }
            .header h1 {
                font-size: 20pt;
            }
            .invoice-details p, .customer-details p, table th, table td {
                font-size: 10pt;
            }
             .totals p {
                font-size: 12pt;
            }
            .no-print {
                display: none;
            }
            /* Ensure table borders are visible when printing */
            table, table th, table td {
                border: 1px solid #ccc !important; /* Use !important to override other styles */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FAKTUR PENJUALAN</h1>
            <p>Bengkel XYZ</p>
            <p>Jl. Raya Contoh No. 123, Kota Contoh</p>
            <p>Telepon: (021) 1234567</p>
        </div>

        <div class="invoice-details">
            <p><strong>No. Faktur:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Tanggal Faktur:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</p>
            <p><strong>Jatuh Tempo:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</p>
            <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
        </div>

        <div class="customer-details">
            <h3>Pelanggan:</h3>
            <p><strong>Nama:</strong> {{ $invoice->customer->name }}</p>
            <p><strong>No. Polisi:</strong> {{ $invoice->vehicle->license_plate }}</p>
            <p><strong>Kendaraan:</strong> {{ $invoice->vehicle->brand }} {{ $invoice->vehicle->model }} ({{ $invoice->vehicle->year }})</p>
            <p><strong>Telepon:</strong> {{ $invoice->customer->phone ?? '-' }}</p>
            <p><strong>Alamat:</strong> {{ $invoice->customer->address ?? '-' }}</p>
        </div>

        <h3>Detail Jasa / Layanan:</h3>
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
            <p>Tidak ada jasa.</p>
        @endif

        <h3>Detail Barang / Suku Cadang:</h3>
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
            <p>Tidak ada barang.</p>
        @endif

        <div class="totals">
            <p><strong>Subtotal:</strong> Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</p>
            <p>
                <strong>Diskon:</strong>
                @if($invoice->discount_type === 'percentage')
                    {{ $invoice->discount_value }}%
                    (Rp {{ number_format(($invoice->subtotal * $invoice->discount_value / 100), 0, ',', '.') }})
                @else
                    Rp {{ number_format($invoice->discount_value, 0, ',', '.') }}
                @endif
            </p>
            <p><strong>Total Akhir:</strong> Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
            <p><strong>Total Dibayar:</strong> Rp {{ number_format($invoice->total_paid_amount, 0, ',', '.') }}</p>
            @if($invoice->balance_due > 0)
                <p><strong>Sisa Tagihan:</strong> Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}</p>
            @endif
            @if($invoice->overpayment > 0)
                 <p><strong>Kembalian:</strong> Rp {{ number_format($invoice->overpayment, 0, ',', '.') }}</p>
            @endif
        </div>

        @if($invoice->terms)
        <div class="terms">
            <h3>Syarat & Ketentuan:</h3>
            <p>{{ $invoice->terms }}</p>
        </div>
        @endif

        <div class="footer">
            <p>Terima kasih atas kepercayaan Anda.</p>
            <p>Hormat Kami, Bengkel XYZ</p>
            <br>
            <br>
            <p>(_________________________)</p>
            <p>Penerima</p>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()">Cetak Faktur</button>
            <button onclick="window.close()">Tutup</button>
        </div>
    </div>
</body>
</html>
