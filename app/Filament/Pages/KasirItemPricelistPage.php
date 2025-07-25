<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ProductResource;
use App\Models\Item;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Pages\Page;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table; // Alias for form component select
use Filament\Tables\Actions\ViewAction;


class KasirItemPricelistPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Pricelist Kasir';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $title = 'Daftar Harga & Stok Barang';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.kasir-item-pricelist-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->join('products', 'items.product_id', '=', 'products.id')
                    ->join('type_items', 'products.type_item_id', '=', 'type_items.id')
                    ->with(['product', 'product.typeItem'])
                    ->select('items.*') // Ensure we only select columns from items to avoid conflicts
            )
            ->heading('Daftar Harga & Stok Semua Item')
            ->description('Tampilan semua item/varian yang bisa dijual untuk referensi kasir.')
            ->recordUrl(fn (Item $record): string => ItemResource::getUrl('view', ['record' => $record]), true)
            ->columns([
                TextColumn::make('product_name_with_variant')
                    ->label('Nama Produk')
                    ->searchable(['products.name', 'products.brand', 'items.name']) // Searchable on related fields
                    ->sortable(['products.name']) // Sortable by product name
                    ->weight('bold')
                    ->getStateUsing(function (Item $record): string {
                        $variantName = $record->name;

                        return $variantName && $variantName !== null
                            ? "{$record->product->name} - {$variantName}"
                            : $record->product->name;
                    })
                    ->description(fn (Item $record): ?string => $record->product->brand ? "Merek: {$record->product->brand}" : null),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('product.typeItem.name')
                    ->label('Kategori')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->currency('IDR')
                    ->sortable()
                    ->weight('semibold')
                    ->color('secondary'),

                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->currency('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state, Item $record): string => match (true) {
                        $state > $record->minimum_stock => 'success',
                        $state > 0 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state, Item $record): string => "{$state} {$record->unit}"),

            ])
            ->filters([
                SelectFilter::make('type_item_id')
                    ->label('Kategori Produk')
                    ->relationship('product.typeItem', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('product_id')
                    ->label('Produk Induk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('stock_status')
                    ->label('Status Stok')
                    ->form([
                        FormSelect::make('stock_type') // Using aliased FormSelect
                            ->label('Status')
                            ->options([
                                'available' => 'Tersedia',
                                'low_stock' => 'Stok Menipis',
                                'out_of_stock' => 'Habis',
                            ])
                            ->placeholder('Semua Status'),
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['stock_type'])) {
                            return $query;
                        }

                        return match ($data['stock_type']) {
                            'available' => $query->whereColumn('items.stock', '>', 'items.minimum_stock'),
                            'low_stock' => $query->whereColumn('items.stock', '<=', 'items.minimum_stock')->where('items.stock', '>', 0),
                            'out_of_stock' => $query->where('items.stock', '<=', 0),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                // No bulk actions needed for a pricelist view
            ])
            ->defaultSort('products.name') // Default sort by product name
            ->emptyStateHeading('Belum ada item/varian produk di sistem')
            ->emptyStateDescription('Data item akan muncul di sini setelah ditambahkan.');
    }
}
