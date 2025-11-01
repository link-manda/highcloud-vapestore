<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangMasuk extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_transaksi',
        'tanggal_masuk',
        'id_supplier',
        'id_cabang_tujuan',
        'id_user',
        'catatan',
        'id_purchase_order',
    ];

    /**
     * Get the details for the barang masuk.
     */
    public function details(): HasMany
    {
        return $this->hasMany(BarangMasukDetail::class, 'id_barang_masuk');
    }

    /**
     * Get the user that created the barang masuk.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Get the supplier for the barang masuk.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    /**
     * Get the destination branch for the barang masuk.
     */
    public function cabangTujuan(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_tujuan');
    }

    /**
     * Get the source branch for the barang masuk.
     */
    public function cabangSumber(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_sumber');
    }

    /**
     * Get the purchase order associated with the barang masuk.
     */
    public function purchaseOrder(): BelongsTo
    {
        // TAMBAHKAN FUNGSI INI JIKA BELUM ADA
        return $this->belongsTo(PurchaseOrder::class, 'id_purchase_order');
    }
}
