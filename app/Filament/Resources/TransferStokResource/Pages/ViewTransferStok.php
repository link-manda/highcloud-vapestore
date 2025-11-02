<?php

namespace App\Filament\Resources\TransferStokResource\Pages;

use App\Filament\Resources\TransferStokResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransferStok extends ViewRecord
{
    protected static string $resource = TransferStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tidak ada EditAction, sesuai rancangan
        ];
    }
}
