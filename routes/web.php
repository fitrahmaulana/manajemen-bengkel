<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Filament\Resources\InvoiceResource;

// Route for printing invoices
Route::get('/admin/invoices/{record}/print', [InvoiceResource::class, 'printInvoice'])
    ->name('filament.admin.resources.invoices.print');
