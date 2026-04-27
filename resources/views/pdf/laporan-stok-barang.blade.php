@extends('pdf.layout')

@section('title', 'Laporan Sisa Stok Barang')

@section('content')
    <table class="table">
        <thead>
            <tr>
                <th>Cabang</th>
                <th>Kategori</th>
                <th>Produk & Varian</th>
                <th>SKU</th>
                <th class="text-right">Stok</th>
                <th class="text-right">Harga Beli</th>
                <th class="text-right">Nilai Aset</th>
            </tr>
        </thead>
        <tbody>
            @php $totalStok = 0; $totalAset = 0; @endphp
            @foreach($records as $record)
                @php 
                    $nilaiAset = (float)$record->stok_saat_ini * (float)($record->varianProduk->harga_beli ?? 0);
                    $totalStok += $record->stok_saat_ini; 
                    $totalAset += $nilaiAset; 
                @endphp
                <tr>
                    <td>{{ $record->cabang->nama_cabang }}</td>
                    <td>{{ $record->varianProduk->produk->kategori->nama_kategori }}</td>
                    <td>{{ $record->varianProduk->produk->nama_produk }} - {{ $record->varianProduk->nama_varian }}</td>
                    <td>{{ $record->varianProduk->sku_code }}</td>
                    <td class="text-right">{{ number_format($record->stok_saat_ini, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($record->varianProduk->harga_beli ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($nilaiAset, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($totalStok, 0, ',', '.') }}</td>
                <td></td>
                <td class="text-right">IDR {{ number_format($totalAset, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
