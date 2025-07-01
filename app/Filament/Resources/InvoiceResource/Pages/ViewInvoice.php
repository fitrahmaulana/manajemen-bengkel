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
            Actions\Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->visible(fn (Invoice $record): bool => $record->balance_due > 0)
                ->modalHeading('Record Payment')
                ->modalSubmitActionLabel('Save Payment')
                ->form([
                    DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->default(now())
                        ->required(),
                    TextInput::make('amount_paid')
                        ->label('Amount Paid')
                        ->numeric()
                        ->prefix('Rp.')
                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn (ViewRecord $livewire) => $livewire->record->balance_due)
                        ->rules([
                            fn (ViewRecord $livewire) => function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                $amount = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$value);
                                if ($amount <= 0) {
                                    $fail("The amount paid must be greater than zero.");
                                }
                                if ($amount > $livewire->record->balance_due) {
                                    $fail("The amount paid cannot exceed the balance due of Rp " . number_format($livewire->record->balance_due, 0, ',', '.'));
                                }
                            },
                        ]),
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash' => 'Cash',
                            'transfer' => 'Bank Transfer',
                            'qris' => 'QRIS',
                            'credit_card' => 'Credit Card',
                            'debit_card' => 'Debit Card',
                            'other' => 'Other',
                        ])
                        ->required(),
                    TextInput::make('reference_number')
                        ->label('Reference Number (Optional)'),
                    Textarea::make('notes')
                        ->label('Notes (Optional)')
                        ->rows(3),
                ])
                ->action(function (array $data, Invoice $record) {
                    try {
                        // Ensure amount_paid is correctly parsed
                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$data['amount_paid']);

                        if ($amountPaid <= 0) {
                            throw ValidationException::withMessages(['amount_paid' => 'Amount paid must be greater than zero.']);
                        }
                        if ($amountPaid > $record->balance_due) {
                             throw ValidationException::withMessages(['amount_paid' => 'Amount paid cannot exceed balance due.']);
                        }

                        $record->payments()->create([
                            'payment_date' => $data['payment_date'],
                            'amount_paid' => $amountPaid,
                            'payment_method' => $data['payment_method'],
                            'reference_number' => $data['reference_number'],
                            'notes' => $data['notes'],
                        ]);

                        // Refresh the record to get updated balance_due
                        $record->refresh();

                        if ($record->balance_due <= 0) {
                            $record->status = 'paid';
                            $record->save();
                            Notification::make()
                                ->title('Payment Recorded & Invoice Paid')
                                ->body('The payment has been recorded and the invoice is now fully paid.')
                                ->success()
                                ->send();
                        } else {
                            // Optionally, add a 'partially_paid' status or just notify
                             $record->status = 'sent'; // Or a new 'partially_paid' status
                             $record->save();
                            Notification::make()
                                ->title('Payment Recorded')
                                ->body('The payment has been successfully recorded. Balance due: Rp ' . number_format($record->balance_due, 0, ',', '.'))
                                ->success()
                                ->send();
                        }
                        // No need to explicitly refresh the page, Filament handles it with Livewire
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Validation Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        // Re-throw to let Filament handle displaying errors in the modal
                        throw $e;
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error Recording Payment')
                            ->body('An unexpected error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
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
