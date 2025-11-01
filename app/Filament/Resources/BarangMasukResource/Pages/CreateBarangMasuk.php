<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use App\Models\PurchaseOrder; // Import
use App\Models\StokCabang; // Import
use App\Models\VarianProduk; // Import
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Import Log
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification; // Import

class CreateBarangMasuk extends CreateRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Mutasi data sebelum proses create (validasi/penambahan data otomatis)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Set User Pencatat
        $data['id_user_pencatat'] = auth()->id();

        // 2. Generate Nomor Dokumen Unik (Contoh: BM-20251101-001)
        $prefix = 'BM-' . now()->format('Ymd');
        $lastRecord = DB::table('barang_masuks')
            ->where('nomor_transaksi', 'like', $prefix . '%')
            ->orderBy('nomor_transaksi', 'desc')
            ->first();

        $nextSequence = 1;
        if ($lastRecord) {
            $lastSequence = (int) substr($lastRecord->nomor_transaksi, -3);
            $nextSequence = $lastSequence + 1;
        }
        $data['nomor_transaksi'] = $prefix . '-' . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

        return $data;
    }

    /**
     * Logika utama setelah record (induk) berhasil disimpan.
     * Kita akan update stok di sini.
     */
    protected function afterCreate(): void
    {
        Log::info('--- Starting afterCreate for BarangMasuk ID: ' . $this->record->id . ' ---');
        $barangMasuk = $this->record;
        $idCabangTujuan = $barangMasuk->id_cabang_tujuan;

        // [PERBAIKAN] Ambil 'details' dari relasi ($this->record),
        // BUKAN dari data form mentah ($this->data).
        // Ini adalah record detail yang BARU SAJA dibuat otomatis oleh Filament.
        $createdDetails = $this->record->details;

        if ($createdDetails->isEmpty()) {
            Log::warning('[afterCreate-BM] No details were auto-created by Filament. Nothing to process.');
            return;
        }

        DB::beginTransaction();
        try {

            // 0. [HAPUS] BAGIAN MANUAL CREATE DETAILS
            // Logika `BarangMasukDetail::create([...])` dihapus
            // karena sudah ditangani otomatis oleh Filament.

            // 1. LOGIKA UPDATE STOK (INCREMENT)
            Log::info('[afterCreate-BM] Starting Stok Increment...');
            foreach ($createdDetails as $detail) { // Loop dari detail yang sudah ada
                Log::info("[afterCreate-BM] Processing Stok Varian ID: {$detail->id_varian_produk}, Jumlah: {$detail->jumlah}, Cabang: {$idCabangTujuan}");

                $stok = StokCabang::firstOrCreate(
                    ['id_cabang' => $idCabangTujuan, 'id_varian_produk' => $detail->id_varian_produk],
                    ['stok_saat_ini' => 0, 'stok_minimum' => 0]
                );

                $stok->increment('stok_saat_ini', $detail->jumlah);
                Log::info("[afterCreate-BM] Stok Varian ID: {$detail->id_varian_produk} incremented. New stok: {$stok->stok_saat_ini}");
            }
            Log::info('[afterCreate-BM] Stok Increment finished.');


            // 2. LOGIKA UPDATE PURCHASE ORDER (PO)
            if ($barangMasuk->id_purchase_order) {
                Log::info('[afterCreate-BM] PO ID detected: ' . $barangMasuk->id_purchase_order . '. Starting PO Update...');

                $po = PurchaseOrder::with('details')->find($barangMasuk->id_purchase_order);

                if ($po) {
                    $totalDipesanPo = $po->details->sum('jumlah_pesan');

                    foreach ($createdDetails as $itemDiterima) { // Loop dari detail yang sudah ada
                        $poDetail = $po->details->firstWhere('id_varian_produk', $itemDiterima->id_varian_produk);

                        if ($poDetail) {
                            $poDetail->increment('jumlah_diterima', $itemDiterima->jumlah);
                            Log::info("[afterCreate-BM] PO Detail Varian ID: {$poDetail->id_varian_produk} incremented by {$itemDiterima->jumlah}. New diterima: {$poDetail->jumlah_diterima}");
                        }
                    }

                    $po->load('details'); // Muat ulang relasi
                    $totalDiterimaPo = $po->details->sum('jumlah_diterima');
                    Log::info("[afterCreate-BM] PO Total Dipesan: {$totalDipesanPo}, PO Total Diterima (Updated): {$totalDiterimaPo}");

                    if ($totalDiterimaPo >= $totalDipesanPo) {
                        $po->status = 'Completed';
                        Log::info("[afterCreate-BM] PO Status changed to: Completed");
                    } else if ($totalDiterimaPo > 0) {
                        $po->status = 'Partially Received';
                        Log::info("[afterCreate-BM] PO Status changed to: Partially Received");
                    }

                    $po->save();
                } else {
                    Log::warning('[afterCreate-BM] PO not found during update logic.');
                }
            }

            DB::commit();
            Log::info('[afterCreate-BM] DB Transaction successful.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[afterCreate-BM] ERROR during transaction: ' . $e->getMessage());
            FilamentNotification::make()
                ->title('Gagal Memperbarui Stok atau PO')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        Log::info('--- Finished afterCreate for BarangMasuk ID: ' . $this->record->id . ' ---');
    }
}
