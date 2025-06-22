<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit')
                ->url(route('filament.admin.resources.invoices.edit', $this->record))
                ->icon('heroicon-o-pencil')
                ->color('primary'),
        ];
    }

    public function getFooterActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print')
                ->url(route('filament.admin.resources.invoices.print', $this->record))
                ->icon('heroicon-o-printer')
                ->color('primary'),
        ];
    }


}
