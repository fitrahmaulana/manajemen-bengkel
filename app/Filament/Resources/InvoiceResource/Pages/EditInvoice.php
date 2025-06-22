<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
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
        $this->record->items()->sync($itemsToSync);
    }
}
