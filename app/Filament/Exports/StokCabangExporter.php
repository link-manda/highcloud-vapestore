<?php

namespace App\Filament\Exports;

use App\Models\StokCabang;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification;

class StokCabangExporter extends Exporter
{
    protected static ?string $model = StokCabang::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('cabang.nama_cabang')
                ->label('Cabang'),

            ExportColumn::make('varianProduk.produk.kategori.nama_kategori')
                ->label('Kategori')
                ->formatStateUsing(fn($record) => $record->varianProduk?->produk?->kategori?->nama_kategori ?? '-'),

            ExportColumn::make('varianProduk.produk.nama_produk')
                ->label('Produk')
                ->formatStateUsing(fn($record) => $record->varianProduk?->produk?->nama_produk ?? '-'),

            ExportColumn::make('varianProduk.nama_varian')
                ->label('Varian')
                ->formatStateUsing(fn($record) => $record->varianProduk?->nama_varian ?? '-'),

            ExportColumn::make('varianProduk.sku_code')
                ->label('SKU')
                ->formatStateUsing(fn($record) => $record->varianProduk?->sku_code ?? '-'),

            ExportColumn::make('stok_saat_ini')
                ->label('Stok Saat Ini'),

            ExportColumn::make('stok_minimum')
                ->label('Stok Minimum'),

            ExportColumn::make('varianProduk.harga_beli')
                ->label('Harga Beli (Satuan)')
                ->formatStateUsing(fn($record) => $record->varianProduk?->harga_beli ?? 0),

            ExportColumn::make('total_nilai_aset')
                ->label('Total Nilai Aset')
                ->formatStateUsing(function (StokCabang $record) {
                    $stok = $record->stok_saat_ini ?? 0;
                    $harga_beli = $record->varianProduk?->harga_beli ?? 0;
                    return (float) $stok * (float) $harga_beli;
                }),
        ];
    }

    /**
     * Pesan notifikasi kustom (TETAP SAMA)
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor laporan sisa stok Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris telah diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }

    /**
     * [PERBAIKAN 2]: Override metode getCompletedNotification
     *
     * Metode ini memberi tahu Filament BAGAIMANA cara mengirim notifikasi.
     * Kita akan menambahkan channel 'mail' di sini.
     */
    public static function getCompletedNotification(Export $export): Notification
    {
        // Ambil notifikasi default (yang sudah berisi tombol download)
        $notification = parent::getCompletedNotification($export);

        // Kirim ke pengguna yang sedang login
        $user = auth()->user();

        // Perintahkan notifikasi untuk dikirim ke KEDUA channel
        $notification
            ->sendToDatabase($user) // Untuk ikon lonceng (bell)
            ->sendToMail($user);     // Untuk email (Mailtrap)

        return $notification;
    }
}
