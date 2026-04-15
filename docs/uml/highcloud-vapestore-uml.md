# Highcloud Vapestore UML

Dokumen ini merangkum desain UML berdasarkan analisis source code dan struktur root project Laravel + Filament.

## 1) Architecture Component UML

```mermaid
flowchart TB
    UI[Filament Admin Panel\nResources Pages Widgets]
    APP[Laravel Application Layer\nControllers Resources Pages\nPolicies Notifications]
    DOMAIN[Domain Models\nMaster Data + Transaction + Stock]
    DB[(MySQL/PostgreSQL\nInventory Tables)]
    AUTH[Spatie Permission\nRole Admin/Staf]
    QUEUE[Queue Worker]
    MAIL[Mail Service]

    UI --> APP
    APP --> DOMAIN
    DOMAIN --> DB
    APP --> AUTH
    APP --> QUEUE
    QUEUE --> MAIL
```

## 2) Package UML (Bounded Context)

```mermaid
classDiagram
    class MasterData {
      Kategori
      Produk
      VarianProduk
      Cabang
      Supplier
      User
    }

    class StockTracking {
      StokCabang
      +tambahStok(jumlah)
      +kurangiStok(jumlah)
      +stokExists(cabangId,varianProdukId)
    }

    class Procurement {
      PurchaseOrder
      PurchaseOrderDetail
      BarangMasuk
      BarangMasukDetail
    }

    class Distribution {
      BarangKeluar
      BarangKeluarDetail
    }

    class Transfer {
      TransferStok
      TransferStokDetail
    }

    class Audit {
      StockOpname
      StockOpnameDetail
    }

    class Notification {
      StokMinimumNotification
    }

    MasterData <.. StockTracking : reference
    MasterData <.. Procurement : reference
    MasterData <.. Distribution : reference
    MasterData <.. Transfer : reference
    MasterData <.. Audit : reference

    StockTracking ..> Notification : low stock event
    Procurement ..> StockTracking : increase stock
    Distribution ..> StockTracking : decrease stock
    Transfer ..> StockTracking : move stock
    Audit ..> StockTracking : reconcile stock
```

## 3) Domain Class UML (Entity + Cardinality)

```mermaid
classDiagram
    class Kategori {
      +id
      +nama_kategori
      +deskripsi
    }
    class Produk {
      +id
      +id_kategori
      +nama_produk
      +deskripsi
    }
    class VarianProduk {
      +id
      +id_produk
      +nama_varian
      +sku_code
      +harga_beli
      +harga_jual
    }
    class Cabang {
      +id
      +nama_cabang
    }
    class User {
      +id
      +id_cabang
      +role
      +name
      +email
    }
    class Supplier {
      +id
      +nama_supplier
    }
    class StokCabang {
      +id
      +id_cabang
      +id_varian_produk
      +stok_saat_ini
      +stok_minimum
    }

    class PurchaseOrder {
      +id
      +nomor_po
      +status
      +id_supplier
      +id_cabang_tujuan
      +id_user_pembuat
    }
    class PurchaseOrderDetail {
      +id
      +id_purchase_order
      +id_varian_produk
      +jumlah_pesan
      +jumlah_diterima
    }

    class BarangMasuk {
      +id
      +nomor_transaksi
      +id_supplier
      +id_cabang_tujuan
      +id_user
      +id_purchase_order
    }
    class BarangMasukDetail {
      +id
      +id_barang_masuk
      +id_varian_produk
      +jumlah
      +harga_beli_saat_transaksi
      +subtotal
    }

    class BarangKeluar {
      +id
      +nomor_transaksi
      +id_cabang
      +id_user
      +nama_pelanggan
    }
    class BarangKeluarDetail {
      +id
      +id_barang_keluar
      +id_varian_produk
      +jumlah
      +harga_jual_saat_transaksi
      +subtotal
    }

    class TransferStok {
      +id
      +nomor_transfer
      +id_cabang_sumber
      +id_cabang_tujuan
      +id_user_pembuat
    }
    class TransferStokDetail {
      +id
      +id_transfer_stok
      +id_varian_produk
      +jumlah
    }

    class StockOpname {
      +id
      +tanggal_opname
      +id_petugas
      +id_cabang
      +status
    }
    class StockOpnameDetail {
      +id
      +id_stock_opname
      +id_varian_produk
      +stok_sistem
      +stok_fisik
      +selisih
    }

    Kategori "1" --> "0..*" Produk : hasMany
    Produk "1" --> "0..*" VarianProduk : hasMany
    Cabang "1" --> "0..*" User : hasMany
    Cabang "1" --> "0..*" StokCabang : hasMany
    VarianProduk "1" --> "0..*" StokCabang : hasMany

    Supplier "1" --> "0..*" PurchaseOrder : hasMany
    Cabang "1" --> "0..*" PurchaseOrder : tujuan
    User "1" --> "0..*" PurchaseOrder : pembuat
    PurchaseOrder "1" --> "1..*" PurchaseOrderDetail : details
    VarianProduk "1" --> "0..*" PurchaseOrderDetail : item

    Supplier "1" --> "0..*" BarangMasuk : supplier
    Cabang "1" --> "0..*" BarangMasuk : tujuan
    User "1" --> "0..*" BarangMasuk : pencatat
    PurchaseOrder "0..1" --> "0..*" BarangMasuk : optional link
    BarangMasuk "1" --> "1..*" BarangMasukDetail : details
    VarianProduk "1" --> "0..*" BarangMasukDetail : item

    Cabang "1" --> "0..*" BarangKeluar : asal
    User "1" --> "0..*" BarangKeluar : pencatat
    BarangKeluar "1" --> "1..*" BarangKeluarDetail : details
    VarianProduk "1" --> "0..*" BarangKeluarDetail : item

    Cabang "1" --> "0..*" TransferStok : sumber
    Cabang "1" --> "0..*" TransferStok : tujuan
    User "1" --> "0..*" TransferStok : pembuat
    TransferStok "1" --> "1..*" TransferStokDetail : details
    VarianProduk "1" --> "0..*" TransferStokDetail : item

    User "1" --> "0..*" StockOpname : petugas
    Cabang "1" --> "0..*" StockOpname : cabang
    StockOpname "1" --> "1..*" StockOpnameDetail : details
    VarianProduk "1" --> "0..*" StockOpnameDetail : item
```

## 4) Sequence UML (Transaksi Barang Keluar -> Notifikasi Stok Minimum)

```mermaid
sequenceDiagram
    actor AdminOrStaf as Admin/Staf
    participant BK as CreateBarangKeluar Page
    participant DB as Database
    participant ST as StokCabang
    participant N as StokMinimumNotification
    participant M as Mail Queue

    AdminOrStaf->>BK: Submit form Barang Keluar
    BK->>DB: Validate stock in beforeCreate()
    DB-->>BK: Stock available
    BK->>DB: Create barang_keluars + barang_keluar_details
    BK->>ST: decrement stok_saat_ini
    ST-->>BK: updated stock
    BK->>BK: if stok_saat_ini <= stok_minimum
    BK->>N: dispatch notification to admin users
    N->>M: queue mail job
```

## 5) Catatan Konsistensi yang Perlu Diperhatikan

- Status Purchase Order di migration menggunakan `Submitted`, `Partially Received`, `Completed`, `Cancelled`.
- Di beberapa logika model masih muncul nilai lama berbahasa Indonesia seperti `Dikirim`, `Sebagian Diterima`, `Selesai`.
- Untuk menjaga integritas UML dan implementasi, sebaiknya satu standar status saja dipakai lintas model/resource/migration.

## 6) Source Verifikasi Utama

- app/Models
- app/Filament/Resources
- app/Filament/Resources/*/Pages
- app/Filament/Pages
- app/Filament/Widgets
- app/Notifications/StokMinimumNotification.php
- database/migrations/2025_10_28_033238_create_inventory_tables.php
- database/migrations/2025_10_29_030119_create_barang_masuk_tables.php
- database/migrations/2025_10_29_135842_create_barang_keluar_tables.php
- database/migrations/2025_10_30_033520_create_purchase_order_tables.php
- database/migrations/2025_11_01_064307_create_transfer_stock_tables.php
- database/migrations/2025_11_12_090127_create_stock_opname_tables.php
