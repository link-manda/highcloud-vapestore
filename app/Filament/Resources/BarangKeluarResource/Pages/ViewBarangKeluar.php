<?php

namespace App\Filament\Resources\BarangKeluarResource\Pages;

use App\Filament\Resources\BarangKeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBarangKeluar extends ViewRecord
{
    protected static string $resource = BarangKeluarResource::class;

    protected function getHeaderActions(): array
    {
        // Biasanya halaman View tidak ada action Edit/Delete untuk transaksi
        return [
            // Actions\EditAction::make(), // Mungkin tidak diizinkan
        ];
    }
}
