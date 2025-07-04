# Fix Error Search Query di ProductResource

## Problem
Error terjadi saat menggunakan fitur search di tabel ProductResource:
```
SQLSTATE[HY000]: General error: 1 no such column: products.name (Connection: sqlite, SQL: ...)
```

## Root Cause
- Query table menggunakan `Item::query()` dengan eager loading `with(['product', 'product.typeItem'])`
- Eager loading tidak membuat SQL JOIN, sehingga kolom dari tabel `products` dan `type_items` tidak bisa diakses langsung dalam query WHERE clause
- Kolom `searchable(['products.name', 'products.brand', 'items.name'])` mencoba mengakses kolom dari tabel yang tidak di-JOIN

## Solution

### 1. Menambahkan JOIN Eksplisit
```php
->query(
    \App\Models\Item::query()
        ->join('products', 'items.product_id', '=', 'products.id')
        ->join('type_items', 'products.type_item_id', '=', 'type_items.id')
        ->with(['product', 'product.typeItem'])
        ->select('items.*') // Pastikan kita hanya select kolom dari items untuk menghindari konflik
)
```

### 2. Memperbaiki Searchable Columns
```php
// Kolom nama produk + varian
Tables\Columns\TextColumn::make('product_name_with_variant')
    ->searchable(['products.name', 'products.brand', 'items.name'])

// Kolom kategori
Tables\Columns\TextColumn::make('product.typeItem.name')
    ->searchable(['type_items.name'])
```

## Files Changed
- `app/Filament/Resources/ProductResource.php`
  - Method `table()` diubah untuk menambahkan JOIN eksplisit
  - Kolom searchable diubah untuk menggunakan nama tabel yang tepat

## Benefits
1. Search berfungsi dengan baik tanpa error SQLSTATE
2. Bisa search berdasarkan:
   - Nama produk (`products.name`)
   - Merek produk (`products.brand`)
   - Nama varian (`items.name`)
   - Nama kategori (`type_items.name`)
3. Performance tetap baik dengan JOIN yang tepat
4. Eager loading tetap digunakan untuk menghindari N+1 query

## Testing
Untuk testing, coba:
1. Buka halaman ProductResource
2. Gunakan search box dengan kata kunci yang ada di nama produk, merek, varian, atau kategori
3. Tidak ada error SQLSTATE yang muncul
4. Hasil search sesuai dengan yang diharapkan

## Notes
- `->select('items.*')` penting untuk menghindari konflik kolom dengan nama yang sama
- Eager loading tetap digunakan untuk relationship yang dibutuhkan dalam view
- JOIN hanya dilakukan untuk kolom yang dibutuhkan dalam search/filter
