<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute; // Untuk status color

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'nomor_po',
        'tanggal_po',
        'id_supplier',
        'id_cabang_tujuan',
        'id_user_pembuat',
        'tanggal_estimasi_tiba',
        'status',
        'catatan',
        'total_harga',
    ];

    /**
     * Get the supplier for the purchase order.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    /**
     * Get the destination branch for the purchase order.
     */
    public function cabangTujuan(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_tujuan');
    }

    /**
     * Get the user who created the purchase order.
     */
    public function userPembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pembuat');
    }

    /**
     * Get the details for the purchase order.
     */
    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'id_purchase_order');
    }

    /**
     * Accessor for status color (used in Filament table badges).
     */
    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->status) {
                'Draft' => 'gray',
                'Submitted' => 'info',
                'Partially Received' => 'warning',
                'Completed' => 'success',
                'Cancelled' => 'danger',
                default => 'secondary',
            },
        );
    }
}
