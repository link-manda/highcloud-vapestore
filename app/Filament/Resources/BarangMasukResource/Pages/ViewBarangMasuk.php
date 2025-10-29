<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBarangMasuk extends ViewRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(), // Tombol Edit di header (jika diperlukan)
            Actions\DeleteAction::make(), // Tambahkan tombol Delete di header jika perlu
        ];
    }
}
