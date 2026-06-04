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
        // Admin bisa akses semua
        if (StockOpnameResource::isAdmin()) {
            return;
        }

        // Staff hanya bisa akses jika memiliki cabang
        $user = auth()->user();
        if (StockOpnameResource::isStaf() && $user->id_cabang) {
            return;
        }

        // Jika tidak memenuhi syarat, redirect atau abort
        abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    protected function getHeaderActions(): array
    {
        if (StockOpnameResource::isAdmin() || (StockOpnameResource::isStaf() && auth()->user()?->id_cabang)) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        return [];
    }
}
