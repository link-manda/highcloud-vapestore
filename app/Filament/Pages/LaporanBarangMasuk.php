<?php

namespace App\Filament\Pages;

use App\Filament\Exports\BarangMasukExporter;
use App\Models\BarangMasuk;
use App\Models\Cabang;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder as QueryBuilder;

class LaporanBarangMasuk extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationLabel = 'Laporan Barang Masuk';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Barang Masuk';

    protected static string $view = 'filament.pages.laporan-barang-masuk';

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

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    /**
     * Definisi Tabel
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                BarangMasuk::query()->with(['supplier', 'cabangTujuan', 'user', 'purchaseOrder', 'details'])
            )
            ->striped()
            ->columns([
                TextColumn::make('nomor_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_masuk')
                    ->label('Tanggal Masuk')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cabangTujuan.nama_cabang')
                    ->label('Cabang Tujuan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Dicatat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchaseOrder.nomor_po')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Kolom Kalkulasi Total Nilai
                TextColumn::make('total_nilai')
                    ->label('Total Nilai (IDR)')
                    ->state(function (BarangMasuk $record): float {
                        // Kalkulasi: hitung jumlah subtotal dari relasi details
                        return $record->details->sum('subtotal');
                    })
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                // Filter Rentang Tanggal
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_masuk', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_masuk', '<=', $date),
                            );
                    }),
                SelectFilter::make('id_supplier')
                    ->label('Supplier')
                    ->relationship('supplier', 'nama_supplier')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('id_cabang_tujuan')
                    ->label('Cabang Tujuan')
                    ->options(Cabang::pluck('nama_cabang', 'id'))
                    ->searchable(),
            ])
            ->actions([
                //
            ])
            ->bulkActions([

            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(BarangMasukExporter::class)
                    ->label('Export Semua Data')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success'),
                Action::make('refresh')
                    ->label('Refresh Data')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        $this->resetTable();
                    }),
            ])
            ->defaultSort('tanggal_masuk', 'desc');
    }

    /**
     * Definisi Footer (TETAP SAMA)
     */
    protected function getTableFooter(): ?View
    {
        // Ambil query yang sudah difilter oleh tabel
        $query = $this->getFilteredTableQuery();

        // Hitung total nilai dari query yang sudah difilter
        // Kita perlu join dengan details untuk menghitung total
        $total = $query->join('barang_masuk_details', 'barang_masuks.id', '=', 'barang_masuk_details.id_barang_masuk')
            ->sum('barang_masuk_details.subtotal');

        return view('filament.pages.laporan-barang-masuk-footer', ['total' => $total]);
    }
}
