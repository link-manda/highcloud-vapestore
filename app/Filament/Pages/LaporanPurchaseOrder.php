<?php

namespace App\Filament\Pages;

// [PERBAIKAN 1]: Impor Exporter kustom yang baru
use App\Filament\Exports\PurchaseOrderExporter;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Cabang;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables; // Diperlukan untuk BulkAction
use Illuminate\Support\Carbon;

class LaporanPurchaseOrder extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationLabel = 'Laporan Purchase Order';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Purchase Order (Status)';

    protected static string $view = 'filament.pages.laporan-purchase-order';

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
     * Query dasar yang sudah difilter
     */
    protected function getTableQuery(): Builder
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'cabangTujuan', 'userPembuat', 'details'])
            // Default filter: Hanya tampilkan PO yang masih terbuka
            ->whereIn('status', ['Completed', 'Partially Received']);
    }

    /**
     * Definisi Tabel
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->striped()
            ->columns([
                TextColumn::make('nomor_po')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_po')
                    ->label('Tanggal PO')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cabangTujuan.nama_cabang')
                    ->label('Cabang Tujuan')
                    ->searchable()
                    ->sortable(),

                // Kolom Status dengan Badge
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'Submitted',
                        'info' => 'Partially Received',
                        'success' => 'Completed',
                        'danger' => 'Cancelled',
                    ])
                    ->sortable(),

                // Kolom Kalkulasi Progres
                TextColumn::make('progres')
                    ->label('Progres Diterima')
                    ->state(function (PurchaseOrder $record): string {
                        $totalDipesan = $record->details->sum('jumlah_pesan');
                        $totalDiterima = $record->details->sum('jumlah_diterima');
                        return "{$totalDiterima} / {$totalDipesan} item";
                    }),

                TextColumn::make('total_harga')
                    ->label('Total Nilai (IDR)')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                // Filter untuk mengganti default view
                SelectFilter::make('status')
                    ->label('Status PO')
                    ->options([
                        'Submitted' => 'Submitted',
                        'Partially Received' => 'Partially Received',
                        'Completed' => 'Completed',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // Jika filter status diisi, gunakan itu.
                        if (!empty($data['value'])) {
                            // Hapus filter default 'whereIn' dan ganti dengan filter pilihan user
                            return $query->withoutGlobalScopes()->where('status', $data['value']);
                        }
                        // Jika filter dikosongkan, kembalikan ke query default
                        return $query;
                    }),
                Filter::make('tanggal_po')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_po', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_po', '<=', $date),
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
                // [PERBAIKAN 2]: Tentukan Exporter kustom dan aktifkan antrian (queue)
                Tables\Actions\ExportBulkAction::make()
                    ->exporter(PurchaseOrderExporter::class)
            ])
            ->defaultSort('tanggal_po', 'desc');
    }
}
