<?php

namespace Database\Seeders;

use App\Models\Invoice;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = [
            [
                'customer_id' => 1,
                'vehicle_id' => 1,
                'invoice_number' => 'INV-2024-001',
                'invoice_date' => '2024-01-15',
                'due_date' => '2024-01-30',
                'status' => 'paid',
                'subtotal' => 450000,
                'discount_type' => 'percentage',
                'discount_value' => 5,
                'total_amount' => 427500,
                'terms' => 'Pembayaran maksimal 15 hari setelah invoice',
            ],
            [
                'customer_id' => 2,
                'vehicle_id' => 3,
                'invoice_number' => 'INV-2024-002',
                'invoice_date' => '2024-01-20',
                'due_date' => '2024-02-04',
                'status' => 'unpaid',
                'subtotal' => 750000,
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'total_amount' => 700000,
                'terms' => 'Pembayaran maksimal 15 hari setelah invoice',
            ],
            [
                'customer_id' => 3,
                'vehicle_id' => 4,
                'invoice_number' => 'INV-2024-003',
                'invoice_date' => '2024-01-25',
                'due_date' => '2024-02-09',
                'status' => 'partially_paid',
                'subtotal' => 300000,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'total_amount' => 270000,
                'terms' => 'Pembayaran maksimal 15 hari setelah invoice',
            ],
            [
                'customer_id' => 4,
                'vehicle_id' => 5,
                'invoice_number' => 'INV-2024-004',
                'invoice_date' => '2024-02-01',
                'due_date' => '2024-02-16',
                'status' => 'paid',
                'subtotal' => 1200000,
                'discount_type' => 'fixed',
                'discount_value' => 100000,
                'total_amount' => 1100000,
                'terms' => 'Pembayaran maksimal 15 hari setelah invoice',
            ],
            [
                'customer_id' => 5,
                'vehicle_id' => 6,
                'invoice_number' => 'INV-2024-005',
                'invoice_date' => '2024-02-05',
                'due_date' => '2024-02-20',
                'status' => 'unpaid',
                'subtotal' => 550000,
                'discount_type' => 'percentage',
                'discount_value' => 0,
                'total_amount' => 550000,
                'terms' => 'Pembayaran maksimal 15 hari setelah invoice',
            ],
            [
                'customer_id' => 6,
                'vehicle_id' => 7,
                'invoice_number' => 'INV-2024-006',
                'invoice_date' => '2024-02-10',
                'due_date' => '2024-02-25',
                'status' => 'overdue',
                'subtotal' => 850000,
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'total_amount' => 722500,
                'terms' => 'Pembayaran maksimal 15 hari setelah invoice',
            ],
        ];

        foreach ($invoices as $invoice) {
            Invoice::create($invoice);
        }
    }
}
