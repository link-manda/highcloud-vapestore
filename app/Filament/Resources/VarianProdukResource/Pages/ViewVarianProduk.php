<?php

namespace App\Filament\Resources\VarianProdukResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\VarianProdukResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVarianProduk extends ViewRecord
{
    protected static string $resource = VarianProdukResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // Hanya Admin yang bisa akses halaman Atur Stok
        if (! auth()->user()->hasRole('Admin')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }
    }

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
