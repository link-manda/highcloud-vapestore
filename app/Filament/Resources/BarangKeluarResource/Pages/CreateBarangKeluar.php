<?php

namespace App\Filament\Resources\BarangKeluarResource\Pages;

use App\Filament\Resources\BarangKeluarResource;
use App\Models\BarangKeluar;
use App\Models\BarangKeluarDetail; // Import
use App\Models\StokCabang;
use App\Models\User; // Import
use App\Models\VarianProduk;
use App\Notifications\StokMinimumNotification; // Import Notifikasi
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Notification as NotificationFacade; // Import Facade

class CreateBarangKeluar extends CreateRecord
{
    protected static string $resource = BarangKeluarResource::class;

    // Simpan daftar Admin untuk notifikasi
    protected $adminUsers = null;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    /**
     * Mutasi data sebelum proses create (penambahan data otomatis)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('--- Starting mutateFormDataBeforeCreate for BarangKeluar ---');
        $user = Auth::user();

        // 1. Set User Pencatat
        $data['id_user'] = $user->id;

        // 2. Set Cabang (jika Staf)
        if ($user->role === 'staf' && $user->id_cabang) {
            $data['id_cabang'] = $user->id_cabang;
            Log::info('[mutateBK] Staff role detected. Cabang ID set: ' . $data['id_cabang']);
        }

        // 3. Generate Nomor Dokumen Unik
        try {
            $today = Carbon::today();
            $prefix = 'BK-' . $today->format('Ymd');

            $lastTransaction = BarangKeluar::where('nomor_transaksi', 'like', $prefix . '-%')
                ->orderBy('nomor_transaksi', 'desc')
                ->first();

            $nextSequence = 1;
            if ($lastTransaction && preg_match('/-(\d+)$/', $lastTransaction->nomor_transaksi, $matches)) {
                $nextSequence = (int)$matches[1] + 1;
            }

            $data['nomor_transaksi'] = $prefix . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            Log::info('[mutateBK] Generated Nomor Transaksi: ' . $data['nomor_transaksi']);
        } catch (\Exception $e) {
            Log::error('[mutateBK] Error generating Nomor Transaksi: ' . $e->getMessage());
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
     * Validasi Backend (Penting untuk mencegah race condition)
     * Kita cek stok sekali lagi TEPAT SEBELUM menyimpan.
     */
    protected function beforeCreate(): void
    {
        Log::info('--- Starting beforeCreate (Validation) for BarangKeluar ---');
        $data = $this->form->getState();
        $cabangId = $data['id_cabang'];
        $details = $data['details'] ?? [];

        foreach ($details as $index => $detail) {
            $jumlahJual = (int)($detail['jumlah'] ?? 0);
            $varianId = $detail['id_varian_produk'] ?? null;

            if ($jumlahJual <= 0 || $varianId === null) continue;

            $stok = StokCabang::where('id_cabang', $cabangId)
                ->where('id_varian_produk', $varianId)
                ->first();

            // Cek jika stok tidak ada ATAU stok tidak cukup
            if (!$stok || $stok->stok_saat_ini < $jumlahJual) {
                $namaVarian = VarianProduk::find($varianId)->nama_varian ?? "Item {$varianId}";
                Log::warning("[beforeCreate-BK] Validation Failed: Stok '{$namaVarian}' tidak cukup.");

                FilamentNotification::make()
                    ->title('Validasi Gagal')
                    ->body("Stok untuk item '{$namaVarian}' di cabang ini tidak mencukupi (Sisa: {$stok->stok_saat_ini}, Diminta: {$jumlahJual}).")
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    "details.{$index}.jumlah" => "Stok tidak cukup. Sisa: {$stok->stok_saat_ini}",
                ]);
            }
        }
        Log::info('[beforeCreate-BK] Backend Validation Successful.');
    }


    /**
     * Logika Inti: Kurangi Stok & Kirim Notifikasi
     */
    protected function afterCreate(): void
    {
        Log::info('--- Starting afterCreate for BarangKeluar ID: ' . $this->record->id . ' ---');
        $barangKeluar = $this->record;
        $cabangId = $barangKeluar->id_cabang;
        $detailsData = $this->data['details'] ?? [];

        // Ambil daftar Admin sekali saja
        $this->adminUsers = User::where('role', 'admin')->get();

        DB::beginTransaction();
        try {
            Log::info('[afterCreate-BK] Starting Stok Decrement & Notification Logic...');

            foreach ($detailsData as $detail) {
                $jumlah = (int)($detail['jumlah'] ?? 0);
                $hargaJual = (float)($detail['harga_jual_saat_transaksi'] ?? 0);
                $varianId = $detail['id_varian_produk'] ?? null;
                $subtotal = $jumlah * $hargaJual;

                if ($jumlah <= 0 || $varianId === null) continue;

                // 1. Buat record detail
                BarangKeluarDetail::create([
                    'id_barang_keluar' => $barangKeluar->id,
                    'id_varian_produk' => $varianId,
                    'jumlah' => $jumlah,
                    'harga_jual_saat_transaksi' => $hargaJual,
                    'subtotal' => $subtotal,
                ]);

                // 2. Kurangi Stok Cabang (Decrement)
                $stok = StokCabang::where('id_cabang', $cabangId)
                    ->where('id_varian_produk', $varianId)
                    ->first();

                // Seharusnya $stok selalu ada karena validasi di beforeCreate
                if ($stok) {
                    $stok->decrement('stok_saat_ini', $jumlah);
                    Log::info("[afterCreate-BK] Decremented Varian ID {$varianId} from Cabang {$cabangId} by {$jumlah}.");

                    // 3. [LOGIKA INTI PROPOSAL] Cek Stok Minimum & Kirim Notifikasi
                    $this->cekDanKirimNotifikasiStok($stok);
                } else {
                    Log::error("[afterCreate-BK] CRITICAL: Stok record not found for Varian ID {$varianId} at Cabang {$cabangId} despite passing validation.");
                }
            }

            DB::commit();
            Log::info('[afterCreate-BK] DB Transaction successful.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[afterCreate-BK] ERROR during transaction: ' . $e->getMessage());
            FilamentNotification::make()
                ->title('Gagal Mengurangi Stok')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

            // Hapus record Induk yang sudah terlanjur dibuat
            $barangKeluar->delete();
        }
    }

    /**
     * Helper function untuk logika notifikasi
     */
    protected function cekDanKirimNotifikasiStok(StokCabang $stok)
    {
        try {
            // Ambil data terbaru (stok_saat_ini yang sudah di-decrement)
            $stok->refresh();

            $stokMinimum = $stok->stok_minimum;
            $stokSaatIni = $stok->stok_saat_ini;

            // Kirim notifikasi HANYA jika stok minimum di-set (lebih dari 0)
            // DAN stok saat ini jatuh di bawah atau sama dengan batas minimum
            if ($stokMinimum > 0 && $stokSaatIni <= $stokMinimum) {

                // Cek apakah notifikasi untuk item ini sudah pernah dikirim hari ini
                // (Ini adalah optimasi agar Admin tidak di-spam setiap penjualan)
                // Implementasi sederhana: (Bisa diganti dengan cache jika perlu)
                // $notifTerakhir = Cache::get("notif_stok_{$stok->id}");
                // if ($notifTerakhir == now()->toDateString()) {
                //     return; // Sudah notif hari ini
                // }

                Log::info("[Notification] Triggered for Varian ID {$stok->id_varian_produk} at Cabang {$stok->id_cabang}. Stok: {$stokSaatIni} <= Min: {$stokMinimum}");

                NotificationFacade::send($this->adminUsers, new StokMinimumNotification($stok));

                // Tandai notifikasi sudah terkirim hari ini
                // Cache::put("notif_stok_{$stok->id}", now()->toDateString(), now()->addDay());

            }
        } catch (\Exception $e) {
            // Jangan hentikan transaksi utama jika notifikasi gagal
            Log::error("[Notification Error] Gagal mengirim notifikasi stok: " . $e->getMessage());
        }
    }
}
