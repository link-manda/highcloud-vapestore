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
            // Tambah kolom foreign key ke purchase_orders setelah kolom id_supplier
            // Nullable karena barang masuk bisa saja tidak berasal dari PO (misal: stok awal, transfer)
            // set null on delete: jika PO dihapus, referensi di barang masuk jadi null (tidak ikut terhapus)
            $table->foreignId('id_purchase_order')->nullable()->after('id_supplier')->constrained('purchase_orders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang_masuks', function (Blueprint $table) {
            // Hapus constraint foreign key dulu sebelum drop kolom
            $table->dropForeign(['id_purchase_order']);
            $table->dropColumn('id_purchase_order');
        });
    }
};
