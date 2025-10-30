<?php

namespace App\Filament\Resources\BarangKeluarResource\Pages;

use App\Filament\Resources\BarangKeluarResource;
use App\Models\StokCabang;
use App\Models\User;
use App\Notifications\StokMinimumNotification;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // <-- Tambahkan use Log
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class CreateBarangKeluar extends CreateRecord
{
    protected static string $resource = BarangKeluarResource::class;

    // --- mutateFormDataBeforeCreate (Tidak ada perubahan, sudah benar) ---
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
            if (!isset($data['id_cabang'])) {
                Log::warning("id_cabang missing for staff user {$user->id} during BarangKeluar creation.");
                throw ValidationException::withMessages(['id_cabang' => 'Cabang tidak terdeteksi untuk pengguna staf.']);
            }
        } elseif (!isset($data['id_cabang'])) {
            throw ValidationException::withMessages(['id_cabang' => 'Cabang asal barang wajib dipilih.']);
        }

        return $data;
    }

    // --- beforeCreate (Tidak ada perubahan, sudah benar) ---
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

    // --- afterCreate (Perbaikan dan Logging Ditambahkan) ---
    protected function afterCreate(): void
    {
        $barangKeluar = $this->record;
        $barangKeluar->load('details');
        $stokUntukNotifikasi = [];

        Log::info("--- Starting afterCreate for BarangKeluar ID: {$barangKeluar->id} ---"); // LOG AWAL

        try {
            DB::transaction(function () use ($barangKeluar, &$stokUntukNotifikasi) {
                $idCabang = $barangKeluar->id_cabang;

                if (!$idCabang) {
                    Log::error("[afterCreate] Cabang tidak ditemukan untuk Barang Keluar ID: {$barangKeluar->id}");
                    throw new \Exception("Cabang tidak valid.");
                }

                foreach ($barangKeluar->details as $item) {
                    $idVarian = $item->id_varian_produk;
                    $jumlahKeluar = $item->jumlah;

                    if (!$idVarian || $jumlahKeluar <= 0) {
                        Log::warning("[afterCreate] Skipping invalid item in BarangKeluarDetail ID: {$item->id}");
                        continue;
                    }

                    Log::info("[afterCreate] Processing Varian ID: {$idVarian}, Jumlah Keluar: {$jumlahKeluar}, Cabang ID: {$idCabang}"); // LOG ITEM

                    // Kunci lagi untuk keamanan saat decrement dan check (meskipun kecil kemungkinannya setelah beforeCreate)
                    $stok = StokCabang::where('id_cabang', $idCabang)
                        ->where('id_varian_produk', $idVarian)
                        ->lockForUpdate() // Kunci lagi saat transaksi berjalan
                        ->first();

                    if (!$stok) {
                        Log::error("[afterCreate] Stok record not found for Varian ID: {$idVarian}, Cabang ID: {$idCabang}. Cannot decrement.");
                        throw new \Exception("Record stok tidak ditemukan untuk varian ID {$idVarian}.");
                    }

                    $stokSebelum = $stok->stok_saat_ini;
                    $stokMinimum = $stok->stok_minimum;

                    Log::info("[afterCreate] Stok Sebelum: {$stokSebelum}, Stok Minimum: {$stokMinimum}"); // LOG STOK SEBELUM

                    if ($stokSebelum < $jumlahKeluar) {
                        Log::error("[afterCreate] Stock inconsistency (after validation) for Varian ID: {$idVarian}, Cabang ID: {$idCabang}. Available: {$stokSebelum}, Required: {$jumlahKeluar}");
                        throw new \Exception("Inkonsistensi stok terdeteksi (setelah validasi) untuk varian ID {$idVarian}. Transaksi dibatalkan.");
                    }

                    // Kurangi stok
                    $stok->decrement('stok_saat_ini', $jumlahKeluar);

                    // PENTING: Ambil nilai stok terbaru SETELAH decrement dari database
                    $stokSaatIni = DB::table('stok_cabangs')
                        ->where('id', $stok->id)
                        ->value('stok_saat_ini'); // Ambil langsung dari DB

                    Log::info("[afterCreate] Stok Setelah Decrement: {$stokSaatIni}"); // LOG STOK SETELAH

                    // --- Logika Cek Notifikasi ---
                    if ($stokMinimum > 0) {
                        Log::info("[afterCreate] Checking notification condition: (stokSebelum > stokMinimum) && (stokSaatIni <= stokMinimum) -> ({$stokSebelum} > {$stokMinimum}) && ({$stokSaatIni} <= {$stokMinimum})"); // LOG KONDISI
                        if (($stokSebelum > $stokMinimum) && ($stokSaatIni <= $stokMinimum)) {
                            Log::info("[afterCreate] !!! NOTIFICATION CONDITION MET for Varian ID: {$idVarian} !!!"); // LOG KONDISI TERPENUHI
                            // Reload relasi penting SEBELUM dikirim
                            $stok->load(['varianProduk.produk', 'cabang']);
                            $stokUntukNotifikasi[] = $stok; // Tambahkan instance model yang sudah di-load
                        }
                    } else {
                        Log::info("[afterCreate] Stok Minimum is 0, skipping notification check."); // LOG SKIP KARENA MIN 0
                    }
                    // --- Akhir Logika Cek Notifikasi ---
                }
            }); // Akhir DB::transaction

            Log::info("[afterCreate] DB Transaction successful."); // LOG TRANSAKSI SUKSES

            // --- Kirim Notifikasi (Dilakukan SETELAH transaksi DB sukses) ---
            if (!empty($stokUntukNotifikasi)) {
                Log::info("[afterCreate] Found " . count($stokUntukNotifikasi) . " items needing notification."); // LOG JML NOTIF
                $admins = User::where('role', 'admin')->get();
                if ($admins->isNotEmpty()) {
                    Log::info("[afterCreate] Found " . $admins->count() . " admin users to notify."); // LOG JML ADMIN
                    foreach ($stokUntukNotifikasi as $stokNotif) {
                        // Pastikan relasi sudah di-load
                        if (!$stokNotif->relationLoaded('varianProduk') || !$stokNotif->varianProduk->relationLoaded('produk') || !$stokNotif->relationLoaded('cabang')) {
                            Log::warning("[afterCreate] Relations not loaded for notification StokCabang ID: {$stokNotif->id}. Reloading...");
                            $stokNotif->load(['varianProduk.produk', 'cabang']);
                        }
                        Log::info("[afterCreate] Sending notification for StokCabang ID: {$stokNotif->id}"); // LOG KIRIM NOTIF
                        NotificationFacade::send($admins, new StokMinimumNotification($stokNotif));
                    }
                } else {
                    Log::warning("[afterCreate] No admin users found to send stock minimum notifications.");
                }
            } else {
                Log::info("[afterCreate] No items reached minimum stock threshold."); // LOG TIDAK ADA NOTIF
            }
            // --- Akhir Kirim Notifikasi ---


            Notification::make()
                ->title('Barang Keluar berhasil ditambahkan')
                ->body('Stok telah berhasil diperbarui.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error("[afterCreate] Exception caught: " . $e->getMessage(), ['exception' => $e]); // LOG ERROR EXCEPTION
            Notification::make()
                ->title('Error Memperbarui Stok atau Mengirim Notifikasi')
                ->body('Gagal memperbarui stok atau mengirim notifikasi: ' . $e->getMessage() . '. Data transaksi mungkin sudah tersimpan, harap periksa manual.')
                ->danger()
                ->persistent()
                ->sendToDatabase(Auth::user());

            // Jangan redirect di sini agar user tahu ada error, Filament akan handle error Exception
            // $this->redirect($this->getResource()::getUrl('index'));
            // Lemparkan kembali error agar Filament bisa menanganinya
            throw $e;
        }
        Log::info("--- Finished afterCreate for BarangKeluar ID: {$barangKeluar->id} ---"); // LOG AKHIR
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
