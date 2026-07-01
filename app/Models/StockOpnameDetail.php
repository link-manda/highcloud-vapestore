<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameDetail extends Model
{
    use HasFactory;

    protected $table = 'stock_opname_details';

    protected static function booted(): void
    {
        static::saving(function (self $detail): void {
            $detail->syncSelisih();
        });
    }

    protected $fillable = [
        'id_stock_opname',
        'id_varian_produk',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'stok_sistem' => 'integer',
            'stok_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    /**
     * Relasi ke StockOpname
     */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'id_stock_opname');
    }

    /**
     * Relasi ke VarianProduk
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'id_varian_produk');
    }

    /**
     * Method untuk menghitung selisih
     */
    public function calculateSelisih(): int
    {
        return (int) $this->stok_fisik - (int) $this->stok_sistem;
    }

    /**
     * Sinkronkan selisih sebelum detail disimpan
     */
    public function syncSelisih(): void
    {
        $this->selisih = $this->calculateSelisih();
    }

    /**
     * Method untuk update selisih otomatis
     */
    public function updateSelisih(): void
    {
        $this->syncSelisih();
        $this->save();
    }
}
