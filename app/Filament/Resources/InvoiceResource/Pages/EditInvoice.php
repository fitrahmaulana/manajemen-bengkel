<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException; // Tambahkan ini

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Hook ini dijalankan SEBELUM form diisi dengan data dari database.
     * Kita memanfaatkannya untuk memuat dan memformat data dari relasi
     * dan menyimpan kuantitas item original.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $invoiceRecord = $this->getRecord();
        // 1. Ambil data relasi services dan items dari record Invoice yang sedang diedit
        $services = $invoiceRecord->services->map(function ($service) {
            return [
                'service_id' => $service->id,
                'price' => $service->pivot->price,
                'description' => $service->pivot->description
            ];
        })->all();

        $items = $invoiceRecord->items->map(function ($item) {
            // Store original quantity for stock adjustment
            // $this->originalItemsQuantities[$item->id] = $item->pivot->quantity;
            return [
                'item_id' => $item->id, // Ensure this is just the ID
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
     * Hook ini dijalankan SEBELUM data dari form di-save ke database.
     * Di sini kita akan menangani logika penyesuaian stok.
     *
     * @param \Illuminate\Database\Eloquent\Model $record
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $newItemsData = collect($data['items'] ?? [])->keyBy('item_id');
        $originalItemsData = collect($record->items)
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->pivot->quantity];
            });

        // Items to add or update quantity
        foreach ($newItemsData as $newItemId => $newItemDetails) {
            $item = Item::find($newItemId);
            if (!$item) continue;

            $newQuantity = (int)$newItemDetails['quantity'];
            $originalQuantity = (int)($originalItemsData->get($newItemId) ?? 0); // Default to 0 if item is new
            $quantityDifference = $newQuantity - $originalQuantity;

            // If quantityDifference is positive, stock decreases (more items sold)
            // If quantityDifference is negative, stock increases (less items sold or returned)
            $item->stock -= $quantityDifference;
            $item->save();
        }

        // Items removed from invoice
        foreach ($originalItemsData as $originalItemId => $originalQuantity) {
            if (!$newItemsData->has($originalItemId)) {
                $item = Item::find($originalItemId);
                if ($item) {
                    $item->stock += (int)$originalQuantity; // Add back the full original quantity
                    $item->save();
                }
            }
        }

        // Proceed with the default update behavior after stock adjustments
        return parent::handleRecordUpdate($record, $data);
    }


    /**
     * Hook ini dijalankan SETELAH record Invoice utama berhasil di-update
     * (termasuk setelah handleRecordUpdate selesai).
     * Di sini kita akan menyinkronkan data di tabel pivot.
     */
    protected function afterSave(): void
    {
        // Ambil data terbaru dari form (setelah handleRecordUpdate mungkin memodifikasinya, meskipun idealnya tidak)
        // atau lebih baik, ambil dari $this->data yang merupakan state terakhir form.

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
            return [$item['item_id'] => [ // Pastikan ini adalah item_id dari form
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'description' => $item['description'],
            ]];
        });

        // Gunakan sync() untuk mengupdate tabel pivot.
        $this->record->services()->sync($servicesToSync);
        $this->record->items()->sync($itemsToSync); // Sync items after stock has been adjusted.
    }
}
