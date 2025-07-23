<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Enums\DiscountType;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Edit PO'),
            Actions\Action::make('complete')
                ->label('Complete')
                ->action(function (PurchaseOrder $record) {
                    if ($record->status === PurchaseOrderStatus::COMPLETED) {
                        Notification::make()
                            ->title('Error')
                            ->body('Purchase order is already completed.')
                            ->danger()
                            ->send();

                        return;
                    }

                    foreach ($record->purchaseOrderItems as $item) {
                        $item->item->stock += $item->quantity;
                        $item->item->save();
                    }

                    $record->status = PurchaseOrderStatus::COMPLETED;
                    $record->save();

                    Notification::make()
                        ->title('Success')
                        ->body('Purchase order has been completed and stock has been updated.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::DRAFT),
            Actions\Action::make('revert')
                ->label('Kembalikan ke Draft')
                ->action(function (PurchaseOrder $record) {
                    if ($record->status !== PurchaseOrderStatus::COMPLETED) {
                        Notification::make()
                            ->title('Error')
                            ->body('Purchase order must be completed before reverting.')
                            ->danger()
                            ->send();

                        return;
                    }
                    foreach ($record->purchaseOrderItems as $item) {
                        $item->item->decrement('stock', $item->quantity);
                    }

                    $record->status = PurchaseOrderStatus::DRAFT;
                    $record->save();

                    Notification::make()->title('Sukses')->body('Pesanan dikembalikan ke draft dan stok telah disesuaikan.')->success()->send();
                })
                ->requiresConfirmation()
                ->color('warning')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::COMPLETED),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pesanan Pembelian')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('supplier.name')->label('Supplier'),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('po_number')->label('No. PO'),
                                    Infolists\Components\TextEntry::make('status')
                                        ->badge(),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('order_date')->label('Tanggal PO')->date('d M Y'),
                                    Infolists\Components\TextEntry::make('payment_status')
                                        ->label('Status Pembayaran')
                                        ->badge(),
                                ]),
                            ]),
                    ]),

                Infolists\Components\Section::make('Detail Barang')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('purchaseOrderItems')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('item.display_name')
                                    ->label('Nama Barang')
                                    ->weight('bold')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Kuantitas')
                                    ->formatStateUsing(function ($record) {
                                        $unit = $record->item->unit;

                                        return ($record->quantity ?? ' ')." $unit";
                                    }),
                                Infolists\Components\TextEntry::make('price')->label('Harga Satuan')->currency('IDR'),
                                Infolists\Components\TextEntry::make('sub_total_calculated')
                                    ->label('Subtotal')
                                    ->currency('IDR')
                                    ->state(fn ($record): float => ($record->quantity ?? 0) * ($record->price ?? 0)),
                                Infolists\Components\TextEntry::make('description')->label('Deskripsi')->columnSpanFull()->placeholder('Tidak ada deskripsi.'),

                            ])->columns(5),
                    ]),

                Infolists\Components\Section::make('Ringkasan Biaya')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('notes')
                                        ->label('Catatan')
                                        ->placeholder('Tidak ada catatan.'),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('subtotal')->currency('IDR'),
                                    Infolists\Components\TextEntry::make('discount_value')
                                        ->label('Diskon')
                                        ->formatStateUsing(function ($record) {
                                            if ($record->discount_type === DiscountType::PERCENTAGE->value) {
                                                return ($record->discount_value ?? 0).'%';
                                            }

                                            return 'Rp. '.number_format($record->discount_value ?? 0, 0, ',', '.');
                                        }),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('total_amount')
                                        ->label('Total Akhir')
                                        ->currency('IDR'),
                                    Infolists\Components\TextEntry::make('total_paid_amount')
                                        ->label('Total Dibayar')
                                        ->currency('IDR')
                                        ->state(fn ($record) => $record->total_paid_amount)
                                        ->weight('semibold'),
                                    Infolists\Components\TextEntry::make('balance_due')
                                        ->label('Sisa Tagihan')
                                        ->currency('IDR')
                                        ->state(fn ($record) => $record->balance_due)
                                        ->weight('bold')
                                        ->color('danger') // Red untuk urgent
                                        ->size('lg')
                                        ->visible(fn ($record) => $record->balance_due > 0)
                                        ->icon('heroicon-o-exclamation-triangle'),
                                    Infolists\Components\TextEntry::make('overpayment')
                                        ->label('Kembalian')
                                        ->currency('IDR')
                                        ->state(fn ($record) => $record->overpayment)
                                        ->weight('bold')
                                        ->color('success') // Green untuk positive
                                        ->size('lg')
                                        ->visible(fn ($record) => $record->overpayment > 0)
                                        ->icon('heroicon-o-banknotes'),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
