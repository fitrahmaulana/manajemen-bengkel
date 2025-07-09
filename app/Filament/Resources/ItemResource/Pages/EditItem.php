<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;


class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            // === TOMBOL AKSI BARU KITA ===
            // Aksi ini sudah tidak relevan karena menggunakan sistem konversi baru
            // Actions\Action::make('syncAndSuggestPrice')
            //     ->label('Hitung & Sarankan Harga Eceran')
            //     ->icon('heroicon-o-calculator')
            //     // Aksi ini hanya muncul jika item ini adalah item induk yang punya target eceran
            //     ->visible(fn(Item $record): bool => $record->is_convertible && $record->target_child_item_id !== null)
            //     ->color('warning')
            //     ...
            // === AKSI INI SUDAH TIDAK RELEVAN DENGAN SISTEM BARU ===
        ];
    }
}
