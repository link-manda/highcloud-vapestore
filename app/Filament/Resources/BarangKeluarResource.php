<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangKeluarResource\Pages;
use App\Models\BarangKeluar;
use App\Models\Cabang;
use App\Models\StokCabang;
use App\Models\VarianProduk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString; // Untuk helper stok
use Illuminate\Database\Eloquent\Model; // Untuk type hint

class BarangKeluarResource extends Resource
{
    protected static ?string $model = BarangKeluar::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';

    protected static ?string $navigationLabel = 'Barang Keluar';

    protected static ?string $pluralModelLabel = 'Barang Keluar';

    protected static ?string $navigationGroup = 'Transaksi Inventori';

    protected static ?int $navigationSort = 2; // Urutan setelah Barang Masuk

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $userCabangId = $user->id_cabang;

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\DateTimePicker::make('tanggal_keluar')
                            ->label('Tanggal Keluar')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('id_cabang')
                            ->label('Cabang Asal Barang')
                            ->relationship('cabang', 'nama_cabang')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(!$isAdmin) // Disable untuk Staf
                            ->default($userCabangId) // Default ke cabang Staf
                            ->reactive(), // Reaktif agar stok di repeater bisa update
                        Forms\Components\TextInput::make('nama_pelanggan')
                            ->label('Nama Pelanggan (Opsional)'),
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan (Opsional)')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Item Barang Keluar')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->label('Item')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('id_varian_produk')
                                    ->label('Varian Produk (SKU)')
                                    ->relationship('varianProduk', 'id') // Relasi sementara untuk search
                                    ->getOptionLabelFromRecordUsing(fn(VarianProduk $record) => "{$record->produk->nama_produk} - {$record->nama_varian}")
                                    ->searchable(['nama_varian', 'sku_code', 'produk.nama_produk']) // Cari berdasarkan varian, sku, dan nama produk induk
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->distinct() // Hanya tampilkan satu kali jika ada duplikat entri
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems() // Jangan biarkan item yang sama dipilih lagi
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        // Set harga jual default saat varian dipilih
                                        $varian = VarianProduk::find($state);
                                        $set('harga_jual_saat_transaksi', $varian?->harga_jual ?? 0);
                                        $set('subtotal', 0); // Reset subtotal
                                        $set('jumlah', 1); // Reset jumlah ke 1
                                    })
                                    ->columnSpan([
                                        'md' => 5,
                                    ]),

                    // Placeholder untuk menampilkan stok tersedia
                    Forms\Components\Placeholder::make('stok_tersedia')
                        ->label('Stok Saat Ini')
                        ->content(function (Get $get) {
                            $idCabang = $get('../../id_cabang');
                            $idVarian = $get('id_varian_produk');

                            if (!$idCabang || !$idVarian) {
                                return '-';
                            }

                            $stok = StokCabang::where('id_cabang', $idCabang)
                                ->where('id_varian_produk', $idVarian)
                                ->value('stok_saat_ini');

                            return $stok ?? 0;
                        })
                        ->extraAttributes(function (Get $get) {
                            $idCabang = $get('../../id_cabang');
                            $idVarian = $get('id_varian_produk');

                            if (!$idCabang || !$idVarian) {
                                return ['class' => 'text-gray-500'];
                            }

                            $stok = StokCabang::where('id_cabang', $idCabang)
                                ->where('id_varian_produk', $idVarian)
                                ->value('stok_saat_ini');

                            $stokValue = $stok ?? 0;

                            // Gunakan kelas Tailwind yang benar untuk Filament v3
                            return [
                                'class' => $stokValue > 0
                                    ? 'text-green-600 font-medium dark:text-green-400'
                                    : 'text-red-600 font-medium dark:text-red-400'
                            ];
                        }),

                                Forms\Components\TextInput::make('jumlah')
                                    ->numeric()
                                    ->label('Jumlah Keluar')
                                    ->required()
                                    ->minValue(1)
                                    // Validasi Max Value berdasarkan Stok Tersedia
                                    ->maxValue(function (Get $get) {
                                        $idCabang = $get('../../id_cabang');
                                        $idVarian = $get('id_varian_produk');
                                        if (!$idCabang || !$idVarian) {
                                            return 1; // Default max 1 jika belum dipilih
                                        }
                                        $stok = StokCabang::where('id_cabang', $idCabang)
                                            ->where('id_varian_produk', $idVarian)
                                            ->value('stok_saat_ini');
                                        return $stok ?? 0; // Jika tidak ada stok, max 0
                                    })
                                    ->live(onBlur: true) // Update subtotal saat blur
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $hargaJual = $get('harga_jual_saat_transaksi') ?? 0;
                                        $set('subtotal', $state * $hargaJual);
                                    })
                                    ->numeric() // Pastikan hanya angka
                                    ->default(1)
                                    ->columnSpan([
                                        'md' => 2,
                                    ]),

                                Forms\Components\TextInput::make('harga_jual_saat_transaksi')
                                    ->numeric()
                                    ->inputMode('decimal')
                                    ->label('Harga Jual Satuan')
                                    ->required()
                                    ->prefix('Rp')
                                    ->live(onBlur: true) // Update subtotal saat blur
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $jumlah = $get('jumlah') ?? 0;
                                        $set('subtotal', $jumlah * $state);
                                    })
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'md' => 2,
                                    ]),

                                Forms\Components\Placeholder::make('subtotal')
                                    ->label('Subtotal')
                                    ->content(function (Get $get): string {
                                        $subtotal = $get('subtotal') ?? 0;
                                        return 'Rp ' . number_format($subtotal, 2, ',', '.');
                                    })
                                    ->columnSpan([
                                        'md' => 2,
                                    ]),

                            ])
                            ->itemLabel(function (array $state): ?string {
                                // Menampilkan nama varian di header repeater
                                $varian = VarianProduk::find($state['id_varian_produk'] ?? null);
                                return $varian ? "{$varian->produk->nama_produk} - {$varian->nama_varian}" : null;
                            })
                            ->addActionLabel('Tambah Item')
                            ->columns([ // Layouting kolom di dalam repeater
                                'md' => 12,
                            ])
                            ->required()
                            ->columnSpanFull()
                            // Kalkulasi subtotal sebelum disimpan ke DB
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $harga = $data['harga_jual_saat_transaksi'] ?? 0;
                                $jumlah = $data['jumlah'] ?? 0;
                                $data['subtotal'] = $harga * $jumlah;
                                return $data;
                            }),

                        Forms\Components\Placeholder::make('total_keseluruhan')
                            ->label('Total Keseluruhan')
                            ->content(function (Get $get): string {
                                $total = 0;
                                $details = $get('details') ?? [];
                                foreach ($details as $item) {
                                    $total += $item['subtotal'] ?? 0;
                                }
                                return 'Rp ' . number_format($total, 2, ',', '.');
                            })
                            ->columnSpanFull(),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_keluar')
                    ->label('Tanggal Keluar')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable()
                    ->visible(Auth::user()->role === 'admin'), // Hanya admin yg lihat kolom cabang
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dicatat Oleh')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_pelanggan')
                    ->label('Pelanggan')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('details_sum_jumlah') // Menampilkan total Qty
                    ->sum('details', 'jumlah')
                    ->label('Total Item'),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->getStateUsing(function (BarangKeluar $record) {
                        // Kalkulasi manual karena tidak ada kolom total di tabel induk
                        return $record->details()->sum('subtotal');
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter berdasarkan cabang jika user adalah admin
                Tables\Filters\SelectFilter::make('id_cabang')
                    ->label('Cabang')
                    ->relationship('cabang', 'nama_cabang')
                    ->searchable()
                    ->preload()
                    ->hidden(Auth::user()->role !== 'admin'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(), // Edit mungkin tidak diizinkan
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(), // Delete mungkin tidak diizinkan
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_transaksi')->label('No. Transaksi'),
                        Infolists\Components\TextEntry::make('tanggal_keluar')->dateTime()->label('Tanggal Keluar'),
                        Infolists\Components\TextEntry::make('cabang.nama_cabang')->label('Cabang Asal'),
                        Infolists\Components\TextEntry::make('user.name')->label('Dicatat Oleh'),
                        Infolists\Components\TextEntry::make('nama_pelanggan')->label('Nama Pelanggan')->placeholder('-'),
                        Infolists\Components\TextEntry::make('catatan')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ])->columns(2),
                Infolists\Components\Section::make('Detail Item Barang Keluar')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('') // Kosongkan label utama
                            ->schema([
                                Infolists\Components\TextEntry::make('varianProduk.produk.nama_produk')
                                    ->label('Produk')
                                    ->hiddenLabel(),
                                Infolists\Components\TextEntry::make('varianProduk.nama_varian')
                                    ->label('Varian')
                                    ->hiddenLabel(),
                                Infolists\Components\TextEntry::make('jumlah')
                                    ->label('Jumlah')
                                    ->alignRight(),
                                Infolists\Components\TextEntry::make('harga_jual_saat_transaksi')
                                    ->label('Harga Satuan')
                                    ->money('IDR')
                                    ->alignRight(),
                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR')
                                    ->alignRight(),
                            ])
                            ->columns(5) // Sesuaikan jumlah kolom
                            ->grid(2) // Tampilkan dalam grid jika perlu
                            ->columnSpanFull()
                            ->contained(false), // Hapus border repeater
                        // Placeholder untuk Total Keseluruhan di Infolist
                        Infolists\Components\TextEntry::make('total_keseluruhan')
                            ->label('Total Keseluruhan')
                            ->money('IDR')
                            ->getStateUsing(function (BarangKeluar $record): float {
                                return $record->details()->sum('subtotal');
                            })
                            ->alignRight()
                            ->columnSpanFull(),
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
            // 'edit' => Pages\EditBarangKeluar::route('/{record}/edit'), // Edit mungkin tidak diizinkan
            'view' => Pages\ViewBarangKeluar::route('/{record}'),
        ];
    }

    /**
     * Modifikasi query dasar untuk pembatasan Staf
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->role === 'staf') {
            $query->where('id_cabang', $user->id_cabang);
        }

        return $query;
    }

    // Menyembunyikan tombol Edit & Delete dari tabel list (opsional)
    public static function canEdit(Model $record): bool
    {
        return false; // Nonaktifkan edit
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Nonaktifkan delete
    }

    public static function canDeleteAny(): bool
    {
        return false; // Nonaktifkan bulk delete
    }
}
