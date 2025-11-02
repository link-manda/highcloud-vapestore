<?php

namespace App\Filament\Resources\TransferStokResource\Pages;

use App\Filament\Resources\TransferStokResource;
use App\Models\TransferStok;
use App\Models\TransferStokDetail;
use App\Models\StokCabang;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateTransferStok extends CreateRecord
{
    protected static string $resource = TransferStokResource::class;

    protected function getRedirectUrl(): string
    {
        // Kembali ke halaman view setelah create
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    /**
     * Mutasi data sebelum proses create (penambahan data otomatis)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('--- Starting mutateFormDataBeforeCreate for TransferStok ---');
        $user = Auth::user();

        // 1. Set User Pembuat
        $data['id_user_pembuat'] = $user->id;

        // 2. Set Cabang Sumber (jika Staf)
        if ($user->role === 'staf' && $user->id_cabang) {
            $data['id_cabang_sumber'] = $user->id_cabang;
            Log::info('[mutateTS] Staff role detected. Cabang Sumber ID set: ' . $data['id_cabang_sumber']);
        }

        // 3. Generate Nomor Dokumen Unik
        try {
            $today = Carbon::today();
            $prefix = 'TS-' . $today->format('Ymd');

            $lastTransaction = TransferStok::where('nomor_transfer', 'like', $prefix . '-%')
                ->orderBy('nomor_transfer', 'desc')
                ->first();

            $nextSequence = 1;
            if ($lastTransaction && preg_match('/-(\d+)$/', $lastTransaction->nomor_transfer, $matches)) {
                $nextSequence = (int)$matches[1] + 1;
            }

            $data['nomor_transfer'] = $prefix . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            Log::info('[mutateTS] Generated Nomor Transfer: ' . $data['nomor_transfer']);
        } catch (\Exception $e) {
            Log::error('[mutateTS] Error generating Nomor Transfer: ' . $e->getMessage());
            FilamentNotification::make()
                ->title('Gagal Membuat Nomor Transfer')
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
        Log::info('--- Starting beforeCreate (Validation) for TransferStok ---');
        $data = $this->form->getState();
        $cabangSumberId = $data['id_cabang_sumber'];
        $details = $data['details'] ?? [];

        foreach ($details as $index => $detail) {
            $jumlahTransfer = (int)($detail['jumlah'] ?? 0);
            $varianId = $detail['id_varian_produk'] ?? null;

            if ($jumlahTransfer <= 0 || $varianId === null) continue;

            $stok = StokCabang::where('id_cabang', $cabangSumberId)
                ->where('id_varian_produk', $varianId)
                ->first();

            // Cek jika stok tidak ada ATAU stok tidak cukup
            if (!$stok || $stok->stok_saat_ini < $jumlahTransfer) {
                $namaVarian = VarianProduk::find($varianId)->nama_varian ?? "Item {$varianId}";
                Log::warning("[beforeCreate-TS] Validation Failed: Stok '{$namaVarian}' tidak cukup.");

                // Hentikan proses dan kirim notifikasi error
                FilamentNotification::make()
                    ->title('Validasi Gagal')
                    ->body("Stok untuk item '{$namaVarian}' di cabang sumber tidak mencukupi (Sisa: {$stok->stok_saat_ini}, Diminta: {$jumlahTransfer}).")
                    ->danger()
                    ->send();

                // Menggunakan ValidationException agar lebih standar
                throw ValidationException::withMessages([
                    "details.{$index}.jumlah" => "Stok tidak cukup. Sisa: {$stok->stok_saat_ini}",
                ]);
            }
        }
        Log::info('[beforeCreate-TS] Backend Validation Successful.');
    }


    /**
     * Logika Inti: Pindahkan Stok setelah data Induk tersimpan
     */
    protected function afterCreate(): void
    {
        Log::info('--- Starting afterCreate for TransferStok ID: ' . $this->record->id . ' ---');
        $transferStok = $this->record;
        $cabangSumberId = $transferStok->id_cabang_sumber;
        $cabangTujuanId = $transferStok->id_cabang_tujuan;
        $detailsData = $this->data['details'] ?? []; // Ambil dari form mentah

        DB::beginTransaction();
        try {
            Log::info('[afterCreate-TS] Starting Stok Transfer Logic...');

            foreach ($detailsData as $detail) {
                $jumlah = (int)($detail['jumlah'] ?? 0);
                $varianId = $detail['id_varian_produk'] ?? null;

                if ($jumlah <= 0 || $varianId === null) continue;

                // 1. Buat record detail
                TransferStokDetail::create([
                    'id_transfer_stok' => $transferStok->id,
                    'id_varian_produk' => $varianId,
                    'jumlah' => $jumlah,
                ]);

                // 2. Kurangi Stok Cabang Sumber (Decrement)
                // Kita sudah validasi di beforeCreate, jadi seharusnya aman
                StokCabang::where('id_cabang', $cabangSumberId)
                    ->where('id_varian_produk', $varianId)
                    ->decrement('stok_saat_ini', $jumlah);
                Log::info("[afterCreate-TS] Decremented Varian ID {$varianId} from Cabang {$cabangSumberId} by {$jumlah}.");

                // 3. Tambah Stok Cabang Tujuan (Increment)
                $stokTujuan = StokCabang::firstOrCreate(
                    ['id_cabang' => $cabangTujuanId, 'id_varian_produk' => $varianId],
                    ['stok_saat_ini' => 0, 'stok_minimum' => 0] // Buat baru jika belum ada
                );
                $stokTujuan->increment('stok_saat_ini', $jumlah);
                Log::info("[afterCreate-TS] Incremented Varian ID {$varianId} to Cabang {$cabangTujuanId} by {$jumlah}.");
            }

            DB::commit();
            Log::info('[afterCreate-TS] DB Transaction successful.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[afterCreate-TS] ERROR during transaction: ' . $e->getMessage());
            FilamentNotification::make()
                ->title('Gagal Memindahkan Stok')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

            // Hapus record Induk yang sudah terlanjur dibuat
            $transferStok->delete();
        }
    }
}
