<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class LaporanPdfService
{
    /**
     * Generate a PDF download response.
     *
     * @param string $view
     * @param Collection $records
     * @param string $filename
     * @param array $extraData
     * @return \Illuminate\Http\Response
     */
    public function generate(string $view, Collection $records, string $filename, array $extraData = [])
    {
        $data = array_merge([
            'records' => $records,
            'date' => now()->format('d/m/Y H:i'),
        ], $extraData);

        $pdf = Pdf::loadView($view, $data);
        
        // Set paper to A4 and orientation to landscape if needed
        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename . '.pdf');
    }
}
