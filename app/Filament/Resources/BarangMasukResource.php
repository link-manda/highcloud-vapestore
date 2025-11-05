<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangMasukResource\Pages;
use App\Models\BarangMasuk;
use App\Models\PurchaseOrder;
use App\Models\VarianProduk;
use App\Models\StokCabang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class BarangMasukResource extends Resource
{
    protected static ?string $model = BarangMasuk::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'Transaksi Inventori';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'nomor_transaksi';
    protected static ?string $navigationLabel = 'Barang Masuk (Supplier)';

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_transaksi')
                            ->default('BM-' . date('Ymd') . '-XXXX')
                            ->disabled()
                            ->dehydrated()
                            ->label('Nomor Transaksi'),
                        Forms\Components\DatePicker::make('tanggal_masuk')
                            ->default(now())
                            ->required()
                            ->label('Tanggal Masuk'),
                        Forms\Components\Select::make('id_cabang_tujuan')
                            ->relationship('cabangTujuan', 'nama_cabang')
                            ->label('Cabang Tujuan (Penerima)')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->disabled($user->role === 'staf' && $user->id_cabang)
                            ->dehydrated()
                            ->default($user->role === 'staf' ? $user->id_cabang : null)
                            ->afterStateUpdated(fn(Set $set) => $set('id_purchase_order', null)),
                        Forms\Components\Select::make('id_supplier')
                            ->relationship('supplier', 'nama_supplier')
                            ->label('Sumber Supplier')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(fn(Set $set) => $set('id_purchase_order', null)),
                        Forms\Components\Select::make('id_purchase_order')
                            ->label('Purchase Order (PO)')
                            ->options(function (Get $get): Collection {
                                $supplierId = $get('id_supplier');
                                $cabangId = $get('id_cabang_tujuan');
                                if (!$supplierId || !$cabangId) {
                                    return collect();
                                }
                                return PurchaseOrder::where('id_supplier', $supplierId)
                                    ->where('id_cabang_tujuan', $cabangId)
                                    ->whereIn('status', ['Submitted', 'Partially Received'])
                                    ->pluck('nomor_po', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->live()
                            ->visible(fn(Get $get) => $get('id_supplier') !== null && $get('id_cabang_tujuan') !== null)
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                self::fillDetailsFromPO($set, $state);
                            })
                            ->helperText('Pilih PO untuk mengisi item secara otomatis. Kosongkan jika barang masuk tanpa PO.'),
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Item Barang Masuk')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->label('Item')
                            ->schema([
                                Forms\Components\Hidden::make('id_varian_produk')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('id_purchase_order') !== null),

                                Forms\Components\Select::make('id_varian_produk')
                                    ->label('Produk Varian (SKU)')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->getSearchResultsUsing(fn(string $search): array => VarianProduk::with('produk')
                                        ->where('nama_varian', 'like', "%{$search}%")
                                        ->orWhere('sku_code', 'like', "%{$search}%")
                                        ->orWhereHas('produk', fn($query) => $query->where('nama_produk', 'like', "%{$search}%"))
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn(VarianProduk $record) => [$record->id => "{$record->produk->nama_produk} - {$record->nama_varian}"])
                                        ->all())
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        $record = VarianProduk::with('produk')->find($value);
                                        return $record ? "{$record->produk->nama_produk} - {$record->nama_varian}" : null;
                                    })
                                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get) {
                                        if ($get('id_purchase_order') === null && $state) {
                                            $varian = VarianProduk::find($state);
                                            $hargaDefault = $varian ? $varian->harga_beli : 0;
                                            $set('harga_beli_saat_transaksi', $hargaDefault);

                                            $jumlah = (int) $get('jumlah');
                                            $set('subtotal', $jumlah * $hargaDefault);
                                        }
                                    })
                                    ->visible(fn(Get $get) => $get('id_purchase_order') === null)
                                    ->columnSpan(['md' => 5]),

                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah Diterima')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $harga = (float) $get('harga_beli_saat_transaksi');
                                        $jumlah = (int) $state;
                                        $set('subtotal', $jumlah * $harga);
                                    })
                                    ->columnSpan(['md' => 2]),

                                Forms\Components\TextInput::make('harga_beli_saat_transaksi')
                                    ->label('Harga Beli')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $harga = (float) $state;
                                        $jumlah = (int) $get('jumlah');
                                        $set('subtotal', $jumlah * $harga);
                                    })
                                    ->prefix('Rp')
                                    ->disabled(fn(Get $get) => $get('id_purchase_order') !== null)
                                    ->columnSpan(['md' => 3]),

                                Forms\Components\Placeholder::make('subtotal_display')
                                    ->label('Subtotal')
                                    ->content(function (Get $get): string {
                                        $subtotal = (float) ($get('jumlah') ?? 0) * (float) ($get('harga_beli_saat_transaksi') ?? 0);
                                        return Number::currency($subtotal, 'IDR');
                                    })
                                    ->columnSpan(['md' => 2]),

                                Forms\Components\Hidden::make('subtotal')->default(0),
                            ])
                            ->itemLabel(function (array $state): ?string {
                                $varian = VarianProduk::with('produk')->find($state['id_varian_produk'] ?? null);
                                return $varian ? "{$varian->produk->nama_produk} - {$varian->nama_varian}" : null;
                            })
                            ->columns(['md' => 12])
                            ->addActionLabel('Tambah Item Manual')
                            ->addable(fn(Get $get) => $get('id_purchase_order') === null)
                            ->deletable(fn(Get $get) => $get('id_purchase_order') === null)
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->required(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_transaksi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_masuk')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabangTujuan.nama_cabang')
                    ->label('Cabang Tujuan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.nomor_po')
                    ->label('Nomor PO')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dicatat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_cabang_tujuan')
                    ->label('Cabang Tujuan')
                    ->relationship('cabangTujuan', 'nama_cabang')
                    ->preload()
                    ->searchable()
                    ->hidden(fn() => !Auth::user()->hasRole('Admin')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
                        Infolists\Components\TextEntry::make('tanggal_masuk')->date('d M Y'),
                        Infolists\Components\TextEntry::make('cabangTujuan.nama_cabang')->label('Cabang Tujuan'),
                        Infolists\Components\TextEntry::make('supplier.nama_supplier')->label('Supplier')->placeholder('-'),
                        Infolists\Components\TextEntry::make('purchaseOrder.nomor_po')->label('Nomor PO')->placeholder('-'),
                        Infolists\Components\TextEntry::make('user.name')->label('Dicatat Oleh'),
                        Infolists\Components\TextEntry::make('catatan')->columnSpanFull(),
                    ])->columns(3),
                Infolists\Components\Section::make('Detail Item Diterima')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                // [PERBAIKAN] Ganti 'varianProduk.full_name' menjadi 'id_varian_produk'
                                // 'id_varian_produk' adalah kolom valid, 'getStateUsing' akan menimpanya.
                                Infolists\Components\TextEntry::make('id_varian_produk')
                                    ->label('Produk Varian')
                                    ->getStateUsing(fn($record): string => $record->varianProduk ? (optional($record->varianProduk->produk)->nama_produk . ' - ' . $record->varianProduk->nama_varian) : 'N/A')
                                    ->columnSpan(4),
                                Infolists\Components\TextEntry::make('jumlah')
                                    ->label('Jumlah Diterima')
                                    ->numeric()
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('harga_beli_saat_transaksi')
                                    ->label('Harga Beli')
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
                            ->label('Total Nilai Barang Masuk')
                            ->money('IDR')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->getStateUsing(fn(BarangMasuk $record): float => $record->details->sum('subtotal')),
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
            'index' => Pages\ListBarangMasuks::route('/'),
            'create' => Pages\CreateBarangMasuk::route('/create'),
            'view' => Pages\ViewBarangMasuk::route('/{record}'),
            'edit' => Pages\EditBarangMasuk::route('/{record}/edit'),
        ];
    }

    public static function fillDetailsFromPO(Set $set, ?string $poId): void
    {
        $fieldName = 'harga_beli_saat_transaksi';

        if (empty($poId)) {
            $set('details', []);
            return;
        }
        $po = PurchaseOrder::with('details.varianProduk')->find($poId);
        if (!$po) {
            $set('details', []);
            return;
        }
        $newDetails = [];
        foreach ($po->details as $detail) {
            $sisaQty = $detail->jumlah_pesan - $detail->jumlah_diterima;
            if ($sisaQty <= 0) continue;

            $newDetails[] = [
                'id_varian_produk' => $detail->id_varian_produk,
                'jumlah' => $sisaQty,
                $fieldName => $detail->harga_beli_saat_po,
                'subtotal' => $sisaQty * $detail->harga_beli_saat_po,
            ];
            Log::info("[fillDetailsFromPO] Added detail - varian_id: {$detail->id_varian_produk}, jumlah: {$sisaQty}");
        }
        $set('details', $newDetails);
        Log::info("[fillDetailsFromPO] Final details to set: " . json_encode($newDetails));
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if ($user->hasRole('Admin')) {
            return $query;
        }

        return $query->where('id_cabang_tujuan', $user->id_cabang);
    }
}
