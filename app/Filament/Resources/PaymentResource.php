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
                    ->label('Tanggal Pembayaran')
                    ->required(),
                Forms\Components\TextInput::make('amount_paid')
                    ->label('Jumlah Dibayar')
                    ->numeric()
                    ->prefix('Rp.')
                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Bank Transfer',
                        'qris' => 'QRIS',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan (Opsional)')
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
                    ->label('Tanggal Pembayaran')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Jumlah Dibayar')
                    ->currency('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->badge()
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
                        $invoice = $record->invoice;
                        if ($invoice) {
                            self::updateInvoiceStatus($invoice);
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
                                    self::updateInvoiceStatus($invoice);
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
     * We will use it to update the invoice status (POS Style).
     */
    public static function afterCreate(Payment $payment, array $data): void
    {
        $invoice = $payment->invoice;
        if ($invoice) {
            self::updateInvoiceStatus($invoice);
        }
    }

    /**
     * This method is called after a payment record is updated.
     * We will use it to update the invoice status (POS Style).
     */
    public static function afterSave(Payment $payment, array $data): void
    {
        $invoice = $payment->invoice;
        if ($invoice) {
            self::updateInvoiceStatus($invoice);
        }
    }

    /**
     * Update invoice status based on payment balance (POS Style)
     */
    private static function updateInvoiceStatus(Invoice $invoice): void
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
}
