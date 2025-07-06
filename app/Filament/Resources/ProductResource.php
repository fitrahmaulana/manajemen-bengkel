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
                            ->live(onBlur: true),
                            // Removed afterStateUpdated that set 'standard_sku' as the field is removed

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
                            ->live()
                            ->helperText('Jika dicentang, varian dikelola di tab "Daftar Varian". Jika tidak, produk ini dianggap sebagai item tunggal yang juga dikelola di "Daftar Varian".'),
                    ]),

                Forms\Components\Section::make('Pengelolaan Varian/Item')
                    ->description('Semua detail varian atau item tunggal (termasuk SKU, harga, dan stok) dikelola melalui tab "Daftar Varian" di halaman detail/edit produk setelah produk ini disimpan.')
                    ->schema([
                        Forms\Components\Placeholder::make('manage_items_info')
                            ->label('')
                            ->content('Navigasi ke tab "Daftar Varian" untuk menambahkan atau mengubah detail item/varian.'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Product::query()) // Changed to Product Query
            ->heading('Daftar Produk')
            ->description('Pengelolaan produk induk. Varian untuk setiap produk dapat dikelola di halaman detail produk.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(Product $record): string => $record->brand ? "Merek: {$record->brand}" : ''),
                Tables\Columns\TextColumn::make('typeItem.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('has_variants')
                    ->label('Memiliki Varian')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items') // Show number of variants
                    ->label('Jumlah Varian')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50)
                    ->tooltip(fn (Product $record): ?string => $record->description),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_item_id')
                    ->label('Kategori')
                    ->relationship('typeItem', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('has_variants')
                    ->label('Memiliki Varian')
                    ->boolean()
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Added DeleteAction for individual records
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada produk')
            ->emptyStateDescription('Tambahkan produk untuk bengkel Anda.')
            ->emptyStateIcon('heroicon-o-cube-transparent')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(), // Changed to standard CreateAction
            ])
            ->headerActions([
                 Tables\Actions\CreateAction::make(), // Added standard CreateAction to header
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductResource\RelationManagers\ItemsRelationManager::class,
        ];
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
