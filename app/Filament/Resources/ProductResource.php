<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;

use Illuminate\Database\Eloquent\Collection;

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
            ->query(
                \App\Models\Item::query()
                    ->join('products', 'items.product_id', '=', 'products.id')
                    ->join('type_items', 'products.type_item_id', '=', 'type_items.id')
                    ->with(['product', 'product.typeItem'])
                    ->select('items.*') // Pastikan kita hanya select kolom dari items untuk menghindari konflik
            )
            ->heading('Daftar Varian Produk')
            ->description('Semua varian produk dalam satu tampilan untuk memudahkan kasir melihat harga dan stok. Jika produk memiliki varian tapi tidak muncul, pastikan sudah menambahkan varian di halaman detail produk.')
            ->columns([
                Tables\Columns\TextColumn::make('product_name_with_variant')
                    ->label('Nama Produk')
                    ->searchable(['products.name', 'products.brand', 'items.name'])
                    ->sortable(['products.name'])
                    ->weight('bold')
                    ->getStateUsing(function ($record) {
                        $productName = $record->product->name;
                        $variant = $record->name;

                        if ($variant && $variant !== 'Belum Ada Varian') {
                            return $productName . ' - ' . $variant;
                        } elseif ($variant === 'Belum Ada Varian') {
                            return $productName . ' (⚠️ Belum Ada Varian)';
                        }

                        return $productName;
                    })
                    ->description(fn($record) => $record->product->brand ? "Merek: {$record->product->brand}" : null)
                    ->color(fn($record) => $record->name === 'Belum Ada Varian' ? 'warning' : null),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(['items.sku'])
                    ->copyable()
                    ->copyMessage('SKU berhasil disalin')
                    ->fontFamily('mono')
                    ->badge(fn($record) => str_ends_with($record->sku, '-TEMP'))
                    ->color(fn($record) => str_ends_with($record->sku, '-TEMP') ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('product.typeItem.name')
                    ->label('Kategori')
                    ->searchable(['type_items.name'])
                    ->sortable(['type_items.name'])
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => $state ?: 'Tidak Ada Kategori')
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->currency('IDR')
                    ->sortable(['items.selling_price'])
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->alignCenter()
                    ->sortable(['items.stock'])
                    ->badge()
                    ->color(fn($state) => $state > 20 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn($state, $record) => $state . ' ' . $record->unit),

                Tables\Columns\IconColumn::make('is_convertible')
                    ->label('Bisa Dipecah')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->groups([
                Tables\Grouping\Group::make('product.typeItem.name')
                    ->label('Kategori')
                    ->collapsible(),
                Tables\Grouping\Group::make('product.name')
                    ->label('Produk')
                    ->collapsible(),
            ])
            ->defaultGroup('product.typeItem.name')
            ->filters([
                Tables\Filters\SelectFilter::make('product.type_item_id')
                    ->label('Kategori')
                    ->relationship('product.typeItem', 'name'),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

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
                            $query->whereHas('product', function ($q) {
                                $q->where('has_variants', true);
                            });
                        } elseif ($data['variant_type'] === 'without_variants') {
                            $query->whereHas('product', function ($q) {
                                $q->where('has_variants', false);
                            });
                        }
                    }),

                Tables\Filters\Filter::make('stock_status')
                    ->label('Status Stok')
                    ->form([
                        Forms\Components\Select::make('stock_type')
                            ->label('Status')
                            ->options([
                                'available' => 'Tersedia (>20)',
                                'low_stock' => 'Stok Menipis (1-20)',
                                'out_of_stock' => 'Habis (0)',
                            ])
                            ->placeholder('Semua Status'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['stock_type'] === 'available') {
                            $query->where('items.stock', '>', 20);
                        } elseif ($data['stock_type'] === 'low_stock') {
                            $query->where('items.stock', '>', 0)->where('items.stock', '<=', 20);
                        } elseif ($data['stock_type'] === 'out_of_stock') {
                            $query->where('items.stock', '<=', 0);
                        }
                    }),

                Tables\Filters\Filter::make('convertible')
                    ->label('Bisa Dipecah')
                    ->query(fn($query) => $query->where('items.target_child_item_id', '!=', null)),

                Tables\Filters\Filter::make('missing_variants')
                    ->label('Belum Ada Varian')
                    ->query(fn($query) => $query->where('items.name', 'Belum Ada Varian'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('addVariants')
                    ->label('Tambah Varian')
                    ->icon('heroicon-o-plus')
                    ->color('warning')
                    ->visible(fn($record) => $record->name === 'Belum Ada Varian')
                    ->url(fn($record) => static::getUrl('view', ['record' => $record->product_id]) . '#items')
                    ->tooltip('Produk ini memiliki varian tapi belum ada varian yang ditambahkan'),

                Tables\Actions\Action::make('viewItem')
                    ->label('Detail Varian')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn($record) => $record->name !== 'Belum Ada Varian')
                    ->infolist([
                        InfolistSection::make('Informasi Varian')
                            ->schema([
                                TextEntry::make('product_name_with_variant')
                                    ->label('Nama Produk')
                                    ->getStateUsing(function ($record) {
                                        $productName = $record->product->name;
                                        $variant = $record->name;

                                        if ($variant) {
                                            return $productName . ' - ' . $variant;
                                        }

                                        return $productName;
                                    }),
                                TextEntry::make('sku')
                                    ->label('SKU'),
                                TextEntry::make('product.typeItem.name')
                                    ->label('Kategori'),
                                TextEntry::make('purchase_price')
                                    ->label('Harga Beli')
                                    ->money('IDR'),
                                TextEntry::make('selling_price')
                                    ->label('Harga Jual')
                                    ->money('IDR'),
                                TextEntry::make('stock')
                                    ->label('Stok')
                                    ->formatStateUsing(fn($state, $record) => $state . ' ' . $record->unit),
                                TextEntry::make('is_convertible')
                                    ->label('Bisa Dipecah')
                                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak'),
                            ])
                            ->columns(2),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Tables\Actions\Action::make('viewProduct')
                    ->label('Detail Produk')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->url(fn($record) => static::getUrl('view', ['record' => $record->product_id]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('editProduct')
                    ->label('Edit Produk')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn($record) => static::getUrl('edit', ['record' => $record->product_id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('updateStock')
                        ->label('Update Stok')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->form([
                            Forms\Components\Select::make('action')
                                ->label('Aksi')
                                ->options([
                                    'set' => 'Set Stok Menjadi',
                                    'add' => 'Tambah Stok',
                                    'subtract' => 'Kurangi Stok',
                                ])
                                ->required()
                                ->live(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            foreach ($records as $record) {
                                $newStock = match ($data['action']) {
                                    'set' => $data['quantity'],
                                    'add' => $record->stock + $data['quantity'],
                                    'subtract' => max(0, $record->stock - $data['quantity']),
                                };
                                $record->update(['stock' => $newStock]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(null)
            ->emptyStateHeading('Belum ada varian produk')
            ->emptyStateDescription('Tambahkan produk dan varian untuk bengkel Anda. Jika produk memiliki varian, pastikan untuk menambahkan varian melalui tab "Daftar Varian" di halaman detail produk.')
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
