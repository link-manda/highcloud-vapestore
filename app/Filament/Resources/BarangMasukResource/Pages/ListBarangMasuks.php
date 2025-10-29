<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder; // Import Builder
use Illuminate\Support\Facades\Auth; // Import Auth

class ListBarangMasuks extends ListRecords
{
    protected static string $resource = BarangMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Modifikasi query dasar untuk membatasi data berdasarkan role user
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = Auth::user();

        // Jika user adalah 'staf', filter berdasarkan cabang tujuan mereka
        if ($user->role === 'staf' && $user->id_cabang) {
            $query->where('id_cabang_tujuan', $user->id_cabang);
        }

        return $query;
    }
}
