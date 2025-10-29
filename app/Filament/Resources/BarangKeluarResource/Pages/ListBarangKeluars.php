<?php

namespace App\Filament\Resources\BarangKeluarResource\Pages;

use App\Filament\Resources\BarangKeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder; // Import Builder
use Illuminate\Support\Facades\Auth; // Import Auth

class ListBarangKeluars extends ListRecords
{
    protected static string $resource = BarangKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Override query dasar untuk filter Staf
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = Auth::user();

        if ($user->role === 'staf') {
            $query->where('id_cabang', $user->id_cabang);
        }

        return $query;
    }
}
