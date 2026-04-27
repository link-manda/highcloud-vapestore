@extends('pdf.layout')

@section('title', 'Laporan Barang Keluar')

@section('content')
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Cabang</th>
                <th>Produk & Varian</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; $totalAmount = 0; @endphp
            @foreach($records as $record)
                @php 
                    $totalQty += $record->jumlah; 
                    $totalAmount += $record->subtotal; 
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($record->barangKeluar->tanggal_keluar)->format('d/m/Y H:i') }}</td>
                    <td>{{ $record->barangKeluar->cabang->nama_cabang }}</td>
                    <td>{{ $record->varianProduk->produk->nama_produk }} - {{ $record->varianProduk->nama_varian }}</td>
                    <td class="text-right">{{ number_format($record->jumlah, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($record->harga_jual_saat_transaksi, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($record->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($totalQty, 0, ',', '.') }}</td>
                <td></td>
                <td class="text-right">IDR {{ number_format($totalAmount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
