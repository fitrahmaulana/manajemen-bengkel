<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    {{-- Tailwind CSS will be linked via app.css or a Filament asset pipeline if applicable --}}
    {{-- For standalone, ensure Tailwind is compiled and linked here or via Vite/Mix --}}
    @vite(['resources/css/app.css', 'resources/js/app.js']) {{-- Assuming Vite setup for Tailwind --}}
</head>
<body class="bg-white text-gray-700 font-sans text-sm print:text-xs">
    <div class="max-w-3xl mx-auto my-6 p-6 bg-white border border-gray-300 shadow-lg print:shadow-none print:border-none print:my-0 print:p-2">
        <!-- Header -->
        <div class="text-center mb-8 print:mb-6">
            <h1 class="text-2xl font-bold text-gray-900 print:text-xl">FAKTUR PENJUALAN</h1>
            <p class="text-gray-600 print:text-xs">Bengkel XYZ</p>
            <p class="text-gray-600 print:text-xs">Jl. Raya Contoh No. 123, Kota Contoh</p>
            <p class="text-gray-600 print:text-xs">Telepon: (021) 1234567</p>
        </div>

        <!-- Invoice and Customer Details -->
        <div class="flex justify-between mb-6 pb-4 border-b border-gray-200 print:mb-4 print:pb-2">
            <div class="w-1/2 pr-2 print:w-1/2">
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-32 inline-block print:w-24">No. Faktur:</strong> {{ $invoice->invoice_number }}</p>
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-32 inline-block print:w-24">Tanggal Faktur:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</p>
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-32 inline-block print:w-24">Jatuh Tempo:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</p>
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-32 inline-block print:w-24">Status:</strong> <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status === 'unpaid' || $invoice->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }} print:bg-transparent print:text-gray-800 print:px-0 print:py-0 print:font-normal">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span></p>
            </div>
            <div class="w-1/2 pl-2 print:w-1/2">
                <h3 class="text-sm font-semibold text-gray-800 mb-1 print:text-xs">Pelanggan:</h3>
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-28 inline-block print:w-20">Nama:</strong> {{ $invoice->customer->name }}</p>
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-28 inline-block print:w-20">No. Polisi:</strong> {{ $invoice->vehicle->license_plate }}</p>
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-28 inline-block print:w-20">Kendaraan:</strong> {{ $invoice->vehicle->brand }} {{ $invoice->vehicle->model }} ({{ $invoice->vehicle->year }})</p>
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-28 inline-block print:w-20">Telepon:</strong> {{ $invoice->customer->phone ?? '-' }}</p>
                <p class="mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800 w-28 inline-block print:w-20">Alamat:</strong> {{ $invoice->customer->address ?? '-' }}</p>
            </div>
        </div>

        <!-- Services -->
        <div class="mb-6 print:mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-2 pb-1 border-b border-gray-200 print:text-sm">Detail Jasa / Layanan</h3>
            @if($invoice->services->isNotEmpty())
                <table class="w-full border-collapse print:text-xs">
                    <thead>
                        <tr>
                            <th class="border border-gray-300 p-2 text-left font-semibold bg-gray-50 print:p-1">Nama Jasa</th>
                            <th class="border border-gray-300 p-2 text-left font-semibold bg-gray-50 print:p-1">Deskripsi</th>
                            <th class="border border-gray-300 p-2 text-right font-semibold bg-gray-50 print:p-1">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->services as $service)
                            <tr class="print:break-inside-avoid">
                                <td class="border border-gray-300 p-2 print:p-1">{{ $service->name }}</td>
                                <td class="border border-gray-300 p-2 print:p-1">{{ $service->pivot->description ?? '-' }}</td>
                                <td class="border border-gray-300 p-2 text-right print:p-1">Rp {{ number_format($service->pivot->price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-center text-gray-500 italic print:text-xs">Tidak ada jasa yang ditambahkan.</p>
            @endif
        </div>

        <!-- Items -->
        <div class="mb-6 print:mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-2 pb-1 border-b border-gray-200 print:text-sm">Detail Barang / Suku Cadang</h3>
            @if($invoice->items->isNotEmpty())
                <table class="w-full border-collapse print:text-xs">
                    <thead>
                        <tr>
                            <th class="border border-gray-300 p-2 text-left font-semibold bg-gray-50 print:p-1">Nama Barang</th>
                            <th class="border border-gray-300 p-2 text-left font-semibold bg-gray-50 print:p-1">Deskripsi</th>
                            <th class="border border-gray-300 p-2 text-center font-semibold bg-gray-50 print:p-1">Kuantitas</th>
                            <th class="border border-gray-300 p-2 text-right font-semibold bg-gray-50 print:p-1">Harga Satuan</th>
                            <th class="border border-gray-300 p-2 text-right font-semibold bg-gray-50 print:p-1">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr class="print:break-inside-avoid">
                                <td class="border border-gray-300 p-2 print:p-1">{{ $item->product->name }} {{ $item->name }}</td>
                                <td class="border border-gray-300 p-2 print:p-1">{{ $item->pivot->description ?? '-' }}</td>
                                <td class="border border-gray-300 p-2 text-center print:p-1">{{ $item->pivot->quantity }} {{ $item->unit }}</td>
                                <td class="border border-gray-300 p-2 text-right print:p-1">Rp {{ number_format($item->pivot->price, 0, ',', '.') }}</td>
                                <td class="border border-gray-300 p-2 text-right print:p-1">Rp {{ number_format($item->pivot->quantity * $item->pivot->price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-center text-gray-500 italic print:text-xs">Tidak ada barang yang ditambahkan.</p>
            @endif
        </div>

        <!-- Totals -->
        <div class="flex justify-end mb-6 print:mb-4">
            <div class="w-full max-w-xs print:max-w-[40%]">
                <div class="pt-2 border-t border-gray-200">
                    <p class="flex justify-between mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800">Subtotal:</strong> <span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></p>
                    <p class="flex justify-between mb-1 print:mb-0.5">
                        <strong class="font-semibold text-gray-800">Diskon:</strong>
                        <span>
                            @if($invoice->discount_type === 'percentage')
                                {{ $invoice->discount_value }}%
                                (Rp {{ number_format(($invoice->subtotal * $invoice->discount_value / 100), 0, ',', '.') }})
                            @else
                                Rp {{ number_format($invoice->discount_value, 0, ',', '.') }}
                            @endif
                        </span>
                    </p>
                    <p class="flex justify-between mb-1 text-base font-bold text-gray-900 print:text-sm print:mb-0.5"><strong class="text-gray-800">Total Akhir:</strong> <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></p>
                    <hr class="my-1 border-dashed border-gray-300 print:my-0.5">
                    <p class="flex justify-between mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800">Total Dibayar:</strong> <span>Rp {{ number_format($invoice->total_paid_amount, 0, ',', '.') }}</span></p>
                    @if($invoice->balance_due > 0)
                        <p class="flex justify-between mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800">Sisa Tagihan:</strong> <span class="text-red-600 font-bold">Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}</span></p>
                    @endif
                    @if($invoice->overpayment > 0)
                         <p class="flex justify-between mb-1 print:mb-0.5"><strong class="font-semibold text-gray-800">Kembalian:</strong> <span class="text-green-600 font-bold">Rp {{ number_format($invoice->overpayment, 0, ',', '.') }}</span></p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Terms and Conditions -->
        @if($invoice->terms)
        <div class="mb-6 print:mb-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-1 print:text-xs">Syarat & Ketentuan:</h3>
            <p class="text-xs text-gray-600 print:text-[8pt] leading-tight">{!! nl2br(e($invoice->terms)) !!}</p>
        </div>
        @endif

        <!-- Signatures -->
        <div class="flex justify-around mt-10 pt-6 border-t border-gray-200 print:mt-8 print:pt-4 print:break-inside-avoid">
            <div class="text-center w-2/5 print:w-2/5">
                <p class="mb-1 text-sm print:text-xs">Hormat Kami,</p>
                <p class="mb-1 text-sm print:text-xs">Bengkel XYZ</p>
                <div class="mt-16 border-b border-gray-400 print:mt-12"></div>
                <p class="mt-1 text-xs text-gray-600 print:text-[8pt]">(Pemilik Toko / Kasir)</p>
            </div>
            <div class="text-center w-2/5 print:w-2/5">
                <p class="mb-1 text-sm print:text-xs">Penerima,</p>
                <div class="mt-20 border-b border-gray-400 print:mt-16"></div>
                <p class="mt-1 text-xs text-gray-600 print:text-[8pt]">({{ $invoice->customer->name }})</p>
            </div>
        </div>

        <!-- Thank You Note -->
        <div class="text-center mt-8 pt-4 border-t border-gray-200 print:mt-6 print:pt-2">
            <p class="text-xs text-gray-600">Terima kasih atas kepercayaan Anda.</p>
        </div>

        <!-- Print/Close Buttons (No Print) -->
        <div class="text-center mt-8 space-x-4 print:hidden">
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-150">
                Cetak Faktur
            </button>
            <button onclick="window.close()" class="px-6 py-2 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 transition duration-150">
                Tutup
            </button>
        </div>
    </div>
</body>
</html>
