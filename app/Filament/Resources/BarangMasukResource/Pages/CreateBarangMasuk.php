<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use App\Models\StokCabang;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // Pastikan Auth di-import

class CreateBarangMasuk extends CreateRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user(); // Ambil data user yang login

        // 1. Generate Nomor Transaksi Unik (Kode sebelumnya sudah benar)
        $prefix = 'BM-';
        $today = now()->format('Ymd');
        $latestToday = DB::table('barang_masuks')
            ->where('nomor_transaksi', 'like', $prefix . $today . '-%')
            ->orderBy('nomor_transaksi', 'desc')
            ->first();

        $nextId = 1;
        if ($latestToday) {
            $lastId = (int) substr($latestToday->nomor_transaksi, -4);
            $nextId = $lastId + 1;
        }
        $data['nomor_transaksi'] = $prefix . $today . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // 2. Set User Pencatat (Kode sebelumnya sudah benar)
        $data['id_user'] = $user->id;

        // --- PERBAIKAN UNTUK STAF ---
        // 3. Jika user adalah Staf, pastikan id_cabang_tujuan diisi dari data Staf
        if ($user->role === 'staf') {
            $data['id_cabang_tujuan'] = $user->id_cabang;
        }
        // --- AKHIR PERBAIKAN ---

        // 4. Pastikan hanya salah satu sumber yang terisi (Kode sebelumnya sudah benar)
        if (!empty($data['id_supplier']) && !empty($data['id_cabang_sumber'])) {
            // Prioritaskan Supplier jika keduanya terisi, atau sesuaikan logikanya
            $data['id_cabang_sumber'] = null; // Contoh: Kosongkan cabang sumber jika supplier dipilih
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $barangMasuk = $this->record;
        $barangMasuk->load('details.varianProduk');

        DB::transaction(function () use ($barangMasuk) {
            $idCabangTujuan = $barangMasuk->id_cabang_tujuan;

            // --- Pastikan $idCabangTujuan ada sebelum loop ---
            if (!$idCabangTujuan) {
                // Berikan error atau log jika cabang tujuan tidak ada (seharusnya tidak terjadi setelah perbaikan di mutate)
                // throw new \Exception("Cabang tujuan tidak ditemukan untuk Barang Masuk ID: {$barangMasuk->id}");
                \Log::error("Cabang tujuan tidak ditemukan untuk Barang Masuk ID: {$barangMasuk->id}");
                return; // Hentikan proses jika cabang tujuan tidak ada
            }
            // --- Akhir pengecekan ---


            foreach ($barangMasuk->details as $item) {
                $idVarian = $item->id_varian_produk;
                $jumlahMasuk = $item->jumlah;

                if (!$idVarian || !$jumlahMasuk || $jumlahMasuk <= 0) { // Tambahkan cek jumlah > 0
                    continue;
                }

                $stok = StokCabang::firstOrCreate(
                    [
                        'id_cabang' => $idCabangTujuan,
                        'id_varian_produk' => $idVarian,
                    ],
                    [
                        'stok_saat_ini' => 0,
                        'stok_minimum' => 0,
                    ]
                );

                $stok->increment('stok_saat_ini', $jumlahMasuk);
            }
        });
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Barang Masuk berhasil ditambahkan';
    }
}
