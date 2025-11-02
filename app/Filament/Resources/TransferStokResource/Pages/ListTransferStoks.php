<?php

namespace App\Filament\Resources\TransferStokResource\Pages;

use App\Filament\Resources\TransferStokResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransferStoks extends ListRecords
{
    protected static string $resource = TransferStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
