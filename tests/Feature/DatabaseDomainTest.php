<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Daerah;
use App\Models\Peserta;
use App\Models\PesertaBatch;
use App\Models\RehabCase;
use App\Models\RehabCaseMember;
use App\Models\RehabInstallment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_can_be_instantiated_with_factories()
    {
        $daerah = Daerah::factory()->create();
        $this->assertDatabaseHas('daerah', ['id' => $daerah->id]);

        $batch = Batch::factory()->create();
        $this->assertDatabaseHas('batches', ['id' => $batch->id]);

        $peserta = Peserta::factory()->create(['daerah_id' => $daerah->id]);
        $this->assertDatabaseHas('pesertas', ['id' => $peserta->id]);

        $pesertaBatch = PesertaBatch::factory()->create([
            'batch_id' => $batch->id,
            'peserta_id' => $peserta->id,
        ]);
        $this->assertDatabaseHas('peserta_batches', ['id' => $pesertaBatch->id]);

        $rehabCase = RehabCase::factory()->create([
            'peserta_id' => $peserta->id,
            'created_from_batch_id' => $batch->id,
        ]);
        $this->assertDatabaseHas('rehab_cases', ['id' => $rehabCase->id]);

        $member = RehabCaseMember::factory()->create([
            'rehab_case_id' => $rehabCase->id,
            'peserta_id' => $peserta->id,
        ]);
        $this->assertDatabaseHas('rehab_case_members', ['id' => $member->id]);

        $installment = RehabInstallment::factory()->create([
            'rehab_case_member_id' => $member->id,
        ]);
        $this->assertDatabaseHas('rehab_installments', ['id' => $installment->id]);
    }

    public function test_peserta_can_be_associated_with_multiple_batches()
    {
        $peserta = Peserta::factory()->create();
        $batch1 = Batch::factory()->create();
        $batch2 = Batch::factory()->create();

        PesertaBatch::factory()->create([
            'batch_id' => $batch1->id,
            'peserta_id' => $peserta->id,
        ]);

        PesertaBatch::factory()->create([
            'batch_id' => $batch2->id,
            'peserta_id' => $peserta->id,
        ]);

        $this->assertCount(2, $peserta->batches);
    }

    public function test_rehab_case_can_have_multiple_family_members()
    {
        $case = RehabCase::factory()->create();

        $peserta1 = Peserta::factory()->create();
        $peserta2 = Peserta::factory()->create();

        RehabCaseMember::factory()->create([
            'rehab_case_id' => $case->id,
            'peserta_id' => $peserta1->id,
        ]);

        RehabCaseMember::factory()->create([
            'rehab_case_id' => $case->id,
            'peserta_id' => $peserta2->id,
        ]);

        $this->assertCount(2, $case->fresh()->members);
    }

    public function test_sisa_tunggakan_is_recalculated_when_installments_are_paid()
    {
        $member = RehabCaseMember::factory()->create([
            'tagihan_awal' => 1000000,
            'sisa_tunggakan' => 0,
        ]);

        // Unpaid installment should not reduce sisa_tunggakan
        $unpaid = RehabInstallment::factory()->create([
            'rehab_case_member_id' => $member->id,
            'besaran_cicilan' => 200000,
            'tanggal_bayar' => null,
        ]);

        $this->assertEquals(1000000, $member->fresh()->sisa_tunggakan);

        // Paid installment should reduce sisa_tunggakan
        $paid = RehabInstallment::factory()->create([
            'rehab_case_member_id' => $member->id,
            'besaran_cicilan' => 300000,
            'tanggal_bayar' => now(),
        ]);

        $this->assertEquals(700000, $member->fresh()->sisa_tunggakan);

        // Ensure parent case is also updated
        $this->assertEquals(700000, $member->case->sisa_tunggakan);
    }

    public function test_unique_constraint_on_peserta_batches()
    {
        $peserta = Peserta::factory()->create();
        $batch = Batch::factory()->create();

        PesertaBatch::factory()->create([
            'batch_id' => $batch->id,
            'peserta_id' => $peserta->id,
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/UNIQUE constraint failed/');

        // Attempting to attach the same participant to the same batch again should fail
        PesertaBatch::factory()->create([
            'batch_id' => $batch->id,
            'peserta_id' => $peserta->id,
        ]);
    }
}
