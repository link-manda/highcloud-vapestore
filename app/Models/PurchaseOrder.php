<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BarangMasukDetail;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        'status', // 'Draft', 'Dikirim', 'Sebagian Diterima', 'Selesai', 'Dibatalkan'
        'total_harga',
        'catatan',
    ];

    protected $casts = [
        'tanggal_po' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function cabangTujuan(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_tujuan');
    }

    public function userPembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pembuat');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'id_purchase_order');
    }

    /**
     * [BARU DITAMBAHKAN]
     * Accessor untuk mendapatkan warna status PO
     * Digunakan di Filament Table (baris 260) dan Infolist (baris 304).
     */
    protected function statusColor(): Attribute
    {
        return new Attribute(
            get: fn() => match ($this->status) {
                'Draft' => 'gray',
                'Dikirim' => 'primary', // Menggunakan 'Dikirim' dari komentar fillable Anda
                'Sebagian Diterima' => 'warning', // Menggunakan 'Sebagian Diterima'
                'Selesai' => 'success', // Menggunakan 'Selesai'
                'Dibatalkan' => 'danger', // Menggunakan 'Dibatalkan'
                default => 'gray', // WAJIB: Menangani status null atau tidak dikenal
            }
        );
    }


    /**
     * [BARU] Fungsi helper untuk mengkalkulasi ulang status PO
     * berdasarkan barang yang sudah diterima.
     *
     * @param int $poId
     * @return void
     */
    public static function updateStatusAfterReceiving(int $poId): void
    {
        // ... (Kode Anda sebelumnya sudah benar) ...
        $po = PurchaseOrder::with('details')->find($poId);
        if (!$po) {
            return;
        }

        // 1. Hitung total kuantitas yang dipesan di PO
        $totalDipesan = $po->details->sum('jumlah'); // Pastikan 'jumlah' adalah kolom yang benar

        // 2. Hitung total kuantitas yang SUDAH DITERIMA
        //    dari SEMUA BarangMasuk yang terkait dengan PO ini
        $totalDiterima = BarangMasukDetail::whereHas('barangMasuk', function ($query) use ($poId) {
            $query->where('id_purchase_order', $poId);
        })->sum('jumlah'); // Kolom 'jumlah' di tabel barang_masuk_details

        // 3. Tentukan status baru
        // PERBAIKAN LOGIKA: Status 'Draft' tidak boleh tertimpa
        if ($po->status == 'Draft') {
            // Jangan ubah status jika masih Draft
            $po->save();
            return;
        }

        if ($totalDiterima == 0) {
            $po->status = 'Dikirim';
        } elseif ($totalDiterima >= $totalDipesan) {
            $po->status = 'Selesai';
        } elseif ($totalDiterima > 0 && $totalDiterima < $totalDipesan) {
            $po->status = 'Sebagian Diterima';
        }

        $po->save();
    }
}
