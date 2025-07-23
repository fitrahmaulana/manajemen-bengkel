<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Faktur Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    /**
     * Handle bulk delete with stock restoration
     */
    public function bulkDelete(): void
    {
        $selectedRecords = $this->getSelectedTableRecords();

        foreach ($selectedRecords as $invoice) {
            // Restore stock for items when invoice is deleted
            foreach ($invoice->items as $item) {
                $itemModel = \App\Models\Item::find($item->id);
                if ($itemModel) {
                    $itemModel->stock += $item->pivot->quantity;
                    $itemModel->save();
                }
            }
        }

        // Perform the actual bulk delete
        parent::bulkDelete();

        Notification::make()
            ->title('Faktur Berhasil Dihapus')
            ->body('Stock telah dikembalikan untuk item yang terhapus.')
            ->success()
            ->send();
    }
}
