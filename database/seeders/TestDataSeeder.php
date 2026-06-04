<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\Supplier;
use App\Models\VarianProduk;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // CABANG (3 branches)
        // ============================================================
        $cabangPusat = Cabang::create([
            'nama_cabang' => 'HighCloud Vape - Pusat',
            'alamat_cabang' => 'Jl. Raya Utama No. 88, Jakarta Selatan',
            'telepon_cabang' => '021-5555-1234',
        ]);

        $cabangTimur = Cabang::create([
            'nama_cabang' => 'HighCloud Vape - Bekasi',
            'alamat_cabang' => 'Jl. Ahmad Yani No. 42, Bekasi Timur',
            'telepon_cabang' => '021-5555-5678',
        ]);

        $cabangBarat = Cabang::create([
            'nama_cabang' => 'HighCloud Vape - Tangerang',
            'alamat_cabang' => 'Jl. Merdeka Raya No. 15, Tangerang Kota',
            'telepon_cabang' => '021-5555-9012',
        ]);

        // ============================================================
        // SUPPLIER (3 suppliers)
        // ============================================================
        $supplierVapeCo = Supplier::create([
            'nama_supplier' => 'PT VapeCo Indonesia',
            'kontak_person' => 'Hendra Wijaya',
            'telepon' => '0812-1111-2222',
            'email' => 'hendra@vapeco.id',
            'alamat' => 'Jl. Industri No. 12, Bandung',
        ]);

        $supplierFlavorUp = Supplier::create([
            'nama_supplier' => 'PT FlavorUp Sentosa',
            'kontak_person' => 'Dewi Lestari',
            'telepon' => '0813-3333-4444',
            'email' => 'dewi@flavorup.id',
            'alamat' => 'Jl. Raya Darmo No. 77, Surabaya',
        ]);

        $supplierCloudTek = Supplier::create([
            'nama_supplier' => 'PT CloudTek Perkasa',
            'kontak_person' => 'Budi Santoso',
            'telepon' => '0814-5555-6666',
            'email' => 'budi@cloudtek.id',
            'alamat' => 'Jl. Kemang Raya No. 55, Jakarta Selatan',
        ]);

        // ============================================================
        // KATEGORI (5 categories)
        // ============================================================
        $katDevice = Kategori::create(['nama_kategori' => 'Device / Mod', 'deskripsi' => 'Perangkat utama vape: pod system, box mod, AIO']);
        $katAtomizer = Kategori::create(['nama_kategori' => 'Atomizer / Tank', 'deskripsi' => 'RDA, RTA, RDTA, dan cartridge coil head']);
        $katLiquid = Kategori::create(['nama_kategori' => 'Liquid / E-Juice', 'deskripsi' => 'Cairan vape freebase, salt nic, dan aroma']);
        $katAksesoris = Kategori::create(['nama_kategori' => 'Aksesoris', 'deskripsi' => 'Drip tip, battery, charger, cotton, wire']);
        $katPaketan = Kategori::create(['nama_kategori' => 'Paket Starter Kit', 'deskripsi' => 'Paket lengkap pemula: device + coil + charger']);

        // ============================================================
        // PRODUK (8 products)
        // ============================================================
        $produkAioCentaurus = Produk::create([
            'id_kategori' => $katDevice->id,
            'nama_produk' => 'AIO CENTAURUS',
            'deskripsi' => 'All-in-one pod-mod dari Lost Vape, chip Quest 2.0, output 5-40W, battery internal 1200mAh',
        ]);

        $produkXros4 = Produk::create([
            'id_kategori' => $katDevice->id,
            'nama_produk' => 'XROS 4',
            'deskripsi' => 'Pod system Vaporesso, adjustable airflow, battery 1000mAh, COREX heating technology',
        ]);

        $produkDeadRabbit = Produk::create([
            'id_kategori' => $katAtomizer->id,
            'nama_produk' => 'Dead Rabbit V3',
            'deskripsi' => 'RTA dual coil dari Hellvape, build deck postless, airflow honeycomb top-to-bottom',
        ]);

        $produkCoilXros = Produk::create([
            'id_kategori' => $katAtomizer->id,
            'nama_produk' => 'Coil XROS Series',
            'deskripsi' => 'Replacement coil/pod untuk XROS, mesh pod 0.6Ω / 0.8Ω / 1.0Ω, isi 4 pcs per pack',
        ]);

        $produkCloudNinja = Produk::create([
            'id_kategori' => $katLiquid->id,
            'nama_produk' => 'Cloud Ninja Salt',
            'deskripsi' => 'Salt nicotine 30mg/ml, berbagai varian buah tropis',
        ]);

        $produkSumoBurst = Produk::create([
            'id_kategori' => $katLiquid->id,
            'nama_produk' => 'Sumo Burst Freebase',
            'deskripsi' => 'Freebase nicotine 3mg/6mg, varian fruity & menthol, VG 70:30',
        ]);

        $produkChargerXtar = Produk::create([
            'id_kategori' => $katAksesoris->id,
            'nama_produk' => 'XTAR VC4 Charger',
            'deskripsi' => 'Universal 4-slot battery charger, LCD display, compatible 18650/20700/21700',
        ]);

        $produkStarterXros = Produk::create([
            'id_kategori' => $katPaketan->id,
            'nama_produk' => 'Starter Kit XROS 4',
            'deskripsi' => 'Paket lengkap: XROS 4 device + 2 pod replacement + USB-C cable + user manual',
        ]);

        // ============================================================
        // VARIAN PRODUK (18 variants)
        // ============================================================
        // AIO CENTAURUS variants
        $vAioBlack = VarianProduk::create([
            'id_produk' => $produkAioCentaurus->id,
            'nama_varian' => 'Matte Black',
            'sku_code' => 'AIO-CENT-BLK',
            'harga_beli' => 280000,
            'harga_jual' => 380000,
        ]);
        $vAioSilver = VarianProduk::create([
            'id_produk' => $produkAioCentaurus->id,
            'nama_varian' => 'Silver Chrome',
            'sku_code' => 'AIO-CENT-SLV',
            'harga_beli' => 280000,
            'harga_jual' => 380000,
        ]);
        $vAioGunmetal = VarianProduk::create([
            'id_produk' => $produkAioCentaurus->id,
            'nama_varian' => 'Gunmetal Gray',
            'sku_code' => 'AIO-CENT-GMG',
            'harga_beli' => 290000,
            'harga_jual' => 395000,
        ]);

        // XROS 4 variants
        $vXrosBlack = VarianProduk::create([
            'id_produk' => $produkXros4->id,
            'nama_varian' => 'Midnight Black',
            'sku_code' => 'XROS4-BLK',
            'harga_beli' => 175000,
            'harga_jual' => 250000,
        ]);
        $vXrosSilver = VarianProduk::create([
            'id_produk' => $produkXros4->id,
            'nama_varian' => 'Arctic Silver',
            'sku_code' => 'XROS4-SLV',
            'harga_beli' => 175000,
            'harga_jual' => 250000,
        ]);

        // Dead Rabbit V3 variants
        $vDrBlack = VarianProduk::create([
            'id_produk' => $produkDeadRabbit->id,
            'nama_varian' => 'Matte Black',
            'sku_code' => 'DRV3-BLK',
            'harga_beli' => 195000,
            'harga_jual' => 275000,
        ]);
        $vDrSsgold = VarianProduk::create([
            'id_produk' => $produkDeadRabbit->id,
            'nama_varian' => 'Stainless Steel Gold',
            'sku_code' => 'DRV3-SSG',
            'harga_beli' => 210000,
            'harga_jual' => 295000,
        ]);

        // Coil XROS
        $vCoil06 = VarianProduk::create([
            'id_produk' => $produkCoilXros->id,
            'nama_varian' => '0.6Ω Mesh Pod (4pcs)',
            'sku_code' => 'XROS-COIL-06',
            'harga_beli' => 58000,
            'harga_jual' => 85000,
        ]);
        $vCoil08 = VarianProduk::create([
            'id_produk' => $produkCoilXros->id,
            'nama_varian' => '0.8Ω Mesh Pod (4pcs)',
            'sku_code' => 'XROS-COIL-08',
            'harga_beli' => 58000,
            'harga_jual' => 85000,
        ]);
        $vCoil10 = VarianProduk::create([
            'id_produk' => $produkCoilXros->id,
            'nama_varian' => '1.0Ω Mesh Pod (4pcs)',
            'sku_code' => 'XROS-COIL-10',
            'harga_beli' => 58000,
            'harga_jual' => 85000,
        ]);

        // Cloud Ninja Salt
        $vCnMango = VarianProduk::create([
            'id_produk' => $produkCloudNinja->id,
            'nama_varian' => 'Mango Tango (30ml)',
            'sku_code' => 'CN-MANGO-30',
            'harga_beli' => 65000,
            'harga_jual' => 95000,
        ]);
        $vCnGrape = VarianProduk::create([
            'id_produk' => $produkCloudNinja->id,
            'nama_varian' => 'Grape Ice (30ml)',
            'sku_code' => 'CN-GRAPE-30',
            'harga_beli' => 65000,
            'harga_jual' => 95000,
        ]);
        $vCnLychi = VarianProduk::create([
            'id_produk' => $produkCloudNinja->id,
            'nama_varian' => 'Lychee Blast (30ml)',
            'sku_code' => 'CN-LYCHI-30',
            'harga_beli' => 65000,
            'harga_jual' => 95000,
        ]);

        // Sumo Burst Freebase
        $vSbMelon = VarianProduk::create([
            'id_produk' => $produkSumoBurst->id,
            'nama_varian' => 'Melon Burst 3mg (60ml)',
            'sku_code' => 'SB-MELON-3',
            'harga_beli' => 85000,
            'harga_jual' => 130000,
        ]);
        $vSbMint = VarianProduk::create([
            'id_produk' => $produkSumoBurst->id,
            'nama_varian' => 'Mint Freeze 3mg (60ml)',
            'sku_code' => 'SB-MINT-3',
            'harga_beli' => 85000,
            'harga_jual' => 130000,
        ]);

        // XTAR Charger
        $vXtarBlack = VarianProduk::create([
            'id_produk' => $produkChargerXtar->id,
            'nama_varian' => 'Standard Black',
            'sku_code' => 'XTAR-VC4-BLK',
            'harga_beli' => 95000,
            'harga_jual' => 145000,
        ]);

        // Starter Kit XROS 4
        $vStarterBlack = VarianProduk::create([
            'id_produk' => $produkStarterXros->id,
            'nama_varian' => 'XROS 4 Midnight Black',
            'sku_code' => 'SK-XROS4-BLK',
            'harga_beli' => 215000,
            'harga_jual' => 310000,
        ]);
        $vStarterSilver = VarianProduk::create([
            'id_produk' => $produkStarterXros->id,
            'nama_varian' => 'XROS 4 Arctic Silver',
            'sku_code' => 'SK-XROS4-SLV',
            'harga_beli' => 215000,
            'harga_jual' => 310000,
        ]);

        // ============================================================
        // STOK CABANG (each variant → each branch)
        // ============================================================
        $allVarians = VarianProduk::all();
        $allCabangs = [$cabangPusat, $cabangTimur, $cabangBarat];

        // Stok distribution: pusat more stock, cabang moderate
        foreach ($allVarians as $varian) {
            foreach ($allCabangs as $i => $cabang) {
                $baseStok = match ($i) {
                    0 => rand(15, 40), // Pusat: banyak
                    1 => rand(5, 20),  // Bekasi: sedang
                    2 => rand(3, 15),  // Tangerang: kecil
                };

                StokCabang::create([
                    'id_cabang' => $cabang->id,
                    'id_varian_produk' => $varian->id,
                    'stok_saat_ini' => $baseStok,
                    'stok_minimum' => rand(3, 10),
                ]);
            }
        }
    }
}
