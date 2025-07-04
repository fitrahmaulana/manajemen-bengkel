# Database Seeder Documentation

## Overview
Seeder ini akan mengisi database dengan data lengkap untuk sistem manajemen bengkel, termasuk:

## User Admin
- **Username**: admin
- **Email**: admin@gmail.com  
- **Password**: admin123

## Data yang Di-seed

### 1. ItemTypeSeeder
Membuat 4 kategori item:
- **Spare Parts**: Suku cadang kendaraan 
- **Oli & Cairan**: Oli mesin, oli transmisi, cairan rem, coolant
- **Ban & Velg**: Ban mobil, velg racing, ban dalam
- **Aksesoris**: Aksesoris kendaraan seperti karpet, cover, parfum

### 2. ProductSeeder (dengan Items)
Membuat 5 produk REAL dengan 11 varian total:

#### Oli & Cairan:
1. **Oli Shell HX7 10W-40** (Shell)
   - ID 1: 1 Liter (Rp 85.000) - 25 stok
   - ID 2: 4 Liter (Rp 320.000) - 12 stok

2. **Oli Castrol GTX 20W-50** (Castrol)
   - ID 3: 1 Liter (Rp 75.000) - 20 stok
   - ID 4: 4 Liter (Rp 280.000) - 8 stok

#### Spare Parts:
3. **Busi NGK G-Power** (NGK)
   - ID 5: BPR6ES (Rp 55.000) - 30 stok
   - ID 6: BPR7ES (Rp 55.000) - 25 stok
   - ID 7: BPR8ES (Rp 55.000) - 20 stok

4. **Filter Udara Denso** (Denso)
   - ID 8: Toyota Avanza (Rp 100.000) - 15 stok
   - ID 9: Honda Civic (Rp 110.000) - 12 stok
   - ID 10: Suzuki Ertiga (Rp 105.000) - 10 stok

5. **Kampas Rem Bendix Toyota Avanza** (Bendix)
   - ID 11: Depan (Rp 220.000) - 18 stok
   - ID 12: Belakang (Rp 180.000) - 15 stok

**Total Item yang dibuat: 11 item dengan ID 1-11**
   - 45AH (Rp 750.000) - 8 stok
   - 65AH (Rp 950.000) - 6 stok

8. **Timing Belt Gates** (Gates)
   - Toyota Avanza (Rp 220.000) - 10 stok
   - Honda Civic (Rp 240.000) - 8 stok

#### Ban & Velg:
9. **Ban Bridgestone Turanza** (Bridgestone)
   - 185/65R15 (Rp 750.000) - 12 stok
   - 195/65R15 (Rp 850.000) - 10 stok

#### Aksesoris:
10. **Parfum Mobil California Scents** (California Scents)
    - Vanilla (Rp 25.000) - 60 stok
    - Lavender (Rp 25.000) - 50 stok
    - Ice (Rp 25.000) - 45 stok

### 3. ServiceSeeder
Membuat 12 layanan bengkel:
- Ganti Oli Mesin (Rp 150.000)
- Tune Up (Rp 300.000)
- Ganti Ban (Rp 500.000)
- Servis AC (Rp 250.000)
- Ganti Kampas Rem (Rp 200.000)
- Balancing Roda (Rp 100.000)
- Spooring (Rp 150.000)
- Ganti Filter Udara (Rp 75.000)
- Cuci Mobil (Rp 50.000)
- Ganti Aki (Rp 800.000)
- Servis Injeksi (Rp 400.000)
- Ganti Timing Belt (Rp 600.000)

### 4. CustomerSeeder
Membuat 8 customer dengan data lengkap:
- Budi Santoso (08123456789)
- Siti Nurhaliza (08234567890)
- Ahmad Wijaya (08345678901)
- Ratna Dewi (08456789012)
- Dedi Kurniawan (08567890123)
- Maya Sari (08678901234)
- Roni Hermawan (08789012345)
- Lina Kartika (08890123456)

### 5. VehicleSeeder
Membuat 9 kendaraan dengan berbagai merk:
- Toyota Avanza (B 1234 ABC)
- Honda Civic (B 5678 DEF)
- Suzuki Ertiga (B 9012 GHI)
- Daihatsu Xenia (B 3456 JKL)
- Mitsubishi Pajero (B 7890 MNO)
- Nissan Serena (B 1357 PQR)
- Mazda CX-5 (B 2468 STU)
- Hyundai Creta (B 9753 VWX)
- Isuzu Panther (B 8642 YZA)

### 6. InvoiceSeeder
Membuat 6 invoice dengan berbagai status:
- **INV-2024-001**: Status paid (Rp 427.500)
- **INV-2024-002**: Status pending (Rp 700.000)
- **INV-2024-003**: Status partial (Rp 270.000)
- **INV-2024-004**: Status paid (Rp 1.100.000)
- **INV-2024-005**: Status pending (Rp 550.000)
- **INV-2024-006**: Status overdue (Rp 722.500)

### 7. InvoiceItemServiceSeeder
Mengisi relasi many-to-many antara invoice dengan service dan item REAL:

#### Invoice 1 - Ganti oli + tune up:
- **Service**: Ganti Oli Mesin (Rp 150.000) + Tune Up (Rp 300.000)
- **Items**: Oli Shell HX7 1L x4 + Busi NGK BPR6ES x4

#### Invoice 2 - Ganti ban + servis AC:
- **Service**: Ganti Ban (Rp 500.000) + Servis AC (Rp 250.000)
- **Items**: Ban Bridgestone 185/65R15 x2 + Cairan Rem Bosch x1

#### Invoice 3 - Kampas rem + cuci:
- **Service**: Ganti Kampas Rem (Rp 200.000) + Cuci Mobil (Rp 50.000)
- **Items**: Kampas Rem Bendix Depan x1 + Parfum California Vanilla x1

#### Invoice 4 - Aki + servis injeksi:
- **Service**: Ganti Aki (Rp 800.000) + Servis Injeksi (Rp 400.000)
- **Items**: Aki GS Astra 65AH x1 + Filter Udara Denso Civic x1

#### Invoice 5 - Balancing + spooring + oli:
- **Service**: Balancing (Rp 100.000) + Spooring (Rp 150.000) + Ganti Oli (Rp 150.000)
- **Items**: Oli Shell HX7 1L x4 + Busi NGK BPR7ES x2

#### Invoice 6 - Timing belt + filter + AC:
- **Service**: Timing Belt (Rp 600.000) + Filter (Rp 75.000) + Servis AC (Rp 250.000)
- **Items**: Timing Belt Gates Avanza x1 + Filter Denso Avanza x1

### 8. PaymentSeeder
Membuat 5 pembayaran:
- Pembayaran lunas untuk Invoice 1 dan 4
- Pembayaran sebagian untuk Invoice 3 dan 6
- Berbagai metode pembayaran (Transfer Bank, Cash)

## Cara Menjalankan Seeder

### Opsi 1: Menggunakan Command Line
```bash
php artisan migrate:fresh --seed
```

### Opsi 2: Menggunakan File Batch
Jalankan file `run_seeder.bat` di root project

## Keunggulan Seeder Baru

1. **Produk Real**: Menggunakan nama produk yang benar-benar ada di pasaran
2. **Varian Realistis**: Oli 1L vs 4L, Busi dengan kode part number asli
3. **Harga Market**: Harga sesuai dengan harga pasar Indonesia
4. **Brand Terkenal**: Shell, Castrol, NGK, Denso, Bendix, GS Astra, dll
5. **SKU Sistematis**: Format yang mudah dipahami dan konsisten
6. **Stok Bervariasi**: Beberapa item hampir habis, beberapa stok banyak
7. **Invoice Realistis**: Kombinasi service dan items yang masuk akal

## Catatan Penting
- Semua data menggunakan format Indonesia (nomor telepon, alamat, dll)
- Harga menggunakan mata uang Rupiah
- Data dibuat realistis untuk kebutuhan testing dan demo
- Seeder akan menghapus semua data yang ada (`migrate:fresh`)
- User admin akan dibuat otomatis dengan kredensial yang disebutkan di atas
