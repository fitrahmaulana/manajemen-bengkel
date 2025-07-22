<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;

    protected User $user;

    protected Product $product;

    protected Customer $customer;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
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
            'invoice_number' => 'INV-'.uniqid(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 0, // Will be set by actual invoice creation logic
            'total_amount' => 0, // Will be set by actual invoice creation logic
        ]);
    }

    /** @test */
    public function stock_service_adjust_stock_for_item_deducts_correctly(): void
    {
        $item1 = $this->createItem('Item C', 'SKU-C', 12.00);

        // Act: Deduct 2.75
        $this->inventoryService->adjustStockForItem($item1->id, 2.75);

        // Assert
        $item1->refresh();
        $this->assertEquals(sprintf('%.2f', 12.00 - 2.75), $item1->stock); // 9.25
    }

    /** @test */
    public function stock_service_adjust_stock_for_item_restores_correctly(): void
    {
        $item1 = $this->createItem('Item D', 'SKU-D', 8.50);

        // Act: Restore 3.20 (by passing negative value for deduction)
        $this->inventoryService->adjustStockForItem($item1->id, -3.20);

        // Assert
        $item1->refresh();
        $this->assertEquals(sprintf('%.2f', 8.50 + 3.20), $item1->stock); // 11.70
    }

    /** @test */
    public function quantity_in_invoice_item_pivot_is_decimal(): void
    {
        $item = $this->createItem('Item Pivot Test', 'SKU-PIVOT', 10.00);
        $invoice = $this->createInvoiceForTest();
        $decimalQuantity = 3.75;

        $invoice->invoiceItems()->create([
            'item_id' => $item->id,
            'quantity' => $decimalQuantity,
            'price' => 100.00,
            'description' => 'Test decimal pivot',
        ]);

        $invoice->refresh(); // Refresh to get relations and pivot data
        $attachedItem = $invoice->invoiceItems()->first();

        $this->assertNotNull($attachedItem);
        $this->assertEquals($decimalQuantity, $attachedItem->quantity);
        // Check if it's retrieved as a float/decimal due to model casting on Invoice items()
        $this->assertIsFloat($attachedItem->quantity);
    }

    /** @test */
    public function stock_is_adjusted_correctly_when_invoice_item_quantity_is_edited_via_observer(): void
    {
        $item = $this->createItem('Item Edit Test', 'SKU-EDIT', 20.00);
        $invoice = $this->createInvoiceForTest();
        $initialQuantity = 5.0;
        $newQuantity = 8.0;

        // 1. Create the initial invoice item. The 'created' observer will fire.
        $invoiceItem = $invoice->invoiceItems()->create([
            'item_id' => $item->id,
            'quantity' => $initialQuantity,
            'price' => 100.00,
        ]);

        // After creation, stock should be 20 - 5 = 15
        $this->assertEquals(15.00, $item->fresh()->stock);

        // 2. Update the quantity. The 'updated' observer should fire.
        $invoiceItem->update(['quantity' => $newQuantity]);

        // The difference is 8 - 5 = 3. Stock should be further deducted by 3.
        // Final stock should be 15 - 3 = 12.
        $this->assertEquals(12.00, $item->fresh()->stock);

        // 3. Test reducing the quantity
        $finalQuantity = 7.0;
        $invoiceItem->update(['quantity' => $finalQuantity]);

        // The difference is 7 - 8 = -1. Stock should be restored by 1.
        // Final stock should be 12 + 1 = 13.
        $this->assertEquals(13.00, $item->fresh()->stock);
    }

    /** @test */
    public function deleting_invoice_restores_stock_for_all_its_items_via_observer(): void
    {
        $item1 = $this->createItem('Item To Restore 1', 'SKU-RESTORE-1', 10.00);
        $item2 = $this->createItem('Item To Restore 2', 'SKU-RESTORE-2', 15.00);
        $invoice = $this->createInvoiceForTest();

        // Attach items and simulate initial stock deduction via 'created' observer
        $invoice->invoiceItems()->create(['item_id' => $item1->id, 'quantity' => 2.00, 'price' => 100]);
        $invoice->invoiceItems()->create(['item_id' => $item2->id, 'quantity' => 5.00, 'price' => 100]);

        // Check stock after deduction
        $this->assertEquals(8.00, $item1->fresh()->stock); // 10 - 2
        $this->assertEquals(10.00, $item2->fresh()->stock); // 15 - 5

        // Act: Delete the entire invoice
        $invoice->delete();

        // Assert: Stock should be restored to original values
        $this->assertEquals(10.00, $item1->fresh()->stock);
        $this->assertEquals(15.00, $item2->fresh()->stock);
    }

    /** @test */
    public function restoring_soft_deleted_invoice_deducts_stock_again_via_observer(): void
    {
        $item = $this->createItem('Item To Restore', 'SKU-RESTORE', 20.00);
        $invoice = $this->createInvoiceForTest();

        // 1. Create item, stock is deducted: 20 - 5 = 15
        $invoice->invoiceItems()->create(['item_id' => $item->id, 'quantity' => 5.00, 'price' => 100]);
        $this->assertEquals(15.00, $item->fresh()->stock);

        // 2. Soft delete the invoice, stock is restored: 15 + 5 = 20
        $invoice->delete();
        $this->assertEquals(20.00, $item->fresh()->stock);

        // 3. Restore the invoice, stock should be deducted again: 20 - 5 = 15
        $invoice->restore();
        $this->assertEquals(15.00, $item->fresh()->stock);
    }
}
