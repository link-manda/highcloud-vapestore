<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockOpname extends EditRecord
{
    protected static string $resource = StockOpnameResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // Admin bisa edit semua
        if (StockOpnameResource::isAdmin()) {
            return;
        }

        // Staff hanya bisa edit stock opname dari cabang mereka
        $user = auth()->user();
        if (StockOpnameResource::isStaf() && $user->id_cabang && $this->record->id_cabang === $user->id_cabang) {
            return;
        }

        // Jika tidak memenuhi syarat
        abort(403, 'Anda tidak memiliki izin untuk mengedit stock opname cabang lain.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
