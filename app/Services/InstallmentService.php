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
     * @param  array  $customInstallments  Array of ['periode_bulan' => 'YYYY-MM-DD', 'besaran_cicilan' => float]
     * @return Collection
     */
    public function generateSchedule(RehabCaseMember $member, string $tanggalPendaftaran, int $jumlahBulan, array $customInstallments = [])
    {
        if ($jumlahBulan <= 0) {
            throw new \InvalidArgumentException('Jumlah bulan cicilan harus lebih dari 0.');
        }

        $tagihanAwal = (float) $member->tagihan_awal;

        $installments = collect();
        $startDate = Carbon::parse($tanggalPendaftaran)->startOfMonth();

        DB::transaction(function () use ($member, $startDate, $jumlahBulan, $tagihanAwal, $customInstallments, &$installments) {

            if (! empty($customInstallments)) {
                $totalCustom = collect($customInstallments)->sum('besaran_cicilan');
                if ((float) $totalCustom !== $tagihanAwal) {
                    throw new \InvalidArgumentException('Total cicilan manual ('.number_format($totalCustom, 0, ',', '.').') tidak sama dengan tagihan awal ('.number_format($tagihanAwal, 0, ',', '.').').');
                }

                // Assuming $customInstallments is sorted by periode_bulan
                foreach ($customInstallments as $index => $custom) {
                    $installment = RehabInstallment::create([
                        'rehab_case_member_id' => $member->id,
                        'nomor_cicilan' => $index + 1,
                        'periode_bulan' => Carbon::parse($custom['periode_bulan'])->startOfMonth()->format('Y-m-d'),
                        'besaran_cicilan' => $custom['besaran_cicilan'],
                        'tanggal_bayar' => null,
                    ]);
                    $installments->push($installment);
                }

                $member->cicilan_bulanan = $customInstallments[0]['besaran_cicilan'] ?? 0;
                $member->save();
            } else {
                $cicilanBulanan = $this->calculateMonthlyInstallment($tagihanAwal, $jumlahBulan);
                $totalDihitung = 0;

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

                $member->cicilan_bulanan = $cicilanBulanan;
                $member->save();
            }
        });

        return $installments;
    }
}
