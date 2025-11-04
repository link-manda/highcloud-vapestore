@php
use Illuminate\Support\Number;
@endphp

{{-- Ini akan merender <tfoot> di dalam tabel Anda --}}
<tfoot class="bg-gray-50 dark:bg-white/5">
    <tr class="filament-tables-row">
        {{--
          Kita punya 7 kolom: Tgl, Cabang, Produk, Varian, Jml, Harga, Subtotal.
          Buat 5 sel kosong pertama.
        --}}
        <td colspan="5" class="filament-tables-cell dark:text-gray-400">
            &nbsp; {{-- Sel kosong untuk alignment --}}
        </td>

        {{-- Sel untuk Label "Total Keseluruhan" --}}
        <td class="filament-tables-cell p-4 text-right font-semibold text-gray-900 dark:text-white">
            Total Keseluruhan
        </td>

        {{-- Sel untuk Nilai Total --}}
        <td class="filament-tables-cell p-4 font-semibold text-gray-900 dark:text-white">
            {{ Number::currency($total, 'IDR') }}
        </td>
    </tr>
</tfoot>