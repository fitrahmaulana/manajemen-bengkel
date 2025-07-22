<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceService;
use App\Services\InvoiceStockService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EditInvoice extends EditRecord
{
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
        $invoiceService = app(InvoiceService::class);
        // Get the current state of the form data, including relationships
        $currentData = $this->data;
        $services = $currentData['invoiceServices'] ?? [];
        $items = $currentData['invoiceItems'] ?? [];

        $servicesTotal = collect($services)->sum(function ($service) use ($invoiceService) {
            return $invoiceService->parseCurrencyValue($service['price'] ?? '0');
        });

        $itemsTotal = collect($items)->sum(function ($item) use ($invoiceService) {
            $quantity = (float)($item['quantity'] ?? 0.0);
            $price = $invoiceService->parseCurrencyValue($item['price'] ?? '0');
            return $quantity * $price;
        });

        $subtotal = $servicesTotal + $itemsTotal;
        $data['subtotal'] = $subtotal;

        $discountType = $data['discount_type'] ?? 'fixed';
        $discountValue = $invoiceService->parseCurrencyValue($data['discount_value'] ?? '0');

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
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make()
                ->after(function (InvoiceStockService $stockService) {
                    $itemsData = $this->record->invoiceItems->map(function ($item) {
                        return ['item_id' => $item->item_id, 'quantity' => $item->quantity];
                    })->toArray();
                    $stockService->deductStockForInvoiceItems($this->record, $itemsData);
                    app(InvoiceService::class)->updateInvoiceStatus($this->record);
                }),
        ];
    }

    protected function afterSave(): void
    {
        // The stock adjustment logic is now handled by the repeater's mutation hooks.
        // This hook is now only responsible for updating the invoice status.
        app(InvoiceService::class)->updateInvoiceStatus($this->record);
    }
}
