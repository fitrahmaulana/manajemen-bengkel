<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Produk';
    protected static ?string $modelLabel = 'Produk';
    protected static ?string $pluralModelLabel = 'Daftar Produk';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->description('Informasi dasar produk yang akan dijual')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->placeholder('Contoh: Oli Mesin Castrol GTX')
                            ->required()
                            ->helperText('Nama umum produk (tanpa spesifikasi ukuran)')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                // Auto-generate SKU untuk produk standard
                                if ($state && !$get('has_variants') && !$get('standard_sku')) {
                                    $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $state), 0, 6));
                                    $sku = $productCode . '-STD';
                                    $set('standard_sku', $sku);
                                }
                            }),

                        Forms\Components\TextInput::make('brand')
                            ->label('Merek')
                            ->placeholder('Contoh: Castrol, Shell, Mobil 1')
                            ->helperText('Merek/brand produk'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Deskripsi singkat produk ini')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type_item_id')
                            ->label('Kategori Produk')
                            ->relationship('typeItem', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih kategori produk'),
                    ]),

                Forms\Components\Section::make('Pengaturan Varian')
                    ->schema([
                        Forms\Components\Checkbox::make('has_variants')
                            ->label('Produk ini memiliki varian')
                            ->helperText('Centang jika produk memiliki beberapa varian. Detail produk akan disembunyikan dan varian akan dikelola melalui tab "Daftar Varian".')
                            ->live(),
                    ]),

                // Form untuk produk tanpa varian (standard)
                Forms\Components\Section::make('Detail Produk')
                    ->description('Isi detail harga dan stok untuk produk standard')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('standard_sku')
                                    ->label('Kode Barang')
                                    ->placeholder('Akan otomatis terisi')
                                    ->helperText('Kode unik untuk produk ini'),
                                Forms\Components\Select::make('standard_unit')
                                    ->label('Satuan')
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
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('standard_purchase_price')
                                    ->label('Harga Beli')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->prefix('Rp'),
                                Forms\Components\TextInput::make('standard_selling_price')
                                    ->label('Harga Jual')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->prefix('Rp'),
                            ]),
                        Forms\Components\TextInput::make('standard_stock')
                            ->label('Stok')
                            ->numeric()
                            ->default(0),
                    ])
                    ->visible(fn(Forms\Get $get): bool => !$get('has_variants')),

                // Info untuk produk dengan varian
                Forms\Components\Section::make('Informasi Varian')
                    ->description('Varian produk akan dikelola melalui tab "Daftar Varian" setelah produk disimpan')
                    ->schema([
                        Forms\Components\Placeholder::make('variant_info')
                            ->label('')
                            ->content('Setelah menyimpan produk, Anda dapat menambah dan mengelola varian melalui tab "Daftar Varian" di halaman detail produk.')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn(Forms\Get $get): bool => $get('has_variants')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Daftar Produk')
            ->description('Kelola produk utama. Untuk melihat dan mengelola varian/stok, gunakan menu "Varian Barang".')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->brand ? "Merek: {$record->brand}" : null),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('typeItem.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('has_variants')
                    ->label('Punya Varian')
                    ->boolean()
                    ->alignCenter()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Varian')
                    ->counts('items')
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Total Stok')
                    ->alignCenter()
                    ->getStateUsing(fn($record) => $record->items()->sum('stock'))
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('typeItem.name')
                    ->label('Kategori')
                    ->collapsible(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_item_id')
                    ->label('Kategori')
                    ->relationship('typeItem', 'name'),

                Tables\Filters\Filter::make('has_variants')
                    ->label('Jenis Produk')
                    ->form([
                        Forms\Components\Select::make('variant_type')
                            ->label('Jenis')
                            ->options([
                                'with_variants' => 'Dengan Varian',
                                'without_variants' => 'Tanpa Varian',
                            ])
                            ->placeholder('Semua Jenis'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['variant_type'] === 'with_variants') {
                            $query->where('has_variants', true);
                        } elseif ($data['variant_type'] === 'without_variants') {
                            $query->where('has_variants', false);
                        }
                    }),

                Tables\Filters\Filter::make('no_items')
                    ->label('Belum Ada Varian')
                    ->query(fn($query) => $query->whereDoesntHave('items'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('viewItems')
                    ->label('Lihat Varian')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn($record) => route('filament.admin.resources.items.index', ['tableFilters[product_id][value]' => $record->id]))
                    ->visible(fn($record) => $record->items_count > 0),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada produk')
            ->emptyStateDescription('Tambahkan produk pertama untuk bengkel Anda.')
            ->emptyStateIcon('heroicon-o-cube-transparent')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Buat Produk Baru')
                    ->url(route('filament.admin.resources.products.create'))
                    ->icon('heroicon-m-plus'),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
