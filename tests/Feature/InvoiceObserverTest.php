<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Item;
use App\Models\Product;
use App\Models\TypeItem;
use App\Models\Customer;
use App\Models\Vehicle;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceObserverTest extends TestCase
{
    use RefreshDatabase;

    protected TypeItem $typeItem;
    protected Product $product;
    protected Item $item;
    protected Customer $customer;
    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary test data manually
        $this->typeItem = TypeItem::create([
            'name' => 'Spare Parts'
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'brand' => 'Test Brand',
            'type_item_id' => $this->typeItem->id
        ]);

        $this->item = Item::create([
            'product_id' => $this->product->id,
            'name' => 'Test Item',
            'sku' => 'TEST-001',
            'stock' => 100,
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'selling_price' => 15000
        ]);

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'phone_number' => '081234567890', // Changed 'phone' to 'phone_number'
            // 'email' => 'test@example.com', // Removed email as it's not in Customer $fillable
            'address' => 'Test Address'
        ]);

        $this->vehicle = Vehicle::create([
            'customer_id' => $this->customer->id,
            'license_plate' => 'B 1234 CD',
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'year' => 2020
        ]);
    }

    /** @test */
    public function it_restores_item_stock_when_invoice_is_deleted()
    {
        // Arrange: Create invoice with items
        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 30000,
            'status' => 'pending'
        ]);

        // Attach items to invoice
        $invoice->items()->attach($this->item->id, [
            'quantity' => 5,
            'price' => 15000, // Changed from unit_price, total_price removed
        ]);

        // Simulate stock reduction (as it would happen when invoice is created)
        $this->item->stock -= 5;
        $this->item->save();

        // Verify stock is reduced
        $this->assertEquals(95, $this->item->fresh()->stock);

        // Act: Delete the invoice (soft delete)
        $invoice->delete();

        // Assert: Stock should be restored
        $this->assertEquals(100, $this->item->fresh()->stock);
    }

    /** @test */
    public function it_decrements_item_stock_when_invoice_is_restored()
    {
        // Arrange: Create and delete invoice
        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'invoice_number' => 'INV-TEST-002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 45000,
            'status' => 'pending'
        ]);

        // Attach items to invoice
        $invoice->items()->attach($this->item->id, [
            'quantity' => 3,
            'price' => 15000, // Changed from unit_price, total_price removed
        ]);

        // Simulate stock reduction and then deletion
        $this->item->stock -= 3;
        $this->item->save();
        $invoice->delete();

        // Verify stock is restored after deletion
        $this->assertEquals(100, $this->item->fresh()->stock);

        // Act: Restore the invoice
        $invoice->restore();

        // Assert: Stock should be decremented again
        $this->assertEquals(97, $this->item->fresh()->stock);
    }

    /** @test */
    public function it_handles_multiple_items_in_invoice_deletion()
    {
        // Arrange: Create additional items
        $item2 = Item::create([
            'product_id' => $this->product->id,
            'name' => 'Test Item 2',
            'sku' => 'TEST-002',
            'stock' => 50,
            'unit' => 'pcs',
            'purchase_price' => 20000,
            'selling_price' => 25000
        ]);

        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'invoice_number' => 'INV-TEST-003',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 85000,
            'status' => 'pending'
        ]);

        // Attach multiple items
        $invoice->items()->attach([
            $this->item->id => [
                'quantity' => 2,
                'price' => 15000, // Changed from unit_price, total_price removed
            ],
            $item2->id => [
                'quantity' => 3,
                'price' => 25000, // Changed from unit_price, total_price removed
            ]
        ]);

        // Simulate stock reduction
        $this->item->stock -= 2;
        $this->item->save();
        $item2->stock -= 3;
        $item2->save();

        // Verify initial stock reduction
        $this->assertEquals(98, $this->item->fresh()->stock);
        $this->assertEquals(47, $item2->fresh()->stock);

        // Act: Delete invoice
        $invoice->delete();

        // Assert: Both items' stock should be restored
        $this->assertEquals(100, $this->item->fresh()->stock);
        $this->assertEquals(50, $item2->fresh()->stock);
    }

    /** @test */
    public function it_handles_zero_stock_items_on_restoration()
    {
        // Arrange: Create item with low stock
        $lowStockItem = Item::create([
            'product_id' => $this->product->id,
            'name' => 'Low Stock Item',
            'sku' => 'LOW-001',
            'stock' => 2,
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'selling_price' => 15000
        ]);

        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'invoice_number' => 'INV-TEST-004',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 75000,
            'status' => 'pending'
        ]);

        // Attach item with quantity that will make stock go negative
        $invoice->items()->attach($lowStockItem->id, [
            'quantity' => 5,
            'price' => 15000, // Changed from unit_price, total_price removed
        ]);

        // Simulate stock reduction (this would make it negative)
        $lowStockItem->stock -= 5;
        $lowStockItem->save();

        // Delete invoice to restore stock
        $invoice->delete();

        // Verify stock is restored
        $this->assertEquals(2, $lowStockItem->fresh()->stock);

        // Act: Restore invoice (this should handle negative stock scenario)
        $invoice->restore();

        // Assert: Stock should be decremented (even if it goes negative)
        // Note: Current observer allows negative stock
        $this->assertEquals(-3, $lowStockItem->fresh()->stock);
    }

    /** @test */
    public function it_handles_non_existent_items_gracefully()
    {
        // Arrange: Create invoice
        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'invoice_number' => 'INV-TEST-005',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 30000,
            'status' => 'pending'
        ]);

        // Attach item normally
        $invoice->items()->attach($this->item->id, [
            'quantity' => 2,
            'price' => 15000, // Changed from unit_price, total_price removed
        ]);

        // Simulate stock reduction
        $this->item->stock -= 2;
        $this->item->save();

        // Delete the item (simulate item being deleted externally)
        $originalItemId = $this->item->id;
        $this->item->forceDelete();

        // Act: Delete invoice (should handle missing item gracefully)
        $invoice->delete();

        // Assert: No exceptions should be thrown
        $this->assertTrue($invoice->trashed());

        // Verify the item doesn't exist anymore
        $this->assertDatabaseMissing('items', ['id' => $originalItemId]);
    }

    /** @test */
    public function it_preserves_pivot_data_correctly()
    {
        // Arrange: Create invoice with specific quantities
        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'invoice_number' => 'INV-TEST-006',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 60000,
            'status' => 'pending'
        ]);

        // Attach items with different quantities
        $invoice->items()->attach($this->item->id, [
            'quantity' => 4,
            'price' => 15000, // Changed from unit_price, total_price removed
        ]);

        // Simulate stock reduction
        $originalStock = $this->item->stock;
        $this->item->stock -= 4;
        $this->item->save();

        // Act: Delete and restore
        $invoice->delete();
        $this->assertEquals($originalStock, $this->item->fresh()->stock);

        $invoice->restore();

        // Assert: Stock should be decremented by the exact pivot quantity
        $this->assertEquals($originalStock - 4, $this->item->fresh()->stock);
    }
}
