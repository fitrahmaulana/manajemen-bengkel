# Perbaikan ProductSeeder dan InvoiceItemServiceSeeder

## Masalah yang Ditemukan:
1. **Ketidaksesuaian data**: Di InvoiceItemServiceSeeder, Invoice #4 menunjukkan service "Ganti Aki" tetapi item yang dijual adalah "oli"
2. **Data tidak lengkap**: Beberapa variant produk hanya memiliki field `stock` tanpa data lainnya
3. **Kurangnya variasi produk**: Perlu ada produk dengan varian dan tanpa varian

## Perbaikan yang Dilakukan:

### 1. ProductSeeder.php
Menambahkan produk-produk baru:

#### Produk TANPA Varian:
- **Aki GS Astra NS40ZL** (Item ID: 13)
  - Harga beli: Rp 650.000
  - Harga jual: Rp 750.000
  - Stock: 15 pcs

- **Ban Bridgestone Turanza T005** (Item ID: 14)
  - Harga beli: Rp 850.000
  - Harga jual: Rp 950.000
  - Stock: 20 pcs

- **Coolant Prestone Universal** (Item ID: 19)
  - Harga beli: Rp 75.000
  - Harga jual: Rp 90.000
  - Stock: 25 botol

#### Produk DENGAN Varian:
- **Timing Belt Gates** (Item ID: 15-17)
  - Varian: Toyota Avanza, Honda Civic, Suzuki Ertiga
  - Harga beli: Rp 385.000 - Rp 425.000
  - Harga jual: Rp 450.000 - Rp 500.000

- **Minyak Rem Bosch** (Item ID: 20-21)
  - Varian: DOT 3, DOT 4
  - Harga beli: Rp 45.000 - Rp 55.000
  - Harga jual: Rp 55.000 - Rp 65.000

### 2. InvoiceItemServiceSeederUpdated.php
Membuat seeder baru yang konsisten:

#### Perbaikan Invoice #4:
- **Service**: Ganti Aki (bukan servis injeksi)
- **Item**: Aki GS Astra NS40ZL (bukan oli)
- **Harga**: Rp 750.000

#### Perbaikan Invoice #2:
- **Service**: Ganti Ban
- **Item**: Ban Bridgestone Turanza T005 (2 pcs)
- **Harga**: Rp 950.000 per pcs

#### Perbaikan Invoice #6:
- **Service**: Ganti Timing Belt
- **Item**: Timing Belt Gates - Toyota Avanza
- **Harga**: Rp 450.000

### 3. DatabaseSeeder.php
Menggunakan seeder yang sudah diperbaiki:
```php
$this->call([
    ItemTypeSeeder::class,
    ProductSeeder::class,
    ServiceSeeder::class,
    CustomerSeeder::class,
    VehicleSeeder::class,
    InvoiceSeeder::class,
    InvoiceItemServiceSeederUpdated::class, // Seeder yang sudah diperbaiki
    PaymentSeeder::class,
]);
```

## Cara Menjalankan:
```bash
php artisan migrate:fresh --seed
```

## Variasi Produk yang Tersedia:
1. **Dengan Varian**: Oli Shell HX7, Oli Castrol GTX, Busi NGK G-Power, Filter Udara Denso, Kampas Rem Bendix, Timing Belt Gates, Minyak Rem Bosch
2. **Tanpa Varian**: Aki GS Astra, Ban Bridgestone, Coolant Prestone

## Konsistensi Data:
- Semua invoice sekarang memiliki service dan item yang sesuai
- Harga sudah disesuaikan dengan harga jual produk
- Quantity sudah realistis sesuai dengan kebutuhan service
