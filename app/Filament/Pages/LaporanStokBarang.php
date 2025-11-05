<?php

namespace App\Filament\Pages;

use App\Filament\Exports\StokCabangExporter;
use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\StokCabang;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Filament\Tables;

class LaporanStokBarang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationLabel = 'Laporan Sisa Stok';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Sisa Stok Barang';

    protected static string $view = 'filament.pages.laporan-stok-barang';

    /**
     * Logika otorisasi: Hanya Admin yang bisa melihat halaman ini.
     */
    public function mount(): void
    {
        // Tolak akses jika bukan Admin
        abort_unless(auth()->user()->hasRole('Admin'), 403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    /**
     * Menyembunyikan halaman dari navigasi jika bukan Admin.
     */
    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }


    /**
     * Mendefinisikan struktur tabel untuk Laporan Sisa Stok.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Query dasar: Ambil StokCabang beserta relasi yang diperlukan
                StokCabang::query()
                    ->with(['cabang', 'varianProduk.produk.kategori'])
            )
            ->striped()
            ->columns([
                TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('varianProduk.produk.kategori.nama_kategori')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('varianProduk.produk.nama_produk')
                    ->label('Produk')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('varianProduk.nama_varian')
                    ->label('Varian')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('varianProduk.sku_code')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('stok_saat_ini')
                    ->label('Stok Saat Ini')
                    ->numeric()
                    ->badge()
                    ->sortable(),
                TextColumn::make('stok_minimum')
                    ->label('Stok Minimum')
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('varianProduk.harga_beli')
                    ->label('Harga Beli (Satuan)')
                    ->money('IDR')
                    ->sortable(),
                // Kolom kustom untuk menghitung Total Nilai Aset
                TextColumn::make('total_nilai_aset')
                    ->label('Total Nilai Aset')
                    ->money('IDR')
                    ->state(function (StokCabang $record): float {
                        // Kalkulasi: stok * harga_beli
                        $stok = $record->stok_saat_ini ?? 0;
                        $harga_beli = $record->varianProduk?->harga_beli ?? 0;
                        return (float) $stok * (float) $harga_beli;
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('id_cabang')
                    ->label('Cabang')
                    ->options(Cabang::pluck('nama_cabang', 'id'))
                    ->searchable(),
                SelectFilter::make('id_kategori')
                    ->label('Kategori')
                    ->relationship('varianProduk.produk.kategori', 'nama_kategori')
                    ->searchable(),
                SelectFilter::make('id_produk')
                    ->label('Produk')
                    ->relationship('varianProduk.produk', 'nama_produk')
                    ->searchable(),
            ])
            ->actions([
                // Tidak ada aksi per baris
            ])
            ->bulkActions([
                // [PERBAIKAN 2]: Tentukan Exporter kustom di sini
                Tables\Actions\ExportBulkAction::make()
                    ->exporter(StokCabangExporter::class),
            ]);
    }
}
