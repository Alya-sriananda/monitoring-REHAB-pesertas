<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentMonitoringRequest;
use App\Models\RehabCase;
use App\Services\PaymentMonitoringService;
use Exception;
use Illuminate\Http\RedirectResponse;

class PaymentMonitoringController extends Controller
{
    public function __construct(
        protected PaymentMonitoringService $paymentMonitoringService
    ) {}

    /**
     * Store or update family payment date for a specific period.
     */
    public function store(StorePaymentMonitoringRequest $request, RehabCase $rehabCase): RedirectResponse
    {
        $this->paymentMonitoringService->updateFamilyPaymentDate(
            $rehabCase->id,
            $request->validated('periode_bulan'),
            $request->validated('tanggal_bayar')
        );

        return back()->with('success', 'Pembayaran berhasil diperbarui.');
    }
}
