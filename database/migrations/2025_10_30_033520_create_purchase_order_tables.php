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
        // Tabel Induk Purchase Order
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_po')->unique();
            $table->date('tanggal_po');
            $table->foreignId('id_supplier')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('id_cabang_tujuan')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_user_pembuat')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_estimasi_tiba')->nullable();
            $table->enum('status', ['Draft', 'Submitted', 'Partially Received', 'Completed', 'Cancelled'])->default('Draft');
            $table->text('catatan')->nullable();
            $table->decimal('total_harga', 15, 2)->default(0); // Menyimpan total harga PO
            $table->timestamps();
        });

        // Tabel Detail Item Purchase Order
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_purchase_order')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('id_varian_produk')->constrained('varian_produks')->cascadeOnDelete();
            $table->integer('jumlah_pesan');
            $table->decimal('harga_beli_saat_po', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->integer('jumlah_diterima')->default(0); // Jumlah yg sudah diterima via Barang Masuk
            $table->timestamps();

            // Mencegah duplikasi varian dalam satu PO
            $table->unique(['id_purchase_order', 'id_varian_produk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_details');
        Schema::dropIfExists('purchase_orders');
    }
};
