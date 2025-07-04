<?php

namespace App\Traits;

use App\Models\Invoice;
use Filament\Forms\Get;
use Filament\Forms\Set;

trait InvoiceCalculationTrait
{
    /**
     * Calculate invoice totals with optimized performance
     * Reduces server load by doing minimal processing
     */
    public static function calculateInvoiceTotals(Get $get, Set $set): void
    {
        $servicesData = $get('services') ?? [];
        $itemsData = $get('items') ?? [];

        $subtotal = 0;

        // Calculate subtotal from services
        foreach ($servicesData as $service) {
            if (!empty($service['service_id']) && isset($service['price'])) {
                $price = self::parseCurrencyValue($service['price']);
                $subtotal += $price;
            }
        }

        // Calculate subtotal from items
        foreach ($itemsData as $item) {
            if (!empty($item['item_id']) && isset($item['price']) && isset($item['quantity'])) {
                $price = self::parseCurrencyValue($item['price']);
                $quantity = (int)($item['quantity'] ?? 1);
                $subtotal += $price * $quantity;
            }
        }

        // Calculate discount
        $discountValue = self::parseCurrencyValue($get('discount_value') ?? '0');
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
    public static function parseCurrencyValue($value): float
    {
        if (!$value) return 0;

        return (float)str_replace(
            ['Rp. ', '.', ','],
            ['', '', '.'],
            (string)$value
        );
    }

    /**
     * Format currency for display
     */
    public static function formatCurrency($value): string
    {
        return 'Rp. ' . number_format($value, 0, ',', '.');
    }

    /**
     * Update invoice status based on payments (POS style logic)
     */
    public static function updateInvoiceStatus(Invoice $invoice): void
    {
        $totalAmount = $invoice->total_amount;
        $totalPaid = $invoice->payments()->sum('amount_paid');

        //jika tidak ada pembayaran, statusnya unpaid atau overdue
        if ($totalPaid <= 0) {
            $status = $invoice->due_date < now() ? 'overdue' : 'unpaid';
        } elseif ($totalPaid >= $totalAmount) { //jika sudah terbayar penuh
            $status = 'paid'; // POS style: overpayment is still "paid"
        } else { //jika ada pembayaran tapi belum penuh
            $status = $invoice->due_date < now() ? 'overdue' : 'partially_paid';
        }

        $invoice->update(['status' => $status]);
    }

    /**
     * Get payment status details for display
     */
    public static function getPaymentStatusDetails(Invoice $invoice): array
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
    public static function createDebouncedCalculation(): callable
    {
        return function (Get $get, Set $set) {
            // Only calculate if we have meaningful data
            $services = $get('services') ?? [];
            $items = $get('items') ?? [];

            if (empty($services) && empty($items)) {
                return; // Skip calculation if no items/services
            }

            self::calculateInvoiceTotals($get, $set);
        };
    }

    /**
     * Validation helper for stock checking
     */
    public static function validateStockAvailability(
        int $itemId,
        int $quantity,
        ?Invoice $currentInvoice = null
    ): array {
        $item = \App\Models\Item::find($itemId);

        if (!$item) {
            return [
                'valid' => false,
                'message' => 'Item tidak valid.'
            ];
        }

        $originalQuantity = 0;
        if ($currentInvoice) {
            $originalItem = $currentInvoice->items()->where('item_id', $itemId)->first();
            if ($originalItem) {
                $originalQuantity = $originalItem->pivot->quantity;
            }
        }

        $neededStock = $currentInvoice ? max(0, $quantity - $originalQuantity) : $quantity;

        if ($neededStock > 0 && $item->stock < $neededStock) {
            $hasSplitOption = !$item->is_convertible &&
                             $item->sourceParents()->where('stock', '>', 0)->exists();

            if ($hasSplitOption) {
                return [
                    'valid' => false,
                    'message' => "Stok {$item->name} tidak cukup untuk menambah {$neededStock} {$item->unit}, silakan gunakan opsi 'Pecah Stok'.",
                    'can_split' => true
                ];
            } else {
                return [
                    'valid' => false,
                    'message' => "Stok {$item->name} hanya {$item->stock} {$item->unit}. Kuantitas ({$quantity} {$item->unit}) melebihi stok yang tersedia.",
                    'can_split' => false
                ];
            }
        }

        return ['valid' => true];
    }
}
