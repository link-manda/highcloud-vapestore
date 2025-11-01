<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use App\Models\BarangMasuk;
use App\Models\BarangMasukDetail; // <-- [PENTING] Import model ini
use App\Models\PurchaseOrder;
use App\Models\StokCabang;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class CreateBarangMasuk extends CreateRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    /**
     * Mutasi data sebelum proses create (validasi/penambahan data otomatis)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('--- Starting mutateFormDataBeforeCreate for BarangMasuk ---');
        $user = Auth::user();

        // 1. Set User Pencatat (SESUAI DENGAN ERROR SEBELUMNYA)
        $data['id_user'] = $user->id;

        // Atur cabang tujuan jika staf
        if ($user->role === 'staf' && $user->id_cabang) {
            $data['id_cabang_tujuan'] = $user->id_cabang;
            Log::info('[mutateBM] Staff role detected. Cabang Tujuan ID set: ' . $data['id_cabang_tujuan']);
        }

        // 2. Generate Nomor Dokumen Unik
        try {
            $today = Carbon::today();
            $prefix = 'BM-' . $today->format('Ymd');

            $lastTransaction = BarangMasuk::where('nomor_transaksi', 'like', $prefix . '-%')
                ->orderBy('nomor_transaksi', 'desc')
                ->first();

            $nextSequence = 1;
            if ($lastTransaction && preg_match('/-(\d+)$/', $lastTransaction->nomor_transaksi, $matches)) {
                $nextSequence = (int)$matches[1] + 1;
            }

            $data['nomor_transaksi'] = $prefix . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            Log::info('[mutateBM] Generated Nomor Transaksi: ' . $data['nomor_transaksi']);
        } catch (\Exception $e) {
            Log::error('[mutateBM] Error generating Nomor Transaksi: ' . $e->getMessage());
            FilamentNotification::make()
                ->title('Gagal Membuat Nomor Transaksi')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
            throw $e;
        }

        Log::info('--- Finished mutateFormDataBeforeCreate ---');
        return $data;
    }

    /**
     * [LOGIKA BARU YANG ROBUST]
     * Ambil kendali penuh atas penyimpanan detail dan update stok.
     */
    protected function afterCreate(): void
    {
        Log::info('--- Starting afterCreate for BarangMasuk ID: ' . $this->record->id . ' ---');
        $barangMasuk = $this->record;
        $idCabangTujuan = $barangMasuk->id_cabang_tujuan;

        // 1. Ambil data 'details' dari FORM DATA MENTAH ($this->data)
        // Kita tidak lagi mengandalkan $this->record->details
        $detailsData = $this->data['details'] ?? [];

        if (empty($detailsData)) {
            Log::warning('[afterCreate-BM] No details found in form data. Transaction stopped.');
            FilamentNotification::make()
                ->title('Gagal Menyimpan Detail')
                ->body('Tidak ada item detail yang ditemukan untuk disimpan.')
                ->danger()
                ->send();
            return;
        }

        DB::beginTransaction();
        try {
            Log::info('[afterCreate-BM] Starting Manual Detail Creation & Stok Increment...');

            // 2. Loop data form dan BUAT DETAIL + UPDATE STOK
            foreach ($detailsData as $detail) {
                // Pastikan tipe data benar
                $jumlah = (int)($detail['jumlah'] ?? 0);
                $harga = (float)($detail['harga_beli_saat_transaksi'] ?? 0);
                $subtotal = $jumlah * $harga;
                $varianId = $detail['id_varian_produk'] ?? null;

                if ($jumlah <= 0 || $varianId === null) {
                    Log::warning('[afterCreate-BM] Skipping invalid item detail.', $detail);
                    continue; // Lewati item yang tidak valid
                }

                // A. BUAT DETAIL (MANUAL)
                BarangMasukDetail::create([
                    'id_barang_masuk' => $barangMasuk->id,
                    'id_varian_produk' => $varianId,
                    'jumlah' => $jumlah,
                    'harga_beli_saat_transaksi' => $harga,
                    'subtotal' => $subtotal,
                ]);
                Log::info("[afterCreate-BM] Created BarangMasukDetail for Varian ID: {$varianId}, Jumlah: {$jumlah}");

                // B. UPDATE STOK (MANUAL)
                $stok = StokCabang::firstOrCreate(
                    ['id_cabang' => $idCabangTujuan, 'id_varian_produk' => $varianId],
                    ['stok_saat_ini' => 0, 'stok_minimum' => 0] // Nilai default jika baru dibuat
                );

                $stok->increment('stok_saat_ini', $jumlah);
                Log::info("[afterCreate-BM] Stok Varian ID: {$varianId} incremented by {$jumlah}. New stok: {$stok->stok_saat_ini}");
            }
            Log::info('[afterCreate-BM] Finished Detail Creation & Stok Increment.');

            // 3. LOGIKA UPDATE PURCHASE ORDER (PO)
            if ($barangMasuk->id_purchase_order) {
                Log::info('[afterCreate-BM] PO ID detected: ' . $barangMasuk->id_purchase_order . '. Starting PO Update...');
                $po = PurchaseOrder::with('details')->find($barangMasuk->id_purchase_order);

                if ($po) {
                    $totalDipesanPo = $po->details->sum('jumlah_pesan');

                    // Loop lagi data form untuk update PO
                    foreach ($detailsData as $itemDiterima) {
                        $jumlahDiterima = (int)($itemDiterima['jumlah'] ?? 0);
                        $varianId = $itemDiterima['id_varian_produk'] ?? null;

                        if ($jumlahDiterima <= 0 || $varianId === null) continue;

                        $poDetail = $po->details->firstWhere('id_varian_produk', $varianId);

                        if ($poDetail) {
                            $poDetail->increment('jumlah_diterima', $jumlahDiterima);
                            Log::info("[afterCreate-BM] PO Detail Varian ID: {$poDetail->id_varian_produk} incremented by {$jumlahDiterima}. New diterima: {$poDetail->jumlah_diterima}");
                        }
                    }

                    // Cek status PO
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
