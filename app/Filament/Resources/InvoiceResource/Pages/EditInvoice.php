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
                    // Get original items with their quantities before any changes
                    $originalItems = [];
                    foreach ($this->record->invoiceItems as $item) {
                        $originalItems[$item->item_id] = (float)$item->quantity;
                    }

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
