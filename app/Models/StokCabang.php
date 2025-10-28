<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokCabang extends Model
{
    use HasFactory;

    protected $table = 'stok_cabangs'; // Nama tabel

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_cabang',
        'id_varian_produk',
        'stok_saat_ini',
        'stok_minimum',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stok_saat_ini' => 'integer',
            'stok_minimum' => 'integer',
        ];
    }

    /**
     * Relasi: Data stok ini dimiliki oleh satu Cabang.
     */
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    /**
     * Relasi: Data stok ini dimiliki oleh satu VarianProduk.
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'id_varian_produk');
    }
}
