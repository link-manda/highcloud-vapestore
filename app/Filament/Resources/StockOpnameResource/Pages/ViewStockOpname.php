<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewStockOpname extends ViewRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
            Actions\Action::make('complete')
                ->label('Selesaikan Opname')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Stok Opname')
                ->modalDescription('Apakah Anda yakin ingin menyelesaikan opname ini? Stok akan disesuaikan berdasarkan hasil opname.')
                ->modalSubmitActionLabel('Ya, Selesaikan')
                ->action(function () {
                    $this->record->update(['status' => 'completed']);

                    $updatedCount = 0;
                    $totalItems = $this->record->details->count();

                    // Update stok berdasarkan hasil opname
                    foreach ($this->record->details as $detail) {
                        $stokCabang = \App\Models\StokCabang::where('id_cabang', $this->record->id_cabang)
                            ->where('id_varian_produk', $detail->id_varian_produk)
                            ->first();

                        if ($stokCabang) {
                            $oldStok = $stokCabang->stok_saat_ini;
                            $newStok = $detail->stok_fisik;

                            $stokCabang->update([
                                'stok_saat_ini' => $newStok
                            ]);

                            $updatedCount++;

                            // Log perubahan stok
                            \Illuminate\Support\Facades\Log::info("Stock Opname Update: Cabang {$this->record->cabang->nama_cabang}, Varian {$detail->varianProduk->nama_varian}, Stok lama: {$oldStok}, Stok baru: {$newStok}");
                        }
                    }

                    // Kirim notifikasi sukses
                    Notification::make()
                        ->title('Stok Opname Berhasil Diselesaikan')
                        ->body("Stok opname untuk cabang {$this->record->cabang->nama_cabang} telah diselesaikan. {$updatedCount} dari {$totalItems} item stok telah disesuaikan.")
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }
}