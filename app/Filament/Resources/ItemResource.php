<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ItemsRelationManager;
// use App\Filament\Resources\ProductResource\RelationManagers\ItemsRelationManager; // No longer directly needed here
use App\Models\Item;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\InventoryService;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput as FormsTextInput;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\text;
use function Livewire\on;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Hidden';
    protected static ?string $navigationLabel = 'Varian Barang';
    protected static ?string $modelLabel = 'Varian';
    protected static ?string $pluralModelLabel = 'Daftar Varian';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $lowStockCount = Item::whereColumn('stock', '<=', 'minimum_stock')->count();
        return $lowStockCount > 0 ? (string) $lowStockCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Barang')
                    ->description('Masukkan detail barang yang akan ditambahkan ke inventory')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            // ->hiddenOn(ItemsRelationManager::class) // This is no longer relevant as RM has its own form
                            ->label('Nama Barang')
                            ->placeholder('Cari Barang')
                            ->relationship('product', 'name')
                            ->hiddenOn(ItemsRelationManager::class)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Contoh: Oli Mesin, Filter Udara, Busi, dll.'),
                        Forms\Components\TextInput::make('name')
                            ->label('Spesifikasi / Ukuran')
                            ->helperText('Isi jika barang memiliki ukuran/spesifikasi khusus (1L, 4L, SAE 20W-50, dll)'),
                        Forms\Components\TextInput::make('sku')
                            ->label('Kode Barang')
                            ->helperText('Kode unik untuk identifikasi barang')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->placeholder('Pilih Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih supplier untuk barang ini (opsional).'),
                    ]),

                Forms\Components\Section::make('Harga & Stok')
                    ->description('Tentukan harga dan jumlah stok barang')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Harga Beli')
                                    ->placeholder('0')
                                    ->helperText('Harga beli dari supplier')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->prefix('Rp')
                                    ->required(),
                                Forms\Components\TextInput::make('selling_price')
                                    ->label('Harga Jual')
                                    ->placeholder('0')
                                    ->helperText('Harga jual ke customer')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->prefix('Rp')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stock')
                                    ->label('Jumlah Stok')
                                    ->placeholder('0')
                                    ->helperText('Jumlah barang yang tersedia (mendukung desimal seperti 3.5)')
                                    ->numeric()
                                    ->step(1) // Memungkinkan input desimal
                                    ->required()
                                    ->default(0),
                                Forms\Components\TextInput::make('minimum_stock')
                                    ->label('Stok Minimum')
                                    ->placeholder('0')
                                    ->helperText('Batas minimum stok sebelum notifikasi muncul')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                Forms\Components\Select::make('unit')
                                    ->label('Satuan')
                                    ->placeholder('Pilih satuan')
                                    ->helperText('Satuan untuk menghitung stok')
                                    ->required()
                                    ->options([
                                        'Pcs' => 'Pcs',
                                        'Botol' => 'Botol',
                                        'Galon' => 'Galon',
                                        'Liter' => 'Liter',
                                        'Ml' => 'Ml',
                                        'Set' => 'Set',
                                        'Drum' => 'Drum',
                                    ])
                                    ->default('Pcs')
                                    ->live(), // Make unit live to update volume_value placeholder
                                FormsTextInput::make('volume_value')
                                    ->label('Nilai Volume Standar')
                                    ->numeric()
                                    ->step('0.01')
                                    ->helperText(fn(Forms\Get $get) => 'Isi jika item ini memiliki representasi volume standar. Cth: Botol 1 Liter -> Nilai: 1, Satuan Standar: Liter. Atau 1 Dus isi 12 Pcs -> Nilai: 12, Satuan Standar: Pcs.')
                                    ->placeholder(fn(Forms\Get $get) => match (strtolower($get('unit'))) {
                                        'liter' => '1000 (jika satuan standar ml)',
                                        'ml' => 'Isi jumlah ml',
                                        'dus' => '12 (jika isi 12 pcs)',
                                        'pcs' => '1 (jika satuan standar pcs)',
                                        default => 'Contoh: 1000',
                                    }),
                                Select::make('base_volume_unit')
                                    ->label('Satuan Volume Standar')
                                    ->options([
                                        'ml' => 'Mililiter (ml)',
                                        'liter' => 'Liter (L)',
                                        'gram' => 'Gram (gr)',
                                        'kg' => 'Kilogram (kg)',
                                        'pcs' => 'Pieces (pcs)',
                                        'set' => 'Set',
                                    ])
                                    ->placeholder('Pilih satuan standar')
                                    ->helperText('Satuan acuan untuk perbandingan volume antar item.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Spesifikasi')
                    ->searchable()
                    ->formatStateUsing(fn(?string $state): string => $state === 'Standard' || empty($state) ? '-' : $state)
                    ->description(fn($record): string => ($record->name === 'Standard' || empty($record->name)) ? 'Tidak ada spesifikasi' : ''),
                Tables\Columns\TextColumn::make('sku')
                    ->label('Kode Barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('selling_price')
                    ->currency('IDR')
                    ->label('Harga Jual')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn(string $state): string => $state <= 5 ? 'warning' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.brand')
                    ->label('Merek')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Nanti kita bisa tambahkan filter di sini
            ])
            ->actions([
                Tables\Actions\Action::make('receiveStockFromConversion')
                    ->label('Terima Stok dari Induk')
                    ->icon('heroicon-o-arrow-down-on-square-stack') // Icon changed to reflect receiving
                    ->form([
                        Forms\Components\Placeholder::make('to_item_info')
                            ->label('Item Tujuan (Saat Ini)')
                            ->content(fn(Item $record): string => "{$record->display_name} (Stok: {$record->stock} {$record->unit})"),

                        Select::make('from_item_id')
                            ->label('Pilih Item Sumber (Induk)')
                            ->options(function (Item $record) {
                                if (!$record->product_id) {
                                    return []; // No product context, no source items
                                }
                                return Item::where('product_id', $record->product_id)
                                    ->where('id', '!=', $record->id) // Exclude self
                                    ->get()
                                    ->mapWithKeys(fn(Item $item) => [$item->id => $item->display_name . " (Stok: {$item->stock} {$item->unit})"]);
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state, Item $record) {
                                $fromItemId = $state;
                                $fromQuantityInput = $get('from_quantity');
                                $fromItem = $fromItemId ? Item::find($fromItemId) : null;

                                if ($fromItem && $fromQuantityInput && is_numeric($fromQuantityInput) && (float)$fromQuantityInput > 0) {
                                    $calculated = InventoryService::calculateTargetQuantity($fromItem, $record, (float)$fromQuantityInput);
                                    $set('calculated_to_quantity', $calculated);
                                } else {
                                    $set('calculated_to_quantity', null);
                                }
                                $set('to_quantity_unit_suffix', $record->unit);


                                // Update from_quantity max stock based on selected from_item
                                if ($fromItem) {
                                    $set('current_from_item_stock', $fromItem->stock);
                                    if ($get('from_quantity') > $fromItem->stock) {
                                        $set('from_quantity', $fromItem->stock);
                                        // Recalculate if capped
                                        $recalculated = InventoryService::calculateTargetQuantity($fromItem, $record, (float)$fromItem->stock);
                                        $set('calculated_to_quantity', $recalculated);
                                    }
                                } else {
                                    $set('current_from_item_stock', null);
                                }
                            }),

                        // Hidden field to store current stock of selected from_item for validation
                        Forms\Components\Hidden::make('current_from_item_stock')->default(null),

                        FormsTextInput::make('from_quantity')
                            ->label('Jumlah Item Sumber yang Akan Dikonversi')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(1)
                            ->maxValue(fn(Forms\Get $get) => $get('current_from_item_stock') ?? null) // Max based on selected item's stock
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state, Item $record) {
                                $fromItemId = $get('from_item_id');
                                $fromQuantityInput = $state;
                                $fromItem = $fromItemId ? Item::find($fromItemId) : null;

                                if ($fromItem && $fromQuantityInput && is_numeric($fromQuantityInput) && (float)$fromQuantityInput > 0) {
                                    $calculated = InventoryService::calculateTargetQuantity($fromItem, $record, (float)$fromQuantityInput);
                                    $set('calculated_to_quantity', $calculated);
                                } else {
                                    $set('calculated_to_quantity', null);
                                }
                                $set('to_quantity_unit_suffix', $record->unit);
                            }),

                        Forms\Components\Placeholder::make('to_quantity_display')
                            ->label('Jumlah Item Ini yang Akan Dihasilkan')
                            ->content(fn(Forms\Get $get) => $get('calculated_to_quantity') ? $get('calculated_to_quantity') . ' ' . $get('to_quantity_unit_suffix') : '-'),

                        // Hidden field to store the actual calculated to_quantity for submission
                        Forms\Components\Hidden::make('calculated_to_quantity')->default(null),
                        Forms\Components\Hidden::make('to_quantity_unit_suffix')->default(null),


                        Textarea::make('notes')
                            ->label('Catatan (Opsional)')
                            ->rows(3),
                    ])
                    ->action(function (array $data, Item $record, InventoryService $inventoryService) {
                        $calculatedToQuantity = $data['calculated_to_quantity'];

                        if (is_null($calculatedToQuantity) || $calculatedToQuantity <= 0) {
                            Notification::make()
                                ->title('Konversi Stok Gagal')
                                ->danger()
                                ->body('Jumlah item yang dihasilkan tidak valid atau tidak dapat dihitung. Pastikan data volume item sumber dan tujuan sudah benar dan satuan volume standar sama.')
                                ->send();
                            return;
                        }

                        try {
                            $conversion = $inventoryService->convertStock(
                                fromItemId: $data['from_item_id'],
                                toItemId: $record->id, // Current item is the target
                                fromQuantity: $data['from_quantity'],
                                toQuantity: $calculatedToQuantity, // Use the auto-calculated value
                                notes: $data['notes'],
                                userId: Auth::id()
                            );

                            Notification::make()
                                ->title('Konversi Stok Berhasil')
                                ->success()
                                ->body("{$conversion->from_quantity} {$conversion->fromItem->unit} {$conversion->fromItem->display_name} berhasil dikonversi menjadi {$conversion->to_quantity} {$conversion->toItem->unit} {$conversion->toItem->display_name}.")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Konversi Stok Gagal')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->tooltip('Aksi untuk varian ini')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view' => Pages\ViewItem::route('/{record}'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Barang')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('product.name')->label('Nama Barang'),
                        TextEntry::make('name')
                            ->label('Spesifikasi')
                            ->formatStateUsing(fn(?string $state): string => ($state === 'Standard' || empty($state)) ? 'Tidak ada spesifikasi' : $state)
                            ->color(fn(?string $state): string => ($state === 'Standard' || empty($state)) ? 'gray' : 'primary'),
                        TextEntry::make('product.typeItem.name')->label('Kategori Barang'),
                        TextEntry::make('sku')->label('Kode Barang'),
                        TextEntry::make('product.brand')->label('Merek'),
                        TextEntry::make('supplier.name')->label('Supplier'),
                        TextEntry::make('volume_value')
                            ->label('Nilai Volume Std.')
                            ->suffix(fn($record) => ' ' . $record->base_volume_unit)
                            ->placeholder('-')
                            ->visible(fn($record) => !is_null($record->volume_value)),
                    ]),

                InfolistSection::make('Harga & Stok')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('stock')
                            ->label('Stok Saat Ini')
                            ->badge()
                            ->color(fn(string $state): string => $state <= 5 ? 'warning' : 'success')
                            ->suffix(fn($record) => ' ' . $record->unit),

                        TextEntry::make('minimum_stock')
                            ->label('Stok Minimum')
                            ->badge()
                            ->color('danger')
                            ->suffix(fn($record) => ' ' . $record->unit),

                        TextEntry::make('purchase_price')
                            ->label('Harga Beli')
                            ->currency('IDR'),

                        TextEntry::make('selling_price')
                            ->label('Harga Jual')
                            ->currency('IDR')
                            ->weight('bold')
                            ->size('lg'),
                    ]),

                // Section "Detail Konversi" yang lama sudah dihapus
            ]);
    }
}
