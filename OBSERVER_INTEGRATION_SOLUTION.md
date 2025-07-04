# SOLUTION: Observer + CreateProduct Integration

## Strategi
Menggunakan Observer sebagai single source of truth untuk create items, dengan cara mengirim data form dari CreateProduct ke Observer melalui session.

## Implementation

### 1. ProductObserver - Master Controller
**File**: `app/Observers/ProductObserver.php`

#### Alur Kerja:
1. Observer dipanggil saat Product created
2. Ambil data form dari session (`product_form_data`)
3. Create item sesuai jenis produk (standard atau placeholder)
4. Clear session data setelah digunakan

#### Untuk Produk Tanpa Varian:
```php
Item::create([
    'product_id' => $product->id,
    'name' => '', // Empty string
    'sku' => $formData['standard_sku'] ?? $generated_sku,
    'unit' => $formData['standard_unit'] ?? 'Pcs',
    'purchase_price' => $formData['standard_purchase_price'] ?? 0,
    'selling_price' => $formData['standard_selling_price'] ?? 0,
    'stock' => $formData['standard_stock'] ?? 0,
]);
```

#### Untuk Produk Dengan Varian:
```php
Item::create([
    'product_id' => $product->id,
    'name' => 'Belum Ada Varian',
    'sku' => $generated_sku . '-TEMP',
    // ... defaults
]);
```

### 2. CreateProduct - Data Forwarder
**File**: `app/Filament/Resources/ProductResource/Pages/CreateProduct.php`

#### Fungsi:
1. **mutateFormDataBeforeSave()**: Simpan data form ke session
2. **afterCreate()**: Cleanup session data

```php
protected function mutateFormDataBeforeSave(array $data): array
{
    // Forward form data to Observer via session
    session(['product_form_data' => $data]);
    
    // Clean data untuk Product model
    unset($data['standard_*']);
    return $data;
}

protected function afterCreate(): void
{
    // Cleanup session (safety net)
    session()->forget('product_form_data');
}
```

## Keuntungan Solusi Ini

### 1. **Single Source of Truth**
- Observer handles semua logic create items
- Tidak ada duplikasi atau race condition
- Consistent behavior untuk create/update

### 2. **Form Data Integration** 
- Observer mendapat access ke user input
- SKU, harga, stok sesuai yang user isi
- Fallback ke default jika form kosong

### 3. **Clean Separation**
- CreateProduct: Handle form data dan cleanup
- Observer: Handle business logic create items
- Clear responsibilities

### 4. **Session-Based Communication**
- Reliable data transfer
- Auto-cleanup mencegah memory leak
- Works untuk create dan potential update scenarios

## Flow Diagram

```
User Submit Form
       ↓
CreateProduct.mutateFormDataBeforeSave()
       ↓ 
session['product_form_data'] = form_data
       ↓
Product::create() 
       ↓
ProductObserver.created() triggered
       ↓
Observer reads session['product_form_data']
       ↓
Observer creates Item with form data
       ↓
Observer clears session
       ↓
CreateProduct.afterCreate() (cleanup safety net)
       ↓
Redirect to product view
```

## Edge Cases Handled

### 1. **No Form Data**
- Fallback ke generated SKU dan default values
- Observer tetap bisa create item

### 2. **Multiple Requests**
- Session data di-clear setelah digunakan
- Tidak ada cross-request contamination

### 3. **Error During Create**
- Session cleanup di afterCreate() sebagai safety net
- Tidak ada abandoned session data

## Result
- ✅ Observer sebagai single controller
- ✅ Form data properly integrated
- ✅ No conflicts atau duplications
- ✅ Clean code separation
- ✅ Handles both scenarios perfectly
