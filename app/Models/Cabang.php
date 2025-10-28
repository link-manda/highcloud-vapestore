<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabang extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_cabang',
        'alamat_cabang',
        'telepon_cabang',
    ];

    /**
     * Relasi: Satu Cabang memiliki banyak User (Staf).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_cabang');
    }

    /**
     * Relasi: Satu Cabang memiliki banyak data stok.
     */
    public function stokCabangs(): HasMany
    {
        return $this->hasMany(StokCabang::class, 'id_cabang');
    }
}
