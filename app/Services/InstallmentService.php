<?php

namespace App\Services;

use App\Models\RehabCaseMember;
use App\Models\RehabInstallment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstallmentService
{
    /**
     * Calculate the base monthly installment based on initial debt and number of months.
     * Rounding to the nearest integer.
     */
    public function calculateMonthlyInstallment(float $tagihanAwal, int $jumlahBulan): float
    {
        if ($jumlahBulan <= 0) {
            return 0;
        }

        return round($tagihanAwal / $jumlahBulan);
    }

    /**
     * Generate the installment schedule for a RehabCaseMember.
     * Starts from the month of $tanggalPendaftaran.
     * Adjusts the final installment to ensure the total sum equals the initial debt exactly.
     *
     * @return Collection
     */
    public function generateSchedule(RehabCaseMember $member, string $tanggalPendaftaran, int $jumlahBulan)
    {
        if ($jumlahBulan <= 0) {
            throw new \InvalidArgumentException('Jumlah bulan cicilan harus lebih dari 0.');
        }

        $tagihanAwal = (float) $member->tagihan_awal;
        $cicilanBulanan = $this->calculateMonthlyInstallment($tagihanAwal, $jumlahBulan);

        $installments = collect();
        $startDate = Carbon::parse($tanggalPendaftaran)->startOfMonth();

        $totalDihitung = 0;

        DB::transaction(function () use ($member, $startDate, $jumlahBulan, $cicilanBulanan, $tagihanAwal, &$installments, &$totalDihitung) {
            for ($i = 1; $i <= $jumlahBulan; $i++) {
                $isLast = ($i === $jumlahBulan);
                $periode = $startDate->copy()->addMonthsNoOverflow($i - 1)->startOfMonth();

                if ($isLast) {
                    $sisaTagihan = $tagihanAwal - $totalDihitung;
                    $besaranCicilan = $sisaTagihan;
                } else {
                    $besaranCicilan = $cicilanBulanan;
                    $totalDihitung += $besaranCicilan;
                }

                $installment = RehabInstallment::create([
                    'rehab_case_member_id' => $member->id,
                    'nomor_cicilan' => $i,
                    'periode_bulan' => $periode->format('Y-m-d'),
                    'besaran_cicilan' => $besaranCicilan,
                    'tanggal_bayar' => null,
                ]);

                $installments->push($installment);
            }

            // Save the calculated cicilan_bulanan to member
            $member->cicilan_bulanan = $cicilanBulanan;
            $member->save();
        });

        return $installments;
    }
}
