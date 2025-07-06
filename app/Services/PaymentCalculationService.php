<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;

class PaymentCalculationService
{
    /**
     * Parse currency amount from string
     */
    public static function parseCurrencyAmount($amount): float
    {
        return (float)str_replace(['Rp. ', '.'], ['', ''], (string)$amount);
    }

    /**
     * Format currency amount
     */
    public static function formatCurrency(float $amount): string
    {
        return 'Rp. ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Calculate change for new payment
     */
    public static function calculateChange(?Invoice $invoice, float $amountPaid): float
    {
        if (!$invoice) {
            return 0;
        }

        return $amountPaid - $invoice->balance_due;
    }

    /**
     * Calculate change for payment edit
     */
    public static function calculateEditChange(?Payment $payment, float $amountPaid): float
    {
        if (!$payment || !$payment->invoice) {
            return 0;
        }

        $invoice = $payment->invoice;
        $otherPayments = $invoice->payments()->where('id', '!=', $payment->id)->sum('amount_paid');
        $remainingBill = $invoice->total_amount - $otherPayments;

        return $amountPaid - $remainingBill;
    }

    /**
     * Get payment status
     */
    public static function getPaymentStatus(float $amountPaid, float $balanceDue): string
    {
        if ($amountPaid > $balanceDue) {
            return 'overpaid';
        } elseif ($amountPaid == $balanceDue) {
            return 'exact';
        } else {
            return 'underpaid';
        }
    }

    /**
     * Generate quick payment options
     */
    public static function generateQuickPaymentOptions(?Invoice $invoice): array
    {
        if (!$invoice || $invoice->balance_due <= 0) {
            return [];
        }

        $totalBill = $invoice->balance_due;
        $options = [];

        // Add exact amount option
        $options[(string)$totalBill] = '💰 Uang Pas';

        // Add rounded amount suggestions
        $roundingBases = [10000, 20000, 50000, 100000];
        $suggestions = [];

        foreach ($roundingBases as $base) {
            if ($base >= $totalBill) {
                $suggestions[] = ceil($totalBill / $base) * $base;
            }
        }

        if ($totalBill > 50000) {
            $suggestions[] = ceil($totalBill / 100000) * 100000;
        }

        $uniqueSuggestions = array_unique($suggestions);
        sort($uniqueSuggestions);
        $finalSuggestions = array_slice($uniqueSuggestions, 0, 3);

        foreach ($finalSuggestions as $suggestion) {
            if ($suggestion != $totalBill) {
                $options[(string)$suggestion] = '💵 ' . self::formatCurrency($suggestion);
            }
        }

        return $options;
    }

    /**
     * Get change display attributes based on payment status
     */
    public static function getChangeDisplayAttributes(float $amountPaid, float $balanceDue): array
    {
        if ($amountPaid >= $balanceDue && $amountPaid > 0) {
            return ['class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-green-700 font-bold text-xl'];
        } elseif ($amountPaid > 0 && $amountPaid < $balanceDue) {
            return ['class' => 'bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 font-bold text-xl'];
        }

        return ['class' => 'bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 font-bold text-xl'];
    }

    /**
     * Validate payment amount
     */
    public static function validatePaymentAmount(float $amountPaid, ?Payment $record = null): array
    {
        $errors = [];

        if ($amountPaid <= 0) {
            $errors[] = 'Jumlah pembayaran harus lebih dari 0.';
        }

        if ($record && $record->invoice) {
            $invoice = $record->invoice;
            $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
            $remainingBill = $invoice->total_amount - $otherPayments;

            if ($amountPaid < $remainingBill) {
                $errors[] = 'Jumlah pembayaran (' . self::formatCurrency($amountPaid) . ') kurang dari sisa tagihan (' . self::formatCurrency($remainingBill) . ')';
            }
        }

        return $errors;
    }
}
