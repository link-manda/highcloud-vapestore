# Laporan QA Upload File

Tanggal audit: 2026-07-01  
Project: HighCloud VapeStore  
Scope: semua fungsi upload file pada modul Laravel/Filament

## Ringkasan

Upload file hanya ditemukan pada modul Cabang.

| Modul | File | Field | Status |
|---|---|---|---|
| Cabang | `app/Filament/Resources/CabangResource.php` | `image` / Foto Cabang | Ada temuan |

Tidak ditemukan fungsi upload lain di `app/`, `resources/`, `routes/`, `config/`, dan `database` selain `FileUpload::make('image')` pada `CabangResource`.

## Informasi Field Upload File

| Properti | Nilai saat ini | Catatan QA |
|---|---|---|
| Modul | Cabang | Form create/edit Cabang |
| Field database | `image` | Tersimpan sebagai path file |
| Komponen Filament | `FileUpload::make('image')` | Field upload utama |
| Label UI | `Foto Cabang` | Sudah jelas untuk user |
| Validasi dasar | `->image()` | Perlu dipin MIME aman eksplisit |
| Direktori storage | `cabang-images` | Path statis, aman dari input user |
| Visibility | `public` | Sesuai karena foto Cabang tampil publik |
| Resize | `imageResizeTargetWidth('500')` | Ada resize lebar, belum ada batas ukuran upload |
| Preview | `imagePreviewHeight('150')` | Preview tersedia di form |
| Batas ukuran | Belum ada `maxSize()` | Perlu standar eksplisit |
| Tipe file | Belum ada `acceptedFileTypes()` | Perlu allowlist JPEG/PNG/WebP |
| Cleanup file lama | Belum ada | Perlu policy hapus/retensi |

## Informasi Field Lokasi Cabang

Schema/model sudah punya field koordinat:

- `latitude`
- `longitude`

Bukti:

- `app/Models/Cabang.php` sudah fillable `latitude` dan `longitude`.
- `database/migrations/2026_05_14_123617_add_coordinates_to_cabangs_table.php` menambahkan kolom `latitude` dan `longitude`.
- `resources/views/components/gallery/location-map.blade.php` memakai koordinat untuk map publik.

Namun form Cabang saat ini hanya punya:

- `nama_cabang`
- `alamat_cabang`
- `telepon_cabang`
- `image`

Field koordinat belum tersedia di halaman menu Cabang.

---

## Temuan QA

### QA-UPLOAD-001 — Staff Berpotensi Akses Langsung Halaman Upload Cabang

**Severity:** High  
**Category:** Authorization / Upload Access Control  
**File:**

- `app/Filament/Resources/CabangResource.php:53`
- `app/Filament/Resources/CabangResource.php:97`
- `app/Filament/Resources/CabangResource.php:98`
- `app/Filament/Resources/CabangResource.php:101`
- `app/Filament/Resources/CabangResource.php:116`

**Bukti kode:**

`CabangResource` hanya membatasi daftar/menu lewat `canViewAny()`:

```php
public static function canViewAny(): bool
{
    return auth()->user()->hasRole('Admin');
}
```

Namun resource tetap mendaftarkan route create/edit:

```php
'create' => Pages\CreateCabang::route('/create'),
'edit' => Pages\EditCabang::route('/{record}/edit'),
```

Action edit/delete juga tersedia:

```php
Tables\Actions\EditAction::make(),
Tables\Actions\DeleteAction::make(),
Tables\Actions\DeleteBulkAction::make(),
```

Tidak ditemukan guard eksplisit:

- `canCreate()`
- `canEdit()`
- `canDelete()`
- `canDeleteAny()`

**Dampak:**

Jika Filament default atau policy tidak menolak route/action tersebut, user `Staf` dapat mencoba akses langsung ke URL create/edit Cabang walau menu Cabang tidak tampil. Ini dapat membuka akses untuk membuat/mengubah Cabang dan upload Foto Cabang.

**Skenario QA:**

1. Login sebagai `Staf`.
2. Buka langsung `/admin/cabangs/create`.
3. Buka langsung `/admin/cabangs/{id}/edit`.
4. Coba upload/ganti Foto Cabang.
5. Coba hapus Cabang dari action halaman edit/list jika bisa diakses.

**Ekspektasi:**

Semua akses create/edit/delete Cabang untuk `Staf` harus ditolak dengan 403 atau redirect aman.

**Rekomendasi fix:**

Tambahkan guard eksplisit di `CabangResource`:

```php
use Illuminate\Database\Eloquent\Model;

public static function canCreate(): bool
{
    return auth()->user()->hasRole('Admin');
}

public static function canEdit(Model $record): bool
{
    return auth()->user()->hasRole('Admin');
}

public static function canDelete(Model $record): bool
{
    return auth()->user()->hasRole('Admin');
}

public static function canDeleteAny(): bool
{
    return auth()->user()->hasRole('Admin');
}
```

**Confidence:** 8/10

---

### QA-UPLOAD-002 — Validasi Tipe File Upload Masih Terlalu Implisit

**Severity:** Medium  
**Category:** File Validation / Public Upload Hardening  
**File:**

- `app/Filament/Resources/CabangResource.php:53`

**Bukti kode:**

```php
FileUpload::make('image')
    ->label('Foto Cabang')
    ->image()
    ->directory('cabang-images')
    ->visibility('public')
```

`->image()` memberi validasi image, tetapi MIME aman tidak dipin eksplisit. Untuk file public, daftar MIME sebaiknya dibatasi hanya format raster aman.

**Dampak:**

Jika format aktif seperti SVG diterima oleh stack upload/runtime, file dapat tersimpan di storage public. Walau exploit langsung tidak otomatis ditemukan, ini risiko hardening yang layak ditutup untuk upload public.

**Skenario QA:**

| File | Ekspektasi |
|---|---|
| `.jpg` valid | Diterima |
| `.png` valid | Diterima |
| `.webp` valid | Diterima |
| `.svg` | Ditolak |
| `.php` rename ke `.jpg` | Ditolak |
| file teks rename ke `.png` | Ditolak |

**Rekomendasi fix:**

Tambahkan MIME allowlist:

```php
->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
```

**Confidence security exploit langsung:** 6/10  
**Confidence QA hardening:** 9/10

---

### QA-UPLOAD-003 — Batas Ukuran Upload Tidak Eksplisit

**Severity:** Low  
**Category:** Upload Validation / Product QA  
**File:**

- `app/Filament/Resources/CabangResource.php:53`

**Bukti kode:**

Upload Foto Cabang tidak memakai `maxSize()`.

```php
->imageResizeTargetWidth('500')
```

Resize ada, tetapi batas upload masih bergantung pada default PHP/Livewire/environment. QA dan production behavior bisa berbeda antar environment.

**Dampak:**

Batas ukuran file tidak konsisten dan tidak terdokumentasi di level modul.

**Skenario QA:**

Coba upload:

- gambar 5 MB
- gambar 10 MB
- gambar 25 MB

Ekspektasi perlu ditentukan oleh aplikasi, bukan default environment.

**Rekomendasi fix:**

Tambahkan batas eksplisit, contoh:

```php
->maxSize(2048)
```

Gunakan `5120` jika butuh toleransi file kamera lebih besar.

**Confidence:** 8/10

---

### QA-UPLOAD-004 — File Lama Tidak Dibersihkan Saat Foto Diganti atau Cabang Dihapus

**Severity:** Low  
**Category:** Orphan File Cleanup  
**File:**

- `app/Filament/Resources/CabangResource.php:53`
- `app/Filament/Resources/CabangResource/Pages/EditCabang.php:13`

**Bukti kode:**

Tidak ditemukan cleanup storage eksplisit saat Foto Cabang diganti atau Cabang dihapus:

- tidak ada `Storage::delete()`
- tidak ada observer model
- tidak ada `deleteUploadedFileUsing()`

**Dampak:**

File lama dapat tetap berada di public storage dan masih bisa diakses jika URL pernah tersebar. Ini lebih tepat sebagai temuan QA/maintenance daripada security vulnerability langsung, karena Foto Cabang memang public.

**Skenario QA:**

1. Upload foto A pada Cabang.
2. Ganti dengan foto B.
3. Cek storage `cabang-images`.
4. Foto A kemungkinan masih ada.
5. Hapus Cabang.
6. Foto B kemungkinan masih ada.

**Rekomendasi fix:**

Tambahkan cleanup saat replace/delete, misalnya lewat hook page/model observer setelah update/delete sukses.

**Confidence:** 7/10

---

### QA-UPLOAD-005 — Field Latitude/Longitude Cabang Belum Tersedia di Form

**Severity:** Medium  
**Category:** Data Completeness / Location Mapping  
**File:**

- `app/Filament/Resources/CabangResource.php:32`
- `app/Models/Cabang.php:23`
- `app/Models/Cabang.php:24`
- `database/migrations/2026_05_14_123617_add_coordinates_to_cabangs_table.php:15`
- `database/migrations/2026_05_14_123617_add_coordinates_to_cabangs_table.php:16`
- `resources/views/components/gallery/location-map.blade.php:13`

**Bukti kode:**

Model dan database sudah mendukung koordinat Cabang:

```php
'latitude',
'longitude',
```

Migration juga menambahkan kolom:

```php
$table->decimal('latitude', 10, 8)->nullable();
$table->decimal('longitude', 11, 8)->nullable();
```

Storefront map memakai koordinat tersebut:

```blade
focusMap({{ $cabang->latitude ?? -8.65 }}, {{ $cabang->longitude ?? 115.22 }})
```

Namun form Cabang belum menyediakan input `latitude` dan `longitude`.

**Dampak:**

Admin tidak bisa mengisi koordinat Cabang dari UI. Map publik akhirnya memakai fallback koordinat, sehingga lokasi Cabang bisa tidak akurat atau beberapa Cabang tampak di titik default yang sama.

**Skenario QA:**

1. Buka menu Cabang.
2. Buat atau edit Cabang.
3. Cari field latitude/longitude.
4. Field tidak tersedia.
5. Buka landing page dan cek map Cabang.
6. Cabang tanpa koordinat memakai fallback lokasi default.

**Rekomendasi fix:**

Tambahkan field koordinat di form Cabang:

```php
TextInput::make('latitude')
    ->numeric()
    ->minValue(-90)
    ->maxValue(90),

TextInput::make('longitude')
    ->numeric()
    ->minValue(-180)
    ->maxValue(180),
```

Opsional lebih nyaman: tambahkan helper link Google Maps atau field paste koordinat, tetapi jangan buat map picker dulu kecuali diminta.

**Confidence:** 10/10

---

## Hal yang Aman

| Area | Status |
|---|---|
| Path upload | Aman: `directory('cabang-images')` statis, bukan input user |
| Filename | Aman: tidak ada `preserveFilenames()` |
| Public visibility | Sesuai fungsi Foto Cabang publik, bukan data rahasia |
| Blade display | Tidak ditemukan raw HTML injection dari nama/path image |
| Upload bebas | Tidak ada upload dokumen bebas, ZIP, PDF, Excel, atau arbitrary file upload |

---

## Checklist QA Manual Upload

### Authorization

- [x] Login sebagai `Staf`.
- [x] Buka `/admin/cabangs/create`.
- [x] Buka `/admin/cabangs/{id}/edit`.
- [x] Pastikan akses ditolak.
- [x] Coba delete Cabang jika action bisa dijangkau.

### Validasi File

- [x] Upload `.jpg` valid: diterima.
- [x] Upload `.png` valid: diterima.
- [x] Upload `.webp` valid: diterima.
- [x] Upload `.svg`: ditolak.
- [x] Upload `.php` rename `.jpg`: ditolak.
- [x] Upload file teks rename `.png`: ditolak.
- [x] Upload file melebihi batas ukuran: ditolak dengan pesan jelas.

### Storage

- [x] Ganti foto Cabang dan cek file lama.
- [x] Hapus Cabang dan cek file foto.
- [x] Pastikan URL `/storage/cabang-images/...` hanya dipakai untuk file yang memang public.

### Lokasi Cabang

- [x] Buka form create/edit Cabang.
- [x] Pastikan field `latitude` tersedia.
- [x] Pastikan field `longitude` tersedia.
- [x] Isi koordinat valid dan simpan.
- [x] Buka landing page dan pastikan marker Cabang memakai koordinat tersebut.
- [x] Coba nilai invalid: latitude di luar -90 sampai 90, longitude di luar -180 sampai 180.

---

## Prioritas Perbaikan

### P0 (✅ SELESAI)

1. [x] Tambahkan `canCreate()`, `canEdit()`, `canDelete()`, dan `canDeleteAny()` di `CabangResource`.

### P1 (✅ SELESAI)

1. [x] Tambahkan `acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])`.
2. [x] Tambahkan `maxSize()` eksplisit.
3. [x] Tambahkan field `latitude` dan `longitude` di form Cabang dengan validasi range.

### P2 (✅ SELESAI)

1. [x] Tambahkan cleanup orphan file saat Foto Cabang diganti atau Cabang dihapus.
