@extends('pdf.layout')

@section('title', 'Laporan Purchase Order')

@section('content')
    <table class="table">
        <thead>
            <tr>
                <th>Nomor PO</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Cabang Tujuan</th>
                <th>Status</th>
                <th class="text-right">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; @endphp
            @foreach($records as $record)
                @php $totalAmount += $record->total_harga; @endphp
                <tr>
                    <td>{{ $record->nomor_po }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->tanggal_po)->format('d/m/Y') }}</td>
                    <td>{{ $record->supplier->nama_supplier }}</td>
                    <td>{{ $record->cabangTujuan->nama_cabang }}</td>
                    <td>{{ $record->status }}</td>
                    <td class="text-right">{{ number_format($record->total_harga, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL KESELURUHAN</td>
                <td class="text-right">IDR {{ number_format($totalAmount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
