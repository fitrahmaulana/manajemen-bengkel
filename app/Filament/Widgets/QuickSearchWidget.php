<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class QuickSearchWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->with(['product', 'product.typeItem'])
                    ->whereHas('product')
                    ->limit(10)
            )
            ->heading('🔍 Pencarian Cepat Barang')
            ->description('10 barang terbaru untuk akses cepat')
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nama Barang')
                    ->getStateUsing(function ($record) {
                        $productName = $record->product->name ?? 'Unknown';
                        $variant = $record->name;
                        
                        if ($variant && $variant !== 'Belum Ada Varian') {
                            return $productName . ' - ' . $variant;
                        }
                        
                        return $productName;
                    })
                    ->searchable(['products.name', 'items.name', 'items.sku'])
                    ->sortable(['products.name']),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga')
                    ->currency('IDR')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->formatStateUsing(fn($state, $record) => $state . ' ' . $record->unit)
                    ->badge()
                    ->color(fn($record) => $record->stock <= 0 ? 'danger' : ($record->stock <= 5 ? 'warning' : 'success')),
            ])
            ->actions([
                Tables\Actions\Action::make('viewInInventory')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => route('filament.admin.resources.inventory.index', ['tableSearch' => $record->sku]))
                    ->openUrlInNewTab(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\Action::make('viewAll')
                    ->label('Lihat Semua Inventory')
                    ->icon('heroicon-o-squares-2x2')
                    ->url(route('filament.admin.resources.inventory.index'))
                    ->color('primary'),
            ])
            ->paginated(false);
    }
}
