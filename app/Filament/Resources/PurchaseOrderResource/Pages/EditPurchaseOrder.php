<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    // Hanya izinkan edit jika status masih 'Draft'
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
        abort_if($this->record->status !== 'Draft', 403, 'Purchase Order ini tidak dapat diedit karena statusnya bukan Draft.');
    }

    // Optional: Recalculate total if details are edited
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $total = 0;
        if (isset($data['details'])) {
            foreach ($data['details'] as $detail) {
                $jumlah = is_numeric($detail['jumlah_pesan'] ?? null) ? (int)$detail['jumlah_pesan'] : 0;
                $harga = is_numeric($detail['harga_beli_saat_po'] ?? null) ? (float)$detail['harga_beli_saat_po'] : 0;
                $total += $jumlah * $harga;
            }
        }
        $data['total_harga'] = $total;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Arahkan ke halaman view setelah edit
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Purchase Order berhasil diperbarui';
    }
}
