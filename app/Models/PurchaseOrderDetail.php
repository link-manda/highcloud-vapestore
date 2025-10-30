<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderDetail extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_details';

    protected $fillable = [
        'id_purchase_order',
        'id_varian_produk',
        'jumlah_pesan',
        'harga_beli_saat_po',
        'subtotal',
        'jumlah_diterima', // Kolom ini akan diupdate oleh proses Barang Masuk
    ];

    /**
     * Get the purchase order that owns the detail.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'id_purchase_order');
    }

    /**
     * Get the product variant associated with the detail.
     */
    public function varianProduk(): BelongsTo
    {
        return $this->belongsTo(VarianProduk::class, 'id_varian_produk');
    }
}
