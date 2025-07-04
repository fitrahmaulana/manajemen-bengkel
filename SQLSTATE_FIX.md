# FIX: SQLSTATE Integrity Constraint Violation

## Masalah
Terjadi konflik antara Observer dan CreateProduct yang keduanya mencoba create item default:
```
SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: items.name
```

## Root Cause
1. **Double Creation**: Observer dan CreateProduct.afterCreate() keduanya create item
2. **NULL Constraint**: Column `name` di items table tidak boleh NULL
3. **Race Condition**: Observer jalan dulu sebelum form data tersedia

## Solusi yang Diterapkan

### 1. Disable ProductObserver
**File**: `app/Providers/AppServiceProvider.php`
```php
// Product::observe(ProductObserver::class); // Disabled
```

### 2. Handle Everything di CreateProduct
**File**: `app/Filament/Resources/ProductResource/Pages/CreateProduct.php`

#### Untuk Produk Tanpa Varian:
```php
Item::create([
    'product_id' => $product->id,
    'name' => '', // Empty string instead of null
    'sku' => $data['standard_sku'] ?? $productCode . '-STD',
    'unit' => $data['standard_unit'] ?? 'Pcs',
    'purchase_price' => $data['standard_purchase_price'] ?? 0,
    'selling_price' => $data['standard_selling_price'] ?? 0,
    'stock' => $data['standard_stock'] ?? 0,
]);
```

#### Untuk Produk Dengan Varian:
```php
Item::create([
    'product_id' => $product->id,
    'name' => 'Belum Ada Varian',
    'sku' => $productCode . '-TEMP',
    // ... defaults
]);
```

### 3. Duplication Check
```php
if ($product->items()->count() == 0) {
    // Only create if no items exist
}
```

## Keuntungan Solusi Ini

### 1. **Single Source of Truth**
- Semua logic create item di satu tempat (CreateProduct)
- Tidak ada race condition antara Observer dan form handler

### 2. **Access to Form Data**
- CreateProduct punya akses penuh ke data form
- Bisa gunakan nilai yang user input (SKU, harga, dll.)

### 3. **Predictable Behavior**
- afterCreate() jalan setelah product tersimpan
- Bisa akses $this->record dan $this->originalFormData

### 4. **Constraint Compliance**
- `name` selalu diisi (empty string atau 'Belum Ada Varian')
- Tidak ada NULL constraint violation

## Flow Sekarang

### Create Produk Tanpa Varian:
1. User isi form dengan data standard_*
2. Product tersimpan
3. afterCreate() ambil data form original
4. Create item dengan data yang user input
5. Item muncul di tabel dengan harga/stok yang benar

### Create Produk Dengan Varian:
1. User set has_variants = true
2. Product tersimpan
3. afterCreate() create placeholder item
4. Item muncul dengan warning "Belum Ada Varian"
5. User bisa klik "Tambah Varian" untuk setup

## Result
- ✅ No more SQLSTATE constraint violation
- ✅ No more duplicate item creation
- ✅ Form data properly used
- ✅ Both scenarios work perfectly
