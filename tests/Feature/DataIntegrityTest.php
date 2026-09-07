<?php

namespace Tests\Feature;

use App\Models\Daerah;
use App\Models\Komunikasi;
use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\RehabInstallment;
use App\Models\SippVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sisa_tunggakan_recalculates_on_installment_payment()
    {
        $user = User::factory()->create(['role' => 'petugas', 'aktif' => true]);
        $daerah = Daerah::factory()->create();
        $peserta = Peserta::factory()->create(['daerah_id' => $daerah->id]);

        $case = RehabCase::factory()->create([
            'peserta_id' => $peserta->id,
            'sisa_tunggakan' => 500000,
        ]);

        $member = RehabCaseMember::factory()->create([
            'rehab_case_id' => $case->id,
            'peserta_id' => $peserta->id,
            'tagihan_awal' => 500000,
            'sisa_tunggakan' => 500000,
            'cicilan_bulanan' => 100000,
        ]);

        // Create unpaid installment
        $installment = RehabInstallment::factory()->create([
            'rehab_case_member_id' => $member->id,
            'besaran_cicilan' => 100000,
            'tanggal_bayar' => null,
        ]);

        $this->assertEquals(500000, $member->fresh()->sisa_tunggakan);
        $this->assertEquals(500000, $case->fresh()->sisa_tunggakan);

        // Pay installment
        $installment->update(['tanggal_bayar' => '2026-09-01']);

        $this->assertEquals(400000, $member->fresh()->sisa_tunggakan);
        $this->assertEquals(400000, $case->fresh()->sisa_tunggakan);

        // Delete installment
        $installment->delete();

        $this->assertEquals(500000, $member->fresh()->sisa_tunggakan);
        $this->assertEquals(500000, $case->fresh()->sisa_tunggakan);
    }

    public function test_sipp_verification_saves_correctly_with_new_fields()
    {
        $case = RehabCase::factory()->create();
        $user = User::factory()->create();

        $sipp = SippVerification::factory()->create([
            'rehab_case_id' => $case->id,
            'user_id' => $user->id,
            'tagihan_bulan_berjalan' => 150000,
            'tagihan_sebelum_bulan_berjalan' => 2000000,
            'status_pembayaran_bulan_berjalan' => 'BELUM LUNAS',
        ]);

        $this->assertDatabaseHas('sipp_verifications', [
            'id' => $sipp->id,
            'tagihan_bulan_berjalan' => 150000,
            'tagihan_sebelum_bulan_berjalan' => 2000000,
            'status_pembayaran_bulan_berjalan' => 'BELUM LUNAS',
        ]);

        $this->assertEquals($case->id, $sipp->case->id);
    }

    public function test_komunikasi_saves_correctly_with_relationships()
    {
        $case = RehabCase::factory()->create();
        $peserta = $case->peserta;
        $user = User::factory()->create();

        $komunikasi = Komunikasi::create([
            'rehab_case_id' => $case->id,
            'peserta_id' => $peserta->id,
            'user_id' => $user->id,
            'no_hp' => '081234567890',
            'periode_bulan' => '2026-09-01',
            'pesan' => 'Halo ini tes',
            'status' => 'sudah_dihubungi',
            'tanggal_dihubungi' => '2026-09-06 10:00:00',
        ]);

        $this->assertDatabaseHas('komunikasis', [
            'id' => $komunikasi->id,
            'rehab_case_id' => $case->id,
            'status' => 'sudah_dihubungi',
        ]);

        $this->assertEquals($case->id, $komunikasi->case->id);
        $this->assertEquals($peserta->id, $komunikasi->peserta->id);
        $this->assertEquals($user->id, $komunikasi->user->id);
    }
}
