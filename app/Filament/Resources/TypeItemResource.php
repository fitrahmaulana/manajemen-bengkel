<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TypeItemResource\Pages;
use App\Filament\Resources\TypeItemResource\RelationManagers;
use App\Models\TypeItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TypeItemResource extends Resource
{
    protected static ?string $model = TypeItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Tipe Barang';
    protected static ?string $modelLabel = 'Tipe Barang';
    protected static ?string $pluralModelLabel = 'Tipe Barang';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Tipe Barang')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->label('Deskripsi Tipe Barang')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Total Items'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListTypeItems::route('/'),
            'create' => Pages\CreateTypeItem::route('/create'),
            'edit' => Pages\EditTypeItem::route('/{record}/edit'),
        ];
    }
}
