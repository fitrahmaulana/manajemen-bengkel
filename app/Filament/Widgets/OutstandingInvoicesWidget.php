<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OutstandingInvoicesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $outstandingInvoices = Invoice::where('status', 'unpaid')->orWhere('status', 'partially_paid')->get();
        $outstandingInvoicesCount = $outstandingInvoices->count();
        $totalOutstandingAmount = $outstandingInvoices->sum('balance_due');

        return [
            Stat::make('Invoice Belum Dibayar', $outstandingInvoicesCount)
                ->description('Jumlah invoice yang belum lunas')
                ->descriptionIcon('heroicon-m-document-chart-bar')
                ->color('warning'),
            Stat::make('Total Piutang', 'Rp ' . number_format($totalOutstandingAmount, 0, ',', '.'))
                ->description('Total nominal piutang dari invoice')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
