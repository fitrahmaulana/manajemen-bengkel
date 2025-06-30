<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use App\Models\Item; // Tambahkan ini
use Illuminate\Validation\ValidationException; // Tambahkan ini

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateDataBeforeSave(array $data): array
    {
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $key => $invoiceItem) {
                if (empty($invoiceItem['item_id']) || !isset($invoiceItem['quantity'])) { // Cek quantity ada, bisa jadi 0 tapi tetap harus divalidasi
                    // Jika item_id atau quantity tidak ada, mungkin lewati atau beri error spesifik
                    // Untuk sekarang, kita asumsikan field repeater akan selalu punya key ini jika ada item
                    continue;
                }

                $item = Item::find($invoiceItem['item_id']);
                $quantityInForm = (int)$invoiceItem['quantity'];

                if ($quantityInForm <= 0) {
                    throw ValidationException::withMessages([
                        "data.items.{$key}.quantity" => "Kuantitas untuk " . ($item?->name ?? 'item') . " harus lebih dari 0.",
                    ]);
                }

                if ($item && $quantityInForm > $item->stock) {
                    $itemName = $item->name;
                    $itemStock = $item->stock;
                    $itemUnit = $item->unit;
                    throw ValidationException::withMessages([
                        "data.items.{$key}.quantity" => "Stok untuk {$itemName} hanya tersisa {$itemStock} {$itemUnit}, sedangkan kuantitas yang diminta {$quantityInForm}. Kurangi kuantitas atau lakukan pecah stok jika tersedia.",
                    ]);
                } elseif (!$item && !empty($invoiceItem['item_id'])) {
                    // Item dipilih tapi tidak ditemukan di DB, ini seharusnya tidak terjadi jika form valid
                    throw ValidationException::withMessages([
                        "data.items.{$key}.item_id" => "Item yang dipilih tidak valid atau tidak ditemukan.",
                    ]);
                }
            }
        }
        return $data;
    }

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
