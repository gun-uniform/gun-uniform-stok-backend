<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangKeluar extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_transaksi',
        'tanggal',
        'barang_id',
        'jumlah',
        'satuan',
        'jumlah_dasar',
        'tujuan',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'integer',
        'jumlah_dasar' => 'integer',
    ];

    /**
     * Relasi ke tabel barangs.
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }
}
