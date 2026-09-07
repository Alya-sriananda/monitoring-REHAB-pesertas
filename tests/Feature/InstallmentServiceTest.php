<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Models\RehabCaseMember;
use App\Services\InstallmentService;
use App\Services\RehabCaseService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InstallmentService $installmentService;

    protected RehabCaseService $rehabCaseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installmentService = app(InstallmentService::class);
        $this->rehabCaseService = app(RehabCaseService::class);
    }

    /**
     * Test 1: Tagihan dibagi rata jika habis dibagi.
     */
    public function test_tagihan_dibagi_rata_jika_habis_dibagi()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 1200000,
        ]);

        $installments = $this->installmentService->generateSchedule($member, '2026-08-17', 4);

        $this->assertCount(4, $installments);
        $this->assertEquals(300000, $installments[0]->besaran_cicilan);
        $this->assertEquals(300000, $installments[1]->besaran_cicilan);
        $this->assertEquals(300000, $installments[2]->besaran_cicilan);
        $this->assertEquals(300000, $installments[3]->besaran_cicilan);
    }

    /**
     * Test 2, 3, 15: Pembagian pecahan, total sama, selisih masuk cicilan terakhir.
     */
    public function test_pembagian_pecahan_selisih_masuk_cicilan_terakhir_dan_total_persis()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 1000000,
        ]);

        $installments = $this->installmentService->generateSchedule($member, '2026-08-17', 3);

        $this->assertCount(3, $installments);
        $this->assertEquals(333333, $installments[0]->besaran_cicilan);
        $this->assertEquals(333333, $installments[1]->besaran_cicilan);
        $this->assertEquals(333334, $installments[2]->besaran_cicilan);

        $total = $installments->sum('besaran_cicilan');
        $this->assertEquals(1000000, $total);
    }

    /**
     * Test 4: Jumlah installment sama dengan jumlah bulan cicilan.
     */
    public function test_jumlah_installment_sama_dengan_jumlah_bulan()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 500000,
        ]);

        $installments = $this->installmentService->generateSchedule($member, '2026-01-01', 5);
        $this->assertCount(5, $installments);
    }

    /**
     * Test 5, 6, 7: Periode dimulai pada awal bulan tanggal_pendaftaran dan maju satu bulan.
     */
    public function test_periode_dimulai_pada_awal_bulan_pendaftaran_dan_maju_satu_bulan()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 300000,
        ]);

        $installments = $this->installmentService->generateSchedule($member, '2026-08-17', 3);

        $this->assertEquals('2026-08-01', $installments[0]->periode_bulan->format('Y-m-d'));
        $this->assertEquals('2026-09-01', $installments[1]->periode_bulan->format('Y-m-d'));
        $this->assertEquals('2026-10-01', $installments[2]->periode_bulan->format('Y-m-d'));
    }

    /**
     * Test 8, 9: tanggal_bayar default NULL, status derived.
     * (Derived status is tested logic-wise, here we check the value is null).
     */
    public function test_tanggal_bayar_default_null()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 100000,
        ]);

        $installments = $this->installmentService->generateSchedule($member, '2026-08-17', 1);

        $this->assertNull($installments[0]->tanggal_bayar);
    }

    /**
     * Test 10: Total cicilan seluruh installment = tagihan awal
     */
    public function test_total_cicilan_seluruh_installment_sama_dengan_tagihan_awal()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 1000000,
        ]);

        $installments = $this->installmentService->generateSchedule($member, '2026-08-17', 6);

        $this->assertEquals(1000000, $installments->sum('besaran_cicilan'));
    }

    /**
     * Test 11: Total cicilan keluarga = SUM cicilan anggota.
     */
    public function test_total_cicilan_keluarga_adalah_sum_cicilan_anggota()
    {
        $caseData = [
            'peserta_id' => Peserta::factory()->create()->id,
            'noka_pendaftar' => '123456789',
            'tanggal_pendaftaran' => '2026-08-17',
            'jumlah_bulan_cicilan' => 4,
            'status_rehab' => 'AKTIF',
        ];

        $membersData = [
            [
                'peserta_id' => Peserta::factory()->create()->id,
                'tagihan_awal' => 1200000,
                'is_pendaftar' => true,
            ],
            [
                'peserta_id' => Peserta::factory()->create()->id,
                'tagihan_awal' => 600000,
            ],
            [
                'peserta_id' => Peserta::factory()->create()->id,
                'tagihan_awal' => 600000,
            ],
        ];

        $rehabCase = $this->rehabCaseService->createCaseWithMembers($caseData, $membersData);

        // member 1: 1.2jt / 4 = 300k
        // member 2: 600k / 4 = 150k
        // member 3: 600k / 4 = 150k
        // total keluarga per bulan = 600k
        $this->assertCount(3, $rehabCase->members);
        $totalCicilanKeluargaPerBulan = $rehabCase->members->sum('cicilan_bulanan');
        $this->assertEquals(600000, $totalCicilanKeluargaPerBulan);
    }

    /**
     * Test 12 & 13: Jadwal tidak dibuat ulang saat bulan berubah / historical case tidak tertimpa.
     * These are domain rules that are enforced by not having logic that blindly overwrites existing cases.
     * The `generateSchedule` generates it ONCE. The service does not have an "auto update on new month" function.
     * We can test that generating twice for the same member throws an error or creates duplicates (which we prevent by constraint).
     */
    public function test_schedule_generation_is_idempotent_or_throws_on_duplicate()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 300000,
        ]);

        $this->installmentService->generateSchedule($member, '2026-08-17', 3);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/Integrity constraint violation/i');

        // This should fail due to unique constraint on rehab_case_member_id + periode_bulan
        $this->installmentService->generateSchedule($member, '2026-08-17', 3);
    }

    /**
     * Test 14: Kasus dengan 1 bulan tetap menghasilkan 1 installment penuh.
     */
    public function test_kasus_satu_bulan_menghasilkan_satu_installment_penuh()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 1500000,
        ]);

        $installments = $this->installmentService->generateSchedule($member, '2026-08-17', 1);

        $this->assertCount(1, $installments);
        $this->assertEquals(1500000, $installments[0]->besaran_cicilan);
    }
}
