<?php

namespace App\Services;

use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use Illuminate\Support\Facades\DB;

class RehabCaseService
{
    protected InstallmentService $installmentService;

    public function __construct(InstallmentService $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    /**
     * Create a new RehabCase along with its members and generate installment schedules.
     * Everything is wrapped in a single database transaction.
     *
     * @param  array  $caseData  Required: peserta_id, noka_pendaftar, tanggal_pendaftaran, jumlah_bulan_cicilan, status_rehab
     * @param  array  $membersData  Array of member data. Each must have: peserta_id, tagihan_awal, is_pendaftar
     */
    public function createCaseWithMembers(array $caseData, array $membersData): RehabCase
    {
        return DB::transaction(function () use ($caseData, $membersData) {
            // 1. Create the Rehab Case
            $rehabCase = RehabCase::create([
                'peserta_id' => $caseData['peserta_id'],
                'id_cicilan' => $caseData['id_cicilan'] ?? null,
                'noka_pendaftar' => $caseData['noka_pendaftar'],
                'npp_petugas' => $caseData['npp_petugas'] ?? null,
                'tanggal_pendaftaran' => $caseData['tanggal_pendaftaran'],
                'jumlah_bulan_cicilan' => $caseData['jumlah_bulan_cicilan'],
                'tanggal_akhir_cicilan' => $caseData['tanggal_akhir_cicilan'] ?? null,
                'status_rehab' => $caseData['status_rehab'],
                'created_from_batch_id' => $caseData['created_from_batch_id'] ?? null,
                'closed_at' => null,
            ]);

            // 2. Create Members and their Schedules
            foreach ($membersData as $memberData) {
                $member = RehabCaseMember::create([
                    'rehab_case_id' => $rehabCase->id,
                    'peserta_id' => $memberData['peserta_id'],
                    'is_pendaftar' => $memberData['is_pendaftar'] ?? false,
                    'tagihan_awal' => $memberData['tagihan_awal'],
                    'jml_bulan_menunggak_awal' => $memberData['jml_bulan_menunggak_awal'] ?? null,
                    'data_source' => $memberData['data_source'] ?? 'sipp_manual',
                    'cicilan_bulanan' => 0,
                ]);

                // Generate Installments for this member
                $this->installmentService->generateSchedule(
                    $member,
                    $rehabCase->tanggal_pendaftaran->format('Y-m-d'),
                    $rehabCase->jumlah_bulan_cicilan
                );
            }

            return $rehabCase->load('members.installments');
        });
    }
}
