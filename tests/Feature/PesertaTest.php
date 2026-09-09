<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Daerah;
use App\Models\Peserta;
use App\Models\PesertaBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PesertaTest extends TestCase
{
    use RefreshDatabase;

    public function test_peserta_index_can_be_accessed()
    {
        $user = User::factory()->create(['must_change_password' => false, 'aktif' => true]);

        $response = $this->actingAs($user)->get('/peserta');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('peserta/index')
            ->has('pesertas')
        );
    }

    public function test_peserta_search_works()
    {
        $user = User::factory()->create(['must_change_password' => false, 'aktif' => true]);

        $batch = Batch::factory()->create();

        $p1 = Peserta::factory()->create(['nama' => 'Budi Susanto', 'noka' => '123456']);
        $p2 = Peserta::factory()->create(['nama' => 'Andi Wijaya', 'noka' => '987654']);

        PesertaBatch::create(['peserta_id' => $p1->id, 'batch_id' => $batch->id, 'data_source' => 'excel']);
        PesertaBatch::create(['peserta_id' => $p2->id, 'batch_id' => $batch->id, 'data_source' => 'excel']);

        // Search by name
        $response = $this->actingAs($user)->get('/peserta?search=Budi');
        $response->assertInertia(fn (Assert $page) => $page
            ->component('peserta/index')
            ->has('pesertas.data', 1)
            ->where('pesertas.data.0.nama', 'Budi Susanto')
        );

        // Search by noka
        $response = $this->actingAs($user)->get('/peserta?search=987654');
        $response->assertInertia(fn (Assert $page) => $page
            ->component('peserta/index')
            ->has('pesertas.data', 1)
            ->where('pesertas.data.0.nama', 'Andi Wijaya')
        );
    }

    public function test_peserta_detail_can_be_accessed()
    {
        $user = User::factory()->create(['must_change_password' => false, 'aktif' => true]);
        $peserta = Peserta::factory()->create();

        $response = $this->actingAs($user)->get('/peserta/'.$peserta->id);

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('peserta/show')
            ->has('peserta')
            ->where('peserta.id', $peserta->id)
        );
    }

    public function test_peserta_can_be_filtered_by_batch()
    {
        $user = User::factory()->create(['must_change_password' => false, 'aktif' => true]);

        $peserta1 = Peserta::factory()->create(['nama' => 'Peserta Batch 1']);
        $peserta2 = Peserta::factory()->create(['nama' => 'Peserta Batch 2']);

        $batch = Batch::factory()->create();

        PesertaBatch::create([
            'peserta_id' => $peserta1->id,
            'batch_id' => $batch->id,
            'data_source' => 'excel',
        ]);

        $response = $this->actingAs($user)->get('/peserta?batch_id='.$batch->id);

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('peserta/index')
            ->has('pesertas.data', 1)
            ->where('pesertas.data.0.nama', 'Peserta Batch 1')
        );
    }

    public function test_peserta_filter_daerah_only_shows_daerah_with_peserta()
    {
        $user = User::factory()->create(['must_change_password' => false, 'aktif' => true]);

        $daerahWithPeserta = Daerah::factory()->create(['nama' => 'SOLOK']);
        $daerahEmpty = Daerah::factory()->create(['nama' => 'PADANG']);

        Peserta::factory()->create(['nama' => 'Budi', 'daerah_id' => $daerahWithPeserta->id]);

        $response = $this->actingAs($user)->get('/peserta');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('peserta/index')
            ->has('daerahs', 1)
            ->where('daerahs.0.nama', 'SOLOK')
        );
    }
}
