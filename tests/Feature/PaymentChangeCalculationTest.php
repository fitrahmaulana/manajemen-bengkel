<?php

namespace Tests\Feature;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentChangeCalculationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_the_change_correctly_when_payment_is_edited()
    {
        $purchaseOrder = PurchaseOrder::factory()->create(['total_amount' => 700000]);
        $payment = Payment::factory()->create([
            'payable_id' => $purchaseOrder->id,
            'payable_type' => PurchaseOrder::class,
            'amount_paid' => 710000,
        ]);

        $change = PaymentResource::calculateChange($payment->amount_paid, $purchaseOrder, $payment);

        $this->assertEquals(10000, $change);

        $payment->amount_paid = 690000;
        $payment->save();

        $change = PaymentResource::calculateChange($payment->amount_paid, $purchaseOrder, $payment);

        $this->assertEquals(-10000, $change);
    }
}
