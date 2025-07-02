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

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $recordTitleAttribute = 'payment_date';
    protected static ?string $title = 'Riwayat Pembayaran';

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    /**
     * Update invoice status based on payment balance (POS Style)
     */
    private function updateInvoiceStatus(Invoice $invoice): void
    {
        $invoice->refresh();

        if ($invoice->total_paid_amount >= $invoice->total_amount) {
            // POS Style: Any payment >= total = Lunas
            $invoice->status = 'paid';
        } else {
            // If there are any payments, it's partially paid
            if ($invoice->payments()->exists()) {
                $invoice->status = 'partially_paid';
            } else {
                // No payments exist, set to unpaid status
                $invoice->status = 'unpaid';
            }
        }

        $invoice->save();
    }

    /**
     * Parse currency mask to float value
     */
    private function parseCurrencyMask(string $value): float
    {
        return (float)str_replace(['Rp. ', '.'], ['', ''], $value);
    }

    /**
     * Handle after payment actions (create, edit, delete)
     */
    private function handleAfterPaymentAction(RelationManager $livewire): void
    {
        /** @var Invoice $invoice */
        $invoice = $livewire->getOwnerRecord();
        if ($invoice) {
            $this->updateInvoiceStatus($invoice);
            $livewire->dispatch('refresh');
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),
                Forms\Components\TextInput::make('amount_paid')
                    ->label('Jumlah Dibayar')
                    ->numeric()
                    ->prefix('Rp.')
                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                    ->required()
                    ->live(onBlur: true)
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
                    ->label('Catat Pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure amount_paid is correctly parsed from currency mask
                        $data['amount_paid'] = $this->parseCurrencyMask((string)$data['amount_paid']);
                        return $data;
                    })
                    ->after(function (RelationManager $livewire, Model $record) {
                        $this->handleAfterPaymentAction($livewire);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount_paid'] = $this->parseCurrencyMask((string)$data['amount_paid']);
                        return $data;
                    })
                    ->after(function (RelationManager $livewire, Model $record) {
                        $this->handleAfterPaymentAction($livewire);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (RelationManager $livewire, Payment $record) {
                        $this->handleAfterPaymentAction($livewire);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (RelationManager $livewire, \Illuminate\Database\Eloquent\Collection $records) {
                            $this->handleAfterPaymentAction($livewire);
                        }),
                ]),
            ]);
    }

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
        return $invoice->balance_due > 0;
    }
}
