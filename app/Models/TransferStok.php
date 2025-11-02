<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferStok extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'transfer_stoks';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_transfer',
        'tanggal_transfer',
        'id_cabang_sumber',
        'id_cabang_tujuan',
        'id_user_pembuat',
        'catatan',
    ];

    /**
     * Tipe data yang harus di-cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_transfer' => 'date',
    ];

    /**
     * Relasi: Mendapatkan detail item dari transfer stok ini.
     */
    public function details(): HasMany
    {
        return $this->hasMany(TransferStokDetail::class, 'id_transfer_stok');
    }

    /**
     * Relasi: Mendapatkan cabang SUMBER (dari mana barang berasal).
     */
    public function cabangSumber(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_sumber');
    }

    /**
     * Relasi: Mendapatkan cabang TUJUAN (ke mana barang dikirim).
     */
    public function cabangTujuan(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_tujuan');
    }

    /**
     * Relasi: Mendapatkan user (staf/admin) yang membuat transfer.
     */
    public function userPembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pembuat');
    }
}
