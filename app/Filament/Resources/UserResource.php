<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Cabang;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Get; // <-- Import Get

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Ganti ikon (https://heroicons.com/)
    protected static ?string $navigationIcon = 'heroicon-o-users';

    // Grup terpisah untuk Manajemen Sistem/Pengguna
    protected static ?string $navigationGroup = 'Manajemen Sistem';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    // Pastikan email unik (abaikan record saat ini jika sedang edit)
                    ->unique(ignoreRecord: true),

                // Input untuk Role (Admin / Staf)
                Select::make('role')
                    ->options([
                        'admin' => 'Admin (Akses Penuh)',
                        'staf' => 'Staf (Terbatas per Cabang)',
                    ])
                    ->required()
                    ->native(false) // Gunakan UI select modern
                    // 'live()' PENTING! Ini akan memuat ulang form saat nilai berubah
                    ->live(),

                // Input untuk Cabang (Relasi)
                Select::make('id_cabang')
                    ->label('Cabang')
                    // Ambil data dari Model Cabang
                    ->relationship('cabang', 'nama_cabang')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    // LOGIKA KUNCI: Hanya terlihat jika role='staf'
                    ->visible(fn(Get $get): bool => $get('role') === 'staf')
                    // LOGIKA KUNCI: Hanya wajib diisi jika role='staf'
                    ->required(fn(Get $get): bool => $get('role') === 'staf'),

                // Input Password
                TextInput::make('password')
                    ->password() // Tipe 'password' (menyembunyikan ketikan)
                    ->required(fn(string $operation): bool => $operation === 'create') // Wajib saat 'create'
                    ->dehydrated(fn(?string $state): bool => filled($state)) // Hanya simpan jika diisi (opsional saat edit)
                    ->dehydrateStateUsing(fn(string $state): string => Hash::make($state)) // HASH PASSWORD
                    ->maxLength(255),

                // Input Konfirmasi Password
                TextInput::make('password_confirmation')
                    ->password()
                    ->label('Konfirmasi Password')
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->dehydrated(false) // Jangan simpan kolom ini di database
                    ->same('password'), // Validasi harus sama dengan 'password'
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('role')
                    ->badge() // Tampilkan sebagai 'badge' (label berwarna)
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger', // Admin = merah
                        'staf' => 'success', // Staf = hijau
                    })
                    ->sortable(),

                // Tampilkan nama cabang dari relasi
                TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->sortable()
                    // Tampilkan '-' jika user adalah admin (tidak punya cabang)
                    ->default('-'),

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
