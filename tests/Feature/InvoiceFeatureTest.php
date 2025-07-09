<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\InvoiceStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceStockService $stockService;
    protected User $user;
    protected Product $product;
    protected Customer $customer;
    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(InvoiceStockService::class);
        $this->user = User::factory()->create(); // For potential ownership context if service needs it

        $this->product = Product::factory()->create(['name' => 'Test Product']);
        $this->customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);
    }

    private function createItem(string $name, string $sku, float $initialStock, float $sellingPrice = 100.00): Item
    {
        return Item::factory()->create([
            'product_id' => $this->product->id,
            'name' => $name,
            'sku' => $sku,
            'selling_price' => $sellingPrice,
            'stock' => $initialStock,
            'unit' => 'Pcs',
        ]);
    }

    private function createInvoiceForTest(): Invoice
    {
        return Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'invoice_number' => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 0, // Will be set by actual invoice creation logic
            'total_amount' => 0, // Will be set by actual invoice creation logic
        ]);
    }

    /** @test */
    public function stock_service_can_deduct_decimal_stock_for_invoice_items(): void
    {
        $item1 = $this->createItem('Item A', 'SKU-A', 10.75);
        $invoice = $this->createInvoiceForTest();
        $rawItemsData = [
            ['item_id' => $item1->id, 'quantity' => 1.55, 'price' => 100.00, 'description' => 'Desc A'],
        ];

        // Act
        $this->stockService->deductStockForInvoiceItems($invoice, $rawItemsData);

        // Assert
        $item1->refresh();
        $this->assertEquals(sprintf('%.2f', 10.75 - 1.55), $item1->stock); // 9.20
    }

    /** @test */
    public function stock_service_can_restore_decimal_stock_for_invoice_items(): void
    {
        $item1 = $this->createItem('Item B', 'SKU-B', 5.80);
        $invoice = $this->createInvoiceForTest();

        // Simulate items being attached to the invoice with pivot data
        $invoice->items()->attach($item1->id, ['quantity' => 2.35, 'price' => 100.00, 'description' => 'Desc B']);

        // Manually set initial stock *before* restoration for test clarity
        $item1->stock = 3.45; // 5.80 - 2.35 = 3.45
        $item1->save();
        $this->assertEquals('3.45', $item1->fresh()->stock);

        // Act
        $this->stockService->restoreStockForInvoiceItems($invoice);

        // Assert
        $item1->refresh();
        // Stock should be original 3.45 + 2.35 (from pivot) = 5.80
        $this->assertEquals(sprintf('%.2f', 3.45 + 2.35), $item1->stock);
    }

    /** @test */
    public function stock_service_adjust_stock_for_item_deducts_correctly(): void
    {
        $item1 = $this->createItem('Item C', 'SKU-C', 12.00);

        // Act: Deduct 2.75
        $this->stockService->adjustStockForItem($item1->id, 2.75);

        // Assert
        $item1->refresh();
        $this->assertEquals(sprintf('%.2f', 12.00 - 2.75), $item1->stock); // 9.25
    }

    /** @test */
    public function stock_service_adjust_stock_for_item_restores_correctly(): void
    {
        $item1 = $this->createItem('Item D', 'SKU-D', 8.50);

        // Act: Restore 3.20 (by passing negative value for deduction)
        $this->stockService->adjustStockForItem($item1->id, -3.20);

        // Assert
        $item1->refresh();
        $this->assertEquals(sprintf('%.2f', 8.50 + 3.20), $item1->stock); // 11.70
    }

    /** @test */
    public function stock_service_deduct_handles_multiple_items(): void
    {
        $item1 = $this->createItem('Item E', 'SKU-E', 20.00);
        $item2 = $this->createItem('Item F', 'SKU-F', 15.00);
        $invoice = $this->createInvoiceForTest();
        $rawItemsData = [
            ['item_id' => $item1->id, 'quantity' => 2.5, 'price' => 10.00],
            ['item_id' => $item2->id, 'quantity' => 1.25, 'price' => 20.00],
        ];

        $this->stockService->deductStockForInvoiceItems($invoice, $rawItemsData);

        $this->assertEquals(sprintf('%.2f', 20.00 - 2.5), $item1->fresh()->stock); // 17.50
        $this->assertEquals(sprintf('%.2f', 15.00 - 1.25), $item2->fresh()->stock); // 13.75
    }

    /** @test */
    public function stock_service_restore_handles_multiple_items(): void
    {
        $item1 = $this->createItem('Item G', 'SKU-G', 10.00); // Initial stock before any transaction
        $item2 = $this->createItem('Item H', 'SKU-H', 5.00);  // Initial stock before any transaction
        $invoice = $this->createInvoiceForTest();

        // Items attached to invoice
        $invoice->items()->attach([
            $item1->id => ['quantity' => 1.5, 'price' => 10.00],
            $item2->id => ['quantity' => 0.5, 'price' => 20.00],
        ]);

        // Simulate stock after deduction
        $item1->stock = 10.00 - 1.5; // 8.50
        $item1->save();
        $item2->stock = 5.00 - 0.5;  // 4.50
        $item2->save();

        $this->stockService->restoreStockForInvoiceItems($invoice);

        $this->assertEquals(sprintf('%.2f', 8.50 + 1.5), $item1->fresh()->stock); // Should be 10.00
        $this->assertEquals(sprintf('%.2f', 4.50 + 0.5), $item2->fresh()->stock); // Should be 5.00
    }

    /** @test */
    public function quantity_in_invoice_item_pivot_is_decimal(): void
    {
        $item = $this->createItem('Item Pivot Test', 'SKU-PIVOT', 10.00);
        $invoice = $this->createInvoiceForTest();
        $decimalQuantity = 3.75;

        $invoice->items()->attach($item->id, [
            'quantity' => $decimalQuantity,
            'price' => 100.00,
            'description' => 'Test decimal pivot'
        ]);

        $invoice->refresh(); // Refresh to get relations and pivot data
        $attachedItem = $invoice->items()->first();

        $this->assertNotNull($attachedItem);
        $this->assertEquals($decimalQuantity, $attachedItem->pivot->quantity);
        // Check if it's retrieved as a float/decimal due to model casting on Invoice items()
        $this->assertIsFloat($attachedItem->pivot->quantity);
    }
}
