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
        'image',
        'latitude',
        'longitude',
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

    /**
     * Cleanup old image on update or delete
     */
    protected static function booted(): void
    {
        static::updated(function (Cabang $cabang) {
            if ($cabang->isDirty('image')) {
                $oldImage = $cabang->getOriginal('image');
                if ($oldImage) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldImage);
                }
            }
        });

        static::deleted(function (Cabang $cabang) {
            if ($cabang->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($cabang->image);
            }
        });
    }
}
