<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $modelLabel = 'Pembayaran';
    protected static ?string $pluralModelLabel = 'Daftar Pembayaran';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Payment Date')
                    ->required(),
                Forms\Components\TextInput::make('amount_paid')
                    ->label('Amount Paid')
                    ->numeric()
                    ->prefix('Rp.')
                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                    ->required(),
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
                    ->label('Reference Number'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Payment $record) {
                        // After deleting a payment, refresh the associated invoice
                        // to recalculate balance and potentially update status.
                        $invoice = $record->invoice;
                        if ($invoice) {
                            $invoice->refresh();
                            // Check if the invoice status needs to be updated,
                            // e.g., from 'paid' to 'partially_paid' if balance is now positive
                            if ($invoice->balance_due > 0 && $invoice->status === 'paid') {
                                $invoice->status = 'partially_paid'; // Or appropriate status
                                $invoice->save();
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function (Payment $record) {
                                $invoice = $record->invoice;
                                if ($invoice) {
                                    $invoice->refresh();
                                    if ($invoice->balance_due > 0 && $invoice->status === 'paid') {
                                        $invoice->status = 'partially_paid';
                                        $invoice->save();
                                    }
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    /**
     * This method is called after a payment record is created.
     * We will use it to update the invoice status if it becomes fully paid.
     */
    public static function afterCreate(Payment $payment, array $data): void
    {
        $invoice = $payment->invoice;
        if ($invoice) {
            $invoice->refresh(); // Recalculate balance_due
            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
                $invoice->save();
            } else if ($invoice->status !== 'overdue') { // Avoid overriding overdue
                $invoice->status = 'partially_paid'; // Or 'partially_paid' if you implement that
                $invoice->save();
            }
        }
    }

    /**
     * This method is called after a payment record is updated.
     * We will use it to update the invoice status.
     */
    public static function afterSave(Payment $payment, array $data): void
    {
        $invoice = $payment->invoice;
        if ($invoice) {
            $invoice->refresh();
            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
                $invoice->save();
            } else if ($invoice->status !== 'overdue') {
                $invoice->status = 'partially_paid'; // Or 'partially_paid'
                $invoice->save();
            }
        }
    }
}
