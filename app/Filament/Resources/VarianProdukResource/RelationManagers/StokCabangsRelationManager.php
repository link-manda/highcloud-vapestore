<?php

namespace App\Filament\Resources\VarianProdukResource\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\TextInput;

class StokCabangsRelationManager extends RelationManager
{
    protected static string $relationship = 'stokCabangs';

    // Method sederhana - hapus logika kompleks
    public function isReadOnly(): bool
    {
        return false; // Langsung return false
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_cabang')
                    ->relationship('cabang', 'nama_cabang')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Cabang')
                    ->disabled(fn(string $operation): bool => $operation === 'edit') // Disable saat edit
                    // Tambahkan validasi untuk mencegah duplikasi - hanya untuk operasi create
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                // Hanya jalankan validasi saat create, bukan edit
                                if (request()->route() && str_contains(request()->route()->getName(), 'create')) {
                                    $exists = \App\Models\StokCabang::where('id_cabang', $value)
                                        ->where('id_varian_produk', $this->getOwnerRecord()->id)
                                        ->exists();

                                    if ($exists) {
                                        $cabang = \App\Models\Cabang::find($value);
                                        $fail("Stok untuk cabang \"{$cabang->nama_cabang}\" sudah ada. Silakan edit stok yang sudah ada.");
                                    }
                                }
                            };
                        },
                    ])
                    ->helperText(fn(string $operation): ?string => $operation === 'edit' ? 'Cabang tidak bisa diubah saat edit.' : 'Pilih cabang yang belum memiliki stok'),

                TextInput::make('stok_saat_ini')
                    ->numeric()
                    ->label('Stok Awal / Saat Ini')
                    ->disabled(fn(string $operation): bool => $operation === 'edit') // Disable saat edit
                    ->required(fn(string $operation): bool => $operation === 'create') // Wajib hanya saat create (stok awal)
                    ->helperText(fn(string $operation): ?string => $operation === 'edit' ? 'Stok saat ini hanya bisa diubah melalui transaksi Barang Masuk/Keluar.' : 'Masukkan jumlah stok awal untuk cabang ini.')
                    ->default(0),
                TextInput::make('stok_minimum')
                    ->numeric()
                    ->required()
                    ->label('Batas Stok Minimum')
                    ->helperText('Batas stok untuk memicu notifikasi email ke Admin.')
                    ->default(0),
                    ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cabang.nama_cabang')
            ->columns([
                Tables\Columns\TextColumn::make('cabang.nama_cabang')
                    ->label('Nama Cabang')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('stok_saat_ini')
                    ->label('Stok')
                    ->sortable()
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state, 0)),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Stok Cabang')
                    ->modalHeading('Tambah Stok di Cabang Baru')
                    ->createAnother(false)
                    ->visible(fn () => auth()->user()->hasRole('Admin')) // Hanya Admin yang bisa tambah stok cabang baru
                    // Tambahkan error handling untuk database constraint
                    ->using(function (array $data) {
                        try {
                            return $this->getRelationship()->create($data);
                        } catch (QueryException $exception) {
                            // Tangani error duplicate entry
                            if (str_contains($exception->getMessage(), 'Duplicate entry')) {
                                $cabang = \App\Models\Cabang::find($data['id_cabang']);
                                throw ValidationException::withMessages([
                                    'id_cabang' => "Stok untuk cabang \"{$cabang->nama_cabang}\" sudah ada. Silakan edit stok yang sudah ada.",
                                ]);
                            }
                            throw $exception;
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Stok Cabang')
                    ->successNotificationTitle('Stok berhasil diperbarui'),

                Tables\Actions\Action::make('tambah_stok')
                    ->label('Tambah Stok')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(fn () => auth()->user()->hasRole('Admin')) // Hanya Admin yang bisa tambah stok manual
                    ->form([
                        TextInput::make('tambahan_stok')
                            ->label('Jumlah Stok yang Ditambahkan')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])
                    ->action(function (array $data, $record) {
                        $tambahan = $data['tambahan_stok'];
                        $record->update([
                            'stok_saat_ini' => $record->stok_saat_ini + $tambahan
                        ]);
                    })
                    ->successNotificationTitle('Stok berhasil ditambahkan'),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Hapus Stok Cabang'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada stok di cabang')
            ->emptyStateDescription('Klik "Tambah Stok Cabang" untuk menambahkan stok di cabang tertentu.')
            ->emptyStateIcon('heroicon-o-building-storefront');
    }

    public function canCreate(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
