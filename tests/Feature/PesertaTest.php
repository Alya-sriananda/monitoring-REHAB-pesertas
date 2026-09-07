<?php

namespace Tests\Feature;

use App\Models\Daerah;
use App\Models\Peserta;
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
        
        Peserta::factory()->create(['nama' => 'Budi Susanto', 'noka' => '123456']);
        Peserta::factory()->create(['nama' => 'Andi Wijaya', 'noka' => '987654']);
        
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
        
        $response = $this->actingAs($user)->get('/peserta/' . $peserta->id);
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('peserta/show')
            ->has('peserta')
            ->where('peserta.id', $peserta->id)
        );
    }
}
