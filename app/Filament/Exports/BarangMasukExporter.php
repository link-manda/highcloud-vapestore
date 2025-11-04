<?php

namespace App\Filament\Exports;

use App\Models\BarangMasuk;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification; // Impor Notifikasi
use Illuminate\Support\Carbon; // Impor Carbon

class BarangMasukExporter extends Exporter
{
    protected static ?string $model = BarangMasuk::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nomor_transaksi')
                ->label('Nomor Transaksi'),
            ExportColumn::make('tanggal_masuk')
                ->label('Tanggal Masuk')
                ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y H:i')), // Format tanggal
            ExportColumn::make('supplier.nama_supplier')
                ->label('Supplier'),
            ExportColumn::make('cabangTujuan.nama_cabang')
                ->label('Cabang Tujuan'),
            ExportColumn::make('user.name') // Tambahkan kolom User Pencatat
                ->label('Dicatat Oleh'),
            ExportColumn::make('purchaseOrder.nomor_po') // Tambahkan kolom PO
                ->label('Nomor PO'),

            // Kolom Kalkulasi: Sama seperti di tabel laporan
            ExportColumn::make('total_nilai')
                ->label('Total Nilai (IDR)')
                ->formatStateUsing(function (BarangMasuk $record) {
                    // Replikasi kalkulasi dari Laporan: hitung jumlah subtotal dari relasi details
                    return $record->details->sum('subtotal');
                }),
        ];
    }

    /**
     * Pesan notifikasi kustom
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor laporan barang masuk Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris telah diekspor.';

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
