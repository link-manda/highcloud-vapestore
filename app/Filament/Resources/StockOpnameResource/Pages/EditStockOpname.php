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

        $user = auth()->user();

        // Admin bisa edit semua
        if ($user->role === 'admin') {
            return;
        }

        // Staff hanya bisa edit stock opname dari cabang mereka
        if ($user->role === 'staf' && $user->id_cabang && $this->record->id_cabang === $user->id_cabang) {
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
