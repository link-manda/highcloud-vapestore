<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangKeluar extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'barang_keluars'; // Eksplisit nama tabel

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_transaksi',
        'tanggal_keluar',
        'id_cabang',
        'id_user',
        'nama_pelanggan',
        'catatan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_keluar' => 'datetime',
    ];

    /**
     * Relasi ke Cabang (asal barang).
     */
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    /**
     * Relasi ke User (pencatat).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Relasi ke Detail Barang Keluar.
     */
    public function details(): HasMany
    {
        return $this->hasMany(BarangKeluarDetail::class, 'id_barang_keluar');
    }
}
