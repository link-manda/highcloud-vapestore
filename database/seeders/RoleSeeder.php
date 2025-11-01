<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role; // Import Role

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat Role Admin
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        // 2. Buat Role Staf
        Role::firstOrCreate(['name' => 'Staf', 'guard_name' => 'web']);

        // Catatan: 'guard_name' => 'web' adalah default untuk aplikasi web.
    }
}
