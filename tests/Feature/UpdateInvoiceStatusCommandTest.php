<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateInvoiceStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_status_of_overdue_invoices()
    {
        // Create an invoice that is due yesterday and status is 'Pending'
        $overdueInvoice = Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => InvoiceStatus::PENDING,
            'total_amount' => 100,
        ]);

        // Create an invoice that is due tomorrow and status is 'Pending'
        $notOverdueInvoice = Invoice::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => InvoiceStatus::PENDING,
            'total_amount' => 100,
        ]);

        // Create an invoice that is due yesterday but status is 'Paid'
        $paidInvoice = Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => InvoiceStatus::PAID,
            'total_amount' => 100,
        ]);

        // Create an invoice that is due yesterday and status is already 'Overdue'
        $alreadyOverdueInvoice = Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => InvoiceStatus::OVERDUE,
            'total_amount' => 100,
        ]);

        $this->artisan('app:update-invoice-status-command')
            ->expectsOutput('Checking for overdue invoices...')
            ->expectsOutput('Found 1 overdue invoices to update.')
            ->expectsOutput(sprintf('Invoice #%s status updated to Overdue.', $overdueInvoice->invoice_number))
            ->expectsOutput('Finished updating overdue invoices.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('invoices', [
            'id' => $overdueInvoice->id,
            'status' => InvoiceStatus::OVERDUE,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $notOverdueInvoice->id,
            'status' => InvoiceStatus::PENDING, // Should not change
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $paidInvoice->id,
            'status' => InvoiceStatus::PAID, // Should not change
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $alreadyOverdueInvoice->id,
            'status' => InvoiceStatus::OVERDUE, // Should not change
        ]);
    }

    /** @test */
    public function it_handles_no_overdue_invoices()
    {
        // Create an invoice that is due tomorrow and status is 'Pending'
        Invoice::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => InvoiceStatus::PENDING,
            'total_amount' => 100,
        ]);

        // Create an invoice that is due yesterday but status is 'Paid'
        Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => InvoiceStatus::PAID,
            'total_amount' => 100,
        ]);

        $this->artisan('app:update-invoice-status-command')
            ->expectsOutput('Checking for overdue invoices...')
            ->expectsOutput('No overdue invoices found.')
            ->assertExitCode(0);
    }
}
