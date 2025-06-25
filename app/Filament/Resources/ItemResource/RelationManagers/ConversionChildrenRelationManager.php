<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Item; // Make sure Item model is imported

class ConversionChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'conversionChildren';

    protected static ?string $recordTitleAttribute = 'name'; // Attribute from the Item model (child item)

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('child_item_id') // This will store into the 'child_item_id' column of the pivot
                    ->label('Child Item (Item Eceran)')
                    ->options(Item::query()->pluck('name', 'id')) // Allow selecting any item as a child
                    ->searchable()
                    ->required()
                    // ->relationship(name: 'record', titleAttribute: 'name') // This is for BelongsTo on main form, not ideal for pivot's target selection
                    ->helperText('Pilih item yang akan menjadi turunan/eceran dari item induk ini.'),

                Forms\Components\TextInput::make('conversion_value')
                    ->label('Nilai Konversi')
                    ->numeric()
                    ->required()
                    ->helperText('Jumlah unit item turunan yang dihasilkan dari 1 unit item induk. Cth: Induk Oli 4L, Anak Oli Eceran (Liter), Nilai Konversi = 4.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name') // This refers to the 'name' of the child Item
            ->columns([
                Tables\Columns\TextColumn::make('name') // This will display the 'name' of the related Item (child)
                    ->label('Nama Item Turunan (Eceran)'),
                Tables\Columns\TextColumn::make('pivot.conversion_value') // Accessing pivot data
                    ->label('Nilai Konversi')
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                // Tables\Actions\AttachAction::make() // AttachAction is good if you are selecting from existing, CreateAction makes a new pivot row.
                                                   // For ManyToMany, CreateAction here actually creates a new pivot record,
                                                   // and the form above defines what goes into that pivot record.
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(), // DetachAction for ManyToMany
                // Tables\Actions\DeleteAction::make(), // DeleteAction would delete the child Item, Detach is usually preferred for pivots
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
