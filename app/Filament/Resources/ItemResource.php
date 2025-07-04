<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;

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

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Barang';
    protected static ?string $modelLabel = 'Barang';
    protected static ?string $pluralModelLabel = 'Daftar Barang';
    protected static ?int $navigationSort = 1;

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
            ->schema([
                Forms\Components\Section::make('Informasi Dasar Barang')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produk')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required(),
                                Forms\Components\TextInput::make('brand')
                                    ->label('Merek'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi'),
                                Forms\Components\Select::make('type_item_id')
                                    ->label('Tipe Barang')
                                    ->relationship('typeItem', 'name')
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return \App\Models\Product::create($data)->id;
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Varian')
                            ->helperText('Contoh: 1 Liter, 4 Liter, Eceran')
                            ->required(),
                        // Grid untuk menempatkan SKU
                        Forms\Components\TextInput::make('sku')
                            ->label('Kode Barang (SKU)')
                            ->required()
                            ->unique(ignoreRecord: true), // Unik, tapi abaikan saat edit data yg sama
                    ]),

                Forms\Components\Section::make('Informasi Stok & Harga')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Harga Beli (Modal)')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->prefix('Rp')
                                    ->required(),
                                Forms\Components\TextInput::make('selling_price')
                                    ->label('Harga Jual')
                                    ->helperText('Harga jual jika barang ini dijual utuh (per Botol/Pcs/dll).')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->prefix('Rp')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stok Saat Ini')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                Forms\Components\Select::make('unit')
                                    ->label('Satuan Barang')
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

                Forms\Components\Section::make('Konversi Stok (Opsional)')
                    ->description('Gunakan fitur ini jika barang ini adalah kemasan besar yang bisa dipecah menjadi eceran.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Group::make()->schema([
                            Forms\Components\TextInput::make('conversion_value')
                                ->label('Nilai Konversi')
                                ->numeric()
                                ->helperText(
                                    fn(Forms\Get $get): string =>
                                    'Satu ' . ($get('unit') ?: 'unit') . ' barang ini akan menghasilkan berapa banyak satuan eceran?'
                                )
                                ->suffix(function (Forms\Get $get) {
                                    $targetItemId = $get('target_child_item_id');
                                    // Suffix akan update setelah item eceran dipilih
                                    if ($targetItemId && $item = Item::find($targetItemId)) {
                                        return $item->unit;
                                    }
                                    return '...';
                                })
                                ->live(onBlur: true), // Tetap reactive agar suffix bisa update

                            // 2. BARU PILIH TARGETNYA
                            Forms\Components\Select::make('target_child_item_id')
                                ->label('Hasil Pecahan Stok Menjadi Item:')
                                // Menggunakan relasi 'targetChild' untuk memilih item eceran tapi pake nama produk juga contoh product.name + targetchild.name
                                // Jadi lebih jelas bagi user
                                // Misal: "Oli HX7 1L - Eceran"
                                ->relationship(
                                    name: 'targetChild',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn(Builder $query) => $query->whereNull('target_child_item_id')
                                )
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    // Gabungkan nama produk dan nama varian eceran
                                    $productName = $record->product?->name ?? '';
                                    $variantName = $record->name ?? '';
                                    return trim("{$productName} - {$variantName}");
                                })
                                ->searchable()
                                ->preload()
                                ->helperText('Pilih item eceran yang stoknya akan bertambah.')
                                ->createOptionForm(fn(Forms\Get $get): array => [
                                    // Logika di dalam modal ini tidak berubah dan sekarang lebih andal
                                    Forms\Components\Select::make('product_id')
                                        ->label('Produk')
                                        ->default($get('product_id'))
                                        ->relationship('product', 'name')
                                        ->required()
                                        ->disabled(), // Disabled karena harus sama dengan produk induk
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nama Varian Eceran')
                                        ->required()
                                        ->default('Eceran'),
                                    Forms\Components\TextInput::make('sku')
                                        ->label('SKU Item Eceran Baru')
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
                                        ->label('Harga Beli (Modal) Eceran')
                                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                        ->prefix('Rp')
                                        ->required()
                                        ->default(function () use ($get) {
                                            // Menghitung Harga Beli (Modal) eceran berdasarkan Harga Beli (Modal) induk dan nilai konversi
                                            $parentPurchasePrice = $get('purchase_price');
                                            $conversionValue = $get('conversion_value');
                                            if (is_numeric($parentPurchasePrice) && is_numeric($conversionValue) && $conversionValue > 0) {
                                                return round($parentPurchasePrice / $conversionValue, 2);
                                            }
                                            return 0;
                                        })
                                        ->helperText('Harga disarankan berdasarkan harga induk. Silakan sesuaikan.'),
                                    Forms\Components\TextInput::make('selling_price')
                                        ->label('Harga Jual Eceran')
                                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                        ->prefix('Rp')
                                        ->required()
                                        ->default(function () use ($get) {
                                            // Menghitung harga jual eceran berdasarkan harga jual induk dan nilai konversi
                                            $parentSellingPrice = $get('selling_price');
                                            $conversionValue = $get('conversion_value');
                                            if (is_numeric($parentSellingPrice) && is_numeric($conversionValue) && $conversionValue > 0) {
                                                return self::roundUpToNearestHundred($parentSellingPrice / $conversionValue);
                                            }
                                            return 0;
                                        })
                                        ->helperText('Harga disarankan berdasarkan harga induk. Silakan sesuaikan.'),
                                ])
                                ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                    // Kode 'createOptionUsing' Anda sudah hampir benar.
                                    // Kita hanya perlu memastikan product_id dari induk ikut terbawa.

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
                                ->live(), // Select juga dibuat reactive agar suffix di atas bisa update

                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Varian')
                    ->searchable(), // Membuat kolom ini bisa dicari
                Tables\Columns\TextColumn::make('sku')
                    ->label('Kode Barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->currency('IDR')
                    ->label('Harga Jual')
                    ->sortable(), // Membuat kolom ini bisa diurutkan
                Tables\Columns\TextColumn::make('stock')
                    ->badge() // Tampilkan dalam bentuk badge
                    ->color(fn(string $state): string => $state <= 5 ? 'warning' : 'success') // Stok <= 5 jadi kuning, sisanya hijau
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.brand')
                    ->label('Merek')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Kolom bisa disembunyikan
                Tables\Columns\TextColumn::make('created_at')
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
                InfolistSection::make('Informasi Dasar Barang')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('product.name')->label('Produk'),
                        TextEntry::make('name')->label('Varian'),
                        TextEntry::make('product.typeItem.name')->label('Tipe Barang'),
                        TextEntry::make('sku')->label('Kode Barang (SKU)'),
                        TextEntry::make('product.brand')->label('Merek'),
                        // Contoh komponen canggih: Menampilkan status dengan ikon
                        IconEntry::make('is_convertible')
                            ->label('Status Konversi')
                            ->boolean()
                            ->trueIcon('heroicon-o-arrows-right-left')
                            ->trueColor('success')
                            ->falseIcon('heroicon-o-cube')
                            ->falseColor('gray')
                            ->helperText(fn($state) => $state ? 'Induk (dapat dipecah)' : 'Eceran/Satuan')
                            ->getStateUsing(fn($record) => $record->is_convertible), // Use accessor
                    ]),

                InfolistSection::make('Informasi Stok & Harga')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('stock')
                            ->label('Stok Saat Ini')
                            ->badge()
                            ->color(fn(string $state): string => $state <= 5 ? 'warning' : 'success')
                            ->suffix(fn($record) => ' ' . $record->unit), // Menampilkan satuan

                        TextEntry::make('purchase_price')
                            ->label('Harga Beli (Modal)')
                            ->currency('IDR'),

                        TextEntry::make('selling_price')
                            ->label('Harga Jual')
                            ->currency('IDR')
                            ->weight('bold')
                            ->size('lg'),
                    ]),

                // Section ini hanya akan muncul jika item ini adalah item induk
                InfolistSection::make('Detail Konversi')
                    ->visible(fn($record) => $record->is_convertible)
                    ->schema([
                        TextEntry::make('conversion_value')
                            ->label('Nilai Konversi')
                            ->helperText(function ($record) {
                                $childName = $record->targetChild?->name ?? '...';
                                $childUnit = $record->targetChild?->unit ?? '...';
                                return "1 {$record->unit} {$record->name} akan menghasilkan {$record->conversion_value} {$childUnit} {$childName}";
                            }),
                        TextEntry::make('targetChild.name')
                            ->label('Target Item Eceran'),
                    ]),
            ]);
    }
}
