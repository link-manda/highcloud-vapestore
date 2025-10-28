<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VarianProdukResource\Pages;
use App\Filament\Resources\VarianProdukResource\RelationManagers\StokCabangsRelationManager;
use App\Models\VarianProduk;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

// IMPORT TAMBAHAN UNTUK FORM
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\NumberInput;

// IMPORT BARU UNTUK INFOLIST (HALAMAN VIEW)
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;

class VarianProdukResource extends Resource
{
    protected static ?string $model = VarianProduk::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $label = 'Varian (SKU)';
    protected static ?string $pluralLabel = 'Varian (SKU)';
    protected static ?string $navigationLabel = 'Stok Varian (SKU)';

    protected static ?string $navigationGroup = 'Data Master';
    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    // Form ini (untuk Create/Edit) sengaja kita biarkan
    // Namun kita tidak akan menggunakannya
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('id_produk')
                    ->relationship('produk', 'nama_produk')
                    ->native(false),
                TextInput::make('nama_varian'),
                TextInput::make('harga_beli'),
                TextInput::make('harga_jual'),
            ]);
    }

    // FUNGSI BARU: INFOLIST (UNTUK HALAMAN VIEW)
    // Ini akan menampilkan detail varian di halaman View,
    // dan secara otomatis akan memuat Relation Manager di bawahnya.
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Detail Varian Produk')
                    ->schema([
                        TextEntry::make('produk.nama_produk')
                            ->label('Produk Induk'),
                        TextEntry::make('nama_varian'),
                        TextEntry::make('harga_beli')
                            ->money('IDR'),
                        TextEntry::make('harga_jual')
                            ->label('Harga Jual (POS)')
                            ->money('IDR'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('produk.nama_produk')
                    ->label('Produk Induk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_varian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('harga_beli')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('harga_jual')
                    ->label('Harga Jual (POS)')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Pastikan ini adalah ViewAction, bukan EditAction
                Tables\Actions\ViewAction::make()
                    ->label('Atur Stok'),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation Manager inilah yang akan muncul di halaman View
            StokCabangsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            // Halaman List (Daftar Varian)
            'index' => Pages\ListVarianProduks::route('/'),
            // Halaman View (Atur Stok)
            'view' => Pages\ViewVarianProduk::route('/{record}'),
            // Kita HAPUS route 'edit'
        ];
    }
}
