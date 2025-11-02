<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferStokDetail extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'transfer_stok_details';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_transfer_stok',
        'id_varian_produk',
        'jumlah',
    ];

    /**
     * Tipe data yang harus di-cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jumlah' => 'integer',
    ];

    /**
     * Relasi: Mendapatkan data header transfer stok yang memiliki detail ini.
     */
    public function transferStok(): BelongsTo
    {
        return $this->belongsTo(TransferStok::class, 'id_transfer_stok');
    }

    /**
     * Relasi: Mendapatkan data varian produk yang ditransfer.
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'id_varian_produk');
    }
}
