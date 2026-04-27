<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
            color: #000;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #666;
        }
        .info {
            margin-bottom: 15px;
            width: 100%;
        }
        .info td {
            padding: 2px 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f2f2f2;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }
        .table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #999;
            padding: 10px 0;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .total-row {
            background-color: #eee !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>HighCloud VapeStore</h1>
        <p>Laporan Manajemen Inventori & Penjualan</p>
    </div>

    <div class="info">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <strong>Laporan:</strong> @yield('title')<br>
                    <strong>Tanggal Cetak:</strong> {{ date('d/m/Y H:i') }}
                </td>
                <td style="width: 50%; text-align: right;">
                    @yield('filters')
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer">
        Dicetak secara otomatis oleh Sistem HighCloud VapeStore &bull; Halaman <span class="pagenum"></span>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $x = 520;
            $y = 820;
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $font = null;
            $size = 9;
            $color = array(0.6, 0.6, 0.6);
            $word_space = 0.0;
            $char_space = 0.0;
            $angle = 0.0;
            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>
</body>
</html>
