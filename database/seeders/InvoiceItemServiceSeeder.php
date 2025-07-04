<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceItemServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data untuk invoice_service (pivot table)
        $invoiceServices = [
            // Invoice 1 - Ganti oli + tune up
            ['invoice_id' => 1, 'service_id' => 1, 'price' => 150000, 'description' => 'Ganti oli mesin Shell HX7'],
            ['invoice_id' => 1, 'service_id' => 2, 'price' => 300000, 'description' => 'Tune up lengkap + ganti busi'],

            // Invoice 2 - Ganti ban + servis AC
            ['invoice_id' => 2, 'service_id' => 3, 'price' => 500000, 'description' => 'Ganti ban depan 2 pcs'],
            ['invoice_id' => 2, 'service_id' => 4, 'price' => 250000, 'description' => 'Servis AC dan ganti filter'],

            // Invoice 3 - Ganti kampas rem + cuci mobil
            ['invoice_id' => 3, 'service_id' => 5, 'price' => 200000, 'description' => 'Ganti kampas rem depan'],
            ['invoice_id' => 3, 'service_id' => 9, 'price' => 50000, 'description' => 'Cuci mobil premium'],

            // Invoice 4 - Servis injeksi + tune up (karena aki tidak ada di stok, diganti dengan servis yang sesuai)
            ['invoice_id' => 4, 'service_id' => 11, 'price' => 400000, 'description' => 'Servis injeksi + ganti filter + busi'],
            ['invoice_id' => 4, 'service_id' => 2, 'price' => 300000, 'description' => 'Tune up lengkap + ganti busi'],

            // Invoice 5 - Balancing + spooring + oli
            ['invoice_id' => 5, 'service_id' => 6, 'price' => 100000, 'description' => 'Balancing 4 roda'],
            ['invoice_id' => 5, 'service_id' => 7, 'price' => 150000, 'description' => 'Spooring roda depan'],
            ['invoice_id' => 5, 'service_id' => 1, 'price' => 150000, 'description' => 'Ganti oli mesin'],

            // Invoice 6 - Ganti timing belt + filter + servis AC
            ['invoice_id' => 6, 'service_id' => 12, 'price' => 600000, 'description' => 'Ganti timing belt'],
            ['invoice_id' => 6, 'service_id' => 8, 'price' => 75000, 'description' => 'Ganti filter udara'],
            ['invoice_id' => 6, 'service_id' => 4, 'price' => 250000, 'description' => 'Servis AC'],
        ];

        foreach ($invoiceServices as $invoiceService) {
            DB::table('invoice_service')->insert($invoiceService);
        }

        // Data untuk invoice_item (pivot table)
        // Item yang sesuai dengan service yang dilakukan
        $invoiceItems = [
            // Invoice 1 - Ganti oli + tune up (butuh oli + busi)
            ['invoice_id' => 1, 'item_id' => 1, 'quantity' => 4, 'price' => 85000, 'description' => 'Oli Shell HX7 10W-40 - 1 Liter'],
            ['invoice_id' => 1, 'item_id' => 5, 'quantity' => 4, 'price' => 55000, 'description' => 'Busi NGK G-Power BPR6ES'],

            // Invoice 2 - Ganti ban + servis AC (butuh filter AC)
            ['invoice_id' => 2, 'item_id' => 8, 'quantity' => 1, 'price' => 100000, 'description' => 'Filter Udara Denso - Toyota Avanza'],
            ['invoice_id' => 2, 'item_id' => 10, 'quantity' => 1, 'price' => 105000, 'description' => 'Filter Udara Denso - Suzuki Ertiga'],

            // Invoice 3 - Ganti kampas rem + cuci mobil (butuh kampas rem)
            ['invoice_id' => 3, 'item_id' => 11, 'quantity' => 1, 'price' => 220000, 'description' => 'Kampas Rem Bendix Toyota Avanza - Depan'],

            // Invoice 4 - Servis injeksi + tune up (butuh filter + busi untuk servis)
            ['invoice_id' => 4, 'item_id' => 9, 'quantity' => 1, 'price' => 110000, 'description' => 'Filter Udara Denso - Honda Civic'],
            ['invoice_id' => 4, 'item_id' => 6, 'quantity' => 4, 'price' => 55000, 'description' => 'Busi NGK G-Power BPR7ES'],

            // Invoice 5 - Balancing + spooring + ganti oli (butuh oli)
            ['invoice_id' => 5, 'item_id' => 1, 'quantity' => 4, 'price' => 85000, 'description' => 'Oli Shell HX7 10W-40 - 1 Liter'],

            // Invoice 6 - Ganti timing belt + filter + servis AC (butuh filter)
            ['invoice_id' => 6, 'item_id' => 8, 'quantity' => 1, 'price' => 100000, 'description' => 'Filter Udara Denso - Toyota Avanza'],
            ['invoice_id' => 6, 'item_id' => 7, 'quantity' => 2, 'price' => 55000, 'description' => 'Busi NGK G-Power BPR8ES'],
        ];

        foreach ($invoiceItems as $invoiceItem) {
            DB::table('invoice_item')->insert($invoiceItem);
        }
    }
}
