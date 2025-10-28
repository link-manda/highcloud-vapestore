<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    // Ikon untuk supplier (https://heroicons.com/)
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    // Grup Data Master
    protected static ?string $navigationGroup = 'Data Master';

    // Urutan navigasi
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Kita gunakan 'Section' agar form terlihat rapi
                Section::make('Informasi Supplier')
                    ->schema([
                        TextInput::make('nama_supplier')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(), // Lebar penuh

                        TextInput::make('kontak_person')
                            ->maxLength(255),

                        TextInput::make('telepon')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->columnSpanFull(), // Lebar penuh

                        Textarea::make('alamat')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2), // Atur layout form menjadi 2 kolom
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kontak_person')
                    ->label('Kontak Person') // Label kolom
                    ->searchable(),

                TextColumn::make('telepon')
                    ->searchable(),

                // Sembunyikan email by default, tapi bisa ditampilkan
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
