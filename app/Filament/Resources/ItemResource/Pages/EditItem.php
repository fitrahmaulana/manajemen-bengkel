<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
// Removed App\Filament\Resources\ItemResource\RelationManagers\ConversionChildrenRelationManager;
// Removed App\Models\Item;
// Removed Filament\Forms\Components\TextInput;
// Removed Illuminate\Support\Facades\DB;
// Removed Filament\Notifications\Notification;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
