<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $invoicesThisMonth = Invoice::whereBetween('invoice_date', [$startOfMonth, $endOfMonth]);
        $totalInvoicesThisMonth = $invoicesThisMonth->count();
        $averageTransactionValue = $invoicesThisMonth->avg('total_amount');
        $totalCustomers = Customer::count();

        return [
            Stat::make('Total Invoice Bulan Ini', $totalInvoicesThisMonth)
                ->description('Jumlah invoice bulan ini')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),
            Stat::make('Rata-rata Nilai Transaksi', 'Rp '.number_format($averageTransactionValue, 0, ',', '.'))
                ->description('Rata-rata nilai transaksi bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
            Stat::make('Total Pelanggan', $totalCustomers)
                ->description('Jumlah pelanggan terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
