<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOpnameResource\Pages;
use App\Filament\Resources\StockOpnameResource\RelationManagers;
use App\Models\StockOpname;
use App\Models\Cabang;
use App\Models\VarianProduk;
use App\Models\StokCabang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Transaksi Inventori';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Stock Opname';

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Opname')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_opname')
                            ->default(now())
                            ->required()
                            ->label('Tanggal Opname'),
                        Forms\Components\Select::make('id_cabang')
                            ->relationship('cabang', 'nama_cabang')
                            ->label('Cabang')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->disabled($user->role === 'staf') // Disabled untuk staff
                            ->dehydrated($user->role === 'admin') // Hanya dehydrate untuk admin
                            ->default($user->role === 'staf' ? $user->id_cabang : null) // Default untuk staff
                            ->visible($user->role === 'admin') // Hanya tampil untuk admin
                            ->afterStateUpdated(function (Set $set) {
                                $set('details', []);
                            }),
                        // Placeholder untuk staff menampilkan cabang mereka
                        Forms\Components\Placeholder::make('cabang_info')
                            ->label('Cabang')
                            ->content(function () use ($user) {
                                if ($user->role === 'staf' && $user->id_cabang) {
                                    $cabang = \App\Models\Cabang::find($user->id_cabang);
                                    return $cabang ? $cabang->nama_cabang : 'Cabang tidak ditemukan';
                                }
                                return '';
                            })
                            ->visible($user->role === 'staf'),
                        Forms\Components\Hidden::make('id_petugas')
                            ->default(Auth::id()),
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Opname')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->relationship('details')
                            ->schema([
                                Forms\Components\Select::make('id_varian_produk')
                                    ->label('Varian Produk')
                                    ->options(function (Get $get) {
                                        $cabangId = $get('../../id_cabang');
                                        if (!$cabangId) return [];

                                        return VarianProduk::whereHas('stokCabangs', function ($query) use ($cabangId) {
                                            $query->where('id_cabang', $cabangId);
                                        })
                                        ->with(['produk', 'stokCabangs' => function ($query) use ($cabangId) {
                                            $query->where('id_cabang', $cabangId);
                                        }])
                                        ->get()
                                        ->mapWithKeys(function ($varian) use ($cabangId) {
                                            $stok = $varian->stokCabangs->first();
                                            $label = $varian->produk->nama_produk . ' - ' . $varian->nama_varian;
                                            if ($stok) {
                                                $label .= ' (Stok: ' . $stok->stok_saat_ini . ')';
                                            }
                                            return [$varian->id => $label];
                                        })
                                        ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $cabangId = $get('../../id_cabang');
                                        if ($cabangId && $state) {
                                            $stok = StokCabang::where('id_cabang', $cabangId)
                                                ->where('id_varian_produk', $state)
                                                ->first();
                                            $set('stok_sistem', $stok ? $stok->stok_saat_ini : 0);
                                            $set('stok_fisik', $stok ? $stok->stok_saat_ini : 0);
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('stok_sistem')
                                    ->label('Stok Sistem')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('stok_fisik')
                                    ->label('Stok Fisik')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $sistem = (int) $get('stok_sistem');
                                        $fisik = (int) $get('stok_fisik');
                                        $set('selisih', $fisik - $sistem);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('selisih')
                                    ->label('Selisih')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('catatan')
                                    ->label('Catatan Item')
                                    ->rows(1)
                                    ->columnSpanFull(),
                            ])
                            ->columns(6)
                            ->addActionLabel('Tambah Item Opname')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => isset($state['id_varian_produk']) ? (VarianProduk::find($state['id_varian_produk'])?->nama_varian ?? 'Item Opname') : 'Item Opname')
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (!auth()->check()) {
                    return $query;
                }

                $user = auth()->user();

                // Jika user adalah staff, batasi hanya melihat stock opname dari cabang mereka
                if ($user->role === 'staf' && $user->id_cabang) {
                    $query->where('id_cabang', $user->id_cabang);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_opname')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'completed' => 'success',
                    }),

                Tables\Columns\TextColumn::make('details_count')
                    ->label('Jumlah Item')
                    ->counts('details'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'completed' => 'Selesai',
                    ]),
                Tables\Filters\SelectFilter::make('id_cabang')
                    ->relationship('cabang', 'nama_cabang')
                    ->label('Cabang'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(function ($record) {
                        $user = auth()->user();
                        // Admin bisa lihat semua
                        if ($user->role === 'admin') {
                            return true;
                        }
                        // Staff hanya bisa lihat stock opname dari cabang mereka
                        return $user->role === 'staf' && $user->id_cabang && $record->id_cabang === $user->id_cabang;
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        if ($record->status !== 'draft') {
                            return false;
                        }
                        $user = auth()->user();
                        // Admin bisa edit semua
                        if ($user->role === 'admin') {
                            return true;
                        }
                        // Staff hanya bisa edit stock opname dari cabang mereka
                        return $user->role === 'staf' && $user->id_cabang && $record->id_cabang === $user->id_cabang;
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Selesaikan Opname')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Stok Opname')
                    ->modalDescription('Apakah Anda yakin ingin menyelesaikan opname ini? Stok akan disesuaikan berdasarkan hasil opname.')
                    ->modalSubmitActionLabel('Ya, Selesaikan')
                    ->action(function (StockOpname $record) {
                        $record->update(['status' => 'completed']);

                        $updatedCount = 0;
                        $totalItems = $record->details->count();

                        // Update stok berdasarkan hasil opname
                        foreach ($record->details as $detail) {
                            $stokCabang = StokCabang::where('id_cabang', $record->id_cabang)
                                ->where('id_varian_produk', $detail->id_varian_produk)
                                ->first();

                            if ($stokCabang) {
                                $oldStok = $stokCabang->stok_saat_ini;
                                $newStok = $detail->stok_fisik;

                                $stokCabang->update([
                                    'stok_saat_ini' => $newStok
                                ]);

                                $updatedCount++;

                                // Log perubahan stok
                                \Illuminate\Support\Facades\Log::info("Stock Opname Update: Cabang {$record->cabang->nama_cabang}, Varian {$detail->varianProduk->nama_varian}, Stok lama: {$oldStok}, Stok baru: {$newStok}");
                            }
                        }

                        // Kirim notifikasi sukses
                        Notification::make()
                            ->title('Stok Opname Berhasil Diselesaikan')
                            ->body("Stok opname untuk cabang {$record->cabang->nama_cabang} telah diselesaikan. {$updatedCount} dari {$totalItems} item stok telah disesuaikan.")
                            ->success()
                            ->send();
                    })
                    ->visible(function ($record) {
                        if ($record->status !== 'draft') {
                            return false;
                        }
                        $user = auth()->user();
                        // Hanya Admin yang bisa complete stock opname
                        return $user->role === 'admin';
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function ($records) {
                            if (!$records || !$records->every(fn ($record) => $record->status === 'draft')) {
                                return false;
                            }
                            $user = auth()->user();
                            // Admin bisa delete semua
                            if ($user->role === 'admin') {
                                return true;
                            }
                            // Staff hanya bisa delete stock opname dari cabang mereka
                            return $records->every(fn ($record) => $record->id_cabang === $user->id_cabang);
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Opname')
                    ->schema([
                        Infolists\Components\TextEntry::make('tanggal_opname')
                            ->label('Tanggal Opname')
                            ->date('d F Y'),
                        Infolists\Components\TextEntry::make('cabang.nama_cabang')
                            ->label('Cabang'),
                        Infolists\Components\TextEntry::make('petugas.name')
                            ->label('Petugas'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'completed' => 'success',
                            }),
                        Infolists\Components\TextEntry::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Detail Item Opname')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('varianProduk.produk.nama_produk')
                                    ->label('Produk'),
                                Infolists\Components\TextEntry::make('varianProduk.nama_varian')
                                    ->label('Varian'),
                                Infolists\Components\TextEntry::make('stok_sistem')
                                    ->label('Stok Sistem'),
                                Infolists\Components\TextEntry::make('stok_fisik')
                                    ->label('Stok Fisik'),
                                Infolists\Components\TextEntry::make('selisih')
                                    ->label('Selisih')
                                    ->color(fn (int $state): string => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                                Infolists\Components\TextEntry::make('catatan')
                                    ->label('Catatan')
                                    ->columnSpanFull(),
                            ])
                            ->columns(6)
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
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'view' => Pages\ViewStockOpname::route('/{record}'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Untuk navigation visibility, izinkan semua authenticated users melihat menu
        // Pembatasan akses akan dilakukan di level route dan action
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Navigation selalu ditampilkan untuk authenticated users
        return auth()->check();
    }
}
