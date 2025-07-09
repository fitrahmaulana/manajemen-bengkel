<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Item;
use App\Services\InvoiceStockService; // Import the new service
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (InvoiceStockService $stockService) {
                    $stockService->restoreStockForInvoiceItems($this->record);
                })
                ->after(function () {
                    // Optional: Any other logic after soft delete
                }),
            Actions\ForceDeleteAction::make() // Note: Stock is not restored on ForceDelete by default here.
                                             // If it should be, similar logic as DeleteAction::before would be needed.
                                             // However, typically force delete implies data is gone permanently.
                ->before(function (InvoiceStockService $stockService) {
                     // If stock should be restored even on force delete (uncommon but possible)
                     // $stockService->restoreStockForInvoiceItems($this->record);
                }),
            Actions\RestoreAction::make()
                ->after(function (InvoiceStockService $stockService) {
                    // Re-deduct stock for items on the restored invoice
                    // Convert Eloquent collection to array format expected by deductStockForInvoiceItems
                    $itemsData = $this->record->items->map(function ($item) {
                        return ['item_id' => $item->id, 'quantity' => $item->pivot->quantity];
                    })->toArray();
                    $stockService->deductStockForInvoiceItems($this->record, $itemsData);

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
                $stockService = app(InvoiceStockService::class);
                $newItemsData = $data['items'] ?? [];

                // Get original items with their quantities before any changes
                $originalItems = [];
                foreach ($record->items as $item) {
                    $originalItems[$item->id] = (float)$item->pivot->quantity;
                }

                // First, update the main record and its direct relations (excluding stock logic for now)
                // We need to call parent::handleRecordUpdate to let Filament do its thing with data.
                // The actual item data for stock adjustment will be from $data['items'] (new state)
                // and $originalItems (old state).
                // Note: The parent method will also sync relationships if not handled separately in afterSave.
                // For this refactor, we'll assume stock is handled *after* the main record update.

                // Detach all items first to handle removals/updates cleanly. Stock will be restored.
                // This is a simple approach; a more complex one would compare item by item.
                if ($record->items()->exists()) {
                    $stockService->restoreStockForInvoiceItems($record); // Restore stock for all old items
                    $record->items()->detach(); // Detach all items
                }

                // Update the record (this will save main invoice fields)
                // $data is already mutated by mutateFormDataBeforeSave for totals
                $record->fill($data);
                $record->save();


                // Re-attach new/updated items and deduct stock
                // (afterSave will also sync items, but stock deduction needs $data items)
                if (!empty($newItemsData)) {
                    $itemsToSync = collect($newItemsData)->mapWithKeys(function ($item) {
                        return [$item['item_id'] => [
                            'quantity' => (float)($item['quantity'] ?? 0.0),
                            'price' => self::parseCurrencyValue($item['price'] ?? '0'),
                            'description' => $item['description'] ?? '',
                        ]];
                    })->all();
                    $record->items()->sync($itemsToSync); // Sync new set of items

                    // Deduct stock for the new set of items
                    $stockService->deductStockForInvoiceItems($record, $newItemsData);
                }


                // Update invoice status
                self::updateInvoiceStatus($record);

                return $record;
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

    // The old adjustItemStock method is removed.

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
                // Note: Stock adjustment is now handled in handleRecordUpdate.
                // This sync is primarily for saving price and description pivot data if they changed.
                // The items themselves (IDs and quantities) are already synced in handleRecordUpdate.
                if (!empty($itemsData)) {
                    $itemsToSync = collect($itemsData)->mapWithKeys(function ($item) {
                        return [$item['item_id'] => [
                            'quantity' => (float)($item['quantity'] ?? 0.0), // Use float
                            'price' => self::parseCurrencyValue($item['price'] ?? '0'),
                            'description' => $item['description'] ?? '',
                        ]];
                    });
                    $this->record->items()->sync($itemsToSync); // This ensures pivot data is correct
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
