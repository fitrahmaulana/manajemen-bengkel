<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;

class InventoryResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Kasir';
    protected static ?string $navigationLabel = 'Inventory Kasir';
    protected static ?string $modelLabel = 'Inventory';
    protected static ?string $pluralModelLabel = 'Inventory Kasir';
    protected static ?int $navigationSort = 1;

    // Disable create/edit for kasir
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->with(['product', 'product.typeItem'])
                    ->whereHas('product') // Only items with valid products
            )
            ->heading('🛒 Inventory Kasir')
            ->description('Semua barang dengan harga dan stok untuk kasir. Data real-time dan optimized untuk pencarian cepat.')
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('🏷️ Nama Barang')
                    ->searchable(['products.name', 'products.brand', 'items.name', 'items.sku'])
                    ->sortable(['products.name'])
                    ->weight('bold')
                    ->size('lg')
                    ->getStateUsing(function ($record) {
                        $productName = $record->product->name ?? 'Unknown Product';
                        $variant = $record->name;
                        $brand = $record->product->brand;

                        // Format: "Oli Shell HX7 - 1 Liter (Shell)"
                        if ($variant && $variant !== 'Belum Ada Varian') {
                            $display = $productName . ' - ' . $variant;
                        } else {
                            $display = $productName;
                        }

                        if ($brand) {
                            $display .= " ({$brand})";
                        }

                        return $display;
                    })
                    ->description(fn($record) => $record->sku)
                    ->color(fn($record) => $record->name === 'Belum Ada Varian' ? 'warning' : null),

                Tables\Columns\TextColumn::make('product.typeItem.name')
                    ->label('📂 Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('💰 Harga Jual')
                    ->currency('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->color('success')
                    ->copyable()
                    ->copyMessage('Harga berhasil disalin'),

                Tables\Columns\TextColumn::make('stock_display')
                    ->label('📦 Stok')
                    ->alignCenter()
                    ->sortable(['stock'])
                    ->getStateUsing(fn($record) => $record->stock . ' ' . $record->unit)
                    ->badge()
                    ->size('lg')
                    ->color(function ($record) {
                        $stock = $record->stock;
                        if ($stock <= 0) return 'danger';
                        if ($stock <= 5) return 'warning';
                        if ($stock <= 20) return 'info';
                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('💸 Harga Beli')
                    ->currency('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_convertible')
                    ->label('🔄 Bisa Dipecah')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->groups([
                Tables\Grouping\Group::make('product.typeItem.name')
                    ->label('Kategori')
                    ->collapsible(),
            ])
            ->defaultGroup('product.typeItem.name')
            ->filters([
                Tables\Filters\SelectFilter::make('product.type_item_id')
                    ->label('Kategori')
                    ->relationship('product.typeItem', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('stock_status')
                    ->label('Status Stok')
                    ->form([
                        Forms\Components\Select::make('stock_type')
                            ->label('Status')
                            ->options([
                                'available' => '✅ Tersedia (>20)',
                                'low_stock' => '⚠️ Stok Menipis (1-20)', 
                                'critical' => '🚨 Stok Kritis (1-5)',
                                'out_of_stock' => '❌ Habis (0)',
                            ])
                            ->multiple(),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['stock_type'])) {
                            $query->where(function ($q) use ($data) {
                                foreach ($data['stock_type'] as $type) {
                                    switch ($type) {
                                        case 'available':
                                            $q->orWhere('stock', '>', 20);
                                            break;
                                        case 'low_stock':
                                            $q->orWhere(function ($sq) {
                                                $sq->where('stock', '>', 5)->where('stock', '<=', 20);
                                            });
                                            break;
                                        case 'critical':
                                            $q->orWhere(function ($sq) {
                                                $sq->where('stock', '>', 0)->where('stock', '<=', 5);
                                            });
                                            break;
                                        case 'out_of_stock':
                                            $q->orWhere('stock', '<=', 0);
                                            break;
                                    }
                                }
                            });
                        }
                    }),

                Tables\Filters\Filter::make('price_range')
                    ->label('Range Harga')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('price_from')
                                ->label('Dari')
                                ->numeric()
                                ->prefix('Rp'),
                            Forms\Components\TextInput::make('price_to')
                                ->label('Sampai')
                                ->numeric()
                                ->prefix('Rp'),
                        ]),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['price_from']) {
                            $query->where('selling_price', '>=', $data['price_from']);
                        }
                        if ($data['price_to']) {
                            $query->where('selling_price', '<=', $data['price_to']);
                        }
                    }),

                Tables\Filters\Filter::make('convertible')
                    ->label('Bisa Dipecah')
                    ->query(fn($query) => $query->whereNotNull('target_child_item_id'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('viewDetail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->infolist([
                        InfolistSection::make('Informasi Barang')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextEntry::make('display_name')
                                    ->label('Nama Lengkap')
                                    ->getStateUsing(function ($record) {
                                        $productName = $record->product->name ?? 'Unknown Product';
                                        $variant = $record->name;
                                        $brand = $record->product->brand;

                                        if ($variant && $variant !== 'Belum Ada Varian') {
                                            $display = $productName . ' - ' . $variant;
                                        } else {
                                            $display = $productName;
                                        }

                                        if ($brand) {
                                            $display .= " ({$brand})";
                                        }

                                        return $display;
                                    }),
                                TextEntry::make('sku')
                                    ->label('Kode Barang')
                                    ->copyable(),
                                TextEntry::make('product.typeItem.name')
                                    ->label('Kategori'),
                                TextEntry::make('unit')
                                    ->label('Satuan'),
                            ]),
                        InfolistSection::make('Harga & Stok')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                TextEntry::make('purchase_price')
                                    ->label('Harga Beli')
                                    ->money('IDR'),
                                TextEntry::make('selling_price')
                                    ->label('Harga Jual')
                                    ->money('IDR'),
                                TextEntry::make('stock')
                                    ->label('Stok')
                                    ->formatStateUsing(fn($state, $record) => $state . ' ' . $record->unit),
                                TextEntry::make('profit_margin')
                                    ->label('Margin Keuntungan')
                                    ->getStateUsing(function ($record) {
                                        if ($record->purchase_price > 0) {
                                            $margin = (($record->selling_price - $record->purchase_price) / $record->purchase_price) * 100;
                                            return number_format($margin, 1) . '%';
                                        }
                                        return 'N/A';
                                    }),
                            ])
                            ->columns(2),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Tables\Actions\Action::make('copyPrice')
                    ->label('Copy Harga')
                    ->icon('heroicon-o-clipboard')
                    ->color('success')
                    ->action(function ($record) {
                        // This will be handled by the copyable feature on the column
                    })
                    ->hidden(), // Hide this since we have copyable on column
            ])
            ->bulkActions([])
            ->recordUrl(null)
            ->poll('30s') // Auto refresh every 30 seconds
            ->emptyStateHeading('Tidak ada barang')
            ->emptyStateDescription('Belum ada barang yang tersedia untuk ditampilkan.')
            ->emptyStateIcon('heroicon-o-cube-transparent')
            ->defaultSort('selling_price', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventory::route('/'),
        ];
    }
}
