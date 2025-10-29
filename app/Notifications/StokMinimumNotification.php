<?php

namespace App\Notifications;

use App\Models\Cabang;
use App\Models\StokCabang;
use App\Models\VarianProduk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str; // Import Str facade

class StokMinimumNotification extends Notification implements ShouldQueue // Implement ShouldQueue agar pengiriman email tidak blocking
{
    use Queueable;

    public StokCabang $stokCabang;
    public VarianProduk $varianProduk;
    public Cabang $cabang;

    /**
     * Create a new notification instance.
     *
     * @param StokCabang $stokCabang Record stok yang mencapai batas minimum
     */
    public function __construct(StokCabang $stokCabang)
    {
        $this->stokCabang = $stokCabang;
        // Eager load relasi untuk efisiensi
        $this->varianProduk = $stokCabang->varianProduk()->with('produk')->firstOrFail();
        $this->cabang = $stokCabang->cabang()->firstOrFail();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Kita akan mengirim via email. Bisa ditambahkan 'database' jika ingin notif in-app
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $namaProdukLengkap = "{$this->varianProduk->produk->nama_produk} - {$this->varianProduk->nama_varian}";
        $namaCabang = $this->cabang->nama_cabang;
        $stokSaatIni = $this->stokCabang->stok_saat_ini;
        $stokMinimum = $this->stokCabang->stok_minimum;

        return (new MailMessage)
            ->subject(Str::title("Peringatan Stok Minimum: {$namaProdukLengkap} di {$namaCabang}")) // Judul email
            ->greeting(Str::title("Halo {$notifiable->name},")) // Sapaan personal
            ->line("Stok untuk produk **{$namaProdukLengkap}** di cabang **{$namaCabang}** telah mencapai atau di bawah batas minimum.")
            ->line("Stok Saat Ini: **{$stokSaatIni}**")
            ->line("Batas Minimum: **{$stokMinimum}**")
            ->line("Mohon segera lakukan pengecekan dan pertimbangkan untuk melakukan pengadaan barang (restock).")
            ->action('Lihat Stok Varian', url(route('filament.admin.resources.varian-produks.view', ['record' => $this->varianProduk->id]))) // Link ke halaman view varian
            ->line('Terima kasih.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Representasi array jika menggunakan channel 'database'
        return [
            'id_stok_cabang' => $this->stokCabang->id,
            'nama_produk' => "{$this->varianProduk->produk->nama_produk} - {$this->varianProduk->nama_varian}",
            'nama_cabang' => $this->cabang->nama_cabang,
            'stok_saat_ini' => $this->stokCabang->stok_saat_ini,
            'stok_minimum' => $this->stokCabang->stok_minimum,
            'message' => "Stok {$this->varianProduk->nama_varian} di {$this->cabang->nama_cabang} mencapai batas minimum.",
        ];
    }
}
