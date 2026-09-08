<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\RehabCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SippVerificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'peserta_id' => \App\Models\Peserta::factory(),
            'rehab_case_id' => RehabCase::factory(),
            'batch_id' => Batch::factory(),
            'user_id' => User::factory(),
            'tanggal_cek' => $this->faker->date(),
            'terdaftar_rehab' => true,
            'status_rehab' => 'AKTIF',
            'id_cicilan' => $this->faker->uuid(),
            'noka_pendaftar' => $this->faker->numerify('#############'),
            'npp_petugas' => $this->faker->numerify('######'),
            'tanggal_daftar_rehab' => $this->faker->date(),
            'tanggal_akhir_cicilan' => $this->faker->date(),
            'jumlah_peserta_sipp' => $this->faker->numberBetween(1, 5),
            'catatan' => $this->faker->sentence(),
        ];
    }
}
