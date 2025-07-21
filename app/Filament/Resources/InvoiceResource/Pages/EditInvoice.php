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
}
