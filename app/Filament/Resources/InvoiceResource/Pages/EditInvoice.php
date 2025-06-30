<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    // Property to store original item quantities
    protected array $originalItemsQuantities = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        // Store original quantities before saving
        $this->originalItemsQuantities = $this->getRecord()->items->pluck('pivot.quantity', 'id')->toArray();
    }

       /**
     * Hook ini dijalankan SEBELUM form diisi dengan data dari database.
     * Kita memanfaatkannya untuk memuat dan memformat data dari relasi.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 1. Ambil data relasi services dan items dari record Invoice yang sedang diedit
        $services = $this->getRecord()->services->map(function ($service) {
            return [
                'service_id' => $service->id,
                'price' => $service->pivot->price,
                'description' => $service->pivot->description
            ];
        })->all();

        $items = $this->getRecord()->items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'quantity' => $item->pivot->quantity,
                'price' => $item->pivot->price,
                'unit_name' => $item->unit,
                'description' => $item->pivot->description
            ];
        })->all();

        // 2. Masukkan data relasi yang sudah diformat ke dalam array data utama form
        $data['services'] = $services;
        $data['items'] = $items;

        return $data;
    }

    /**
     * Hook ini dijalankan SETELAH record Invoice utama berhasil di-update.
     * Di sini kita akan menyinkronkan data di tabel pivot.
     */
    protected function afterSave(): void
    {
        // Ambil data terbaru dari form
        $servicesData = $this->data['services'] ?? [];
        $itemsData = $this->data['items'] ?? [];

        // Siapkan data untuk disinkronkan
        $servicesToSync = collect($servicesData)->mapWithKeys(function ($service) {
            return [$service['service_id'] => [
                'price' => $service['price'],
                'description' => $service['description'],
            ]];
        });

        $itemsToSync = collect($itemsData)->mapWithKeys(function ($item) {
            return [$item['item_id'] => [
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'description' => $item['description'],
            ]];
        });

        // Gunakan sync() untuk mengupdate tabel pivot.
        // Sync akan otomatis menambah, mengubah, dan menghapus record di tabel pivot sesuai data terakhir.
        $this->record->services()->sync($servicesToSync);
        // $this->record->items()->sync($itemsToSync); // We will handle item syncing manually to adjust stock

        // Adjust stock based on changes
        $newItemsFromForm = collect($itemsData)->keyBy('item_id'); // item_id from form
        $currentInvoiceItems = $this->record->items()->get()->keyBy('id'); // Item model id

        // Iterate through items currently in the invoice (after potential form submission)
        foreach ($newItemsFromForm as $formItemId => $formItemData) {
            $itemModel = Item::find($formItemId);
            if (!$itemModel) {
                continue;
            }

            $newQuantity = (int)($formItemData['quantity'] ?? 0);
            $originalQuantity = (int)($this->originalItemsQuantities[$formItemId] ?? 0); // Use item ID as key

            if ($currentInvoiceItems->has($formItemId)) { // Item was already in invoice
                $quantityDifference = $newQuantity - $originalQuantity;
                $itemModel->stock -= $quantityDifference;
            } else { // New item added to invoice
                $itemModel->stock -= $newQuantity;
            }
            $itemModel->save();
        }

        // Check for items removed from the invoice
        foreach ($this->originalItemsQuantities as $itemId => $originalQuantity) {
            if (!$newItemsFromForm->has((string)$itemId) && $currentInvoiceItems->has($itemId)) { // Check if item was removed
                $itemModel = Item::find($itemId);
                if ($itemModel) {
                    $itemModel->stock += (int)$originalQuantity; // Add back the stock
                    $itemModel->save();
                }
            }
        }

        // Now sync the items with pivot data
        $this->record->items()->sync($itemsToSync);
    }
}
