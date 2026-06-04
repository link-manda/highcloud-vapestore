<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;

    public function mount(): void
    {
        // Admin bisa create untuk semua cabang
        if (StockOpnameResource::isAdmin()) {
            return;
        }

        // Staff bisa create untuk cabang mereka
        $user = auth()->user();
        if (StockOpnameResource::isStaf() && $user->id_cabang) {
            return;
        }

        // Jika tidak memenuhi syarat
        abort(403, 'Hanya Admin dan Staf dengan cabang yang dapat membuat Stock Opname baru.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Set petugas yang melakukan opname
        $data['id_petugas'] = $user->id;

        // Untuk staff, isi id_cabang dengan cabang mereka secara otomatis
        if (StockOpnameResource::isStaf() && $user->id_cabang) {
            $data['id_cabang'] = $user->id_cabang;
        }

        return $data;
    }
}
