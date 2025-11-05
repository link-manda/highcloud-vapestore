<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VarianProdukResource\Pages;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\VarianProdukResource\RelationManagers\StokCabangsRelationManager;
use App\Models\VarianProduk;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

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

                InfolistSection::make('Summary Stok')
                    ->schema([
                        TextEntry::make('total_stok')
                            ->label('Total Stok Semua Cabang')
                            ->getStateUsing(function ($record) {
                                return $record->stokCabangs->sum('stok_saat_ini');
                            })
                            ->numeric()
                            ->formatStateUsing(fn($state) => number_format($state, 0)),

                        TextEntry::make('jumlah_cabang')
                            ->label('Jumlah Cabang yang Memiliki Stok')
                            ->getStateUsing(function ($record) {
                                return $record->stokCabangs->count();
                            }),
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

                TextColumn::make('total_stok')
                    ->label('Total Stok')
                    ->getStateUsing(function ($record) {
                        return $record->stokCabangs->sum('stok_saat_ini');
                    })
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 0))
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('cabang_count')
                    ->label('Jumlah Cabang')
                    ->getStateUsing(function ($record) {
                        return $record->stokCabangs->count();
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            StokCabangsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVarianProduks::route('/'),
            'view' => Pages\ViewVarianProduk::route('/{record}'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
