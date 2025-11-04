<?php

namespace App\Filament\Pages;

use App\Filament\Exports\BarangKeluarDetailExporter;
use App\Models\BarangKeluarDetail;
use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\VarianProduk;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables; 
use Illuminate\Contracts\View\View; 
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

class LaporanBarangKeluar extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?string $navigationLabel = 'Laporan Barang Keluar';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Barang Keluar (Penjualan)';

    protected static string $view = 'filament.pages.laporan-barang-keluar';

    /**
     * Otorisasi: Hanya Admin
     */
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    /**
     * Definisi Tabel
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Query dasar (tanpa join manual)
                BarangKeluarDetail::query()
                    ->with([
                        'barangKeluar.cabang',
                        'varianProduk.produk.kategori'
                    ])
            )
            ->striped()
            ->columns([
                TextColumn::make('barangKeluar.tanggal_keluar')
                    ->label('Tanggal Keluar')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('barangKeluar.cabang.nama_cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('varianProduk.produk.kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('varianProduk.produk.nama_produk')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('varianProduk.nama_varian')
                    ->label('Varian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jumlah')
                    ->label('Jml Terjual')
                    ->alignEnd()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('harga_jual_saat_transaksi')
                    ->label('Harga Jual (Satuan)')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('Subtotal (IDR)')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                // Filter Rentang Tanggal (menggunakan whereHas)
                Filter::make('tanggal_keluar')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereHas(
                                    'barangKeluar',
                                    fn($q) => $q->whereDate('tanggal_keluar', '>=', $date)
                                ),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereHas(
                                    'barangKeluar',
                                    fn($q) => $q->whereDate('tanggal_keluar', '<=', $date)
                                ),
                            );
                    }),
                SelectFilter::make('cabang')
                    ->label('Cabang')
                    ->options(Cabang::pluck('nama_cabang', 'id'))
                    ->query(fn(Builder $query, array $data) => $query->whereHas(
                        'barangKeluar',
                        fn($q) => $q->where('id_cabang', $data['value'])
                    )),
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(Kategori::pluck('nama_kategori', 'id'))
                    ->query(fn(Builder $query, array $data) => $query->whereHas(
                        'varianProduk.produk',
                        fn($q) => $q->where('id_kategori', $data['value'])
                    )),
                Filter::make('varian_produk')
                    ->form([
                        FormSelect::make('id_varian_produk')
                            ->label('Cari Produk / Varian')
                            ->options(VarianProduk::with('produk')->get()->mapWithKeys(fn($varian) => [
                                $varian->id => "{$varian->produk->nama_produk} - {$varian->nama_varian}"
                            ]))
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(fn(Builder $query, array $data) => $query->when(
                        $data['id_varian_produk'],
                        fn(Builder $query, $id) => $query->where('id_varian_produk', $id)
                    )),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                // [PERBAIKAN 2]: Tentukan Exporter kustom dan aktifkan antrian (queue)
                Tables\Actions\ExportBulkAction::make()
                    ->exporter(BarangKeluarDetailExporter::class)
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Definisi Footer (TETAP SAMA)
     */
    protected function getTableFooter(): ?View
    {
        $query = $this->getFilteredTableQuery();

        // Cukup hitung total 'subtotal' dari query utama (BarangKeluarDetail)
        $total = $query->sum('subtotal');

        return view('filament.pages.laporan-barang-keluar-footer', ['total' => $total]);
    }
}
