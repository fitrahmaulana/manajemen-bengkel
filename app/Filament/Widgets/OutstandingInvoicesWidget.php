<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class OutstandingInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $outstandingInvoices = Invoice::where('status', 'unpaid')->orWhere('status', 'partially_paid')->get();
        $outstandingInvoicesCount = $outstandingInvoices->count();
        $totalOutstandingAmount = $outstandingInvoices->sum('balance_due');
        $overdueInvoicesCount = Invoice::where('status', 'overdue')->count();

        return [
            Stat::make('Invoice Belum Dibayar', $outstandingInvoicesCount)
                ->description('Jumlah invoice yang belum lunas')
                ->descriptionIcon('heroicon-m-document-chart-bar')
                ->color('warning'),
            Stat::make('Total Piutang', 'Rp ' . number_format($totalOutstandingAmount, 0, ',', '.'))
                ->description('Total nominal piutang dari invoice')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Invoice Jatuh Tempo', $overdueInvoicesCount)
                ->description('Jumlah invoice yang sudah jatuh tempo')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}
