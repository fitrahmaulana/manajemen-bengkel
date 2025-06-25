<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Barang';
    protected static ?string $modelLabel = 'Barang';
    protected static ?string $pluralModelLabel = 'Daftar Barang';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar Barang')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Barang')
                            ->required(),
                        Forms\Components\Select::make('type_item_id')
                            ->label('Tipe Barang')
                            ->relationship('typeItem', 'name')
                            ->searchable()
                            ->preload(),
                        // Grid untuk menempatkan Kode & Merek berdampingan
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('Kode Barang (SKU)')
                                    ->required()
                                    ->unique(ignoreRecord: true), // Unik, tapi abaikan saat edit data yg sama
                                Forms\Components\TextInput::make('brand')
                                    ->label('Merek'),
                            ]),
                        Forms\Components\Toggle::make('is_convertible')
                            ->label('Item Dapat Dikonversi (Induk)')
                            ->helperText('Aktifkan jika item ini adalah item induk yang dapat dipecah menjadi item eceran.')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('Informasi Stok & Harga')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Harga Beli')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                Forms\Components\TextInput::make('selling_price')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stock')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                Forms\Components\Select::make('unit')
                                    ->label('Satuan')
                                    ->required()
                                    ->options([
                                        'Pcs' => 'Pcs',
                                        'Set' => 'Set',
                                    ])
                                    ->default('Pcs'),
                            ]),
                    ]),
                Forms\Components\TextInput::make('location')
                    ->label('Lokasi Penyimpanan'),

                // Section 'Detail Konversi Stok' and its fields (parent_item_id, conversion_value, base_unit)
                // are removed as this is now handled by the ConversionChildrenRelationManager.
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable(), // Membuat kolom ini bisa dicari
                Tables\Columns\TextColumn::make('sku')
                    ->label('Kode Barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR') // Otomatis format ke Rupiah
                    ->sortable(), // Membuat kolom ini bisa diurutkan
                Tables\Columns\TextColumn::make('stock')
                    ->badge() // Tampilkan dalam bentuk badge
                    ->color(fn(string $state): string => $state <= 5 ? 'warning' : 'success') // Stok <= 5 jadi kuning, sisanya hijau
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('pecahStok')
                    ->label('Pecah 1 Unit Stok')
                    ->icon('heroicon-o-arrows-right-left')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pecah Stok')
                    ->modalDescription('Apakah Anda yakin ingin memecah 1 unit dari item ini? Stok item turunan pertama yang terhubung akan bertambah sesuai nilai konversi yang diatur.')
                    ->modalSubmitActionLabel('Ya, Pecah')
                    ->action(function (Item $record) {
                        $sourceItem = $record;

                        // Get the first child item from the many-to-many relationship, including pivot data
                        $firstChildConversion = $sourceItem->conversionChildren()
                                                          ->withPivot('conversion_value', 'id')
                                                          ->orderByPivot('id') // Order by pivot table's ID to get the first added if multiple
                                                          ->first();

                        if (!$firstChildConversion) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body('Tidak ada item turunan (eceran) yang terhubung dengan item induk ini melalui tabel konversi.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // $firstChildConversion is the Item model of the child
                        $targetChildItem = $firstChildConversion;
                        $conversionValue = (float) $targetChildItem->pivot->conversion_value;

                        if ($conversionValue <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body("Nilai konversi yang diatur untuk '{$targetChildItem->name}' tidak valid (harus lebih dari 0).")
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($sourceItem->stock < 1) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body("Stok {$sourceItem->name} tidak mencukupi (kurang dari 1).")
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($sourceItem, $targetChildItem, $conversionValue) {
                                $sourceItem->decrement('stock', 1);
                                $targetChildItem->increment('stock', $conversionValue);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Pecah Stok')
                                ->success()
                                ->body("1 {$sourceItem->unit} {$sourceItem->name} telah dipecah. Stok {$targetChildItem->name} bertambah {$conversionValue} {$targetChildItem->unit}.")
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body('Terjadi kesalahan internal saat memecah stok: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Item $record): bool =>
                        // Show if this item has at least one child defined in the item_conversions table
                        $record->conversionChildren()->exists()
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
            RelationManagers\ConversionChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
