<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice; // Added for type hinting and refreshing
use App\Models\Payment; // Added for afterDelete hook

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'payment_date'; // Or something more descriptive

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Payment Date')
                    ->default(now())
                    ->required(),
                Forms\Components\TextInput::make('amount_paid')
                    ->label('Amount Paid')
                    ->numeric()
                    ->prefix('Rp.')
                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                    ->required()
                    ->live(onBlur: true) // Added live onBlur for dynamic validation
                    ->rules([
                        fn (RelationManager $livewire) => function (string $attribute, $value, \Closure $fail) use ($livewire) {
                            $amount = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$value);
                            /** @var Invoice $invoice */
                            $invoice = $livewire->getOwnerRecord();
                            if (!$invoice) {
                                $fail("Invoice context not found.");
                                return;
                            }

                            // For new payments in relation manager, max value is balance due
                            // For editing, it could be different if they are adjusting an existing payment amount
                            // but typically you'd want to prevent overpayment.
                            // Let's assume for now that even edits shouldn't make the *total* paid exceed total_amount too much.
                            // Or, more simply, a single payment shouldn't grossly exceed the original invoice total.
                            // The original `ViewInvoice` action had a stricter rule for new payments.

                            if ($amount <= 0) {
                                $fail("The amount paid must be greater than zero.");
                            }

                            // If it's a new record (create context within relation manager)
                            // For now, let's keep it simple: positive amount.
                            // More complex validation (like not exceeding balance_due) can be added
                            // if the existing modal on ViewInvoice isn't sufficient.
                        },
                    ]),
                Forms\Components\Select::make('payment_method')
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
                Forms\Components\TextInput::make('reference_number')
                    ->label('Reference Number (Optional)'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes (Optional)')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_date') // Or a more descriptive composite title
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
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->placeholder('N/A')
                    ->searchable(),
            ])
            ->filters([
                // Filters can be added if needed
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure amount_paid is correctly parsed from currency mask
                        $data['amount_paid'] = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$data['amount_paid']);
                        return $data;
                    })
                    ->after(function (RelationManager $livewire, Model $record) {
                        /** @var Invoice $invoice */
                        $invoice = $livewire->getOwnerRecord();
                        if ($invoice) {
                            $invoice->refresh();
                            if ($invoice->balance_due <= 0) {
                                $invoice->status = 'paid';
                                $invoice->save();
                            } else if ($invoice->status !== 'overdue' && $invoice->status !== 'paid') {
                                $invoice->status = 'sent'; // Or a new 'partially_paid' status
                                $invoice->save();
                            }
                            // This should trigger a refresh of dependent components on the page
                            $livewire->dispatch('refresh'); // Generic event, might need specific targeting
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount_paid'] = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$data['amount_paid']);
                        return $data;
                    })
                    ->after(function (RelationManager $livewire, Model $record) {
                        /** @var Invoice $invoice */
                        $invoice = $livewire->getOwnerRecord();
                        if ($invoice) {
                            $invoice->refresh();
                            if ($invoice->balance_due <= 0) {
                                $invoice->status = 'paid';
                                $invoice->save();
                            } else if ($invoice->status !== 'overdue' && $invoice->status !== 'paid') {
                                $invoice->status = 'sent';
                                $invoice->save();
                            }
                             $livewire->dispatch('refresh');
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (RelationManager $livewire, Payment $record) { // Specify Payment model for clarity
                        /** @var Invoice $invoice */
                        $invoice = $livewire->getOwnerRecord();
                        if ($invoice) {
                            // The payment is already deleted by Filament at this point.
                            // We need to refresh the invoice to recalculate totals and status.
                            $invoice->refresh(); // Recalculates balance_due via accessor

                            // Update invoice status if necessary
                            if ($invoice->balance_due > 0 && $invoice->status === 'paid') {
                                $invoice->status = 'sent'; // Or appropriate status like 'partially_paid'
                                $invoice->save();
                            } else if ($invoice->balance_due <= 0 && $invoice->status !== 'paid') {
                                // This case might occur if other payments cover the amount,
                                // or if an overpayment was deleted.
                                $invoice->status = 'paid';
                                $invoice->save();
                            } else if ($invoice->balance_due > 0 && $invoice->status === 'sent' && $invoice->payments()->doesntExist()){
                                // If all payments are deleted and it was 'sent', maybe it should be 'draft' or 'sent'
                                // For now, keep it simple: if balance > 0 and status was 'paid', revert to 'sent'.
                                // If it was already 'sent' or 'draft' or 'overdue', its status might not need to change
                                // unless all payments are gone and it should revert to a more initial state.
                                // Let's assume 'sent' is fine if balance > 0.
                            }
                            // Notify the parent page or specific components to refresh.
                            // Filament's default behavior might handle this, but an explicit event can ensure it.
                            $livewire->dispatch('refresh'); // Refresh the relation manager itself and potentially other components
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (RelationManager $livewire, \Illuminate\Database\Eloquent\Collection $records) {
                            /** @var Invoice $invoice */
                            $invoice = $livewire->getOwnerRecord();
                            if ($invoice) {
                                $invoice->refresh();
                                if ($invoice->balance_due > 0 && $invoice->status === 'paid') {
                                    $invoice->status = 'sent';
                                    $invoice->save();
                                } else if ($invoice->balance_due <= 0 && $invoice->status !== 'paid') {
                                     $invoice->status = 'paid';
                                     $invoice->save();
                                }
                                $livewire->dispatch('refresh');
                            }
                        }),
                ]),
            ]);
    }

    // Optional: If you want to control the query, for example, to always sort by payment_date
    // public function getTableQuery(): Builder
    // {
    //     return parent::getTableQuery()->orderBy('payment_date', 'desc');
    // }

    protected function canCreate(): bool
    {
        /** @var Invoice $invoice */
        $invoice = $this->getOwnerRecord();
        if (!$invoice) {
            return false;
        }
        // Allow creating payments as long as the invoice is not fully paid, or if you allow overpayments.
        // For simplicity, let's say you can always add a payment record.
        // The form validation should handle if amount exceeds balance_due.
        // Or, like the modal, prevent if balance_due <= 0
        return $invoice->balance_due > 0;
    }

     /**
     * This method is called after a payment record is created via the relation manager.
     */
    protected function afterCreate(): void
    {
        $invoice = $this->getOwnerRecord();
        if ($invoice instanceof Invoice) {
            $invoice->refresh();
            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
                $invoice->save();
            } elseif ($invoice->status !== 'overdue') {
                $invoice->status = 'sent'; // Or 'partially_paid'
                $invoice->save();
            }
        }
    }

    /**
     * This method is called after a payment record is updated via the relation manager.
     */
    protected function afterSave(): void // Covers both create and update through table actions
    {
        $invoice = $this->getOwnerRecord();
        if ($invoice instanceof Invoice) {
            $invoice->refresh();
            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
                $invoice->save();
            } elseif ($invoice->status !== 'overdue') {
                $invoice->status = 'sent'; // Or 'partially_paid'
                $invoice->save();
            }
        }
    }
}
