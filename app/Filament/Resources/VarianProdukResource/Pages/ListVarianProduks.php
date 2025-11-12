<?php

namespace App\Filament\Resources\VarianProdukResource\Pages;

use App\Filament\Resources\VarianProdukResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVarianProduks extends ListRecords
{
    protected static string $resource = VarianProdukResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }
}
