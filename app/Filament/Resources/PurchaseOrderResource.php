<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use App\Models\VarianProduk; // Import VarianProduk
use App\Models\StokCabang;   // Import StokCabang
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists; // Import Infolists
use Filament\Infolists\Infolist; // Import Infolist
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get; // Import Get
use Filament\Forms\Set; // Import Set
use Illuminate\Support\Number; // Import Number facade

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Pengadaan';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nomor_po'; // Judul record saat dilihat/edit

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi PO')
                            ->schema([
                                Forms\Components\TextInput::make('nomor_po')
                                    ->label('Nomor PO')
                                    ->default('PO-' . date('Ymd') . '-XXXX') // Placeholder default
                                    ->disabled() // Nomor PO dibuat otomatis
                                    ->dehydrated(), // Pastikan dikirim saat create
                                Forms\Components\DatePicker::make('tanggal_po')
                                    ->label('Tanggal PO')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\Select::make('id_supplier')
                                    ->relationship('supplier', 'nama_supplier')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Supplier'),
                                Forms\Components\Select::make('id_cabang_tujuan')
                                    ->relationship('cabangTujuan', 'nama_cabang')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Cabang Tujuan (Penerima)'),
                                Forms\Components\DatePicker::make('tanggal_estimasi_tiba')
                                    ->label('Estimasi Tiba'),
                                // Status hanya bisa diedit secara manual jika Draft
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        // Status lain diubah via Actions
                                    ])
                                    ->default('Draft')
                                    ->required()
                                    ->visibleOn('create') // Hanya terlihat saat create
                                    ->disabled(fn(string $operation): bool => $operation !== 'create')
                            ])->columns(2),

                        Forms\Components\Section::make('Catatan')
                            ->schema([
                                Forms\Components\Textarea::make('catatan')
                                    ->label('Catatan Tambahan')
                                    ->rows(3),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Detail Item Pesanan')
                            ->schema([
                                Forms\Components\Repeater::make('details')
                                    ->relationship() // Gunakan relasi 'details'
                                    ->label('Item Pesanan')
                                    ->schema([
                                        Forms\Components\Select::make('id_varian_produk')
                                            ->label('Produk Varian (SKU)')
                                            ->relationship('varianProduk', 'id', function (Builder $query) {
                                                // Eager load relasi produk untuk menampilkan nama lengkap
                                                $query->with('produk');
                                            })
                                            ->getOptionLabelFromRecordUsing(fn(VarianProduk $record) => "{$record->produk->nama_produk} - {$record->nama_varian}")
                                            ->searchable(['nama_varian', 'sku_code', 'produk.nama_produk']) // Cari berdasarkan varian, sku, nama produk induk
                                            ->preload()
                                            ->required()
                                            ->reactive() // Reaktif agar bisa update harga
                                            ->afterStateUpdated(function (Set $set, ?int $state, Get $get) { // Tambahkan Get
                                                // Ambil harga beli default saat varian dipilih
                                                $varian = VarianProduk::find($state);
                                                $hargaDefault = $varian ? $varian->harga_beli : 0;
                                                $set('harga_beli_saat_po', $hargaDefault);
                                                // Hitung ulang subtotal dengan harga baru dan jumlah yang ada
                                                $jumlah = (int) $get('jumlah_pesan');
                                                $set('subtotal', $jumlah * $hargaDefault);
                                            })
                                            ->columnSpan([
                                                'md' => 5,
                                            ]),
                                        Forms\Components\TextInput::make('jumlah_pesan')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->default(1)
                                            ->reactive()
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                // Hitung subtotal saat jumlah atau harga berubah
                                                $harga = (float) $get('harga_beli_saat_po');
                                                $jumlah = (int) $state;
                                                $set('subtotal', $jumlah * $harga);
                                            })
                                            ->columnSpan([
                                                'md' => 2,
                                            ]),
                                        Forms\Components\TextInput::make('harga_beli_saat_po')
                                            ->label('Harga Beli')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->default(0) // Tambahkan default 0
                                            ->reactive()
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                // Hitung subtotal saat jumlah atau harga berubah
                                                $harga = (float) $state;
                                                $jumlah = (int) $get('jumlah_pesan');
                                                $set('subtotal', $jumlah * $harga);
                                            })
                                            ->prefix('Rp')
                                            ->columnSpan([
                                                'md' => 3,
                                            ]),

                                        // Placeholder HANYA untuk TAMPILAN
                                        Forms\Components\Placeholder::make('subtotal_display') // Ganti nama agar tidak konflik
                                            ->label('Subtotal')
                                            ->content(function (Get $get): string {
                                                $harga = (float) $get('harga_beli_saat_po');
                                                $jumlah = (int) $get('jumlah_pesan');
                                                $subtotal = $jumlah * $harga;
                                                // Update state subtotal (jika perlu, tapi sudah dihandle afterStateUpdated lain)
                                                // $set('subtotal', $subtotal); // Tidak perlu $set di placeholder
                                                return Number::currency($subtotal, 'IDR');
                                            }),

                                        // Hidden input untuk MENYIMPAN subtotal
                                        Forms\Components\Hidden::make('subtotal')->default(0),


                                    ])
                                    ->itemLabel(function (array $state): ?string {
                                        // Menampilkan nama varian di header repeater item
                                        $varian = VarianProduk::with('produk')->find($state['id_varian_produk'] ?? null); // Eager load produk
                                        return $varian ? "{$varian->produk->nama_produk} - {$varian->nama_varian}" : null;
                                    })
                                    ->columns([
                                        'md' => 10, // Sesuaikan jumlah kolom dalam repeater
                                    ])
                                    ->addActionLabel('Tambah Item')
                                    ->live() // Agar total harga terupdate live
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        // Pastikan subtotal dihitung sebelum disimpan ke DB (safety net)
                                        $jumlah = (int)($data['jumlah_pesan'] ?? 0);
                                        $harga = (float)($data['harga_beli_saat_po'] ?? 0);
                                        $data['subtotal'] = $jumlah * $harga;
                                        return $data;
                                    })
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        // Hitung total keseluruhan PO saat repeater berubah
                                        self::updateTotalHarga($get, $set);
                                    })
                                    ->deleteAction(
                                        fn(Forms\Components\Actions\Action $action) => $action->after(fn(Get $get, Set $set) => self::updateTotalHarga($get, $set)),
                                    )
                                    ->reorderable(false) // Biasanya PO tidak perlu reorder
                                    ->defaultItems(1) // Minimal 1 item saat create
                                    ->required(), // Pastikan repeater tidak kosong

                                Forms\Components\Placeholder::make('total_harga_placeholder')
                                    ->label('Total Harga PO')
                                    ->content(function (Get $get): string {
                                        // Ambil dari state total_harga yang diupdate oleh updateTotalHarga
                                        return Number::currency((float) $get('total_harga'), 'IDR');
                                    }),

                                // Hidden input untuk menyimpan total harga (opsional jika hanya butuh placeholder)
                                Forms\Components\Hidden::make('total_harga')->default(0),

                            ])->columns(1), // Section detail item hanya 1 kolom
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3); // Total 3 kolom utama (2 untuk kiri, 1 untuk kanan)
    }

    // Fungsi helper untuk menghitung total harga
    public static function updateTotalHarga(Get $get, Set $set): void
    {
        $total = 0;
        $details = $get('details'); // Ambil semua item di repeater

        if (is_array($details)) {
            foreach ($details as $key => $detail) { // Tambahkan $key
                // Kalkulasi subtotal per item
                $jumlah = (int)($detail['jumlah_pesan'] ?? 0);
                $harga = (float)($detail['harga_beli_saat_po'] ?? 0);
                $subtotalItem = $jumlah * $harga;
                // Update state subtotal di dalam item repeater (PENTING)
                $set("details.{$key}.subtotal", $subtotalItem); // Update state hidden input subtotal
                // Tambahkan subtotal item ke total PO
                $total += $subtotalItem;
            }
        }

        // Update state total harga di form utama
        $set('total_harga', $total);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_po')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_po')
                    ->label('Tanggal PO')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabangTujuan.nama_cabang')
                    ->label('Cabang Tujuan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge() // Tampilkan sebagai badge
                    ->color(fn(PurchaseOrder $record): string => $record->status_color) // Gunakan accessor
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->numeric()
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('userPembuat.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft',
                        'Submitted' => 'Submitted',
                        'Partially Received' => 'Partially Received',
                        'Completed' => 'Completed',
                        'Cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('id_supplier')
                    ->relationship('supplier', 'nama_supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('id_cabang_tujuan')
                    ->relationship('cabangTujuan', 'nama_cabang')
                    ->label('Cabang Tujuan')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Edit hanya jika status Draft
                Tables\Actions\EditAction::make()
                    ->visible(fn(PurchaseOrder $record): bool => $record->status === 'Draft'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(PurchaseOrder $record): bool => $record->status === 'Draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tambahkan bulk action jika perlu (misal: bulk cancel draft PO)
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc'); // Urutkan berdasarkan terbaru
    }

    // Definisi Infolist untuk Halaman View
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi PO')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_po')->label('Nomor PO'),
                        Infolists\Components\TextEntry::make('tanggal_po')->label('Tanggal PO')->date('d M Y'),
                        Infolists\Components\TextEntry::make('supplier.nama_supplier')->label('Supplier'),
                        Infolists\Components\TextEntry::make('cabangTujuan.nama_cabang')->label('Cabang Tujuan'),
                        Infolists\Components\TextEntry::make('tanggal_estimasi_tiba')->label('Estimasi Tiba')->date('d M Y')->placeholder('-'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn(PurchaseOrder $record): string => $record->status_color),
                        Infolists\Components\TextEntry::make('userPembuat.name')->label('Dibuat Oleh'),
                        Infolists\Components\TextEntry::make('catatan')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ])->columns(3), // 3 kolom untuk info header

                Infolists\Components\Section::make('Detail Item Pesanan')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('') // Kosongkan label repeater
                            ->schema([
                                Infolists\Components\TextEntry::make('varianProduk.full_name') // Gunakan accessor jika ada, atau join manual
                                    ->label('Produk Varian')
                                    ->getStateUsing(fn($record): string => optional(optional($record->varianProduk)->produk)->nama_produk . ' - ' . optional($record->varianProduk)->nama_varian) // Versi aman jika relasi null
                                    ->columnSpan(4),
                                Infolists\Components\TextEntry::make('jumlah_pesan')
                                    ->label('Jumlah Pesan')
                                    ->numeric()
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('harga_beli_saat_po')
                                    ->label('Harga Beli')
                                    ->money('IDR')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('jumlah_diterima')
                                    ->label('Jumlah Diterima')
                                    ->numeric()
                                    ->badge()
                                    ->color(fn($state, $record) => $state >= $record->jumlah_pesan ? 'success' : ($state > 0 ? 'warning' : 'gray'))
                                    ->columnSpan(2),
                            ])
                            ->columns(12), // Total 12 kolom grid
                    ]),
                Infolists\Components\Section::make('Ringkasan')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_harga')
                            ->label('Total Harga PO')
                            ->money('IDR')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large), // Ukuran besar
                    ])

            ]);
    }


    public static function getRelations(): array
    {
        return [
            // Jika perlu menampilkan relasi lain di halaman view/edit
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            // 'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'), // Komentari/hapus jika hanya bisa diedit saat draft
            'view' => Pages\ViewPurchaseOrder::route('/{record}'), // Ganti edit ke view default
        ];
    }
}
