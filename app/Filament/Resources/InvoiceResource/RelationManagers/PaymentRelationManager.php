<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class PaymentRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'payment_date'; // Or another suitable attribute

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->rules([
                        fn (RelationManager $livewire): callable => function (string $attribute, $value, \Closure $fail) use ($livewire) {
                            $amount = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$value);
                            /** @var Invoice $invoice */
                            $invoice = $livewire->getOwnerRecord();
                            if ($amount <= 0) {
                                $fail("The amount paid must be greater than zero.");
                            }
                            // For new records, check against current balance due.
                            // For existing records, calculate what the balance *would be* if this payment was its original value, then add the new value.
                            // This is tricky if editing; simpler to just ensure it doesn't make total paid > total amount.
                            // A simpler rule for edit: ensure new total_paid doesn't exceed total_amount.
                            // For create: new payment amount <= balance_due
                            if ($livewire->mountedTableAction === 'create') {
                                if ($amount > $invoice->balance_due) {
                                    $fail("The amount paid cannot exceed the current balance due of Rp " . number_format($invoice->balance_due, 0, ',', '.'));
                                }
                            } elseif ($livewire->mountedTableAction === 'edit') {
                                /** @var Payment $paymentRecord */
                                $paymentRecord = $livewire->getMountedTableActionRecord();
                                $otherPaymentsTotal = $invoice->payments()->where('id', '!=', $paymentRecord->id)->sum('amount_paid');
                                if (($otherPaymentsTotal + $amount) > $invoice->total_amount) {
                                     $fail("The total paid amount (Rp " . number_format($otherPaymentsTotal + $amount, 0, ',', '.') .") cannot exceed the invoice total amount of Rp " . number_format($invoice->total_amount, 0, ',', '.'));
                                }
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
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_date')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->date('d M Y')
                    ->label('Payment Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->currency('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount_paid'] = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$data['amount_paid']);
                        return $data;
                    })
                    ->after(function (RelationManager $livewire) {
                        $this->updateInvoiceStatus($livewire->getOwnerRecord());
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount_paid'] = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$data['amount_paid']);
                        return $data;
                    })
                    ->after(function (RelationManager $livewire) {
                        $this->updateInvoiceStatus($livewire->getOwnerRecord());
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (RelationManager $livewire) {
                        $this->updateInvoiceStatus($livewire->getOwnerRecord());
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (RelationManager $livewire) {
                            $this->updateInvoiceStatus($livewire->getOwnerRecord());
                        }),
                ]),
            ]);
    }

    protected function updateInvoiceStatus(Model $invoice): void
    {
        if (!$invoice instanceof Invoice) {
            return;
        }

        $invoice->refresh(); // Recalculate balance_due and other attributes

        $newStatus = $invoice->status; // Default to current status

        if ($invoice->balance_due <= 0) {
            $newStatus = 'paid';
        } else {
            // If not paid, and not overdue, it's 'sent' (or could be 'partially_paid')
            // Don't override 'overdue' status if it's already set and balance is due
            if ($invoice->status !== 'overdue') {
                 $newStatus = 'sent';
            }
        }

        if ($invoice->status !== $newStatus) {
            $invoice->status = $newStatus;
            $invoice->save();

            Notification::make()
                ->title('Invoice Status Updated')
                ->body("Invoice {$invoice->invoice_number} status changed to {$invoice->status}.")
                ->success()
                ->send();
        }
    }
}
