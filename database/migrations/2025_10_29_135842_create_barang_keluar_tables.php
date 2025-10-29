<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel Induk Barang Keluar
        Schema::create('barang_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi')->unique(); // BK-YYYYMMDD-XXXX
            $table->dateTime('tanggal_keluar');
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete(); // Cabang asal barang
            $table->foreignId('id_user')->constrained('users')->restrictOnDelete(); // User pencatat
            $table->string('nama_pelanggan')->nullable(); // Opsional
            $table->text('catatan')->nullable(); // Opsional
            $table->timestamps();
        });

        // Tabel Detail Item Barang Keluar
        Schema::create('barang_keluar_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barang_keluar')->constrained('barang_keluars')->cascadeOnDelete(); // Relasi ke induk
            $table->foreignId('id_varian_produk')->constrained('varian_produks')->restrictOnDelete(); // Varian/SKU yang keluar
            $table->integer('jumlah'); // Kuantitas keluar
            $table->decimal('harga_jual_saat_transaksi', 15, 2); // Catat harga jual saat itu
            $table->decimal('subtotal', 15, 2); // jumlah * harga_jual_saat_transaksi
            $table->timestamps();

             // Index untuk performa query
            $table->index('id_barang_keluar');
            $table->index('id_varian_produk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_keluar_details');
        Schema::dropIfExists('barang_keluars');
    }
};
