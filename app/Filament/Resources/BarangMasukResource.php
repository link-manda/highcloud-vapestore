<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangMasukResource\Pages;
use App\Models\BarangMasuk;
use App\Models\Cabang;
use App\Models\Supplier;
use App\Models\VarianProduk;
use App\Models\StokCabang;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Tambahkan use statement ini di bagian atas
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection; // Alias untuk Section Infolist
use Filament\Infolists\Components\RepeatableEntry; // Untuk menampilkan detail di Infolist


class BarangMasukResource extends Resource
{
    protected static ?string $model = BarangMasuk::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'Transaksi Inventori';
    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Barang Masuk';
    protected static ?string $pluralLabel = 'Barang Masuk';


    public static function form(Form $form): Form
    {
        $isAdmin = Auth::user()->role === 'admin';
        $userCabangId = Auth::user()->id_cabang;

        return $form
            ->schema([
                Section::make('Informasi Transaksi')
                    ->schema([
                        TextInput::make('nomor_transaksi')
                            ->default('BM-' . date('YmdHis'))
                            ->disabled()
                            ->required()
                            ->maxLength(255)
                            ->unique(BarangMasuk::class, 'nomor_transaksi', ignoreRecord: true),
                        DatePicker::make('tanggal_masuk')
                            ->default(now())
                            ->required(),
                        Select::make('id_cabang_tujuan')
                            ->label('Cabang Tujuan (Penerima)')
                            ->relationship('cabangTujuan', 'nama_cabang')
                            ->options(
                                $isAdmin
                                    ? Cabang::pluck('nama_cabang', 'id')
                                    : Cabang::where('id', $userCabangId)->pluck('nama_cabang', 'id')
                            )
                            ->default($isAdmin ? null : $userCabangId)
                            ->disabled(!$isAdmin)
                            ->required(),
                        Select::make('id_supplier')
                            ->label('Sumber: Supplier')
                            ->relationship('supplier', 'nama_supplier')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('id_cabang_sumber', null))
                            ->hidden(fn(Get $get) => $get('id_cabang_sumber') !== null), // Tampilkan jika cabang sumber KOSONG
                        Select::make('id_cabang_sumber')
                            ->label('Sumber: Cabang Lain (Transfer)')
                            ->relationship('cabangSumber', 'nama_cabang')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(fn(Get $get) => Cabang::where('id', '!=', $get('id_cabang_tujuan'))->pluck('nama_cabang', 'id'))
                            ->afterStateUpdated(fn(Set $set) => $set('id_supplier', null))
                            ->hidden(fn(Get $get) => $get('id_supplier') !== null), // Tampilkan jika supplier KOSONG
                        Textarea::make('catatan')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Detail Item Barang Masuk')
                    ->schema([
                        Repeater::make('details')
                            ->relationship()
                            ->label('Item')
                            ->schema([
                                Select::make('id_varian_produk')
                                    ->label('Varian Produk (SKU)')
                                    ->relationship('varianProduk', 'id')
                                    ->getOptionLabelFromRecordUsing(fn(VarianProduk $record) => "{$record->produk->nama_produk} - {$record->nama_varian}")
                                    ->searchable(['nama_varian', 'produk.nama_produk'])
                                    ->preload()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $varian = VarianProduk::find($state);
                                        $set('harga_beli_saat_transaksi', $varian?->harga_beli ?? 0);
                                        $set('subtotal', 0);
                                    }),
                                TextInput::make('jumlah')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateSubtotal($get, $set);
                                    }),
                                TextInput::make('harga_beli_saat_transaksi')
                                    ->label('Harga Beli Satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateSubtotal($get, $set);
                                    }),
                                Placeholder::make('subtotal')
                                    ->label('Subtotal')
                                    ->content(function (Get $get): string {
                                        $subtotal = ($get('jumlah') ?? 0) * ($get('harga_beli_saat_transaksi') ?? 0);
                                        return 'Rp ' . number_format($subtotal, 0, ',', '.');
                                    }),
                            ])
                            ->columns(4)
                            ->addActionLabel('Tambah Item')
                            ->reorderableWithButtons()
                            ->collapsible()
                            // --- PERBAIKAN DI SINI ---
                            ->itemLabel(function (array $state): ?string {
                                $varian = VarianProduk::find($state['id_varian_produk'] ?? null);
                                return $varian ? "{$varian->produk->nama_produk} - {$varian->nama_varian}" : null;
                            })
                            // --- AKHIR PERBAIKAN ---
                            ->deleteAction(
                                fn(Forms\Components\Actions\Action $action) => $action->requiresConfirmation(),
                            )
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['subtotal'] = ($data['jumlah'] ?? 0) * ($data['harga_beli_saat_transaksi'] ?? 0);
                                return $data;
                            }),

                        // Placeholder::make('total_keseluruhan')
                        //     ->label('Total Keseluruhan')
                        //     ->content(function (Get $get): string {
                        //         $total = 0;
                        //         $details = $get('details') ?? [];
                        //         foreach ($details as $item) {
                        //             $total += ($item['jumlah'] ?? 0) * ($item['harga_beli_saat_transaksi'] ?? 0);
                        //         }
                        //         return 'Rp ' . number_format($total, 0, ',', '.');
                        //     })->columnSpanFull(), // Pastikan placeholder ini punya column span full
                    ])->columns(1),
            ]);
    }

    // Fungsi helper untuk update subtotal (tidak perlu diubah)
    public static function updateSubtotal(Get $get, Set $set): void
    {
        $jumlah = $get('jumlah') ?? 0;
        $harga = $get('harga_beli_saat_transaksi') ?? 0;
        // Kita tidak set state 'subtotal' karena Placeholder menghitungnya sendiri
        // $set('subtotal', $jumlah * $harga);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_masuk')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('cabangTujuan.nama_cabang')
                    ->label('Cabang Tujuan')
                    ->sortable(),
                TextColumn::make('sumber')
                    ->label('Sumber')
                    ->getStateUsing(function (BarangMasuk $record) {
                        return $record->supplier?->nama_supplier ?? $record->cabangSumber?->nama_cabang ?? '-';
                    }),
                TextColumn::make('user.name')
                    ->label('Dicatat Oleh')
                    ->sortable(),
                TextColumn::make('details_count')
                    ->counts('details')
                    ->label('Jml Item'),
                TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->getStateUsing(function (BarangMasuk $record) {
                        // Akses relasi details dan hitung sum dari subtotal
                        return $record->details()->sum('subtotal');
                    })
                    ->money('IDR', 0) // Menampilkan sebagai mata uang Rupiah tanpa desimal
                    ->alignRight(), // Rata kanan
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_masuk', 'desc');
    }

    // --- Tambahkan Method Infolist ---
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Transaksi')
                    ->schema([
                        TextEntry::make('nomor_transaksi'),
                        TextEntry::make('tanggal_masuk')->dateTime('d M Y H:i'),
                        TextEntry::make('cabangTujuan.nama_cabang')->label('Cabang Tujuan'),
                        TextEntry::make('sumber')->label('Sumber')
                            ->getStateUsing(function (BarangMasuk $record) {
                                return $record->supplier?->nama_supplier ?? $record->cabangSumber?->nama_cabang ?? '-';
                            }),
                        TextEntry::make('user.name')->label('Dicatat Oleh'),
                        TextEntry::make('catatan')->columnSpanFull(),
                    ])->columns(2),
                InfolistSection::make('Detail Item')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->label('') // Kosongkan label utama jika perlu
                            ->schema([
                                TextEntry::make('varianProduk.produk.nama_produk')
                                    ->label('Produk')
                                    ->inlineLabel(), // Tampilkan label di samping
                                TextEntry::make('varianProduk.nama_varian')
                                    ->label('Varian')
                                    ->inlineLabel(),
                                TextEntry::make('jumlah')
                                    ->numeric()
                                    ->inlineLabel(),
                                TextEntry::make('harga_beli_saat_transaksi')
                                    ->money('IDR', 0)
                                    ->label('Harga Beli')
                                    ->inlineLabel(),
                                TextEntry::make('subtotal')
                                    ->money('IDR', 0)
                                    ->inlineLabel(),
                            ])
                            ->columns(5), // Sesuaikan jumlah kolom
                        // --- Placeholder untuk Total Keseluruhan di View ---
                        TextEntry::make('total_keseluruhan_view')
                            ->label('Total Keseluruhan')
                            ->money('IDR', 0)
                            ->getStateUsing(function (BarangMasuk $record): float {
                                return $record->details()->sum('subtotal');
                            })

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
        return [
            'index' => Pages\ListBarangMasuks::route('/'),
            'create' => Pages\CreateBarangMasuk::route('/create'),
            'view' => Pages\ViewBarangMasuk::route('/{record}'),
            // 'edit' => Pages\EditBarangMasuk::route('/{record}/edit'), // Komentari jika tidak ada edit
        ];
    }
}
