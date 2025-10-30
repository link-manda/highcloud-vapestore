<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use App\Models\BarangMasuk;
use App\Models\StokCabang;
use App\Models\User;
use App\Models\PurchaseOrder; // <= IMPORT BARU
use App\Models\PurchaseOrderDetail; // <= IMPORT BARU
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
        // Arahkan ke halaman view setelah create
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('--- Starting mutateFormDataBeforeCreate for BarangMasuk ---');
        $user = Auth::user();
        $data['id_user'] = $user->id;

        // Set Cabang Tujuan (jika user adalah staf)
        if ($user->role === 'staf' && $user->id_cabang) {
            $data['id_cabang_tujuan'] = $user->id_cabang;
            Log::info('[mutateBM] Staff role detected. Cabang Tujuan ID set: ' . $data['id_cabang_tujuan']);
        }

        // Generate Nomor Transaksi Unik
        try {
            $today = Carbon::today();
            $idCabang = $data['id_cabang_tujuan'] ?? 'NULL'; // Ambil cabang tujuan

            $lastTransactionToday = BarangMasuk::where('id_cabang_tujuan', $idCabang)
                ->whereDate('created_at', $today)
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

        // Validasi jika menerima dari PO
        if (!empty($data['id_purchase_order'])) {
            Log::info('[mutateBM] PO ID provided: ' . $data['id_purchase_order']);
            $po = PurchaseOrder::with('details')->find($data['id_purchase_order']);
            if (!$po) {
                throw new \Exception("Purchase Order tidak ditemukan.");
            }

            foreach ($data['details'] as $itemDiterima) {
                $poDetail = $po->details->firstWhere('id_varian_produk', $itemDiterima['id_varian_produk']);
                $jumlahDiterima = (int)$itemDiterima['jumlah'];
                $sisaQty = $poDetail->jumlah_pesan - $poDetail->jumlah_diterima;

                // Validasi final jumlah diterima vs sisa PO
                if ($jumlahDiterima > $sisaQty) {
                    Log::warning('[mutateBM] Validation Failed: Jumlah diterima > Sisa PO.');
                    FilamentNotification::make()
                        ->title('Validasi Gagal')
                        ->body("Jumlah diterima untuk item '{$poDetail->varianProduk->nama_varian}' ({$jumlahDiterima}) melebihi sisa yang dipesan di PO ({$sisaQty}).")
                        ->danger()
                        ->send();
                    // Hentikan proses
                    $this->halt();
                }
            }
        }


        Log::info('--- Finished mutateFormDataBeforeCreate ---');
        return $data;
    }

    protected function afterCreate(): void
    {
        Log::info('--- Starting afterCreate for BarangMasuk ID: ' . $this->record->id . ' ---');
        $barangMasuk = $this->record;
        $idCabangTujuan = $barangMasuk->id_cabang_tujuan;

        // Gunakan DB Transaction untuk memastikan integritas data (Stok + PO Update)
        DB::beginTransaction();
        try {

            // 1. LOGIKA UPDATE STOK (INCREMENT)
            Log::info('[afterCreate-BM] Starting Stok Increment...');
            foreach ($barangMasuk->details as $detail) {
                Log::info("[afterCreate-BM] Processing Stok Varian ID: {$detail->id_varian_produk}, Jumlah: {$detail->jumlah}, Cabang: {$idCabangTujuan}");

                // Cari atau buat record stok
                $stok = StokCabang::firstOrCreate(
                    [
                        'id_cabang' => $idCabangTujuan,
                        'id_varian_produk' => $detail->id_varian_produk
                    ],
                    [
                        'stok_saat_ini' => 0, // Inisialisasi jika baru
                        'stok_minimum' => 0  // Default minimum
                    ]
                );

                // Tambah stok
                $stok->increment('stok_saat_ini', $detail->jumlah);
                Log::info("[afterCreate-BM] Stok Varian ID: {$detail->id_varian_produk} incremented. New stok: {$stok->stok_saat_ini}");
            }
            Log::info('[afterCreate-BM] Stok Increment finished.');


            // 2. LOGIKA UPDATE PURCHASE ORDER (PO)
            if ($barangMasuk->id_purchase_order) {
                Log::info('[afterCreate-BM] PO ID detected: ' . $barangMasuk->id_purchase_order . '. Starting PO Update...');

                $po = PurchaseOrder::with('details')->find($barangMasuk->id_purchase_order);

                if ($po) {
                    $totalDiterimaPo = 0;
                    $totalDipesanPo = $po->details->sum('jumlah_pesan');

                    // Loop melalui item yang baru diterima di BarangMasuk
                    foreach ($barangMasuk->details as $itemDiterima) {
                        // Cari detail PO yang sesuai
                        $poDetail = $po->details->firstWhere('id_varian_produk', $itemDiterima->id_varian_produk);

                        if ($poDetail) {
                            // Update jumlah_diterima di PO Detail
                            $poDetail->increment('jumlah_diterima', $itemDiterima->jumlah);
                            Log::info("[afterCreate-BM] PO Detail Varian ID: {$poDetail->id_varian_produk} incremented by {$itemDiterima->jumlah}. New diterima: {$poDetail->jumlah_diterima}");
                        }
                    }

                    // Muat ulang (refresh) data PO detail untuk mendapatkan total terbaru
                    $po->refresh();
                    $totalDiterimaPo = $po->details->sum('jumlah_diterima');
                    Log::info("[afterCreate-BM] PO Total Dipesan: {$totalDipesanPo}, PO Total Diterima (Updated): {$totalDiterimaPo}");

                    // Update Status PO Induk
                    if ($totalDiterimaPo >= $totalDipesanPo) {
                        $po->status = 'Completed';
                        Log::info("[afterCreate-BM] PO Status changed to: Completed");
                    } else if ($totalDiterimaPo > 0) {
                        $po->status = 'Partially Received';
                        Log::info("[afterCreate-BM] PO Status changed to: Partially Received");
                    }
                    // (Jika 0, status tetap 'Submitted')

                    $po->save(); // Simpan perubahan status PO
                } else {
                    Log::warning('[afterCreate-BM] PO not found during update logic.');
                }
            }

            // Jika semua sukses
            DB::commit();
            Log::info('[afterCreate-BM] DB Transaction successful.');
        } catch (\Exception $e) {
            // Jika ada error
            DB::rollBack();
            Log::error('[afterCreate-BM] ERROR during transaction: ' . $e->getMessage());
            // Tampilkan notifikasi error ke user
            FilamentNotification::make()
                ->title('Gagal Memperbarui Stok atau PO')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

            // Hapus record BarangMasuk yg baru dibuat agar tidak gantung (Opsional, tapi disarankan)
            // $barangMasuk->delete(); 
        }

        Log::info('--- Finished afterCreate for BarangMasuk ID: ' . $this->record->id . ' ---');
    }
}
