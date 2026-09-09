<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\RehabInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDataContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_data_contract_retrieves_correct_installments_up_to_process_period()
    {
        $peserta = Peserta::create([
            'noka' => '1234567890123',
            'nama' => 'Budi Santoso',
            'no_hp' => '081234567890',
        ]);

        $rehabCase = RehabCase::create([
            'peserta_id' => $peserta->id,
            'noka_pendaftar' => '1234567890123',
            'tanggal_pendaftaran' => '2026-08-01',
            'jumlah_bulan_cicilan' => 4,
            'status_rehab' => 'AKTIF',
        ]);

        $member = RehabCaseMember::create([
            'rehab_case_id' => $rehabCase->id,
            'peserta_id' => $peserta->id,
            'is_pendaftar' => true,
            'tagihan_awal' => 1000000,
            'sisa_tunggakan' => 1000000,
            'cicilan_bulanan' => 250000,
            'data_source' => 'excel',
        ]);

        // Create installments: August, September, October
        RehabInstallment::create([
            'rehab_case_member_id' => $member->id,
            'nomor_cicilan' => 1,
            'periode_bulan' => '2026-08-01',
            'besaran_cicilan' => 250000,
            'tanggal_bayar' => '2026-08-05', // Sudah bayar
        ]);

        RehabInstallment::create([
            'rehab_case_member_id' => $member->id,
            'nomor_cicilan' => 2,
            'periode_bulan' => '2026-09-01',
            'besaran_cicilan' => 250000,
            'tanggal_bayar' => null, // Belum bayar
        ]);

        RehabInstallment::create([
            'rehab_case_member_id' => $member->id,
            'nomor_cicilan' => 3,
            'periode_bulan' => '2026-10-01',
            'besaran_cicilan' => 250000,
            'tanggal_bayar' => null, // Future
        ]);

        // Process period is September 2026
        $processPeriod = '2026-09-01 23:59:59';

        // Simulasikan query untuk PDF
        $caseData = RehabCase::with(['peserta', 'members.peserta', 'members.installments' => function ($query) use ($processPeriod) {
            $query->where('periode_bulan', '<=', $processPeriod)->orderBy('periode_bulan');
        }])->find($rehabCase->id);

        $this->assertNotNull($caseData);
        $this->assertCount(1, $caseData->members);

        $installments = $caseData->members->first()->installments;

        // Hanya boleh ada 2 installment (Agustus dan September)
        $this->assertCount(2, $installments);

        $august = $installments->firstWhere('periode_bulan', '2026-08-01 00:00:00');
        $this->assertNotNull($august);
        $this->assertNotNull($august->tanggal_bayar); // Sudah bayar

        $september = $installments->firstWhere('periode_bulan', '2026-09-01 00:00:00');
        $this->assertNotNull($september);
        $this->assertNull($september->tanggal_bayar); // Belum bayar
    }
}
