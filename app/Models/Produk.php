<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produks'; // Nama tabel

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_kategori',
        'nama_produk',
        'deskripsi',
    ];

    /**
     * Relasi: Satu Produk dimiliki oleh satu Kategori.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    /**
     * Relasi: Satu Produk memiliki banyak VarianProduk.
     */
    public function varians(): HasMany
    {
        return $this->hasMany(VarianProduk::class, 'id_produk');
    }
}
