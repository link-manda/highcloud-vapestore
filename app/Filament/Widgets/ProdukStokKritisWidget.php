<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProdukResource;
use App\Models\StokCabang;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Closure;

class ProdukStokKritisWidget extends BaseWidget
{
    // Properti yang mendefinisikan tampilan widget
    protected static ?string $heading = 'Item Stok Kritis (Butuh Segera Restock)';
    protected int | string | array $columnSpan = 'full'; // Agar widget ini mengambil lebar penuh
    protected static ?int $sort = 3; // Urutan widget di dashboard (setelah Stats dan Grafik)

    /**
     * Otorisasi: Hanya Admin yang bisa melihat widget ini
     */
    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    /**
     * Definisi Tabel (Satu-satunya method table())
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Query utama: Ambil data StokCabang
                StokCabang::query()
                    // Muat relasi yang diperlukan untuk ditampilkan
                    ->with(['varianProduk.produk', 'cabang'])

                    // [LOGIKA KRITIS]
                    // Hanya tampilkan item di mana stok saat ini
                    // lebih kecil atau sama dengan stok minimum...
                    ->whereColumn('stok_saat_ini', '<=', 'stok_minimum')
                    // ...dan pastikan stok minimumnya bukan 0 (karena 0 <= 0)
                    ->where('stok_minimum', '>', 0)

                    // Urutkan berdasarkan yang paling kritis (stok paling sedikit)
                    ->orderBy('stok_saat_ini', 'asc')

                    // Batasi hanya 10 item teratas
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('varianProduk.produk.nama_produk')
                    ->label('Nama Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('varianProduk.nama_varian')
                    ->label('Varian')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->badge(), // Tampilkan sebagai badge
                Tables\Columns\TextColumn::make('stok_saat_ini')
                    ->label('Sisa Stok')
                    ->alignEnd()
                    ->numeric()
                    ->color('danger') // Beri warna merah
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('stok_minimum')
                    ->label('Stok Min.')
                    ->alignEnd()
                    ->numeric(),
            ])
            // Jangan tampilkan pagination
            ->paginated(false);
        // [PERBAIKAN] Baris ->header(false); telah dihapus karena menyebabkan TypeError.
    }

    /**
     * [FITUR "ACTIONABLE"]
     * Membuat seluruh baris dapat diklik, mengarahkan ke halaman Edit Produk terkait.
     * (Ini adalah method perbaikan dari error sebelumnya)
     */
    protected function getTableRecordUrlUsing(): ?Closure
    {
        return fn(StokCabang $record): string =>
        ProdukResource::getUrl('edit', ['record' => $record->varianProduk->produk]);
    }
}
