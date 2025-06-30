<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    // use Illuminate\Validation\ValidationException; // Sudah di-import oleh duplikat di bawah, atau pastikan hanya ada satu
    // Hapus atau pastikan hanya ada satu 'use Item;' jika ada duplikasi juga.

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;
    protected $listeners = ['stockUpdated' => '$refresh']; // Tambahkan listener

    // Property to store original item quantities
    // protected array $originalItemsQuantities = []; // Ditangani secara berbeda atau tidak lagi krusial dengan validasi baru

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(), // Jika menggunakan soft delete
            Actions\RestoreAction::make(),   // Jika menggunakan soft delete
        ];
    }

    protected function mutateDataBeforeSave(array $data): array
    {
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $key => $invoiceItem) {
                if (empty($invoiceItem['item_id']) || !isset($invoiceItem['quantity'])) {
                    continue;
                }

                $item = Item::find($invoiceItem['item_id']);
                $quantityInForm = (int)$invoiceItem['quantity'];

                if ($quantityInForm <= 0) {
                    throw ValidationException::withMessages([
                        "data.items.{$key}.quantity" => "Kuantitas untuk " . ($item?->name ?? 'item') . " harus lebih dari 0.",
                    ]);
                }

                // Saat edit, validasi stok sedikit berbeda. Kita perlu memperhitungkan kuantitas item yang *sudah ada* di faktur ini sebelumnya.
                // Stok yang relevan adalah: stok_aktual_db + stok_item_ini_di_faktur_sebelumnya.
                // Namun, untuk validasi sebelum save, lebih aman membandingkan dengan stok aktual di DB.
                // Logika penyesuaian stok di afterSave() akan menangani kalkulasi selisihnya.
                // Di sini, kita hanya pastikan kuantitas baru tidak melebihi stok total yang ada di DB saat ini.
                if ($item && $quantityInForm > $item->stock) {
                    // Cek apakah item ini sudah ada di faktur sebelumnya untuk mendapatkan kuantitas original
                    $originalQuantityForItem = 0;
                    if($this->record && $this->record->items()->where('item_id', $item->id)->exists()){
                        $pivot = $this->record->items()->where('item_id', $item->id)->first()->pivot;
                        $originalQuantityForItem = $pivot->quantity;
                    }

                    // Stok yang "tersedia" untuk item ini adalah stok_db + original_qty_di_faktur_ini
                    // Jika kuantitas baru > (stok_db + original_qty_di_faktur_ini), maka user mencoba mengambil lebih banyak dari yang ada + yang sudah dialokasikan.
                    // Tapi ini menjadi rumit. Validasi paling aman adalah $quantityInForm > $item->stock (stok aktual di DB).
                    // Jika user mengurangi kuantitas dari faktur, $item->stock akan bertambah di afterSave.
                    // Jika user menambah kuantitas, $item->stock akan berkurang di afterSave.
                    // Yang penting, $quantityInForm tidak boleh lebih besar dari $item->stock saat ini, *kecuali*
                    // jika sebagian dari $quantityInForm itu sudah dialokasikan ke faktur ini.

                    // Validasi untuk Edit: langsung bandingkan dengan stok aktual di DB.
                    // Logika penyesuaian (tambah/kurang) stok ada di afterSave.
                    // Di sini kita pastikan permintaan baru tidak melebihi apa yang TERSISA di gudang.
                    // Jika user mengurangi kuantitas, $quantityInForm akan lebih kecil, dan $item->stock di DB akan bertambah nanti.
                    // Jika user menambah kuantitas, $quantityInForm akan lebih besar, dan $item->stock di DB akan berkurang nanti.
                    // Yang penting, permintaan $quantityInForm tidak boleh melebihi $item->stock aktual di DB KECUALI
                    // sebagian dari $quantityInForm itu sudah dialokasikan ke faktur ini.
                    // Untuk mutateDataBeforeSave, kita harus memastikan bahwa penambahan bersih tidak melebihi stok yang ada.

                    $originalQuantityOnInvoice = 0;
                    if ($this->record) {
                        $existingItemPivot = $this->record->items()->where('item_id', $item->id)->first();
                        if ($existingItemPivot) {
                            $originalQuantityOnInvoice = (int)$existingItemPivot->pivot->quantity;
                        }
                    }

                    // Jumlah bersih yang ingin diambil dari stok (di luar apa yang sudah ada di faktur)
                    $netQuantityRequestedFromStock = $quantityInForm - $originalQuantityOnInvoice;

                    if ($netQuantityRequestedFromStock > $item->stock) {
                         throw ValidationException::withMessages([
                            "data.items.{$key}.quantity" => "Stok {$item->name} hanya {$item->stock} {$item->unit}. Anda mencoba mengambil tambahan {$netQuantityRequestedFromStock} {$item->unit} padahal kuantitas sebelumnya {$originalQuantityOnInvoice} {$item->unit}.",
                        ]);
                    }
                    // Jika netQuantityRequestedFromStock negatif, berarti user mengurangi jumlah item, yang selalu valid dari segi stok.
                    // Jika netQuantityRequestedFromStock positif, maka harus <= $item->stock.

                } elseif (!$item && !empty($invoiceItem['item_id'])) {
                    throw ValidationException::withMessages([
                        "data.items.{$key}.item_id" => "Item yang dipilih tidak valid atau tidak ditemukan.",
                    ]);
                }
            }
        }
        return $data;
    }

    protected function beforeSave(): void
    {
        // Store original quantities before saving
        // Ini mungkin tidak lagi diperlukan jika validasi utama ada di mutateDataBeforeSave
        // atau perlu disesuaikan. Untuk sekarang, biarkan.
        // $this->originalItemsQuantities = $this->getRecord()->items->pluck('pivot.quantity', 'id')->toArray();
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
