<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $revenueToday = Invoice::whereDate('invoice_date', $today)->sum('total_amount');
        $revenueThisMonth = Invoice::whereBetween('invoice_date', [$startOfMonth, $endOfMonth])->sum('total_amount');

        // Data for the chart
        $revenueLast7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = Invoice::whereDate('invoice_date', $date)->sum('total_amount');
            $revenueLast7Days[] = $revenue;
        }

        return [
            Stat::make('Pendapatan (Omzet) Hari Ini', 'Rp '.number_format($revenueToday, 0, ',', '.'))
                ->description('Total pendapatan hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Pendapatan (Omzet) Bulan Ini', 'Rp '.number_format($revenueThisMonth, 0, ',', '.'))
                ->description('Total pendapatan bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Trend 7 Hari Terakhir', null)
                ->chart($revenueLast7Days)
                ->color('success'),
        ];
    }
}
