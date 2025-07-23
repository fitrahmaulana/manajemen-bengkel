<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use Illuminate\Console\Command;

class UpdateInvoiceStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-invoice-status-command'; // Changed from 'command:name'

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for overdue invoices...');

        $invoices = \App\Models\Invoice::whereNotIn('status', [
            InvoiceStatus::PAID,
            InvoiceStatus::CANCELLED,
            InvoiceStatus::OVERDUE,
        ])
            ->whereDate('due_date', '<', now())
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No overdue invoices found.');

            return;
        }

        $this->info(sprintf('Found %d overdue invoices to update.', $invoices->count()));

        foreach ($invoices as $invoice) {
            $invoice->status = InvoiceStatus::OVERDUE;
            $invoice->save();
            $this->info(sprintf('Invoice #%s status updated to Overdue.', $invoice->invoice_number));
        }

        $this->info('Finished updating overdue invoices.');
    }
}
