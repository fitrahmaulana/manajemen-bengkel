<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Models\Payment;
use App\Models\Invoice;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array // Changed visibility to protected as is common
    {
        return [
            // The 'Record Payment' action is removed as this functionality
            // will now be handled by the CreateAction in PaymentRelationManager.
            Actions\EditAction::make(),
            Actions\DeleteAction::make(), // Will soft delete
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    // Footer actions can be kept if needed, or removed if not.
    // For now, I will comment it out to focus on header actions.
    // public function getFooterActions(): array
    // {
    //     return [
    //         Actions\Action::make('print')
    //             ->label('Print')
    //             ->url(fn (Invoice $record) => route('filament.admin.resources.invoices.print', $record))
    //             ->icon('heroicon-o-printer')
    //             ->color('primary'),
    //     ];
    // }
}
