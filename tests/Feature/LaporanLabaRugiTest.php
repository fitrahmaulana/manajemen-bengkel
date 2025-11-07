<?php

namespace Tests\Feature;

use App\Filament\Pages\LaporanLabaRugi;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanLabaRugiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_render_laporan_laba_rugi_page()
    {
        Livewire::test(LaporanLabaRugi::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_calculate_profit_and_loss_correctly_on_accrual_basis()
    {
        $this->seed();

        $item1 = Item::factory()->create(['purchase_price' => 1000, 'selling_price' => 1500]);
        $item2 = Item::factory()->create(['purchase_price' => 2000, 'selling_price' => 3000]);

        $invoice1 = Invoice::factory()->create(['invoice_date' => '2023-01-15', 'total_amount' => 6000]);
        $invoice2 = Invoice::factory()->create(['invoice_date' => '2023-02-10', 'total_amount' => 4500]);

        // Invoice 1 Items
        InvoiceItem::factory()->create(['invoice_id' => $invoice1->id, 'item_id' => $item1->id, 'quantity' => 2, 'price' => 1500]); // COGS: 2 * 1000 = 2000
        InvoiceItem::factory()->create(['invoice_id' => $invoice1->id, 'item_id' => $item2->id, 'quantity' => 1, 'price' => 3000]); // COGS: 1 * 2000 = 2000
        // Total COGS for Jan: 4000

        // Invoice 2 Items
        InvoiceItem::factory()->create(['invoice_id' => $invoice2->id, 'item_id' => $item1->id, 'quantity' => 3, 'price' => 1500]); // COGS: 3 * 1000 = 3000
        // Total COGS for Feb: 3000

        Livewire::test(LaporanLabaRugi::class)
            ->set('data.startDate', '2023-01-01')
            ->set('data.endDate', '2023-01-31')
            ->assertSee('Rp 6.000') // Revenue (from invoice total)
            ->assertSee('Rp 4.000') // COGS (2000 + 2000)
            ->assertSee('Rp 2.000'); // Profit (6000 - 4000)
    }
}
