@php
use Illuminate\Support\Number;
@endphp

{{-- Ini akan merender <tfoot> di dalam tabel Anda --}}
<tfoot class="bg-gray-50 dark:bg-white/5">
    <tr class="filament-tables-row">
        {{--
          Kita punya 6 kolom: No, Tgl, Supplier, Cabang, PO, Total.
          Buat 4 sel kosong pertama, gabungkan (colspan)
        --}}
        <td colspan="4" class="filament-tables-cell dark:text-gray-400">
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

        {{-- Sel kosong terakhir (untuk kolom 'Dicatat Oleh' jika terlihat) --}}
        <td class="filament-tables-cell dark:text-gray-400">
            &nbsp;
        </td>
    </tr>
</tfoot>