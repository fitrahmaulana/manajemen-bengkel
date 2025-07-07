<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Item;
use App\Traits\InvoiceCalculationTrait; // Use the optimized trait
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EditInvoice extends EditRecord
{
    use InvoiceCalculationTrait; // Use the trait for consistency

    protected static string $resource = InvoiceResource::class;

    /**
     * Hook ini dijalankan SEBELUM data disimpan ke database saat update.
     * Menghitung subtotal dan total_amount berdasarkan services dan items.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $services = $data['services'] ?? [];
        $items = $data['items'] ?? [];

        // 1. Hitung subtotal dari services
        $servicesTotal = collect($services)->sum(function ($service) {
            return self::parseCurrencyValue($service['price'] ?? '0');
        });

        // 2. Hitung subtotal dari items
        $itemsTotal = collect($items)->sum(function ($item) {
            $quantity = (int)($item['quantity'] ?? 0);
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Restore stock before deleting invoice
                    foreach ($this->record->items as $itemPivot) {
                        $itemModel = \App\Models\Item::find($itemPivot->id);
                        if ($itemModel) {
                            $quantityToRestore = $itemPivot->pivot->quantity;
                            $itemModel->stock += $quantityToRestore;
                            $itemModel->save();
                        }
                    }
                })
                ->after(function () {
                    // Update invoice status after deletion if needed
                    // Note: This is for soft delete, hard delete would not need this
                }),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make()
                ->after(function () {
                    // Re-decrement stock for items on the restored invoice
                    foreach ($this->record->items as $itemPivot) {
                        $itemModel = \App\Models\Item::find($itemPivot->id);
                        if ($itemModel) {
                            $quantityToDecrement = $itemPivot->pivot->quantity;

                            // Check if stock would go negative
                            if ($itemModel->stock >= $quantityToDecrement) {
                                $itemModel->stock -= $quantityToDecrement;
                                $itemModel->save();
                            } else {
                                // Handle negative stock scenario
                                \Filament\Notifications\Notification::make()
                                    ->title('⚠️ Stock Tidak Mencukupi')
                                    ->body("Item {$itemModel->name} tidak memiliki stock yang cukup untuk di-restore.")
                                    ->warning()
                                    ->send();
                            }
                        }
                    }
                    // Update invoice status after restoration
                    self::updateInvoiceStatus($this->record);
                }),
        ];
    }

    /**
     * Hook ini dijalankan SEBELUM form diisi dengan data dari database.
     * Optimized untuk memuat data relasi dan menyimpan state original.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $invoiceRecord = $this->getRecord();

        // 1. Ambil dan format data services
        $services = $invoiceRecord->services->map(function ($service) {
            return [
                'service_id' => $service->id,
                'price' => $service->pivot->price,
                'description' => $service->pivot->description ?? ''
            ];
        })->toArray();

        // 2. Ambil dan format data items
        $items = $invoiceRecord->items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'quantity' => $item->pivot->quantity,
                'price' => $item->pivot->price,
                'unit_name' => $item->unit,
                'description' => $item->pivot->description ?? ''
            ];
        })->toArray();

        // 3. Masukkan data ke form
        $data['services'] = $services;
        $data['items'] = $items;

        return $data;
    }

    /**
     * Handle record update dengan optimized stock management.
     * Menggunakan transaksi database untuk consistency.
     */
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {

        try {
            return DB::transaction(function () use ($record, $data) {
                // 1. Calculate and apply stock adjustments first
                $this->adjustItemStock($record, $data['items'] ?? []);

                // 2. Update main record
                $updatedRecord = parent::handleRecordUpdate($record, $data);

                // 3. Update invoice status
                self::updateInvoiceStatus($updatedRecord);

                return $updatedRecord;
            });
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Mengupdate Faktur')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    /**
     * Optimized stock adjustment logic.
     * Langsung menggunakan data original dari $record - lebih efisien!
     */
    private function adjustItemStock(\Illuminate\Database\Eloquent\Model $record, array $newItemsData): void
    {
        $newItemsCollection = collect($newItemsData)->keyBy('item_id');

        // Ambil data original langsung dari record - no need for separate storage!
        $originalItemsCollection = collect($record->items)->mapWithKeys(function ($item) {
            return [$item->id => $item->pivot->quantity];
        });

        // Process existing and new items
        foreach ($newItemsCollection as $itemId => $itemDetails) {
            $item = Item::find($itemId);
            if (!$item) continue;

            $newQuantity = (int)($itemDetails['quantity'] ?? 1);
            $originalQuantity = (int)($originalItemsCollection->get($itemId, 0));
            $quantityDifference = $newQuantity - $originalQuantity;

            // Adjust stock: positive difference = more items used (decrease stock)
            // negative difference = items returned (increase stock)
            if ($quantityDifference !== 0) {
                $item->stock -= $quantityDifference;
                $item->save();
            }
        }

        // Process removed items (restore their stock)
        $removedItems = $originalItemsCollection->keys()->diff($newItemsCollection->keys());
        foreach ($removedItems as $removedItemId) {
            $item = Item::find($removedItemId);
            if ($item) {
                $restoredQuantity = (int)$originalItemsCollection->get($removedItemId, 0);
                $item->stock += $restoredQuantity;
                $item->save();
            }
        }
    }

    /**
     * Hook ini dijalankan SETELAH record berhasil di-update.
     * Sync relasi pivot tables dengan data yang sudah dioptimasi.
     */
    protected function afterSave(): void
    {
        $servicesData = $this->data['services'] ?? [];
        $itemsData = $this->data['items'] ?? [];

        try {
            DB::transaction(function () use ($servicesData, $itemsData) {
                // Sync services with optimized data structure
                if (!empty($servicesData)) {
                    $servicesToSync = collect($servicesData)->mapWithKeys(function ($service) {
                        return [$service['service_id'] => [
                            'price' => self::parseCurrencyValue($service['price'] ?? 0),
                            'description' => $service['description'] ?? '',
                        ]];
                    });
                    $this->record->services()->sync($servicesToSync);
                }

                // Sync items with optimized data structure
                if (!empty($itemsData)) {
                    $itemsToSync = collect($itemsData)->mapWithKeys(function ($item) {
                        return [$item['item_id'] => [
                            'quantity' => (int)($item['quantity'] ?? 1),
                            'price' => self::parseCurrencyValue($item['price'] ?? 0),
                            'description' => $item['description'] ?? '',
                        ]];
                    });
                    $this->record->items()->sync($itemsToSync);
                }
            });

            // Show success notification
            Notification::make()
                ->title('Faktur Berhasil Diperbarui')
                ->body('Faktur dan stock telah berhasil diperbarui.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Menyimpan Relasi')
                ->body('Terjadi kesalahan saat menyimpan: ' . $e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
