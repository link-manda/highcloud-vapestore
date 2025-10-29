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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'barang_masuks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_transaksi',
        'tanggal_masuk',
        'id_supplier',
        'id_cabang_sumber',
        'id_cabang_tujuan',
        'id_user',
        'catatan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_masuk' => 'datetime',
    ];

    /**
     * Get the supplier that owns the barang masuk.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    /**
     * Get the source branch for the barang masuk (if it's a transfer).
     */
    public function cabangSumber(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_sumber');
    }

    /**
     * Get the destination branch for the barang masuk.
     */
    public function cabangTujuan(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_tujuan');
    }

    /**
     * Get the user who recorded the barang masuk.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Get the details for the barang masuk.
     */
    public function details(): HasMany
    {
        return $this->hasMany(BarangMasukDetail::class, 'id_barang_masuk');
    }
}
