<?php

namespace App\Filament\Exports;

use App\Models\StockOpnameDetail;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class StockOpnameExporter extends Exporter
{
    protected static ?string $model = StockOpnameDetail::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('stockOpname.cabang.nama_cabang')
                ->label('Cabang'),
            ExportColumn::make('stockOpname.tanggal_opname')
                ->label('Tanggal Opname')
                ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
            ExportColumn::make('varianProduk.produk.nama_produk')
                ->label('Produk'),
            ExportColumn::make('varianProduk.nama_varian')
                ->label('Varian'),
            ExportColumn::make('stok_sistem')
                ->label('Stok Sistem'),
            ExportColumn::make('stok_fisik')
                ->label('Stok Fisik'),
            ExportColumn::make('selisih')
                ->label('Selisih'),
            ExportColumn::make('catatan')
                ->label('Keterangan'),
            ExportColumn::make('created_at')
                ->label('Dibuat')
                ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y H:i')),
        ];
    }

    /**
     * Pesan notifikasi kustom
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor laporan stok opname Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris telah diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }

    /**
     * Override metode notifikasi untuk mengirim ke database dan email
     */
    public static function getCompletedNotification(Export $export): Notification
    {
        $notification = parent::getCompletedNotification($export);
        $user = auth()->user();

        // Kirim ke database (lonceng) dan email
        $notification
            ->sendToDatabase($user)
            ->sendToMail($user);

        return $notification;
    }
}