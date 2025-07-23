<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ProductResource;
use App\Models\Item;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Pages\Page;
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
            ->columns([
                TextColumn::make('product_name_with_variant')
                    ->label('Nama Produk')
                    ->searchable(['products.name', 'products.brand', 'items.name']) // Searchable on related fields
                    ->sortable(['products.name']) // Sortable by product name
                    ->weight('bold')
                    ->getStateUsing(function (Item $record) {
                        $productName = $record->product->name;
                        $variantName = $record->name; // Item's own name is the variant spec

                        if ($variantName && $variantName !== 'Standard' && ! is_null($variantName)) {
                            return $productName.' - '.$variantName;
                        }

                        // If item's name is null, 'Standard', or empty, just show product name
                        return $productName;
                    })
                    ->description(fn (Item $record) => $record->product->brand ? "Merek: {$record->product->brand}" : ''),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('product.typeItem.name')
                    ->label('Kategori')
                    ->searchable(query: function ($query, string $search) {
                        // Custom search for relationship
                        return $query->whereHas('product.typeItem', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable() // Make sure alias or actual column name is sortable
                    ->badge()
                    ->color('success'),

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
                    ->color(fn ($state) => $state > 20 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state, Item $record) => $state.' '.$record->unit),
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
                                'available' => 'Tersedia (>20)',
                                'low_stock' => 'Stok Menipis (1-20)',
                                'out_of_stock' => 'Habis (0)',
                            ])
                            ->placeholder('Semua Status'),
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['stock_type'])) {
                            return $query;
                        }

                        return match ($data['stock_type']) {
                            'available' => $query->where('items.stock', '>', 20),
                            'low_stock' => $query->where('items.stock', '>', 0)->where('items.stock', '<=', 20),
                            'out_of_stock' => $query->where('items.stock', '<=', 0),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                ViewAction::make()->url(fn (Item $record): string => ItemResource::getUrl('view', ['record' => $record->id])),
            ])
            ->bulkActions([
                // No bulk actions needed for a pricelist view
            ])
            ->defaultSort('products.name') // Default sort by product name
            ->emptyStateHeading('Belum ada item/varian produk di sistem')
            ->emptyStateDescription('Data item akan muncul di sini setelah ditambahkan.');
    }
}
