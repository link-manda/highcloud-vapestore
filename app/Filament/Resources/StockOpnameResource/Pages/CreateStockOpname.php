<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;

    public function mount(): void
    {
        $user = auth()->user();

        // Hanya Admin dan Staf dengan cabang yang bisa create stock opname
        if ($user->role === 'admin') {
            // Admin bisa create untuk semua cabang
            return;
        }

        if ($user->role === 'staf' && $user->id_cabang) {
            // Staff bisa create untuk cabang mereka
            return;
        }

        // Jika tidak memenuhi syarat
        abort(403, 'Hanya Admin dan Staf dengan cabang yang dapat membuat Stock Opname baru.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Untuk staff, isi id_cabang dengan cabang mereka secara otomatis
        if ($user->role === 'staf' && $user->id_cabang) {
            $data['id_cabang'] = $user->id_cabang;
        }

        return $data;
    }
}
