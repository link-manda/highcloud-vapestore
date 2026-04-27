@extends('pdf.layout')

@section('title', 'Laporan Barang Masuk')

@section('content')
    <table class="table">
        <thead>
            <tr>
                <th>No. Transaksi</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Cabang Tujuan</th>
                <th class="text-right">Total Nilai</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; @endphp
            @foreach($records as $record)
                @php 
                    $subtotal = $record->details->sum('subtotal');
                    $totalAmount += $subtotal; 
                @endphp
                <tr>
                    <td>{{ $record->nomor_transaksi }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->tanggal_masuk)->format('d/m/Y H:i') }}</td>
                    <td>{{ $record->supplier->nama_supplier }}</td>
                    <td>{{ $record->cabangTujuan->nama_cabang }}</td>
                    <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL KESELURUHAN</td>
                <td class="text-right">IDR {{ number_format($totalAmount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
