<?php

namespace App\Filament\Imports;

use App\Models\Item;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\TypeItem;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

class ItemImporter extends Importer
{
    protected static ?string $model = Item::class;

    public static function getColumns(): array
    {
        return [
            // ===== PRODUCT (relasi) =====
            ImportColumn::make('product')
                ->label('Nama Produk')
                ->requiredMapping()
                ->relationship(resolveUsing: function (
                    ?string $state,                              // nilai dari kolom yang user mapping ke "Nama Produk"
                    array $data,                                 // data yang sudah diproses (kolom lain)
                    array $originalData                          // raw CSV data baris ini
                ) {
                    if (blank($state)) {
                        return null;
                    }

                    // Cari / buat TypeItem dari kolom CSV "product_type_item_name" (kalau ada)
                    $typeName = $data['product_type_item_name'] ?? $originalData['product_type_item_name'] ?? null;
                    $type = null;
                    if (filled($typeName)) {
                        $type = TypeItem::firstOrCreate(
                            ['name' => $typeName],
                            ['description' => $data['product_type_item_desc'] ?? $originalData['product_type_item_desc'] ?? null]
                        );
                    }

                    // Cari / buat Product dari nama
                    $product = Product::firstOrNew(['name' => $state]);

                    // Isi atribut tambahan jika tersedia
                    if (isset($data['product_brand'])) {
                        $product->brand = $data['product_brand'];
                    }
                    if (isset($data['product_description'])) {
                        $product->description = $data['product_description'];
                    }
                    if ($type) {
                        $product->type_item_id = $type->id;
                    }

                    // Jika model Anda punya kolom `has_variant`, pastikan kolom itu memang ada di DB.
                    // Jika sebenarnya tidak ada, hapus baris di bawah.
                    $product->has_variants = true;

                    $product->save();

                    return $product;
                }),

            // Kolom pendukung Product yang *tidak* disimpan langsung ke Item.
            ImportColumn::make('product_brand')
                ->label('Brand Produk')
                ->fillRecordUsing(function () {
                    // sengaja kosong -> jangan isi ke Item
                }),
            ImportColumn::make('product_description')
                ->label('Deskripsi Produk')
                ->fillRecordUsing(function () {
                    //
                }),
            ImportColumn::make('product_type_item_name')
                ->label('Tipe Produk')
                ->fillRecordUsing(function () {
                    //
                }),

            // ===== VARIAN / ITEM =====
            ImportColumn::make('name')
                ->label('Nama Varian'),

            ImportColumn::make('sku')
                ->label('SKU Varian')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('purchase_price')
                ->label('Harga Beli Varian')
                ->numeric(),

            ImportColumn::make('selling_price')
                ->label('Harga Jual Varian')
                ->numeric(),

            ImportColumn::make('stock')
                ->label('Stok Varian')
                ->numeric(),

            ImportColumn::make('minimum_stock')
                ->label('Stok Minimum')
                ->numeric(),

            ImportColumn::make('unit')
                ->label('Satuan Varian'),

            ImportColumn::make('volume_value')
                ->label('Nilai Volume')
                ->numeric(),

            ImportColumn::make('base_volume_unit')
                ->label('Satuan Volume Dasar'),

            // ===== SUPPLIER (relasi) =====
            ImportColumn::make('supplier')
                ->label('Supplier')
                ->relationship(resolveUsing: function (?string $state) {
                    if (blank($state)) {
                        return null;
                    }

                    return Supplier::firstOrCreate(['name' => $state]);
                }),
        ];
    }

    /**
     * Cari Item berdasarkan SKU, atau buat baru.
     */
    public function resolveRecord(): ?Item
    {
        return Item::firstOrNew([
            'sku' => $this->data['sku'],
        ]);
    }

    /**
     * (Opsional) Hook sebelum simpan – bisa dipakai untuk normalisasi angka, dsb.
     */
    protected function beforeSave(): void
    {
        // contoh: pastikan stok >= 0
        if (($this->record->stock ?? 0) < 0) {
            $this->record->stock = 0;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your item import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
