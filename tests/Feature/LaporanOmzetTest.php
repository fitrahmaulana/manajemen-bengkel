<?php

namespace Tests\Feature;

use App\Filament\Pages\LaporanOmzet;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanOmzetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_render_laporan_omzet_page()
    {
        Livewire::test(LaporanOmzet::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_filter_payments_by_date()
    {
        $this->seed();

        Payment::factory()->create(['payment_date' => '2023-01-15', 'amount_paid' => 1000]);
        Payment::factory()->create(['payment_date' => '2023-01-20', 'amount_paid' => 2000]);
        Payment::factory()->create(['payment_date' => '2023-02-10', 'amount_paid' => 5000]);

        Livewire::test(LaporanOmzet::class)
            ->set('data.startDate', '2023-01-01')
            ->set('data.endDate', '2023-01-31')
            ->assertSee('Rp 3.000')
            ->assertDontSee('Rp 5.000')
            ->assertCanSeeTableRecords(Payment::whereBetween('payment_date', ['2023-01-01', '2023-01-31'])->get());
    }
}
