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
    protected static ?int $sort = 2;

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
            ->whereColumn('stok_saat_ini', '<=', 'stok_minimum')
            ->where('stok_minimum', '>', 0)
            ->count();

        // 4. KPI: PO Terbuka
        $jumlahPOTerbuka = PurchaseOrder::query()
            ->whereIn('status', ['Submitted', 'Partially Received'])
            ->count();


        return [
            Stat::make('Pendapatan Hari Ini', Number::currency($totalPenjualanHariIni, 'IDR'))
                ->description($jumlahTransaksiHariIni . ' transaksi berhasil diproses')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Item Stok Kritis', $jumlahStokKritis . ' Item')
                ->description('Segera lakukan restock cabang')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($jumlahStokKritis > 0 ? 'danger' : 'gray')
                ->chart([10, 4, 15, 3, 10, 2, 7]),

            Stat::make('PO Menunggu', $jumlahPOTerbuka . ' Pesanan')
                ->description('Purchase order status aktif')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
        ];
    }
}
