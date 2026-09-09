<?php

namespace App\Services;

use App\Models\RehabCase;
use App\Models\RehabInstallment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentMonitoringService
{
    /**
     * Update the payment date for all installments of a REHAB case for a specific month.
     *
     * @throws InvalidArgumentException
     */
    public function updateFamilyPaymentDate(int $rehabCaseId, string $periodeBulan, ?string $tanggalBayar): void
    {
        DB::transaction(function () use ($rehabCaseId, $periodeBulan, $tanggalBayar) {
            $case = RehabCase::with('members')->lockForUpdate()->find($rehabCaseId);

            if (! $case) {
                throw new InvalidArgumentException('Rehab Case not found.');
            }

            $memberIds = $case->members->pluck('id');

            if ($memberIds->isEmpty()) {
                throw new InvalidArgumentException('Rehab Case has no members.');
            }

            $installments = RehabInstallment::whereIn('rehab_case_member_id', $memberIds)
                ->whereDate('periode_bulan', $periodeBulan)
                ->lockForUpdate()
                ->get();

            if ($installments->isEmpty()) {
                throw new InvalidArgumentException('No installments found for the given period.');
            }

            // Update all installments
            foreach ($installments as $installment) {
                $installment->tanggal_bayar = $tanggalBayar;
                $installment->save();
            }
        });
    }

    /**
     * Clear the payment date for all installments of a REHAB case for a specific month.
     */
    public function clearFamilyPaymentDate(int $rehabCaseId, string $periodeBulan): void
    {
        $this->updateFamilyPaymentDate($rehabCaseId, $periodeBulan, null);
    }
}
