<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Panggil fungsi kalkulasi dari resource
        $totals = static::$resource::calculateTotals($this->data);

        // Tambahkan hasil kalkulasi ke dalam array data yang akan disimpan
        $data['subtotal'] = $totals['subtotal'];
        $data['total_amount'] = $totals['total_amount'];

        return $data;
    }
}
