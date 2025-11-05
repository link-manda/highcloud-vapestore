<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\BarangKeluarDetail;
use Illuminate\Support\Facades\DB;

class KategoriTerlarisChart extends ChartWidget
{
    protected static ?string $heading = 'Kategori Terlaris (7 Hari Terakhir)';

    // Setengah lebar
    protected int | string | array $columnSpan = 'md:col-span-1';

    // Muncul setelah (di kanan) grafik Penjualan
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = BarangKeluarDetail::query()
            // Gabung ke tabel barang_keluars (untuk tanggal)
            ->join('barang_keluars', 'barang_keluars.id', '=', 'barang_keluar_details.id_barang_keluar')
            // Gabung ke varian_produks (untuk id_produk)
            ->join('varian_produks', 'varian_produks.id', '=', 'barang_keluar_details.id_varian_produk')
            // Gabung ke produks (untuk id_kategori)
            ->join('produks', 'produks.id', '=', 'varian_produks.id_produk')
            // Gabung ke kategoris (untuk nama_kategori)
            ->join('kategoris', 'kategoris.id', '=', 'produks.id_kategori')
            // Filter 7 hari terakhir
            ->where('barang_keluars.tanggal_keluar', '>=', now()->subDays(7))
            // Group berdasarkan nama kategori
            ->groupBy('kategoris.nama_kategori')
            // Hitung jumlah item (QTY) yang terjual, BUKAN total rupiah
            ->select('kategoris.nama_kategori', DB::raw('SUM(barang_keluar_details.jumlah) as total_qty'))
            ->orderBy('total_qty', 'desc')

            // [PERBAIKAN 1]: Batasi hanya Top 5 agar lebih rapi
            ->limit(5)

            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Qty Terjual',
                    'data' => $data->map(fn($value) => $value->total_qty),

                    // [PERBAIKAN 2]: Tambahkan warna yang konsisten untuk Bar chart
                    'backgroundColor' => 'rgba(54, 162, 235, 0.7)', // Biru
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                ],
            ],
            'labels' => $data->map(fn($value) => $value->nama_kategori),
        ];
    }

    protected function getType(): string
    {
        // [PERBAIKAN 3]: Ubah tipe chart dari 'pie' menjadi 'bar'
        return 'bar';
    }
}
