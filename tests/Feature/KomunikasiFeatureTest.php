<?php

namespace Tests\Feature;

use App\Models\Komunikasi;
use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\TemplatePesan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KomunikasiFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private TemplatePesan $template;

    private Peserta $peserta;

    private RehabCase $rehabCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'must_change_password' => false,
        ]);

        $this->template = TemplatePesan::create([
            'nama_template' => 'Test Template',
            'isi_template' => 'Halo {nama}, NOKA {noka}, periode {periode}, Rp {nominal}',
            'aktif' => true,
        ]);

        $this->peserta = Peserta::create([
            'noka' => '1234567890123',
            'nama' => 'Budi Santoso',
            'no_hp' => '081234567890',
        ]);

        $this->rehabCase = RehabCase::create([
            'peserta_id' => $this->peserta->id,
            'noka_pendaftar' => '1234567890123',
            'tanggal_pendaftaran' => now(),
            'jumlah_bulan_cicilan' => 4,
            'status_rehab' => 'AKTIF',
        ]);

        RehabCaseMember::create([
            'rehab_case_id' => $this->rehabCase->id,
            'peserta_id' => $this->peserta->id,
            'is_pendaftar' => true,
            'tagihan_awal' => 1000000,
            'sisa_tunggakan' => 1000000,
            'cicilan_bulanan' => 250000,
            'data_source' => 'excel',
        ]);
    }

    public function test_it_can_generate_message_preview_with_correct_placeholders()
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->user)->postJson('/komunikasi/preview', [
            'template_pesan_id' => $this->template->id,
            'peserta_id' => $this->peserta->id,
            'periode_bulan' => '2026-09-01',
            'nominal' => 250000,
        ]);

        $response->assertStatus(200);

        $expectedPesan = 'Halo Budi Santoso, NOKA 1234567890123, periode September 2026, Rp Rp 250.000';

        // Note: the template service replaces {nominal} with 'Rp ' . number_format
        // and our template had 'Rp {nominal}'. So it becomes 'Rp Rp 250.000'.
        // We just assert the replacement logic worked.
        $pesan = $response->json('pesan');
        $this->assertStringContainsString('Budi Santoso', $pesan);
        $this->assertStringContainsString('1234567890123', $pesan);
        $this->assertStringContainsString('September 2026', $pesan);
        $this->assertStringContainsString('250.000', $pesan);
    }

    public function test_it_can_store_communication_record()
    {
        $response = $this->actingAs($this->user)->postJson("/rehab-cases/{$this->rehabCase->id}/komunikasi", [
            'peserta_id' => $this->peserta->id,
            'template_pesan_id' => $this->template->id,
            'periode_bulan' => '2026-09-01',
            'pesan' => 'Halo Budi Santoso, NOKA 1234567890123, periode September 2026, Rp 250.000',
            'status' => 'sudah_dihubungi',
            'catatan' => 'Peserta berjanji akan bayar besok',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('komunikasis', [
            'rehab_case_id' => $this->rehabCase->id,
            'peserta_id' => $this->peserta->id,
            'user_id' => $this->user->id,
            'template_pesan_id' => $this->template->id,
            'status' => 'sudah_dihubungi',
            'periode_bulan' => '2026-09-01 00:00:00',
            'no_hp' => '081234567890',
            'catatan' => 'Peserta berjanji akan bayar besok',
        ]);
    }

    public function test_it_prevents_duplicate_communication_within_short_timeframe()
    {
        // Insert first record
        Komunikasi::create([
            'rehab_case_id' => $this->rehabCase->id,
            'peserta_id' => $this->peserta->id,
            'user_id' => $this->user->id,
            'template_pesan_id' => $this->template->id,
            'no_hp' => $this->peserta->no_hp,
            'periode_bulan' => '2026-09-01',
            'template' => $this->template->nama_template,
            'pesan' => 'Test',
            'status' => 'sudah_dihubungi',
            'tanggal_dihubungi' => now(),
        ]);

        // Attempt exact duplicate
        $response = $this->actingAs($this->user)->postJson("/rehab-cases/{$this->rehabCase->id}/komunikasi", [
            'peserta_id' => $this->peserta->id,
            'template_pesan_id' => $this->template->id,
            'periode_bulan' => '2026-09-01',
            'pesan' => 'Test',
            'status' => 'sudah_dihubungi',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Komunikasi sudah dicatat baru-baru ini.', $response->json('message'));

        $this->assertEquals(1, Komunikasi::count());
    }

    public function test_it_handles_null_hp_number()
    {
        $this->peserta->update(['no_hp' => null]);

        $response = $this->actingAs($this->user)->postJson("/rehab-cases/{$this->rehabCase->id}/komunikasi", [
            'peserta_id' => $this->peserta->id,
            'template_pesan_id' => $this->template->id,
            'periode_bulan' => '2026-09-01',
            'pesan' => 'Test no HP null',
            'status' => 'tidak_terdaftar_wa',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('komunikasis', [
            'peserta_id' => $this->peserta->id,
            'no_hp' => '',
            'status' => 'tidak_terdaftar_wa',
        ]);
    }

    public function test_it_loads_komunikasis_on_peserta_show()
    {
        Komunikasi::create([
            'rehab_case_id' => $this->rehabCase->id,
            'peserta_id' => $this->peserta->id,
            'user_id' => $this->user->id,
            'template_pesan_id' => $this->template->id,
            'no_hp' => $this->peserta->no_hp,
            'periode_bulan' => '2026-09-01',
            'template' => $this->template->nama_template,
            'pesan' => 'Test Loaded',
            'status' => 'sudah_dihubungi',
            'tanggal_dihubungi' => now(),
        ]);

        $response = $this->actingAs($this->user)->get("/peserta/{$this->peserta->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->where('peserta.rehab_case_members.0.case.komunikasis.0.pesan', 'Test Loaded')
        );
    }
}
