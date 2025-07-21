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

    public array $originalItems = [];

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
        // Store the original items before they are updated
        $this->originalItems = $this->record->invoiceItems->mapWithKeys(function ($item) {
            return [$item->item_id => $item->quantity];
        })->toArray();

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

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        DB::transaction(function () use ($record, $data) {
            $stockService = app(InvoiceStockService::class);

            // 1. Restore stock for all original items
            $stockService->restoreStockForInvoiceItems($record);

            // 2. Update the main invoice attributes
            $record->update($data);

            // 3. Sync the new invoiceServices and invoiceItems
            $services = $data['invoiceServices'] ?? [];
            $items = $data['invoiceItems'] ?? [];
            $record->invoiceServices()->delete();
            $record->invoiceItems()->delete();
            $record->invoiceServices()->createMany($services);
            $record->invoiceItems()->createMany($items);

            // 4. Deduct stock for the new set of items
            if (!empty($items)) {
                $stockService->deductStockForInvoiceItems($record, $items);
            }

            // 5. Update the invoice status
            self::updateInvoiceStatus($record);
        });

        return $record;
    }
}
