<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Log
use Illuminate\Database\Eloquent\Model; // Import Model

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getRedirectUrl(): string
    {
        // Arahkan ke halaman view setelah create
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    // Method ini berjalan SEBELUM PO induk disimpan
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('[CreatePO] Mutating form data before create...');
        
        // 1. Set User Pembuat
        $data['id_user_pembuat'] = Auth::id();

        // 2. Generate Nomor PO Unik
        $today = Carbon::today();
        $lastPoToday = PurchaseOrder::whereDate('created_at', $today)
                                    ->orderBy('id', 'desc')
                                    ->first();
        
        $countToday = 0;
        if ($lastPoToday && preg_match('/-(\d+)$/', $lastPoToday->nomor_po, $matches)) {
            $countToday = (int)$matches[1];
        }
        $data['nomor_po'] = 'PO-' . $today->format('Ymd') . '-' . str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
        Log::info('[CreatePO] Nomor PO set: ' . $data['nomor_po']);

        // 3. Set Total Harga Awal ke 0. Akan dihitung di afterCreate().
        $data['total_harga'] = 0;
        
        // Penting: Hapus 'details' dari data utama agar Filament tidak bingung,
        // karena 'details' akan dihandle oleh logic ->relationship()
        // unset($data['details']); // Sebenarnya tidak perlu jika ->relationship() dipakai

        return $data;
    }

    // Method ini berjalan SETELAH PO induk DAN relasi 'details' disimpan
    protected function afterCreate(): void
    {
        Log::info('[CreatePO] Starting afterCreate for PO ID: ' . $this->record->id);
        
        // $this->record adalah PO induk yang baru saja dibuat
        // Relasi 'details' juga sudah dibuat oleh Filament
        
        $totalHargaPO = 0;
        
        // Muat ulang relasi 'details' untuk mendapatkan data yang baru disimpan
        $this->record->load('details'); 

        if ($this->record->details) {
            Log::info('[CreatePO] Found ' . $this->record->details->count() . ' details.');
            foreach ($this->record->details as $detail) {
                // Ambil subtotal dari setiap detail yang sudah disimpan
                Log::info("[CreatePO] Detail ID {$detail->id}, Subtotal: {$detail->subtotal}");
                $totalHargaPO += $detail->subtotal;
            }
        } else {
             Log::warning('[CreatePO] No details found in relationship after create.');
        }

        Log::info('[CreatePO] Calculated Total Harga: ' . $totalHargaPO);

        // Update PO induk dengan total harga yang benar
        $this->record->total_harga = $totalHargaPO;
        $this->record->saveQuietly(); // saveQuietly agar tidak memicu event/loop
        
        Log::info('[CreatePO] Total Harga updated on PO ID: ' . $this->record->id);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Purchase Order berhasil dibuat';
    }
}

