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

        // Sisa seeder Anda...
        // User::factory(10)->create();

        // Anda bisa nonaktifkan factory default jika Anda buat user manual
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@highcloud.com',
            'password' => bcrypt('password'), // Ganti password jika perlu
            // 'id_cabang' => 1, // Opsional, set jika perlu
        ]);

        // [PENTING] Assign role ke user pertama
        $user = User::where('email', 'admin@highcloud.com')->first();
        if ($user) {
            $user->assignRole('Admin'); // Assign role 'Admin'
        }
    }
}
