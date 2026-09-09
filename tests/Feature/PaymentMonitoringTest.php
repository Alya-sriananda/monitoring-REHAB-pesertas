<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\RehabInstallment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $rehabCase;

    protected $member1;

    protected $member2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['must_change_password' => false]);

        $peserta1 = Peserta::factory()->create();
        $peserta2 = Peserta::factory()->create();

        $this->rehabCase = RehabCase::factory()->create([
            'peserta_id' => $peserta1->id,
            'status_rehab' => 'AKTIF',
        ]);

        $this->member1 = RehabCaseMember::factory()->create([
            'rehab_case_id' => $this->rehabCase->id,
            'peserta_id' => $peserta1->id,
        ]);

        $this->member2 = RehabCaseMember::factory()->create([
            'rehab_case_id' => $this->rehabCase->id,
            'peserta_id' => $peserta2->id,
        ]);
    }

    private function createInstallments(string $periode, $member1Amount = 320000, $member2Amount = 320000)
    {
        $i1 = RehabInstallment::factory()->create([
            'rehab_case_member_id' => $this->member1->id,
            'periode_bulan' => $periode,
            'besaran_cicilan' => $member1Amount,
            'tanggal_bayar' => null,
        ]);

        $i2 = RehabInstallment::factory()->create([
            'rehab_case_member_id' => $this->member2->id,
            'periode_bulan' => $periode,
            'besaran_cicilan' => $member2Amount,
            'tanggal_bayar' => null,
        ]);

        return [$i1, $i2];
    }

    public function test_default_belum_bayar_and_input_tanggal_bayar_atomic_update()
    {
        $this->createInstallments('2026-09-01');

        $response = $this->actingAs($this->user)->post(route('rehab-cases.payments.store', $this->rehabCase->id), [
            'periode_bulan' => '2026-09-01',
            'tanggal_bayar' => '2026-09-15',
        ]);

        $response->assertSessionHas('success');

        $this->assertEquals('2026-09-15', RehabInstallment::where('rehab_case_member_id', $this->member1->id)
            ->whereDate('periode_bulan', '2026-09-01')->first()->tanggal_bayar->format('Y-m-d'));

        $this->assertEquals('2026-09-15', RehabInstallment::where('rehab_case_member_id', $this->member2->id)
            ->whereDate('periode_bulan', '2026-09-01')->first()->tanggal_bayar->format('Y-m-d'));
    }

    public function test_edit_tanggal_pembayaran()
    {
        $installments = $this->createInstallments('2026-09-01');
        foreach ($installments as $inst) {
            $inst->update(['tanggal_bayar' => '2026-09-15']);
        }

        $response = $this->actingAs($this->user)->post(route('rehab-cases.payments.store', $this->rehabCase->id), [
            'periode_bulan' => '2026-09-01',
            'tanggal_bayar' => '2026-09-16',
        ]);

        $this->assertEquals('2026-09-16', RehabInstallment::where('rehab_case_member_id', $this->member1->id)
            ->first()->tanggal_bayar->format('Y-m-d'));
    }

    public function test_clear_payment_date()
    {
        $installments = $this->createInstallments('2026-09-01');
        foreach ($installments as $inst) {
            $inst->update(['tanggal_bayar' => '2026-09-15']);
        }

        $response = $this->actingAs($this->user)->post(route('rehab-cases.payments.store', $this->rehabCase->id), [
            'periode_bulan' => '2026-09-01',
            'tanggal_bayar' => null,
        ]);

        $this->assertNull(RehabInstallment::where('rehab_case_member_id', $this->member1->id)
            ->first()->tanggal_bayar);
    }

    public function test_different_period_isolation()
    {
        $this->createInstallments('2026-09-01');
        $this->createInstallments('2026-10-01');

        $this->actingAs($this->user)->post(route('rehab-cases.payments.store', $this->rehabCase->id), [
            'periode_bulan' => '2026-09-01',
            'tanggal_bayar' => '2026-09-15',
        ]);

        $this->assertEquals('2026-09-15', RehabInstallment::whereDate('periode_bulan', '2026-09-01')->first()->tanggal_bayar->format('Y-m-d'));
        $this->assertNull(RehabInstallment::whereDate('periode_bulan', '2026-10-01')->first()->tanggal_bayar);
    }

    public function test_different_case_isolation()
    {
        $this->createInstallments('2026-09-01');

        // Create case B
        $caseB = RehabCase::factory()->create();
        $memberB = RehabCaseMember::factory()->create(['rehab_case_id' => $caseB->id]);
        RehabInstallment::factory()->create([
            'rehab_case_member_id' => $memberB->id,
            'periode_bulan' => '2026-09-01',
            'tanggal_bayar' => null,
        ]);

        $this->actingAs($this->user)->post(route('rehab-cases.payments.store', $this->rehabCase->id), [
            'periode_bulan' => '2026-09-01',
            'tanggal_bayar' => '2026-09-15',
        ]);

        $this->assertEquals('2026-09-15', RehabInstallment::where('rehab_case_member_id', $this->member1->id)->first()->tanggal_bayar->format('Y-m-d'));
        $this->assertNull(RehabInstallment::where('rehab_case_member_id', $memberB->id)->first()->tanggal_bayar);
    }
}
