# 📊 Entity Relationship Diagram (ERD) - HighCloud VapeStore

Dokumen ini berisi visualisasi struktur database inti untuk sistem **HighCloud VapeStore**. Diagram ini berfokus pada logika bisnis utama seperti manajemen stok, transaksi barang masuk/keluar, dan operasional cabang.

## 1. Diagram Mermaid

```mermaid
erDiagram
    CABANGS ||--o{ USERS : "memiliki"
    CABANGS ||--o{ STOK_CABANGS : "menyimpan stok"
    CABANGS ||--o{ BARANG_MASUKS : "sebagai tujuan"
    CABANGS ||--o{ BARANG_KELUARS : "sebagai asal"
    CABANGS ||--o{ PURCHASE_ORDERS : "sebagai tujuan"
    CABANGS ||--o{ TRANSFER_STOKS : "sebagai sumber/tujuan"
    CABANGS ||--o{ STOCK_OPNAMES : "melakukan opname"

    KATEGORIS ||--o{ PRODUKS : "mengelompokkan"
    PRODUKS ||--o{ VARIAN_PRODUKS : "memiliki varian"
    VARIAN_PRODUKS ||--o{ STOK_CABANGS : "tercatat di"
    VARIAN_PRODUKS ||--o{ BARANG_MASUK_DETAILS : "masuk sebagai item"
    VARIAN_PRODUKS ||--o{ BARANG_KELUAR_DETAILS : "keluar sebagai item"
    VARIAN_PRODUKS ||--o{ PURCHASE_ORDER_DETAILS : "dipesan sebagai item"
    VARIAN_PRODUKS ||--o{ TRANSFER_STOK_DETAILS : "dipindah sebagai item"
    VARIAN_PRODUKS ||--o{ STOCK_OPNAME_DETAILS : "dihitung sebagai item"

    SUPPLIERS ||--o{ BARANG_MASUKS : "mengirim barang"
    SUPPLIERS ||--o{ PURCHASE_ORDERS : "menerima pesanan"

    USERS ||--o{ BARANG_MASUKS : "mencatat"
    USERS ||--o{ BARANG_KELUARS : "mencatat"
    USERS ||--o{ PURCHASE_ORDERS : "membuat"
    USERS ||--o{ TRANSFER_STOKS : "membuat"
    USERS ||--o{ STOCK_OPNAMES : "bertugas"

    BARANG_MASUKS ||--o{ BARANG_MASUK_DETAILS : "memiliki detail"
    BARANG_KELUARS ||--o{ BARANG_KELUAR_DETAILS : "memiliki detail"
    PURCHASE_ORDERS ||--o{ PURCHASE_ORDER_DETAILS : "memiliki detail"
    TRANSFER_STOKS ||--o{ TRANSFER_STOK_DETAILS : "memiliki detail"
    STOCK_OPNAMES ||--o{ STOCK_OPNAME_DETAILS : "memiliki detail"

    CABANGS {
        bigint id PK
        string nama_cabang
        text alamat_cabang
        string telepon_cabang
        string image
    }

    KATEGORIS {
        bigint id PK
        string nama_kategori
    }

    SUPPLIERS {
        bigint id PK
        string nama_supplier
        string telepon
    }

    PRODUKS {
        bigint id PK
        bigint id_kategori FK
        string nama_produk
    }

    VARIAN_PRODUKS {
        bigint id PK
        bigint id_produk FK
        string nama_varian
        string sku_code
        decimal harga_beli
        decimal harga_jual
    }

    STOK_CABANGS {
        bigint id PK
        bigint id_cabang FK
        bigint id_varian_produk FK
        integer stok_saat_ini
        integer stok_minimum
    }

    BARANG_MASUKS {
        bigint id PK
        string nomor_transaksi
        datetime tanggal_masuk
        bigint id_supplier FK
        bigint id_cabang_tujuan FK
        bigint id_user FK
    }

    BARANG_MASUK_DETAILS {
        bigint id PK
        bigint id_barang_masuk FK
        bigint id_varian_produk FK
        integer jumlah
        decimal subtotal
    }

    BARANG_KELUARS {
        bigint id PK
        string nomor_transaksi
        datetime tanggal_keluar
        bigint id_cabang FK
        bigint id_user FK
    }

    BARANG_KELUAR_DETAILS {
        bigint id PK
        bigint id_barang_keluar FK
        bigint id_varian_produk FK
        integer jumlah
        decimal subtotal
    }

    USERS {
        bigint id PK
        string name
        string email
        enum role
        bigint id_cabang FK
    }
```

## 2. Penjelasan Singkat Relasi & Logika Laporan

Diagram di atas menggambarkan bagaimana data mengalir di dalam HighCloud VapeStore:

1.  **Struktur Produk**: Produk dikelola melalui hierarki `Kategori` -> `Produk` -> `Varian Produks`. Hal ini memungkinkan satu produk (misal: Liquid A) memiliki banyak varian (misal: Nikotin 3mg, 6mg).
2.  **Manajemen Stok**: Tabel `Stok Cabangs` adalah tabel persimpangan (*pivot*) antara `Cabang` dan `Varian Produk`. Laporan Sisa Stok sangat bergantung pada relasi ini untuk menunjukkan sisa barang di tiap lokasi.
3.  **Alur Transaksi**:
    - **Barang Masuk**: Menghubungkan `Supplier` (asal) ke `Cabang` (tujuan). Detail item dicatat di `Barang Masuk Details`.
    - **Barang Keluar (Penjualan)**: Mencatat pengurangan stok dari suatu `Cabang`. Detail item dicatat di `Barang Keluar Details`.
    - **Stock Opname**: Digunakan untuk sinkronisasi fisik antara stok di gudang/toko dengan stok di sistem.
4.  **Audit Trail**: Melalui relasi ke tabel `Users`, sistem dapat melacak siapa petugas yang bertanggung jawab atas setiap transaksi (pencatat barang masuk, kasir barang keluar, atau petugas opname).

Diagram ini menjadi fondasi utama dalam pembuatan modul **Laporan** di Filament, di mana setiap query laporan akan melakukan *join* ke tabel-tabel terkait berdasarkan foreign key yang didefinisikan di atas.
