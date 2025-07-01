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
    protected static ?string $title = 'Riwayat Pembayaran'; // Title for the relation manager

    protected $listeners = [
        'refresh' => '$refresh',
    ];

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
                    ->minValue(1)
                    ->default(fn(RelationManager $livewire) => $livewire->getOwnerRecord()->balance_due),
                Forms\Components\Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Bank Transfer',
                        'qris' => 'QRIS',
                    ])
                    ->default('cash')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan (Optional)')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_date') // Or a more descriptive composite title
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->date('d M Y')
                    ->label('Tanggal Pembayaran')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Jumlah yang Dibayar')
                    ->currency('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->badge()
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
                                $invoice->status = 'partially_paid'; // Or a new 'partially_paid' status
                                $invoice->save();
                            }
                            // This should trigger a refresh of dependent components on the page
                            $livewire->dispatch('refresh');
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
                                $invoice->status = 'partially_paid';
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
                                $invoice->status = 'partially_paid'; // Or appropriate status like 'partially_paid'
                                $invoice->save();
                            } else if ($invoice->balance_due <= 0 && $invoice->status !== 'paid') {
                                // This case might occur if other payments cover the amount,
                                // or if an overpayment was deleted.
                                $invoice->status = 'paid';
                                $invoice->save();
                            } else if ($invoice->balance_due > 0 && $invoice->status === 'partially_paid' && $invoice->payments()->doesntExist()) {
                                // If all payments are deleted and it was 'partially_paid', maybe it should be 'unpaid' or 'partially_paid'
                                // For now, keep it simple: if balance > 0 and status was 'paid', revert to 'partially_paid'.
                                // If it was already 'partially_paid' or 'unpaid' or 'overdue', its status might not need to change
                                // unless all payments are gone and it should revert to a more initial state.
                                // Let's assume 'partially_paid' is fine if balance > 0.
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
                                    $invoice->status = 'partially_paid';
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
    public function isReadOnly(): bool
    {
        return false;
    }

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
}
