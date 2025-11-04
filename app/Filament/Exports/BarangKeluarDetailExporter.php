<?php

namespace App\Filament\Exports;

use App\Models\BarangKeluarDetail;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification; 
use Illuminate\Support\Carbon; 

class BarangKeluarDetailExporter extends Exporter
{
    protected static ?string $model = BarangKeluarDetail::class;

    public static function getColumns(): array
    {
        // Kolom ini mereplikasi kolom yang ada di LaporanBarangKeluar.php
        return [
            ExportColumn::make('barangKeluar.tanggal_keluar')
                ->label('Tanggal Keluar')
                ->formatStateUsing(fn($record) => Carbon::parse($record->barangKeluar?->tanggal_keluar)->format('d/m/Y H:i')),

            ExportColumn::make('barangKeluar.cabang.nama_cabang')
                ->label('Cabang')
                ->formatStateUsing(fn($record) => $record->barangKeluar?->cabang?->nama_cabang ?? '-'),

            ExportColumn::make('varianProduk.produk.kategori.nama_kategori')
                ->label('Kategori')
                ->formatStateUsing(fn($record) => $record->varianProduk?->produk?->kategori?->nama_kategori ?? '-'),

            ExportColumn::make('varianProduk.produk.nama_produk')
                ->label('Produk')
                ->formatStateUsing(fn($record) => $record->varianProduk?->produk?->nama_produk ?? '-'),

            ExportColumn::make('varianProduk.nama_varian')
                ->label('Varian')
                ->formatStateUsing(fn($record) => $record->varianProduk?->nama_varian ?? '-'),

            ExportColumn::make('jumlah')
                ->label('Jumlah Terjual'),

            ExportColumn::make('harga_jual_saat_transaksi')
                ->label('Harga Jual (Satuan)'),

            ExportColumn::make('subtotal')
                ->label('Subtotal (IDR)'),
        ];
    }

    /**
     * Pesan notifikasi kustom
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor laporan barang keluar Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris telah diekspor.';

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
