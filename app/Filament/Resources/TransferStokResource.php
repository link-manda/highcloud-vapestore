<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransferStokResource\Pages;
use App\Models\TransferStok;
use App\Models\Cabang;
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

class TransferStokResource extends Resource
{
    protected static ?string $model = TransferStok::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Transaksi Inventori';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Transfer Stok Cabang';
    protected static ?string $recordTitleAttribute = 'nomor_transfer';

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transfer')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_transfer')
                            ->default('TS-' . date('Ymd') . '-XXXX')
                            ->disabled()
                            ->dehydrated() // Pastikan tetap tersimpan
                            ->label('Nomor Transfer')
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('tanggal_transfer')
                            ->default(now())
                            ->required()
                            ->label('Tanggal Transfer')
                            ->columnSpan(1),

                        Forms\Components\Select::make('id_cabang_sumber')
                            ->relationship('cabangSumber', 'nama_cabang')
                            ->label('Dari Cabang (Sumber)')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive() // Penting untuk filter item
                            // Validasi: Tidak boleh sama dengan tujuan
                            ->validationMessages([
                                'not_in' => 'Cabang Sumber tidak boleh sama dengan Cabang Tujuan.',
                            ])
                            ->rules([
                                fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($value === $get('id_cabang_tujuan')) {
                                        $fail('Cabang Sumber tidak boleh sama dengan Cabang Tujuan.');
                                    }
                                },
                            ])
                            // Logika Scoping: Staf hanya bisa transfer DARI cabangnya
                            ->disabled($user->role === 'staf' && $user->id_cabang)
                            ->dehydrated()
                            ->default($user->role === 'staf' ? $user->id_cabang : null)
                            ->columnSpan(1),

                        Forms\Components\Select::make('id_cabang_tujuan')
                            ->relationship('cabangTujuan', 'nama_cabang')
                            ->label('Ke Cabang (Tujuan)')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            // Validasi: Tidak boleh sama dengan sumber
                            ->validationMessages([
                                'not_in' => 'Cabang Tujuan tidak boleh sama dengan Cabang Sumber.',
                            ])
                            ->rules([
                                fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($value === $get('id_cabang_sumber')) {
                                        $fail('Cabang Tujuan tidak boleh sama dengan Cabang Sumber.');
                                    }
                                },
                            ])
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Item Transfer')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->label('Item')
                            ->schema([
                                Forms\Components\Select::make('id_varian_produk')
                                    ->label('Produk Varian (SKU)')
                                    ->searchable()
                                    ->required()
                                    ->reactive() // Penting untuk validasi jumlah
                                    // Logika Krusial: Hanya tampilkan produk yang ADA STOK di cabang SUMBER
                                    ->options(function (Get $get): Collection {
                                        $cabangSumberId = $get('../../id_cabang_sumber'); // Ambil ID dari luar repeater
                                        if (!$cabangSumberId) {
                                            return collect(); // Kosong jika cabang sumber belum dipilih
                                        }

                                        // Cari varian produk yang punya stok > 0 di cabang sumber
                                        return VarianProduk::whereHas('stokCabangs', function (Builder $query) use ($cabangSumberId) {
                                            $query->where('id_cabang', $cabangSumberId)
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
                                    ->columnSpan(['md' => 7]),

                                // Placeholder untuk menunjukkan sisa stok
                                Forms\Components\Placeholder::make('stok_sumber_display')
                                    ->label('Stok Saat Ini')
                                    ->content(function (Get $get): string {
                                        $varianId = $get('id_varian_produk');
                                        $cabangSumberId = $get('../../id_cabang_sumber');
                                        if (!$varianId || !$cabangSumberId) {
                                            return 'Pilih produk';
                                        }
                                        $stok = StokCabang::where('id_cabang', $cabangSumberId)
                                            ->where('id_varian_produk', $varianId)
                                            ->first();
                                        return $stok ? $stok->stok_saat_ini : '0';
                                    })
                                    ->columnSpan(['md' => 2]),

                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah Transfer')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->reactive()
                                    // Logika Krusial: Validasi jumlah tidak melebihi stok
                                    ->maxValue(function (Get $get): int {
                                        $varianId = $get('id_varian_produk');
                                        $cabangSumberId = $get('../../id_cabang_sumber');
                                        if (!$varianId || !$cabangSumberId) {
                                            return 1; // Default
                                        }
                                        $stok = StokCabang::where('id_cabang', $cabangSumberId)
                                            ->where('id_varian_produk', $varianId)
                                            ->first();
                                        return $stok ? $stok->stok_saat_ini : 0;
                                    })
                                    ->columnSpan(['md' => 3]),

                            ])
                            ->itemLabel(function (array $state): ?string {
                                $varian = VarianProduk::with('produk')->find($state['id_varian_produk'] ?? null);
                                return $varian ? "{$varian->produk->nama_produk} - {$varian->nama_varian} (Qty: {$state['jumlah']})" : null;
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
                Tables\Columns\TextColumn::make('nomor_transfer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_transfer')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabangSumber.nama_cabang')
                    ->label('Dari Cabang (Sumber)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabangTujuan.nama_cabang')
                    ->label('Ke Cabang (Tujuan)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('userPembuat.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('details_count')
                    ->counts('details') // Menghitung jumlah item
                    ->label('Total Item')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_cabang_sumber')
                    ->label('Cabang Sumber')
                    ->relationship('cabangSumber', 'nama_cabang')
                    ->preload()
                    ->searchable()
                    ->hidden(fn() => !Auth::user()->hasRole('Admin')),
                Tables\Filters\SelectFilter::make('id_cabang_tujuan')
                    ->label('Cabang Tujuan')
                    ->relationship('cabangTujuan', 'nama_cabang')
                    ->preload()
                    ->searchable()
                    ->hidden(fn() => !Auth::user()->hasRole('Admin')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Transaksi tidak boleh di-edit
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn() => !Auth::user()->hasRole('Admin')), // Hanya Admin boleh hapus
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn() => !Auth::user()->hasRole('Admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Transfer')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_transfer'),
                        Infolists\Components\TextEntry::make('tanggal_transfer')->date('d M Y'),
                        Infolists\Components\TextEntry::make('cabangSumber.nama_cabang')->label('Dari Cabang (Sumber)'),
                        Infolists\Components\TextEntry::make('cabangTujuan.nama_cabang')->label('Ke Cabang (Tujuan)'),
                        Infolists\Components\TextEntry::make('userPembuat.name')->label('Dibuat Oleh'),
                        Infolists\Components\TextEntry::make('catatan')->columnSpanFull(),
                    ])->columns(3),
                Infolists\Components\Section::make('Detail Item Ditransfer')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('id_varian_produk')
                                    ->label('Produk Varian')
                                    ->getStateUsing(fn($record): string => $record->varianProduk ? (optional($record->varianProduk->produk)->nama_produk . ' - ' . $record->varianProduk->nama_varian) : 'N/A')
                                    ->columnSpan(8),
                                Infolists\Components\TextEntry::make('jumlah')
                                    ->label('Jumlah Ditransfer')
                                    ->numeric()
                                    ->columnSpan(4),
                            ])
                            ->columns(12)
                    ]),
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
        // Kita mendaftarkan Page kustom kita
        return [
            'index' => Pages\ListTransferStoks::route('/'),
            'create' => Pages\CreateTransferStok::route('/create'),
            'view' => Pages\ViewTransferStok::route('/{record}'),
            // 'edit' => Pages\EditTransferStok::route('/{record}/edit'), // Kita nonaktifkan edit
        ];
    }

    /**
     * Logika Scoping (Kebijakan)
     * Staf hanya boleh melihat transfer yang melibatkan cabang mereka.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if ($user->hasRole('Admin')) {
            return $query; // Admin bisa lihat semua
        }

        // Staf hanya bisa lihat jika dia adalah PENGIRIM atau PENERIMA
        return $query->where(function (Builder $q) use ($user) {
            $q->where('id_cabang_sumber', $user->id_cabang)
                ->orWhere('id_cabang_tujuan', $user->id_cabang);
        });
    }
}
