<?php

namespace App\Providers;

use App\Models\InvoiceItem;
use App\Observers\InvoiceItemObserver;
use Illuminate\Support\ServiceProvider;

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
        InvoiceItem::observe(InvoiceItemObserver::class);
    }
}
