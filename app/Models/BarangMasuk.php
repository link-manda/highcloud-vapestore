<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangMasuk extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_transaksi',
        'tanggal_masuk',
        'id_supplier',
        'id_cabang_tujuan',
        'id_user',
        'catatan',
        'id_purchase_order',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(BarangMasukDetail::class, 'id_barang_masuk');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function cabangTujuan(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_tujuan');
    }

    /**
     * HAPUS FUNGSI INI KARENA KOLOM 'id_cabang_sumber' SUDAH DI-DROP
     *
     * public function cabangSumber(): BelongsTo
     * {
     * return $this->belongsTo(Cabang::class, 'id_cabang_sumber');
     * }
     */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'id_purchase_order');
    }
}
