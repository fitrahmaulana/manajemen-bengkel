<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Item;
use Filament\Forms\Get;
use Filament\Forms\Set;

class InvoiceService
{
    /**
     * Calculate invoice totals with optimized performance
     * Reduces server load by doing minimal processing
     */
    public function calculateInvoiceTotals(Get $get, Set $set): void
    {
        $servicesData = $get('invoiceServices') ?? [];
        $itemsData = $get('invoiceItems') ?? [];

        $subtotal = 0;

        // Calculate subtotal from services
        foreach ($servicesData as $service) {
            if (! empty($service['service_id']) && isset($service['price'])) {
                $price = $this->parseCurrencyValue($service['price']);
                $subtotal += $price;
            }
        }

        // Calculate subtotal from items
        foreach ($itemsData as $item) {
            if (! empty($item['item_id']) && isset($item['price']) && isset($item['quantity'])) {
                $price = $this->parseCurrencyValue($item['price']);
                $quantity = (float) ($item['quantity'] ?? 1.0); // Changed to float
                $subtotal += $price * $quantity;
            }
        }

        // Calculate discount
        $discountValue = $this->parseCurrencyValue($get('discount_value') ?? '0');
        $finalDiscount = 0;

        if ($get('discount_type') === 'percentage' && $discountValue > 0) {
            $finalDiscount = ($subtotal * $discountValue) / 100;
        } else {
            $finalDiscount = $discountValue;
        }

        $total = max(0, $subtotal - $finalDiscount); // Ensure total is never negative

        $set('subtotal', $subtotal);
        $set('total_amount', $total);
    }

    /**
     * Parse currency value from masked input
     */
    public function parseCurrencyValue($value): float
    {
        if (! $value) {
            return 0;
        }

        return (float) str_replace(
            ['Rp. ', '.', ','],
            ['', '', '.'],
            (string) $value
        );
    }

    /**
     * Format currency for display
     */
    public function formatCurrency($value): string
    {
        return 'Rp. '.number_format($value, 0, ',', '.');
    }

    /**
     * Update invoice status based on payments (POS style logic)
     */
    public function updateInvoiceStatus(Invoice $invoice): void
    {
        $totalAmount = $invoice->total_amount;
        $totalPaid = $invoice->payments()->sum('amount_paid');

        if ($totalPaid <= 0) {
            $status = $invoice->due_date < now() ? InvoiceStatus::OVERDUE : InvoiceStatus::UNPAID;
        } elseif ($totalPaid >= $totalAmount) {
            $status = InvoiceStatus::PAID;
        } else {
            $status = $invoice->due_date < now() ? InvoiceStatus::OVERDUE : InvoiceStatus::PARTIALLY_PAID;
        }

        $invoice->update(['status' => $status]);
    }

    /**
     * Get payment status details for display
     */
    public function getPaymentStatusDetails(Invoice $invoice): array
    {
        $totalAmount = $invoice->total_amount;
        $totalPaid = $invoice->payments()->sum('amount_paid');
        $remaining = $totalAmount - $totalPaid;

        return [
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'remaining' => max(0, $remaining),
            'overpayment' => max(0, -$remaining),
            'is_overpaid' => $totalPaid > $totalAmount,
            'is_fully_paid' => $totalPaid >= $totalAmount,
            'is_partially_paid' => $totalPaid > 0 && $totalPaid < $totalAmount,
            'is_unpaid' => $totalPaid <= 0,
        ];
    }

    /**
     * Create optimized afterStateUpdated function for price fields
     */
    public function createDebouncedCalculation(): callable
    {
        return function (Get $get, Set $set) {
            // Only calculate if we have meaningful data
            $services = $get('services') ?? [];
            $items = $get('items') ?? [];

            if (empty($services) && empty($items)) {
                return; // Skip calculation if no items/services
            }

            $this->calculateInvoiceTotals($get, $set);
        };
    }

    /**
     * Validation helper for stock checking
     */
    public function validateStockAvailability(
        int $itemId,
        float $quantity,
        ?Invoice $currentInvoice = null
    ): array {
        $item = Item::find($itemId);

        if (! $item) {
            return ['valid' => false, 'message' => 'Item tidak valid.'];
        }

        // Kuantitas lama jika invoice sedang diedit
        $originalQuantity = 0;
        if ($currentInvoice) {
            $originalLine = $currentInvoice->relationLoaded('invoiceItems')
                ? $currentInvoice->invoiceItems->firstWhere('item_id', $itemId)
                : $currentInvoice->invoiceItems()->where('item_id', $itemId)->first();

            if ($originalLine) {
                $originalQuantity = (float) $originalLine->quantity;
            }
        }

        // Validasi: qty baru tidak boleh melebihi stok sekarang + qty lama (yang bisa direstore)
        if ($quantity > ($item->stock + $originalQuantity)) {
            $hasSplitOption = Item::where('product_id', $item->product_id)
                ->where('id', '!=', $item->id)
                ->where('stock', '>', 0)
                ->when($item->volume_value && $item->base_volume_unit, function ($query) use ($item) {
                    $query->where('volume_value', '>', $item->volume_value)
                        ->where('base_volume_unit', $item->base_volume_unit);
                })
                ->exists();

            if ($hasSplitOption) {
                return [
                    'valid' => false,
                    'message' => "Stok {$item->display_name} tidak cukup untuk menambah {$quantity} {$item->unit}, silakan gunakan opsi 'Pecah Stok'.",
                    'can_split' => true,
                ];
            }

            return [
                'valid' => false,
                'message' => "Stok {$item->display_name} hanya {$item->stock} {$item->unit}. Kuantitas ({$quantity} {$item->unit}) melebihi stok yang tersedia.",
                'can_split' => false,
            ];
        }

        return ['valid' => true];
    }
}
