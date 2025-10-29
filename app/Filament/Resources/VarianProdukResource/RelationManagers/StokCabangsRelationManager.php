<?php

namespace App\Filament\Resources\VarianProdukResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

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
                    // Tambahkan validasi untuk mencegah duplikasi
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                $exists = \App\Models\StokCabang::where('id_cabang', $value)
                                    ->where('id_varian_produk', $this->getOwnerRecord()->id)
                                    ->exists();

                                if ($exists) {
                                    $cabang = \App\Models\Cabang::find($value);
                                    $fail("Stok untuk cabang \"{$cabang->nama_cabang}\" sudah ada. Silakan edit stok yang sudah ada.");
                                }
                            };
                        },
                    ])
                    ->helperText('Pilih cabang yang belum memiliki stok'),

                Forms\Components\TextInput::make('stok_saat_ini')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->label('Jumlah Stok'),
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
                    ->form([
                        Forms\Components\TextInput::make('tambahan_stok')
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
}
