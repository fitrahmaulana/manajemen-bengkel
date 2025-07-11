<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Invoice;
use Carbon\Carbon;

class UpdateInvoiceStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_status_of_overdue_invoices()
    {
        // Create an invoice that is due yesterday and status is 'Pending'
        $overdueInvoice = Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'Pending',
            'total_amount' => 100,
        ]);

        // Create an invoice that is due tomorrow and status is 'Pending'
        $notOverdueInvoice = Invoice::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => 'Pending',
            'total_amount' => 100,
        ]);

        // Create an invoice that is due yesterday but status is 'Paid'
        $paidInvoice = Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'Paid',
            'total_amount' => 100,
        ]);

        // Create an invoice that is due yesterday and status is already 'Overdue'
        $alreadyOverdueInvoice = Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'Overdue',
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
            'status' => 'Overdue',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $notOverdueInvoice->id,
            'status' => 'Pending', // Should not change
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $paidInvoice->id,
            'status' => 'Paid', // Should not change
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $alreadyOverdueInvoice->id,
            'status' => 'Overdue', // Should not change
        ]);
    }

    /** @test */
    public function it_handles_no_overdue_invoices()
    {
        // Create an invoice that is due tomorrow and status is 'Pending'
        Invoice::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => 'Pending',
            'total_amount' => 100,
        ]);

        // Create an invoice that is due yesterday but status is 'Paid'
        Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'Paid',
            'total_amount' => 100,
        ]);

        $this->artisan('app:update-invoice-status-command')
            ->expectsOutput('Checking for overdue invoices...')
            ->expectsOutput('No overdue invoices found.')
            ->assertExitCode(0);
    }
}
