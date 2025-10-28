<?php

namespace App\Filament\Resources\ProdukResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VarianProduksRelationManager extends RelationManager
{
    protected static string $relationship = 'varians'; // Sesuai nama relasi di Model Produk

    protected static ?string $recordTitleAttribute = 'nama_varian';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_varian')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Contoh: CENTAURUS B60, AF AVOCADO'),

                TextInput::make('sku_code')
                    ->label('Kode SKU (Opsional)')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                // Input Harga Beli
                TextInput::make('harga_beli')
                    ->numeric()
                    ->inputMode('decimal')
                    ->required()
                    ->prefix('IDR')
                    ->minValue(0)
                    ->default(0),

                // Input Harga Jual
                TextInput::make('harga_jual')
                    ->numeric()
                    ->inputMode('decimal')
                    ->label('Harga Jual (POS)')
                    ->required()
                    ->prefix('IDR')
                    ->minValue(0)
                    ->default(0),
            ])
            ->columns(2); // Layout 2 kolom
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_varian')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku_code')
                    ->label('Kode SKU')
                    ->searchable(),

                TextColumn::make('harga_beli')
                    ->money('IDR') // Format sebagai mata uang
                    ->sortable(),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual (POS)')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
