<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokCabang extends Model
{
    use HasFactory;

    protected $table = 'stok_cabangs';

    protected $fillable = [
        'id_cabang',
        'id_varian_produk',
        'stok_saat_ini',
        'stok_minimum',
    ];

    protected function casts(): array
    {
        return [
            'stok_saat_ini' => 'integer',
            'stok_minimum' => 'integer',
        ];
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'id_varian_produk');
    }

    /**
     * Method untuk menambah stok
     */
    public function tambahStok(int $jumlah): void
    {
        $this->update([
            'stok_saat_ini' => $this->stok_saat_ini + $jumlah
        ]);
    }

    /**
     * Method untuk mengurangi stok
     */
    public function kurangiStok(int $jumlah): void
    {
        $stokBaru = $this->stok_saat_ini - $jumlah;
        if ($stokBaru < 0) {
            throw new \Exception('Stok tidak boleh kurang dari 0');
        }

        $this->update([
            'stok_saat_ini' => $stokBaru
        ]);
    }

    /**
     * Method untuk memeriksa apakah stok sudah ada
     */
    public static function stokExists(int $cabangId, int $varianProdukId): bool
    {
        return static::where('id_cabang', $cabangId)
            ->where('id_varian_produk', $varianProdukId)
            ->exists();
    }
}
