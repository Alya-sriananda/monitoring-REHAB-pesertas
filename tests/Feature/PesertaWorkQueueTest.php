<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Daerah;
use App\Models\Peserta;
use App\Models\PesertaBatch;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\RehabInstallment;
use App\Models\SippVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesertaWorkQueueTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Daerah $daerah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'aktif' => true, 'must_change_password' => false]);
        $this->daerah = Daerah::factory()->create();
    }

    public function test_scenario_a_tidak_terdaftar_rehab()
    {
        $batch = Batch::factory()->create(['tanggal_data' => '2026-09-08']);
        $peserta = Peserta::factory()->create();
        PesertaBatch::create(['peserta_id' => $peserta->id, 'batch_id' => $batch->id, 'data_source' => 'excel']);

        // Staff verifies SIPP and participant is not enrolled in REHAB
        SippVerification::create([
            'peserta_id' => $peserta->id,
            'batch_id' => $batch->id,
            'rehab_case_id' => null,
            'user_id' => $this->user->id,
            'terdaftar_rehab' => false,
            'tanggal_cek' => '2026-09-09',
        ]);

        $pesertaFromDB = Peserta::forWorkQueue($batch)->withStatusProses($batch)->first();
        $this->assertEquals('TERVERIFIKASI / NON-REHAB', $pesertaFromDB->status_proses);
    }

    public function test_scenario_b_terdaftar_rehab()
    {
        $batch = Batch::factory()->create(['tanggal_data' => '2026-09-08']);
        $peserta = Peserta::factory()->create();
        PesertaBatch::create(['peserta_id' => $peserta->id, 'batch_id' => $batch->id, 'data_source' => 'excel']);

        $case = RehabCase::factory()->create(['peserta_id' => $peserta->id, 'status_rehab' => 'AKTIF']);
        $member = RehabCaseMember::factory()->create(['peserta_id' => $peserta->id, 'rehab_case_id' => $case->id]);

        RehabInstallment::factory()->create([
            'rehab_case_member_id' => $member->id,
            'periode_bulan' => '2026-09-01',
            'tanggal_bayar' => null, // Unpaid
            'besaran_cicilan' => 500000,
        ]);

        SippVerification::create([
            'peserta_id' => $peserta->id,
            'batch_id' => $batch->id,
            'rehab_case_id' => $case->id,
            'user_id' => $this->user->id,
            'terdaftar_rehab' => true,
            'tanggal_cek' => '2026-09-09',
        ]);

        $pesertaFromDB = Peserta::forWorkQueue($batch)->withStatusProses($batch)->first();
        // Since there is an active case with unpaid installments in this period, it should be PERLU FOLLOW-UP.
        $this->assertEquals('PERLU FOLLOW-UP', $pesertaFromDB->status_proses);
    }

    public function test_scenario_c_manual_member()
    {
        $batch = Batch::factory()->create(['tanggal_data' => '2026-09-08']);
        $headPeserta = Peserta::factory()->create();
        PesertaBatch::create(['peserta_id' => $headPeserta->id, 'batch_id' => $batch->id, 'data_source' => 'excel']);

        $case = RehabCase::factory()->create(['peserta_id' => $headPeserta->id, 'status_rehab' => 'AKTIF']);
        $headMember = RehabCaseMember::factory()->create(['peserta_id' => $headPeserta->id, 'rehab_case_id' => $case->id]);

        // Create a manual member
        $manualPeserta = Peserta::factory()->create();
        $manualMember = RehabCaseMember::factory()->create(['peserta_id' => $manualPeserta->id, 'rehab_case_id' => $case->id]);

        // The head should appear in the work queue
        $workQueueIds = Peserta::forWorkQueue($batch)->pluck('id')->toArray();
        $this->assertContains($headPeserta->id, $workQueueIds);
        // The manual member should NOT appear in the work queue as a primary row
        $this->assertNotContains($manualPeserta->id, $workQueueIds);
    }

    public function test_scenario_d_batch_isolation()
    {
        $batchAgustus = Batch::factory()->create(['tanggal_data' => '2026-08-01']);
        $batchSeptember = Batch::factory()->create(['tanggal_data' => '2026-09-01']);

        $peserta = Peserta::factory()->create();
        // Exists in August batch
        PesertaBatch::create(['peserta_id' => $peserta->id, 'batch_id' => $batchAgustus->id, 'data_source' => 'excel']);

        // Verify in August
        SippVerification::create([
            'peserta_id' => $peserta->id,
            'batch_id' => $batchAgustus->id,
            'rehab_case_id' => null,
            'user_id' => $this->user->id,
            'terdaftar_rehab' => false,
            'tanggal_cek' => '2026-08-05',
        ]);

        $pesertaAgustus = Peserta::forWorkQueue($batchAgustus)->withStatusProses($batchAgustus)->first();
        $this->assertEquals('TERVERIFIKASI / NON-REHAB', $pesertaAgustus->status_proses);

        // In September, they appear in Excel again
        PesertaBatch::create(['peserta_id' => $peserta->id, 'batch_id' => $batchSeptember->id, 'data_source' => 'excel']);

        $pesertaSeptember = Peserta::forWorkQueue($batchSeptember)->withStatusProses($batchSeptember)->first();
        // Since verification is isolated to batch_id, September should say BELUM DIVERIFIKASI
        $this->assertEquals('BELUM DIVERIFIKASI', $pesertaSeptember->status_proses);
    }

    public function test_scenario_e_follow_up()
    {
        $batchAgustus = Batch::factory()->create(['tanggal_data' => '2026-08-01']);
        $peserta = Peserta::factory()->create();
        PesertaBatch::create(['peserta_id' => $peserta->id, 'batch_id' => $batchAgustus->id, 'data_source' => 'excel']);

        $case = RehabCase::factory()->create(['peserta_id' => $peserta->id, 'status_rehab' => 'AKTIF']);
        $member = RehabCaseMember::factory()->create(['peserta_id' => $peserta->id, 'rehab_case_id' => $case->id]);

        RehabInstallment::factory()->create([
            'rehab_case_member_id' => $member->id,
            'periode_bulan' => '2026-08-01',
            'tanggal_bayar' => null,
            'besaran_cicilan' => 500000,
        ]);

        // New month: September (participant not in September excel batch)
        $batchSeptember = Batch::factory()->create(['tanggal_data' => '2026-09-01']);

        // Should appear in September work queue due to active rehab with unpaid past installment
        $pesertaInWorkQueue = Peserta::forWorkQueue($batchSeptember)->withTunggakanStats($batchSeptember)->withStatusProses($batchSeptember)->first();

        $this->assertNotNull($pesertaInWorkQueue);
        $this->assertEquals('PERLU FOLLOW-UP', $pesertaInWorkQueue->status_proses);
        $this->assertEquals(1, $pesertaInWorkQueue->jumlah_bulan_menunggak);
        $this->assertEquals(500000, $pesertaInWorkQueue->sisa_tunggakan);
    }
}
