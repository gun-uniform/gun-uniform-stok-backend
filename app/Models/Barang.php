<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'detail_ukuran',
        'satuan_masuk',
        'isi_per_satuan',
        'satuan_keluar',
        'stok_minimum',
    ];

    protected $casts = [
        'isi_per_satuan' => 'integer',
        'stok_minimum' => 'integer',
    ];

    /**
     * Relasi ke barang masuk.
     */
    public function barangMasuk(): HasMany
    {
        return $this->hasMany(BarangMasuk::class);
    }

    /**
     * Relasi ke barang keluar.
     */
    public function barangKeluar(): HasMany
    {
        return $this->hasMany(BarangKeluar::class);
    }
}
