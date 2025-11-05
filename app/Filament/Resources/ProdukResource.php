<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdukResource\Pages;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\ProdukResource\RelationManagers\VarianProduksRelationManager;
use App\Models\Produk;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    // Ikon (https://heroicons.com/)
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    // Grup Data Master
    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Input Kategori (Relasi)
                Select::make('id_kategori')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori') // Ambil dari relasi 'kategori'
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),

                // Input Nama Produk (Induk)
                TextInput::make('nama_produk')
                    ->label('Nama Produk (Induk)')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Contoh: AIO CENTAURUS, AMERICAN FRUTY 60ML'),

                Textarea::make('deskripsi')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_produk')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                // Tampilkan Kategori dari relasi
                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    // PENTING: Mendaftarkan Relation Manager
    public static function getRelations(): array
    {
        return [
            VarianProduksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduks::route('/'),
            'create' => Pages\CreateProduk::route('/create'),
            // 'edit' Page akan menampilkan Relation Manager
            'edit' => Pages\EditProduk::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
