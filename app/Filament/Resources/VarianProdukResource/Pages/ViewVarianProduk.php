<?php

namespace App\Filament\Resources\VarianProdukResource\Pages;

use App\Filament\Resources\VarianProdukResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVarianProduk extends ViewRecord
{
    protected static string $resource = VarianProdukResource::class;

    // Opsional: Kita bisa tambahkan tombol header jika perlu,
    // tapi untuk sekarang kita biarkan kosong agar bersih.
    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(), 
        ];
    }
}
