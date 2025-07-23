<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payments = [
            // Pembayaran untuk Invoice 1 (Lunas)
            [
                'payable_id' => 1,
                'payable_type' => Invoice::class,
                'payment_date' => '2024-01-16',
                'amount_paid' => 427500,
                'payment_method' => 'Transfer Bank',
                'notes' => 'Pembayaran lunas via transfer BCA',
            ],

            // Pembayaran untuk Invoice 3 (Partial)
            [
                'payable_id' => 3,
                'payable_type' => Invoice::class,
                'payment_date' => '2024-01-26',
                'amount_paid' => 150000,
                'payment_method' => 'Cash',
                'notes' => 'Pembayaran sebagian - DP',
            ],

            // Pembayaran untuk Invoice 4 (Lunas)
            [
                'payable_id' => 4,
                'payable_type' => Invoice::class,
                'payment_date' => '2024-02-02',
                'amount_paid' => 500000,
                'payment_method' => 'Transfer Bank',
                'notes' => 'Pembayaran pertama via transfer Mandiri',
            ],
            [
                'payable_id' => 4,
                'payable_type' => Invoice::class,
                'payment_date' => '2024-02-10',
                'amount_paid' => 600000,
                'payment_method' => 'Cash',
                'notes' => 'Pelunasan pembayaran',
            ],

            // Pembayaran untuk Invoice 6 (Overdue - ada pembayaran sebagian)
            [
                'payable_id' => 6,
                'payable_type' => Invoice::class,
                'payment_date' => '2024-02-28',
                'amount_paid' => 300000,
                'payment_method' => 'Transfer Bank',
                'notes' => 'Pembayaran sebagian - terlambat',
            ],
        ];

        foreach ($payments as $payment) {
            Payment::create($payment);
        }
    }
}
