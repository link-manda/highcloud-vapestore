<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\BarangKeluarDetail; // [PERBAIKAN] Gunakan model Detail
use Illuminate\Support\Facades\DB;

class PenjualanTujuhHariChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Penjualan (7 Hari Terakhir)';

    // [PERBAIKAN UKURAN] Ubah ke setengah lebar
    protected int | string | array $columnSpan = 'md:col-span-1';

    // [PERBAIKAN SORTING] Atur urutan agar muncul di kiri
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // [PERBAIKAN QUERY]
        // Ambil data dari detail, gabung ke induk untuk tanggal
        $data = BarangKeluarDetail::query()
            ->join('barang_keluars', 'barang_keluars.id', '=', 'barang_keluar_details.id_barang_keluar')
            ->where('barang_keluars.tanggal_keluar', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(barang_keluars.tanggal_keluar) as tanggal'),
                DB::raw('SUM(barang_keluar_details.subtotal) as total') // Ambil subtotal dari detail
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $data->map(fn($value) => $value->total),
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1,
                ],
            ],
            'labels' => $data->map(fn($value) => \Carbon\Carbon::parse($value->tanggal)->format('d M')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
