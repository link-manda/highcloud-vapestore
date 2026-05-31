# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

HighCloud VapeStore — multi-branch inventory and retail management system. Laravel 11 (PHP 8.2+) backend, Filament v3.3 admin panel (Livewire), MySQL, Spatie Laravel Permission for roles. Code, comments, and UI text are primarily in Indonesian — match this convention.

## Commands

| Task | Command |
| :--- | :--- |
| Serve app | `php artisan serve` |
| Asset dev watch | `npm run dev` |
| Asset prod build | `npm run build` |
| Migrate | `php artisan migrate` |
| Seed | `php artisan db:seed` |
| Fresh DB + seed | `php artisan migrate:fresh --seed` |
| Run all tests | `php artisan test` |
| Run single test | `php artisan test --filter=TestMethodName` |
| Run one suite | `php artisan test --testsuite=Feature` |
| Lint/format PHP | `./vendor/bin/pint` |
| New Filament resource | `php artisan make:filament-resource {Name}` |

Tests use PHPUnit (`tests/Unit`, `tests/Feature`). Only example tests exist so far.

## Database notes

- Runtime DB is **MySQL** (`.env`). `.env.example` ships `DB_CONNECTION=sqlite` — do not assume sqlite.
- `php artisan migrate:fresh --seed` creates an admin: `admin@highcloud.com` / `password` (assigned Spatie `Admin` role).
- Schema and FK constraints are defined across ordered migrations; `create_inventory_tables` is the core schema (cabangs, kategoris, suppliers, produks, varian_produks, stok_cabangs + the `users.role`/`users.id_cabang` columns).

## Architecture

### Domain model (inventory flow)

Table/column names are Indonesian. Core entities:

- `Cabang` (branch) — multi-branch is the central concept. Most data is scoped by `id_cabang`.
- `Kategori` → `Produk` → `VarianProduk` (category → product → variant). Variants carry SKU, `harga_beli`, `harga_jual`.
- `StokCabang` — stock level per (`id_cabang`, `id_varian_produk`), unique pair. Holds `stok_saat_ini` and `stok_minimum`. Has `tambahStok()` / `kurangiStok()` helpers (`kurangiStok` throws if it would go negative).
- Transaction tables, each with a `*Detail` child (header/detail pattern): `BarangMasuk` (goods in), `BarangKeluar` (goods out/sales), `PurchaseOrder`, `TransferStok` (inter-branch), `StockOpname` (stock count/audit).

### Stock mutation pattern (critical)

Stock changes are NOT in the models — they live in Filament resource **Page** classes (`Create*`/`Edit*` under `app/Filament/Resources/{Resource}/Pages/`). Follow the pattern in `CreateBarangKeluar.php`:

1. `mutateFormDataBeforeCreate()` — inject `id_user`, scope `id_cabang` for staff, generate document numbers (e.g. `BK-YYYYMMDD-0001` via prefix + zero-padded sequence lookup).
2. `beforeCreate()` — re-validate stock availability right before save to guard against race conditions; throw `ValidationException` on insufficient stock.
3. `afterCreate()` — wrap detail-record creation + stock increment/decrement in `DB::beginTransaction()` / `commit()` / `rollBack()`. On failure, roll back AND delete the parent record. Low-stock notifications (`StokMinimumNotification`) fire here, after the stock change.

When adding or editing any stock-affecting transaction, keep this three-phase structure and always use DB transactions across the header, detail, and `stok_cabangs` tables.

### Roles (two parallel systems — be careful)

There are TWO role mechanisms and they do NOT share casing/values:

1. `users.role` enum column: lowercase `'admin'` / `'staf'`. Business logic branches on this (e.g. `$user->role === 'staf'` to scope `id_cabang`).
2. Spatie roles: capitalized `'Admin'` / `'Staf'` (seeded in `RoleSeeder`, assigned via `assignRole()`).

Check which mechanism existing code uses before adding permission logic. Staff are branch-scoped; admins are not (`users.id_cabang` is nullable for admins).

### Filament admin panel

- Single panel, path `/admin`, custom login `App\Filament\Auth\CustomLogin`. Defined in `app/Providers/Filament/AdminPanelProvider.php`.
- Navigation groups (set `$navigationGroup` on resources to match): `Data Master`, `Transaksi Inventori`, `Laporan`, `Manajemen Sistem`.
- Resources auto-discovered from `app/Filament/Resources`, pages from `app/Filament/Pages`, widgets from `app/Filament/Widgets`.
- `app/Filament/Pages/Laporan*` are report pages; `app/Filament/Exports/*Exporter.php` are Filament CSV/Excel exporters; widgets include dashboard charts and a critical-stock widget.
- Primary color is Violet; theme is "Neon Monsoon" (charcoal `#060e20`, neon purple `#ba9eff`, cyan `#53ddfc`, glassmorphism). Custom sidebar CSS is injected via `renderHook` in the panel provider.

### Public storefront

- `routes/web.php` — single public route `/` rendering `resources/views/pages/home.blade.php` with all `Cabang` records (for the location map).
- Blade components in `resources/views/components/` (incl. a `gallery/` subset) build the landing page. PDF report templates live in `resources/views/pdf/`.

## Workflow conventions

- Use migrations for all schema changes; never edit the DB manually.
- Filament resource edits stay surgical — focus on `form()`, `table()`, `infolist()`.
- Run `./vendor/bin/pint` before considering PHP work done.
- `barryvdh/laravel-dompdf` generates PDFs from `resources/views/pdf/`.
