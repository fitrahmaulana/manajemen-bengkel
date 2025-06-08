<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Kendaraan';
    protected static ?string $modelLabel = 'Kendaraan';
    protected static ?string $pluralModelLabel = 'Daftar Kendaraan';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Pemilik Kendaraan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                ...self::getFormSchema()

            ]);
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('license_plate')
                ->label('Nomor Polisi')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('brand')->label('Merek Kendaraan')->required(),
                Forms\Components\TextInput::make('model')->label('Model Kendaraan')->required(),
            ]),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('color')->label('Warna'),
                Forms\Components\TextInput::make('year')->label('Tahun')->numeric()->required(),
            ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('license_plate')
                    ->label('Nomor Polisi')
                    ->searchable(),
                // Menampilkan nama pelanggan menggunakan dot notation
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pemilik')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek'),
                Tables\Columns\TextColumn::make('model')
                    ->label('Model'),
                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
