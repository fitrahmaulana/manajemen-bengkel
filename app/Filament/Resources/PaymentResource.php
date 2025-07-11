<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\InvoiceCalculationTrait; // Import trait

class PaymentResource extends Resource
{
    use InvoiceCalculationTrait; // Gunakan trait
    protected static ?string $model = Payment::class;
    protected static bool $shouldRegisterNavigation = false;


    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $modelLabel = 'Pembayaran';
    protected static ?string $pluralModelLabel = 'Daftar Pembayaran';
    protected static ?int $navigationSort = 2;

    private static function getInvoiceFromContext(Forms\Get $get, $record, $livewire): ?\App\Models\Invoice
    {
        if ($record && $record->invoice) { // Check if $record->invoice exists
            // Edit mode: return the existing invoice from the payment record
            return $record->invoice;
        }

        // Create mode
        if ($livewire instanceof PaymentsRelationManager) {
            // If in RelationManager context, invoice is the owner record
            $invoice = $livewire->getOwnerRecord();
            if ($invoice instanceof \App\Models\Invoice) {
                return $invoice;
            }
        } else {
            // If in standalone form, get invoice_id from form state
            $invoiceId = $get('invoice_id');
            if ($invoiceId) {
                return \App\Models\Invoice::find($invoiceId);
            }
        }
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->relationship('invoice', 'invoice_number')
                            ->label('Invoice Number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->live()
                            ->hiddenOn(PaymentsRelationManager::class)
                            ->disabled(fn(string $operation) => $operation === 'edit'),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required(),

                        // Tampilkan total tagihan yang harus dibayar
                        Forms\Components\Placeholder::make('total_tagihan')
                            ->label('Total Tagihan')
                            ->content(function (Forms\Get $get, $record, $livewire) { // Explicitly type Forms\Get
                                $invoice = self::getInvoiceFromContext($get, $record, $livewire);
                                if ($invoice) {
                                    // For 'total_tagihan', in edit mode, show original total_amount. In create, show balance_due.
                                    if ($record) { // Edit mode
                                        return '🧾 ' . self::formatCurrency($invoice->total_amount);
                                    }
                                    // Create mode
                                    return '🧾 ' . self::formatCurrency($invoice->balance_due);
                                }
                                return '🧾 Pilih invoice terlebih dahulu';
                            })
                            ->extraAttributes([
                                'class' => 'border border-200 rounded-lg p-3 font-bold',
                                'style' => 'margin: 8px 0;'
                            ]),

                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Jumlah Uang Diterima')
                            ->numeric()
                            ->prefix('Rp.')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->required()
                            ->minValue(1)
                            ->helperText('Masukkan jumlah uang tunai yang diterima dari pelanggan')
                            ->live(debounce: 300)
                            ->afterStateUpdated(function ($state, $set, $get, $record, $livewire) {
                                if ($record) {
                                    // Edit mode
                                    $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$state);
                                    $invoice = $record->invoice;

                                    // Edit mode: hitung berdasarkan total tagihan dikurangi pembayaran lain
                                    $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                                    $remainingBill = $invoice->total_amount - $otherPayments;

                                    if ($amountPaid > $remainingBill) {
                                        $set('payment_status', 'overpaid');
                                    } elseif ($amountPaid == $remainingBill) {
                                        $set('payment_status', 'exact');
                                    } else {
                                        $set('payment_status', 'underpaid');
                                    }
                                } else {
                                    // Create mode
                                    $invoice = self::getInvoiceFromContext($get, $record, $livewire);

                                    if ($invoice) {
                                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$state);
                                        $balanceDue = $invoice->balance_due;

                                        if ($amountPaid > $balanceDue) {
                                            $overpayment = $amountPaid - $balanceDue;
                                            $set('change_amount', $overpayment);
                                            $set('payment_status', 'overpaid');
                                        } elseif ($amountPaid == $balanceDue) {
                                            $set('change_amount', 0);
                                            $set('payment_status', 'exact');
                                        } else {
                                            $set('change_amount', 0);
                                            $set('payment_status', 'underpaid');
                                        }
                                    }
                                }
                            })
                            ->default(function ($get, $record, $livewire) {
                                if ($record) {
                                    // Edit mode: return current payment amount
                                    return $record->amount_paid;
                                } else {
                                    // Create mode: suggest balance due
                                    $invoice = self::getInvoiceFromContext($get, $record, $livewire);
                                    if ($invoice) {
                                        return $invoice->balance_due;
                                    }
                                    return null;
                                }
                            }),

                        Forms\Components\ToggleButtons::make('quick_payment_options')
                            ->label('Pilihan Cepat Pembayaran')
                            ->helperText('Klik salah satu tombol untuk mengisi jumlah bayar secara otomatis.')
                            ->live()
                            ->afterStateUpdated(fn($state, $set) => $set('amount_paid', $state))
                            ->visible(fn(string $operation) => $operation === 'create')
                            ->options(function (Forms\Get $get, $livewire): array { // Explicitly type Forms\Get
                                $invoice = self::getInvoiceFromContext($get, null, $livewire); // $record is null in this context for options

                                if (!$invoice) {
                                    return [];
                                }

                                $totalBill = $invoice->balance_due;
                                if (!$totalBill || $totalBill <= 0) {
                                    return [];
                                }

                                $options = [];
                                $suggestions = [];

                                // 1. Opsi Uang Pas
                                $options[(string)$totalBill] = '💰 Uang Pas';

                                // 2. Daftar pembulatan umum
                                $roundingBases = [10000, 20000, 50000, 100000];

                                foreach ($roundingBases as $base) {
                                    // Jangan tampilkan saran yang lebih kecil dari tagihan
                                    if ($base < $totalBill) {
                                        $suggestion = ceil($totalBill / $base) * $base;

                                        if ($suggestion <= $totalBill) {
                                            $suggestion += $base;
                                        }

                                        // Batasi saran agar tidak terlalu jauh (misal: tagihan 12rb, jangan sarankan 100rb)
                                        if ($suggestion < $totalBill * 2.5 && $suggestion < 1000000) {
                                            $suggestions[] = $suggestion;
                                        }
                                    }
                                }

                                // Tambahkan juga pembulatan ke 100rb terdekat jika tagihan besar
                                if ($totalBill > 50000) {
                                    $suggestions[] = ceil($totalBill / 100000) * 100000;
                                }

                                // 3. Saring hasil dan ambil 3 terbaik
                                $uniqueSuggestions = array_unique($suggestions);
                                sort($uniqueSuggestions);

                                $finalSuggestions = array_slice($uniqueSuggestions, 0, 3);

                                foreach ($finalSuggestions as $s) {
                                    if ($s != $totalBill) {
                                        $options[(string)$s] = '💵 Rp. ' . number_format($s, 0, ',', '.');
                                    }
                                }

                                return $options;
                            })
                            ->columns(4),

                        // Kalkulator Kembalian - Real-time
                        Forms\Components\Placeholder::make('kembalian_calculator')
                            ->label('Kembalian')
                            ->content(function (Forms\Get $get, $record, $livewire) { // Explicitly type Forms\Get
                                $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                                $invoice = self::getInvoiceFromContext($get, $record, $livewire);

                                if (!$invoice) {
                                    return '💵 Rp. 0';
                                }

                                if ($record) { // Edit mode
                                    $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                                    $remainingBill = $invoice->total_amount - $otherPayments;
                                    $change = $amountPaid - $remainingBill;
                                } else { // Create mode
                                    $balanceDue = $invoice->balance_due;
                                    $change = $amountPaid - $balanceDue;
                                }
                                return '💵 ' . self::formatCurrency($change);
                            })
                            ->extraAttributes(function (Forms\Get $get, $record, $livewire) { // Explicitly type Forms\Get
                                $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                                $invoice = self::getInvoiceFromContext($get, $record, $livewire);

                                if (!$invoice) {
                                    // Default style if no invoice context
                                    return ['class' => 'bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 font-bold text-xl'];
                                }

                                $targetAmount = 0;
                                if ($record) { // Edit mode
                                    $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                                    $targetAmount = $invoice->total_amount - $otherPayments;
                                } else { // Create mode
                                    $targetAmount = $invoice->balance_due;
                                }

                                if ($amountPaid >= $targetAmount && $amountPaid > 0) {
                                    return ['class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-green-700 font-bold text-xl'];
                                } elseif ($amountPaid > 0 && $amountPaid < $targetAmount) {
                                    return ['class' => 'bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 font-bold text-xl'];
                                }
                                // Waiting for input or zero amount paid
                                return ['class' => 'bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 font-bold text-xl'];
                            }),

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
                    ])
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
                    ->sortable()
                    ->hiddenOn(PaymentsRelationManager::class),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Payment $record) {
                        // Store invoice info before deletion
                        $record->loadMissing('invoice');
                    })
                    ->after(function (Payment $record) {
                        // Update invoice status after payment deletion
                        $invoice = $record->invoice;
                        if ($invoice) {
                            $invoice->refresh();
                            if ($invoice->total_paid_amount >= $invoice->total_amount) {
                                $invoice->status = 'paid';
                            } else if ($invoice->payments()->exists()) {
                                $invoice->status = 'partially_paid';
                            } else {
                                $invoice->status = 'unpaid';
                            }
                            $invoice->save();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // Store invoice info before deletion
                            $records->load('invoice');
                        })
                        ->after(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // Get unique invoices and update their status
                            $invoices = $records->pluck('invoice')->unique('id');

                            foreach ($invoices as $invoice) {
                                if ($invoice) {
                                    $invoice->refresh();
                                    if ($invoice->total_paid_amount >= $invoice->total_amount) {
                                        $invoice->status = 'paid';
                                    } else if ($invoice->payments()->exists()) {
                                        $invoice->status = 'partially_paid';
                                    } else {
                                        $invoice->status = 'unpaid';
                                    }
                                    $invoice->save();
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayments::route('/'),
        ];
    }

    /**
     * Handle after payment actions (create, edit, delete) - Shared method
     */
    public static function handleAfterPaymentAction(?Payment $payment = null): void
    {
        if ($payment) {
            $invoice = $payment->invoice;
        } else {
            return;
        }

        if ($invoice) {
            // Panggil method dari InvoiceCalculationTrait untuk update status
            self::updateInvoiceStatus($invoice); // Ini akan menghandle save juga
            $invoice->refresh(); // Refresh untuk mendapatkan status terbaru dan balance_due

            // Logika notifikasi bisa tetap di sini atau disesuaikan berdasarkan status baru
            // Contoh notifikasi yang disesuaikan:
            $newStatus = $invoice->status;
            $balanceDue = $invoice->balance_due; // balance_due dari accessor di model Invoice
            $overpayment = $invoice->overpayment; // overpayment dari accessor di model Invoice

            if ($newStatus === 'paid') {
                if ($overpayment > 0) {
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Invoice Lunas dengan Kembalian')
                        ->body("Invoice {$invoice->invoice_number} lunas. Kembalian: Rp. " . number_format($overpayment, 0, ',', '.'))
                        ->success()
                        ->send();
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Invoice Lunas')
                        ->body("Invoice {$invoice->invoice_number} telah lunas.")
                        ->success()
                        ->send();
                }
            } elseif ($newStatus === 'partially_paid') {
                \Filament\Notifications\Notification::make()
                    ->title('💰 Status Pembayaran Diperbarui')
                    ->body("Invoice {$invoice->invoice_number} sebagian dibayar. Sisa tagihan: Rp. " . number_format($balanceDue, 0, ',', '.'))
                    ->info()
                    ->send();
            } elseif ($newStatus === 'unpaid') {
                \Filament\Notifications\Notification::make()
                    ->title('📋 Status Invoice Diperbarui')
                    ->body("Invoice {$invoice->invoice_number} menjadi belum dibayar. Sisa tagihan: Rp. " . number_format($balanceDue, 0, ',', '.'))
                    ->warning()
                    ->send();
            } elseif ($newStatus === 'overdue') {
                 \Filament\Notifications\Notification::make()
                    ->title('⚠️ Invoice Jatuh Tempo')
                    ->body("Invoice {$invoice->invoice_number} telah jatuh tempo. Sisa tagihan: Rp. " . number_format($balanceDue, 0, ',', '.'))
                    ->danger()
                    ->send();
            }
        }
    }
}
