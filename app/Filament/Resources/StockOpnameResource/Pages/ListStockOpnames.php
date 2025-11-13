<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockOpnames extends ListRecords
{
    protected static string $resource = StockOpnameResource::class;

    public function mount(): void
    {
        $user = auth()->user();

        // Admin bisa akses semua
        if ($user->role === 'admin') {
            return;
        }

        // Staff hanya bisa akses jika memiliki cabang
        if ($user->role === 'staf' && $user->id_cabang) {
            return;
        }

        // Jika tidak memenuhi syarat, redirect atau abort
        abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        if ($user->role === 'admin' || ($user->role === 'staf' && $user->id_cabang)) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        return [];
    }
}
