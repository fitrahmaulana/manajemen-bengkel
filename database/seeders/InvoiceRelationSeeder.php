<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Item;
use App\Models\Service;
use Illuminate\Database\Seeder;

class InvoiceRelationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada invoice, item, dan service yang bisa digunakan
        $invoices = Invoice::take(6)->get();
        $items = Item::take(10)->get();
        $services = Service::take(12)->get();

        if ($invoices->count() > 0 && $items->count() > 0 && $services->count() > 0) {
            // Attach services to invoices
            foreach ($invoices as $index => $invoice) {
                // Attach 1-2 services per invoice
                $serviceIds = $services->random(rand(1, 2))->pluck('id');
                foreach ($serviceIds as $serviceId) {
                    $service = $services->find($serviceId);
                    $invoice->services()->attach($serviceId, [
                        'price' => $service->price,
                        'description' => $service->description,
                    ]);
                }

                // Attach 1-2 items per invoice
                $itemIds = $items->random(rand(1, 2))->pluck('id');
                foreach ($itemIds as $itemId) {
                    $item = $items->find($itemId);
                    $invoice->items()->attach($itemId, [
                        'quantity' => rand(1, 3),
                        'price' => $item->selling_price,
                        'description' => $item->name,
                    ]);
                }
            }
        }
    }
}
