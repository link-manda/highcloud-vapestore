<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::table('stok_cabangs', function (Blueprint $table) {
        //     // Pastikan kombinasi cabang dan varian produk unik
        //     $table->unique(['id_cabang', 'id_varian_produk'], 'unique_cabang_varian');
        // });
    }

    public function down(): void
    {
        // Schema::table('stok_cabangs', function (Blueprint $table) {
        //     $table->dropUnique('unique_cabang_varian');
        // });
    }
};
