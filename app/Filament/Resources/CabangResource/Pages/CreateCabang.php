<?php

namespace App\Filament\Resources\CabangResource\Pages;

use App\Filament\Resources\CabangResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCabang extends CreateRecord
{
    protected static string $resource = CabangResource::class;

    protected function afterCreate(): void
    {
        // Redirect to the list page after successful creation
        $this->redirect(CabangResource::getUrl('index'));
    }
}
