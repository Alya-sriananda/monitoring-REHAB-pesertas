<?php

namespace Database\Factories;

use App\Models\Peserta;
use App\Models\RehabCase;
use Illuminate\Database\Eloquent\Factories\Factory;

class RehabCaseMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rehab_case_id' => RehabCase::factory(),
            'peserta_id' => Peserta::factory(),
            'is_pendaftar' => false,
            'tagihan_awal' => $this->faker->numberBetween(100000, 1000000),
            'sisa_tunggakan' => 0,
            'jml_bulan_menunggak_awal' => $this->faker->numberBetween(2, 24),
            'cicilan_bulanan' => $this->faker->numberBetween(50000, 200000),
            'data_source' => 'excel',
        ];
    }
}
