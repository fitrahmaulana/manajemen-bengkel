<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
    /**
     * Hook ini dijalankan SETELAH record Invoice utama berhasil dibuat.
     * Di sinilah kita akan menyimpan relasi ke tabel pivot.
     */
    protected function afterCreate(): void
    {
        $services = $this->data['services'] ?? [];
        $items = $this->data['items'] ?? [];

        // 1. Simpan relasi untuk Jasa (Services)
        foreach ($services as $service) {
            $this->record->services()->attach($service['service_id'], [
                'price' => $service['price'],
                'description' => $service['description'],
            ]);
        }

        // 2. Simpan relasi untuk Barang (Items)
        foreach ($items as $item) {
            $this->record->items()->attach($item['item_id'], [
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'description' => $item['description'],
            ]);

            // Update item stock
            $itemModel = \App\Models\Item::find($item['item_id']);
            if ($itemModel) {
                $itemModel->stock -= ($item['quantity'] ?? 1);
                $itemModel->save();
            }
        }
    }
}
