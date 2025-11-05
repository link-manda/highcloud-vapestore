<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangKeluarResource\Pages;
use App\Models\BarangKeluar;
use App\Models\StokCabang;
use App\Models\VarianProduk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class BarangKeluarResource extends Resource
{
    protected static ?string $model = BarangKeluar::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup = 'Transaksi Inventori';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'nomor_transaksi';

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_transaksi')
                            ->default('BK-' . date('Ymd') . '-XXXX')
                            ->disabled()
                            ->dehydrated()
                            ->label('Nomor Transaksi')
                            ->columnSpan(1),
                        Forms\Components\DateTimePicker::make('tanggal_keluar')
                            ->default(now())
                            ->required()
                            ->label('Tanggal Keluar/Penjualan')
                            ->columnSpan(1),

                        Forms\Components\Select::make('id_cabang')
                            ->relationship('cabang', 'nama_cabang')
                            ->label('Cabang Penjualan')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive() // Penting untuk filter item
                            // Logika Scoping: Staf hanya bisa input DARI cabangnya
                            ->disabled($user->role === 'staf' && $user->id_cabang)
                            ->dehydrated() // Paksa simpan meski disabled
                            ->default($user->role === 'staf' ? $user->id_cabang : null)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nama_pelanggan')
                            ->label('Nama Pelanggan (Opsional)')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Item Terjual')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->label('Item')
                            ->schema([
                                Forms\Components\Select::make('id_varian_produk')
                                    ->label('Produk Varian (SKU)')
                                    ->searchable()
                                    ->required()
                                    ->reactive() // Penting untuk validasi & harga
                                    ->live() // Pastikan placeholder stok update
                                    // Logika Krusial: Hanya tampilkan produk yang ADA STOK di cabang ini
                                    ->options(function (Get $get): Collection {
                                        $cabangId = $get('../../id_cabang'); // Ambil ID dari luar repeater
                                        if (!$cabangId) {
                                            return collect(); // Kosong jika cabang belum dipilih
                                        }

                                        // Cari varian produk yang punya stok > 0 di cabang sumber
                                        return VarianProduk::whereHas('stokCabangs', function (Builder $query) use ($cabangId) {
                                            $query->where('id_cabang', $cabangId)
                                                ->where('stok_saat_ini', '>', 0);
                                        })
                                            ->with('produk') // Load relasi produk untuk nama
                                            ->get()
                                            ->mapWithKeys(fn(VarianProduk $v) => [$v->id => "{$v->produk->nama_produk} - {$v->nama_varian}"]);
                                    })
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        $record = VarianProduk::with('produk')->find($value);
                                        return $record ? "{$record->produk->nama_produk} - {$record->nama_varian}" : null;
                                    })
                                    // Logika Auto-fill Harga Jual
                                    ->afterStateUpdated(function (Set $set, ?int $state) {
                                        $varian = VarianProduk::find($state);
                                        $hargaDefault = $varian ? $varian->harga_jual : 0;
                                        $set('harga_jual_saat_transaksi', $hargaDefault);
                                        // Reset jumlah ke 1
                                        $set('jumlah', 1);
                                    })
                                    ->columnSpan(['md' => 5]),

                                // Placeholder untuk menunjukkan sisa stok
                                Forms\Components\Placeholder::make('stok_saat_ini_display')
                                    ->label('Stok Saat Ini')
                                    ->content(function (Get $get): string {
                                        $varianId = $get('id_varian_produk');
                                        $cabangId = $get('../../id_cabang');
                                        if (!$varianId || !$cabangId) {
                                            return 'Pilih produk';
                                        }
                                        $stok = StokCabang::where('id_cabang', $cabangId)
                                            ->where('id_varian_produk', $varianId)
                                            ->first();
                                        return $stok ? $stok->stok_saat_ini : '0';
                                    })
                                    ->columnSpan(['md' => 2]),

                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah Jual')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->reactive()
                                    ->live() // Agar subtotal update
                                    // Logika Krusial: Validasi jumlah tidak melebihi stok
                                    ->maxValue(function (Get $get): int {
                                        $varianId = $get('id_varian_produk');
                                        $cabangId = $get('../../id_cabang');
                                        if (!$varianId || !$cabangId) {
                                            return 1; // Default
                                        }
                                        $stok = StokCabang::where('id_cabang', $cabangId)
                                            ->where('id_varian_produk', $varianId)
                                            ->first();
                                        // Jika stok tidak ditemukan (seharusnya tidak mungkin krn filter), set max 0
                                        return $stok ? $stok->stok_saat_ini : 0;
                                    })
                                    ->columnSpan(['md' => 2]),

                                Forms\Components\TextInput::make('harga_jual_saat_transaksi')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->live() // Agar subtotal update
                                    ->columnSpan(['md' => 3]),

                                // Placeholder untuk Subtotal
                                Forms\Components\Placeholder::make('subtotal_display')
                                    ->label('Subtotal')
                                    ->content(function (Get $get): string {
                                        $jumlah = (int) $get('jumlah');
                                        $harga = (float) $get('harga_jual_saat_transaksi');
                                        $subtotal = $jumlah * $harga;
                                        return Number::currency($subtotal, 'IDR');
                                    }),

                            ])
                            ->itemLabel(function (array $state): ?string {
                                $varian = VarianProduk::with('produk')->find($state['id_varian_produk'] ?? null);
                                $harga = Number::currency($state['harga_jual_saat_transaksi'] ?? 0, 'IDR');
                                return $varian ? "{$varian->produk->nama_produk} - {$varian->nama_varian} (Qty: {$state['jumlah']} @ {$harga})" : null;
                            })
                            ->columns(['md' => 12])
                            ->addActionLabel('Tambah Item')
                            ->defaultItems(1)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_transaksi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_keluar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dicatat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nama_pelanggan')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('total_penjualan')
                    ->label('Total Penjualan')
                    ->money('IDR')
                    ->getStateUsing(fn(BarangKeluar $record): float => $record->details->sum('subtotal'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_cabang')
                    ->label('Cabang')
                    ->relationship('cabang', 'nama_cabang')
                    ->preload()
                    ->searchable()
                    ->hidden(fn() => !Auth::user()->hasRole('Admin')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Transaksi penjualan biasanya tidak boleh di-edit/delete untuk menjaga integritas data
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_transaksi'),
                        Infolists\Components\TextEntry::make('tanggal_keluar')->dateTime('d M Y H:i'),
                        Infolists\Components\TextEntry::make('cabang.nama_cabang')->label('Cabang'),
                        Infolists\Components\TextEntry::make('user.name')->label('Dicatat Oleh'),
                        Infolists\Components\TextEntry::make('nama_pelanggan')->placeholder('-'),
                        Infolists\Components\TextEntry::make('catatan')->columnSpanFull(),
                    ])->columns(3),
                Infolists\Components\Section::make('Detail Item Terjual')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('id_varian_produk')
                                    ->label('Produk Varian')
                                    ->getStateUsing(fn($record): string => $record->varianProduk ? (optional($record->varianProduk->produk)->nama_produk . ' - ' . $record->varianProduk->nama_varian) : 'N/A')
                                    ->columnSpan(4),
                                Infolists\Components\TextEntry::make('jumlah')
                                    ->label('Jumlah Terjual')
                                    ->numeric()
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('harga_jual_saat_transaksi')
                                    ->label('Harga Jual')
                                    ->money('IDR')
                                    ->columnSpan(3),
                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR')
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                    ]),
                Infolists\Components\Section::make('Ringkasan')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_harga')
                            ->label('Total Nilai Penjualan')
                            ->money('IDR')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->getStateUsing(fn(BarangKeluar $record): float => $record->details->sum('subtotal')),
                    ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarangKeluars::route('/'),
            'create' => Pages\CreateBarangKeluar::route('/create'),
            'view' => Pages\ViewBarangKeluar::route('/{record}'),
            // 'edit' => Pages\EditBarangKeluar::route('/{record}/edit'), // Nonaktifkan Edit
        ];
    }

    /**
     * Logika Scoping (Kebijakan)
     * Staf hanya boleh melihat penjualan di cabang mereka.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if ($user->hasRole('Admin')) {
            return $query; // Admin bisa lihat semua
        }

        // Staf hanya bisa lihat penjualan dari cabang mereka
        return $query->where('id_cabang', $user->id_cabang);
    }
}
