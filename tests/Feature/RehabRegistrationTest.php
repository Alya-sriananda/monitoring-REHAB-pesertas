<?php

namespace Tests\Feature;

use App\Models\Daerah;
use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RehabRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Peserta $peserta;

    protected Peserta $candidate1;

    protected Peserta $candidate2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'aktif' => true,
            'must_change_password' => false,
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
    }

    public function test_unauthorized_user_cannot_create_case()
    {
        $response = $this->post(route('peserta.rehab.store', $this->peserta->id), []);
        $response->assertRedirect('/login');
    }

    public function test_valid_registration_creates_case_members_installments_and_sipp_verification()
    {
        $payload = [
            'sipp_tanggal_cek' => '2026-09-01',
            'sipp_terdaftar_rehab' => true,
            'sipp_status_rehab' => 'AKTIF',
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
        ]);

        $case = RehabCase::first();

        // Verify SippVerification
        $this->assertDatabaseHas('sipp_verifications', [
            'rehab_case_id' => $case->id,
            'tanggal_cek' => '2026-09-01 00:00:00',
            'terdaftar_rehab' => 1,
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
            'sipp_tanggal_cek' => '2026-09-01',
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
            'sipp_tanggal_cek' => '2026-09-01',
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
}
