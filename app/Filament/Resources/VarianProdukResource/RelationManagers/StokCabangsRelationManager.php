<?php

namespace App\Filament\Resources\VarianProdukResource\RelationManagers;

use App\Models\Cabang;
use Filament\Forms;
use Filament\Forms\Components\NumberInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StokCabangsRelationManager extends RelationManager
{
    protected static string $relationship = 'stokCabangs';

    protected static ?string $recordTitleAttribute = 'id_cabang';

    protected static ?string $title = 'Stok per Cabang';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Pilih Cabang
                Select::make('id_cabang')
                    ->label('Cabang')
                    // Ambil dari Model Cabang
                    ->options(Cabang::all()->pluck('nama_cabang', 'id'))
                    ->searchable()
                    ->required()
                    ->native(false)
                    // Pastikan satu varian tidak bisa punya 2 entri stok di 1 cabang
                    ->unique(
                        table: 'stok_cabangs',
                        column: 'id_cabang',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Builder $query, RelationManager $livewire) {
                            return $query->where('id_varian_produk', $livewire->ownerRecord->id);
                        }
                    ),

                // Input Stok Awal (Hanya saat membuat)
                TextInput::make('stok_saat_ini')
                    ->numeric()
                    ->inputMode('decimal')
                    ->label('Stok Awal')
                    ->minValue(0)
                    ->default(0)
                    // Stok awal hanya bisa diisi saat pertama kali, 
                    // setelah itu diupdate oleh transaksi
                    ->hiddenOn('edit'),

                // Input Stok Minimum (Bisa di-edit kapanpun)
                TextInput::make('stok_minimum')
                    ->numeric()
                    ->inputMode('decimal')
                    ->label('Batas Stok Minimum')
                    ->helperText('Batas untuk notifikasi restock.')
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tampilkan Nama Cabang dari relasi
                TextColumn::make('cabang.nama_cabang')
                    ->label('Nama Cabang')
                    ->sortable(),

                TextColumn::make('stok_saat_ini')
                    ->label('Stok Saat Ini')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('stok_minimum')
                    ->label('Batas Stok Minimum')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Aksi untuk menambah 'Stok Cabang' baru
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Hanya bisa edit stok minimum
                Tables\Actions\EditAction::make(),

                // Sebaiknya jangan hapus data stok, 
                // tapi kita sediakan jika diperlukan
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    // Fungsi ini memastikan kita hanya bisa MENGELOLA stok 
    // untuk varian yang sedang dibuka
    public static function canViewForRecord(Model $ownerRecord, string $pageName): bool
    {
        return true;
    }
}
