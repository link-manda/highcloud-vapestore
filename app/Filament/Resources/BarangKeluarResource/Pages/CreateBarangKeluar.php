<?php

namespace App\Filament\Resources\BarangKeluarResource\Pages;

use App\Filament\Resources\BarangKeluarResource;
use App\Models\StokCabang;
use App\Models\User; // <-- Tambahkan use User
use App\Notifications\StokMinimumNotification; // <-- Tambahkan use Notification
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Notification as NotificationFacade; // <-- Tambahkan alias untuk facade Notification

class CreateBarangKeluar extends CreateRecord
{
    protected static string $resource = BarangKeluarResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // 1. Generate Nomor Transaksi Unik
        $prefix = 'BK-';
        $today = now()->format('Ymd');
        $latestToday = DB::table('barang_keluars')
            ->where('nomor_transaksi', 'like', $prefix . $today . '-%')
            ->orderBy('nomor_transaksi', 'desc')
            ->first();

        $nextId = 1;
        if ($latestToday) {
            $lastId = (int) substr($latestToday->nomor_transaksi, -4);
            $nextId = $lastId + 1;
        }
        $data['nomor_transaksi'] = $prefix . $today . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // 2. Set User Pencatat
        $data['id_user'] = $user->id;

        // 3. Jika user adalah Staf, pastikan id_cabang diisi dari data Staf
        if ($user->role === 'staf') {
            $data['id_cabang'] = $user->id_cabang;
            // Pastikan data ini ada saat submit meskipun field disabled
            if (!isset($data['id_cabang'])) {
                \Log::warning("id_cabang missing for staff user {$user->id} during BarangKeluar creation.");
                // Throw exception or handle error appropriately
                throw ValidationException::withMessages(['id_cabang' => 'Cabang tidak terdeteksi untuk pengguna staf.']);
            }
        } elseif (!isset($data['id_cabang'])) {
            // Handle jika admin tapi cabang belum dipilih (seharusnya tidak terjadi jika required)
            throw ValidationException::withMessages(['id_cabang' => 'Cabang asal barang wajib dipilih.']);
        }


        return $data;
    }

    protected function beforeCreate(): void
    {
        $items = $this->data['details'] ?? [];
        $idCabang = $this->data['id_cabang'] ?? null;

        if (!$idCabang || empty($items)) {
            Notification::make()
                ->title('Error Validasi')
                ->body('Cabang atau Detail item tidak boleh kosong.')
                ->danger()
                ->send();
            $this->halt();
        }

        try {
            DB::transaction(function () use ($items, $idCabang) {
                foreach ($items as $index => $item) {
                    $idVarian = $item['id_varian_produk'] ?? null;
                    $jumlahKeluar = $item['jumlah'] ?? 0;

                    if (!$idVarian || $jumlahKeluar <= 0) {
                        throw ValidationException::withMessages([
                            "data.details.{$index}.jumlah" => 'Varian Produk atau Jumlah tidak valid.',
                        ]);
                    }

                    $stok = StokCabang::where('id_cabang', $idCabang)
                        ->where('id_varian_produk', $idVarian)
                        ->lockForUpdate()
                        ->first();

                    $stokTersedia = $stok?->stok_saat_ini ?? 0;

                    if ($jumlahKeluar > $stokTersedia) {
                        $varian = \App\Models\VarianProduk::find($idVarian);
                        $namaVarian = $varian ? "{$varian->produk->nama_produk} - {$varian->nama_varian}" : "ID: {$idVarian}";
                        $errorMessage = "Stok {$namaVarian} tidak mencukupi (Tersedia: {$stokTersedia}, Diminta: {$jumlahKeluar}).";

                        throw ValidationException::withMessages([
                            "data.details.{$index}.jumlah" => $errorMessage,
                        ]);
                    }
                }
            });
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Validasi Stok Gagal')
                ->body('Jumlah keluar melebihi stok tersedia. Periksa kembali detail item.')
                ->danger()
                ->send();
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Transaksi')
                ->body('Terjadi kesalahan saat memvalidasi stok: ' . $e->getMessage())
                ->danger()
                ->send();
            $this->halt();
        }
    }


    protected function afterCreate(): void
    {
        $barangKeluar = $this->record;
        $barangKeluar->load('details');
        $stokUntukNotifikasi = []; // Kumpulkan record StokCabang yang perlu notifikasi

        try {
            DB::transaction(function () use ($barangKeluar, &$stokUntukNotifikasi) { // Gunakan reference &
                $idCabang = $barangKeluar->id_cabang;

                if (!$idCabang) {
                    \Log::error("Cabang tidak ditemukan untuk Barang Keluar ID: {$barangKeluar->id}");
                    throw new \Exception("Cabang tidak valid.");
                }

                foreach ($barangKeluar->details as $item) {
                    $idVarian = $item->id_varian_produk;
                    $jumlahKeluar = $item->jumlah;

                    if (!$idVarian || $jumlahKeluar <= 0) {
                        \Log::warning("Skipping invalid item in BarangKeluarDetail ID: {$item->id}");
                        continue;
                    }

                    $stok = StokCabang::where('id_cabang', $idCabang)
                        ->where('id_varian_produk', $idVarian)
                        // ->lockForUpdate() // Seharusnya tidak perlu lock lagi jika beforeCreate sudah pakai lock
                        ->first();

                    if (!$stok) {
                        // Ini seharusnya tidak terjadi jika stok awal sudah diinisialisasi
                        \Log::error("Stok record not found for Varian ID: {$idVarian}, Cabang ID: {$idCabang} during BarangKeluar ID: {$barangKeluar->id}. Cannot decrement.");
                        throw new \Exception("Record stok tidak ditemukan untuk varian ID {$idVarian}.");
                    }

                    // Ambil stok sebelum dikurangi untuk cek notifikasi
                    $stokSebelum = $stok->stok_saat_ini;
                    $stokMinimum = $stok->stok_minimum;

                    if ($stokSebelum < $jumlahKeluar) {
                        \Log::error("Stock inconsistency detected (after validation) for Varian ID: {$idVarian}, Cabang ID: {$idCabang}. Available: {$stokSebelum}, Required: {$jumlahKeluar}");
                        throw new \Exception("Inkonsistensi stok terdeteksi (setelah validasi) untuk varian ID {$idVarian}. Transaksi dibatalkan.");
                    }

                    // Kurangi stok
                    $stok->decrement('stok_saat_ini', $jumlahKeluar);
                    $stokSaatIni = $stok->stok_saat_ini; // Ambil stok terbaru setelah decrement

                    // --- Logika Cek Notifikasi ---
                    if ($stokMinimum > 0) { // Hanya cek jika stok minimum diset (> 0)
                        if (($stokSebelum > $stokMinimum) && ($stokSaatIni <= $stokMinimum)) {
                            // Kondisi terpenuhi, tambahkan ke list notifikasi
                            // Kita perlu refresh model untuk mendapatkan data relasi terbaru jika diperlukan
                            $stokUntukNotifikasi[] = $stok->fresh(['varianProduk.produk', 'cabang']);
                        }
                    }
                    // --- Akhir Logika Cek Notifikasi ---
                }
            });

            // --- Kirim Notifikasi (Dilakukan SETELAH transaksi DB sukses) ---
            if (!empty($stokUntukNotifikasi)) {
                $admins = User::where('role', 'admin')->get(); // Ambil semua admin
                if ($admins->isNotEmpty()) {
                    foreach ($stokUntukNotifikasi as $stokNotif) {
                        // Kirim notifikasi ke semua admin untuk setiap item yang mencapai minimum
                        NotificationFacade::send($admins, new StokMinimumNotification($stokNotif));
                        \Log::info("Stok minimum notification sent for Varian ID: {$stokNotif->id_varian_produk}, Cabang ID: {$stokNotif->id_cabang}");
                    }
                } else {
                    \Log::warning("No admin users found to send stock minimum notifications.");
                }
            }
            // --- Akhir Kirim Notifikasi ---


            // Kirim notifikasi sukses transaksi
            Notification::make()
                ->title('Barang Keluar berhasil ditambahkan')
                ->body('Stok telah berhasil diperbarui.') // Tambahkan detail
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Memperbarui Stok atau Mengirim Notifikasi')
                ->body('Gagal memperbarui stok atau mengirim notifikasi: ' . $e->getMessage() . '. Data transaksi mungkin sudah tersimpan, harap periksa manual.')
                ->danger()
                ->persistent()
                ->sendToDatabase(Auth::user());

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // Kita handle notifikasi sukses di afterCreate
    }
}
