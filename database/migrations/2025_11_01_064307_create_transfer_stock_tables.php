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
        // Tabel Induk Transfer
        Schema::create('transfer_stoks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transfer')->unique();
            $table->date('tanggal_transfer');

            $table->foreignId('id_cabang_sumber')->constrained('cabangs')->comment('Cabang asal barang');
            $table->foreignId('id_cabang_tujuan')->constrained('cabangs')->comment('Cabang tujuan barang');
            $table->foreignId('id_user_pembuat')->constrained('users')->comment('User yang mencatat');

            $table->text('catatan')->nullable();
            $table->timestamps();

            // Tambahkan constraint agar sumber dan tujuan tidak boleh sama (jika didukung database Anda)
            // $table->check('id_cabang_sumber <> id_cabang_tujuan'); 
        });

        // Tabel Detail Item Transfer
        Schema::create('transfer_stok_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_transfer_stok')->constrained('transfer_stoks')->cascadeOnDelete();
            $table->foreignId('id_varian_produk')->constrained('varian_produks');
            $table->integer('jumlah');

            // Kita tidak perlu harga di sini sesuai rancangan

            $table->timestamps();

            // Pastikan satu varian hanya ada satu kali per transfer
            $table->unique(['id_transfer_stok', 'id_varian_produk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_stok_details');
        Schema::dropIfExists('transfer_stoks');
    }
};
