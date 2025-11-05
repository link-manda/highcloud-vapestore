<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CabangResource\Pages;
use App\Models\Cabang;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CabangResource extends Resource
{
    protected static ?string $model = Cabang::class;

    // Ganti ikon (https://heroicons.com/)
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    // Grup yang sama dengan Kategori
    protected static ?string $navigationGroup = 'Data Master';

    // Urutan navigasi (kita letakkan di atas Kategori)
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Kolom Input Nama Cabang
                TextInput::make('nama_cabang')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                // Kolom Input Alamat Cabang
                Textarea::make('alamat_cabang')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                // Kolom Input Telepon Cabang
                TextInput::make('telepon_cabang')
                    ->tel() // Tipe 'tel' untuk format telepon
                    ->maxLength(20),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom Tabel Nama Cabang
                TextColumn::make('nama_cabang')
                    ->searchable()
                    ->sortable(),

                // Kolom Tabel Telepon Cabang
                TextColumn::make('telepon_cabang')
                    ->searchable(),

                // Kolom Tabel Tanggal Dibuat (disembunyikan by default)
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCabangs::route('/'),
            'create' => Pages\CreateCabang::route('/create'),
            'edit' => Pages\EditCabang::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
