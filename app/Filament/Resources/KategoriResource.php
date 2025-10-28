<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KategoriResource\Pages;
// PASTIKAN IMPORT MODEL KATEGORI SUDAH BENAR
use App\Models\Kategori;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KategoriResource extends Resource
{
    // PASTIKAN BARIS INI ADA DAN MENUNJUK KE MODEL YANG BENAR
    protected static ?string $model = Kategori::class;

    // Mengatur ikon navigasi (https://heroicons.com/)
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    // Mengelompokkan navigasi
    protected static ?string $navigationGroup = 'Data Master';

    // Mengatur urutan navigasi
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Kolom Input Nama Kategori
                TextInput::make('nama_kategori')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true) // Pastikan unik
                    ->columnSpanFull(), // Lebar penuh

                // Kolom Input Deskripsi
                Textarea::make('deskripsi')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom Tabel Nama Kategori
                TextColumn::make('nama_kategori')
                    ->searchable() // Aktifkan pencarian
                    ->sortable(), // Aktifkan pengurutan

                // Kolom Tabel Tanggal Dibuat
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan by default

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter (jika diperlukan nanti)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Tambahkan aksi hapus
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKategoris::route('/'),
            'create' => Pages\CreateKategori::route('/create'),
            'edit' => Pages\EditKategori::route('/{record}/edit'),
        ];
    }
}
