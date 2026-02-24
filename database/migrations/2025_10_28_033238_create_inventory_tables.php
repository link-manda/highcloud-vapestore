<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Tabel tanpa dependensi (dibuat pertama)
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_cabang');
            $table->text('alamat_cabang')->nullable();
            $table->string('telepon_cabang')->nullable();
            $table->timestamps();
        });

        // 2. Tabel tanpa dependensi
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori')->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // 3. Tabel tanpa dependensi
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_supplier');
            $table->string('kontak_person')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        // 4. Tabel 'users' (dependen ke 'cabangs')
        // Kita modifikasi dari bawaan Laravel
        // 4. Tabel 'users' (dependen ke 'cabangs')
        // Kita modifikasi dari bawaan Laravel
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'staf'])->default('staf')->after('password');

            // Relasi ke Cabang (Nullable untuk Admin)
            $table->foreignId('id_cabang')->nullable()->after('role')->constrained('cabangs')->onDelete('set null');
        });

        // 5. Tabel 'produks' (dependen ke 'kategoris')
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            // Relasi ke Kategori
            $table->foreignId('id_kategori')->constrained('kategoris');
            $table->string('nama_produk'); // Misal: "AIO CENTAURUS"
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // 6. Tabel 'varian_produks' (dependen ke 'produks')
        Schema::create('varian_produks', function (Blueprint $table) {
            $table->id();
            // Relasi ke Produk Induk
            $table->foreignId('id_produk')->constrained('produks')->onDelete('cascade');
            $table->string('nama_varian'); // Misal: "CENTAURUS B60"
            $table->string('sku_code')->unique()->nullable();

            // Menggunakan decimal untuk harga agar presisi
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);

            $table->timestamps();
        });

        // 7. Tabel 'stok_cabangs' (dependen ke 'cabangs' dan 'varian_produks')
        Schema::create('stok_cabangs', function (Blueprint $table) {
            $table->id();
            // Relasi
            $table->foreignId('id_cabang')->constrained('cabangs')->onDelete('cascade');
            $table->foreignId('id_varian_produk')->constrained('varian_produks')->onDelete('cascade');

            // Data Stok
            $table->integer('stok_saat_ini')->default(0);
            $table->integer('stok_minimum')->default(0); // Batas untuk notifikasi

            // Memastikan satu varian hanya punya satu data stok per cabang
            $table->unique(['id_cabang', 'id_varian_produk']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Urutan drop dibalik dari 'up' untuk menghindari error foreign key
        Schema::dropIfExists('stok_cabangs');
        Schema::dropIfExists('varian_produks');
        Schema::dropIfExists('produks');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_cabang']);
            $table->dropColumn(['role', 'id_cabang']);
        });
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('kategoris');
        Schema::dropIfExists('cabangs');
    }
};
