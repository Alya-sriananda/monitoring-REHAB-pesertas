<?php

namespace App\Http\Controllers;

use App\Models\RehabCase;
use App\Services\RehabCasePdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RehabCasePdfController extends Controller
{
    public function __construct(protected RehabCasePdfService $pdfService) {}

    public function __invoke(RehabCase $rehabCase)
    {
        $data = $this->pdfService->buildPdfData($rehabCase->id);

        $pdf = Pdf::loadView('pdf.tagihan', $data)
            ->setPaper('a4', 'portrait')
            ->setWarnings(false);

        $processPeriodFormatted = Carbon::now()->format('Ym');

        return $pdf->stream('Tagihan_REHAB_'.$data['caseData']->noka_pendaftar.'_'.$processPeriodFormatted.'.pdf');
    }
}
