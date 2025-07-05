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
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.3;
        }

        .container {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 2mm;
        }

        .header {
            text-align: center;
            margin-bottom: 2mm;
            padding-bottom: 2mm;
            border-bottom: 1px dashed #000;
        }

        .shop-name {
            font-weight: bold;
            font-size: 14px;
            margin: 2mm 0;
        }

        .shop-address {
            font-size: 10px;
            margin-bottom: 2mm;
        }

        .invoice-title {
            font-weight: bold;
            margin: 2mm 0;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
        }

        .customer-info {
            margin-bottom: 3mm;
        }

        .info-label {
            font-weight: bold;
        }

        .vehicle-info {
            margin-bottom: 3mm;
            padding: 2mm;
            border: 1px dashed #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }

        table th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding: 1mm 0;
        }

        table td {
            padding: 1mm 0;
            border-bottom: 1px dashed #ccc;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .item-type {
            font-style: italic;
            color: #555;
            font-size: 11px;
        }

        .totals {
            margin-top: 3mm;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }

        .grand-total {
            font-weight: bold;
            border-top: 1px dashed #000;
            padding-top: 2mm;
            margin-top: 2mm;
        }

        .footer {
            margin-top: 5mm;
            text-align: center;
            font-size: 10px;
        }

        .signature {
            margin-top: 8mm;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 11px;
            }

            .no-print {
                display: none;
            }

            .container {
                width: 80mm;
                max-width: 80mm;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="shop-name">BRIE SEJAHTERA MOBIL</div>
            <div class="shop-address">Jl. Teuku Iskandar Km.6, Aceh Besar</div>
            <div class="shop-address">Telp: (021) 1234567</div>
            <div class="invoice-title">FAKTUR: #{{ $invoice->invoice_number }}</div>
        </div>

        <div class="invoice-info">
            <div>
                <div><span class="info-label">Tanggal:</span> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</div>
                <div><span class="info-label">Jth Tempo:</span> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</div>
            </div>
            <div>
                <div><span class="info-label">Status:</span> {{ strtoupper($invoice->status) }}</div>
            </div>
        </div>

        <div class="customer-info">
            <div><span class="info-label">Pelanggan:</span> {{ $invoice->customer->name }}</div>
            <div><span class="info-label">Telp:</span> {{ $invoice->customer->phone ?? '-' }}</div>
        </div>

        <div class="vehicle-info">
            <div><span class="info-label">No. Polisi:</span> {{ $invoice->vehicle->license_plate }}</div>
            <div><span class="info-label">Kendaraan:</span> {{ $invoice->vehicle->brand }} {{ $invoice->vehicle->model }} ({{ $invoice->vehicle->year }})</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="50%">Item</th>
                    <th width="20%" class="text-center">Qty</th>
                    <th width="30%" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <!-- Layanan -->
                @if ($invoice->services->isNotEmpty())
                    @foreach ($invoice->services as $service)
                    <tr>
                        <td>
                            {{ $service->name }}
                            <span class="item-type">[Layanan]</span>
                        </td>
                        <td class="text-center">-</td>
                        <td class="text-right">Rp {{ number_format($service->pivot->price, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @endif

                <!-- Barang -->
                @if ($invoice->items->isNotEmpty())
                    @foreach ($invoice->items as $item)
                    <tr>
                        <td>
                            {{ $item->product->name }}
                            <span class="item-type">[Suku Cadang]</span>
                        </td>
                        <td class="text-center">{{ $item->pivot->quantity }} {{ $item->unit }}</td>
                        <td class="text-right">Rp {{ number_format($item->pivot->quantity * $item->pivot->price, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @endif

                @if ($invoice->services->isEmpty() && $invoice->items->isEmpty())
                <tr>
                    <td colspan="3" style="text-align: center;">- TIDAK ADA ITEM -</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <div>Subtotal:</div>
                <div>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</div>
            </div>

            <div class="total-row">
                <div>Diskon:</div>
                <div>
                    @if ($invoice->discount_type === 'percentage')
                        {{ $invoice->discount_value }}%
                        (Rp {{ number_format(($invoice->subtotal * $invoice->discount_value) / 100, 0, ',', '.') }})
                    @else
                        Rp {{ number_format($invoice->discount_value, 0, ',', '.') }}
                    @endif
                </div>
            </div>

            <div class="total-row grand-total">
                <div>TOTAL:</div>
                <div>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</div>
            </div>

            <div class="total-row">
                <div>Dibayar:</div>
                <div>Rp {{ number_format($invoice->total_paid_amount, 0, ',', '.') }}</div>
            </div>

            @if ($invoice->balance_due > 0)
            <div class="total-row">
                <div>Sisa:</div>
                <div>Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}</div>
            </div>
            @endif

            @if ($invoice->overpayment > 0)
            <div class="total-row">
                <div>Kembali:</div>
                <div>Rp {{ number_format($invoice->overpayment, 0, ',', '.') }}</div>
            </div>
            @endif
        </div>

        <div class="footer">
            <div class="signature">
                <div>_________________________</div>
                <div>Hormat Kami</div>
            </div>
            <div style="margin-top: 3mm;">
                Terima kasih atas kunjungan Anda
            </div>
            <div style="font-size: 9px; margin-top: 2mm;">
                {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} - {{ $invoice->invoice_number }}
            </div>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 5mm;">
            <button onclick="window.print()" style="padding: 5px 10px; font-size: 12px;">Cetak Faktur</button>
        </div>
    </div>
</body>

</html>
