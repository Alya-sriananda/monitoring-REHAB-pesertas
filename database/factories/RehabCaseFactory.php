<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Peserta;
use Illuminate\Database\Eloquent\Factories\Factory;

class RehabCaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'peserta_id' => Peserta::factory(),
            'id_cicilan' => $this->faker->unique()->uuid(),
            'noka_pendaftar' => $this->faker->numerify('#############'),
            'tanggal_pendaftaran' => $this->faker->date(),
            'jumlah_bulan_cicilan' => $this->faker->numberBetween(3, 24),
            'sisa_tunggakan' => 0,
            'status_rehab' => 'AKTIF',
            'created_from_batch_id' => Batch::factory(),
        ];
    }
}
