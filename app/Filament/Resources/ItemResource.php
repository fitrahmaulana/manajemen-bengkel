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

                Forms\Components\Section::make('Detail Konversi Stok')
                    ->description('Isi bagian ini jika barang ini adalah barang induk yang bisa dipecah atau barang eceran hasil pecahan.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('parent_item_id')
                            ->label('Induk Barang (untuk Barang Eceran)')
                            ->relationship(name: 'parent', titleAttribute: 'name', modifyQueryUsing: fn (Builder $query, ?Item $record) => $record ? $query->where('id', '!=', $record->id) : $query)
                            ->helperText('Jika ini adalah barang eceran/turunan, pilih barang induknya.')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('conversion_value')
                            ->label('Nilai Konversi (untuk Barang Induk)')
                            ->numeric()
                            ->helperText('Jika barang ini bisa dipecah, berapa banyak satuan dasar yang dikandungnya? (Misal: 4 untuk kemasan 4 Liter)')
                            ->nullable(),
                        Forms\Components\TextInput::make('base_unit')
                            ->label('Satuan Dasar (untuk Barang Induk)')
                            ->helperText('Jika barang ini bisa dipecah, apa satuan dasarnya? (Misal: Liter, Kg, Pcs)')
                            ->nullable(),
                    ])
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
                Tables\Actions\Action::make('pecahStokOneClick') // Renamed for clarity if old one is kept temporarily
                    ->label('Pecah 1 Unit Stok')
                    ->icon('heroicon-o-arrows-right-left')
                    ->requiresConfirmation()
                    ->modalHeading('Pecah Stok Induk')
                    ->modalDescription('Apakah Anda yakin ingin memecah 1 unit dari item induk ini? Stok item eceran terkait akan bertambah sesuai nilai konversi.')
                    ->modalSubmitActionLabel('Ya, Pecah Stok')
                    ->action(function (Item $record) {
                        $sourceItem = $record;
                        $targetItem = $sourceItem->children()->orderBy('id')->first(); // Get the first child

                        if (!$targetItem) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body('Tidak ditemukan item eceran/turunan yang terhubung dengan item induk ini.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($sourceItem->stock < 1) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body("Stok {$sourceItem->name} tidak mencukupi untuk dipecah.")
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!$sourceItem->conversion_value || $sourceItem->conversion_value <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body("Nilai konversi untuk {$sourceItem->name} tidak valid.")
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($sourceItem, $targetItem) {
                                $sourceItem->decrement('stock', 1);
                                $convertedAmount = (float) $sourceItem->conversion_value; // Ensure float for calculation
                                $targetItem->increment('stock', $convertedAmount);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Pecah Stok')
                                ->success()
                                ->body("1 {$sourceItem->unit} {$sourceItem->name} telah dipecah. Stok {$targetItem->name} bertambah {$sourceItem->conversion_value} {$targetItem->unit}.")
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body('Terjadi kesalahan saat memecah stok: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Item $record): bool =>
                        $record->parent_item_id === null &&      // Is a parent item
                        $record->conversion_value > 0 &&         // Has a valid conversion value
                        !empty($record->base_unit) &&            // Has a base unit defined
                        $record->children()->exists()            // Has at least one child item
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
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
