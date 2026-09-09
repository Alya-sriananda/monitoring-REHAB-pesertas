<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Daerah;
use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RehabRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Peserta $peserta;

    protected Peserta $candidate1;

    protected Peserta $candidate2;

    protected Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'aktif' => true,
            'must_change_password' => false,
            'npp' => '999888777',
        ]);
        $daerah = Daerah::factory()->create();

        $this->peserta = Peserta::factory()->create([
            'daerah_id' => $daerah->id,
            'no_hp' => '08123456789',
        ]);

        $this->candidate1 = Peserta::factory()->create([
            'daerah_id' => $daerah->id,
            'no_hp' => '08123456789',
        ]);

        $this->candidate2 = Peserta::factory()->create([
            'daerah_id' => $daerah->id,
            'no_hp' => '08123456789',
        ]);

        $this->batch = Batch::factory()->create();
    }

    public function test_unauthorized_user_cannot_create_case()
    {
        $response = $this->post(route('peserta.rehab.store', $this->peserta->id), []);
        $response->assertRedirect('/login');
    }

    public function test_valid_registration_creates_case_members_installments_and_sipp_verification()
    {
        $now = now();
        Carbon::setTestNow($now);

        $payload = [
            'batch_id' => $this->batch->id,
            'sipp_terdaftar_rehab' => true,
            'sipp_noka_pendaftar' => $this->peserta->noka,
            'tanggal_pendaftaran' => '2026-09-01',
            'jumlah_bulan_cicilan' => 4,
            'members' => [
                [
                    'peserta_id' => $this->peserta->id,
                    'tagihan_awal' => 1200000,
                    'is_pendaftar' => true,
                ],
                [
                    'peserta_id' => $this->candidate1->id,
                    'tagihan_awal' => 600000,
                    'is_pendaftar' => false,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('peserta.rehab.store', $this->peserta->id), $payload);

        $response->assertRedirect(route('peserta.show', $this->peserta->id));
        $response->assertSessionHas('success');

        // Verify RehabCase
        $this->assertDatabaseHas('rehab_cases', [
            'peserta_id' => $this->peserta->id,
            'status_rehab' => 'AKTIF',
            'npp_petugas' => '999888777',
        ]);

        $case = RehabCase::first();

        // Verify SippVerification
        $this->assertDatabaseHas('sipp_verifications', [
            'peserta_id' => $this->peserta->id,
            'rehab_case_id' => $case->id,
            'tanggal_cek' => $now->toDateTimeString(),
            'terdaftar_rehab' => 1,
            'npp_petugas' => '999888777',
        ]);

        // Verify Members (only 2 out of 3 participants)
        $this->assertDatabaseCount('rehab_case_members', 2);
        $this->assertDatabaseHas('rehab_case_members', [
            'peserta_id' => $this->peserta->id,
            'tagihan_awal' => 1200000,
        ]);
        $this->assertDatabaseHas('rehab_case_members', [
            'peserta_id' => $this->candidate1->id,
            'tagihan_awal' => 600000,
        ]);
        $this->assertDatabaseMissing('rehab_case_members', [
            'peserta_id' => $this->candidate2->id,
        ]);

        // Verify Installments Generated (2 members * 4 months = 8 installments)
        $this->assertDatabaseCount('rehab_installments', 8);
    }

    public function test_unregistered_rehab_creates_verification_only_without_case()
    {
        $now = now();
        Carbon::setTestNow($now);

        $payload = [
            'batch_id' => $this->batch->id,
            'sipp_terdaftar_rehab' => false,
            'members' => [
                [
                    'peserta_id' => $this->peserta->id,
                ],
                [
                    'peserta_id' => $this->candidate1->id,
                ],
                [
                    'peserta_id' => null, // Manual non-rehab check
                    'nama' => 'Adik Manual',
                    'noka' => '888899990000',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('peserta.rehab.store', $this->peserta->id), $payload);

        $response->assertRedirect(route('peserta.show', $this->peserta->id));
        $response->assertSessionHas('success', 'Verifikasi SIPP berhasil disimpan.');

        // Verify SippVerification
        $this->assertDatabaseHas('sipp_verifications', [
            'peserta_id' => $this->peserta->id,
            'rehab_case_id' => null,
            'terdaftar_rehab' => 0,
            'npp_petugas' => '999888777',
        ]);
        $this->assertDatabaseHas('sipp_verifications', [
            'peserta_id' => $this->candidate1->id,
            'rehab_case_id' => null,
            'terdaftar_rehab' => 0,
            'npp_petugas' => '999888777',
        ]);
        $newPeserta = Peserta::where('noka', '888899990000')->first();
        $this->assertNotNull($newPeserta);
        $this->assertDatabaseHas('sipp_verifications', [
            'peserta_id' => $newPeserta->id,
            'rehab_case_id' => null,
            'terdaftar_rehab' => 0,
            'npp_petugas' => '999888777',
        ]);

        $this->assertDatabaseCount('sipp_verifications', 3);

        // Verify RehabCase was NOT created
        $this->assertDatabaseCount('rehab_cases', 0);
        $this->assertDatabaseCount('rehab_case_members', 0);
        $this->assertDatabaseCount('rehab_installments', 0);
    }

    public function test_cannot_overwrite_active_historical_case()
    {
        // Setup an active case
        $case = RehabCase::factory()->create([
            'peserta_id' => $this->peserta->id,
            'status_rehab' => 'AKTIF',
        ]);
        RehabCaseMember::factory()->create([
            'rehab_case_id' => $case->id,
            'peserta_id' => $this->peserta->id,
        ]);

        $payload = [
            'batch_id' => $this->batch->id,
            'sipp_terdaftar_rehab' => true,
            'tanggal_pendaftaran' => '2026-09-01',
            'jumlah_bulan_cicilan' => 4,
            'members' => [
                [
                    'peserta_id' => $this->peserta->id,
                    'tagihan_awal' => 1200000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('peserta.rehab.store', $this->peserta->id), $payload);

        // Assert error
        $response->assertSessionHasErrors(['members']);

        // Assert no new case was created
        $this->assertDatabaseCount('rehab_cases', 1);
    }

    public function test_validation_fails_on_missing_financial_fields()
    {
        $payload = [
            'batch_id' => $this->batch->id,
            'sipp_terdaftar_rehab' => true,
            // missing tanggal_pendaftaran
            // missing jumlah_bulan_cicilan
            'members' => [
                [
                    'peserta_id' => $this->peserta->id,
                    // missing tagihan_awal
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('peserta.rehab.store', $this->peserta->id), $payload);

        $response->assertSessionHasErrors([
            'tanggal_pendaftaran',
            'jumlah_bulan_cicilan',
            'members.0.tagihan_awal',
        ]);

        $this->assertDatabaseCount('rehab_cases', 0);
    }

    public function test_manual_participant_addition()
    {
        $payload = [
            'batch_id' => $this->batch->id,
            'sipp_terdaftar_rehab' => true,
            'tanggal_pendaftaran' => '2026-09-01',
            'jumlah_bulan_cicilan' => 1,
            'members' => [
                [
                    'peserta_id' => $this->peserta->id,
                    'tagihan_awal' => 1000000,
                ],
                [
                    'peserta_id' => null, // Manual
                    'nama' => 'Adik Manual',
                    'noka' => '888899990000',
                    'tagihan_awal' => 500000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('peserta.rehab.store', $this->peserta->id), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Check if new Peserta was created and inherited data
        $this->assertDatabaseHas('pesertas', [
            'noka' => '888899990000',
            'nama' => 'Adik Manual',
            'no_hp' => $this->peserta->no_hp,
            'daerah_id' => $this->peserta->daerah_id,
        ]);

        $newPeserta = Peserta::where('noka', '888899990000')->first();

        // Check if added to RehabCaseMember
        $this->assertDatabaseHas('rehab_case_members', [
            'peserta_id' => $newPeserta->id,
            'tagihan_awal' => 500000,
        ]);
    }
}
