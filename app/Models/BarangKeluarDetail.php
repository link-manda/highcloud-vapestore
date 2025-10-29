<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangKeluarDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'barang_keluar_details'; // Eksplisit nama tabel

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_barang_keluar',
        'id_varian_produk',
        'jumlah',
        'harga_jual_saat_transaksi',
        'subtotal',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jumlah' => 'integer',
        'harga_jual_saat_transaksi' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relasi ke BarangKeluar (induk).
     */
    public function barangKeluar(): BelongsTo
    {
        return $this->belongsTo(BarangKeluar::class, 'id_barang_keluar');
    }

    /**
     * Relasi ke VarianProduk (item).
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'id_varian_produk');
    }
}
