<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewStockOpname extends ViewRecord
{
    protected static string $resource = StockOpnameResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        $user = auth()->user();

        // Admin bisa lihat semua
        if ($user->role === 'admin') {
            return;
        }

        // Staff hanya bisa melihat stock opname dari cabang mereka
        if ($user->role === 'staf' && $user->id_cabang && $this->record->id_cabang === $user->id_cabang) {
            return;
        }

        // Jika tidak memenuhi syarat
        abort(403, 'Anda tidak memiliki izin untuk mengakses stock opname cabang lain.');
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $actions = [];

        // Edit action - hanya untuk draft dan sesuai role
        if ($this->record->status === 'draft') {
            if ($user->role === 'admin' || ($user->role === 'staf' && $user->id_cabang === $this->record->id_cabang)) {
                $actions[] = Actions\EditAction::make()
                    ->visible(fn () => $this->record->status === 'draft');
            }
        }

        // Complete action - hanya admin
        if ($user->role === 'admin' && $this->record->status === 'draft') {
            $actions[] = Actions\Action::make('complete')
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
                ->visible(fn () => $this->record->status === 'draft');
        }

        return $actions;
    }
}