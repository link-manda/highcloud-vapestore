<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use App\Models\BarangMasuk;
use App\Models\StokCabang;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\BarangMasukDetail; // <= IMPORT BARU
use App\Notifications\StokMinimumNotification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Filament\Notifications\Notification as FilamentNotification;

class CreateBarangMasuk extends CreateRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('--- Starting mutateFormDataBeforeCreate for BarangMasuk ---');
        $user = Auth::user();
        $data['id_user'] = $user->id;

        if ($user->role === 'staf' && $user->id_cabang) {
            $data['id_cabang_tujuan'] = $user->id_cabang;
            Log::info('[mutateBM] Staff role detected. Cabang Tujuan ID set: ' . $data['id_cabang_tujuan']);
        }

        try {
            $today = Carbon::today();
            $lastTransactionToday = BarangMasuk::whereDate('created_at', $today)
                ->orderBy('id', 'desc')
                ->first();
            $countToday = 0;
            if ($lastTransactionToday && preg_match('/-(\d+)$/', $lastTransactionToday->nomor_transaksi, $matches)) {
                $countToday = (int)$matches[1];
            }
            $data['nomor_transaksi'] = 'BM-' . $today->format('Ymd') . '-' . str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
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

    // Validasi sekarang akan berjalan karena 'details' ada di $formData
    protected function beforeCreate(): void
    {
        Log::info('--- Starting beforeCreate for BarangMasuk ---');

        $formData = $this->form->getState();

        // DEBUG: Log seluruh structure details
        Log::info('[beforeCreate-BM] Full form data details: ', $formData['details'] ?? []);

        $idPo = $formData['id_purchase_order'] ?? null;

        if ($idPo) {
            Log::info('[beforeCreate-BM] PO ID provided: ' . $idPo . '. Validating details...');

            $po = PurchaseOrder::with('details.varianProduk')->find($idPo);
            if (!$po) {
                FilamentNotification::make()
                    ->title('Validasi Gagal')
                    ->body("Purchase Order (ID: {$idPo}) tidak ditemukan.")
                    ->danger()
                    ->send();
                $this->halt();
                return;
            }

            // Validasi details structure
            if (empty($formData['details']) || !is_array($formData['details'])) {
                Log::error('[beforeCreate-BM] Details is empty or not array');
                FilamentNotification::make()
                    ->title('Validasi Gagal')
                    ->body("Detail item dari PO tidak boleh kosong.")
                    ->danger()
                    ->send();
                $this->halt();
                return;
            }

            foreach ($formData['details'] as $index => $itemDiterima) {
                $varianId = $itemDiterima['id_varian_produk'] ?? null;

                // Validasi yang lebih detail
                if (empty($varianId)) {
                    Log::error("[beforeCreate-BM] Item at index {$index} has null or empty id_varian_produk");
                    Log::error("[beforeCreate-BM] Full item data:", $itemDiterima);

                    FilamentNotification::make()
                        ->title('Validasi Gagal')
                        ->body("Item ke-" . ($index + 1) . " tidak memiliki varian produk yang valid. Silakan refresh halaman dan coba lagi.")
                        ->danger()
                        ->send();
                    $this->halt();
                    return;
                }

                $poDetail = $po->details->firstWhere('id_varian_produk', $varianId);

                if (!$poDetail) {
                    Log::error("[beforeCreate-BM] PO Detail not found for varian_id: {$varianId}");
                    FilamentNotification::make()
                        ->title('Validasi Gagal')
                        ->body("Item (Varian ID: {$varianId}) tidak ditemukan di PO terkait.")
                        ->danger()
                        ->send();
                    $this->halt();
                    return;
                }

                $jumlahDiterimaDiForm = $itemDiterima['jumlah'] ?? 0;

                // Pastikan jumlah adalah integer, bukan array
                if (is_array($jumlahDiterimaDiForm)) {
                    Log::error("[beforeCreate-BM] Jumlah is array for item {$index}");
                    $jumlahDiterimaDiForm = 0;
                }

                $jumlahDiterimaDiForm = (int)$jumlahDiterimaDiForm;
                $jumlahPesan = (int)$poDetail->jumlah_pesan;
                $jumlahSudahDiterima = (int)$poDetail->jumlah_diterima;
                $sisaQty = $jumlahPesan - $jumlahSudahDiterima;

                Log::info("[beforeCreate-BM] VALIDATING ITEM: Varian ID {$varianId}");
                Log::info("[beforeCreate-BM]   -> Jumlah di Form: {$jumlahDiterimaDiForm}");
                Log::info("[beforeCreate-BM]   -> Jumlah di PO (Pesan): {$jumlahPesan}");
                Log::info("[beforeCreate-BM]   -> Jumlah di PO (Diterima): {$jumlahSudahDiterima}");
                Log::info("[beforeCreate-BM]   -> Sisa Qty (Calculated): {$sisaQty}");

                if ($jumlahDiterimaDiForm > $sisaQty) {
                    $namaVarian = $poDetail->varianProduk ? $poDetail->varianProduk->nama_varian : "Varian ID " . $varianId;
                    Log::warning("[beforeCreate-BM] Validation Failed: Jumlah diterima ({$jumlahDiterimaDiForm}) > Sisa PO ({$sisaQty}) for Varian: {$namaVarian}");

                    FilamentNotification::make()
                        ->title('Validasi Gagal')
                        ->body("Jumlah diterima untuk item '{$namaVarian}' ({$jumlahDiterimaDiForm}) melebihi sisa yang dipesan di PO ({$sisaQty}).")
                        ->danger()
                        ->send();

                    $this->halt();
                    return;
                }
            }
            Log::info('[beforeCreate-BM] PO Validation Successful.');
        } else {
            Log::info('[beforeCreate-BM] No PO ID provided. Skipping PO validation.');
        }
    }


    protected function afterCreate(): void
    {
        Log::info('--- Starting afterCreate for BarangMasuk ID: ' . $this->record->id . ' ---');
        $barangMasuk = $this->record;
        $idCabangTujuan = $barangMasuk->id_cabang_tujuan;

        // Ambil data 'details' dari form (bukan dari $this->record, karena belum ada)
        $detailsData = $this->data['details'] ?? [];
        if (empty($detailsData)) {
            Log::warning('[afterCreate-BM] No details found in form data to process.');
            return;
        }

        DB::beginTransaction();
        try {

            $createdDetails = []; // Array untuk menampung detail yang baru dibuat

            // 0. (BARU) MANUAL CREATE DETAILS
            Log::info('[afterCreate-BM] Starting Manual Detail Creation...');
            // Di dalam afterCreate(), perbaiki bagian pembuatan detail:
            foreach ($detailsData as $detail) {
                // Pastikan tipe data benar sebelum perhitungan
                $jumlah = $detail['jumlah'] ?? 0;
                $harga = $detail['harga_beli_saat_transaksi'] ?? 0;

                if (is_array($jumlah)) {
                    Log::warning('[afterCreate-BM] jumlah is array, using 0');
                    $jumlah = 0;
                }
                if (is_array($harga)) {
                    Log::warning('[afterCreate-BM] harga_beli_saat_transaksi is array, using 0');
                    $harga = 0;
                }

                $subtotal = (int)$jumlah * (float)$harga;

                $createdDetail = BarangMasukDetail::create([
                    'id_barang_masuk' => $barangMasuk->id,
                    'id_varian_produk' => $detail['id_varian_produk'],
                    'jumlah' => (int)$jumlah,
                    'harga_beli_saat_transaksi' => (float)$harga,
                    'subtotal' => $subtotal,
                ]);
                $createdDetails[] = $createdDetail;
                Log::info("[afterCreate-BM] Created BarangMasukDetail ID: {$createdDetail->id}");
            }


            // 1. LOGIKA UPDATE STOK (INCREMENT)
            Log::info('[afterCreate-BM] Starting Stok Increment...');
            foreach ($createdDetails as $detail) { // Loop dari detail yang baru dibuat
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

                    foreach ($createdDetails as $itemDiterima) { // Loop dari detail yang baru dibuat
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
