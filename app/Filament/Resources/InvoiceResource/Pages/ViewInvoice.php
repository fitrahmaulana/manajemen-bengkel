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
