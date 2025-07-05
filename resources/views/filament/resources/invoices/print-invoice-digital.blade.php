<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #2d3748;
        }

        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .invoice-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #ffeaa7);
            background-size: 300% 100%;
            animation: gradientMove 8s ease infinite;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .header-section {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.1; }
        }

        .header-content {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 30px;
            position: relative;
            z-index: 2;
        }

        .logo-section {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .logo-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: logoShine 3s ease-in-out infinite;
        }

        @keyframes logoShine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
            100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        }

        .company-info {
            color: white;
            text-align: center;
        }

        .company-name {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .company-address {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .invoice-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
            position: relative;
            overflow: hidden;
        }

        .invoice-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: badgeSlide 2s ease-in-out infinite;
        }

        @keyframes badgeSlide {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .invoice-number {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .invoice-type {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }

        .content-section {
            padding: 40px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .detail-card {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid;
            position: relative;
            overflow: hidden;
        }

        .detail-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .invoice-details {
            border-left-color: #667eea;
        }

        .customer-details {
            border-left-color: #ff6b6b;
        }

        .detail-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .detail-label {
            font-weight: 500;
            color: #4a5568;
        }

        .detail-value {
            font-weight: 600;
            color: #2d3748;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: linear-gradient(135deg, #ffeaa7, #fdcb6e); color: #2d3748; }
        .status-paid { background: linear-gradient(135deg, #55efc4, #00b894); color: white; }
        .status-overdue { background: linear-gradient(135deg, #fd79a8, #e84393); color: white; }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            position: relative;
        }

        .section-title::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .modern-table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .modern-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: white;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modern-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .modern-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
            font-style: italic;
        }

        .totals-section {
            background: linear-gradient(135deg, #2d3748, #4a5568);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            max-width: 400px;
            margin-left: auto;
            position: relative;
            overflow: hidden;
        }

        .totals-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 6s ease-in-out infinite;
        }

        .total-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }

        .total-label {
            font-weight: 500;
            font-size: 14px;
        }

        .total-value {
            font-weight: 600;
            font-size: 16px;
        }

        .total-final {
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            padding-top: 15px;
            margin-top: 15px;
            font-size: 18px;
            font-weight: 700;
        }

        .balance-due {
            color: #ff6b6b !important;
            font-weight: 700;
        }

        .overpayment {
            color: #00b894 !important;
            font-weight: 700;
        }

        .terms-section {
            background: linear-gradient(135deg, #fff7ed, #fed7aa);
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            border-left: 5px solid #f97316;
        }

        .terms-title {
            font-size: 16px;
            font-weight: 600;
            color: #9a3412;
            margin-bottom: 10px;
        }

        .terms-content {
            font-size: 14px;
            line-height: 1.6;
            color: #7c2d12;
        }

        .signatures-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin: 50px 0;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .signature-box {
            text-align: center;
        }

        .signature-label {
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 50px;
        }

        .signature-line {
            border-bottom: 2px solid #2d3748;
            margin-bottom: 10px;
        }

        .signature-name {
            font-weight: 600;
            color: #2d3748;
        }

        .footer-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .thank-you-message {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .thank-you-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        .print-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: linear-gradient(135deg, #00b894, #55efc4);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 184, 148, 0.3);
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 184, 148, 0.4);
        }

        .btn-close {
            background: linear-gradient(135deg, #fd79a8, #e84393);
            color: white;
            box-shadow: 0 5px 15px rgba(253, 121, 168, 0.3);
        }

        .btn-close:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(253, 121, 168, 0.4);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                border-radius: 0;
                max-width: none;
            }

            .print-buttons {
                display: none;
            }

            .invoice-container::before {
                display: none;
            }

            .header-section::before,
            .logo-section::before,
            .invoice-badge::before,
            .totals-section::before {
                display: none;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">

        <div class="header-section">
            <div class="header-content">
                <div class="logo-section">
                    🚗
                </div>
                <div class="company-info">
                    <div class="company-name">Brie Sejahtera Mobil</div>
                    <div class="company-address">
                        Jl. Teuku Iskandar No.Km.6, Meunasah Intan<br>
                        Kec. Krueng Barona Jaya, Aceh Besar<br>
                        📞 (021) 1234567
                    </div>
                </div>
                <div class="invoice-badge">
                    <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                    <div class="invoice-type">Faktur Penjualan</div>
                </div>
            </div>
        </div>

        <div class="content-section">
            <div class="details-grid">
                <div class="detail-card invoice-details">
                    <div class="detail-title">📋 Detail Faktur</div>
                    <div class="detail-item">
                        <span class="detail-label">Nomor Faktur:</span>
                        <span class="detail-value">{{ $invoice->invoice_number }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tanggal Faktur:</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Jatuh Tempo:</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge status-{{ strtolower($invoice->status) }}">
                            {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                        </span>
                    </div>
                </div>

                <div class="detail-card customer-details">
                    <div class="detail-title">👤 Informasi Pelanggan</div>
                    <div class="detail-item">
                        <span class="detail-label">Nama:</span>
                        <span class="detail-value">{{ $invoice->customer->name }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">No. Polisi:</span>
                        <span class="detail-value">{{ $invoice->vehicle->license_plate }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Kendaraan:</span>
                        <span class="detail-value">{{ $invoice->vehicle->brand }} {{ $invoice->vehicle->model }} ({{ $invoice->vehicle->year }})</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Telepon:</span>
                        <span class="detail-value">{{ $invoice->customer->phone ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="section-title">🔧 Detail Jasa & Layanan</div>
            @if ($invoice->services->isNotEmpty())
                <table class="modern-table">
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
                                <td style="text-align: right; font-weight: 600;">Rp {{ number_format($service->pivot->price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 15px;">🔍</div>
                    <div>Tidak ada jasa yang ditambahkan</div>
                </div>
            @endif

            <div class="section-title">📦 Detail Barang & Suku Cadang</div>
            @if ($invoice->items->isNotEmpty())
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Deskripsi</th>
                            <th style="text-align: center;">Qty</th>
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
                                <td style="text-align: right; font-weight: 600;">Rp {{ number_format($item->pivot->price, 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: 600;">Rp {{ number_format($item->pivot->quantity * $item->pivot->price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 15px;">📦</div>
                    <div>Tidak ada barang yang ditambahkan</div>
                </div>
            @endif

            <div class="totals-section">
                <div class="total-item">
                    <span class="total-label">Subtotal:</span>
                    <span class="total-value">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="total-item">
                    <span class="total-label">Diskon:</span>
                    <span class="total-value">
                        @if ($invoice->discount_type === 'percentage')
                            {{ $invoice->discount_value }}% (Rp {{ number_format(($invoice->subtotal * $invoice->discount_value) / 100, 0, ',', '.') }})
                        @else
                            Rp {{ number_format($invoice->discount_value, 0, ',', '.') }}
                        @endif
                    </span>
                </div>
                <div class="total-item total-final">
                    <span class="total-label">Total Akhir:</span>
                    <span class="total-value">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="total-item">
                    <span class="total-label">Total Dibayar:</span>
                    <span class="total-value">Rp {{ number_format($invoice->total_paid_amount, 0, ',', '.') }}</span>
                </div>
                @if ($invoice->balance_due > 0)
                    <div class="total-item">
                        <span class="total-label">Sisa Tagihan:</span>
                        <span class="total-value balance-due">Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if ($invoice->overpayment > 0)
                    <div class="total-item">
                        <span class="total-label">Kembalian:</span>
                        <span class="total-value overpayment">Rp {{ number_format($invoice->overpayment, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>

            @if ($invoice->terms)
                <div class="terms-section">
                    <div class="terms-title">📝 Syarat & Ketentuan</div>
                    <div class="terms-content">{{ nl2br(e($invoice->terms)) }}</div>
                </div>
            @endif

            <div class="signatures-section">
                <div class="signature-box">
                    <div class="signature-label">Hormat Kami,</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">(Brie Sejahtera Mobil)</div>
                </div>
                <div class="signature-box">
                    <div class="signature-label">Penerima,</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">({{ $invoice->customer->name }})</div>
                </div>
            </div>
        </div>

        <div class="footer-section">
            <div class="thank-you-message">🙏 Terima kasih atas kepercayaan Anda!</div>
            <div class="thank-you-subtitle">Semoga kendaraan Anda selalu dalam kondisi prima</div>
        </div>

        <div class="print-buttons" style="background: white; padding: 20px;">
            <button class="btn btn-print" onclick="window.print()">
                🖨️ Cetak Faktur
            </button>
            <button class="btn btn-close" onclick="window.close()">
                ✖️ Tutup
            </button>
        </div>
    </div>
</body>

</html>
