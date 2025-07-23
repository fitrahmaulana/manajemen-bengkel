<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function purchase_order_payment_status_is_unpaid_when_no_payment_is_made()
    {
        $purchaseOrder = PurchaseOrder::factory()->create(['total_amount' => 1000]);

        $purchaseOrder->updatePaymentStatus();

        $this->assertEquals(PaymentStatus::UNPAID, $purchaseOrder->payment_status);
    }

    /** @test */
    public function purchase_order_payment_status_is_partial_when_partial_payment_is_made()
    {
        $purchaseOrder = PurchaseOrder::factory()->create(['total_amount' => 1000]);
        Payment::factory()->create([
            'payable_id' => $purchaseOrder->id,
            'payable_type' => PurchaseOrder::class,
            'amount_paid' => 500,
        ]);

        $purchaseOrder->updatePaymentStatus();

        $this->assertEquals(PaymentStatus::PARTIAL, $purchaseOrder->payment_status);
    }

    /** @test */
    public function purchase_order_payment_status_is_paid_when_full_payment_is_made()
    {
        $purchaseOrder = PurchaseOrder::factory()->create(['total_amount' => 1000]);
        Payment::factory()->create([
            'payable_id' => $purchaseOrder->id,
            'payable_type' => PurchaseOrder::class,
            'amount_paid' => 1000,
        ]);

        $purchaseOrder->updatePaymentStatus();

        $this->assertEquals(PaymentStatus::PAID, $purchaseOrder->payment_status);
    }
}
