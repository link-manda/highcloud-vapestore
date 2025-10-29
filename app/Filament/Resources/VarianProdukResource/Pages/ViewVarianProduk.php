<?php

namespace App\Filament\Resources\VarianProdukResource\Pages;

use App\Filament\Resources\VarianProdukResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVarianProduk extends ViewRecord
{
    protected static string $resource = VarianProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
