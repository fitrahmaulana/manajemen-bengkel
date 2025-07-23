<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->whereColumn('stock', '<=', 'minimum_stock')
                    ->orderBy('stock', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Nama Barang'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Spesifikasi'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok Saat Ini'),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->label('Stok Minimum'),
            ]);
    }
}
