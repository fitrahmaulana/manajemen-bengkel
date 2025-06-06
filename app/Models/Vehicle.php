<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'customer_id', // ID pelanggan yang memiliki kendaraan ini
        'license_plate', // Nomor Polisi
        'brand', // Merek kendaraan
        'model', // Model kendaraan
        'color', // Warna kendaraan
        'year', // Tahun pembuatan kendaraan
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer()
    {
        // Satu kendaraan dimiliki oleh SATU pelanggan
        return $this->belongsTo(Customer::class);
    }
}
