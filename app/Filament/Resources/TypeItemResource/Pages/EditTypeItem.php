<?php

namespace App\Filament\Resources\TypeItemResource\Pages;

use App\Filament\Resources\TypeItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTypeItem extends EditRecord
{
    protected static string $resource = TypeItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
