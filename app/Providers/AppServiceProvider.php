<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\Product;
use App\Observers\InvoiceObserver;
use App\Observers\ProductObserver;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
        Product::observe(ProductObserver::class);
    }
}
