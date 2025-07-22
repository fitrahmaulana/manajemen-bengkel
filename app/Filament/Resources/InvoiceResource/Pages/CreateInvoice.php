<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Traits\InvoiceCalculationTrait;
use Filament\Resources\Pages\CreateRecord;

use App\Services\InvoiceStockService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateInvoice extends CreateRecord
{
    use InvoiceCalculationTrait;

    protected static string $resource = InvoiceResource::class;

    /**
     * Hook ini dijalankan SEBELUM data form utama dan relasi disimpan ke database.
     * Alur:
     * 1. Mengambil data dari repeater 'invoiceServices' dan 'invoiceItems'.
     * 2. Menghitung total biaya dari jasa dan barang.
     * 3. Menghitung diskon (jika ada).
     * 4. Menghitung dan menyimpan `subtotal` dan `total_amount` ke dalam data invoice utama.
     *
     * @param array $data Data form saat ini.
     * @return array Data form yang telah dimutasi.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get the current state of the form data, including relationships
        $currentData = $this->data;
        $services = $currentData['invoiceServices'] ?? [];
        $items = $currentData['invoiceItems'] ?? [];

        $servicesTotal = collect($services)->sum(function ($service) {
            return self::parseCurrencyValue($service['price'] ?? '0');
        });

        $itemsTotal = collect($items)->sum(function ($item) {
            $quantity = (float)($item['quantity'] ?? 0.0);
            $price = self::parseCurrencyValue($item['price'] ?? '0');
            return $quantity * $price;
        });

        $subtotal = $servicesTotal + $itemsTotal;
        $data['subtotal'] = $subtotal;

        $discountType = $data['discount_type'] ?? 'fixed';
        $discountValue = self::parseCurrencyValue($data['discount_value'] ?? '0');

        if ($discountType === 'percentage') {
            $discountAmount = ($subtotal * $discountValue) / 100;
        } else {
            $discountAmount = $discountValue;
        }

        $totalAmount = $subtotal - $discountAmount;
        $data['total_amount'] = $totalAmount;

        return $data;
    }

    /**
     * Hook ini dijalankan SETELAH record Invoice utama dan relasinya berhasil dibuat.
     * Alur:
     * 1. Mengambil data item dari form.
     * 2. Memanggil InvoiceStockService untuk mengurangi stok barang yang terjual.
     * 3. Memanggil trait untuk mengupdate status invoice (misal: dari 'draft' ke 'unpaid').
     * 4. Menampilkan notifikasi sukses.
     * 5. Jika terjadi error, transaksi di-rollback dan notifikasi error ditampilkan.
     */
    protected function afterCreate(): void
    {
        // The stock adjustment logic is now handled by the repeater's mutation hooks.
        // This hook is now only responsible for updating the invoice status.
        self::updateInvoiceStatus($this->record);

        Notification::make()
            ->title('Faktur Berhasil Dibuat')
            ->body('Faktur dan stock telah berhasil diperbarui.')
            ->success()
            ->send();
    }
}
