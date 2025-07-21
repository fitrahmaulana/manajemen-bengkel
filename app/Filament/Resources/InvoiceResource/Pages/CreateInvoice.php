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

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        $items = $this->data['invoiceItems'] ?? [];

        try {
            DB::transaction(function () use ($items) {
                if (!empty($items)) {
                    app(InvoiceStockService::class)->deductStockForInvoiceItems($this->record, $items);
                }
                self::updateInvoiceStatus($this->record);
            });

            Notification::make()
                ->title('Faktur Berhasil Dibuat')
                ->body('Faktur dan stock telah berhasil diperbarui.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Membuat Faktur')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
            throw $e;
        }
    }
}
