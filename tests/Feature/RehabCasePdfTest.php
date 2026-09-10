<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\RehabInstallment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class RehabCasePdfTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $case;

    protected $member1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'must_change_password' => false,
        ]);

        $batch = Batch::create([
            'tanggal_data' => now()->subDays(5),
            'nama_file' => 'test.xlsx',
            'file_hash' => 'hash',
        ]);

        $peserta = Peserta::create([
            'noka' => '1234567890123',
            'nama' => 'Baharudin',
        ]);

        $this->case = RehabCase::create([
            'peserta_id' => $peserta->id,
            'noka_pendaftar' => '1234567890123',
            'created_from_batch_id' => $batch->id,
            'tanggal_pendaftaran' => '2026-05-01',
            'jumlah_bulan_cicilan' => 12,
            'status_rehab' => 'AKTIF',
        ]);

        $this->member1 = RehabCaseMember::create([
            'rehab_case_id' => $this->case->id,
            'peserta_id' => $peserta->id,
            'data_source' => 'test',
            'tagihan_awal' => 3600000,
            'sisa_tunggakan' => 3600000,
            'cicilan_bulanan' => 300000,
            'jml_bulan_menunggak_awal' => 12,
        ]);
    }

    protected function mockPdf($callback)
    {
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.tagihan', \Mockery::on($callback))
            ->andReturnSelf();
        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('setWarnings')->andReturnSelf();
        Pdf::shouldReceive('stream')->andReturn(new Response('pdf_content', 200, ['Content-Type' => 'application/pdf']));
    }

    public function test_case_1_sebagian_sudah_bayar()
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10'));

        $periods = ['2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'];
        foreach ($periods as $idx => $p) {
            RehabInstallment::create([
                'rehab_case_member_id' => $this->member1->id,
                'nomor_cicilan' => $idx + 1,
                'periode_bulan' => $p.'-01',
                'besaran_cicilan' => 300000,
                'tanggal_bayar' => in_array($p, ['2026-05', '2026-06']) ? $p.'-05' : null,
            ]);
        }

        $this->mockPdf(function ($data) {
            $this->assertEquals(900000, $data['totalTunggakan']);
            $this->assertFalse($data['isLunas']);
            $this->assertCount(3, $data['rincianTagihan']); // Juli, Agustus, September

            return true;
        });

        $response = $this->actingAs($this->user)->get("/rehab-cases/{$this->case->id}/pdf");
        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_case_2_semua_sudah_lunas()
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10'));

        $periods = ['2026-05', '2026-06', '2026-07', '2026-08', '2026-09'];
        foreach ($periods as $idx => $p) {
            RehabInstallment::create([
                'rehab_case_member_id' => $this->member1->id,
                'nomor_cicilan' => $idx + 1,
                'periode_bulan' => $p.'-01',
                'besaran_cicilan' => 300000,
                'tanggal_bayar' => clone now(), // all paid
            ]);
        }

        $this->mockPdf(function ($data) {
            $this->assertTrue($data['isLunas']);
            $this->assertEquals(0, $data['totalTunggakan']);
            $this->assertCount(0, $data['rincianTagihan']);
            $this->assertStringContainsString('telah lunas', $data['statusMessage']);

            return true;
        });

        $response = $this->actingAs($this->user)->get("/rehab-cases/{$this->case->id}/pdf");
        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_case_3_36_bulan_cicilan()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10'));

        for ($i = 1; $i <= 36; $i++) {
            $month = Carbon::parse('2026-01-01')->addMonths($i - 1);
            $paid = in_array($i, [1, 2, 3, 4, 5]) ? clone now() : null;

            RehabInstallment::create([
                'rehab_case_member_id' => $this->member1->id,
                'nomor_cicilan' => $i,
                'periode_bulan' => $month->format('Y-m-01'),
                'besaran_cicilan' => 100000,
                'tanggal_bayar' => $paid,
            ]);
        }

        $this->mockPdf(function ($data) {
            $this->assertEquals(300000, $data['totalTunggakan']); // June, July, August
            $this->assertCount(3, $data['rincianTagihan']);
            $this->assertFalse($data['isLunas']);

            return true;
        });

        $response = $this->actingAs($this->user)->get("/rehab-cases/{$this->case->id}/pdf");
        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_case_4_future_installment()
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10'));

        $periods = ['2026-10', '2026-11', '2026-12']; // Future unpaid
        foreach ($periods as $idx => $p) {
            RehabInstallment::create([
                'rehab_case_member_id' => $this->member1->id,
                'nomor_cicilan' => $idx + 1,
                'periode_bulan' => $p.'-01',
                'besaran_cicilan' => 300000,
                'tanggal_bayar' => null,
            ]);
        }

        $this->mockPdf(function ($data) {
            $this->assertTrue($data['isLunas']); // up to September it's lunas
            $this->assertEquals(0, $data['totalTunggakan']);

            return true;
        });

        $response = $this->actingAs($this->user)->get("/rehab-cases/{$this->case->id}/pdf");
        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_case_6_multiple_family_members()
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10'));

        $member2 = RehabCaseMember::create([
            'rehab_case_id' => $this->case->id,
            'peserta_id' => Peserta::factory()->create(['nama' => 'Syapia'])->id,
            'data_source' => 'test',
            'tagihan_awal' => 3600000,
            'sisa_tunggakan' => 3600000,
            'cicilan_bulanan' => 300000,
            'jml_bulan_menunggak_awal' => 12,
        ]);

        $member3 = RehabCaseMember::create([
            'rehab_case_id' => $this->case->id,
            'peserta_id' => Peserta::factory()->create(['nama' => 'Yarwanis'])->id,
            'data_source' => 'test',
            'tagihan_awal' => 3600000,
            'sisa_tunggakan' => 3600000,
            'cicilan_bulanan' => 300000,
            'jml_bulan_menunggak_awal' => 12,
        ]);

        // Baharudin September unpaid
        RehabInstallment::create([
            'rehab_case_member_id' => $this->member1->id,
            'nomor_cicilan' => 1,
            'periode_bulan' => '2026-09-01',
            'besaran_cicilan' => 320000,
            'tanggal_bayar' => null,
        ]);

        // Syapia September unpaid
        RehabInstallment::create([
            'rehab_case_member_id' => $member2->id,
            'nomor_cicilan' => 1,
            'periode_bulan' => '2026-09-01',
            'besaran_cicilan' => 320000,
            'tanggal_bayar' => null,
        ]);

        // Yarwanis September paid
        RehabInstallment::create([
            'rehab_case_member_id' => $member3->id,
            'nomor_cicilan' => 1,
            'periode_bulan' => '2026-09-01',
            'besaran_cicilan' => 320000,
            'tanggal_bayar' => clone now(),
        ]);

        $this->mockPdf(function ($data) {
            $this->assertEquals(640000, $data['totalTunggakan']);
            $this->assertCount(1, $data['rincianTagihan']);
            $breakdown = $data['rincianTagihan'][0]['member_breakdown'];
            $this->assertArrayHasKey('Baharudin', $breakdown);
            $this->assertArrayHasKey('Syapia', $breakdown);
            $this->assertArrayNotHasKey('Yarwanis', $breakdown);

            return true;
        });

        $response = $this->actingAs($this->user)->get("/rehab-cases/{$this->case->id}/pdf");
        $response->assertStatus(200);

        Carbon::setTestNow();
    }
}
