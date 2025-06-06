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

    protected static ?string $navigationIcon = 'heroicon-o-cube'; // Ikon kubus untuk Item
    protected static ?string $navigationGroup = 'Inventory'; // Grup navigasi untuk Item


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar Barang')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Barang')
                            ->required(),
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
