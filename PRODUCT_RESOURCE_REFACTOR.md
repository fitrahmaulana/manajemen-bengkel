# PRODUCT RESOURCE REFACTOR - MENAMPILKAN VARIAN PRODUK

## Tujuan
Mengubah tampilan tabel ProductResource agar menampilkan semua varian produk (Item) sebagai baris terpisah, sehingga kasir dapat dengan mudah melihat semua produk dan variannya dalam satu tampilan.

## Perubahan yang Dilakukan

### 1. Query Tabel
- **Sebelum**: Menggunakan `Product::query()` yang menampilkan hanya produk induk
- **Sesudah**: Menggunakan `Item::query()` yang menampilkan semua varian sebagai baris terpisah
- **Dengan**: Eager loading untuk relasi `product` dan `product.typeItem`

### 2. Kolom Tabel
Menampilkan kolom-kolom berikut:

#### Kolom Utama (Selalu Terlihat)
- **Nama Produk**: Dari `product.name` dengan deskripsi merek
- **Varian**: Dari `name` Item (menampilkan "Standard" jika kosong)
- **SKU**: Kode unik varian yang bisa disalin
- **Kategori**: Dari `product.typeItem.name` dengan badge hijau
- **Harga Jual**: Formatted dalam rupiah dengan warna hijau
- **Stok**: Dengan badge warna (hijau >20, kuning 1-20, merah 0)
- **Bisa Dipecah**: Icon check/x untuk konversi item

#### Kolom Tambahan (Dapat Disembunyikan)
- **Harga Beli**: Untuk analisis margin
- **Margin Profit**: Persentase keuntungan dengan badge berwarna
- **Satuan**: Unit barang
- **Tanggal Dibuat**: Timestamp pembuatan

### 3. Fitur Tabel

#### Grouping
- **Default**: Dikelompokkan berdasarkan kategori (`product.typeItem.name`)
- **Alternatif**: Bisa dikelompokkan berdasarkan produk (`product.name`)

#### Filter
- **Kategori**: Filter berdasarkan jenis produk
- **Produk**: Filter berdasarkan produk induk tertentu
- **Jenis Produk**: Filter produk dengan/tanpa varian
- **Status Stok**: Filter berdasarkan jumlah stok (tersedia/menipis/habis)
- **Bisa Dipecah**: Filter item yang bisa dikonversi

#### Search
- **Nama Produk**: Search di nama dan merek produk
- **Varian**: Search di nama varian
- **SKU**: Search di kode barang
- **Kategori**: Search di nama kategori

#### Sorting
Semua kolom utama dapat di-sort (nama produk, varian, kategori, harga, stok)

### 4. Actions

#### Row Actions
- **Detail Varian**: Modal dengan informasi lengkap varian
- **Detail Produk**: Link ke halaman detail produk induk
- **Edit Produk**: Link ke halaman edit produk induk

#### Bulk Actions
- **Update Stok**: Mengubah stok multiple item sekaligus
  - Set stok menjadi nilai tertentu
  - Tambah stok
  - Kurangi stok
- **Delete**: Hapus multiple item terpilih

### 5. User Experience

#### Untuk Kasir
- Melihat semua produk dan varian dalam satu tampilan
- Harga jual langsung terlihat dengan format rupiah
- Status stok dengan kode warna yang jelas
- SKU yang mudah disalin untuk input transaksi
- Filter cepat berdasarkan kategori dan stok

#### Untuk Admin
- Grouping berdasarkan kategori untuk organisasi yang lebih baik
- Bulk actions untuk update stok massal
- Filter advanced untuk analisis produk
- Link cepat ke detail/edit produk

## Keunggulan Pendekatan Ini

### 1. Filament Native
- Menggunakan 100% komponen bawaan Filament
- Tidak ada custom blade atau JavaScript
- Kompatibel dengan semua fitur Filament (search, sort, filter, etc.)

### 2. Performance
- Eager loading untuk menghindari N+1 queries
- Query optimized untuk tabel besar
- Efficient filtering dan sorting

### 3. User-Friendly
- Tampilan yang familiar untuk kasir
- Informasi penting langsung terlihat
- Tidak perlu navigasi ke halaman lain untuk melihat varian

### 4. Maintenance
- Kode yang bersih dan mudah dipelihara
- Mengikuti best practices Filament
- Mudah untuk ditambahkan fitur baru

## Contoh Tampilan

```
Nama Produk      | Varian    | SKU      | Kategori | Harga Jual | Stok
Oli Castrol GTX  | Standard  | OIL-001  | Oli      | Rp 75.000  | 25 Liter
Oli Castrol GTX  | 1 Liter   | OIL-002  | Oli      | Rp 25.000  | 50 Botol
Oli Castrol GTX  | 5 Liter   | OIL-003  | Oli      | Rp 120.000 | 10 Galon
Filter Oli       | Standard  | FLT-001  | Filter   | Rp 35.000  | 0 Pcs
```

## Kesimpulan

Dengan perubahan ini:
- Kasir dapat dengan mudah melihat semua produk dan varian dalam satu tampilan
- Tidak perlu membuka halaman detail atau ekspansi untuk melihat varian
- Semua fitur menggunakan komponen native Filament
- Performa tetap optimal dengan query yang efisien
- User experience yang lebih baik untuk operasional bengkel
