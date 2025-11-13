<?php

namespace App\Filament\Pages;

use App\Filament\Exports\StockOpnameExporter;
use App\Models\StockOpnameDetail;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Notifications\Notification;

class LaporanStokOpnamePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.laporan-stok-opname-page';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Stok Opname';
    protected static ?string $title = 'Laporan Stok Opname';

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

    protected function getTableQuery(): Builder
    {
        return StockOpnameDetail::query()
            ->with(['varianProduk.produk', 'stockOpname.cabang']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('stockOpname.cabang.nama_cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stockOpname.tanggal_opname')
                    ->label('Tanggal Opname')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('varianProduk.produk.nama_produk')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('varianProduk.nama_varian')
                    ->label('Varian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stok_sistem')
                    ->label('Stok Sistem')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('stok_fisik')
                    ->label('Stok Fisik')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('selisih')
                    ->label('Selisih')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->sortable(),
                TextColumn::make('catatan')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
                Filter::make('tanggal_opname')
                    ->form([
                        DatePicker::make('tanggal_dari')
                            ->label('Tanggal Dari')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_sampai')
                            ->label('Tanggal Sampai')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['tanggal_dari'], fn (Builder $query, $date) => $query->whereHas('stockOpname', fn ($q) => $q->whereDate('tanggal_opname', '>=', $date)))
                            ->when($data['tanggal_sampai'], fn (Builder $query, $date) => $query->whereHas('stockOpname', fn ($q) => $q->whereDate('tanggal_opname', '<=', $date)));
                    }),
                SelectFilter::make('id_cabang')
                    ->relationship('stockOpname.cabang', 'nama_cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('selisih_type')
                    ->label('Tipe Selisih')
                    ->options([
                        'positive' => 'Selisih Positif (Lebih)',
                        'negative' => 'Selisih Negatif (Kurang)',
                        'zero' => 'Selisih Nol (Sesuai)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'] ?? null, function ($query, $value) {
                            return match ($value) {
                                'positive' => $query->where('selisih', '>', 0),
                                'negative' => $query->where('selisih', '<', 0),
                                'zero' => $query->where('selisih', '=', 0),
                                default => $query,
                            };
                        });
                    }),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\ExportBulkAction::make()
                    ->exporter(StockOpnameExporter::class)
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(StockOpnameExporter::class)
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
            ->defaultSort('stockOpname.tanggal_opname', 'desc')
            ->emptyStateHeading('Belum ada data stok opname')
            ->emptyStateDescription('Data stok opname akan muncul di sini setelah dilakukan proses opname.')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
    }
}