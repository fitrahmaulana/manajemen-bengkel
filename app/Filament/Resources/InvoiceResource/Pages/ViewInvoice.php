<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use App\Models\Payment;
use App\Models\Invoice;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Filament\Infolists\Infolist; // Added for infolist method
use Filament\Infolists; // Added for components namespace
use Filament\Support\Enums\MaxWidth;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    // Infolist definition moved from InvoiceResource to here
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // === BAGIAN ATAS: DETAIL PELANGGAN & FAKTUR ===
                Infolists\Components\Section::make('Informasi Faktur')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('customer.name')->label('Pelanggan'),
                                    Infolists\Components\TextEntry::make('vehicle.license_plate')->label('No. Polisi'),
                                    Infolists\Components\TextEntry::make('vehicle.brand')->label('Merk Kendaraan'),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('invoice_number')->label('No. Invoice'),
                                    Infolists\Components\TextEntry::make('status')
                                        ->formatStateUsing(fn(string $state): string => match ($state) {
                                            'unpaid' => 'Belum Dibayar',
                                            'partially_paid' => 'Sebagian Dibayar',
                                            'paid' => 'Lunas',
                                            'overdue' => 'Terlambat',
                                        })
                                        ->badge()->color(fn(string $state): string => match ($state) {
                                            'unpaid' => 'gray',
                                            'partially_paid' => 'info',
                                            'paid' => 'success',
                                            'overdue' => 'danger',
                                        }),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('invoice_date')->label('Tanggal Invoice')->date('d M Y'),
                                    Infolists\Components\TextEntry::make('due_date')->label('Tanggal Jatuh Tempo')->date('d M Y'),
                                ]),
                            ]),
                    ]),

                // === BAGIAN TENGAH: DAFTAR JASA & BARANG ===
                Infolists\Components\Section::make('Detail Jasa / Layanan')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('services')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')->label('Nama Jasa')->weight('bold'),
                                Infolists\Components\TextEntry::make('pivot.description')->label('Deskripsi')->placeholder('Tidak ada deskripsi.'),
                                Infolists\Components\TextEntry::make('pivot.price')->label('Biaya')->currency('IDR'),
                            ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Detail Barang / Suku Cadang')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('display_name')
                                    ->label('Nama Barang')
                                    ->weight('bold')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('pivot.quantity')
                                    ->label('Kuantitas')
                                    ->formatStateUsing(function ($record) {
                                        $unit = $record->unit;
                                        return ($record->pivot->quantity ?? ' ') . " $unit";
                                    }),
                                Infolists\Components\TextEntry::make('pivot.price')->label('Harga Satuan')->currency('IDR'),
                                Infolists\Components\TextEntry::make('sub_total_calculated')
                                    ->label('Subtotal')
                                    ->currency('IDR')
                                    ->state(fn($record): float => ($record->pivot->quantity ?? 0) * ($record->pivot->price ?? 0)),
                                Infolists\Components\TextEntry::make('pivot.description')->label('Deskripsi')->columnSpanFull()->placeholder('Tidak ada deskripsi.'),

                            ])->columns(5),
                    ]),

                // === BAGIAN BAWAH: RINGKASAN BIAYA ===
                Infolists\Components\Section::make('Ringkasan Biaya')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('terms')
                                        ->label('Syarat & Ketentuan')
                                        ->placeholder('Tidak ada syarat & ketentuan.'),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('subtotal')->currency('IDR'),
                                    Infolists\Components\TextEntry::make('discount_value')
                                        ->label('Diskon')
                                        ->formatStateUsing(function ($record) {
                                            if ($record->discount_type === 'percentage') {
                                                return ($record->discount_value ?? 0) . '%';
                                            }
                                            // For fixed discount, format as currency
                                            return 'Rp. ' . number_format($record->discount_value ?? 0, 0, ',', '.');
                                        }),

                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('total_amount')
                                        ->label('Total Akhir')
                                        ->currency('IDR'),
                                    Infolists\Components\TextEntry::make('total_paid_amount')
                                        ->label('Total Dibayar')
                                        ->currency('IDR')
                                        ->state(fn($record) => $record->total_paid_amount)
                                        ->weight('semibold'),
                                    Infolists\Components\TextEntry::make('balance_due')
                                        ->label('Sisa Tagihan')
                                        ->currency('IDR')
                                        ->state(fn($record) => $record->balance_due)
                                        ->weight('bold')
                                        ->color('danger') // Red untuk urgent
                                        ->size('lg')
                                        ->visible(fn($record) => $record->balance_due > 0)
                                        ->icon('heroicon-o-exclamation-triangle'),
                                    Infolists\Components\TextEntry::make('overpayment')
                                        ->label('Kembalian')
                                        ->currency('IDR')
                                        ->state(fn($record) => $record->overpayment)
                                        ->weight('bold')
                                        ->color('success') // Green untuk positive
                                        ->size('lg')
                                        ->visible(fn($record) => $record->overpayment > 0)
                                        ->icon('heroicon-o-banknotes'),
                                ]),
                            ]),
                    ]),
                // Note: The 'Riwayat Pembayaran' (Payment History) section using RepeatableEntry
                // has been intentionally omitted here as it's replaced by the PaymentsRelationManager.
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('recordPayment')
                ->label('Catat Pembayaran')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->visible(fn(Invoice $record): bool => $record->balance_due > 0)
                ->modalHeading('Pembayaran & Kembalian')
                ->modalDescription('Masukkan jumlah uang yang diterima. Kembalian akan dihitung otomatis.')
                ->modalSubmitActionLabel('💾 Catat Pembayaran')
                ->modalWidth(MaxWidth::Large) // Set modal width to Large
                ->form([
                    Hidden::make('payment_date')
                        ->label('Tanggal Pembayaran')
                        ->default(now())
                        ->required(),

                    // Tampilkan total tagihan yang harus dibayar
                    Placeholder::make('total_tagihan')
                        ->label('Total Tagihan')
                        ->content(fn(ViewRecord $livewire) => '🧾 Rp. ' . number_format($livewire->record->balance_due, 0, ',', '.'))
                        ->extraAttributes([
                            'class' => 'border border-200 rounded-lg p-3 font-bold ',
                            'style' => 'margin: 8px 0;'
                        ]),

                    TextInput::make('amount_paid')
                        ->label('Jumlah Uang Diterima')
                        ->numeric()
                        ->prefix('Rp.')
                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                        ->required()
                        ->minValue(1)
                        ->helperText('Masukkan jumlah uang tunai yang diterima dari pelanggan')
                        ->live(debounce: 300) // Real-time calculation dengan debounce
                        ->afterStateUpdated(function ($state, $set, ViewRecord $livewire) {
                            $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$state);
                            $balanceDue = $livewire->record->balance_due;

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
                        }),

                    ToggleButtons::make('quick_payment_options')
                        ->label('Pilihan Cepat Pembayaran')
                        ->helperText('Klik salah satu tombol untuk mengisi jumlah bayar secara otomatis.')
                        ->live() // Wajib agar bisa berinteraksi dengan field lain
                        ->afterStateUpdated(fn($state, $set) => $set('amount_paid', $state)) // Mengisi field 'amount_paid'
                        ->options(function (ViewRecord $livewire): array {
                            $totalBill = $livewire->record->balance_due;
                            if (!$totalBill) {
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
                        ->columns(4), // Atur jumlah kolom tombol

                    // Kalkulator Kembalian - Real-time
                    Placeholder::make('kembalian_calculator')
                        ->label('Kembalian')
                        ->content(function ($get, ViewRecord $livewire) {
                            $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                            $balanceDue = $livewire->record->balance_due;
                            $change = $amountPaid - $balanceDue;
                            return '💵 Rp. ' . number_format($change, 0, ',', '.');
                        })
                        ->extraAttributes(function ($get, ViewRecord $livewire) {
                            $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                            $balanceDue = $livewire->record->balance_due;

                            if ($amountPaid >= $balanceDue && $amountPaid > 0) {
                                // Overpayment or exact payment - green
                                return ['class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-green-700 font-bold text-xl'];
                            } elseif ($amountPaid > 0 && $amountPaid < $balanceDue) {
                                // Underpayment - red
                                return ['class' => 'bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 font-bold text-xl'];
                            } else {
                                // Waiting for input - gray
                                return ['class' => 'bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 font-bold text-xl'];
                            }
                        }),

                    Select::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->options([
                            'cash' => 'Cash',
                            'transfer' => 'Bank Transfer',
                            'qris' => 'QRIS',
                        ])
                        ->default('cash')
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan (Optional)')
                        ->rows(3),
                ])
                ->action(function (array $data, Invoice $record, $livewire) {
                    try {
                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$data['amount_paid']);
                        $balanceDue = $record->balance_due;
                        $changeAmount = max(0, $amountPaid - $balanceDue);

                        // Validasi pembayaran minimum
                        if ($amountPaid <= 0) {
                            Notification::make()
                                ->title('⚠️ Jumlah Pembayaran Tidak Valid')
                                ->body('Jumlah pembayaran harus lebih dari 0.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Peringatan jika pembayaran kurang
                        if ($amountPaid < $balanceDue) {
                            Notification::make()
                                ->title('⚠️ Pembayaran Kurang')
                                ->body('Jumlah pembayaran (Rp. ' . number_format($amountPaid, 0, ',', '.') . ') kurang dari total tagihan (Rp. ' . number_format($balanceDue, 0, ',', '.') . ')')
                                ->warning()
                                ->send();
                            // Tetap lanjutkan untuk pembayaran parsial
                        }

                        // Buat record pembayaran
                        $record->payments()->create([
                            'payment_date' => $data['payment_date'],
                            'amount_paid' => $amountPaid,
                            'payment_method' => $data['payment_method'],
                            'notes' => $data['notes'],
                        ]);

                        // Refresh record untuk mendapatkan kalkulasi terbaru
                        $record->refresh();

                        // Update status berdasarkan total pembayaran
                        if ($record->total_paid_amount >= $record->total_amount) {
                            $record->status = 'paid';
                            $record->save();

                            // Notifikasi untuk transaksi lunas
                            if ($record->overpayment > 0) {
                                Notification::make()
                                    ->title('✅ Transaksi Lunas')
                                    ->body('💰 KEMBALIAN: Rp. ' . number_format($record->overpayment, 0, ',', '.') . ' - Berikan kepada pelanggan!')
                                    ->success()
                                    ->duration(15000) // 15 detik untuk mengambil uang kembalian
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('✅ Transaksi Lunas')
                                    ->body('💯 Pembayaran pas - Tidak ada kembalian')
                                    ->success()
                                    ->send();
                            }
                        } else {
                            $record->status = 'partially_paid';
                            $record->save();
                            $remaining = $record->balance_due;

                            Notification::make()
                                ->title('💰 Pembayaran Sebagian Berhasil')
                                ->body('Sisa tagihan: Rp. ' . number_format($remaining, 0, ',', '.'))
                                ->info()
                                ->send();
                        }

                        // Success notification untuk pembayaran yang berhasil dicatat
                        Notification::make()
                            ->title('✅ Pembayaran Berhasil Dicatat')
                            ->body('Jumlah: Rp. ' . number_format($amountPaid, 0, ',', '.') . ' via ' . strtoupper($data['payment_method']))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Kesalahan Mencatat Pembayaran')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }

                    // Refresh halaman untuk update data
                    $livewire->dispatch('refresh');
                }),
            Actions\EditAction::make()->label('Edit Faktur')->icon('heroicon-o-pencil'),
            Actions\Action::make('printInvoice')
                ->label('Cetak Faktur')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn(Invoice $record): string => route('filament.admin.resources.invoices.print', $record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make()
                ->label('Hapus Faktur')
                ->icon('heroicon-o-trash')
                ->before(function (Invoice $record) {
                    // Restore stock before deleting invoice
                    foreach ($record->items as $itemPivot) {
                        $itemModel = \App\Models\Item::find($itemPivot->id);
                        if ($itemModel) {
                            $quantityToRestore = $itemPivot->pivot->quantity;
                            $itemModel->stock += $quantityToRestore;
                            $itemModel->save();
                        }
                    }
                }),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make()
                ->after(function (Invoice $record) {
                    // Re-decrement stock for items on the restored invoice
                    foreach ($record->items as $itemPivot) {
                        $itemModel = \App\Models\Item::find($itemPivot->id);
                        if ($itemModel) {
                            $quantityToDecrement = $itemPivot->pivot->quantity;

                            // Check if stock would go negative
                            if ($itemModel->stock >= $quantityToDecrement) {
                                $itemModel->stock -= $quantityToDecrement;
                                $itemModel->save();
                            } else {
                                // Handle negative stock scenario
                                Notification::make()
                                    ->title('⚠️ Stock Tidak Mencukupi')
                                    ->body("Item {$itemModel->name} tidak memiliki stock yang cukup untuk di-restore.")
                                    ->warning()
                                    ->send();
                            }
                        }
                    }
                }),
        ];
    }
    public function getRelationManagers(): array
    {
        // Hanya tampilkan PaymentsRelationManager di halaman ini
        return [
            PaymentsRelationManager::class,
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
