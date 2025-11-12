<?php

namespace App\Filament\Pages;

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
use Filament\Tables;
use Illuminate\Support\Carbon;

class LaporanPurchaseOrder extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationLabel = 'Laporan Purchase Order';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Purchase Order (Status)';

    protected static string $view = 'filament.pages.laporan-purchase-order';

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
     * [PERBAIKAN 1]:
     * Hapus filter default ->whereIn() dari query dasar.
     * Biarkan query ini mengambil SEMUA PO.
     */
    protected function getTableQuery(): Builder
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'cabangTujuan', 'userPembuat', 'details']);
    }

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
                
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'Submitted',
                        'info' => 'Partially Received',
                        'success' => 'Completed',
                        'danger' => 'Cancelled',
                    ])
                    ->sortable(),
                
                TextColumn::make('progres')
                    ->label('Progres Diterima')
                    ->state(function (PurchaseOrder $record): string {
                        $totalDipesan = $record->details->sum('jumlah_pesan');
                        $totalDiterima = $record->details->sum('jumlah_diterima');
                        // Hindari pembagian nol jika PO tidak memiliki item
                        if ($totalDipesan == 0) {
                            return "0 / 0 item";
                        }
                        return "{$totalDiterima} / {$totalDipesan} item";
                    }),
                
                TextColumn::make('total_harga')
                    ->label('Total Nilai (IDR)')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                /**
                 * [PERBAIKAN 2]:
                 * Modifikasi SelectFilter 'status'
                 * 1. Tambahkan ->multiple()
                 * 2. Tambahkan ->default() untuk menetapkan status default (rancangan proaktif kita)
                 * 3. Ubah query() untuk menggunakan ->whereIn()
                 */
                SelectFilter::make('status')
                    ->label('Status PO')
                    ->multiple() // <-- Tambahkan ini
                    ->options([
                        'Draft' => 'Draft', // Tambahkan Draft
                        'Submitted' => 'Submitted',
                        'Partially Received' => 'Partially Received',
                        'Completed' => 'Completed',
                        'Cancelled' => 'Cancelled',
                    ])
                    // HAPUS atau KOMENTARI baris ini untuk menampilkan semua status:
                    // ->default(['Submitted', 'Partially Received'])
                    ->query(function (Builder $query, array $data): Builder {
                        // $data['values'] akan berisi array (karena ->multiple())
                        if (empty($data['values'])) {
                            // Jika user menghapus semua filter, tampilkan semua PO
                            return $query;
                        }
                        // Tampilkan PO yang statusnya ada di dalam array pilihan user
                        return $query->whereIn('status', $data['values']);
                    }),

                // Filter lain tetap sama
                Filter::make('tanggal_po')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_po', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_po', '<=', $date),
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
                Tables\Actions\ExportBulkAction::make()
                    ->exporter(PurchaseOrderExporter::class)
            ])
            ->defaultSort('tanggal_po', 'desc');
    }
}