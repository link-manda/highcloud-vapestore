<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $table = 'stock_opnames';

    protected $fillable = [
        'tanggal_opname',
        'id_petugas',
        'id_cabang',
        'catatan',
        'status', // draft, completed
    ];

    protected function casts(): array
    {
        return [
            'tanggal_opname' => 'date',
        ];
    }

    /**
     * Relasi ke User (petugas yang melakukan opname)
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_petugas');
    }

    /**
     * Relasi ke Cabang
     */
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    /**
     * Relasi ke detail opname
     */
    public function details(): HasMany
    {
        return $this->hasMany(StockOpnameDetail::class, 'id_stock_opname');
    }

    /**
     * Scope untuk opname yang sudah selesai
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope untuk opname draft
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}