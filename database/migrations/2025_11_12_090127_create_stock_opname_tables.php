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
        // Tabel stock_opnames
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_opname');
            $table->foreignId('id_petugas')->constrained('users')->onDelete('restrict');
            $table->foreignId('id_cabang')->constrained('cabangs')->onDelete('cascade');
            $table->text('catatan')->nullable();
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamps();
        });

        // Tabel stock_opname_details
        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_stock_opname')->constrained('stock_opnames')->onDelete('cascade');
            $table->foreignId('id_varian_produk')->constrained('varian_produks')->onDelete('cascade');
            $table->integer('stok_sistem');
            $table->integer('stok_fisik');
            $table->integer('selisih');
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Unique constraint untuk mencegah duplikasi varian dalam satu opname
            $table->unique(['id_stock_opname', 'id_varian_produk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_details');
        Schema::dropIfExists('stock_opnames');
    }
};
