<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VarianProduk extends Model
{
    use HasFactory;

    protected $table = 'varian_produks'; // Nama tabel

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_produk',
        'nama_varian',
        'sku_code',
        'harga_beli',
        'harga_jual',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
        ];
    }

    /**
     * Relasi: Satu VarianProduk dimiliki oleh satu Produk (induk).
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    /**
     * Relasi: Satu VarianProduk memiliki banyak data stok di berbagai cabang.
     */
    public function stokCabangs(): HasMany
    {
        return $this->hasMany(StokCabang::class, 'id_varian_produk');
    }
}
