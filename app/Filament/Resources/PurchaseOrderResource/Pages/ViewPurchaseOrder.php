<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification; // Import Notification
use App\Models\PurchaseOrder; // Import Model

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Edit hanya muncul jika status Draft
            Actions\EditAction::make()
                ->visible(fn(PurchaseOrder $record): bool => $record->status === 'Draft'),

            // Tombol Submit PO (mengubah status ke Submitted)
            Actions\Action::make('submitPO')
                ->label('Submit PO')
                ->color('info')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn(PurchaseOrder $record): bool => $record->status === 'Draft') // Hanya muncul jika Draft
                ->requiresConfirmation() // Minta konfirmasi
                ->modalHeading('Submit Purchase Order?')
                ->modalDescription('Setelah disubmit, detail item PO tidak dapat diubah. Anda yakin?')
                ->modalSubmitActionLabel('Ya, Submit')
                ->action(function (PurchaseOrder $record) {
                    if ($record->status === 'Draft') {
                        $record->update(['status' => 'Submitted']);
                        Notification::make()
                            ->title('PO berhasil disubmit')
                            ->success()
                            ->send();
                        // Refresh halaman untuk update tampilan status & tombol
                        return redirect($this->getResource()::getUrl('view', ['record' => $record]));
                    } else {
                        Notification::make()
                            ->title('Gagal submit PO')
                            ->body('Status PO bukan Draft.')
                            ->danger()
                            ->send();
                    }
                }),

            // Tombol Cancel PO (mengubah status ke Cancelled)
            Actions\Action::make('cancelPO')
                ->label('Cancel PO')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                // Bisa dicancel jika Draft atau Submitted (belum diterima sebagian)
                ->visible(fn(PurchaseOrder $record): bool => in_array($record->status, ['Draft', 'Submitted']))
                ->requiresConfirmation()
                ->modalHeading('Batalkan Purchase Order?')
                ->modalDescription('PO yang dibatalkan tidak dapat diproses lebih lanjut. Anda yakin?')
                ->modalSubmitActionLabel('Ya, Batalkan')
                ->action(function (PurchaseOrder $record) {
                    if (in_array($record->status, ['Draft', 'Submitted'])) {
                        $record->update(['status' => 'Cancelled']);
                        Notification::make()
                            ->title('PO berhasil dibatalkan')
                            ->warning() // Gunakan warna warning/danger
                            ->send();
                        // Refresh halaman
                        return redirect($this->getResource()::getUrl('view', ['record' => $record]));
                    } else {
                        Notification::make()
                            ->title('Gagal membatalkan PO')
                            ->body('Status PO tidak memungkinkan untuk dibatalkan.')
                            ->danger()
                            ->send();
                    }
                }),

            // TODO: Nanti tambahkan Action "Terima Barang" di sini
            // Actions\Action::make('terimaBarang')
            //    ->label('Terima Barang dari PO Ini')
            //    ->color('success')
            //    ->icon('heroicon-o-archive-box-arrow-down')
            //    ->visible(fn (PurchaseOrder $record): bool => in_array($record->status, ['Submitted', 'Partially Received']))
            //    ->url(fn (PurchaseOrder $record): string => \App\Filament\Resources\BarangMasukResource::getUrl('create', ['po_id' => $record->id])) // Kirim ID PO ke halaman create BarangMasuk
        ];
    }
}
