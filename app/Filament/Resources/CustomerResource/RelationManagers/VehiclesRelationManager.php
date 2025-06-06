<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\VehicleResource;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    public function form(Form $form): Form
    {
        return $form
            ->schema(VehicleResource::getFormSchema());
        }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('license_plate')
            ->columns([
                Tables\Columns\TextColumn::make('license_plate')
                    ->label('Nomor Polisi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek Kendaraan'),
                Tables\Columns\TextColumn::make('model')
                    ->label('Model Kendaraan'),
                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tombol untuk membuat kendaraan baru langsung dari halaman customer
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tombol Edit dan Delete untuk setiap baris kendaraan
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
