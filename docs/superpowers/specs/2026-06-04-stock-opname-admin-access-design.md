# Stock Opname Admin Access Fix — Design Spec

**Date:** 2026-06-04
**Status:** Draft

## Problem

Admin user (`admin@highcloud.com`) tidak bisa akses Stock Opname. Menu tampil di sidebar tapi halaman index return 403.

## Root Cause

Dual role system tidak sinkron saat seeding:

| Layer | Admin User Value | Yang dicek StockOpnameResource |
|-------|-----------------|-------------------------------|
| `users.role` (enum column) | `'staf'` (default DB) | `$user->role === 'admin'` |
| Spatie role (`assignRole`) | `'Admin'` | Tidak dicek |

`DatabaseSeeder` pakai `User::factory()->create()` — factory TIDAK set `role`. Migration default `users.role = 'staf'`. Hasil: admin user dapat Spatie `Admin` role tapi column `role` tetap `staf`. StockOpnameResource (dan Pages-nya) hanya cek `$user->role === 'admin'` untuk memberi akses penuh. Karena nilainya `'staf'`, flow jatuh ke path staf — yang butuh `id_cabang` terisi. Admin punya `id_cabang = null` → 403.

Resource lain (BarangKeluar, BarangMasuk, TransferStok) tidak kena — mereka cuma cek `staf` untuk scoping, jadi admin dengan `role = 'staf'` tetap bisa akses (masuk path default/admin).

## Affected Files

### StockOpname (5 files, ~20 check points)

1. `app/Filament/Resources/StockOpnameResource.php` — 12x `$user->role` check
2. `app/Filament/Resources/StockOpnameResource/Pages/ListStockOpnames.php` — 3x
3. `app/Filament/Resources/StockOpnameResource/Pages/CreateStockOpname.php` — 2x
4. `app/Filament/Resources/StockOpnameResource/Pages/EditStockOpname.php` — 2x
5. `app/Filament/Resources/StockOpnameResource/Pages/ViewStockOpname.php` — 3x

### Seeder (1 file)

6. `database/seeders/DatabaseSeeder.php` — factory create tidak set `role`

## Design

### Principle

Jangan refactor dual-role system — itu scope terpisah. Tambah fallback: setiap `$user->role === 'admin'` juga cek `$user->hasRole('Admin')` (Spatie). Admin yang sudah diperbaiki seedernya akan lolos kedua check. Admin existing yang column `role` masih `'staf'` tetap bisa akses via Spatie fallback.

### Pattern

```php
// Before
if ($user->role === 'admin') {
    // full access
}

// After
if ($user->role === 'admin' || $user->hasRole('Admin')) {
    // full access
}
```

### Changes

#### 1. DatabaseSeeder — tambah `role` column

```php
User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@highcloud.com',
    'password' => bcrypt('password'),
    'role' => 'admin',  // <-- TAMBAH INI
]);
```

#### 2. StockOpnameResource — helper method + ganti semua check

Tambah **public static** helper di `StockOpnameResource` (bukan private — Pages perlu akses dari luar):

```php
public static function isAdmin(): bool
{
    $user = auth()->user();
    return $user && ($user->role === 'admin' || $user->hasRole('Admin'));
}

public static function isStaf(): bool
{
    $user = auth()->user();
    return $user && ($user->role === 'staf' || $user->hasRole('Staf'));
}
```

Ganti SEMUA `$user->role === 'admin'` dengan `StockOpnameResource::isAdmin()`.
Ganti SEMUA `$user->role === 'staf'` dengan `StockOpnameResource::isStaf()`.

Di `StockOpnameResource.php` sendiri, bisa pakai `static::isAdmin()` / `static::isStaf()` (resolve ke self). Di Pages, panggil `StockOpnameResource::isAdmin()`.

#### 3. Pages — semua check role lewat Resource helper

- `ListStockOpnames.php`: `mount()` line 18, 23; `getHeaderActions()` line 35
- `CreateStockOpname.php`: `mount()` line 18, 23; `mutateFormDataBeforeCreate()` line 40
- `EditStockOpname.php`: `mount()` line 20, 25
- `ViewStockOpname.php`: `mount()` line 21, 26, 41; `getHeaderActions()` line 48

### Tidak disentuh

- Resource lain (BarangKeluar, BarangMasuk, TransferStok) — tidak ada `'admin'` check, hanya `'staf'`. Tidak kena bug ini.
- `users.role` column — tetap ada, tidak dihapus.
- Spatie permission tables — tidak diubah.

## Verification

1. `php artisan migrate:fresh --seed` → login sebagai `admin@highcloud.com` / `password`
2. Buka `/admin/stock-opnames` → harus tampil (tidak 403)
3. Buat Stock Opname baru → form tampil, bisa pilih cabang
4. View, Edit, Complete action → semua berfungsi
5. Login sebagai staf → tetap terbatas ke cabang sendiri
