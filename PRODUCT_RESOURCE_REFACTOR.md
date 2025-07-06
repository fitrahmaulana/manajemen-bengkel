# PRODUCT RESOURCE REFACTOR - Conventional Structure

## Tujuan Awal (Historis)
Dokumen ini awalnya mendeskripsikan refactor `ProductResource` untuk menampilkan semua varian produk (`Item`) sebagai baris terpisah dalam tabel utama `ProductResource`. Tujuannya adalah agar kasir dapat dengan mudah melihat semua produk dan variannya dalam satu tampilan.

## Perubahan Struktur (Saat Ini)
Berdasarkan feedback bahwa pendekatan awal (menampilkan `Item` di tabel `ProductResource`) tidak konvensional dan membingungkan, struktur telah diubah kembali ke pendekatan yang lebih standar di Filament:

1.  **`ProductResource` Utama:**
    *   Sekarang menampilkan daftar `Product` (produk induk) pada tabel utamanya.
    *   Kolom-kolom tabel fokus pada informasi produk induk seperti nama produk, merek, kategori, dan apakah produk tersebut memiliki varian.
    *   Formulir untuk membuat/mengedit `Product` kini hanya berisi field untuk informasi produk induk (`name`, `brand`, `description`, `type_item_id`, `has_variants`).

2.  **`ItemsRelationManager`:**
    *   Sebuah `RelationManager` baru bernama `ItemsRelationManager` telah ditambahkan (atau dikonfigurasi ulang jika sudah ada) di dalam `ProductResource`.
    *   Relation manager ini muncul di halaman lihat/edit `Product`.
    *   Bertanggung jawab untuk menampilkan, membuat, dan mengedit `Item` (varian) yang terkait dengan produk induk tersebut.
    *   Jika produk induk ditandai `has_variants = false` (item tunggal), relation manager ini akan mengelola satu `Item` saja. Jika `has_variants = true`, bisa ada banyak `Item`.
    *   Formulir dan tabel di dalam relation manager ini fokus pada detail `Item` seperti spesifikasi/ukuran varian, SKU, harga beli/jual, dan stok.

3.  **`ItemResource` Global:**
    *   `ItemResource` (`app/Filament/Resources/ItemResource.php`) tetap ada dan berfungsi sebagai resource global untuk melihat dan mengelola *semua* `Item` dalam sistem, terlepas dari produk induknya. Ini bisa digunakan untuk manajemen inventaris secara keseluruhan.

## Alasan Perubahan ke Struktur Saat Ini
*   **Konvensi:** Mengikuti praktik standar Filament, di mana sebuah resource (`ProductResource`) mengelola model utamanya (`Product`), dan relasi dikelola melalui `RelationManager`.
*   **Kejelasan:** Mengurangi kebingungan bagi developer dengan struktur yang lebih intuitif.
*   **Modularitas:** Pemisahan yang lebih jelas antara manajemen produk induk dan manajemen varian/item-nya.

## User Experience Kasir
*   Untuk melihat semua varian dari sebuah produk, kasir sekarang perlu:
    1.  Mencari produk induk di tabel `ProductResource`.
    2.  Masuk ke halaman lihat/edit produk tersebut.
    3.  Melihat dan berinteraksi dengan varian/item di tab "Varian / Item Produk" (`ItemsRelationManager`).
*   Jika kasir memerlukan tampilan flat semua item yang bisa dijual (seperti pada pendekatan lama), mereka bisa menggunakan `ItemResource` global, meskipun ini mungkin menampilkan lebih banyak detail inventaris daripada yang dibutuhkan kasir. Tampilan ini bisa disesuaikan lebih lanjut jika perlu.

## Kesimpulan
Perubahan ini mengembalikan `ProductResource` ke struktur yang lebih konvensional dan diharapkan lebih mudah dipahami dan dipelihara, sambil tetap menyediakan cara untuk mengelola produk dan varian/item-nya secara terstruktur.
