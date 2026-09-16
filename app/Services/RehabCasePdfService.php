<?php

namespace App\Services;

use App\Models\RehabCase;
use App\Models\RehabInstallment;
use Carbon\Carbon;

class RehabCasePdfService
{
    /**
     * Build data required for the Rehab PDF letter.
     *
     * @return array{
     *     caseData: RehabCase,
     *     processPeriod: string,
     *     tanggalData: string,
     *     jumlahAnggota: int,
     *     jumlahBulanMenunggak: int,
     *     rincianTagihan: array<int, array{
     *         periode_label: string,
     *         periode_bulan: string,
     *         member_breakdown: array<string, int>,
     *         monthly_total: int
     *     }>,
     *     totalTunggakan: int,
     *     isLunas: bool,
     *     statusMessage: string,
     *     reminderMessage: string,
     *     nokaKepalaKeluarga: string
     * }
     */
    public function buildPdfData(int $rehabCaseId): array
    {
        $processPeriod = now();
        $cutoffDate = $processPeriod->copy()->endOfMonth()->format('Y-m-d 23:59:59');

        $caseData = RehabCase::with(['peserta', 'members.peserta'])->findOrFail($rehabCaseId);

        $memberIds = $caseData->members->pluck('id');
        $jumlahAnggota = $caseData->members->count();

        $installments = RehabInstallment::whereIn('rehab_case_member_id', $memberIds)
            ->where('periode_bulan', '<=', $cutoffDate)
            ->whereNull('tanggal_bayar')
            ->orderBy('periode_bulan')
            ->get();

        // Group by logical month (e.g. "2026-09")
        $groupedInstallments = $installments->groupBy(function ($installment) {
            return Carbon::parse($installment->periode_bulan)->format('Y-m');
        });

        $rincianTagihan = [];
        $totalTunggakan = 0;

        foreach ($groupedInstallments as $month => $monthInstallments) {
            $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

            // For a single logical month, group by member to show breakdown per member
            $memberBreakdown = [];
            $monthlyTotal = 0;

            foreach ($monthInstallments as $inst) {
                $member = $caseData->members->firstWhere('id', $inst->rehab_case_member_id);
                $memberName = $member ? $member->peserta->nama : 'Unknown';

                if (! isset($memberBreakdown[$memberName])) {
                    $memberBreakdown[$memberName] = 0;
                }
                $memberBreakdown[$memberName] += $inst->besaran_cicilan;
                $monthlyTotal += $inst->besaran_cicilan;
            }

            $rincianTagihan[] = [
                'periode_label' => $monthDate->translatedFormat('F Y'),
                'periode_bulan' => $monthDate->format('Y-m'),
                'member_breakdown' => $memberBreakdown,
                'monthly_total' => $monthlyTotal,
            ];

            $totalTunggakan += $monthlyTotal;
        }

        $isLunas = count($rincianTagihan) === 0;
        $jumlahBulanMenunggak = count($rincianTagihan);

        $nextMonth = $processPeriod->copy()->addMonth()->translatedFormat('F Y');

        if ($isLunas) {
            $statusMessage = 'Seluruh tagihan REHAB Anda sampai dengan '.$processPeriod->translatedFormat('F Y').' telah lunas dibayarkan.';
            $reminderMessage = 'Mohon tetap melakukan pembayaran iuran untuk bulan '.$nextMonth.'.';
        } else {
            $statusMessage = 'Berdasarkan catatan kami, Anda masih memiliki tunggakan iuran REHAB sampai dengan '.$processPeriod->translatedFormat('F Y').'.';
            $reminderMessage = 'Mohon segera lakukan pembayaran untuk menghindari penghentian layanan. Jangan lupa juga untuk membayar iuran bulan '.$nextMonth.'.';
        }

        return [
            'caseData' => $caseData,
            'processPeriod' => $processPeriod->translatedFormat('F Y'),
            'tanggalData' => $processPeriod->translatedFormat('d F Y'),
            'jumlahAnggota' => $jumlahAnggota,
            'jumlahBulanMenunggak' => $jumlahBulanMenunggak,
            'rincianTagihan' => $rincianTagihan,
            'totalTunggakan' => $totalTunggakan,
            'isLunas' => $isLunas,
            'statusMessage' => $statusMessage,
            'reminderMessage' => $reminderMessage,
            'nokaKepalaKeluarga' => $caseData->noka_pendaftar,
        ];
    }
}
