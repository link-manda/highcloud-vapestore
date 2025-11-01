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
        // Tabel Induk Transaksi Barang Masuk
        Schema::create('barang_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi')->unique(); // Contoh: BM-20251029-0001
            $table->dateTime('tanggal_masuk');

            // Sumber Barang (Pilih salah satu)
            $table->foreignId('id_supplier')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('id_cabang_sumber')->nullable()->constrained('cabangs')->nullOnDelete(); // Untuk transfer antar cabang

            // Tujuan Barang
            $table->foreignId('id_cabang_tujuan')->constrained('cabangs')->restrictOnDelete(); // Cabang penerima

            // Pencatat
            $table->foreignId('id_user')->constrained('users')->restrictOnDelete();

            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // Tabel Detail Item Barang Masuk
        Schema::create('barang_masuk_details', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel Induk
            $table->foreignId('id_barang_masuk')->constrained('barang_masuks')->cascadeOnDelete();

            // Item yang masuk
            $table->foreignId('id_varian_produk')->constrained('varian_produks')->restrictOnDelete();

            $table->integer('jumlah'); // Kuantitas
            $table->decimal('harga_beli_saat_transaksi', 15, 2); // Catat harga beli saat itu
            $table->decimal('subtotal', 15, 2); // jumlah * harga_beli

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_masuk_details');
        Schema::dropIfExists('barang_masuks');
    }
};
