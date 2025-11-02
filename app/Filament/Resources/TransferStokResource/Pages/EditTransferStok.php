<?php

namespace App\Filament\Resources\TransferStokResource\Pages;

use App\Filament\Resources\TransferStokResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransferStok extends EditRecord
{
    protected static string $resource = TransferStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
