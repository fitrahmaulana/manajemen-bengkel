<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;


class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            // === TOMBOL AKSI BARU KITA ===
            Actions\Action::make('syncAndSuggestPrice')
                ->label('Hitung & Sarankan Harga Eceran')
                ->icon('heroicon-o-calculator')
                // Aksi ini hanya muncul jika item ini adalah item induk yang punya target eceran
                ->visible(fn(Item $record): bool => $record->is_convertible && $record->target_child_item_id !== null)
                ->color('warning')
                ->modalHeading('Saran Harga Eceran')
                ->modalDescription('Sistem telah menghitung harga eceran baru berdasarkan harga induk saat ini. Anda bisa menyesuaikannya sebelum menyimpan.')
                ->modalSubmitActionLabel('Simpan Harga Eceran')
                // Bagian ini mendefinisikan form yang ada di dalam modal
                ->form(function (Item $record) {
                    // 1. Lakukan kalkulasi terlebih dahulu
                    $suggestedPurchasePrice = 0;
                    $suggestedSellingPrice = 0;

                    if ($record->conversion_value > 0) {
                        $suggestedPurchasePrice = round($record->purchase_price / $record->conversion_value, 2);
                        // Gunakan fungsi helper yang sudah kita buat sebelumnya
                        $suggestedSellingPrice = ItemResource::roundUpToNearestHundred($record->selling_price / $record->conversion_value);
                    }

                    // 2. Tampilkan form dengan nilai default dari hasil kalkulasi
                    return [
                        TextInput::make('new_purchase_price')
                            ->label('Harga Beli Eceran yang Disarankan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default($suggestedPurchasePrice)
                            ->required(),
                        TextInput::make('new_selling_price')
                            ->label('Harga Jual Eceran yang Disarankan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default($suggestedSellingPrice)
                            ->required(),
                    ];
                })
                // Bagian ini adalah apa yang terjadi setelah tombol "Simpan" di modal ditekan
                ->action(function (Item $record, array $data) {
                    $childItem = $record->targetChild;

                    if ($childItem) {
                        $childItem->update([
                            'purchase_price' => $data['new_purchase_price'],
                            'selling_price' => $data['new_selling_price'],
                        ]);

                        // Cara yang benar untuk memanggil notifikasi
                        Notification::make()
                            ->title('Berhasil')
                            ->body("Harga '{$childItem->name}' telah berhasil diperbarui.")
                            ->success()
                            ->send();
                    } else {
                        // Cara yang benar untuk memanggil notifikasi
                        Notification::make()
                            ->title('Gagal')
                            ->body('Target item eceran tidak ditemukan.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
