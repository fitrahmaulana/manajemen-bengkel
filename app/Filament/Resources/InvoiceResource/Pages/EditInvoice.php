<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceStockService;
use App\Traits\InvoiceCalculationTrait;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EditInvoice extends EditRecord
{
    use InvoiceCalculationTrait;

    protected static string $resource = InvoiceResource::class;

    /**
     * Hook ini dijalankan SEBELUM data form utama dan relasi di-update ke database.
     * Fungsinya sama dengan di halaman Create, yaitu memastikan `subtotal` dan `total_amount`
     * dihitung ulang dan disimpan dengan benar setiap kali ada perubahan.
     *
     * @param array $data Data form saat ini.
     * @return array Data form yang telah dimutasi.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $services = $data['invoiceServices'] ?? [];
        $items = $data['invoiceItems'] ?? [];

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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (InvoiceStockService $stockService) {
                    $stockService->restoreStockForInvoiceItems($this->record);
                }),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make()
                ->after(function (InvoiceStockService $stockService) {
                    $itemsData = $this->record->invoiceItems->map(function ($item) {
                        return ['item_id' => $item->item_id, 'quantity' => $item->quantity];
                    })->toArray();
                    $stockService->deductStockForInvoiceItems($this->record, $itemsData);
                    self::updateInvoiceStatus($this->record);
                }),
        ];
    }

    protected function afterSave(): void
    {
        $items = $this->data['invoiceItems'] ?? [];

        try {
            DB::transaction(function () use ($items) {
                if (!empty($items)) {
                    // NOTE: The stock adjustment logic here is simplified.
                    // It restores stock for ALL items that were previously on the invoice
                    // and then deducts stock for ALL items currently in the form state.
                    // This works but can be inefficient for large invoices.
                    // A more optimized approach would be to calculate the diff between
                    // the original and new item states and only adjust the difference.
                    // However, for most cases, this approach is safe and reliable.

                    // Restore stock for all old items
                    app(InvoiceStockService::class)->restoreStockForInvoiceItems($this->record);

                    // Deduct stock for the new set of items
                    app(InvoiceStockService::class)->deductStockForInvoiceItems($this->record, $items);
                }
                self::updateInvoiceStatus($this->record);
            });

            Notification::make()
                ->title('Faktur Berhasil Diperbarui')
                ->body('Faktur dan stock telah berhasil diperbarui.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Memperbarui Faktur')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
            throw $e;
        }
    }
}
