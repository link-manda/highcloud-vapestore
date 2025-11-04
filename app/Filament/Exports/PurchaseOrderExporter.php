<?php

namespace App\Filament\Exports;

use App\Models\PurchaseOrder;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class PurchaseOrderExporter extends Exporter
{
    protected static ?string $model = PurchaseOrder::class;

    public static function getColumns(): array
    {
        // Kolom ini mereplikasi kolom yang ada di LaporanPurchaseOrder.php
        return [
            ExportColumn::make('nomor_po')
                ->label('Nomor PO'),
            ExportColumn::make('tanggal_po')
                ->label('Tanggal PO')
                ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y')),
            ExportColumn::make('supplier.nama_supplier')
                ->label('Supplier'),
            ExportColumn::make('cabangTujuan.nama_cabang')
                ->label('Cabang Tujuan'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn(string $state) => ucfirst($state)), // Format agar rapi, misal: 'Submitted'

            // Kolom Kalkulasi: Replikasi logika dari Laporan
            ExportColumn::make('progres')
                ->label('Progres Diterima')
                ->formatStateUsing(function (PurchaseOrder $record) {
                    $totalDipesan = $record->details->sum('jumlah_pesan');
                    $totalDiterima = $record->details->sum('jumlah_diterima');
                    return "{$totalDiterima} / {$totalDipesan} item";
                }),

            ExportColumn::make('total_harga')
                ->label('Total Nilai (IDR)'),
        ];
    }

    /**
     * Pesan notifikasi kustom
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor laporan purchase order Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris telah diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }

    /**
     * Override metode notifikasi untuk mengirim ke 'mail' (Mailtrap) dan 'database' (Lonceng)
     */
    public static function getCompletedNotification(Export $export): Notification
    {
        $notification = parent::getCompletedNotification($export);
        $user = auth()->user();

        // Kirim ke KEDUA channel
        $notification
            ->sendToDatabase($user) // Untuk ikon lonceng (bell)
            ->sendToMail($user);     // Untuk email (Mailtrap)

        return $notification;
    }
}
