# Highcloud Vapestore — Activity Diagrams

Berdasarkan analisis source code aktual: Models, Filament Resource Pages, Migration, dan Notification.

---

## 1. Login & Role-Based Access Control

```mermaid
stateDiagram-v2
    [*] --> MasukkanKredensial
    MasukkanKredensial: User masukkan email dan password

    MasukkanKredensial --> CekAuth
    state CekAuth <<choice>>
    CekAuth --> TampilkanError : Gagal
    CekAuth --> CekRole : Berhasil

    TampilkanError: Tampilkan error - Credential invalid
    TampilkanError --> MasukkanKredensial

    state CekRole <<choice>>
    CekRole --> AksesDitolak : canAccessPanel false
    CekRole --> DashboardAdmin : Role Admin
    CekRole --> DashboardStaf : Role Staf

    AksesDitolak: Akses ditolak 403
    AksesDitolak --> [*]

    state DashboardAdmin {
        [*] --> AksesAdmin
        AksesAdmin: Dapat akses Data Master, Semua Transaksi, Laporan, Widget Stok Kritis
        AksesAdmin --> [*]
    }

    state DashboardStaf {
        [*] --> AksesStaf
        AksesStaf: Transaksi cabang sendiri saja, tidak bisa lihat Laporan global
        AksesStaf --> [*]
    }

    DashboardAdmin --> [*]
    DashboardStaf --> [*]
```

---

## 2. Alur Purchase Order (PO)

```mermaid
stateDiagram-v2
    [*] --> BukaFormPO
    BukaFormPO: Admin buka form PO baru

    BukaFormPO --> IsiHeader
    IsiHeader: Isi Supplier, Cabang Tujuan, Tanggal PO, Estimasi Tiba

    IsiHeader --> TambahItem
    TambahItem: Tambah item - Varian Produk, Jumlah Pesan, Harga Beli Saat PO

    TambahItem --> CekTambah
    state CekTambah <<choice>>
    CekTambah --> TambahItem : Tambah item lagi
    CekTambah --> SimpanPO : Selesai

    SimpanPO: Simpan PO - Status Draft

    SimpanPO --> CekSubmit
    state CekSubmit <<choice>>
    CekSubmit --> StatusSubmitted : Admin konfirmasi submit
    CekSubmit --> StatusCancelled : Admin batalkan

    StatusCancelled: Status Cancelled
    StatusCancelled --> [*]

    StatusSubmitted: Status Submitted
    StatusSubmitted --> MenungguBarang
    MenungguBarang: Menunggu kedatangan barang dari Supplier

    MenungguBarang --> CekBM
    state CekBM <<choice>>
    CekBM --> CekQty : Barang Masuk dibuat dengan referensi PO ini
    CekBM --> StatusCancelled : PO dibatalkan tanpa penerimaan

    state CekQty <<choice>>
    CekQty --> StatusPartial : Total diterima kurang dari yang dipesan
    CekQty --> StatusCompleted : Total diterima sudah memenuhi semua pesanan

    StatusPartial: Status Partially Received
    StatusPartial --> MenungguBarang

    StatusCompleted: Status Completed
    StatusCompleted --> [*]
```

---

## 3. Alur Barang Masuk (dari Supplier + PO Linking)

```mermaid
stateDiagram-v2
    [*] --> CekRole
    state CekRole <<choice>>
    CekRole --> FormAdmin : Role Admin
    CekRole --> FormStaf : Role Staf

    FormAdmin: Buka form - Pilih Cabang Tujuan bebas
    FormStaf: Buka form - Cabang Tujuan dikunci ke cabang sendiri

    FormAdmin --> PilihSupplier
    FormStaf --> PilihSupplier
    PilihSupplier: Pilih Supplier dan Cabang Tujuan

    PilihSupplier --> CekPO
    state CekPO <<choice>>
    CekPO --> PilihPO : Ada PO aktif untuk Supplier dan Cabang ini
    CekPO --> TambahManual : Tanpa PO atau PO tidak tersedia

    PilihPO: Pilih Nomor PO dengan Status Submitted atau Partially Received
    PilihPO --> AutoFill
    AutoFill: Item terisi otomatis dari sisa qty PO, harga beli dikunci dari PO

    AutoFill --> CekAdjust
    state CekAdjust <<choice>>
    CekAdjust --> EditQty : Perlu adjust jumlah diterima
    CekAdjust --> SubmitBM : Langsung submit

    EditQty: Edit jumlah diterima saja - tidak bisa tambah atau hapus item
    EditQty --> SubmitBM

    TambahManual: Tambah item manual - Varian Produk, Jumlah, Harga Beli
    TambahManual --> CekTambah
    state CekTambah <<choice>>
    CekTambah --> TambahManual : Tambah item lagi
    CekTambah --> SubmitBM : Selesai

    SubmitBM: Submit Barang Masuk
    SubmitBM --> DBTransaksiBM

    state "Proses DB Transaction" as DBTransaksiBM {
        [*] --> GenerateNo
        GenerateNo: Generate nomor BM-YYYYMMDD-XXXX
        GenerateNo --> SimpanBM
        SimpanBM: Simpan barang_masuks
        SimpanBM --> SimpanDetail
        SimpanDetail: Simpan barang_masuk_details
        SimpanDetail --> UpdateStok
        UpdateStok: Tambah stok_cabangs.stok_saat_ini per varian, firstOrCreate jika belum ada
        UpdateStok --> CekLinkPO
        state CekLinkPO <<choice>>
        CekLinkPO --> UpdatePODetail : BM terhubung ke PO
        CekLinkPO --> Commit : BM tidak terhubung PO
        UpdatePODetail: Increment jumlah_diterima di purchase_order_details
        UpdatePODetail --> CekStatusPO
        state CekStatusPO <<choice>>
        CekStatusPO --> POSelesai : Total diterima lebih dari atau sama dengan total dipesan
        CekStatusPO --> POSebagian : Total diterima masih kurang
        POSelesai: Update PO status ke Completed
        POSebagian: Update PO status ke Partially Received
        POSelesai --> Commit
        POSebagian --> Commit
        Commit: COMMIT TRANSACTION
        Commit --> [*]
    }

    DBTransaksiBM --> RedirectBM
    RedirectBM: Redirect ke halaman View Barang Masuk
    RedirectBM --> [*]
```

---

## 4. Alur Barang Keluar (Penjualan + Validasi Stok + Notifikasi)

```mermaid
stateDiagram-v2
    [*] --> CekRoleBK
    state CekRoleBK <<choice>>
    CekRoleBK --> FormAdminBK : Role Admin
    CekRoleBK --> FormStafBK : Role Staf

    FormAdminBK: Form BK terbuka - Pilih Cabang bebas
    FormStafBK: Form BK terbuka - Cabang auto dari cabang staf

    FormAdminBK --> IsiFormBK
    FormStafBK --> IsiFormBK
    IsiFormBK: Isi Tanggal Keluar, Nama Pelanggan opsional, Catatan

    IsiFormBK --> TambahItemBK
    TambahItemBK: Tambah item - Varian Produk, Jumlah, Harga Jual Saat Transaksi

    TambahItemBK --> CekTambahBK
    state CekTambahBK <<choice>>
    CekTambahBK --> TambahItemBK : Tambah item lagi
    CekTambahBK --> SubmitBK : Selesai

    SubmitBK: Submit form Barang Keluar
    SubmitBK --> ValidasiBackendBK
    ValidasiBackendBK: beforeCreate - validasi stok semua item ke database StokCabang

    ValidasiBackendBK --> CekValidBK
    state CekValidBK <<choice>>
    CekValidBK --> TampilErrorBK : Stok tidak cukup untuk salah satu item
    CekValidBK --> GenerateNoBK : Semua item lolos validasi stok

    TampilErrorBK: Tampilkan error per item - Sisa stok tidak mencukupi
    TampilErrorBK --> TambahItemBK

    GenerateNoBK: Generate nomor BK-YYYYMMDD-XXXX
    GenerateNoBK --> DBTransaksiBK

    state "Proses DB Transaction" as DBTransaksiBK {
        [*] --> SimpanBK
        SimpanBK: Simpan barang_keluars
        SimpanBK --> SimpanDetailBK
        SimpanDetailBK: Simpan barang_keluar_details
        SimpanDetailBK --> DecrStok
        DecrStok: Decrement stok_cabangs.stok_saat_ini per varian
        DecrStok --> CekMin
        state CekMin <<choice>>
        CekMin --> DispatchNotif : stok_saat_ini <= stok_minimum dan stok_minimum lebih dari 0
        CekMin --> CekLanjutBK : Stok masih aman di atas minimum
        DispatchNotif: Dispatch StokMinimumNotification ke semua Admin via Laravel Queue
        DispatchNotif --> KirimEmail
        KirimEmail: Kirim email Peringatan Stok Minimum ke semua Admin via ShouldQueue
        KirimEmail --> CekLanjutBK
        state CekLanjutBK <<choice>>
        CekLanjutBK --> DecrStok : Masih ada item berikutnya
        CekLanjutBK --> CommitBK : Semua item selesai diproses
        CommitBK: COMMIT TRANSACTION
        CommitBK --> [*]
    }

    DBTransaksiBK --> RedirectBK
    RedirectBK: Redirect ke halaman View Barang Keluar
    RedirectBK --> [*]
```

---

## 5. Alur Transfer Stok Antar Cabang

```mermaid
stateDiagram-v2
    [*] --> CekRoleTS
    state CekRoleTS <<choice>>
    CekRoleTS --> FormAdminTS : Role Admin
    CekRoleTS --> FormStafTS : Role Staf

    FormAdminTS: Form Transfer terbuka - Pilih Cabang Sumber bebas
    FormStafTS: Form Transfer terbuka - Cabang Sumber dikunci ke cabang staf

    FormAdminTS --> PilihTujuan
    FormStafTS --> PilihTujuan
    PilihTujuan: Pilih Cabang Tujuan

    PilihTujuan --> CekSamaCabang
    state CekSamaCabang <<choice>>
    CekSamaCabang --> ErrSamaCabang : Cabang Sumber sama dengan Tujuan
    CekSamaCabang --> TambahItemTS : Cabang berbeda

    ErrSamaCabang: Error - Cabang Sumber tidak boleh sama dengan Cabang Tujuan
    ErrSamaCabang --> PilihTujuan

    TambahItemTS: Tambah item - hanya Varian dengan stok lebih dari 0 di Cabang Sumber, max qty sama dengan stok_saat_ini
    TambahItemTS --> CekTambahTS
    state CekTambahTS <<choice>>
    CekTambahTS --> TambahItemTS : Tambah item lagi
    CekTambahTS --> GenerateNoTS : Selesai

    GenerateNoTS: Generate nomor TS-YYYYMMDD-XXXX
    GenerateNoTS --> ValidasiBackendTS
    ValidasiBackendTS: beforeCreate - validasi ulang stok di cabang sumber untuk mencegah race condition

    ValidasiBackendTS --> CekRace
    state CekRace <<choice>>
    CekRace --> ErrRace : Stok berubah setelah form dibuka, tidak cukup
    CekRace --> DBTransaksiTS : Stok masih cukup

    ErrRace: Error - Stok tidak cukup di cabang sumber
    ErrRace --> TambahItemTS

    state "Proses DB Transaction" as DBTransaksiTS {
        [*] --> SimpanTransfer
        SimpanTransfer: Simpan transfer_stoks
        SimpanTransfer --> SimpanDetailTS
        SimpanDetailTS: Simpan transfer_stok_details per item
        SimpanDetailTS --> DecrSumber
        DecrSumber: Decrement stok_cabangs di Cabang Sumber
        DecrSumber --> IncrTujuan
        IncrTujuan: Increment stok_cabangs di Cabang Tujuan, firstOrCreate jika belum ada
        IncrTujuan --> CekItemTS
        state CekItemTS <<choice>>
        CekItemTS --> DecrSumber : Masih ada item berikutnya
        CekItemTS --> CommitTS : Semua item selesai diproses
        CommitTS: COMMIT TRANSACTION
        CommitTS --> [*]
    }

    DBTransaksiTS --> RedirectTS
    RedirectTS: Redirect ke halaman View Transfer Stok
    RedirectTS --> [*]
```

---

## 6. Alur Stock Opname (Audit & Rekonsiliasi Stok)

```mermaid
stateDiagram-v2
    [*] --> CekRoleSO
    state CekRoleSO <<choice>>
    CekRoleSO --> FormAdminSO : Role Admin
    CekRoleSO --> FormStafSO : Role Staf

    FormAdminSO: Form Opname - Pilih Cabang bebas, id_petugas diisi otomatis user login
    FormStafSO: Form Opname - Cabang dikunci ke cabang staf, id_petugas diisi otomatis user login

    FormAdminSO --> PilihTanggal
    FormStafSO --> PilihTanggal
    PilihTanggal: Pilih tanggal opname

    PilihTanggal --> TambahItemSO
    TambahItemSO: Pilih Varian Produk yang ada di cabang - Stok Sistem terisi otomatis dari StokCabang

    TambahItemSO --> IsiStokFisik
    IsiStokFisik: Isi Stok Fisik hasil hitung manual

    IsiStokFisik --> HitungSelisih
    HitungSelisih: Selisih dihitung otomatis - selisih = stok_fisik - stok_sistem

    HitungSelisih --> CekTambahSO
    state CekTambahSO <<choice>>
    CekTambahSO --> TambahItemSO : Tambah item lagi
    CekTambahSO --> SimpanDraft : Selesai

    SimpanDraft: Simpan Opname - Status Draft

    SimpanDraft --> CekFinalisasi
    state CekFinalisasi <<choice>>
    CekFinalisasi --> EditDraft : Belum selesai, perlu edit dulu
    CekFinalisasi --> CekAktor : Finalisasi opname

    EditDraft: Edit item - ubah stok_fisik (Staf hanya cabangnya, Admin semua)
    EditDraft --> CekFinalisasi

    state CekAktor <<choice>>
    CekAktor --> Forbidden : Bukan Admin
    CekAktor --> KonfirmasiModal : Admin

    Forbidden: Akses ditolak - Tombol Complete tidak tampil untuk Staf
    Forbidden --> CekFinalisasi

    KonfirmasiModal: Konfirmasi modal - Stok akan disesuaikan berdasarkan hasil opname
    KonfirmasiModal --> UpdateStokSO

    UpdateStokSO: Update stok_cabangs.stok_saat_ini sama dengan stok_fisik untuk setiap varian

    UpdateStokSO --> CekItemSO
    state CekItemSO <<choice>>
    CekItemSO --> UpdateStokSO : Masih ada item berikutnya
    CekItemSO --> StatusCompletedSO : Semua item selesai diupdate

    StatusCompletedSO: Update status Opname menjadi completed
    StatusCompletedSO --> NotifUI

    NotifUI: Notifikasi sukses Filament - jumlah item yang disesuaikan ditampilkan
    NotifUI --> [*]
```

---

## Ringkasan Penanda Alur Sistem

| Alur | Trigger | Aktor | Update Stok |
|------|---------|-------|-------------|
| Purchase Order | Manual Admin | Admin | Tidak langsung |
| Barang Masuk | PO linked atau manual | Admin / Staf | Naik di cabang tujuan |
| Barang Keluar | Penjualan | Admin / Staf | Turun di cabang asal |
| Transfer Stok | Kebutuhan antar cabang | Admin / Staf | Turun dari sumber, naik ke tujuan |
| Stock Opname | Audit berkala | Admin / Staf | Override ke stok_fisik saat complete |
| Notif Stok Minimum | Triggered oleh Barang Keluar | Sistem (auto) | Tidak ada, hanya alert |
