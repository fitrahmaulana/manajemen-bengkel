<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Item;
use App\Services\InvoiceStockService; // Import the new service
use App\Traits\InvoiceCalculationTrait; // Use the optimized trait
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateInvoice extends CreateRecord
{
    use InvoiceCalculationTrait; // Use the trait for consistency

    protected static string $resource = InvoiceResource::class;

    /**
     * Hook ini dijalankan SEBELUM data disimpan ke database.
     * Menghitung subtotal dan total_amount berdasarkan services dan items.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $services = $data['services'] ?? [];
        $items = $data['items'] ?? [];

        // 1. Hitung subtotal dari services
        $servicesTotal = collect($services)->sum(function ($service) {
            return self::parseCurrencyValue($service['price'] ?? '0');
        });

        // 2. Hitung subtotal dari items
        $itemsTotal = collect($items)->sum(function ($item) {
            $quantity = (float)($item['quantity'] ?? 0.0); // Changed to float
            $price = self::parseCurrencyValue($item['price'] ?? '0');
            return $quantity * $price;
        });

        // 3. Hitung subtotal
        $subtotal = $servicesTotal + $itemsTotal;
        $data['subtotal'] = $subtotal;

        // 4. Hitung diskon
        $discountType = $data['discount_type'] ?? 'fixed';
        $discountValue = self::parseCurrencyValue($data['discount_value'] ?? '0');

        if ($discountType === 'percentage') {
            $discountAmount = ($subtotal * $discountValue) / 100;
        } else {
            $discountAmount = $discountValue;
        }

        // 5. Hitung total akhir
        $totalAmount = $subtotal - $discountAmount;
        $data['total_amount'] = $totalAmount;

        return $data;
    }

    /**
     * Hook ini dijalankan SETELAH record Invoice utama berhasil dibuat.
     * Optimized untuk menangani relasi dan stock adjustment dalam satu transaksi.
     */
    protected function afterCreate(): void
    {
        $services = $this->data['services'] ?? [];
        $items = $this->data['items'] ?? [];

        try {
            DB::transaction(function () use ($services, $items) {
                // 1. Simpan relasi untuk Jasa (Services)
                if (!empty($services)) {
                    $serviceData = collect($services)->mapWithKeys(function ($service) {
                        return [$service['service_id'] => [
                            'price' => self::parseCurrencyValue($service['price'] ?? 0),
                            'description' => $service['description'] ?? '',
                        ]];
                    });
                    $this->record->services()->sync($serviceData);
                }

                // 2. Simpan relasi untuk Barang (Items) dan update stock
                if (!empty($items)) {
                    $itemData = collect($items)->mapWithKeys(function ($item) {
                        return [$item['item_id'] => [
                            'quantity' => (float)($item['quantity'] ?? 1.0),
                            'price' => self::parseCurrencyValue($item['price'] ?? 0),
                            'description' => $item['description'] ?? '',
                        ]];
                    });
                    $this->record->items()->sync($itemData);

                    // Use InvoiceStockService to deduct stock
                    app(InvoiceStockService::class)->deductStockForInvoiceItems($this->record, $items);
                }

                // 3. Update invoice status after creation
                self::updateInvoiceStatus($this->record);
            });

            // Show success notification
            Notification::make()
                ->title('Faktur Berhasil Dibuat')
                ->body('Faktur dan stock telah berhasil diperbarui.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Rollback akan otomatis terjadi karena DB::transaction
            Notification::make()
                ->title('Error Membuat Faktur')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

            // Optionally redirect back or handle error
            throw $e;
        }
    }
}
