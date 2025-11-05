<?php

namespace App\Filament\Widgets;

use App\Models\BarangKeluar;
use App\Models\BarangKeluarDetail;
use App\Models\PurchaseOrder;
use App\Models\StokCabang;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class StatsOverview extends BaseWidget
{
    // Mengatur agar widget ini hanya bisa dilihat oleh Admin
    // (Staf akan melihat dashboard kosong, yang tidak masalah)
    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    protected function getStats(): array
    {
        // 1. KPI: Total Penjualan (Hari Ini)
        $totalPenjualanHariIni = BarangKeluarDetail::query()
            ->whereHas('barangKeluar', function ($query) {
                $query->whereDate('tanggal_keluar', Carbon::today());
            })
            ->sum('subtotal');

        // 2. KPI: Transaksi (Hari Ini)
        $jumlahTransaksiHariIni = BarangKeluar::query()
            ->whereDate('tanggal_keluar', Carbon::today())
            ->count();

        // 3. KPI: Stok Kritis
        $jumlahStokKritis = StokCabang::query()
            // Stok saat ini <= stok minimum
            ->whereColumn('stok_saat_ini', '<=', 'stok_minimum')
            // Dan stok minimum tidak 0 (agar 0/0 tidak dihitung)
            ->where('stok_minimum', '>', 0)
            ->count();

        // 4. KPI: PO Terbuka
        $jumlahPOTerbuka = PurchaseOrder::query()
            ->whereIn('status', ['Submitted', 'Partially Received'])
            ->count();


        // Kembalikan data dalam bentuk Stat Cards
        return [
            Stat::make('Total Penjualan (Hari Ini)', Number::currency($totalPenjualanHariIni, 'IDR'))
                ->description('Total pendapatan dari semua cabang hari ini')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Placeholder chart

            Stat::make('Transaksi (Hari Ini)', $jumlahTransaksiHariIni . ' Transaksi')
                ->description('Jumlah nota penjualan hari ini')
                ->color('success')
                ->chart([17, 16, 14, 15, 10, 13, 12]), // Placeholder chart

            Stat::make('Item Stok Kritis', $jumlahStokKritis . ' Item')
                ->description('Item yang stoknya di bawah batas minimum')
                ->color($jumlahStokKritis > 0 ? 'danger' : 'gray') // Warna jadi merah jika ada
                ->chart([10, 4, 15, 3, 10, 2, 7]), // Placeholder chart

            Stat::make('Purchase Order Terbuka', $jumlahPOTerbuka . ' PO')
                ->description('PO yang masih menunggu barang dari supplier')
                ->color('info')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Placeholder chart
        ];
    }
}
