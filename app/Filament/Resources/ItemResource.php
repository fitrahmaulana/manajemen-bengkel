<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ItemsRelationManager;
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

use function Laravel\Prompts\text;
use function Livewire\on;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Varian Barang';
    protected static ?string $modelLabel = 'Varian';
    protected static ?string $pluralModelLabel = 'Daftar Varian';
    protected static ?int $navigationSort = 2;

    public static function roundUpToNearestHundred($number)
    {
        if (!is_numeric($number) || $number <= 0) {
            return 0;
        }
        return ceil($number / 100) * 100;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([                Forms\Components\Section::make('Informasi Barang')
                    ->description('Masukkan detail barang yang akan ditambahkan ke inventory')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->hiddenOn(ItemsRelationManager::class)
                            ->label('Nama Barang')
                            ->placeholder('Cari atau buat barang baru')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Contoh: Oli Mesin, Filter Udara, Busi, dll.')
                            ->default(fn() => request()->get('product_id')) // Pre-fill dari URL parameter
                            ->live() // Reactive untuk auto-generate SKU
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                // Auto-generate SKU ketika produk dipilih
                                if ($state && !$get('sku')) {
                                    $product = \App\Models\Product::find($state);
                                    $productName = $product ? $product->name : '';
                                    $variantName = $get('name') ?: 'STD';

                                    if ($productName) {
                                        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 6));
                                        $variantCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $variantName), 0, 3));
                                        $sku = $productCode . '-' . $variantCode;
                                        $set('sku', $sku);
                                    }
                                }
                            })                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Barang')
                                    ->placeholder('Contoh: Oli Mesin Castrol GTX')
                                    ->required(),
                                Forms\Components\TextInput::make('brand')
                                    ->label('Merek')
                                    ->placeholder('Contoh: Castrol, Shell, Mobil 1'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Deskripsi singkat barang ini'),
                                Forms\Components\Select::make('type_item_id')
                                    ->label('Kategori Barang')
                                    ->relationship('typeItem', 'name')
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return \App\Models\Product::create($data)->id;
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label('Spesifikasi / Ukuran')
                            ->placeholder('Contoh: 1 Liter, 5W-30, atau kosongkan jika tidak ada')
                            ->helperText('Isi jika barang memiliki ukuran/spesifikasi khusus (1L, 4L, SAE 20W-50, dll)')
                            ->live() // Reactive untuk auto-generate SKU
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                // Auto-generate SKU ketika varian berubah
                                $productId = $get('product_id');
                                if ($productId && !$get('sku')) {
                                    $product = \App\Models\Product::find($productId);
                                    $productName = $product ? $product->name : '';
                                    $variantName = $state ?: 'STD';

                                    if ($productName) {
                                        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 6));
                                        $variantCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $variantName), 0, 3));
                                        $sku = $productCode . '-' . $variantCode;
                                        $set('sku', $sku);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('sku')
                            ->label('Kode Barang')
                            ->placeholder('Akan otomatis terisi')
                            ->helperText('Kode unik untuk identifikasi barang (otomatis dibuat)')
                            ->required()
                            ->unique(ignoreRecord: true),
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
                                    ->helperText('Jumlah barang yang tersedia')
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
                                    ->default('Pcs'),
                            ]),
                    ]),

                // Checkbox untuk mengaktifkan konversi stok
                Forms\Components\Section::make('Pengaturan Tambahan')
                    ->schema([
                        Forms\Components\Checkbox::make('enable_conversion')
                            ->label('Barang ini bisa dipecah ke eceran')
                            ->helperText('Centang jika barang kemasan besar bisa dipecah (contoh: 1 Galon = 4 Liter)')
                            ->live()
                            ->dehydrated(false) // Tidak disimpan ke database
                            ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                                // Set checkbox berdasarkan apakah sudah ada conversion_value
                                $set('enable_conversion', !empty($get('conversion_value')));
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if (!$state) {
                                    // Reset conversion fields when disabled
                                    $set('conversion_value', null);
                                    $set('target_child_item_id', null);
                                }
                            }),
                    ]),

                // Konversi stok - hanya muncul jika checkbox dicentang
                Forms\Components\Section::make('Pengaturan Konversi Stok')
                    ->description('Atur bagaimana barang ini bisa dipecah menjadi eceran')
                    ->schema([
                        Forms\Components\Placeholder::make('conversion_info')
                            ->label('')
                            ->content('💡 Contoh: 1 Galon oli bisa dipecah menjadi 4 Liter oli eceran')
                            ->columnSpanFull(),
                        Forms\Components\Group::make()->schema([
                            Forms\Components\TextInput::make('conversion_value')
                                ->label('Nilai Konversi')
                                ->placeholder('Contoh: 4')
                                ->numeric()
                                ->helperText(
                                    fn(Forms\Get $get): string =>
                                    'Berapa unit eceran yang dihasilkan dari 1 ' . ($get('unit') ?: 'unit') . ' barang ini?'
                                )
                                ->suffix(function (Forms\Get $get) {
                                    $targetItemId = $get('target_child_item_id');
                                    if ($targetItemId && $item = Item::find($targetItemId)) {
                                        return $item->unit;
                                    }
                                    return 'unit eceran';
                                })
                                ->live(onBlur: true),

                            Forms\Components\Select::make('target_child_item_id')
                                ->label('Barang Eceran')
                                ->placeholder('Pilih barang eceran yang sudah ada')
                                ->relationship(
                                    name: 'targetChild',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn(Builder $query) => $query->whereNull('target_child_item_id')
                                )
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $productName = $record->product?->name ?? '';
                                    $variantName = $record->name ?? '';
                                    return trim("{$productName} - {$variantName}");
                                })
                                ->searchable()
                                ->preload()
                                ->helperText('Barang eceran yang akan bertambah stoknya')
                                ->createOptionForm(fn(Forms\Get $get): array => [
                                    Forms\Components\Placeholder::make('info')
                                        ->label('')
                                        ->content('Membuat barang eceran baru')
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('product_id')
                                        ->label('Nama Barang')
                                        ->default($get('product_id'))
                                        ->relationship('product', 'name')
                                        ->required()
                                        ->disabled(),
                                    Forms\Components\TextInput::make('name')
                                        ->label('Spesifikasi Eceran')
                                        ->placeholder('Contoh: 1 Liter, Eceran')
                                        ->required()
                                        ->default('Eceran'),
                                    Forms\Components\TextInput::make('sku')
                                        ->label('Kode Barang Eceran')
                                        ->placeholder('Akan otomatis terisi')
                                        ->required()
                                        ->unique(table: Item::class, column: 'sku')
                                        ->default($get('sku') ? $get('sku') . '-ECER' : null),
                                    Forms\Components\Select::make('unit')
                                        ->label('Satuan Eceran')
                                        ->required()
                                        ->options([
                                            'Liter' => 'Liter',
                                            'Ml' => 'Ml',
                                            'Pcs' => 'Pcs',
                                        ])
                                        ->default('Liter'),
                                    Forms\Components\TextInput::make('purchase_price')
                                        ->label('Harga Beli Eceran')
                                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                        ->prefix('Rp')
                                        ->required()
                                        ->default(function () use ($get) {
                                            $parentPurchasePrice = $get('purchase_price');
                                            $conversionValue = $get('conversion_value');
                                            if (is_numeric($parentPurchasePrice) && is_numeric($conversionValue) && $conversionValue > 0) {
                                                return round($parentPurchasePrice / $conversionValue, 2);
                                            }
                                            return 0;
                                        })
                                        ->helperText('Harga otomatis dihitung, bisa diubah'),
                                    Forms\Components\TextInput::make('selling_price')
                                        ->label('Harga Jual Eceran')
                                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                        ->prefix('Rp')
                                        ->required()
                                        ->default(function () use ($get) {
                                            $parentSellingPrice = $get('selling_price');
                                            $conversionValue = $get('conversion_value');
                                            if (is_numeric($parentSellingPrice) && is_numeric($conversionValue) && $conversionValue > 0) {
                                                return self::roundUpToNearestHundred($parentSellingPrice / $conversionValue);
                                            }
                                            return 0;
                                        })
                                        ->helperText('Harga otomatis dihitung dan dibulatkan'),
                                ])
                                ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                    $eceranData = [
                                        'product_id' => $get('product_id'),
                                        'name' => $data['name'],
                                        'sku' => $data['sku'],
                                        'unit' => $data['unit'],
                                        'selling_price' => $data['selling_price'],
                                        'purchase_price' => $data['purchase_price'],
                                        'stock' => 0,
                                    ];

                                    $newItem = Item::create($eceranData);
                                    return $newItem->id;
                                })
                                ->live(),
                        ]),
                    ])
                    ->visible(fn(Forms\Get $get): bool => $get('enable_conversion') === true),
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
                    ->formatStateUsing(fn (?string $state): string => $state === 'Standard' || empty($state) ? '-' : $state)
                    ->description(fn ($record): string => ($record->name === 'Standard' || empty($record->name)) ? 'Tidak ada spesifikasi' : ''),
                Tables\Columns\TextColumn::make('sku')
                    ->label('Kode Barang')
                    ->searchable(),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('pecahStok')
                    ->label('Pecah 1 Unit Stok')
                    ->icon('heroicon-o-arrows-right-left')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pecah Stok')
                    ->modalDescription('Apakah Anda yakin ingin memecah 1 unit dari item ini? Stok item eceran target akan bertambah sesuai nilai konversi.')
                    ->modalSubmitActionLabel('Ya, Pecah')
                    ->action(function (Item $record) {
                        $sourceItem = $record;
                        $targetItem = $sourceItem->targetChild; // Using the BelongsTo relationship

                        if (!$targetItem) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body('Target item eceran belum diatur untuk item induk ini.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!$sourceItem->conversion_value || $sourceItem->conversion_value <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body("Nilai konversi untuk {$sourceItem->name} tidak valid atau belum diatur.")
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($sourceItem->stock < 1) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body("Stok {$sourceItem->name} tidak mencukupi untuk dipecah (kurang dari 1).")
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($sourceItem, $targetItem) {
                                $sourceItem->decrement('stock', 1);
                                $targetItem->increment('stock', $sourceItem->conversion_value);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Pecah Stok')
                                ->success()
                                ->body("1 {$sourceItem->unit} {$sourceItem->name} telah dipecah. Stok {$targetItem->name} bertambah {$sourceItem->conversion_value} {$targetItem->unit} (satuan eceran).")
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body('Terjadi kesalahan internal saat memecah stok: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn(Item $record): bool =>
                        $record->target_child_item_id !== null &&
                            $record->conversion_value > 0
                    ),
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
                            ->formatStateUsing(fn (?string $state): string => ($state === 'Standard' || empty($state)) ? 'Tidak ada spesifikasi' : $state)
                            ->color(fn (?string $state): string => ($state === 'Standard' || empty($state)) ? 'gray' : 'primary'),
                        TextEntry::make('product.typeItem.name')->label('Kategori Barang'),
                        TextEntry::make('sku')->label('Kode Barang'),
                        TextEntry::make('product.brand')->label('Merek'),
                        // Status konversi
                        IconEntry::make('is_convertible')
                            ->label('Bisa Dipecah')
                            ->boolean()
                            ->trueIcon('heroicon-o-arrows-right-left')
                            ->trueColor('success')
                            ->falseIcon('heroicon-o-cube')
                            ->falseColor('gray')
                            ->helperText(fn($state) => $state ? 'Bisa dipecah ke eceran' : 'Tidak bisa dipecah')
                            ->getStateUsing(fn($record) => $record->is_convertible),
                    ]),

                InfolistSection::make('Harga & Stok')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('stock')
                            ->label('Stok Saat Ini')
                            ->badge()
                            ->color(fn(string $state): string => $state <= 5 ? 'warning' : 'success')
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

                // Detail konversi - hanya muncul jika bisa dipecah
                InfolistSection::make('Detail Konversi')
                    ->visible(fn($record) => $record->is_convertible)
                    ->schema([
                        TextEntry::make('conversion_value')
                            ->label('Nilai Konversi')
                            ->helperText(function ($record) {
                                $childName = $record->targetChild?->name ?? '...';
                                $childUnit = $record->targetChild?->unit ?? '...';
                                return "1 {$record->unit} akan menghasilkan {$record->conversion_value} {$childUnit} {$childName}";
                            }),
                        TextEntry::make('targetChild.name')
                            ->label('Barang Eceran')
                            ->formatStateUsing(function ($record) {
                                if ($record->targetChild) {
                                    return $record->targetChild->product->name . ' - ' . $record->targetChild->name;
                                }
                                return '-';
                            }),
                    ]),
            ]);
    }
}
