@extends('pdf.layout')

@section('title', 'Laporan Stok Opname')

@section('content')
    <table class="table">
        <thead>
            <tr>
                <th>Cabang</th>
                <th>Tanggal Opname</th>
                <th>Produk & Varian</th>
                <th class="text-right">Stok Sistem</th>
                <th class="text-right">Stok Fisik</th>
                <th class="text-right">Selisih</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ $record->stockOpname->cabang->nama_cabang }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->stockOpname->tanggal_opname)->format('d/m/Y') }}</td>
                    <td>{{ $record->varianProduk->produk->nama_produk }} - {{ $record->varianProduk->nama_varian }}</td>
                    <td class="text-right">{{ number_format($record->stok_sistem, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($record->stok_fisik, 0, ',', '.') }}</td>
                    <td class="text-right" style="color: {{ $record->selisih > 0 ? 'green' : ($record->selisih < 0 ? 'red' : 'black') }}">
                        {{ number_format($record->selisih, 0, ',', '.') }}
                    </td>
                    <td>{{ $record->catatan }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
