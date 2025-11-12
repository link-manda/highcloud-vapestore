<?php

namespace App\Filament\Pages;

use App\Models\StockOpname;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;

class LaporanStokOpnamePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.laporan-stok-opname-page';
    protected static ?string $title = 'Laporan Stok Opname';
    protected static ?string $navigationLabel = 'Laporan Stok Opname';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 5;

    public static function canView(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    protected function getTableQuery(): Builder
    {
        return StockOpname::query()->with(['cabang', 'petugas', 'details']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->striped()
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('tanggal_opname')->label('Tanggal')->date('d/m/Y')->sortable(),
                TextColumn::make('cabang.nama_cabang')->label('Cabang')->searchable(),
                TextColumn::make('petugas.name')->label('Petugas'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'completed' => 'success',
                    default => 'gray',
                }),
                TextColumn::make('details_count')->label('Jumlah Item')->state(fn ($record) => $record->details->count()),
                TextColumn::make('total_selisih')->label('Total Selisih')->state(fn ($record) => $record->details->sum('selisih')),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('tanggal_opname')
                    ->form([
                        DatePicker::make('tanggal_dari')->label('Tanggal Dari')->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_sampai')->label('Tanggal Sampai')->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['tanggal_dari'], fn (Builder $query, $date) => $query->whereDate('tanggal_opname', '>=', $date))
                            ->when($data['tanggal_sampai'], fn (Builder $query, $date) => $query->whereDate('tanggal_opname', '<=', $date));
                    }),
                SelectFilter::make('id_cabang')
                    ->relationship('cabang', 'nama_cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'completed' => 'Selesai',
                    ]),
            ])
            ->actions([])
            ->headerActions([])
            ->bulkActions([])
            ->defaultSort('tanggal_opname', 'desc')
            ->emptyStateHeading('Belum ada data stok opname')
            ->emptyStateDescription('Data stok opname akan muncul di sini setelah dilakukan proses opname.')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
    }
}
