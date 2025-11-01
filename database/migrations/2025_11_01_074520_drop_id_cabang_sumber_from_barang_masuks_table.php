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
        Schema::table('barang_masuks', function (Blueprint $table) {
            // Hanya drop jika kolomnya ada
            if (Schema::hasColumn('barang_masuks', 'id_cabang_sumber')) {
                // 1. Drop foreign key constraint terlebih dahulu
                // Nama constraint default: nama_tabel_nama_kolom_foreign
                $table->dropForeign(['id_cabang_sumber']);
            }
        });

        // Pisahkan DDL untuk drop kolom setelah drop constraint
        Schema::table('barang_masuks', function (Blueprint $table) {
            if (Schema::hasColumn('barang_masuks', 'id_cabang_sumber')) {
                // 2. Drop kolomnya
                $table->dropColumn('id_cabang_sumber');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang_masuks', function (Blueprint $table) {
            // Jika di-rollback, tambahkan kembali kolomnya
            $table->foreignId('id_cabang_sumber')
                ->nullable()
                ->constrained('cabangs')
                ->after('id_supplier'); // Sesuaikan posisi jika perlu
        });
    }
};
