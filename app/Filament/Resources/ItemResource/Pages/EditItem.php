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
            // The "Buat & Lampirkan Item Eceran" action is removed as this functionality
            // is now better handled by the createOptionForm in the ItemResource main form's
            // 'target_child_item_id' Select field.
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Conditionally load relation managers.
     * Since ConversionChildrenRelationManager is removed, this method might not be needed
     * unless there are other relation managers to be handled conditionally.
     * For now, reverting to parent or an empty array if no other RMs.
     */
    public function getRelationManagers(): array
    {
        // If ConversionChildrenRelationManager was the only one being conditionally loaded,
        // and ItemResource::getRelations() is empty or doesn't list it,
        // this method can be simplified or removed to use default behavior.
        // For now, let's ensure it doesn't try to load the removed RM.
        $managers = parent::getRelationManagers();

        // Filter out ConversionChildrenRelationManager explicitly if it was ever registered globally
        // and we want to ensure it's not shown based on old logic.
        // However, the primary control is now that it should have been removed from ItemResource::getRelations() as well.
        // $managers = array_filter($managers, function ($managerClass) {
        //     return $managerClass !== \App\Filament\Resources\ItemResource\RelationManagers\ConversionChildrenRelationManager::class;
        // });

        // If the parent ItemResource::getRelations() is empty, this will also be empty.
        return $managers;
    }
}
