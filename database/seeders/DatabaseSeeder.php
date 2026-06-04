<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil RoleSeeder yang baru kita buat
        $this->call(RoleSeeder::class);

        // Data pengujian: cabang, supplier, kategori, produk, varian, stok
        $this->call(TestDataSeeder::class);

        // Admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@highcloud.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // [PENTING] Assign role ke user pertama
        $user = User::where('email', 'admin@highcloud.com')->first();
        if ($user) {
            $user->assignRole('Admin');
        }
    }
}
