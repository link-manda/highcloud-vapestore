<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangMasukDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'barang_masuk_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_barang_masuk',
        'id_varian_produk',
        'jumlah',
        'harga_beli_saat_transaksi',
        'subtotal',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jumlah' => 'integer',
        'harga_beli_saat_transaksi' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the barang masuk header that owns the detail.
     */
    public function barangMasuk(): BelongsTo
    {
        return $this->belongsTo(BarangMasuk::class, 'id_barang_masuk');
    }

    /**
     * Get the varian produk associated with the detail.
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'id_varian_produk');
    }
}
