<?php

namespace App\Filament\Resources\VarianProdukResource\Pages;

use App\Filament\Resources\VarianProdukResource;
use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVarianProduk extends ViewRecord
{
    protected static string $resource = VarianProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createPO')
                ->label('Buat Purchase Order')
                ->icon('heroicon-o-shopping-cart')
                ->url(fn () => PurchaseOrderResource::getUrl('create', [
                    'varian_id' => $this->record->id,
                ]))
                ->color('success')
                ->visible(fn () => auth()->user()->hasRole('Admin')), // Hanya Admin yang bisa buat PO
        ];
    }
}
