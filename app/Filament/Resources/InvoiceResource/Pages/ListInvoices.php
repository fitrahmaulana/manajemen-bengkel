<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Traits\InvoiceCalculationTrait; // Use the trait for consistency
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListInvoices extends ListRecords
{
    use InvoiceCalculationTrait; // Use the trait for consistency

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
