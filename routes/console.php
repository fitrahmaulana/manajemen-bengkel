<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:update-invoice-status-command')
    ->daily()
    ->onFailure(function () {
        \Log::error('Failed to update overdue invoice statuses.');
    });
